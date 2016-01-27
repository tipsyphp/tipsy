<?php

namespace Tipsy;

class Looper implements \Iterator, \JsonSerializable {
	private $_items;
	private $_position;

	const DONE = 'looper\break';

	public function jsonSerialize() {
		foreach ($this->_items as $key => $item) {
			if (is_object($item) && method_exists($item, 'exports')) {
				$items[$key] = $item->exports();
			} else {
				$items[$key] = $item;
			}
		}
		return $items;
	}

	public function __construct() {
		$items = [];
		$args = func_get_args();
		$args = array_reverse($args);

		foreach ($args as $arg) {
			if (is_object($arg) && (get_class($arg) == 'Tipsy\Looper' || is_subclass_of($arg, 'Tipsy\Looper'))) {
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
		$iterator = new \ReflectionClass(get_called_class());
		return $iterator->newInstanceArgs(func_get_args());
	}

	public function items() {
		return $this->_items;
	}

	public function get($index) {
		return $this->eq($index);
	}

	public function set($var, $val) {
		if ($var) {
			foreach ($this->_items as $item) {
				$item->{$var} = $val;
			};
		}
		return $this;
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
			$res = $func($key, $item);
			$this->_items[$key] = $item;
			if ($res == self::DONE) {
				break;
			}
		}
	}

	public function json($args = []) {
		return json_encode($this->jsonSerialize());
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

	public function __call($name, $arguments) {
		foreach ($this->_items as $key => $item) {
			if (is_callable($item, $name) || method_exists($item, $name)) {
				$items[] = (new \ReflectionMethod($item, $name))->invokeArgs($item, $arguments);
			} else {
				// not callable
			}
		}

		return self::o($items);
	}

	public function &__get($name) {
		/** looper should never need this, but this might break stuff
		if (property_exists($this,$name)) {
			return $this->{$name};
		} else {
			if (isset($name{0}) && $name{0} == '_') {
				return $this->_items[0]->{$name};
			} else {
				return $this->_items[0]->_properties[$name];
			}
		}
		*/
		if (isset($name{0}) && $name{0} == '_') {
			return $this->_items[0]->{$name};
		} else {
			return $this->_items[0]->_properties[$name];
		}
	}

	public function __set($name, $value) {
		/** looper should never need this, but this might break stuff
		if (property_exists($this,$name)) {
			$this->{$name} = $value;
		} else {
			foreach ($this->_items as $key => $item) {
				$this->_items[$key]->{$name} = $value;
			}
		}
		*/
		foreach ($this->_items as $key => $item) {
			$this->_items[$key]->{$name} = $value;
		}
		return $value;
	}

	/** these appear to never have been implimented correctly

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
	**/
}
