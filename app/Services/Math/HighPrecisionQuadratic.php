<?php
namespace App\Services\Math;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * High-precision quadratic formula solver using brick/math library.
 * This avoids floating-point precision issues for edge cases like:
 * x² - 2x + 1e-12 = 0 (near-perfect square)
 */
class HighPrecisionQuadratic
{
    private const PRECISION = 50;  // 50 significant digits
    
    /**
     * Solve ax² + bx + c = 0 with high precision
     * Returns array of two roots as strings, or null if complex roots
     * 
     * @param float $a Coefficient of x²
     * @param float $b Coefficient of x
     * @param float $c Constant term
     * @return array|null Array of [root1, root2] as strings, or null for complex
     */
    public static function solveReal(float $a, float $b, float $c): ?array
    {
        try {
            // Convert to BigDecimal with high precision
            $aD = BigDecimal::of((string)$a);
            $bD = BigDecimal::of((string)$b);
            $cD = BigDecimal::of((string)$c);
            
            // Calculate discriminant: Δ = b² - 4ac
            $bSquared = $bD->multipliedBy($bD);
            $fourAC = $aD->multipliedBy($cD)->multipliedBy(BigDecimal::of(4));
            $discriminant = $bSquared->minus($fourAC);
            
            // Check if discriminant is negative (complex roots)
            if ($discriminant->isNegative()) {
                return null;  // Complex roots - use standard method
            }
            
            // High-precision square root using Newton-Raphson
            $sqrtD = self::sqrt($discriminant, self::PRECISION);
            
            // Use numerically stable formula:
            // Instead of x = (-b ± √Δ) / 2a which has cancellation issues,
            // we use:
            // x₁ = (-b - sign(b)*√Δ) / 2a  (the larger magnitude root)
            // x₂ = c / (a*x₁)               (Vieta's formula)
            
            $twoA = $aD->multipliedBy(BigDecimal::of(2));
            $negB = $bD->negated();
            
            // Determine sign of b
            if ($bD->isZero()) {
                // b = 0: roots are ±√Δ/2a
                $root1 = $sqrtD->dividedBy($twoA, self::PRECISION, RoundingMode::HALF_UP);
                $root2 = $root1->negated();
            } elseif ($bD->isPositive()) {
                // b > 0: x₁ = (-b - √Δ) / 2a (negative, larger magnitude)
                $root1 = $negB->minus($sqrtD)->dividedBy($twoA, self::PRECISION, RoundingMode::HALF_UP);
                // x₂ = c / (a * x₁)
                $root2 = $cD->dividedBy($aD->multipliedBy($root1), self::PRECISION, RoundingMode::HALF_UP);
            } else {
                // b < 0: x₁ = (-b + √Δ) / 2a (positive, larger magnitude)
                $root1 = $negB->plus($sqrtD)->dividedBy($twoA, self::PRECISION, RoundingMode::HALF_UP);
                // x₂ = c / (a * x₁)
                $root2 = $cD->dividedBy($aD->multipliedBy($root1), self::PRECISION, RoundingMode::HALF_UP);
            }
            
            return [
                self::formatResult($root1),
                self::formatResult($root2)
            ];
            
        } catch (\Exception $e) {
            return null;  // Fall back to standard method
        }
    }
    
    /**
     * Newton-Raphson square root for BigDecimal
     */
    private static function sqrt(BigDecimal $n, int $precision): BigDecimal
    {
        if ($n->isZero()) {
            return BigDecimal::zero();
        }
        
        if ($n->isNegative()) {
            throw new \InvalidArgumentException("Cannot compute sqrt of negative number");
        }
        
        // Initial guess: use PHP's float sqrt (convert BigDecimal to string first)
        $floatVal = (float)(string)$n->toScale(15, RoundingMode::HALF_UP);
        $floatGuess = sqrt($floatVal);
        $guess = BigDecimal::of((string)$floatGuess);
        
        // Newton-Raphson iterations: x_{n+1} = (x_n + n/x_n) / 2
        $two = BigDecimal::of(2);
        $epsilon = BigDecimal::of('1e-' . ($precision - 5));
        
        for ($i = 0; $i < 100; $i++) {
            $newGuess = $guess->plus($n->dividedBy($guess, $precision + 10, RoundingMode::HALF_UP))
                              ->dividedBy($two, $precision + 10, RoundingMode::HALF_UP);
            
            $diff = $newGuess->minus($guess)->abs();
            if ($diff->compareTo($epsilon) <= 0) {
                return $newGuess->toScale($precision, RoundingMode::HALF_UP);
            }
            
            $guess = $newGuess;
        }
        
        return $guess->toScale($precision, RoundingMode::HALF_UP);
    }
    
    /**
     * Format result: preserve significant precision
     */
    private static function formatResult(BigDecimal $value): string
    {
        $absValue = $value->abs();
        
        // For effectively zero values
        if ($absValue->compareTo(BigDecimal::of('1e-20')) < 0) {
            return '0';
        }
        
        // Check if it's very close to an integer
        // Use extremely tight tolerance (1e-15) to avoid losing precision
        $rounded = $value->toScale(0, RoundingMode::HALF_UP);
        $diff = $value->minus($rounded)->abs();
        
        // Only round to integer if the difference is truly negligible (< 1e-15)
        // This preserves answers like 1.9999999999995 instead of rounding to 2
        if ($diff->compareTo(BigDecimal::of('1e-15')) < 0) {
            return (string)$rounded;
        }
        
        // For very small numbers (less than 1e-6), use LaTeX scientific notation
        if ($absValue->compareTo(BigDecimal::of('1e-6')) < 0) {
            // Convert to scientific notation using LaTeX format: n \times 10^{exp}
            // This avoids confusion between scientific 'e' and Euler's number 'e'
            $str = (string)$value->toScale(20, RoundingMode::HALF_UP);
            $floatVal = (float)$str;
            
            // Calculate mantissa and exponent manually
            if ($floatVal == 0) {
                return '0';
            }
            
            $sign = $floatVal < 0 ? '-' : '';
            $absFloat = abs($floatVal);
            $exponent = (int)floor(log10($absFloat));
            $mantissa = $absFloat / pow(10, $exponent);
            
            // Format mantissa with appropriate precision
            $mantissaStr = rtrim(rtrim(sprintf('%.6f', $mantissa), '0'), '.');
            
            return $sign . $mantissaStr . ' \\times 10^{' . $exponent . '}';
        }
        
        // For normal numbers, show up to 13 decimal places for near-integer precision
        $scale = 13;
        $str = (string)$value->toScale($scale, RoundingMode::HALF_UP);
        
        // Remove trailing zeros after decimal point
        if (strpos($str, '.') !== false) {
            $str = rtrim(rtrim($str, '0'), '.');
        }
        
        return $str;
    }
}
