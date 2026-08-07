<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Tipsy\Tipsy();

// In-memory store (resets on each request — use a DB in production)
$items = [
    ['id' => 1, 'name' => 'Espresso',  'price' => 3.50],
    ['id' => 2, 'name' => 'Latte',     'price' => 4.75],
    ['id' => 3, 'name' => 'Americano', 'price' => 3.00],
];

// Helper to send JSON responses
function json_response(mixed $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// List all items
$app->get('items', function () use (&$items) {
    json_response(['data' => $items]);
});

// Get a single item by ID
$app->get('items/:id', function ($Params) use (&$items) {
    $id = (int) $Params->id;
    foreach ($items as $item) {
        if ($item['id'] === $id) {
            json_response(['data' => $item]);
            return;
        }
    }
    json_response(['error' => 'Item not found'], 404);
});

// Create a new item
$app->post('items', function ($Request) use (&$items) {
    $body = $Request->request();
    if (empty($body['name'])) {
        json_response(['error' => 'Name is required'], 422);
        return;
    }

    $newItem = [
        'id'    => max(array_column($items, 'id')) + 1,
        'name'  => $body['name'],
        'price' => (float) ($body['price'] ?? 0),
    ];
    $items[] = $newItem;

    json_response(['data' => $newItem], 201);
});

// Update an item
$app->put('items/:id', function ($Params, $Request) use (&$items) {
    $id = (int) $Params->id;
    $body = $Request->request();

    foreach ($items as &$item) {
        if ($item['id'] === $id) {
            $item['name']  = $body['name'] ?? $item['name'];
            $item['price'] = isset($body['price']) ? (float) $body['price'] : $item['price'];
            json_response(['data' => $item]);
            return;
        }
    }
    json_response(['error' => 'Item not found'], 404);
});

// Delete an item
$app->delete('items/:id', function ($Params) use (&$items) {
    $id = (int) $Params->id;
    foreach ($items as $key => $item) {
        if ($item['id'] === $id) {
            unset($items[$key]);
            json_response(['deleted' => true]);
            return;
        }
    }
    json_response(['error' => 'Item not found'], 404);
});

// Catch-all
$app->otherwise(function () {
    json_response(['error' => 'Not found'], 404);
});

$app->start();
