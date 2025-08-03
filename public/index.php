<?php

// ===================================================================
//  FORCE PHP TO SHOW ERRORS (FOR DEVELOPMENT ONLY)
// ===================================================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

error_reporting(E_ALL);
// ===================================================================

define('BASE_PATH', __DIR__ . '/..');


require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/helpers.php';


use App\Core\App;


$app = new App();


$app->run();