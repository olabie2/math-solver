<?php
namespace App\Services\Math\Solvers;

use App\Services\Math\Complex;
use App\Services\Math\Tokenizer;
use App\Services\Math\Parser;
use App\Services\Math\Token;
use Exception;

class IntegralSolver implements SolverInterface
{
    private ArithmeticSolver $evaluator;
    private Tokenizer $tokenizer;

    public function __construct(Tokenizer $tokenizer, ArithmeticSolver $evaluator)
    {
        $this->tokenizer = $tokenizer;
        $this->evaluator = $evaluator;
    }

    public function canSolve(array $tokens): bool
    {
        return !empty($tokens) && $tokens[0]->type === Token::T_FUNCTION && $tokens[0]->value === 'integral';
    }

    public function solve(array $tokens, string $originalExpression): array
    {
        $baseResult = [
            'type' => 'definite_integral',
            'expression' => $originalExpression,
            'result' => null,
            'error' => null,
        ];

        try {
            $args = $this->parseArguments($originalExpression);
            $expressionTokens = $this->tokenizer->tokenize($args['expression']);
            $variableName = $args['variable'];
            $lowerBound = (float)$args['lower'];
            $upperBound = (float)$args['upper'];
            
            $num_intervals = 1000;
            $dx = ($upperBound - $lowerBound) / $num_intervals;
            $sum = $this->evaluator->evaluate($expressionTokens, [$variableName => new Complex($lowerBound)]);
            $sum = $sum->add($this->evaluator->evaluate($expressionTokens, [$variableName => new Complex($upperBound)]));

            for ($i = 1; $i < $num_intervals; $i++) {
                $x = $lowerBound + $i * $dx;
                $weight = ($i % 2 == 0) ? 2 : 4;
                $y = $this->evaluator->evaluate($expressionTokens, [$variableName => new Complex($x)]);
                $sum = $sum->add($y->multiply(new Complex($weight)));
            }

            $integral = $sum->multiply(new Complex($dx / 3));
            $baseResult['result'] = (string)$integral;

        } catch (Exception $e) {
            $baseResult['error'] = "Integral Error: " . $e->getMessage();
        }

        return $baseResult;
    }
    
    private function parseArguments(string $expression): array
    {
        if (!preg_match('/integral\((.+)\)/i', $expression, $matches)) {
            throw new Exception("Invalid integral syntax.");
        }
        $args = explode(',', $matches[1]);
        if (count($args) !== 4) {
            throw new Exception("Integral requires 4 arguments: expression, variable, lower bound, upper bound.");
        }
        return [
            'expression' => trim($args[0]),
            'variable' => trim($args[1]),
            'lower' => trim($args[2]),
            'upper' => trim($args[3]),
        ];
    }
}