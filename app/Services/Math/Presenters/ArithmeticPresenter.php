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
        $originalExpression = htmlspecialchars($solution['expression']);
        $result = $solution['result'];
        
        // Build step-by-step HTML
        $stepsHtml = '';
        if (!empty($solution['steps']) && is_array($solution['steps'])) {
            $stepsHtml .= '<p class="mt-6 mb-2 text-lg font-semibold">Step-by-Step Calculation:</p>';
            $stepsHtml .= '<div class="space-y-4">';

            foreach ($solution['steps'] as $index => $step) {
                $stepsHtml .= "
                    <div class='flex items-start'>
                        <div class='flex-shrink-0 bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center mr-3'>" . ($index + 1) . "</div>
                        <div class='flex-grow pt-px'>{$step}</div>
                    </div>
                ";
            }

            $stepsHtml .= '</div>';
        }

        return <<<HTML
        <div>
            <p class="text-xl font-bold">Arithmetic Calculation</p>
            <p class="text-gray-500 mb-4">Expression: <math-field readonly>{$originalExpression}</math-field></p>
            
            <div class="p-4 bg-green-100 border-l-4 border-green-500 text-green-800">
                <p class="font-semibold text-lg">Result:</p>
                <div class="text-2xl"><math-field readonly>{$result}</math-field></div>
            </div>

            {$stepsHtml}
        </div>
        HTML;
    }
}