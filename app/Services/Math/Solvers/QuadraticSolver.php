<?php

namespace App\Services\Math\Solvers;

use App\Services\Math\Complex;
use App\Services\Math\EquationParser;
use App\Services\Math\Polynomial;
use App\Services\Math\Token;
use Exception;

class QuadraticSolver implements SolverInterface
{
    private EquationParser $parser;

    public function __construct(EquationParser $parser)
    {
        $this->parser = $parser;
    }

    public function canSolve(array $tokens): bool
    {
        $hasVariable = false;
        $hasEquals = false;
        $hasPowerOfTwo = false;

        foreach ($tokens as $token) {
            if ($token->type === Token::T_VARIABLE) {
                $hasVariable = true;
            }
            if ($token->type === Token::T_EQUALS) {
                $hasEquals = true;
            }
            if ($token->type === Token::T_OPERATOR && $token->value === '^') {
                $next_token_index = array_search($token, $tokens) + 1;
                if (isset($tokens[$next_token_index]) && $tokens[$next_token_index]->type === Token::T_NUMBER && $tokens[$next_token_index]->value === '2') {
                    $prev_token_index = array_search($token, $tokens) - 1;
                    if (isset($tokens[$prev_token_index]) && $tokens[$prev_token_index]->type === Token::T_VARIABLE) {
                        $hasPowerOfTwo = true;
                    }
                }
            }
        }
        return $hasVariable && $hasEquals && $hasPowerOfTwo;
    }

    public function solve(array $tokens, string $originalExpression): array
    {
        $baseResult = ['type' => 'quadratic_equation', 'expression' => $originalExpression, 'variable' => null, 'solutions' => [], 'steps' => [], 'error' => null];

        try {
            $variableName = $this->findVariable($tokens);
            if (!$variableName) throw new Exception("Could not find a variable to solve for.");
            $baseResult['variable'] = $variableName;

            $steps = [];
            $steps[] = "Start with the original equation: <math-field readonly>{$originalExpression}</math-field>";

            $sides = $this->parser->parse($tokens);
            $poly = $this->evaluateAsPolynomial($sides['lhs'], $variableName)->subtract($this->evaluateAsPolynomial($sides['rhs'], $variableName));

            if ($poly->getCoefficient(2)->isZero()) {
                throw new Exception("This is not a quadratic equation.");
            }

            $steps[] = "Rearrange into standard form <math-field readonly>ax^2 + bx + c = 0</math-field>.";
            $steps[] = "Standard Form: <math-field readonly>" . $poly->toLatex($variableName) . " = 0</math-field>";

            $a = $poly->getCoefficient(2);
            $b = $poly->getCoefficient(1);
            $c = $poly->getCoefficient(0);
            $steps[] = "Identify coefficients: <math-field readonly>a = " . $a->toLatex() . ", b = " . $b->toLatex() . ", c = " . $c->toLatex() . "</math-field>.";

            $steps[] = "Use the <strong>Quadratic Formula</strong>:";
            $steps[] = "<math-field readonly style='font-size: 1.2em;'>" . "x = \\frac{-b \\pm \\sqrt{b^2 - 4ac}}{2a}" . "</math-field>";

            $discriminant = $b->pow(new Complex(2))->subtract((new Complex(4))->multiply($a)->multiply($c));
            $steps[] = "Calculate the discriminant <math-field readonly>\\Delta = b^2 - 4ac</math-field> to determine the nature of the roots.";
            $steps[] = "<math-field readonly>\\Delta = (" . $b->toLatex() . ")^2 - 4(" . $a->toLatex() . ")(" . $c->toLatex() . ") = " . $discriminant->toLatex() . "</math-field>";

            $sqrt_discriminant = $discriminant->sqrt();
            $neg_b = $b->negate();
            $two_a = (new Complex(2))->multiply($a);

            if ($discriminant->isZero()) {
                $steps[] = "Since the discriminant is <strong>zero</strong>, there is exactly <strong>one real root</strong>. This also means the original equation is a <strong>perfect square</strong>.";
                $solution = $neg_b->divide($two_a);
                $baseResult['solutions'] = [(string)$solution];
                $steps[] = "The formula simplifies to <math-field readonly>x = \\frac{-b}{2a}</math-field>:";
                $steps[] = "<math-field readonly>x = \\frac{" . $neg_b->toLatex() . "}{" . $two_a->toLatex() . "} = " . $solution->toLatex() . "</math-field>";
            } elseif ($discriminant->real > 0 && $discriminant->imaginary == 0) {
                $steps[] = "Since the discriminant is <strong>positive</strong>, there are <strong>two distinct real roots</strong>.";
                $solution1 = $neg_b->add($sqrt_discriminant)->divide($two_a);
                $solution2 = $neg_b->subtract($sqrt_discriminant)->divide($two_a);
                $baseResult['solutions'] = array_unique([(string)$solution1, (string)$solution2]);
                $steps[] = "The two solutions are:";
                $steps[] = "<math-field readonly>x_1 = \\frac{" . $neg_b->toLatex() . " + " . $sqrt_discriminant->toLatex() . "}{" . $two_a->toLatex() . "} = " . $solution1->toLatex() . "</math-field>";
                $steps[] = "<math-field readonly>x_2 = \\frac{" . $neg_b->toLatex() . " - " . $sqrt_discriminant->toLatex() . "}{" . $two_a->toLatex() . "} = " . $solution2->toLatex() . "</math-field>";
            } else {
                $steps[] = "Since the discriminant is <strong>negative or complex</strong>, there are <strong>two complex roots</strong>.";
                $solution1 = $neg_b->add($sqrt_discriminant)->divide($two_a);
                $solution2 = $neg_b->subtract($sqrt_discriminant)->divide($two_a);
                $baseResult['solutions'] = array_unique([(string)$solution1, (string)$solution2]);
                $steps[] = "The two complex solutions are:";
                $steps[] = "<math-field readonly>x_1 = " . $solution1->toLatex() . "</math-field>";
                $steps[] = "<math-field readonly>x_2 = " . $solution2->toLatex() . "</math-field>";
            }

            $baseResult['steps'] = $steps;
        } catch (Exception $e) {
            if ($e->getMessage() === "This is not a quadratic equation.") {
                return ['type' => 'not_quadratic'];
            }
            $baseResult['error'] = $e->getMessage();
        }
        return $baseResult;
    }

