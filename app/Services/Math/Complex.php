<?php

namespace App\Services\Math;

use Exception;

class Complex
{
    public float $real;
    public float $imaginary;

    public function __construct(float $real = 0.0, float $imaginary = 0.0)
    {
        $this->real = $real;
        $this->imaginary = $imaginary;
    }

    public function toLatex(): string
    {
        $real = $this->formatNumberLatex($this->real);
        $imag = $this->formatNumberLatex($this->imaginary);
        $imagFloat = $this->imaginary;

        if (abs($imagFloat) < 1e-10) return $real;
        if (abs($this->real) < 1e-10) {
            if (abs($imagFloat - 1) < 1e-10) return 'i';
            if (abs($imagFloat + 1) < 1e-10) return '-i';
            return $imag . 'i';
        }

        $sign = $imagFloat < 0 ? ' - ' : ' + ';
        $imagAbs = abs($imagFloat);
        $imagStr = (abs($imagAbs - 1) < 1e-10) ? 'i' : $this->formatNumberLatex($imagAbs) . 'i';

        return "({$real}{$sign}{$imagStr})";
    }
    
    /**
     * Check if this complex number is effectively zero (within precision threshold)
     */
    public function isZero(): bool
    {
        return abs($this->real) < 1e-10 && abs($this->imaginary) < 1e-10;
    }

    public function inverse(): Complex
    {
        $denominator = ($this->real ** 2) + ($this->imaginary ** 2);
        if ($denominator == 0) {
            return new Complex(NAN, NAN);
        }

        return new Complex(
            $this->real / $denominator,
            -$this->imaginary / $denominator
        );
    }

    public function add(Complex $other): Complex
    {
        return new Complex($this->real + $other->real, $this->imaginary + $other->imaginary);
    }

    public function subtract(Complex $other): Complex
    {
        return new Complex($this->real - $other->real, $this->imaginary - $other->imaginary);
    }

    public function multiply(Complex $other): Complex
    {
        return new Complex(
            ($this->real * $other->real) - ($this->imaginary * $other->imaginary),
            ($this->real * $other->imaginary) + ($this->imaginary * $other->real)
        );
    }

    public function divide(Complex $other): Complex
    {
        $denominator = ($other->real ** 2) + ($other->imaginary ** 2);
        if ($denominator == 0) return new Complex(NAN, NAN);

        return new Complex(
            (($this->real * $other->real) + ($this->imaginary * $other->imaginary)) / $denominator,
            (($this->imaginary * $other->real) - ($this->real * $other->imaginary)) / $denominator
        );
    }

    public function pow(Complex $exponent): Complex
    {
        if ($exponent->imaginary == 0 && $exponent->real == floor($exponent->real)) {
            $exp = (int)$exponent->real;

            if ($exp == 0) {
                return new Complex(1);
            }

            if ($exp > 0) {
                $result = clone $this;
                for ($i = 1; $i < $exp; $i++) {
                    $result = $result->multiply($this);
                }
                return $result;
            }

            if ($exp < 0) {
                $result = clone $this;
                for ($i = 1; $i < abs($exp); $i++) {
                    $result = $result->multiply($this);
                }
                return $result->inverse();
            }
        }

        if ($this->isZero() && $exponent->isZero()) return new Complex(1);
        if ($this->isZero()) return new Complex(0);
        return $this->log()->multiply($exponent)->exp();
    }

    public function log(): Complex
    {
        if ($this->isZero()) {
            throw new Exception("Logarithm of zero is undefined.");
        }
        return new Complex(log($this->magnitude()), $this->argument());
    }

    public function exp(): Complex
    {
        $exp_real = exp($this->real);
        return new Complex(
            $exp_real * cos($this->imaginary),
            $exp_real * sin($this->imaginary)
        );
    }

    public function sin(): Complex
    {
        return new Complex(
            sin($this->real) * cosh($this->imaginary),
            cos($this->real) * sinh($this->imaginary)
        );
    }

    public function cos(): Complex
    {
        return new Complex(
            cos($this->real) * cosh($this->imaginary),
            -sin($this->real) * sinh($this->imaginary)
        );
    }

