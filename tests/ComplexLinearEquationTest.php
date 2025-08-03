<?php
use PHPUnit\Framework\TestCase;
use App\Services\MathSolverService;
use App\Services\SolutionPresenterService;

class ComplexLinearEquationTest extends TestCase
{
    private $mathSolver;
    private $presenter;

    protected function setUp(): void
    {
        $this->mathSolver = new MathSolverService();
        $this->presenter = new SolutionPresenterService();
    }

    public function testComplexLinearEquation()
    {
        $latex = "\\left(\\frac{13x}{4}\\right)+3=x+1";
        $solutionData = $this->mathSolver->solve($latex);
        $solutionHtml = $this->presenter->render($solutionData);

        // Expected solution for (13x/4)+3=x+1 is x = -8/9
        $this->assertStringContainsString("x = -0.88889", $solutionHtml); // Using rounded value for assertion
    }
}
