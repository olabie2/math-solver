<?php
namespace App\Services\Math\Solvers;

use App\Services\Math\Complex;
use App\Services\Math\Parser;
use App\Services\Math\Polynomial;
use App\Services\Math\Token;
use Exception;

class ExpressionSimplifierSolver implements SolverInterface
{
    private ?string $variableName = null;

    public function canSolve(array $tokens): bool
    {
        $hasVariable = false;
        $hasEquals = false;
        foreach ($tokens as $token) {
            if ($token->type === Token::T_VARIABLE) {
                $hasVariable = true;
            }
            if ($token->type === Token::T_EQUALS) {
                $hasEquals = true;
            }
        }
        return $hasVariable && !$hasEquals;
    }

    public function solve(array $tokens, string $originalExpression): array
    {
        $baseResult = [ 'type' => 'expression_simplification', 'expression' => $originalExpression, 'result' => null, 'steps' => [], 'error' => null ];
        
        try {
            $steps = [];
            $steps[] = "Starting expression: <strong>{$originalExpression}</strong>";

            $this->variableName = $this->validateAndFindVariable($tokens);

            $polynomial = $this->checkForSpecialPatterns($tokens, $steps);
            
            if (!$polynomial) {
                $steps[] = "This expression does not match a known algebraic formula. Simplifying by expanding and combining like terms.";
                $polynomial = $this->evaluateSymbolicRpn($tokens);
            }
            
            $finalResult = $this->polynomialToString($polynomial);
            $steps[] = "The simplified expression is: <strong>{$finalResult}</strong>";
            
            $baseResult['result'] = $finalResult;
            $baseResult['steps'] = $steps;

            $tokenizer = new \App\Services\Math\Tokenizer();
            $baseResult['simplified_tokens'] = $tokenizer->tokenize($finalResult);

        } catch (Exception $e) {
            $baseResult['error'] = $e->getMessage();
        }
        return $baseResult;
    }

    private function checkForSpecialPatterns(array $tokens, array &$steps): ?Polynomial
    {
        $tokenCount = count($tokens);
        if ($tokenCount >= 5 &&
            $tokens[0]->type === Token::T_LPAREN &&
            $tokens[$tokenCount - 3]->type === Token::T_RPAREN &&
            $tokens[$tokenCount - 2]->type === Token::T_OPERATOR && $tokens[$tokenCount - 2]->value == '^' &&
            $tokens[$tokenCount - 1]->type === Token::T_NUMBER && $tokens[$tokenCount - 1]->value == '2'
        ) {
            $insideTokens = array_slice($tokens, 1, $tokenCount - 4);
            $insidePoly = $this->evaluateSymbolicRpn($insideTokens);
            
            if (count($insidePoly->getCoefficients()) <= 2 && !$insidePoly->getCoefficient(1)->isZero()) {
                $aPoly = new Polynomial([1 => $insidePoly->getCoefficient(1)]);
                $bPoly = new Polynomial([0 => $insidePoly->getCoefficient(0)]);

                $steps[] = "This expression matches the <strong>Perfect Square Trinomial</strong> formula: <i>(a+b)² = a² + 2ab + b²</i>.";
                $steps[] = "In this case, term 'a' is <strong>" . $aPoly->toString($this->variableName) . "</strong> and term 'b' is <strong>" . $bPoly->toString($this->variableName) . "</strong>.";
                
                $a_squared = $aPoly->pow(2)->toString($this->variableName);
                $two_ab = $aPoly->multiply($bPoly)->multiply(new Polynomial([0=>new Complex(2)]))->toString($this->variableName);
                $b_squared = $bPoly->pow(2)->toString($this->variableName);
                
                $steps[] = "Applying the formula: (a)² + 2(a)(b) + (b)²";
                $steps[] = "Result: <strong>{$a_squared} + {$two_ab} + {$b_squared}</strong>";
                
                return $insidePoly->pow(2);
            }
        }
        
        return null;
    }

    private function polynomialToString(Polynomial $poly): string
    {
        $parts = [];
        $coeffs = $poly->getCoefficients();
        krsort($coeffs);

        if (empty($coeffs)) return '0';

        foreach ($coeffs as $power => $coeff) {
            if ($coeff->isZero()) continue;

            $part = '';
            $isFirstPart = empty($parts);
            
            $originalCoeff = clone $coeff;
            $absCoeff = new Complex(abs($coeff->real), abs($coeff->imaginary));
            
            $sign = '';
            if (!$isFirstPart) {
                if ($originalCoeff->real != 0) {
                    $sign = $originalCoeff->real < 0 ? ' - ' : ' + ';
                } else {
                    $sign = $originalCoeff->imaginary < 0 ? ' - ' : ' + ';
                }
            } elseif ($originalCoeff->real < 0 || ($originalCoeff->real == 0 && $originalCoeff->imaginary < 0)) {
                $sign = '-';
            }

            $part .= $sign;
            $coeffStr = (string)$absCoeff;

            if ($power > 0) {
                if ($coeffStr !== '1' || ($absCoeff->real == 1 && $absCoeff->imaginary != 0)) {
                    $part .= $coeffStr;
                }
            } else {
                $part .= $coeffStr;
            }

            if ($power > 0) {
                $part .= $this->variableName;
                if ($power > 1) {
                    $part .= '^' . $power;
                }
            }
            $parts[] = $part;
        }
        return ltrim(implode('', $parts), ' +');
    }

    private function evaluateSymbolicRpn(array $tokens): Polynomial
    {
        if (empty($tokens)) return new Polynomial();
        $rpn = (new Parser())->parse($tokens);
        $stack = [];
        $variablePoly = new Polynomial([1 => new Complex(1)]);

        foreach ($rpn as $token) {
            if ($token->type === Token::T_NUMBER) {
                $stack[] = new Polynomial([0 => new Complex((float)$token->value)]);
            } elseif ($token->type === Token::T_CONSTANT) {
                $poly = null;
                switch (strtolower($token->value)) {
                    case 'pi': $poly = new Polynomial([0 => new Complex(M_PI)]); break;
                    case 'e': $poly = new Polynomial([0 => new Complex(M_E)]); break;
                    case 'i': $poly = new Polynomial([0 => new Complex(0, 1)]); break;
                }
                if ($poly) $stack[] = $poly;
            } elseif ($token->type === Token::T_VARIABLE) {
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
            case '+': return $a->add($b);
            case '-': return $a->subtract($b);
            case '*': return $a->multiply($b);
            case '/': return $a->divide($b);
            case '^': 
                if (!$b->isConstant()) throw new Exception("Exponent must be a constant.");
                $exponent = (int)$b->getCoefficient(0)->real;
                return $a->pow($exponent);
            case 'neg': return $b->multiply(new Polynomial([0 => new Complex(-1)]));
            case 'pos': return $b;
        }
        throw new Exception("Unsupported symbolic operator: $op");
    }

    private function validateAndFindVariable(array $tokens): string
    {
        $variablesFound = [];
        foreach ($tokens as $token) {
            if ($token->type === Token::T_VARIABLE) {
                $variablesFound[$token->value] = true;
            }
        }

        $uniqueVariables = array_keys($variablesFound);
        
        if (count($uniqueVariables) === 0) {
            throw new Exception("No variable found to simplify around.");
        }

        if (count($uniqueVariables) > 1) {
            throw new Exception("Simplification of expressions with multiple variables (e.g., x, y) is not supported.");
        }

        return $uniqueVariables[0];
    }
}