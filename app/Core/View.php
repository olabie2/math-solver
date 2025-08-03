<?php

namespace App\Core;

class View
{
    protected static array $blocks = [];
    protected static string $currentBlock;

    public static function startBlock(string $name)
    {
        self::$currentBlock = $name;
        ob_start();
    }

    public static function endBlock()
    {
        self::$blocks[self::$currentBlock] = ob_get_clean();
    }

    public static function yieldBlock(string $name)
    {
        echo self::$blocks[$name] ?? '';
    }

    public static function render(string $view, array $data = [])
    {
        $path = __DIR__ . '/../../views/' . $view . '.html.php';
        if (!file_exists($path)) {
            throw new \Exception("View '$view' not found.");
        }

        extract($data);
        require $path;
    }

    public static function renderLayout(string $layout)
    {
        self::render($layout);
    }
}
