<?php

namespace Tipsy;


/**
 * Route object
 */
class Route {

	protected $_tipsy;
	protected $_routeParams;

	public function __construct($args) {
		$this->_controller = $args['controller'];
		$this->_caseSensitive = $args['caseSensitive'] ? true : false;
		$this->_view = $args['view'] ? true : false;

		if ($args['route']{0} == '/' && !@preg_match($args['route'], null)) {
			$this->_route = $args['route'];
			$this->_regex = true;
		} else {
			$this->_route = preg_replace('/^\/?(.*?)\/?$/i','\\1', $args['route']);
		}

		$this->_tipsy = $args['tipsy'];
		$this->_method = $args['method'] == 'all' ? '*' : $args['method'];

		$this->_routeParams = new RouteParams;
	}

	public function match($page) {

		if ($this->method() != '*') {
			$methods = explode(',',strtolower($this->method()));
			$match = false;

			foreach ($methods as $method) {
				if ($method == strtolower($this->tipsy()->request()->method())) {
					$match = true;
					break;
				}
			}

			if (!$match) {
				return false;
			}
		}

		// index page
		if (($this->_route === '' || $this->_route == '/') && ($page === '' || $page == '/')) {
			return $this;
		}

		$pathParams = [];

		if ($this->_regex) {
			if (preg_match($this->_route, $page, $matches)) {
				$this->_routeParams = $matches;
				return $this;
			}
		} else {

			$paths = explode('/',$this->_route);

			foreach ($paths as $key => $path) {
				if (strpos($path,':') === 0) {
					$pathParams[$key] = substr($path,1);
				}
			}

			$r = preg_replace('/:[a-z]+/i','.*',$this->_route);
			$r = preg_replace('/\//','\/',$r);

			if (preg_match('/^'.$r.'$/'.($this->_caseSensitive ? '' : 'i'),$page)) {
				$paths = explode('/',$page);

				foreach ($pathParams as $key => $path) {
					$this->_routeParams->{$path} = $paths[$key];
				}

				return $this;
			}
		}
		return false;
	}

	public function params() {
		return $this->_routeParams;
	}

	public function controller() {

		if (!isset($this->_controllerRef)) {

			if (is_callable($this->_controller)) {

				$controller = new Controller([
					'closure' => $this->_controller,
					'tipsy' => $this->tipsy()
				]);
				$this->_controllerRef = $controller;

			} elseif(is_object($this->_controller)) {
				$this->_controllerRef = $this->_controller;
				$this->_controllerRef->tipsy($this->tipsy());

			} elseif (is_string($this->_controller) && $this->tipsy()->controller($this->_controller)) {

				$this->_controllerRef = $this->tipsy()->controller($this->_controller);

			} elseif (is_string($this->_controller) && class_exists($this->_controller)) {

				$this->_controllerRef = new $this->_controller(['tipsy' => $this->tipsy()]);
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

	public function tipsy() {
		return $this->_tipsy;
	}

	public function method() {
		return $this->_method;
	}
}
