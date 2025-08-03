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
        $baseResult = [ 'type' => 'linear_equation', 'expression' => $originalExpression, 'variable' => null, 'solution' => null, 'steps' => [], 'error' => null ];
        
        try {
            $variableName = $this->findVariable($tokens);
            if (!$variableName) {
                throw new Exception("Could not find a variable to solve for.");
            }
            $baseResult['variable'] = $variableName;
            
            $steps = [];
            $steps[] = "Start with the original equation: <strong>{$originalExpression}</strong>";

            $sides = $this->parser->parse($tokens);
            
            $lhsPoly = $this->evaluateAsPolynomial($sides['lhs'], $variableName);
            $rhsPoly = $this->evaluateAsPolynomial($sides['rhs'], $variableName);

            $steps[] = "The goal is to isolate the variable '{$variableName}'. First, we simplify both sides of the equation.";
            $steps[] = "The Left-Hand Side (LHS) simplifies to: <strong>" . $lhsPoly->toString($variableName) . "</strong>";
            $steps[] = "The Right-Hand Side (RHS) simplifies to: <strong>" . $rhsPoly->toString($variableName) . "</strong>";

            $rearrangedPoly = $lhsPoly->subtract($rhsPoly);
            $steps[] = "Next, we move all terms to one side to set the equation to zero. This gives us the standard form <i>Ax + B = 0</i>.";
            $steps[] = "Rearranged equation: <strong>" . $rearrangedPoly->toString($variableName) . " = 0</strong>";

            $a = $rearrangedPoly->getCoefficient(1);
            $b = $rearrangedPoly->getCoefficient(0);

            if ($a->isZero()) {
                if ($b->isZero()) throw new Exception("This equation simplifies to 0 = 0, which means there are infinite solutions.");
                else throw new Exception("This equation is a contradiction (e.g., 5 = 0), which means there is no solution.");
            }

            $solution = $b->negate()->divide($a);
            $steps[] = "Now, we isolate '{$variableName}'. We move the constant term to the other side: <strong>" . $a . $variableName . " = " . $b->negate() . "</strong>";
            $steps[] = "Finally, we divide by the coefficient of '{$variableName}' to find the solution.";
            $steps[] = "{$variableName} = (" . $b->negate() . ") / (" . $a . ")";
            
            $baseResult['solution'] = (string)$solution;
            $baseResult['steps'] = $steps;
            $baseResult['steps'][] = "The final answer is: <strong>{$variableName} = {$solution}</strong>";

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