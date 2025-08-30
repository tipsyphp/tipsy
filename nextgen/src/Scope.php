<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Scope class for managing dynamic properties in templates and contexts
 * 
 * Provides a container for variables that can be accessed in view templates
 * and other scoped contexts within the framework.
 */
class Scope
{
    /** @var array<string, mixed> Dynamic properties storage */
    private array $_properties = [];
    
    public function __construct()
    {
        $this->_properties = [];
    }

    /**
     * Magic getter for dynamic property access
     */
    public function &__get(string $name): mixed
    {
        return $this->_properties[$name];
    }

    /**
     * Magic setter for dynamic property assignment
     */
    public function __set(string $name, mixed $value): void
    {
        $this->_properties[$name] = $value;
    }

    /**
     * Check if property exists
     */
    public function __isset(string $name): bool
    {
        return isset($this->_properties[$name]);
    }

    /**
     * Unset property
     */
    public function __unset(string $name): void
    {
        unset($this->_properties[$name]);
    }
    
    /**
     * Get reference to all properties
     */
    public function &properties(): array
    {
        return $this->_properties;
    }

    /**
     * Set multiple properties at once
     * 
     * @param array<string, mixed> $properties
     */
    public function setProperties(array $properties): self
    {
        $this->_properties = array_merge($this->_properties, $properties);
        return $this;
    }

    /**
     * Get all properties as array
     */
    public function toArray(): array
    {
        return $this->_properties;
    }
}