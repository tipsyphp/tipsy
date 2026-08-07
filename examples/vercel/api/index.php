<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Tipsy\Tipsy();

// --- Routes ---

$app->home(function () {
    header('Content-Type: application/json');
    echo json_encode([
        'framework' => 'Tipsy',
        'runtime' => 'vercel-php',
        'php' => PHP_VERSION,
        'region' => $_SERVER['VERCEL_REGION'] ?? 'local',
        'routes' => [
            'GET /'             => 'This info page',
            'GET /hello/:name'  => 'Greeting with route param',
            'GET /time'         => 'Current server time',
            'POST /echo'        => 'Echoes back your JSON body',
            'GET /health'       => 'Health check',
        ],
    ], JSON_PRETTY_PRINT);
});

$app->get('hello/:name', function ($Params) {
    header('Content-Type: application/json');
    echo json_encode([
        'greeting' => 'Hello, ' . ucfirst($Params->name) . '!',
        'region' => $_SERVER['VERCEL_REGION'] ?? 'local',
    ]);
});

$app->get('time', function () {
    header('Content-Type: application/json');
    echo json_encode([
        'utc' => gmdate('c'),
        'timestamp' => time(),
    ]);
});

$app->post('echo', function ($Request) {
    header('Content-Type: application/json');
    echo json_encode([
        'method' => $Request->method(),
        'body' => $Request->request(),
    ]);
});

$app->get('health', function () {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'php' => PHP_VERSION,
        'memory' => memory_get_usage(true),
        'sapi' => php_sapi_name(),
    ]);
});

$app->otherwise(function () {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not found']);
});

$app->start();
