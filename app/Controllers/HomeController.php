<?php

namespace App\Controllers;

class HomeController
{
    public function index()
    {
        return view('home', ['title' => 'MathLab Home']);
    }
}
