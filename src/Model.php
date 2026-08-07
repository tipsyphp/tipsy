<?php

declare(strict_types=1);

namespace Tipsy;

#[\AllowDynamicProperties]
class Model implements \JsonSerializable {
	private array $_methods = [];
	private array $_properties = [];

	public function json(): string {
		return json_encode($this->exports());
	}

	public function jsonSerialize(): mixed {
		return $this->exports();
	}

	public function addMethod(string $method, callable $closure): void {
		$this->_methods[$method] = $closure;
	}

	public function hasMethod(string $method): bool {
		return isset($this->_methods[$method]) && is_callable($this->_methods[$method]);
	}

	public static function __callStatic(string $method, array $args = []): mixed {
		$name = '__' . $method . '_static';

		if (method_exists(static::class, $name)) {
			return (new \ReflectionMethod(static::class, $name))->invokeArgs(null, $args);
		} else {
			throw new Exception('Could not call static ' . $method . ' on ' . static::class);
		}
	}

	public function __call(string $method, array $args = []): mixed {
		if (isset($this->_methods[$method]) && is_callable($this->_methods[$method])) {
			$this->_methods[$method] = $this->_methods[$method]->bindTo($this);
			return ($this->_methods[$method])(...$args);
		} elseif (method_exists($this, '__' . $method)) {
			return $this->{'__' . $method}(...$args);
		} else {
			throw new Exception('Could not call ' . $method . ' on ' . static::class);
		}
	}

	public function &__properties(): array {
		if (!$this->_properties) {
			// Copy dynamic public props into the bag so we can return by reference safely
			foreach (get_object_vars($this) as $key => $value) {
				if ($key !== '_properties' && $key !== '_methods' && !(isset($key[0]) && $key[0] === '_')) {
					$this->_properties[$key] = $value;
				}
			}
		}
		return $this->_properties;
	}

	public function &__property(string $name): mixed {
		return $this->_properties[$name];
	}

	public function &__get(string $name): mixed {
		if (isset($name[0]) && $name[0] === '_') {
			return $this->{$name};
		} else {
			return $this->_properties[$name];
		}
	}

	public function __set(string $name, mixed $value): void {
		if ($name[0] === '_') {
			$this->{$name} = $value;
		} else {
			$this->_properties[$name] = $value;
		}
	}

	public function __isset(string $name): bool {
		return $name[0] === '_' ? isset($this->{$name}) : isset($this->_properties[$name]);
	}

	public function __exports(): array {
		$out = $this->__properties();
		foreach ($out as $k => $v) {
			if (is_callable($v)) {
				unset($out[$k]);
			}
		}
		return $out;
	}

	public function tipsy(?App $tipsy = null): ?App {
		if ($tipsy !== null) {
			$this->_tipsy = $tipsy;
		}
		return $this->_tipsy ?? null;
	}
}
