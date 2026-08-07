<?php

declare(strict_types=1);

namespace Tipsy;

class Looper implements \Iterator, \JsonSerializable, \Countable {
	private array $_items;
	private int $_position = 0;
	private ?self $_parent = null;

	public const string DONE = 'looper\break';

	public function jsonSerialize(): array {
		$items = [];
		foreach ($this->_items as $key => $item) {
			if (is_object($item) && method_exists($item, 'exports')) {
				$items[$key] = $item->exports();
			} else {
				$items[$key] = $item;
			}
		}
		return $items;
	}

	public function __construct(mixed ...$args) {
		$items = [];
		$args = array_reverse($args);

		foreach ($args as $arg) {
			if ($arg instanceof self) {
				$arg = $arg->items();
			} elseif (is_object($arg)) {
				$arg = [$arg];
			}
			$items = array_merge((array)$arg, $items);
		}

		$this->_items = $items;
		$this->_position = 0;
	}

	public static function o(mixed ...$args): static {
		return new static(...$args);
	}

	public function items(): array {
		return $this->_items;
	}

	public function get(int $index): mixed {
		return $this->eq($index);
	}

	public function set(string $var, mixed $val): static {
		if ($var) {
			foreach ($this->_items as $item) {
				$item->{$var} = $val;
			}
		}
		return $this;
	}

	public function eq(int $pos): mixed {
		$pos = $pos < 0 ? count($this->_items) - abs($pos) : $pos;
		return $this->_items[$pos] ?? null;
	}

	public function remove(int $start): static {
		unset($this->_items[$start]);
		return $this;
	}

	public function slice(int $start, ?int $end = null): static {
		$items = array_slice($this->_items, $start, $end);
		return $this->_returnItems($items);
	}

	public function not(mixed ...$args): static {
		$items = $this->_filter(...$args);
		return $this->_returnItems($items['no'] ?? []);
	}

	public function filter(mixed ...$args): static {
		$items = $this->_filter(...$args);
		return $this->_returnItems($items['yes'] ?? []);
	}

	public function each(callable $func, array $params = []): void {
		foreach ($this->_items as $key => $item) {
			if ($func instanceof \Closure) {
				$func = $func->bindTo(!is_object($item) ? (object)$item : $item);
			}
			$res = $func($key, $item);
			$this->_items[$key] = $item;
			if ($res === self::DONE) {
				break;
			}
		}
	}

	public function json(array $args = []): string {
		return json_encode($this->jsonSerialize()) ?: '[]';
	}

	public function e(callable $f): void {
		$this->each($f);
	}

	public function rewind(): void {
		$this->_position = 0;
	}

	public function current(): mixed {
		return $this->_items[$this->_position];
	}

	public function key(): int {
		return $this->_position;
	}

	public function next(): void {
		++$this->_position;
	}

	public function valid(): bool {
		return isset($this->_items[$this->_position]);
	}

	public function count(): int {
		return count($this->_items);
	}

	public function parent(): ?self {
		return $this->_parent;
	}

	private function _filter(mixed ...$filterArgs): array {
		$items = $this->_items;
		$mismatch = [];
		$strict = false;
		$func = null;
		$filters = [];

		if (count($filterArgs) === 1 && is_callable($filterArgs[0])) {
			$func = $filterArgs[0];

		} elseif (count($filterArgs) === 2 && !is_array($filterArgs[0]) && !is_array($filterArgs[1])) {
			$filters[][$filterArgs[0]] = $filterArgs[1];

		} else {
			foreach ($filterArgs as $arg) {
				if (is_array($arg)) {
					$filters[] = $arg;
				}
			}
		}

		if ($filters) {
			foreach ($filters as $set) {
				foreach ($items as $key => $item) {
					$mis = 0;
					foreach ($set as $k => $v) {
						if ($item->{$k} != $v) {
							$mis++;
						}
					}
					if (($strict && count($set) === $mis) || $mis) {
						$mismatch[$key] = ($mismatch[$key] ?? 0) + 1;
					}
				}
			}
		}

		if ($func) {
			foreach ($items as $key => $item) {
				if (!$func($item, $key)) {
					$mismatch[$key] = $key;
					break;
				}
			}
		}

		$newitems = [];
		$trash = [];

		foreach ($items as $key => $value) {
			if (array_key_exists($key, $mismatch) && ($func || ($mismatch[$key] ?? 0) === count($filters))) {
				$trash[] = $items[$key];
			} else {
				$newitems[] = $items[$key];
			}
		}

		return ['yes' => $newitems, 'no' => $trash];
	}

	private function _returnItems(array $items): static {
		if (count($items) !== count($this->_items)) {
			$return = new static($items);
			$return->_parent = $this;
		} else {
			$return = $this;
		}
		return $return;
	}

	public function __toString(): string {
		$print = '';
		foreach ($this->_items as $item) {
			if (is_object($item) && method_exists($item, '__toString')) {
				$print .= $item->__toString();
			} elseif (is_string($item) || is_int($item)) {
				$print .= (string)$item;
			}
		}
		return $print;
	}

	public function __call(string $name, array $arguments): static {
		$items = [];
		foreach ($this->_items as $item) {
			if (is_callable([$item, $name]) || method_exists($item, $name)) {
				$items[] = $item->$name(...$arguments);
			}
		}

		return static::o($items);
	}

	public function &__get(string $name): mixed {
		if (isset($name[0]) && $name[0] === '_') {
			return $this->_items[0]->{$name};
		} else {
			return $this->_items[0]->_properties[$name];
		}
	}

	public function __set(string $name, mixed $value): void {
		foreach ($this->_items as $key => $item) {
			$this->_items[$key]->{$name} = $value;
		}
	}
}
