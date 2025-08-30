<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Iterator for collections with filtering, transformation, and manipulation methods
 * 
 * Provides a jQuery-like interface for working with arrays and objects,
 * supporting chaining, filtering, and bulk operations.
 */
class Looper implements \Iterator, \JsonSerializable, \Countable
{
    /** @var array<int, mixed> Collection items */
    private array $_items;
    
    /** @var int Current iterator position */
    private int $_position = 0;
    
    /** @var ?self Parent looper for chained operations */
    private ?self $_parent = null;

    /** Break constant for early termination in each() */
    public const DONE = 'looper\break';

    /**
     * JsonSerializable interface implementation
     */
    public function jsonSerialize(): array
    {
        $items = [];
        
        foreach ($this->_items as $key => $item) {
            if (is_object($item) && method_exists($item, 'exports')) {
                $items[$key] = $item->exports();
            } else {
                $items[$key] = $item;
            }
        }
        
        return $items;
    }

    /**
     * Construct looper with variable arguments
     * Merges arrays, objects, and other loopers into single collection
     */
    public function __construct(mixed ...$args)
    {
        $items = [];
        $args = array_reverse($args);

        foreach ($args as $arg) {
            if ($arg instanceof self) {
                $arg = $arg->items();
            } elseif (is_object($arg)) {
                $arg = [$arg];
            }
            
            $items = array_merge((array) $arg, $items);
        }

        $this->_items = $items;
        $this->_position = 0;
    }

    /**
     * Static factory method for fluid creation
     */
    public static function o(mixed ...$args): static
    {
        return new static(...$args);
    }

    /**
     * Get all items in collection
     */
    public function items(): array
    {
        return $this->_items;
    }

    /**
     * Get item at specific index
     */
    public function get(int $index): mixed
    {
        return $this->eq($index);
    }

    /**
     * Set property on all items in collection
     */
    public function set(string $property, mixed $value): self
    {
        foreach ($this->_items as $item) {
            if (is_object($item)) {
                $item->$property = $value;
            }
        }
        
        return $this;
    }

    /**
     * Get item at position (supports negative indexing)
     */
    public function eq(int $position): mixed
    {
        $position = $position < 0 ? count($this->_items) - abs($position) : $position;
        return $this->_items[$position] ?? null;
    }

    /**
     * Remove item at index
     */
    public function remove(int $start): self
    {
        unset($this->_items[$start]);
        return $this;
    }

    /**
     * Get slice of collection
     */
    public function slice(int $start, ?int $length = null): self
    {
        $items = array_slice($this->_items, $start, $length);
        return $this->_returnItems($items);
    }

    /**
     * Filter items that do NOT match criteria
     */
    public function not(mixed ...$criteria): self
    {
        $filtered = $this->_filter(...$criteria);
        return $this->_returnItems($filtered['no']);
    }

    /**
     * Filter items that match criteria
     */
    public function filter(mixed ...$criteria): self
    {
        $filtered = $this->_filter(...$criteria);
        return $this->_returnItems($filtered['yes']);
    }

    /**
     * Execute callback for each item
     */
    public function each(\Closure $callback, array $params = []): self
    {
        foreach ($this->_items as $key => $item) {
            $boundCallback = $callback->bindTo(is_object($item) ? $item : (object) $item);
            $result = $boundCallback($key, $item);
            
            if ($result === self::DONE) {
                break;
            }
        }
        
        return $this;
    }

    /**
     * Convert to JSON string
     */
    public function json(): string
    {
        return json_encode($this->jsonSerialize(), JSON_THROW_ON_ERROR);
    }

    /**
     * Alias for each() method
     */
    public function e(\Closure $callback): self
    {
        return $this->each($callback);
    }

    // Iterator interface implementation
    public function rewind(): void
    {
        $this->_position = 0;
    }

    public function current(): mixed
    {
        return $this->_items[$this->_position] ?? null;
    }

    public function key(): int
    {
        return $this->_position;
    }

    public function next(): void
    {
        ++$this->_position;
    }

    public function valid(): bool
    {
        return isset($this->_items[$this->_position]);
    }

    // Countable interface implementation
    public function count(): int
    {
        return count($this->_items);
    }

    /**
     * Get parent looper (for chained operations)
     */
    public function parent(): ?self
    {
        return $this->_parent;
    }

    /**
     * Internal filtering logic
     * 
     * @return array{yes: array<mixed>, no: array<mixed>}
     */
    private function _filter(mixed ...$args): array
    {
        $items = $this->_items;
        $mismatch = [];
        $filters = [];
        $func = null;

        // Parse arguments
        if (count($args) === 1 && is_callable($args[0])) {
            $func = $args[0];
        } elseif (count($args) === 2 && !is_array($args[0]) && !is_array($args[1])) {
            $filters[] = [$args[0] => $args[1]];
        } else {
            foreach ($args as $arg) {
                if (is_array($arg)) {
                    $filters[] = $arg;
                }
            }
        }

        // Apply property filters
        if ($filters) {
            foreach ($items as $key => $item) {
                $mismatches = 0;
                
                foreach ($filters as $filterSet) {
                    foreach ($filterSet as $property => $value) {
                        if (!is_object($item) || ($item->$property ?? null) !== $value) {
                            $mismatches++;
                        }
                    }
                }
                
                if ($mismatches > 0) {
                    $mismatch[$key] = true;
                }
            }
        }

        // Apply callback filter
        if ($func) {
            foreach ($items as $key => $item) {
                if (!$func($item, $key)) {
                    $mismatch[$key] = true;
                }
            }
        }

        // Separate matching and non-matching items
        $yes = [];
        $no = [];
        
        foreach ($items as $key => $item) {
            if (isset($mismatch[$key])) {
                $no[] = $item;
            } else {
                $yes[] = $item;
            }
        }

        return ['yes' => $yes, 'no' => $no];
    }

    /**
     * Create new looper or return self based on item count change
     */
    private function _returnItems(array $items): self
    {
        if (count($items) !== count($this->_items)) {
            $newLooper = new static($items);
            $newLooper->_parent = $this;
            return $newLooper;
        }
        
        return $this;
    }

    /**
     * String representation of all items
     */
    public function __toString(): string
    {
        $output = '';
        
        foreach ($this->_items as $item) {
            if (is_object($item) && method_exists($item, '__toString')) {
                $output .= $item->__toString();
            } elseif (is_scalar($item)) {
                $output .= (string) $item;
            }
        }
        
        return $output;
    }

    /**
     * Call method on all items and return new looper with results
     */
    public function __call(string $name, array $arguments): self
    {
        $results = [];
        
        foreach ($this->_items as $item) {
            if (is_object($item) && method_exists($item, $name)) {
                $results[] = $item->$name(...$arguments);
            }
        }
        
        return static::o($results);
    }

    /**
     * Get property from first item
     */
    public function &__get(string $name): mixed
    {
        $firstItem = $this->_items[0] ?? null;
        
        if (!$firstItem) {
            $null = null;
            return $null;
        }
        
        if (str_starts_with($name, '_')) {
            return $firstItem->$name;
        }
        
        return $firstItem->_properties[$name] ?? $firstItem->$name;
    }

    /**
     * Set property on all items
     */
    public function __set(string $name, mixed $value): void
    {
        foreach ($this->_items as $item) {
            if (is_object($item)) {
                $item->$name = $value;
            }
        }
    }
}
