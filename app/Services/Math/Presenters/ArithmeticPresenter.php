<?php
namespace App\Services\Math\Presenters;

class ArithmeticPresenter implements SolutionPresenterInterface
{
    public function canPresent(array $solution): bool
    {
        
        return $solution['type'] === 'arithmetic_evaluation';
    }

    public function present(array $solution): string
    {
        $safeResult = htmlspecialchars($solution['result']);
        return <<<HTML
        <p class="text-lg"><strong>Result:</strong> 
            <span class="font-mono bg-gray-200 p-1 rounded">{$safeResult}</span>
        </p>
        HTML;
    }
}