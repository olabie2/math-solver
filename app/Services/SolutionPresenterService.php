<?php
namespace App\Services;

// Import all the presenter strategies
use App\Services\Math\Presenters\ArithmeticPresenter;
use App\Services\Math\Presenters\ErrorPresenter;
use App\Services\Math\Presenters\LinearPresenter;
use App\Services\Math\Presenters\QuadraticPresenter;
use App\Services\Math\Presenters\SolutionPresenterInterface;
use App\Services\Math\Presenters\ExpressionSimplifierPresenter;

/**
 * Manages all solution presenters and selects the correct one to format a result.
 * This class embodies the Strategy pattern for the presentation layer.
 */
class SolutionPresenterService
{
    /** @var SolutionPresenterInterface[] */
    private array $presenters;

    public function __construct()
    {
        // Register all our available presenters.
        // The order is CRITICAL. The first one that can handle the data wins.
        $this->presenters = [
            new ErrorPresenter(), // Check for errors first.
            new LinearPresenter(),
            new QuadraticPresenter(),
            new ArithmeticPresenter(),
            new ExpressionSimplifierPresenter(),
           
        ];
    }

    /**
     * Takes a raw solution array and returns a formatted HTML block.
     * @param array $solution
     * @return string
     */
    public function render(array $solution): string
    {
        // Loop through the strategies to find one that can do the job.
        foreach ($this->presenters as $presenter) {
            if ($presenter->canPresent($solution)) {
                return $presenter->present($solution);
            }
        }
        
        // Fallback if no specific presenter was found (for 'unknown' type, etc.)
        return ''; // Return an empty string if there's nothing to show
    }
}