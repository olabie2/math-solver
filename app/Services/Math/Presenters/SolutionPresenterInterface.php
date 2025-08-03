<?php
namespace App\Services\Math\Presenters;


interface SolutionPresenterInterface
{
   
    public function canPresent(array $solution): bool;

  
    public function present(array $solution): string;
}