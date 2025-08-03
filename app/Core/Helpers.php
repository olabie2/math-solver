<?php
namespace App\Core;
use App\Core\View;

function view(string $viewName, array $data = [])
{
    View::render($viewName, $data);
}

function renderLayout(string $layout)
{
    View::renderLayout($layout);
}

function startBlock(string $name)
{
    View::startBlock($name);
}

function endBlock()
{
    View::endBlock();
}

function yieldBlock(string $name)
{
    View::yieldBlock($name);
}

function vite(string|array $entrypoints): string
{
    $uri = 'http://localhost:5173';
    $srcDirectory = 'resources';
    $buildDirectory = 'build';

    $hotFile = BASE_PATH . '/public/' . $buildDirectory . '/hot';

    if (file_exists($hotFile)) {
        $handle = fopen($hotFile, 'r');
        $url = fread($handle, filesize($hotFile));
        fclose($handle);

        $url = rtrim($url);

        $tags = '<script type="module" src="' . $url . '/@vite/client"></script>';

        if (! is_array($entrypoints)) {
            $entrypoints = [$entrypoints];
        }

        foreach ($entrypoints as $entrypoint) {
            $tags .= '<script type="module" src="' . $url . '/' . $srcDirectory . '/' . $entrypoint . '"></script>';
        }

        return $tags;
    }

    $manifestPath = BASE_PATH . '/public/' . $buildDirectory . '/.vite/manifest.json';

    if (! file_exists($manifestPath)) {
        return '';
    }

    $manifest = json_decode(file_get_contents($manifestPath), true);

    $tags = '';

    if (! is_array($entrypoints)) {
        $entrypoints = [$entrypoints];
    }

    foreach ($entrypoints as $entrypoint) {
        $entrypoint = $srcDirectory . '/' . $entrypoint;

        if (! isset($manifest[$entrypoint])) {
            continue;
        }

        $file = $manifest[$entrypoint]['file'];
        $css = $manifest[$entrypoint]['css'] ?? [];

        if (pathinfo($file, PATHINFO_EXTENSION) === 'js') {
            $tags .= '<script type="module" src="/' . $buildDirectory . '/' . $file . '"></script>';
        } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'css') {
            $tags .= '<link rel="stylesheet" href="/' . $buildDirectory . '/' . $file . '">';
        }


        foreach ($css as $cssFile) {
            $tags .= '<link rel="stylesheet" href="/' . $buildDirectory . '/' . $cssFile . '">';
        }
    }

    return $tags;
}
