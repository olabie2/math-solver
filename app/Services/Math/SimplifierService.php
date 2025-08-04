<?php

namespace App\Services\Math;

use App\Services\Math\Solvers\ExpressionSimplifierSolver;

class SimplifierService
{
    private ExpressionSimplifierSolver $simplifier;

    public function __construct()
    {
        $this->simplifier = new ExpressionSimplifierSolver();
    }

    public function simplify(array $tokens): array
    {
        $equalsPosition = -1;
        foreach ($tokens as $i => $token) {
            if ($token->type === \App\Services\Math\Token::T_EQUALS) {
                $equalsPosition = $i;
                break;
            }
        }

        if ($equalsPosition !== -1) {
            $lhsTokens = array_slice($tokens, 0, $equalsPosition);
            $rhsTokens = array_slice($tokens, $equalsPosition + 1);

            $simplifiedLhs = $this->simplifySide($lhsTokens);
            $simplifiedRhs = $this->simplifySide($rhsTokens);

            return array_merge($simplifiedLhs, [new \App\Services\Math\Token(\App\Services\Math\Token::T_EQUALS, '=')], $simplifiedRhs);
        }

        return $this->simplifySide($tokens);
    }

    private function simplifySide(array $tokens): array
    {
        if ($this->simplifier->canSolve($tokens)) {
            $result = $this->simplifier->solve($tokens, '');
            if (isset($result['simplified_tokens'])) {
                return $result['simplified_tokens'];
            }
        }

        return $tokens;
    }
}