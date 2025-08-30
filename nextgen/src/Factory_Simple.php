<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Simple Object Factory with Caching
 * Modernized for PHP 8.3+ with reduced complexity
 */
class Factory_Simple {

    /** @var array<string, array<string|int, object>> Object cache map */
    private array $_objectMap = [];
    
    /** @var App Framework instance */
    private App $_tipsy;

    public function __construct(App $tipsy) {
        $this->_tipsy = $tipsy;
    }

    /**
     * Simple object creation and caching
     * 
     * @param string|object $class Class name or object instance
     * @param mixed $identifier Object identifier or constructor parameter
     * @return object Cached or new object instance
     */
    public function objectMap(string|object $class, mixed $identifier = null): object
    {
        // If caching disabled, create new instance
        if ($this->_tipsy->config()['tipsy']['factory'] === false) {
            return is_string($class) ? new $class($identifier) : $class;
        }

        // Handle object instances
        if (is_object($class)) {
            $className = $class::class;
            $id = $identifier ?? (method_exists($class, 'idVar') ? $class->{$class->idVar()} : null);
            
            if ($id !== null) {
                return $this->_objectMap[$className][$id] = $class;
            }
            return $class;
        }
        
        // Handle class strings - check cache first
        if ($identifier !== null && isset($this->_objectMap[$class][$identifier])) {
            return $this->_objectMap[$class][$identifier];
        }
        
        // Create and cache new instance
        $object = new $class($identifier);
        if ($identifier !== null) {
            $this->_objectMap[$class][$identifier] = $object;
        }
        
        return $object;
    }

    /**
     * Get cache statistics
     * 
     * @return int Number of cached objects
     */
    public function count(): int
    {
        $count = 0;
        foreach ($this->_objectMap as $class => $objects) {
            $count += count($objects);
        }
        return $count;
    }

    /**
     * Clear object cache
     */
    public function clear(): void
    {
        $this->_objectMap = [];
    }

    /**
     * Get cache contents for debugging
     * 
     * @return array<string, array<string|int, object>>
     */
    public function debug(): array
    {
        return $this->_objectMap;
    }
}