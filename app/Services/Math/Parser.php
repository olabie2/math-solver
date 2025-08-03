<?php
namespace App\Services\Math;

use Exception;

/**
 * Implements the Shunting-yard algorithm to convert tokens into Reverse Polish Notation.
 * This version correctly handles function argument separators (commas).
 */
class Parser
{
    private const OPERATORS = [
        '='   => ['precedence' => 0, 'associativity' => 'right'],
        '+'   => ['precedence' => 1, 'associativity' => 'left'],
        '-'   => ['precedence' => 1, 'associativity' => 'left'],
        '*'   => ['precedence' => 2, 'associativity' => 'left'],
        '/'   => ['precedence' => 2, 'associativity' => 'left'],
        'neg' => ['precedence' => 3, 'associativity' => 'right'], // Unary minus
        'pos' => ['precedence' => 3, 'associativity' => 'right'], // Unary plus
        '^'   => ['precedence' => 4, 'associativity' => 'right'],
    ];

    public function parse(array $tokens): array
    {
        $outputQueue = [];
        $operatorStack = [];

        foreach ($tokens as $index => $token) {
            switch ($token->type) {
                case Token::T_NUMBER:
                case Token::T_CONSTANT:
                case Token::T_VARIABLE:
                    $outputQueue[] = $token;
                    break;
                
                case Token::T_FUNCTION:
                case Token::T_LPAREN:
                    $operatorStack[] = $token;
                    break;

                case Token::T_OPERATOR:
                    $prevToken = $tokens[$index - 1] ?? null;
                    $isUnary = ($prevToken === null || in_array($prevToken->type, [Token::T_OPERATOR, Token::T_LPAREN, Token::T_EQUALS, Token::T_COMMA]));
                    if ($isUnary) {
                        if ($token->value === '-') $token->value = 'neg';
                        if ($token->value === '+') $token->value = 'pos';
                    }

                    while (!empty($operatorStack)) {
                        $top = end($operatorStack);
                        if ($top->type !== Token::T_OPERATOR) break;
                        
                        $op1 = self::OPERATORS[$token->value];
                        $op2 = self::OPERATORS[$top->value];

                        if (($op1['associativity'] === 'left' && $op1['precedence'] <= $op2['precedence']) ||
                            ($op1['associativity'] === 'right' && $op1['precedence'] < $op2['precedence'])) {
                            $outputQueue[] = array_pop($operatorStack);
                        } else {
                            break;
                        }
                    }
                    $operatorStack[] = $token;
                    break;

                case Token::T_RPAREN:
                    while (($top = end($operatorStack)) && $top->type !== Token::T_LPAREN) {
                        $outputQueue[] = array_pop($operatorStack);
                    }
                    if (empty($operatorStack)) throw new Exception("Syntax Error: Mismatched parentheses.");
                    array_pop($operatorStack); // Pop the left parenthesis
                    
                    if (($top = end($operatorStack)) && $top->type === Token::T_FUNCTION) {
                        $outputQueue[] = array_pop($operatorStack);
                    }
                    break;
                
                case Token::T_COMMA:
                    while (($top = end($operatorStack)) && $top->type !== Token::T_LPAREN) {
                        $outputQueue[] = array_pop($operatorStack);
                    }
                    if (empty($operatorStack) || end($operatorStack)->type !== Token::T_LPAREN) {
                        throw new Exception("Syntax Error: Misplaced comma or parenthesis.");
                    }
                    break;

                case Token::T_EQUALS:
                     while (!empty($operatorStack) && end($operatorStack)->type === Token::T_OPERATOR) {
                        $outputQueue[] = array_pop($operatorStack);
                    }
                    $operatorStack[] = $token;
                    break;
            }
        }

        while (!empty($operatorStack)) {
            $op = array_pop($operatorStack);
            if ($op->type === Token::T_LPAREN || $op->type === Token::T_RPAREN) {
                 throw new Exception("Syntax Error: Mismatched parentheses.");
            }
            $outputQueue[] = $op;
        }
        
        return $outputQueue;
    }
}