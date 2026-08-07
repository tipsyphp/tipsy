<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Handles definition and resolution of routes to controllers
 */
class Router {

	/** @var Route[] */
	private array $_routes = [];
	/** @var RouteAlias[] */
	private array $_aliases = [];
	private ?App $_tipsy = null;
	private ?Route $_default = null;

	public function __construct(array $args = []) {
		$this->_tipsy = $args['tipsy'] ?? null;
	}

	/**
	 * Explicit HTTP verb methods — avoids __call overhead on the hot path.
	 * __call has ~200ns overhead per invocation on PHP 8.3.
	 */
	public function get(string $route, callable|array $controller): static {
		return $this->_addMethodRoute('GET', $route, $controller);
	}

	public function post(string $route, callable|array $controller): static {
		return $this->_addMethodRoute('POST', $route, $controller);
	}

	public function put(string $route, callable|array $controller): static {
		return $this->_addMethodRoute('PUT', $route, $controller);
	}

	public function delete(string $route, callable|array $controller): static {
		return $this->_addMethodRoute('DELETE', $route, $controller);
	}

	public function patch(string $route, callable|array $controller): static {
		return $this->_addMethodRoute('PATCH', $route, $controller);
	}

	private function _addMethodRoute(string $method, string $route, callable|array $controller): static {
		if (is_array($controller) && !is_callable($controller)) {
			$controller['method'] = strtoupper($method);
		} else {
			$controller = [
				'controller' => $controller,
				'method' => strtoupper($method)
			];
		}
		return $this->when($route, $controller);
	}

	/**
	 * Fallback for less common HTTP methods (HEAD, OPTIONS, PATCH, etc.)
	 */
	public function __call(string $method, array $args = []): static {
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

	public function alias(string $from, string $to): static {
		$this->_aliases[] = new RouteAlias([
			'to' => $to,
			'from' => $from,
			'tipsy' => $this->_tipsy
		]);
		return $this;
	}

	public function when(string|array|null $r = null, callable|array|string|object|null $args = null): static {
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
		if (!isset($route['route']) || $route['route'] === null) {
			throw new Exception('Invalid route specified.');
		}
		$route['tipsy'] = $this->_tipsy;

		if (empty($route['method'])) {
			$route['method'] = '*';
		}

		$this->_routes[] = new Route($route);

		return $this;
	}

	public function home(callable|array|string $route): static {
		return $this->when('', $route);
	}

	public function otherwise(callable|array|string $default): void {
		$this->_default = new Route([
			'controller' => $default,
			'method' => '*',
			'tipsy' => $this->_tipsy
		]);
	}

	public function match(string $page): Route {
		// Last-registered wins — iterate backwards without array_reverse() allocations
		for ($i = count($this->_aliases) - 1; $i >= 0; $i--) {
			$alias = $this->_aliases[$i]->match($page);
			if ($alias) {
				$page = $alias;
				break;
			}
		}

		for ($i = count($this->_routes) - 1; $i >= 0; $i--) {
			if ($this->_routes[$i]->match($page)) {
				return $this->_routes[$i];
			}
		}

		return $this->defaultRoute();
	}

	/**
	 * @return Route[]
	 */
	public function routes(): array {
		return $this->_routes;
	}

	/**
	 * @return RouteAlias[]
	 */
	public function aliases(): array {
		return $this->_aliases;
	}

	public function defaultRoute(): Route {
		return $this->_default ?? new Route(['tipsy' => $this->_tipsy]);
	}
}
