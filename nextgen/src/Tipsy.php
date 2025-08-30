<?php

declare(strict_types=1);

/**
 * Tipsy
 * An MVW PHP Framework
 *
 * A little bit of a mess. Still a work in progress.
 */


namespace Tipsy;

// supress datetime warnings and set to UTC if not set
@date_default_timezone_get();
ini_set('always_populate_raw_post_data', -1);

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

	public static function __callStatic(string $name, array $arguments): mixed {
		return call_user_func_array([self::app(), $name], $arguments);
	}

	public function __call(string $name, array $arguments): mixed {
		return call_user_func_array([self::app(), $name], $arguments);
	}
}

class_alias('\Tipsy\Tipsy', 't');


// useful for nginx
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
