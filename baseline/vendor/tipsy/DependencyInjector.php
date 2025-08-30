<?php

namespace Tipsy;

class DependencyInjector extends Model {
	private $_closure;
	private $_tipsy;

	public function __construct($args = []) {
		if (isset($args['tipsy'])) {
			$this->tipsy($args['tipsy']);
		}
		if (isset($args['closure'])) {
			$this->closure(\Closure::bind($args['closure'], $this, get_class()));
		}
	}
	public function service($name) {
		return $this->_getDependency($name, $this->_scope);
	}
	private function _getDependency($name, $scope = null) {
		if ($this->tipsy()->services($name) && $name != 'Db') {
			return $this->tipsy()->service($name);

		} else {
			switch ($name) {
				case 'Db':
					return $this->tipsy()->db();
				case 'Route':
					return $this->tipsy()->route();
				case 'Request':
					return $this->tipsy()->request();
				case 'Headers':
					return $this->tipsy()->request()->headers();
				case 'Params':
					return $this->tipsy()->route()->params();
				case 'Tipsy':
					return $this->tipsy();
				case 'View':
					return $this->tipsy()->view();
				case 'Scope':
					return $scope;
				case 'RootScope':
					return $this->tipsy()->rootScope();
			}
		}

		return false;
	}

	public function inject($closure, $scope = null) {
		$avail = ['Db', 'Route', 'Request', 'Headers', 'Params', 'Tipsy', 'View', 'Scope', 'RootScope'];

		if (!$this->tipsy()) {
			throw new Exception('Tipsy is not defined!');
		}

		foreach ($this->tipsy()->services() as $name => $service) {
			$avail[] = $name;
		}

		$args = [];
		$refFunc = new \ReflectionFunction($closure);

		foreach ($refFunc->getParameters() as $refParameter) {
			$name = $refParameter->getName();
			if (in_array($name, $avail)) {
				$args[] = $this->_getDependency($name, $scope);
			} else {
				$args[] = null;
			}
		}

		return call_user_func_array($closure, $args);
	}

	public function closure($closure = null) {
		if ($closure) {
			$this->_closure = $closure;
		}
		return $this->_closure;
	}
}
