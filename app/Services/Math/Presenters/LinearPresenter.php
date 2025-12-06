<?php
namespace App\Services\Math\Presenters;

class LinearPresenter implements SolutionPresenterInterface
{
    public function canPresent(array $solution): bool
    {
        // Handle linear_equation, no_solution, and infinite_solutions types
        return isset($solution['type']) && in_array($solution['type'], ['linear_equation', 'no_solution', 'infinite_solutions']);
    }

    public function present(array $solution): string
    {
        if (empty($solution['variable']) || !isset($solution['solution'])) {
            return '';
        }
        
        $variable = htmlspecialchars($solution['variable']);
        $result = htmlspecialchars($solution['solution']);
        $originalExpression = htmlspecialchars($solution['expression']);
        
        // Determine styling based on solution type
        $isSuccess = $solution['type'] === 'linear_equation';
        $isNoSolution = $solution['type'] === 'no_solution';
        $isInfinite = $solution['type'] === 'infinite_solutions';
        
        // Result box styling
        $boxClass = $isSuccess ? 'bg-green-100 border-green-500 text-green-800' : 
                   ($isNoSolution ? 'bg-red-100 border-red-500 text-red-800' : 
                                    'bg-blue-100 border-blue-500 text-blue-800');
        
        // Result display
        $resultDisplay = $isSuccess 
            ? "<math-field readonly>{$variable} = {$result}</math-field>"
            : "<span class='font-bold'>{$result}</span>";

        // Build step-by-step HTML
        $stepsHtml = '';
        if (!empty($solution['steps']) && is_array($solution['steps'])) {
            $stepsHtml .= '<p class="mt-6 mb-2 text-lg font-semibold">Step-by-Step Solution:</p>';
            $stepsHtml .= '<div class="space-y-4">';

            foreach ($solution['steps'] as $index => $step) {
                $stepsHtml .= "
                    <div class='flex items-start'>
                        <div class='flex-shrink-0 bg-sky-600 text-white rounded-full w-6 h-6 flex items-center justify-center mr-3'>" . ($index + 1) . "</div>
                        <div class='flex-grow pt-px'>{$step}</div>
                    </div>
                ";
            }

            $stepsHtml .= '</div>';
        }
     
        return <<<HTML
        <div>
            <p class="text-xl font-bold">Linear Equation Solution</p>
            <p class="text-gray-500 mb-4">Equation: <math-field readonly>{$originalExpression}</math-field></p>
            
            <div class="p-4 border-l-4 {$boxClass}">
                <p class="font-semibold text-lg">Result:</p>
                <div class="text-2xl">{$resultDisplay}</div>
            </div>

            {$stepsHtml}
        </div>
        HTML;
    }
}