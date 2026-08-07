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
	private bool $_caseSensitive = false;
	private bool $_view = false;
	private string $_method = '*';
	private ?string $_compiledRegex = null;
	/** @var array<int, string> */
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
		$this->_route = preg_replace('/^\\/?(.*?)\\/?$/i', '\\1', $route);

		// Don't compile empty routes (home page) — handled by direct comparison
		if ($this->_route === '' || $this->_route === '/') {
			return;
		}

		// Extract named parameters (:name) and build regex
		$paths = explode('/', $this->_route);
		$paramKeys = [];

		foreach ($paths as $key => $path) {
			if (str_starts_with($path, ':')) {
				$paramKeys[$key] = substr($path, 1);
			}
		}

		$this->_paramKeys = $paramKeys;

		// Build the compiled regex once
		$r = preg_replace('/:[a-z_]+/i', '([^/]+)', $this->_route);
		$r = preg_replace('/\\//', '\\/', $r);
		$flags = $this->_caseSensitive ? '' : 'i';
		$this->_compiledRegex = '/^' . $r . '$/' . $flags;
	}

	public function match(string $page): static|string|false {

		if ($this->_method !== '*') {
			$methods = explode(',', strtolower($this->_method));
			$match = false;

			$requestMethod = strtolower($this->_tipsy?->request()->method() ?? 'GET');
			foreach ($methods as $method) {
				if (trim($method) === $requestMethod) {
					$match = true;
					break;
				}
			}

			if (!$match) {
				return false;
			}
		}

		// Index page — direct comparison, no regex needed
		if (($this->_route === '' || $this->_route === '/') && ($page === '' || $page === '/')) {
			return $this;
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
			$captureIndex = 1;
			foreach ($this->_paramKeys as $key) {
				$this->_routeParams->{$key} = $matches[$captureIndex] ?? '';
				$captureIndex++;
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
