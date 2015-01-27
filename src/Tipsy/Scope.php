<?php

namespace Tipsy;

class Scope {
	private $_properties;
	
	public function __construct() {
		$this->_properties = [];
	}

	public function &__get($name) {
		return $this->_properties[$name];
	}

	public function __set($name, $value) {
		return $this->_properties[$name] = $value;
	}
	
	public function &properties() {
		return $this->_properties;
	}
}