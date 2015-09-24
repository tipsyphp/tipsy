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
	
	public function __construct() {
		$this->_controllers = [];
		$this->_config = [];
		$this->_services = [];
		$this->_services = [];
		$this->_rootScope = new Scope;
	}

	public function start($url = null) {
		$this->_url = $this->request()->path($url);
		$this->_route = $this->router()->match($this->_url);
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

	public function service($service, $args = []) {
		list($service, $extend) = $this->_serviceName($service, $args);

		if (!$this->_services[$service]) {

			if ($service && is_callable($args)) {
				$config = ['_controller' => new Service([
					'closure' => $args,
					'tipsy' => $this
				])];


			} elseif ($service && !$args && class_exists($service)) {
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
		if (!is_null($args)) {
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


// @todo: clean this up. its just a copy and paste from cana. we dont need all of it
class Looper implements \Iterator {
	private $_items;
	private $_position;

	public function __construct() {
		$items = [];
		foreach (func_get_args() as $arg) {
			if (is_object($arg) && (get_class($arg) == 'Looper' || is_subclass_of($arg,'Looper'))) {
				$arg = $arg->items();
			} elseif (is_object($arg)) {
				$arg = [$arg];
			}
			$items = array_merge((array)$arg, $items);
		}

		$this->_items = $items;
		$this->_position = 0;
	}
	
	// if anyone knows any way to pass func_get_args by reference i would love you. i want string manipulation
	public static function o() {
		$iterator = new ReflectionClass(get_called_class());
		return $iterator->newInstanceArgs(func_get_args());
	}
	
	public function items() {
		return $this->_items;
	}
	
	public function get($index) {
		return $this->_items[$index];
	}

	public function eq($pos) {
		$pos = $pos < 0 ? count($this->_items) - abs($pos) : $pos;
		return $this->_items[$pos];
	}
	
	public function remove($start) {
		unset($this->_items[$start]);
		return $this;
	}
	
	public function slice($start, $end = null) {
		$items = $this->_items;
		$items = array_slice($items, $start, $end);

		return $this->_returnItems($items);
	}
	
	public function not() {
		$items = call_user_func_array([$this, '_filter'], func_get_args());
		return $this->_returnItems($items['no']);
	}
		
	public function filter() {
		$items = call_user_func_array([$this, '_filter'], func_get_args());
		return $this->_returnItems($items['yes']);
	}
	
	public function each($func, $params = []) {
		foreach ($this->_items as $key => $item) {
			$func = $func->bindTo(!is_object($item) ? (object)$item : $item);
			$func($key, $item);
			$this->_items[$key] = $item;
		}
	}
	
	public function e($f) {
		self::each($f);
	}

	public function rewind() {
		$this->_position = 0;
	}

	public function current() {
		return $this->_items[$this->_position];
	}

	public function key() {
		return $this->_position;
	}

	public function next() {
		++$this->_position;
	}

	public function valid() {
		return isset($this->_items[$this->_position]);
	}
	
	public function json() {
		foreach ($this->_items as $key => $item) {
			if (is_callable($item, 'exports') || method_exists($item, 'exports')) {
				$items[$key] = (new \ReflectionMethod($item, 'exports'))->invokeArgs($item, []);
			}
			$items[$key] = $item->exports();
		}
		return json_encode($items);
	}
	
	public function count() {
		return count($this->_items);
	}

	public function parent() {
		return $this->_parent;
	}

	private function _filter() {
		$items = $this->_items;
		$mismatch = [];
		$strict = false;

		if (func_num_args() == 1 && is_callable(func_get_arg(0))) {
			$func = func_get_arg(0);

		} elseif (func_num_args() == 2 && !is_array(func_get_arg(0)) && !is_array(func_get_arg(1))) {
			$filters[][func_get_arg(0)] = func_get_arg(1);

		} else {
			foreach (func_get_args() as $arg) {
				if (is_array($arg)) {
					$filters[] = $arg;
				}
			}
		}

		if ($filters) {
			foreach ($filters as $key => $set) {
				foreach ($items as $key => $item) {
					$mis = 0;
					foreach ($set as $k => $v) {
						if ($item->{$k} != $v) {
							$mis++;
						}
					}
					if (($strict && count($set) == $mis) || $mis) {
						$mismatch[$key]++;
					}
				}
			}
		}
		
		if ($func) {
			foreach ($items as $key => $item) {
				if (!$func($item,$key)) {
					$mismatch[$key] = $key;
					break;
				}
			}
		}

		foreach ($items as $key => $value) {
			if (array_key_exists($key, $mismatch) && ($func || $mismatch[$key] == count($filters))) {
				$trash[] = $items[$key];
			} else {
				$newitems[] = $items[$key];
			}
		}
		
		return ['yes' => $newitems,'no' => $trash];
	}

	private function _returnItems($items) {
		if (count($items) != count($this->_items)) {
			$return = new self($items);
			$return->_parent = $this;
		} else {
			$return = $this;
		}
		return $return;
	}
	
	public function __toString() {
		$print = '';
		foreach ($this->_items as $key => $item) {
			if (is_object($item) && method_exists($item,'__toString')) {
				$print .= $item->__toString();
			} elseif (is_string($item) || is_int($item)) {
				$print .= $item;
			}
		}
		return $print;
	}
	
	// export all available objects as a csv. asume that they are all table objects
	// may not be the best place to put this but o well. exporting iterators is great.
	public function csv() {

		$fields = [];
		foreach ($this->_items as $key => $item) {
			if (is_object($item) && method_exists($item,'csv')) {
				foreach ($item->csv() as $field => $value) {
					$fields[$field] = $field;
				}
			}
		}
		$output = '';
		foreach ($fields as $field) {
			$output .= ($output ? ',' : '').$field;
		}
		$output .= "\n";
		foreach ($this->_items as $key => $item) {
			if (is_object($item) && method_exists($item,'csv')) {
				$o = $item->csv();
				foreach ($fields as $field) {
					$output .= '"'.addslashes($o[$field]).'",';
				}
				$output = substr($output,0,-1);
				$output .= "\n";
			}
		}
		return $output;
		
	}
	
	public function __call($name, $arguments) {
		foreach ($this->_items as $key => $item) {
			if (is_callable($item, $name) || method_exists($item, $name)) {
				$items[] = (new \ReflectionMethod($item, $name))->invokeArgs($item, $arguments);
			} else {
				// not callable
			}
		}

		return i::o($items);
	}

	public function &__get($name) {
		if (property_exists($this,$name)) {
			return $this->{$name};
		} else {
			if (isset($name{0}) && $name{0} == '_') {
				return $this->_items[0]->{$name};
			} else {
				return $this->_items[0]->_properties[$name];
			}
		}
	}

	public function __set($name, $value) {
		if (property_exists($this,$name)) {
			$this->{$name} = $value;
		} else {
			foreach ($this->_items as $key => $item) {
				$this->_items[$key]->{$name} = $value;
			}
		}
		return $value;
	}
	
	public function __isset($property) {
		if (isset($property{0}) && $property{0} == '_') {
			return $this->_items[0]->{$property} ? true : false;
		} else {
			return $this->_items[0]->_properties[$property] ? true : false;
		}
	}
	
	public function __unset($property) {
		if (isset($property{0}) && $property{0} == '_') {
			unset($this->_items[0]->{$property});
		} else {
			unset($this->_items[0]->_properties[$property]);
		}
		return $this;
	}
}


function joinPaths() {
	$args = func_get_args();
	$paths = [];
	foreach ($args as $arg) {
		$paths = array_merge($paths, (array)$arg);
	}

	$paths = array_map(create_function('$p', 'return trim($p, "/");'), $paths);
	$paths = array_filter($paths);
	return join('/', $paths);
}
