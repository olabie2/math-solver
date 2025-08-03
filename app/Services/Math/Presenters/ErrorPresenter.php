<?php
namespace App\Services\Math\Presenters;

class ErrorPresenter implements SolutionPresenterInterface
{
    public function canPresent(array $solution): bool
    {
        
        return !empty($solution['error']);
    }

    public function present(array $solution): string
    {
        $safeError = htmlspecialchars($solution['error']);
        return <<<HTML
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error:</strong>
            <span class="block sm:inline">{$safeError}</span>
        </div>
        HTML;
    }
}