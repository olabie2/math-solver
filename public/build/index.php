<?php

// ===================================================================
// STEP 1: FORCE PHP TO SHOW ERRORS (FOR DEVELOPMENT ONLY)
// ===================================================================
// This is the most important part. It will change the "can't connect"
// error into a specific, readable "Class not found" or "Parse error".
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ===================================================================

define('BASE_PATH', __DIR__ . '/..');

// This is likely the source of the crash. Let's verify it.
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/helpers.php';

// The "use" statement will fail if the autoloader can't find the class.
use App\Core\App;

// The constructor of "App" might be trying to load other classes that fail.
$app = new App();

// The run() method is likely where your Router and Controller are called.
// The crash is happening somewhere inside this method.
$app->run();