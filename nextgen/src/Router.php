<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Handles definition and resolution of routes to controllers
 * Optimized for PHP 8.3+ performance with type safety
 */
class Router {

	private array $_routes;
	private array $_aliass;
	private App $_tipsy;
	private ?Route $_default = null;

	public function __construct(array $args = []) {
		$this->_routes = [];
		$this->_aliass = [];
		$this->_tipsy = $args['tipsy'];
	}

	public function __call(string $method, array $args = []): self {
		if (count($args) === 1) {
			$args[0]['method'] = strtoupper($method);
		} else {
			$args[1] = [
				'controller' => $args[1],
				'method' => strtoupper($method)
			];
		}
		return $this->when(...$args);
	}

	public function alias(string $from, string $to): self {
		$this->_aliass[] = new RouteAlias([
			'to' => $to,
			'from' => $from,
			'tipsy' => $this->_tipsy
		]);
		return $this;
	}

	public function when(string|array|null $r = null, mixed $args = null): self {
		if (is_array($r)) {
			$route = $r;
		} else {
			if (is_array($args)) {
				$route = $args;
			} else {
				$route = ['controller' => $args];
			}
			$route['route'] = $r;
		}
		if ($route['route'] === null) {
			throw new Exception('Invalid route specified.');
		}
		$route['tipsy'] = $this->_tipsy;

		$route['method'] ??= '*';

		$this->_routes[] = new Route($route);

		return $this;
	}

	public function home(mixed $route): self {
		return $this->when('', $route);
	}

	public function otherwise(mixed $default): void {
		$this->_default = new Route([
			'controller' => $default,
			'method' => '*',
			'tipsy' => $this->_tipsy
		]);
	}

	public function match(string $page): Route {
		// Check aliases first - using reverse order for last-defined-wins
		foreach (array_reverse($this->aliass(), true) as $route) {
			if ($alias = $route->match($page)) {
				$page = $alias;
				break;
			}
		}
		
		// Check routes - using reverse order for last-defined-wins  
		foreach (array_reverse($this->routes(), true) as $route) {
			if ($route->match($page)) {
				return $route;
			}
		}

		return $this->defaultRoute();
	}

	public function routes(): array {
		return $this->_routes;
	}

	public function aliass(): array {
		return $this->_aliass;
	}

	public function defaultRoute(): Route {
		return $this->_default ?? new Route(['tipsy' => $this->_tipsy]);
	}
}
