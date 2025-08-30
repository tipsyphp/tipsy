<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Service class extending DependencyInjector for service initialization
 * 
 * Provides closure-based service initialization with dependency injection
 */
class Service extends DependencyInjector
{
    /**
     * Initialize service with dependency injection
     * 
     * @param array<string, mixed> $args Additional arguments for service initialization
     */
    public function init(array $args = []): mixed
    {
        $closure = $this->closure();
        
        if ($closure instanceof \Closure) {
            return $this->inject($closure, $this->_scope);
        }
        
        return null;
    }
}