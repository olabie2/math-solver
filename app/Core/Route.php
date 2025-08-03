<?php

namespace App\Core;

class Route
{
    public static array $routes = [];

    public static function get(string $uri, string $action)
    {
        self::$routes['GET'][$uri] = $action;
    }

    public static function post(string $uri, string $action)
    {
        self::$routes['POST'][$uri] = $action;
    }

    public static function put(string $uri, string $action)
    {
        self::$routes['PUT'][$uri] = $action;
    }

    public static function delete(string $uri, string $action)
    {
        self::$routes['DELETE'][$uri] = $action;
    }

    public static function resolve(string $method, string $uri)
    {
        $uri = rtrim($uri, '/') ?: '/';
        $action = self::$routes[$method][$uri] ?? null;

        if (!$action) {
            http_response_code(404);
            echo "404 Not Found";
            exit;
        }

        list($controller, $method) = explode('@', $action);

        $controller = "App\\Controllers\\$controller";

        if (!class_exists($controller) || !method_exists($controller, $method)) {
            http_response_code(500);
            echo "Controller or method not found";
            exit;
        }

        call_user_func([new $controller, $method]);
    }
}
