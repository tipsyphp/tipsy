<?php

/**
 * Tipsy
 * An MVW PHP Framework
 *
 * A little bit of a mess. Still a work in progress.
 */


namespace Tipsy;

set_error_handler(function ($errno, $errstr){
	throw new Exception($errstr);
	return false;
});
try {
	date_default_timezone_get();
}
catch(Exception $e) {
	date_default_timezone_set('UTC');
}
restore_error_handler();


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
		}
		return self::$_app;
	}

	public static function __callStatic($name, $arguments) {
		return (new \ReflectionMethod(self::app(), $name))->invokeArgs(self::app(), $arguments);
	}

	public function __call($name, $arguments) {
		return (new \ReflectionMethod(self::app(), $name))->invokeArgs(self::app(), $arguments);
	}
}


// useful for nginx
if (!function_exists('getallheaders'))  {
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
