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
        $real = $this->real;
        $imag = $this->imaginary;

        if ($imag == 0) return (string)$real;
        if ($real == 0) {
            if ($imag == 1) return 'i';
            if ($imag == -1) return '-i';
            return $imag . 'i';
        }

        $sign = $imag < 0 ? ' - ' : ' + ';
        $imagAbs = abs($imag);
        $imagStr = ($imagAbs == 1) ? 'i' : $imagAbs . 'i';

        return "({$real}{$sign}{$imagStr})";
    }
    public function isZero(): bool
    {
        return $this->real == 0.0 && $this->imaginary == 0.0;
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
        $real = round($this->real, 5);
        $imag = round($this->imaginary, 5);

        if ($imag == 0) {
            return (string)$real;
        }

        if ($real == 0) {
            if ($imag == 1) return 'i';
            if ($imag == -1) return '-i';
            return $imag . 'i';
        }

        $sign = $imag < 0 ? ' - ' : ' + ';
        $imagAbs = abs($imag);

        $imagStr = ($imagAbs == 1) ? 'i' : $imagAbs . 'i';

        return $real . $sign . $imagStr;
    }
}