<?php
namespace App\Controllers;

use App\Services\MathSolverService;
use App\Services\SolutionPresenterService;

class CalculatorController
{
    public function solve()
    {
        $latex = $_POST['latex'] ?? '';

        
        $mathSolver = new MathSolverService();
        $solutionData = $mathSolver->solve($latex);

        
        $presenter = new SolutionPresenterService();
        $solutionHtml = $presenter->render($solutionData); 

        
        return view("calculator", [
            'title' => "Solve math calc",
            'solution' => $solutionData, 
            'solutionHtml' => $solutionHtml, 
            'submitted_latex' => $latex
        ]);
    }

    public function index()
    {
        return view("calculator", ['title' => "Solve math calc"]);
    }
}