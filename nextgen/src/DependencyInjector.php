<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Dependency Injector - handles automatic dependency resolution
 * Optimized for PHP 8.3+ with performance improvements
 */
class DependencyInjector extends Model {
	private ?\Closure $_closure = null;
	private ?App $_tipsy = null;

	public function __construct(array $args = []) {
		if (isset($args['tipsy'])) {
			$this->tipsy($args['tipsy']);
		}
		if (isset($args['closure'])) {
			$this->closure(\Closure::bind($args['closure'], $this, static::class));
		}
	}

	public function service(string $name): mixed {
		return $this->_getDependency($name, $this->_scope ?? null);
	}

	private function _getDependency(string $name, mixed $scope = null): mixed {
		if ($this->tipsy()->services($name) && $name !== 'Db') {
			return $this->tipsy()->service($name);
		}

		return match ($name) {
			'Db' => $this->tipsy()->db(),
			'Route' => $this->tipsy()->route(),
			'Request' => $this->tipsy()->request(),
			'Headers' => $this->tipsy()->request()->headers(),
			'Params' => $this->tipsy()->route()->params(),
			'Tipsy' => $this->tipsy(),
			'View' => $this->tipsy()->view(),
			'Scope' => $scope,
			'RootScope' => $this->tipsy()->rootScope(),
			default => false
		};
	}

	public function inject(\Closure $closure, mixed $scope = null): mixed {
		$builtInDependencies = ['Db', 'Route', 'Request', 'Headers', 'Params', 'Tipsy', 'View', 'Scope', 'RootScope'];

		if (!$this->tipsy()) {
			throw new Exception('Tipsy is not defined!');
		}

		// Collect all available dependencies
		$availableDependencies = array_merge(
			$builtInDependencies,
			array_keys($this->tipsy()->services())
		);

		$args = [];
		$refFunc = new \ReflectionFunction($closure);

		foreach ($refFunc->getParameters() as $refParameter) {
			$name = $refParameter->getName();
			$args[] = in_array($name, $availableDependencies, true) 
				? $this->_getDependency($name, $scope)
				: null;
		}

		return $closure(...$args);
	}

	public function closure(?\Closure $closure = null): ?\Closure {
		if ($closure !== null) {
			$this->_closure = $closure;
		}
		return $this->_closure;
	}
}
