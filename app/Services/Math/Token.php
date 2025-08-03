<?php
namespace App\Services\Math;

/**
 * Represents a single token in a mathematical expression.
 * This class acts as a structured container for the type and value of each component.
 * This version is compatible with the provided Parser and Solver classes.
 */
class Token
{
    
    public const T_NUMBER     = 'number';
    public const T_VARIABLE   = 'variable';
    public const T_CONSTANT   = 'constant'; // e, i, pi
    public const T_OPERATOR   = 'operator';
    public const T_LPAREN     = 'lparen';    
    public const T_RPAREN     = 'rparen';    
    public const T_EQUALS     = 'equals';
    public const T_FUNCTION   = 'function';   // log, sin, cos
    public const T_COMMA      = 'comma';
    
    

    public string $type;
    public string $value;
    
 

    public function __construct(string $type, string $value)
    {
        $this->type = $type;
        $this->value = $value;
    }
}