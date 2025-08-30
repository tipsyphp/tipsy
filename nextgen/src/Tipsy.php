<?php

declare(strict_types=1);

/**
 * Tipsy
 * An MVW PHP Framework
 *
 * Modernized for PHP 8.3+
 */

namespace Tipsy;

// Modern PHP 8.3+ - no legacy compatibility needed

/**
 * Wrapper class
 */
class Tipsy {

	private static ?App $_app = null;

	public function __construct(?array $params = null) {
		self::init($params);
	}

	public static function init(?array $params = null): void {
		self::app(new App($params));
	}

	public static function app(?App $app = null): App {
		if ($app !== null) {
			self::$_app = $app;
		} elseif (self::$_app === null) {
			self::init();
		}
		return self::$_app;
	}

	public function __call(string $name, array $arguments): mixed {
		return self::app()->{$name}(...$arguments);
	}

	public static function __callStatic(string $name, array $arguments): mixed {
		return self::app()->{$name}(...$arguments);
	}
}

class_alias('\Tipsy\Tipsy', 't');
