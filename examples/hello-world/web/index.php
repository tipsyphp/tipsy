<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Tipsy\Tipsy();

$app->config([
    'view' => [
        'path' => __DIR__ . '/../views',
    ],
]);

// Home page — renders a view with a variable
$app->home(function ($View) {
    $View->display('index', ['name' => 'World']);
});

// Dynamic route with a parameter
$app->get('hello/:name', function ($Params, $View) {
    $View->display('index', ['name' => ucfirst($Params->name)]);
});

// JSON endpoint
$app->get('api/status', function () {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'php' => PHP_VERSION,
        'framework' => 'Tipsy',
    ]);
});

$app->otherwise(function () {
    http_response_code(404);
    echo '<h1>404 — Not Found</h1>';
});

$app->start();
