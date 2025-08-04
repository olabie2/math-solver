<?php

namespace App\Services;

use App\Services\Math\Parser;
use App\Services\Math\Tokenizer;
use App\Services\Math\EquationParser;
use App\Services\Math\Solvers\ArithmeticSolver;
use App\Services\Math\Solvers\LinearSolver;
use App\Services\Math\Solvers\QuadraticSolver;
use App\Services\Math\SimplifierService;

class MathSolverService
{
    private Tokenizer $tokenizer;
    private SimplifierService $simplifier;
    private array $solvers;

    public function __construct()
    {
        $this->tokenizer = new Tokenizer();
        $this->simplifier = new SimplifierService();
        $shuntingYardParser = new Parser();
        $equationParser = new EquationParser($shuntingYardParser);

        $this->solvers = [
            new QuadraticSolver($equationParser),
            new LinearSolver($equationParser),
            new ArithmeticSolver($shuntingYardParser),
        ];
    }

    public function solve(string $expression): array
    {
        $result = [
            'type' => 'unknown',
            'expression' => $expression,
            'solution' => null,
            'solutions' => [],
            'steps' => [],
            'result' => null,
            'error' => null,
        ];

        try {
            $originalTokens = $this->tokenizer->tokenize($expression);

            if (empty($originalTokens)) {
                $result['error'] = 'Invalid expression. Could not understand the input.';
                return $result;
            }

            $simplifiedTokens = $this->simplifier->simplify($originalTokens);
            
            $tokens = $simplifiedTokens;

            foreach ($this->solvers as $solver) {
                if ($solver->canSolve($tokens)) {
                    return array_merge($result, $solver->solve($tokens, $expression));
                }
            }

            $result['error'] = 'Could not find a suitable solver for this type of expression.';
            return $result;

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            return $result;
        }
    }
}