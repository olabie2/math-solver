<?php
namespace App\Services\Math\Presenters;


class LinearPresenter implements SolutionPresenterInterface
{
    
    public function canPresent(array $solution): bool
    {
        return isset($solution['type']) && $solution['type'] === 'linear_equation';
    }

    
    public function present(array $solution): string
    {
        // Check for the minimum required data.
        if (empty($solution['variable']) || !isset($solution['solution'])) {
            return '';
        }
        
        // Sanitize user-facing output that is NOT pre-formatted.
        $variable = htmlspecialchars($solution['variable']);
        $result = htmlspecialchars($solution['solution']);
        $originalExpression = htmlspecialchars($solution['expression']);

       
        $stepsHtml = '';
        if (!empty($solution['steps']) && is_array($solution['steps'])) {
            $stepsHtml .= '<p class="mt-4 text-lg"><strong>Step-by-Step Explanation:</strong></p>';
            $stepsHtml .= '<ol class="list-decimal list-inside ml-4 space-y-2">'; // Creates a numbered list

            foreach ($solution['steps'] as $step) {
                // IMPORTANT: The step strings are already formatted with <strong> etc.
                // by the solver, so we do NOT use htmlspecialchars() on them here.
                $stepsHtml .= "<li>{$step}</li>";
            }

            $stepsHtml .= '</ol>';
        }
     
        return <<<HTML
        <div>
            <p class="text-lg"><strong>Linear Equation Solution:</strong></p>
            <p class="text-gray-600">For the expression: <i>{$originalExpression}</i></p>
            <div class="mt-2 p-4 bg-green-100 border-l-4 border-green-500 text-green-700">
                <p class="font-mono text-xl">{$variable} = {$result}</p>
            </div>
            {$stepsHtml}
        </div>
        HTML;
    }
}