<?php

namespace App\Core;

class App
{
    public function run()
    {
        $this->loadRoutes();

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        Route::resolve($method, $uri);
    }

    protected function loadRoutes()
    {
        require_once __DIR__ . '/../../routes/web.php';
    }
}