    private function evaluateAsPolynomial(array $rpnTokens, string $variableName): Polynomial
    {
        if (empty($rpnTokens)) return new Polynomial();
        $stack = [];
        $variablePoly = new Polynomial([1 => new Complex(1)]);

        foreach ($rpnTokens as $token) {
            if ($token->type === Token::T_NUMBER) {
                $stack[] = new Polynomial([0 => new Complex((float)$token->value)]);
            } elseif ($token->type === Token::T_CONSTANT) {
                $poly = null;
                switch (strtolower($token->value)) {
                    case 'pi':
                        $poly = new Polynomial([0 => new Complex(M_PI)]);
                        break;
                    case 'e':
                        $poly = new Polynomial([0 => new Complex(M_E)]);
                        break;
                    case 'i':
                        $poly = new Polynomial([0 => new Complex(0, 1)]);
                        break;
                }
                if ($poly) $stack[] = $poly;
            } elseif ($token->type === Token::T_VARIABLE) {
                if ($token->value !== $variableName) throw new Exception("Multi-variable equations are not supported.");
                $stack[] = clone $variablePoly;
            } elseif ($token->type === Token::T_OPERATOR) {
                $op2 = array_pop($stack);
                $op1 = (in_array($token->value, ['neg', 'pos'])) ? null : array_pop($stack);
                $stack[] = $this->applySymbolicOperator($token->value, $op1, $op2);
            }
        }
        return array_pop($stack);
    }

    private function applySymbolicOperator(string $op, ?Polynomial $a, Polynomial $b): Polynomial
    {
        switch ($op) {
            case '+':
                return $a->add($b);
            case '-':
                return $a->subtract($b);
            case '*':
                return $a->multiply($b);
            case '/':
                if (!$b->isConstant()) throw new Exception("Division by a variable is not supported.");
                return $a->multiply(new Polynomial([0 => $b->getCoefficient(0)->inverse()]));
            case '^':
                if (!$b->isConstant()) throw new Exception("Variable exponents are not supported.");
                $exponent = (int)$b->getCoefficient(0)->real;
                return $a->pow($exponent);
            case 'neg':
                return $b->multiply(new Polynomial([0 => new Complex(-1)]));
            case 'pos':
                return $b;
        }
        throw new Exception("Unsupported symbolic operator: $op");
    }

    private function findVariable(array $tokens): ?string
    {
        foreach ($tokens as $token) {
            if ($token->type === Token::T_VARIABLE) return $token->value;
        }
        return null;
    }
}
