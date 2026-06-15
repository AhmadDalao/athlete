<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Shared hosting subdirectory deploys can leave REQUEST_URI prefixed with the
// public folder name, which makes "/" resolve as "athlete" instead of root.
if (isset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME'])) {
    $scriptDirectory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

    if ($scriptDirectory !== '' && $scriptDirectory !== '/' && str_starts_with($_SERVER['REQUEST_URI'], $scriptDirectory)) {
        $normalizedRequestUri = substr($_SERVER['REQUEST_URI'], strlen($scriptDirectory));
        $_SERVER['REQUEST_URI'] = $normalizedRequestUri !== '' ? $normalizedRequestUri : '/';
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
