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
        $baseResult = [ 
            'type' => 'arithmetic_evaluation', 
            'expression' => $originalExpression, 
            'result' => null, 
            'steps' => [], 
            'error' => null 
        ];
        
        try {
            $steps = [];
            $result = $this->evaluate($tokens, $steps);
            
            $baseResult['result'] = (string)$result;
            $baseResult['steps'] = $steps;

        } catch (Exception $e) {
            $baseResult['error'] = $e->getMessage();
        }
        return $baseResult;
    }

    /**
     * Evaluates the expression and populates a steps array with LaTeX-formatted explanations.
     */
    public function evaluate(array $tokens, array &$steps = []): Complex
    {
        $rpnQueue = $this->parser->parse($tokens);
        
        // Initial step - show the expression being evaluated
        $infixString = implode(' ', array_map(fn($t) => $this->tokenToLatex($t), $tokens));
        $steps[] = "Evaluate the expression: <math-field readonly>{$infixString}</math-field>";

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
                
                // Log the operation with LaTeX formatting
                if ($isUnary) {
                    $opSymbol = $token->value === 'neg' ? '-' : '+';
                    $steps[] = "Apply negation: <math-field readonly>{$opSymbol}({$op2->toLatex()}) = {$result->toLatex()}</math-field>";
                } else {
                    $opSymbol = $this->getLatexOperator($token->value);
                    $steps[] = "Calculate: <math-field readonly>{$op1->toLatex()} {$opSymbol} {$op2->toLatex()} = {$result->toLatex()}</math-field>";
                }
                
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

                // Log the function application with LaTeX
                $latexFunc = $this->getLatexFunction($funcName, $args);
                $steps[] = "Evaluate function: <math-field readonly>{$latexFunc} = {$result->toLatex()}</math-field>";

                $stack[] = $result;
            }
        }
        
        if (count($stack) !== 1) throw new Exception('Malformed expression.');
        
        $finalResult = array_pop($stack);
        $steps[] = "<strong>Final Answer:</strong> <math-field readonly style='display:inline-block;font-size:1.2em;'>{$finalResult->toLatex()}</math-field>";
        return $finalResult;
    }
    
    /**
     * Converts operator symbols to LaTeX format
     */
    private function getLatexOperator(string $op): string
    {
        return match($op) {
            '*' => '\\times',
            '/' => '\\div',
            '^' => '^',
            default => $op
        };
    }
    
    /**
     * Formats a function call in LaTeX
     */
    private function getLatexFunction(string $funcName, array $args): string
    {
        $argsLatex = array_map(fn($a) => $a->toLatex(), $args);
        
        return match($funcName) {
            'sqrt' => "\\sqrt{{$argsLatex[0]}}",
            'sin' => "\\sin({$argsLatex[0]})",
            'cos' => "\\cos({$argsLatex[0]})",
            'tan' => "\\tan({$argsLatex[0]})",
            'log' => "\\log_{{$argsLatex[1]}}({$argsLatex[0]})",
            default => "{$funcName}(" . implode(', ', $argsLatex) . ")"
        };
    }
    
    /**
     * Convert a token to LaTeX representation for display
     */
    private function tokenToLatex(Token $token): string
    {
        return match($token->type) {
            Token::T_CONSTANT => match($token->value) {
                'pi' => '\\pi',
                'e' => 'e',
                'i' => 'i',
                default => $token->value
            },
            Token::T_OPERATOR => match($token->value) {
                '*' => '\\times',
                '/' => '\\div',
                default => $token->value
            },
            Token::T_FUNCTION => '\\' . $token->value,
            default => $token->value
        };
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
            case 'degree':
                if ($b->imaginary != 0) {
                    throw new Exception("The degree operator can only be applied to real numbers.");
                }
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