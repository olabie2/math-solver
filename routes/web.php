<?php

use App\Core\Route;

Route::get('/', 'HomeController@index');
Route::get('/calculator', 'CalculatorController@index');
Route::post('/calculator', 'CalculatorController@solve');