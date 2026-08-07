<?php

declare(strict_types=1);

/**
 * Tipsy
 * An MVW PHP Framework
 */

namespace Tipsy;

/**
 * Wrapper class
 */
class Tipsy {

	private static ?App $_app = null;

	public function __construct() {
		static::init();
	}

	public static function init(): void {
		static::app(new App());
	}

	public static function app(?App $app = null): App {
		if ($app !== null) {
			static::$_app = $app;
		} elseif (static::$_app === null) {
			static::init();
		}
		return static::$_app;
	}

	public static function __callStatic(string $name, array $arguments): mixed {
		return static::app()->$name(...$arguments);
	}

	public function __call(string $name, array $arguments): mixed {
		return static::app()->$name(...$arguments);
	}
}

class_alias(Tipsy::class, 't');


// Polyfill for nginx — kept for server compatibility
if (!function_exists('getallheaders')) {
	function getallheaders(): array {
		if (!is_array($_SERVER)) {
			return [];
		}

		$headers = [];
		foreach ($_SERVER as $name => $value) {
			if (str_starts_with($name, 'HTTP_')) {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		return $headers;
	}
}
