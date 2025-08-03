<?php
namespace App\Services\Math\Presenters;


class ExpressionSimplifierPresenter implements SolutionPresenterInterface
{
    /**
     * Determines if this presenter can format the given solution array.
     * It looks for the specific type 'expression_simplification'.
     *
     * @param array $solution The raw solution data from a solver.
     * @return bool True if it can be presented, false otherwise.
     */
    public function canPresent(array $solution): bool
    {
        
        return isset($solution['type']) && $solution['type'] === 'expression_simplification';
    }

    /**
     * Formats the raw simplified expression data into a final HTML string.
     *
     * @param array $solution The raw solution data.
     * @return string The formatted HTML block.
     */
    public function present(array $solution): string
    {
        // Ensure the required key exists for safety.
        if (empty($solution['result'])) {
            return '';
        }

        
        $safeResult = htmlspecialchars($solution['result']);

        
        return <<<HTML
        <p class="text-lg"><strong>Simplified Expression:</strong> 
            <span class="font-mono bg-gray-200 p-1 rounded">{$safeResult}</span>
        </p>
        HTML;
    }
}