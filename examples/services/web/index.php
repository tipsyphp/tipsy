<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Tipsy\Tipsy();

// --- Service 1: Config service (closure-based) ---
// Services defined with closures are evaluated once and cached.
$app->service('Config', function () {
    return [
        'app_name' => 'Tipsy Demo',
        'version' => '2.0.0',
        'debug' => true,
    ];
});

// --- Service 2: Logger service (class-based) ---
class Logger extends Tipsy\Service {
    private array $logs = [];

    public function log(string $level, string $message): void {
        $this->logs[] = [
            'time' => date('c'),
            'level' => $level,
            'message' => $message,
        ];
        if ($level === 'error') {
            error_log("[{$level}] {$message}");
        }
    }

    public function getLogs(): array {
        return $this->logs;
    }
}

$app->service('Logger', Logger::class);

// --- Service 3: Greeter (depends on Config) ---
// Demonstrates service-to-service dependency via closure injection.
// Injected services are Tipsy\Service objects — use property access, not array keys.
$app->service('Greeter', function ($Config) {
    return [
        'greet' => function (string $name) use ($Config): string {
            return "Hello {$name}, welcome to {$Config->app_name} v{$Config->version}!";
        }
    ];
});

// --- Routes that consume services ---

$app->home(function ($Greeter) {
    header('Content-Type: application/json');
    echo json_encode([
        'greeting' => $Greeter->greet('World'),
    ]);
});

$app->get('info', function ($Config, $Logger) {
    $Logger->log('info', 'Info endpoint hit');

    header('Content-Type: application/json');
    echo json_encode([
        'config' => $Config,
        'logs' => $Logger->getLogs(),
    ]);
});

$app->get('greet/:name', function ($Params, $Greeter, $Logger) {
    $Logger->log('info', "Greeting {$Params->name}");

    header('Content-Type: application/json');
    echo json_encode([
        'greeting' => $Greeter->greet($Params->name),
    ]);
});

$app->otherwise(function () {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not found']);
});

$app->start();
