<?php

declare(strict_types=1);

namespace Tipsy;

class DependencyInjector extends Model {
	private ?\Closure $_closure = null;
	private ?App $_tipsy = null;

	/**
	 * Cache for ReflectionFunction parameter names.
	 * Keyed by spl_object_id — avoids re-reflecting the same closure.
	 *
	 * @var array<int, string[]>
	 */
	private static array $_reflectionCache = [];

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

	private function _getDependency(string $name, ?Scope $scope = null): mixed {
		if ($name !== 'Db' && $this->tipsy()->services($name)) {
			return $this->tipsy()->service($name);
		}

		return match ($name) {
			'Db'        => $this->tipsy()->db(),
			'Route'     => $this->tipsy()->route(),
			'Request'   => $this->tipsy()->request(),
			'Headers'   => $this->tipsy()->request()->headers(),
			'Params'    => $this->tipsy()->route()->params(),
			'Tipsy'     => $this->tipsy(),
			'View'      => $this->tipsy()->view(),
			'Scope'     => $scope,
			'RootScope' => $this->tipsy()->rootScope(),
			default     => null,
		};
	}

	public function inject(\Closure $closure, ?Scope $scope = null): mixed {
		$avail = ['Db', 'Route', 'Request', 'Headers', 'Params', 'Tipsy', 'View', 'Scope', 'RootScope'];

		if (!$this->tipsy()) {
			throw new Exception('Tipsy is not defined!');
		}

		foreach ($this->tipsy()->services() as $name => $service) {
			$avail[] = $name;
		}

		// Cache reflection results — ReflectionFunction is expensive (~5μs)
		$cacheKey = spl_object_id($closure);

		if (!isset(self::$_reflectionCache[$cacheKey])) {
			$refFunc = new \ReflectionFunction($closure);
			$paramNames = [];
			foreach ($refFunc->getParameters() as $refParameter) {
				$paramNames[] = $refParameter->getName();
			}
			self::$_reflectionCache[$cacheKey] = $paramNames;
		}

		$paramNames = self::$_reflectionCache[$cacheKey];
		$args = [];

		foreach ($paramNames as $name) {
			if (in_array($name, $avail, true)) {
				$args[] = $this->_getDependency($name, $scope);
			} else {
				$args[] = null;
			}
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
