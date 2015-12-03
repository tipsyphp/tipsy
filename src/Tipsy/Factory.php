<?php

namespace Tipsy;


class Factory extends Model {
	private $_objectMap;

	public function __construct($tipsy) {
		$this->_objectMap = [];
		$this->_tipsy = $tipsy;
	}

	public function objectMap($a, $b = null) {
		// create a new object if not caching
		if ($this->_tipsy->config()['tipsy']['factory'] === false) {
			$obj = new $a($b);

		} else {

			// @todo: i dont think this is right...
			if (is_string($a)) {
				$t = new $a;
			}

			// NOCACHE: if the first param is an object, and you gave us the id, use the id you gave us
			if (is_object($a) && (is_string($b) || is_int($b))) {
				$obj = $this->_objectMap[get_class($a)][$b] = $a;

			// CACHED: if the first param is an object, the second is an id, and we have it cached
			} elseif (is_object($a) && (is_string($b) || is_int($b)) && $this->_objectMap[get_class($a)][$a->{$b}]) {
				$obj = $this->_objectMap[get_class($a)][$a->{$b}];

			// CACHED: if the first param is an object, and we have it cached
			} elseif (is_object($a) && method_exists($a, 'idVar') && $this->_objectMap[get_class($a)][$a->{$a->idVar()}]) {
				$obj = $this->_objectMap[get_class($a)][$a->{$a->idVar()}];

			// NOCACHE: if the first param is an object with no other info, store it. these come from Resource typicaly
			} elseif (is_object($a) && method_exists($a, 'idVar')) {
				$obj = $this->_objectMap[get_class($a)][$a->{$a->idVar()}] = $a;

			// CACHED: if the first param is the type of object, and the second one is the id
			} elseif (is_string($a) && (is_string($b) || is_int($b)) && $this->_objectMap[$a][$b]) {
				$obj = $this->_objectMap[$a][$b];

			// CACHED: if the first param is the type of object, and the second one is the object and we didnt know that we already had it
			} elseif (is_string($a) && is_object($b) && method_exists($t, 'idVar') && $this->_objectMap[$a][$b->{$t->idVar()}]) {
				$obj = $this->_objectMap[$a][$b->{$t->idVar()}];

			// NOCACHE: we dont have it, so make it and store it
			} elseif ($a) {
				$obj = new $a($b);
				if (!$this->_objectMap[get_class($obj)][$obj->{$obj->idVar()}]) {
					$this->_objectMap[get_class($obj)][$obj->{$obj->idVar()}] = $obj;
				}

			// NOCACHE: you didnt give us anything to work with
			} else {
				$obj = new Model;
			}
		}

		// return an object of some type
		$t = null;
		return $obj;
	}
}
