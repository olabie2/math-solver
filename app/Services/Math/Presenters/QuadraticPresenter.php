<?php
namespace App\Services\Math\Presenters;

class QuadraticPresenter implements SolutionPresenterInterface
{
    public function canPresent(array $solution): bool
    {
        return isset($solution['type']) && $solution['type'] === 'quadratic_equation';
    }

    public function present(array $solution): string
    {
        $originalExpression = '<math-field readonly>' . htmlspecialchars($solution['expression']) . '</math-field>';

      
        $finalAnswer = '';
        if (!empty($solution['solutions'])) {
            $solutionStrings = [];
            foreach ($solution['solutions'] as $sol) {
               
                $solutionStrings[] = '<math-field readonly>' . htmlspecialchars($solution['variable']) . ' = ' . htmlspecialchars($sol) . '</math-field>';
            }
            $finalAnswer = implode('', $solutionStrings);
        }

       
        $stepsHtml = '';
        if (!empty($solution['steps']) && is_array($solution['steps'])) {
            $stepsHtml .= '<p class="mt-6 mb-2 text-lg font-semibold">Step-by-Step Explanation:</p>';
            $stepsHtml .= '<div class="space-y-4">'; 

            foreach ($solution['steps'] as $index => $step) {
             
                $stepsHtml .= "
                    <div class='flex items-start'>
                        <div class='flex-shrink-0 flex--0 bg-sky-600 text-white rounded-full w-6 h-6 flex items-center justify-center mr-3'>" . ($index + 1) . "</div>
                        <div class='flex-grow pt-px'>{$step}</div>
                    </div>
                ";
            }

            $stepsHtml .= '</div>';
        }

        return <<<HTML
        <div>
            <p class="text-xl font-bold">Quadratic Equation Solution</p>
            <p class="text-gray-500 mb-4">For the expression: {$originalExpression}</p>
            
            <div class="p-4 bg-green-100 border-l-4 border-green-500 text-green-800">
                <p class="font-semibold text-lg">Final Answer:</p>
                <div class="text-xl">{$finalAnswer}</div>
            </div>

            {$stepsHtml}
        </div>
        HTML;
    }
}