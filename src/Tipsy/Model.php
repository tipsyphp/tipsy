<?php
	
namespace Tipsy;

class Model {
	private $_methods;
	private $_properties;

	public function json() {
		return json_encode($this->exports());
	}

	public function addMethod($method, $closure) {
		$this->_methods[$method] = $closure;
	}

	public function __call($method, $args) {
		if (is_callable($this->_methods[$method])) {
			$this->_methods[$method] = $this->_methods[$method]->bindTo($this);
			return call_user_func_array($this->_methods[$method], $args);
		} elseif (method_exists($this,'__'.$method)) {
			return call_user_func_array([$this, '__'.$method], $args);
		} else {
			throw new Exception('Could not call ' . $method. ' on '.get_class());
		}
	}

	public function &__properties() {
		return $this->_properties ? $this->_properties : get_object_vars($this);
	}

	public function __property($name) {
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
		return $this->__properties();
	}

	public function csv() {
		$csv = $this->__properties();
		if ($this->idVar() != 'id') {
			unset($csv['id']);
		}
		return $csv;
	}
	
	public function tipsy($tipsy = null) {
		if (!is_null($tipsy)) {
			$this->_tipsy = $tipsy;
		}
		return $this->_tipsy;
	}

}