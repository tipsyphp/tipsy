<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Route object - handles route matching and parameter extraction
 * Optimized for PHP 8.3+ performance
 */
class Route {

	protected App $_tipsy;
	protected RouteParams $_routeParams;
	protected mixed $_controller;
	protected bool $_caseSensitive;
	protected bool $_view;
	protected string $_route;
	protected bool $_regex = false;
	protected string $_method;
	protected ?Controller $_controllerRef = null;

	public function __construct(array $args) {
		$this->_controller = $args['controller'];
		$this->_caseSensitive = (bool)($args['caseSensitive'] ?? false);
		$this->_view = (bool)($args['view'] ?? false);

		// Check if this is a regex route (starts with / and is valid regex)
		if (isset($args['route'][0]) && $args['route'][0] === '/') {
			// Test if it's a valid regex pattern
			try {
				preg_match($args['route'], '');
				$this->_route = $args['route'];
				$this->_regex = true;
			} catch (\Throwable) {
				// Not a valid regex, treat as normal route
				$this->_route = preg_replace('/^\/?(.*?)\/?$/i', '\\1', $args['route'] ?? '');
			}
		} else {
			$this->_route = preg_replace('/^\/?(.*?)\/?$/i', '\\1', $args['route'] ?? '');
		}

		$this->_tipsy = $args['tipsy'];
		$this->_method = $args['method'] === 'all' ? '*' : ($args['method'] ?? '*');

		$this->_routeParams = new RouteParams();
	}

	public function match(string $page): Route|false {
		// Check HTTP method matching first (early exit for performance)
		if ($this->method() !== '*') {
			$methods = explode(',', strtolower($this->method()));
			$requestMethod = strtolower($this->tipsy()->request()->method());
			
			if (!in_array($requestMethod, $methods, true)) {
				return false;
			}
		}

		// Handle index page matching
		if (($this->_route === '' || $this->_route === '/') && ($page === '' || $page === '/')) {
			return $this;
		}

		// Handle regex routes
		if ($this->_regex) {
			if (preg_match($this->_route, $page, $matches)) {
				$this->_routeParams = $matches;
				return $this;
			}
			return false;
		}

		// Handle parameterized routes
		return $this->matchParameterizedRoute($page);
	}

	private function matchParameterizedRoute(string $page): Route|false {
		$paths = explode('/', $this->_route);
		$pathParams = [];

		// Extract parameter positions
		foreach ($paths as $key => $path) {
			if (str_starts_with($path, ':')) {
				$pathParams[$key] = substr($path, 1);
			}
		}

		// Create regex pattern for matching
		$pattern = preg_replace('/:[a-z_]+/i', '([^/]+)', $this->_route);
		$pattern = str_replace('/', '\/', $pattern);
		$flags = $this->_caseSensitive ? '' : 'i';

		if (preg_match("/^{$pattern}$/{$flags}", $page)) {
			$pagePaths = explode('/', $page);

			// Assign parameter values
			foreach ($pathParams as $key => $paramName) {
				$this->_routeParams->{$paramName} = $pagePaths[$key] ?? '';
			}

			return $this;
		}

		return false;
	}

	public function params(): RouteParams {
		return $this->_routeParams;
	}

	public function controller(): Controller {
		if (!isset($this->_controllerRef)) {
			$this->_controllerRef = $this->createController();
			
			if ($this->_controllerRef) {
				$this->_controllerRef->tipsy()->route($this);
			}
		}

		if (!$this->_controllerRef) {
			throw new Exception('No controller attached to route.');
		}

		return $this->_controllerRef;
	}

	private function createController(): ?Controller {
		return match (true) {
			is_callable($this->_controller) => new Controller([
				'closure' => $this->_controller,
				'tipsy' => $this->tipsy()
			]),
			
			is_object($this->_controller) => $this->setupObjectController(),
			
			is_string($this->_controller) && $this->tipsy()->controller($this->_controller) => 
				$this->tipsy()->controller($this->_controller),
			
			is_string($this->_controller) && class_exists($this->_controller) => 
				new $this->_controller(['tipsy' => $this->tipsy()]),
			
			default => null
		};
	}

	private function setupObjectController(): Controller {
		$controller = $this->_controller;
		$controller->tipsy($this->tipsy());
		return $controller;
	}

	public function tipsy(): App {
		return $this->_tipsy;
	}

	public function method(): string {
		return $this->_method;
	}
}
