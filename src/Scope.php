<?php

declare(strict_types=1);

namespace Tipsy;

#[\AllowDynamicProperties]
class Scope {
	private array $_properties = [];

	public function &__get(string $name): mixed {
		return $this->_properties[$name];
	}

	public function __set(string $name, mixed $value): void {
		$this->_properties[$name] = $value;
	}

	public function __isset(string $name): bool {
		return isset($this->_properties[$name]);
	}

	public function &properties(): array {
		return $this->_properties;
	}
}