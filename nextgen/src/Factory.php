<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Factory class for object creation and caching
 * 
 * Provides object instantiation with optional caching based on configuration.
 * Handles various object creation scenarios including ID-based caching.
 */
class Factory extends Model
{
    /** @var array<string, array<string|int, object>> Object cache map */
    private array $_objectMap = [];

    public function __construct(App $tipsy)
    {
        parent::__construct();
        $this->_objectMap = [];
        $this->_tipsy = $tipsy;
    }

    /**
     * Create or retrieve cached object based on class and identifier
     * 
     * @param string|object $class Class name or object instance
     * @param mixed $identifier Object identifier or constructor parameter
     */
    public function objectMap(string|object $class, mixed $identifier = null): object
    {
        // Create new object if caching is disabled
        if ($this->_tipsy->config()['tipsy']['factory'] === false) {
            if (is_string($class)) {
                return new $class($identifier);
            }
            return $class;
        }

        // Handle different object creation/caching scenarios
        return match (true) {
            // Object with explicit ID provided
            is_object($class) && (is_string($identifier) || is_int($identifier)) => 
                $this->_objectMap[$class::class][$identifier] = $class,
            
            // Cached object with explicit ID
            is_object($class) && (is_string($identifier) || is_int($identifier)) 
            && isset($this->_objectMap[$class::class][$identifier]) => 
                $this->_objectMap[$class::class][$identifier],
            
            // Cached object using idVar method
            is_object($class) && method_exists($class, 'idVar') 
            && isset($this->_objectMap[$class::class][$class->{$class->idVar()}]) => 
                $this->_objectMap[$class::class][$class->{$class->idVar()}],
            
            // Store object using idVar method
            is_object($class) && method_exists($class, 'idVar') => 
                $this->_objectMap[$class::class][$class->{$class->idVar()}] = $class,
            
            // Cached object by class name and ID
            is_string($class) && (is_string($identifier) || is_int($identifier)) 
            && isset($this->_objectMap[$class][$identifier]) => 
                $this->_objectMap[$class][$identifier],
            
            // Cached object by class name and object with idVar
            is_string($class) && is_object($identifier) => $this->handleObjectIdentifier($class, $identifier),
            
            // Create new object and cache if has idVar
            is_string($class) => $this->createAndCacheObject($class, $identifier),
            
            // Default fallback
            default => new Model()
        };
    }

    /**
     * Handle object identifier caching scenario
     */
    private function handleObjectIdentifier(string $className, object $obj): object
    {
        $tempInstance = new $className();
        
        if (method_exists($tempInstance, 'idVar')) {
            $idVar = $tempInstance->idVar();
            $objectId = $obj->$idVar;
            
            if (isset($this->_objectMap[$className][$objectId])) {
                return $this->_objectMap[$className][$objectId];
            }
        }
        
        return $obj;
    }

    /**
     * Create new object and cache if it has idVar method
     */
    private function createAndCacheObject(string $className, mixed $identifier): object
    {
        $obj = new $className($identifier);
        
        if (method_exists($obj, 'idVar')) {
            $idVar = $obj->idVar();
            $objectId = $obj->$idVar;
            
            if (!isset($this->_objectMap[$className][$objectId])) {
                $this->_objectMap[$className][$objectId] = $obj;
            }
        }
        
        return $obj;
    }
}
