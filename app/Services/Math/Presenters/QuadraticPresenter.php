<?php
namespace App\Services\Math\Presenters;

class QuadraticPresenter implements SolutionPresenterInterface
{
    public function canPresent(array $solution): bool
    {
        // Handle quadratic_equation, linear_from_quadratic, no_solution, and infinite_solutions
        $types = ['quadratic_equation', 'linear_from_quadratic', 'no_solution', 'infinite_solutions'];
        return isset($solution['type']) && in_array($solution['type'], $types);
    }

    public function present(array $solution): string
    {
        $originalExpression = '<math-field readonly>' . htmlspecialchars($solution['expression']) . '</math-field>';
        
        // Determine styling based on solution type
        $type = $solution['type'] ?? 'quadratic_equation';
        $isNoSolution = $type === 'no_solution';
        $isInfinite = $type === 'infinite_solutions';
        
        $boxClass = $isNoSolution ? 'bg-red-100 border-red-500 text-red-800' :
                    ($isInfinite ? 'bg-blue-100 border-blue-500 text-blue-800' :
                                   'bg-green-100 border-green-500 text-green-800');
      
        $finalAnswer = '';
        if (!empty($solution['solutions'])) {
            $solutionStrings = [];
            foreach ($solution['solutions'] as $sol) {
                if ($sol === 'All real numbers') {
                    $solutionStrings[] = '<span class="font-bold">All real numbers</span>';
                } else {
                    $solutionStrings[] = '<math-field readonly>' . htmlspecialchars($solution['variable'] ?? 'x') . ' = ' . htmlspecialchars($sol) . '</math-field>';
                }
            }
            $finalAnswer = implode(' <span class="mx-2 text-gray-500">or</span> ', $solutionStrings);
        } elseif ($isNoSolution) {
            $finalAnswer = '<span class="font-bold">No solution</span>';
        }

       
        $stepsHtml = '';
        if (!empty($solution['steps']) && is_array($solution['steps'])) {
            $stepsHtml .= '<p class="mt-6 mb-2 text-lg font-semibold">Step-by-Step Explanation:</p>';
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

        $title = $isNoSolution ? 'No Solution' : 
                 ($isInfinite ? 'Infinite Solutions' : 
                 ($type === 'linear_from_quadratic' ? 'Linear Equation (Degenerate Quadratic)' : 'Quadratic Equation Solution'));

        return <<<HTML
        <div>
            <p class="text-xl font-bold">{$title}</p>
            <p class="text-gray-500 mb-4">For the expression: {$originalExpression}</p>
            
            <div class="p-4 border-l-4 {$boxClass}">
                <p class="font-semibold text-lg">Result:</p>
                <div class="text-xl">{$finalAnswer}</div>
            </div>

            {$stepsHtml}
        </div>
        HTML;
    }
}