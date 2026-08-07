<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Tipsy\Tipsy();

// --- Middleware 1: Request Logger ---
// Logs every request to stderr (visible in `php -S` output)
$app->middleware(function ($Request) {
    return [
        'run' => function () use ($Request): void {
            $time = date('Y-m-d H:i:s');
            $method = $Request->method();
            $path = $Request->path();
            error_log("[{$time}] {$method} /{$path}");
        }
    ];
});

// --- Middleware 2: CORS Headers ---
// Adds CORS headers to every response
$app->middleware(function () {
    return [
        'run' => function (): void {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
        }
    ];
});

// --- Middleware 3: Auth Guard (class-based) ---
class AuthMiddleware extends Tipsy\Middleware {
    public function run(mixed $args = null): mixed {
        // Check for a Bearer token in the Authorization header
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? '';

        if (str_starts_with($auth, 'Bearer ')) {
            $token = substr($auth, 7);
            // In a real app, validate the token here
            $this->user = ['id' => 1, 'name' => 'Admin', 'token' => $token];
        } else {
            $this->user = null;
        }

        return true;
    }
}

$app->middleware('Auth', AuthMiddleware::class);

// --- Routes ---

$app->get('public', function () {
    header('Content-Type: application/json');
    echo json_encode(['message' => 'This is public — no auth needed']);
});

$app->get('protected', function ($Auth) {
    header('Content-Type: application/json');

    if (!$Auth->user) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized — send Authorization: Bearer <token>']);
        return;
    }

    echo json_encode([
        'message' => 'Welcome, ' . $Auth->user['name'],
        'user' => $Auth->user,
    ]);
});

$app->otherwise(function () {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not found']);
});

$app->start();
