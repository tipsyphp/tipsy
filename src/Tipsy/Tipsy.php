<?php

/**
 * Tipsy
 * An MVW PHP Framework
 *
 * A little bit of a mess. Still a work in progress.
 */


namespace Tipsy;

set_error_handler(function ($errno, $errstr){
	throw new Exception($errstr);
	return false;
});
try {
	date_default_timezone_get();
}
catch(Exception $e) {
	date_default_timezone_set('UTC');
}
restore_error_handler();


/**
 * Main class
 */
class Tipsy {
	private $_controllers;
	private $_config;
	private $_view;
	private $_services;
	private $_route;
	private $_url;
	private $_middlewares;
	private $_middlewareStart;

	public function __construct() {
		$this->_controllers = [];
		$this->_middlewares = [];
		$this->_config = [];
		$this->_services = [];
		$this->_services = [];
		$this->_rootScope = new Scope;
		$this->_middlewareStart = false;
	}

	public function start($url = null) {
		$this->_url = $this->request()->path($url);
		$this->_route = $this->router()->match($this->_url);
		$this->_middlewareStart = true;

		foreach ($this->middlewares() as $middleware) {
			if ($middleware['started']) {
				continue;
			}
			Middleware::_start($middleware, $this);
		}

		$this->_route->controller()->init();
	}
	public function router() {
		if (!isset($this->_router)) {
			$this->_router = new Router(['tipsy' => $this]);
		}
		return $this->_router;
	}
	public function controller($controller, $closure = null) {
		if ($controller && is_callable($closure)) {
			$this->_controllers[$controller] = new Controller([
				'closure' => $closure,
				'tipsy' => $this
			]);
			return $this;

		} elseif ($controller) {
			return $this->_controllers[$controller];

		} else {
			return null;
		}
	}
	public function config($args = null, $recursive = false) {
		$merge = ($recursive ? 'array_merge_recursive' : 'array_merge');
		if (is_string($args)) {
			// assume its a config file

			$iterator = new \GlobIterator($args);

			foreach($iterator as $file) {
			    $config = parse_ini_file($file->getPathname(), true);
			    $this->_config = $merge($this->_config, $config);
			}

			return $this;

		} elseif (is_array($args)) {
			$this->_config = $merge($this->_config, $args);
			return $this;

		} else {
			return $this->_config;
		}
	}

	public function provider($service, $args = []) {
		$this->service($service, $args);
		$this->service($service);
		return $this;
	}

	public function service($service, $args = [], $static = false) {
		list($service, $extend) = $this->_serviceName($service, $args);

		if (!$this->_services[$service]) {

			if ($service && is_callable($args)) {
				$config = ['_controller' => new Service([
					'closure' => $args,
					'tipsy' => $this
				])];


			} elseif ($service && class_exists($service)) { //!$args && @note: not sure why i had this here. tests still pass without it
				$extend = $service;
				if (property_exists($service,'_id')) {
					$config['_id'] = $service::$_id;
				}
				if (property_exists($service,'_table')) {
					$config['_table'] = $service::$_table;
				}

			} elseif ($service && is_array($args)) {
				$config = $args;
			}

			if ($this->_services[$extend]) {
				$extend = $this->_services[$extend];
			}
			
			if ($static) {
				$config['_static'] = true;
			}

			$name = $extend ? $extend : 'Tipsy\Service';
			$config['_service'] = $service;

			$this->_services[$service] = [
				'reflection' => new \ReflectionClass($name),
				'config' => $config
			];

			return $this;

		} else {

			if ($this->_services[$service]['config']['_controller']) {
				$this->_services[$service]['config'] = $this->_services[$service]['config']['_controller']->init(['tipsy' => $this]);
			}

			if ($this->_services[$service]['config']['_static'] && $this->_services[$service]['instance']) {
				return $this->_services[$service]['instance'];
			}
			
			if ($this->_services[$service]['reflection']->hasMethod('__construct')) {
				$config = array_merge(is_array($this->_services[$service]['config']) ? $this->_services[$service]['config'] : [],['_tipsy' => $this],$args);
				$instance = $this->_services[$service]['reflection']->newInstance($config);

			} else {
				$instance = $this->_services[$service]['reflection']->newInstance();
			}

			if ($this->_services[$service]['config']) {
				foreach ($this->_services[$service]['config'] as $name => $config) {
					if (is_callable($config) && method_exists($instance, 'addMethod')) {
						$instance->addMethod($name, $config);
					} else {
						$instance->{$name} = $config;
					}
					$instance->tipsy($this);
				}
			}
			
			if ($this->_services[$service]['config']['_static']) {
				$this->_services[$service]['instance'] = $instance;
			}

			return $instance;
		}
	}
	public function services($service = null) {
		if ($service) {
			return $this->_services[$service] ? true : false;
		}
		return $this->_services;
	}
	public function db() {
		if (!isset($this->_db)) {
			$this->_db = new Db($this->_config['db']);

			// kill the db config in case something gets outputted
			unset($this->_config['db']);
		}
		return $this->_db;
	}
	public function view() {
		if (!isset($this->_view)) {
			$config = $this->_config['view'];
			$config['tipsy'] = $this;
			$this->_view = new View($config);
		}
		return $this->_view;
	}
	public function request() {
		if (!isset($this->_request)) {
			$this->_request = new Request;
		}
		return $this->_request;
	}

	private function _serviceName($service, $args = []) {
		if (!is_null($args) && strpos($service, '/')) {
			$service = explode('/',$service);
			if (count($service) > 2) {
				throw new Exception('Cant extend more than one model.');
			} elseif (count($service) > 1) {
				$extend = array_shift($service);
			}
			$service = array_shift($service);
		}
		return [$service, $extend];
	}

	public function rootScope() {
		return $this->_rootScope;
	}
	public function route() {
		return $this->_route;
	}
	public function url() {
		return $this->_url;
	}

	public function middleware($service, $args = []) {
		if (!$this->_services[$service]) {
			$this->service($service, $args, true);
		}
		if ($this->_middlewares[$service]) {
			return $this->service($service);
		}
		$middleware = [
			'service' => $service,
			'args' => $args,
			'started' => $this->_middlewareStart
		];
		$this->_middlewares[$service] = $middleware;
		if ($this->_middlewareStart) {
			Middleware::_start($middleware, $this);
		}
		return $this->service($service);
	}

	public function middlewares() {
		return $this->_middlewares;
	}
}












class RouteParams extends Scope {

}


class View_Filter {

}


class StripWhite extends View_Filter {
	public static function filter($content) {
		$find = [
			'/^(\s?)(.*?)(\s?)$/',
			'/\t|\n|\r/',
			'/(\<\!\-\-)(.*?)\-\-\>/'
		];
		$replace = [
			'\\2',
			'',
			''
		];
		return preg_replace($find, $replace, $content);
	}
}


