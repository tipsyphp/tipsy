<?php

namespace Tipsy;

class Model implements \JsonSerializable {
	private $_methods;
	private $_properties;

	public function json() {
		return json_encode($this->exports());
	}

	public function jsonSerialize() {
		return $this->exports();
	}

	public function addMethod($method, $closure) {
		$this->_methods[$method] = $closure;
	}

	public function hasMethod($method) {
		return $this->_methods[$method] ? true : false;
	}

	public static function __callStatic($method, $args = []) {
		$name = '__'.$method.'_static';

		if (method_exists(get_called_class(),$name)) {
			return (new \ReflectionMethod(get_called_class(), $name))->invokeArgs(null, $args);
		} else {
			throw new Exception('Could not call static ' . $method. ' on '.get_called_class());
		}
	}

	public function __call($method, $args = []) {
		if (is_callable($this->_methods[$method])) {
			$this->_methods[$method] = $this->_methods[$method]->bindTo($this);
			return call_user_func_array($this->_methods[$method], $args);
		} elseif (method_exists($this,'__'.$method)) {
			// @todo: internets say call_user_func_array is faster but who knows
			//return (new \ReflectionMethod($this, '__'.$method))->invokeArgs($this, $args);
			return call_user_func_array([$this, '__'.$method], $args);
		} else {
			throw new Exception('Could not call ' . $method. ' on '.get_class());
		}
	}

	public function &__properties() {
		return $this->_properties ? $this->_properties : get_object_vars($this);
	}

	public function &__property($name) {
		return isset($this->_properties[$name]) ? $this->_properties[$name] : null;
	}

	public function &__get($name) {
		if (isset($name{0}) && $name{0} == '_') {
			return $this->{$name};
		} else {
			return $this->_properties[$name];
		}
	}

	public function __set($name, $value) {
		if ($name{0} == '_') {
			return $this->{$name} = $value;
		} else {
			return $this->_properties[$name] = $value;
		}
	}

	public function __isset($name) {
		return $name{0} == '_' ? isset($this->{$name}) : isset($this->_properties[$name]);
	}

	public function __exports() {
		$out = $this->__properties();
		foreach ($out as $k => $v) {
			if (is_callable($v)) {
				unset($out[$k]);
			}
		}
		return $out;
	}

	public function tipsy($tipsy = null) {
		if (!is_null($tipsy)) {
			$this->_tipsy = $tipsy;
		}
		return $this->_tipsy;
	}
}
