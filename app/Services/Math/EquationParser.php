<?php
namespace App\Services\Math;

use Exception;

class EquationParser
{
    private Parser $shuntingYardParser;

    public function __construct(Parser $shuntingYardParser)
    {
        $this->shuntingYardParser = $shuntingYardParser;
    }

    public function parse(array $tokens): array
    {
        $equalsPosition = -1;
        foreach ($tokens as $i => $token) {
            if ($token->type === Token::T_EQUALS) {
                if ($equalsPosition !== -1) {
                    throw new Exception("Syntax Error: Multiple equals signs found.");
                }
                $equalsPosition = $i;
            }
        }

        if ($equalsPosition === -1) {
            throw new Exception("Invalid equation: No equals sign found.");
        }
        
        if ($equalsPosition === 0 || $equalsPosition === count($tokens) - 1) {
            throw new Exception("Syntax Error: Equation must have expressions on both sides of the equals sign.");
        }

        $lhsTokens = array_slice($tokens, 0, $equalsPosition);
        $rhsTokens = array_slice($tokens, $equalsPosition + 1);

        return [
            'lhs' => $this->shuntingYardParser->parse($lhsTokens),
            'rhs' => $this->shuntingYardParser->parse($rhsTokens)
        ];
    }
}