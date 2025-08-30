<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Middleware class for request/response processing pipeline
 * 
 * Extends Service to provide middleware functionality with initialization
 * and execution phases for request processing.
 */
class Middleware extends Service
{
    /**
     * Initialize middleware with dependency injection
     * 
     * @param array<string, mixed> $args Additional arguments for middleware initialization
     */
    public function init(array $args = []): mixed
    {
        $closure = $this->closure();
        
        if ($closure instanceof \Closure) {
            return $this->inject($closure, $this->_scope);
        }
        
        return null;
    }

    /**
     * Run middleware execution
     * 
     * @param mixed $args Arguments for middleware execution
     */
    public function run(mixed $args = null): mixed
    {
        // Default implementation - can be overridden in specific middleware
        return true;
    }

    /**
     * Start middleware execution with error handling
     * 
     * @param array{service: string, args?: mixed} $middleware Middleware configuration
     * @param App $tipsy Framework instance
     * @throws Exception When middleware fails to start
     */
    public static function _start(array $middleware, App $tipsy): void
    {
        $service = $tipsy->service($middleware['service']);

        if ($service->hasMethod('run') || method_exists($service, 'run')) {
            $status = $service->run($middleware['args'] ?? null);
            
            if ($status === false) {
                throw new Exception("Middleware \"{$middleware['service']}\" failed to start");
            }
        }
    }
}
