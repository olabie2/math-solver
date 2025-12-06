<?php
namespace App\Services\Math\Solvers;

use App\Services\Math\Complex;
use App\Services\Math\EquationParser;
use App\Services\Math\Polynomial;
use App\Services\Math\Token;
use Exception;

class LinearSolver implements SolverInterface
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
        foreach ($tokens as $token) {
            if ($token->type === Token::T_VARIABLE) $hasVariable = true;
            if ($token->type === Token::T_EQUALS) $hasEquals = true;
        }
        return $hasVariable && $hasEquals;
    }

    public function solve(array $tokens, string $originalExpression): array
    {
        $baseResult = [ 
            'type' => 'linear_equation', 
            'expression' => $originalExpression, 
            'variable' => null, 
            'solution' => null, 
            'steps' => [], 
            'error' => null 
        ];
        
        try {
            $variableName = $this->findVariable($tokens);
            if (!$variableName) {
                throw new Exception("Could not find a variable to solve for.");
            }
            $baseResult['variable'] = $variableName;
            
            $steps = [];
            $steps[] = "Start with the original equation: <math-field readonly>{$originalExpression}</math-field>";

            $sides = $this->parser->parse($tokens);
            
            $lhsPoly = $this->evaluateAsPolynomial($sides['lhs'], $variableName);
            $rhsPoly = $this->evaluateAsPolynomial($sides['rhs'], $variableName);

            $steps[] = "Simplify both sides of the equation to isolate the variable '<math-field readonly>{$variableName}</math-field>'.";
            $steps[] = "Left-Hand Side (LHS): <math-field readonly>" . $lhsPoly->toLatex($variableName) . "</math-field>";
            $steps[] = "Right-Hand Side (RHS): <math-field readonly>" . $rhsPoly->toLatex($variableName) . "</math-field>";

            $rearrangedPoly = $lhsPoly->subtract($rhsPoly);
            $steps[] = "Move all terms to one side (subtract RHS from both sides): <math-field readonly>" . $rearrangedPoly->toLatex($variableName) . " = 0</math-field>";

            $a = $rearrangedPoly->getCoefficient(1);
            $b = $rearrangedPoly->getCoefficient(0);

            // Edge case: coefficient of variable is zero (0x + b = 0)
            if ($a->isZero()) {
                if ($b->isZero()) {
                    // 0x + 0 = 0  →  0 = 0 (infinite solutions)
                    $steps[] = "The equation simplifies to <math-field readonly>0 = 0</math-field>, which is always true.";
                    $steps[] = "<strong>Result:</strong> This equation has <strong>infinitely many solutions</strong>. Any value of <math-field readonly>{$variableName}</math-field> is a solution.";
                    
                    $baseResult['type'] = 'infinite_solutions';
                    $baseResult['solution'] = 'All real numbers';
                    $baseResult['steps'] = $steps;
                    return $baseResult;
                } else {
                    // 0x + 5 = 0  →  5 = 0 (no solution - contradiction)
                    $steps[] = "The equation simplifies to <math-field readonly>" . $b->toLatex() . " = 0</math-field>, which is a contradiction.";
                    $steps[] = "<strong>Result:</strong> This equation has <strong>no solution</strong>. It is inconsistent.";
                    
                    $baseResult['type'] = 'no_solution';
                    $baseResult['solution'] = 'No solution';
                    $baseResult['steps'] = $steps;
                    return $baseResult;
                }
            }

            $solution = $b->negate()->divide($a);
            
            $steps[] = "This is a linear equation in standard form <math-field readonly>A{$variableName} + B = 0</math-field> where <math-field readonly>A = " . $a->toLatex() . "</math-field> and <math-field readonly>B = " . $b->toLatex() . "</math-field>.";
            $steps[] = "Isolate <math-field readonly>{$variableName}</math-field>: Move constant to the other side: <math-field readonly>" . $a->toLatex() . "{$variableName} = " . $b->negate()->toLatex() . "</math-field>";
            $steps[] = "Divide both sides by the coefficient: <math-field readonly>{$variableName} = \\frac{" . $b->negate()->toLatex() . "}{" . $a->toLatex() . "}</math-field>";
            $steps[] = "<strong>Final Answer:</strong> <math-field readonly style='font-size:1.2em;'>{$variableName} = " . $solution->toLatex() . "</math-field>";
            
            $baseResult['solution'] = (string)$solution;
            $baseResult['steps'] = $steps;

        } catch (Exception $e) {
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
                switch(strtolower($token->value)) {
                    case 'pi': $poly = new Polynomial([0 => new Complex(M_PI)]); break;
                    case 'e': $poly = new Polynomial([0 => new Complex(M_E)]); break;
                    case 'i': $poly = new Polynomial([0 => new Complex(0, 1)]); break;
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

    private function findVariable(array $tokens): ?string
    {
        foreach ($tokens as $token) {
            if ($token->type === Token::T_VARIABLE) return $token->value;
        }
        return null;
    }
}