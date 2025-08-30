<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Base model class providing dynamic properties, methods, and JSON serialization
 * 
 * Features:
 * - Dynamic property access with automatic _properties management
 * - Dynamic method binding via closures
 * - JSON serialization support
 * - Magic method handling for extensibility
 */
class Model implements \JsonSerializable
{
    /** @var array<string, \Closure> Dynamic methods collection */
    private array $_methods = [];
    
    /** @var array<string, mixed> Dynamic properties collection */
    private array $_properties = [];
    
    /** @var ?App Framework instance reference */
    private ?App $_tipsy = null;

    /**
     * Get JSON representation of model exports
     */
    public function json(): string
    {
        return json_encode($this->exports(), JSON_THROW_ON_ERROR);
    }

    /**
     * JsonSerializable interface implementation
     */
    public function jsonSerialize(): array
    {
        return $this->exports();
    }

    /**
     * Add dynamic method to model instance
     */
    public function addMethod(string $method, \Closure $closure): void
    {
        $this->_methods[$method] = $closure;
    }

    /**
     * Check if dynamic method exists
     */
    public function hasMethod(string $method): bool
    {
        return isset($this->_methods[$method]);
    }

    /**
     * Handle static method calls with __ prefix convention
     */
    public static function __callStatic(string $method, array $args = []): mixed
    {
        $name = '__' . $method . '_static';
        $class = static::class;

        if (method_exists($class, $name)) {
            return (new \ReflectionMethod($class, $name))->invokeArgs(null, $args);
        }
        
        throw new Exception("Could not call static {$method} on {$class}");
    }

    /**
     * Handle dynamic method calls and __ prefix convention
     */
    public function __call(string $method, array $args = []): mixed
    {
        // Check dynamic methods first
        if (isset($this->_methods[$method]) && is_callable($this->_methods[$method])) {
            $closure = $this->_methods[$method]->bindTo($this);
            return $closure(...$args);
        }
        
        // Check __ prefixed methods
        $internalMethod = '__' . $method;
        if (method_exists($this, $internalMethod)) {
            return $this->$internalMethod(...$args);
        }
        
        throw new Exception("Could not call {$method} on " . static::class);
    }

    /**
     * Get all model properties (dynamic + object vars)
     */
    public function &__properties(): array
    {
        return $this->_properties ?: get_object_vars($this);
    }

    /**
     * Get specific property by name
     */
    public function &__property(string $name): mixed
    {
        return $this->_properties[$name] ?? null;
    }

    /**
     * Magic getter with _ prefix protection and dynamic properties
     */
    public function &__get(string $name): mixed
    {
        if (str_starts_with($name, '_')) {
            return $this->$name;
        }
        
        return $this->_properties[$name];
    }

    /**
     * Magic setter with _ prefix protection and dynamic properties
     */
    public function __set(string $name, mixed $value): void
    {
        if (str_starts_with($name, '_')) {
            $this->$name = $value;
        } else {
            $this->_properties[$name] = $value;
        }
    }

    /**
     * Magic isset with _ prefix handling
     */
    public function __isset(string $name): bool
    {
        return str_starts_with($name, '_') 
            ? isset($this->$name) 
            : isset($this->_properties[$name]);
    }

    /**
     * Get exportable properties (excluding callables)
     */
    public function __exports(): array
    {
        $out = $this->__properties();
        
        return array_filter($out, static fn($v): bool => !is_callable($v));
    }

    /**
     * Get/set framework instance
     */
    public function tipsy(?App $tipsy = null): ?App
    {
        if ($tipsy !== null) {
            $this->_tipsy = $tipsy;
        }
        
        return $this->_tipsy;
    }

    /**
     * Alias for __exports() for JSON serialization
     */
    private function exports(): array
    {
        return $this->__exports();
    }
}
