<?php

namespace App\Services\Math;

use Exception;

class Polynomial
{
    private array $coefficients;

    public function __construct(array $coefficients = [])
    {
        $this->coefficients = $coefficients;
    }
     
    public function __clone()
    {
        foreach ($this->coefficients as $power => $coeff) {
            $this->coefficients[$power] = clone $coeff;
        }
    }

    public function getCoefficient(int $power): Complex
    {
        return $this->coefficients[$power] ?? new Complex(0);
    }

    public function getCoefficients(): array
    {
        return $this->coefficients;
    }

    public function add(Polynomial $other): Polynomial
    {
        $newCoeffs = $this->coefficients;
        foreach ($other->getCoefficients() as $power => $coeff) {
            $newCoeffs[$power] = ($newCoeffs[$power] ?? new Complex())->add($coeff);
        }
        return new Polynomial($newCoeffs);
    }

    public function subtract(Polynomial $other): Polynomial
    {
        $newCoeffs = $this->coefficients;
        foreach ($other->getCoefficients() as $power => $coeff) {
            $newCoeffs[$power] = ($newCoeffs[$power] ?? new Complex())->subtract($coeff);
        }
        return new Polynomial($newCoeffs);
    }

    public function multiply(Polynomial $other): Polynomial
    {
        $newPoly = new Polynomial();
        foreach ($this->coefficients as $p1 => $c1) {
            foreach ($other->getCoefficients() as $p2 => $c2) {
                $newPoly = $newPoly->add(new Polynomial([$p1 + $p2 => $c1->multiply($c2)]));
            }
        }
        return $newPoly;
    }

    public function pow(int $exponent): Polynomial
    {
        if ($exponent < 0) throw new Exception("Negative exponents on polynomials are not supported.");
        if ($exponent == 0) return new Polynomial([0 => new Complex(1)]);

        $result = $this;
        for ($i = 1; $i < $exponent; $i++) {
            $result = $result->multiply($this);
        }
        return $result;
    }

    public function divide(Polynomial $divisor): Polynomial
    {
        if ($divisor->isZeroPolynomial()) {
            throw new Exception("Division by zero polynomial is undefined.");
        }

        if ($this->isZeroPolynomial()) {
            return new Polynomial();
        }

        $quotient = new Polynomial();
        $remainder = clone $this;

        while (!$remainder->isZeroPolynomial() && $remainder->getDegree() >= $divisor->getDegree()) {
            $remainderLeadingCoeff = $remainder->getLeadingCoefficient();
            $remainderLeadingPower = $remainder->getDegree();

            $divisorLeadingCoeff = $divisor->getLeadingCoefficient();
            $divisorLeadingPower = $divisor->getDegree();

            $termCoeff = $remainderLeadingCoeff->divide($divisorLeadingCoeff);
            $termPower = $remainderLeadingPower - $divisorLeadingPower;

            $term = new Polynomial([$termPower => $termCoeff]);

            $quotient = $quotient->add($term);

            $remainder = $remainder->subtract($term->multiply($divisor));
        }

        if (!$remainder->isZeroPolynomial()) {
            throw new Exception("Polynomial division resulted in a non-zero remainder. Not a clean division.");
        }

        return $quotient;
    }

    public function getDegree(): int
    {
        $degree = 0;
        foreach ($this->coefficients as $power => $coeff) {
            if (!$coeff->isZero() && $power > $degree) {
                $degree = $power;
            }
        }
        return $degree;
    }

    public function getLeadingCoefficient(): Complex
    {
        return $this->getCoefficient($this->getDegree());
    }

    public function isZeroPolynomial(): bool
    {
        foreach ($this->coefficients as $coeff) {
            if (!$coeff->isZero()) {
                return false;
            }
        }
        return true;
    }

    public function isConstant(): bool
    {
        foreach ($this->coefficients as $power => $coeff) {
            if ($power > 0 && !$coeff->isZero()) {
                return false;
            }
        }
        return true;
    }
    
    public function toLatex(string $variableName): string
    {
        $parts = [];
        $coeffs = $this->coefficients;
        krsort($coeffs);

        if (empty($coeffs) || array_reduce($coeffs, fn($carry, $c) => $carry && $c->isZero(), true)) {
            return '0';
        }

        foreach ($coeffs as $power => $coeff) {
            if ($coeff->isZero()) continue;

            $part = '';
            $isFirstPart = empty($parts);
            $coeffForSign = $coeff->real !== 0 ? $coeff->real : $coeff->imaginary;

            if (!$isFirstPart) {
                $part .= $coeffForSign < 0 ? ' - ' : ' + ';
            } elseif ($coeffForSign < 0) {
                $part .= '-';
            }

            $absCoeff = new Complex(abs($coeff->real), abs($coeff->imaginary));
            $coeffStr = $absCoeff->toLatex();

            if ($power > 0) {
                if ($coeffStr !== '1') {
                    $part .= $coeffStr;
                }
            } else {
                $part .= $coeffStr;
            }

            if ($power > 0) {
                $part .= $variableName;
                if ($power > 1) {
                    $part .= '^' . '{' . $power . '}';
                }
            }
            $parts[] = $part;
        }

        return ltrim(implode('', $parts), ' +');
    }


    public function toString(string $variableName): string
    {
        $parts = [];
        $coeffs = $this->coefficients;
        krsort($coeffs);

        if (empty($coeffs) || array_reduce($coeffs, fn($carry, $c) => $carry && $c->isZero(), true)) {
            return '0';
        }

        foreach ($coeffs as $power => $coeff) {
            if ($coeff->isZero()) continue;

            $part = '';
            $isFirstPart = empty($parts);

            $sign = '';
            $coeffForSign = $coeff->real !== 0 ? $coeff->real : $coeff->imaginary;
            if (!$isFirstPart) {
                $sign = $coeffForSign < 0 ? ' - ' : ' + ';
            } elseif ($coeffForSign < 0) {
                $sign = '-';
            }
            $part .= $sign;

            $absCoeff = new Complex(abs($coeff->real), abs($coeff->imaginary));
            $coeffStr = (string)$absCoeff;

            if ($power > 0) {
                if ($coeffStr !== '1' || $absCoeff->imaginary != 0) {
                    $part .= $coeffStr;
                }
            } else {
                $part .= $coeffStr;
            }

            if ($power > 0) {
                $part .= $variableName;
                if ($power > 1) {
                    $part .= '^' . $power;
                }
            }
            $parts[] = $part;
        }

        return ltrim(implode('', $parts), ' +');
    }
}