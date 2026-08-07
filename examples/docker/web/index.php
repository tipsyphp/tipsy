<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Tipsy\Tipsy();

$app->config([
    'view' => [
        'path' => __DIR__ . '/../views',
    ],
]);

$app->home(function ($View) {
    $View->display('index', [
        'title' => 'Tipsy on Docker',
        'php_version' => PHP_VERSION,
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'sapi' => php_sapi_name(),
        'opcache' => function_exists('opcache_get_status') ? opcache_get_status(false) : null,
    ]);
});

$app->get('health', function () {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'healthy',
        'php' => PHP_VERSION,
        'memory_usage' => memory_get_usage(true),
        'peak_memory' => memory_get_peak_usage(true),
        'opcache_enabled' => function_exists('opcache_get_status'),
    ]);
});

$app->otherwise(function () {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not found']);
});

$app->start();
