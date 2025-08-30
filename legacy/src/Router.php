<?php

namespace Tipsy;

/**
 * Handles definition and resolution of routes to controllers
 */
class Router {

	private $_routes;
	private $_aliass;
	private $_tipsy;

	public function __construct($args = []) {
		$this->_routes = [];
		$this->_aliass = [];
		$this->_tipsy = $args['tipsy'];
	}

	public function __call($method, $args = []) {
		if (count($args) == 1) {
			$args[0]['method'] = strtoupper($method);
		} else {
			$args[1] = [
				'controller' => $args[1],
				'method' => strtoupper($method)
			];
		}
		return call_user_func_array([$this, 'when'], $args);
	}

	public function alias($from, $to) {
		$this->_aliass[] = new RouteAlias([
			'to' => $to,
			'from' => $from,
			'tipsy' => $this->_tipsy
		]);
		return $this;
	}

	public function when($r = null, $args = null) {
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
		if (is_null($route['route'])) {
			throw new Exception('Invalid route specified.');
		}
		$route['tipsy'] = $this->_tipsy;

		if (!$route['method']) {
			$route['method'] = '*';
		}

		$this->_routes[] = new Route($route);

		return $this;
	}

	public function home($route) {
		return $this->when('', $route);
	}

	public function otherwise($default) {
		$this->_default = new Route([
			'controller' => $default,
			'method' => '*',
			'tipsy' => $this->_tipsy
		]);
	}

	public function match($page) {
		foreach (array_reverse($this->aliass(), true) as $route) {
			if ($alias = $route->match($page)) {
				$page = $alias;
				break;
			}
		}
		foreach (array_reverse($this->routes(), true) as $route) {
			if ($route->match($page)) {
				return $route;
			}
		}

		return $this->defaultRoute();
	}

	public function routes($routes = null) {
		return $this->_routes;
	}

	public function aliass($aliass = null) {
		return $this->_aliass;
	}

	public function defaultRoute() {
		return $this->_default ? $this->_default : new Route(['tipsy' => $this->_tipsy]);
	}

}
