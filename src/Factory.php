<?php

declare(strict_types=1);

namespace Tipsy;


class Factory extends Model {
	private array $_objectMap = [];
	private ?App $_tipsy = null;

	public function __construct(App $tipsy) {
		$this->_objectMap = [];
		$this->_tipsy = $tipsy;
	}

	public function count(): int {
		$count = 0;
		foreach ($this->_objectMap as $group) {
			$count += is_array($group) ? count($group) : 0;
		}
		return $count;
	}

	public function objectMap(mixed $a = null, mixed $b = null): mixed {
		// create a new object if not caching
		if (($this->_tipsy->config()['tipsy']['factory'] ?? null) === false) {
			$obj = new $a($b);

		} else {

			$t = null;
			if (is_string($a) && class_exists($a)) {
				$t = new $a();
			}

			// NOCACHE: if the first param is an object, and you gave us the id, use the id you gave us
			if (is_object($a) && (is_string($b) || is_int($b))) {
				$obj = $this->_objectMap[get_class($a)][$b] = $a;

			// CACHED: if the first param is an object, the second is an id, and we have it cached
			} elseif (is_object($a) && (is_string($b) || is_int($b)) && isset($this->_objectMap[get_class($a)][$a->{$b}])) {
				$obj = $this->_objectMap[get_class($a)][$a->{$b}];

			// CACHED: if the first param is an object, and we have it cached
			} elseif (is_object($a) && method_exists($a, 'idVar') && isset($this->_objectMap[get_class($a)][$a->{$a->idVar()}])) {
				$obj = $this->_objectMap[get_class($a)][$a->{$a->idVar()}];

			// NOCACHE: if the first param is an object with no other info, store it
			} elseif (is_object($a) && method_exists($a, 'idVar')) {
				$obj = $this->_objectMap[get_class($a)][$a->{$a->idVar()}] = $a;

			// CACHED: if the first param is the type of object, and the second one is the id
			} elseif (is_string($a) && (is_string($b) || is_int($b)) && isset($this->_objectMap[$a][$b])) {
				$obj = $this->_objectMap[$a][$b];

			// CACHED: if the first param is the type of object, and the second one is the object
			} elseif (is_string($a) && is_object($b) && $t !== null && method_exists($t, 'idVar') && isset($this->_objectMap[$a][$b->{$t->idVar()}])) {
				$obj = $this->_objectMap[$a][$b->{$t->idVar()}];

			// NOCACHE: we dont have it, so make it and store it
			} elseif ($a) {
				$obj = new $a($b);
				if (method_exists($obj, 'idVar') && !isset($this->_objectMap[get_class($obj)][$obj->{$obj->idVar()}])) {
					$this->_objectMap[get_class($obj)][$obj->{$obj->idVar()}] = $obj;
				}

			// NOCACHE: you didnt give us anything to work with
			} else {
				$obj = new Model();
			}
		}

		$t = null;
		return $obj;
	}
}
