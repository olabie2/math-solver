<?php
namespace App\Services\Math\Solvers;

use App\Services\Math\Token;


interface SolverInterface
{
   
    public function canSolve(array $tokens): bool;

   
    public function solve(array $tokens, string $originalExpression): array;
}