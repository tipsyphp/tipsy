<?php

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

	private static $_app;

	public function __construct($params = null) {
		self::init();
	}

	public static function init($params = null) {
		self::app(new App($params));
	}

	public static function app($app = null) {
		if (!is_null($app)) {
			self::$_app = $app;
		} elseif (is_null(self::$_app)) {
			self::init();
		}
		return self::$_app;
	}

	public static function __callStatic($name, $arguments) {
		//return (new \ReflectionMethod(self::app(), $name))->invokeArgs(self::app(), $arguments);
		return call_user_func_array([self::app(), $name], $arguments);
	}

	public function __call($name, $arguments) {
		//return (new \ReflectionMethod(self::app(), $name))->invokeArgs(self::app(), $arguments);
		return call_user_func_array([self::app(), $name], $arguments);
	}
}

class_alias('\Tipsy\Tipsy', 't');


// useful for nginx
if (!function_exists('getallheaders')) {
	function getallheaders() {
		if (!is_array($_SERVER)) {
			return [];
		}

		$headers = [];
		foreach ($_SERVER as $name => $value) {
			if (substr($name, 0, 5) == 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		return $headers;
	}
}
