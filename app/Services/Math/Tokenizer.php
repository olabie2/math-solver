<?php
namespace App\Services\Math;

/**
 * Converts an expression string into an array of Token objects.
 * This version handles commas, robust LaTeX normalization, and expanded
 * rules for implicit multiplication.
 */
class Tokenizer
{
    private const TOKEN_PATTERN = '/(\d*\.?\d+)|(log|sin|cos|tan|sqrt)|([a-zA-Z])|([\+\-\*\/\^])|([\(\),])|(=)|(pi|e|i)/i';

    /**
     * Converts an expression string into an array of Token objects.
     * @param string $expression
     * @return Token[]
     */
    public function tokenize(string $expression): array
    {
        $normalizedExpression = $this->normalize($expression);
        preg_match_all(self::TOKEN_PATTERN, $normalizedExpression, $matches, PREG_SET_ORDER);
        
        $tokens = [];
        foreach ($matches as $match) {
            $value = $match[0];
            if (is_numeric($value)) {
                $tokens[] = new Token(Token::T_NUMBER, $value);
            } elseif (in_array(strtolower($value), ['log', 'sin', 'cos', 'tan', 'sqrt'])) {
                $tokens[] = new Token(Token::T_FUNCTION, strtolower($value));
            } elseif (in_array(strtolower($value), ['e', 'i', 'pi'])) {
                $tokens[] = new Token(Token::T_CONSTANT, strtolower($value));
            } elseif (in_array($value, ['+', '-', '*', '/', '^'])) {
                $tokens[] = new Token(Token::T_OPERATOR, $value);
            } elseif ($value === '(') {
                $tokens[] = new Token(Token::T_LPAREN, $value);
            } elseif ($value === ')') {
                $tokens[] = new Token(Token::T_RPAREN, $value);
            } elseif ($value === '=') {
                $tokens[] = new Token(Token::T_EQUALS, $value);
            } elseif ($value === ',') {
                $tokens[] = new Token(Token::T_COMMA, $value);
            } elseif (ctype_alpha($value)) {
                $tokens[] = new Token(Token::T_VARIABLE, $value);
            }
        }

        return $this->insertImplicitMultiplication($tokens);
    }
    
    /**
     * Scans the token stream and inserts multiplication operators where they are implied.
     */
    private function insertImplicitMultiplication(array $tokens): array
    {
        $result = [];
        for ($i = 0; $i < count($tokens); $i++) {
            $currentToken = $tokens[$i];
            $result[] = $currentToken;

            if ($i < count($tokens) - 1) {
                $nextToken = $tokens[$i + 1];

                $isNumericOrParen = in_array($currentToken->type, [Token::T_NUMBER, Token::T_RPAREN]);
                $isVarConst = in_array($currentToken->type, [Token::T_VARIABLE, Token::T_CONSTANT]);
                $isFunc = $currentToken->type === Token::T_FUNCTION;

                $nextIsGoodForImplicit = in_array($nextToken->type, [
                    Token::T_VARIABLE, Token::T_CONSTANT, Token::T_FUNCTION, Token::T_LPAREN, Token::T_NUMBER
                ]);

                if (($isNumericOrParen || $isVarConst) && $nextToken->type === Token::T_LPAREN) {
                     $result[] = new Token(Token::T_OPERATOR, '*');
                }
                elseif ($isNumericOrParen && $nextIsGoodForImplicit) {
                    $result[] = new Token(Token::T_OPERATOR, '*');
                }
                // Handles 'sin x' by turning it into 'sin * x'
                elseif ($isFunc && in_array($nextToken->type, [Token::T_VARIABLE, Token::T_NUMBER, Token::T_CONSTANT])) {
                    $result[] = new Token(Token::T_OPERATOR, '*');
                }
            }
        }
        return $result;
    }

    /**
     * Cleans and normalizes a raw LaTeX string for mathematical tokenization.
     */
    private function normalize(string $rawExpression): string
    {
        $replacements = [
            '\\times'   => '*', '\\div'     => '/', '\\cdot'    => '*',
            '\\left'    => '', '\\right'   => '',  '\\sin'     => 'sin',
            '\\cos'     => 'cos', '\\tan'     => 'tan', '\\log'     => 'log',
            '\\pi'      => 'pi',
        ];
        $expression = str_replace(array_keys($replacements), array_values($replacements), $rawExpression);

        $expression = preg_replace('/\\\\frac\{(.+?)\}\{(.+?)\}/', '($1)/($2)', $expression);
        $expression = preg_replace('/\\\\frac(.)(.)/', '($1)/($2)', $expression);
        $expression = preg_replace('/\\\\sqrt\[(.+?)\]\{(.+?)\}/', '($2)^(1/($1))', $expression);
        // Handle both \sqrt{x} and \sqrt x
        $expression = preg_replace('/\\\\sqrt\{(.+?)\}/', 'sqrt($1)', $expression);
        $expression = preg_replace('/\\\\sqrt\s*([a-zA-Z0-9\.]+)/', 'sqrt($1)', $expression);

        $expression = preg_replace('/\{([a-zA-Z0-9\.]+)\}/', '$1', $expression);
        $expression = str_replace(' ', '', $expression);

        return $expression;
    }
}