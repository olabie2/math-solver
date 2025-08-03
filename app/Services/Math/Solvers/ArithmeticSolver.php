<?php
namespace App\Services\Math\Solvers;

use App\Services\Math\Complex;
use App\Services\Math\Parser;
use App\Services\Math\Token;
use Exception;

/**
 * Solves arithmetic expressions and generates a step-by-step log of the evaluation.
 */
class ArithmeticSolver implements SolverInterface
{
    private Parser $parser;
    
    private const FUNCTION_ARITY = [
        'sqrt' => 1, 'sin'  => 1, 'cos'  => 1, 'tan'  => 1, 'log'  => 2,
    ];

    public function __construct(Parser $parser) {
        $this->parser = $parser;
    }
    
    public function canSolve(array $tokens): bool
    {
        foreach ($tokens as $token) {
            if ($token->type === Token::T_VARIABLE || $token->type === Token::T_EQUALS) {
                return false;
            }
        }
        return true;
    }

    /**
     * Orchestrates the solving process and adds the generated steps to the result.
     */
    public function solve(array $tokens, string $originalExpression): array
    {
        // Add a new 'steps' array to the base result.
        $baseResult = [ 'type' => 'arithmetic_evaluation', 'expression' => $originalExpression, 'result' => null, 'steps' => [], 'error' => null ];
        
        try {
            $steps = []; // This array will hold our log.
            $result = $this->evaluate($tokens, $steps); // Pass $steps by reference.
            
            $baseResult['result'] = (string)$result;
            $baseResult['steps'] = $steps;

        } catch (Exception $e) {
            $baseResult['error'] = $e->getMessage();
        }
        return $baseResult;
    }

    /**
     * Evaluates the expression and populates a steps array that explains the process.
     * @param array $tokens The tokens to evaluate.
     * @param array &$steps An array passed by reference to be filled with step-by-step explanations.
     * @return Complex The final result of the evaluation.
     */
    public function evaluate(array $tokens, array &$steps = []): Complex
    {
        $rpnQueue = $this->parser->parse($tokens);
        
        // Initial logging
        $rpnString = implode(' ', array_map(fn($t) => $t->value, $rpnQueue));
        $steps[] = "1. Parse expression into Reverse Polish Notation (RPN): " . $rpnString;
        $stepCounter = 2;

        $stack = [];
        foreach ($rpnQueue as $token) {
            if (in_array($token->type, [Token::T_NUMBER, Token::T_CONSTANT])) {
                $stack[] = $this->tokenToComplex($token);
            } elseif ($token->type === Token::T_OPERATOR) {
                $isUnary = in_array($token->value, ['neg', 'pos']);
                if (count($stack) < ($isUnary ? 1 : 2)) throw new Exception('Syntax Error');
                
                $op2 = array_pop($stack);
                $op1 = $isUnary ? null : array_pop($stack);
                
                $result = $this->applyOperator($token->value, $op1, $op2);
                
                // Log the operation
                if ($isUnary) {
                    $steps[] = "{$stepCounter}. Apply unary operator '{$token->value}' to {$op2}: {$result}";
                } else {
                    $steps[] = "{$stepCounter}. Calculate {$op1} {$token->value} {$op2}: {$result}";
                }
                $stepCounter++;
                
                $stack[] = $result;

            } elseif ($token->type === Token::T_FUNCTION) {
                $funcName = $token->value;
                if (!isset(self::FUNCTION_ARITY[$funcName])) throw new Exception("Unknown function '$funcName'");
                
                $arity = self::FUNCTION_ARITY[$funcName];
                if (count($stack) < $arity) throw new Exception("Not enough arguments for '$funcName'");
                
                $args = [];
                for ($i = 0; $i < $arity; $i++) $args[] = array_pop($stack);
                $args = array_reverse($args);

                $result = $this->applyFunction($funcName, $args);

                // Log the function application
                $argString = implode(', ', $args);
                $steps[] = "{$stepCounter}. Apply function {$funcName}({$argString}): {$result}";
                $stepCounter++;

                $stack[] = $result;
            }
        }
        
        if (count($stack) !== 1) throw new Exception('Malformed expression.');
        
        $finalResult = array_pop($stack);
        $steps[] = "Final Answer: " . $finalResult;
        return $finalResult;
    }
    
    private function applyOperator(string $op, ?Complex $a, Complex $b): Complex
    {
        switch ($op) {
            case '+': return $a->add($b);
            case '-': return $a->subtract($b);
            case '*': return $a->multiply($b);
            case '/': return $a->divide($b);
            case '^': return $a->pow($b);
            case 'neg': return $b->negate();
            case 'pos': return $b;
            
            // NEW: Handle the 'degree' operator.
            // It's a unary operator, so it only uses the second operand ($b).
            case 'degree':
                // The degree operator is only meaningful for real numbers.
                if ($b->imaginary != 0) {
                    throw new Exception("The degree operator can only be applied to real numbers.");
                }
                // Convert the real part to radians and return a new Complex number.
                return new Complex(deg2rad($b->real));
        }
        throw new Exception("Internal Error: Unknown operator '$op'");
    }

    private function applyFunction(string $funcName, array $args): Complex
    {
        switch ($funcName) {
            case 'sqrt': return $args[0]->sqrt();
            case 'sin':  return $args[0]->sin();
            case 'cos':  return $args[0]->cos();
            case 'tan':  return $args[0]->tan();
            case 'log':
                $number = $args[0];
                $base = $args[1];
                return $number->log()->divide($base->log());
        }
        throw new Exception("Internal Error: Unknown function '$funcName'");
    }
    
    private function tokenToComplex(Token $token): Complex
    {
        if ($token->type === Token::T_CONSTANT) {
            if ($token->value === 'e') return new Complex(M_E);
            if ($token->value === 'i') return new Complex(0, 1);
            if ($token->value === 'pi') return new Complex(M_PI);
        }
        return new Complex((float)$token->value);
    }
}