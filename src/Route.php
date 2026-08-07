<?php

declare(strict_types=1);

namespace Tipsy;


/**
 * Route object
 */
class Route {

	protected ?App $_tipsy = null;
	protected RouteParams $_routeParams;
	private string $_route = '';
	private bool $_regex = false;
	private bool $_exact = false;
	private bool $_caseSensitive = false;
	private bool $_view = false;
	private string $_method = '*';
	/** @var list<string>|null Uppercased allowed methods; null means any */
	private ?array $_methods = null;
	private ?string $_compiledRegex = null;
	/** @var list<string> */
	private array $_paramKeys = [];
	private mixed $_controller = null;
	private ?DependencyInjector $_controllerRef = null;

	public function __construct(array $args = []) {
		$this->_controller = $args['controller'] ?? null;
		$this->_caseSensitive = !empty($args['caseSensitive']);
		$this->_view = !empty($args['view']);
		$this->_tipsy = $args['tipsy'] ?? null;
		$this->_method = ($args['method'] ?? '*') === 'all' ? '*' : ($args['method'] ?? '*');
		$this->_routeParams = new RouteParams();

		if ($this->_method !== '*') {
			$methods = [];
			foreach (explode(',', $this->_method) as $method) {
				$method = strtoupper(trim($method));
				if ($method !== '') {
					$methods[] = $method;
				}
			}
			$this->_methods = $methods;
		}

		$route = $args['route'] ?? '';
		$this->_compileRoute($route);
	}

	/**
	 * Pre-compile route pattern into a regex at construction time.
	 * Avoids rebuilding regex on every match() call.
	 */
	private function _compileRoute(string $route): void {
		// Check if the route is already a regex (starts with / and is valid preg)
		if ($route !== '' && $route[0] === '/' && @preg_match($route, '') !== false) {
			$this->_route = $route;
			$this->_regex = true;
			$this->_compiledRegex = $route;
			return;
		}

		// Strip leading/trailing slashes for normalized matching
		$this->_route = preg_replace('/^\\/?(.*?)\\/?$/i', '\\1', $route) ?? $route;

		// Don't compile empty routes (home page) — handled by direct comparison
		if ($this->_route === '' || $this->_route === '/') {
			$this->_exact = true;
			return;
		}

		// Extract named parameters (:name) and build regex
		$paths = explode('/', $this->_route);
		$paramKeys = [];

		foreach ($paths as $path) {
			if (str_starts_with($path, ':')) {
				$paramKeys[] = substr($path, 1);
			}
		}

		$this->_paramKeys = $paramKeys;

		// Static route — string compare is cheaper than preg_match
		if ($paramKeys === []) {
			$this->_exact = true;
			return;
		}

		// Build the compiled regex once
		$r = preg_replace('/:[a-z_]+/i', '([^/]+)', $this->_route);
		$r = str_replace('/', '\\/', $r ?? '');
		$flags = $this->_caseSensitive ? '' : 'i';
		$this->_compiledRegex = '/^' . $r . '$/' . $flags;
	}

	public function match(string $page): static|string|false {

		if ($this->_methods !== null) {
			$requestMethod = $this->_tipsy?->request()->method() ?? 'GET';
			if (!in_array($requestMethod, $this->_methods, true)) {
				return false;
			}
		}

		// Exact / static routes — no regex
		if ($this->_exact) {
			if ($this->_caseSensitive) {
				return $page === $this->_route ? $this : false;
			}
			return strcasecmp($page, $this->_route) === 0 ? $this : false;
		}

		// User-supplied regex route
		if ($this->_regex) {
			if (preg_match($this->_compiledRegex, $page, $matches)) {
				foreach ($matches as $k => $v) {
					$this->_routeParams->{$k} = $v;
				}
				return $this;
			}
			return false;
		}

		// Pre-compiled parametric route
		if ($this->_compiledRegex !== null && preg_match($this->_compiledRegex, $page, $matches)) {
			foreach ($this->_paramKeys as $i => $key) {
				$this->_routeParams->{$key} = $matches[$i + 1] ?? '';
			}
			return $this;
		}

		return false;
	}

	public function params(): RouteParams|array {
		return $this->_routeParams;
	}

	public function controller(): DependencyInjector {

		if (!isset($this->_controllerRef)) {

			if (is_callable($this->_controller)) {

				$controller = new Controller([
					'closure' => $this->_controller,
					'tipsy' => $this->tipsy()
				]);
				$this->_controllerRef = $controller;

			} elseif (is_object($this->_controller)) {
				$this->_controllerRef = $this->_controller;
				$this->_controllerRef->tipsy($this->tipsy());

			} elseif (is_string($this->_controller) && $this->tipsy()->controller($this->_controller)) {

				$this->_controllerRef = $this->tipsy()->controller($this->_controller);

			} elseif (is_string($this->_controller) && class_exists($this->_controller)) {

				$this->_controllerRef = new ($this->_controller)(['tipsy' => $this->tipsy()]);
			}

			if ($this->_controllerRef) {
				$this->_controllerRef->tipsy()->route($this);
			}
		}

		if (!$this->_controllerRef) {
			throw new Exception('No controller attached to route.');
		}

		return $this->_controllerRef;
	}

	public function tipsy(?App $tipsy = null): ?App {
		if ($tipsy !== null) {
			$this->_tipsy = $tipsy;
		}
		return $this->_tipsy;
	}

	public function method(): string {
		return $this->_method;
	}
}