    public function tan(): Complex
    {
        return $this->sin()->divide($this->cos());
    }

    public function sqrt(): Complex
    {
        $magnitude = $this->magnitude();
        $realPart = sqrt(($magnitude + $this->real) / 2);
        $imaginaryPart = ($this->imaginary < 0 ? -1 : 1) * sqrt(($magnitude - $this->real) / 2);
        return new Complex($realPart, $imaginaryPart);
    }

    public function negate(): Complex
    {
        return new Complex(-$this->real, -$this->imaginary);
    }

    public function magnitude(): float
    {
        return sqrt(($this->real ** 2) + ($this->imaginary ** 2));
    }

    public function argument(): float
    {
        return atan2($this->imaginary, $this->real);
    }

    public function isEqualTo(Complex $other): bool
    {
        return $this->real === $other->real && $this->imaginary === $other->imaginary;
    }

    public function __toString(): string
    {
        $real = $this->formatNumber($this->real);
        $imag = $this->formatNumber($this->imaginary);
        $imagFloat = (float)$imag;

        if ($imagFloat == 0) {
            return $real;
        }

        if ((float)$real == 0) {
            if ($imagFloat == 1) return 'i';
            if ($imagFloat == -1) return '-i';
            return $imag . 'i';
        }

        $sign = $imagFloat < 0 ? ' - ' : ' + ';
        $imagAbs = abs($imagFloat);

        $imagStr = ($imagAbs == 1) ? 'i' : $this->formatNumber($imagAbs) . 'i';

        return $real . $sign . $imagStr;
    }
    
    /**
     * Format a number cleanly: remove trailing zeros, handle -0, detect integers
     */
    private function formatNumber(float $value): string
    {
        // Handle near-zero case
        if (abs($value) < 1e-10) {
            return '0';
        }
        
        // Round to reasonable precision (10 decimal places)
        $rounded = round($value, 10);
        
        // Check if it's effectively an integer
        if (abs($rounded - round($rounded)) < 1e-9) {
            return (string)(int)round($rounded);
        }
        
        // Format with up to 10 decimal places, then trim trailing zeros
        $formatted = rtrim(rtrim(sprintf('%.10f', $rounded), '0'), '.');
        
        return $formatted;
    }
    
    /**
     * Format a number for LaTeX output with fraction detection
     */
    private function formatNumberLatex(float $value): string
    {
        // Handle near-zero case
        if (abs($value) < 1e-10) {
            return '0';
        }
        
        $sign = $value < 0 ? '-' : '';
        $absValue = abs($value);
        
        // Check if it's effectively an integer
        $rounded = round($absValue, 10);
        if (abs($rounded - round($rounded)) < 1e-9) {
            return $sign . (string)(int)round($rounded);
        }
        
        // Try to find a simple fraction representation (denominator up to 12)
        $fraction = $this->tryFraction($absValue);
        if ($fraction !== null) {
            return $sign . $fraction;
        }
        
        // Fall back to decimal, but limit to 6 significant figures for readability
        $formatted = rtrim(rtrim(sprintf('%.6f', $rounded), '0'), '.');
        return $sign . $formatted;
    }
    
    /**
     * Try to express a decimal as a simple fraction
     * Returns LaTeX fraction string or null if no simple fraction found
     */
    private function tryFraction(float $value): ?string
    {
        // Common denominators to check
        $denominators = [2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 16, 100];
        
        foreach ($denominators as $denom) {
            $numerator = $value * $denom;
            $roundedNum = round($numerator);
            
            // Check if multiplying by denominator gives an integer
            if (abs($numerator - $roundedNum) < 1e-8 && $roundedNum != 0) {
                // Simplify the fraction
                $gcd = $this->gcd((int)abs($roundedNum), $denom);
                $num = (int)($roundedNum / $gcd);
                $den = $denom / $gcd;
                
                if ($den == 1) {
                    return (string)$num;
                }
                return "\\frac{" . abs($num) . "}{" . $den . "}";
            }
        }
        
        return null;
    }
    
    /**
     * Greatest common divisor
     */
    private function gcd(int $a, int $b): int
    {
        while ($b != 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }
        return $a;
    }
}