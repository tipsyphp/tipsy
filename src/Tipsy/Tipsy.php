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












class RouteParams extends Scope {

}


class View_Filter {

}


class StripWhite extends View_Filter {
	public static function filter($content) {
		$find = [
			'/^(\s?)(.*?)(\s?)$/',
			'/\t|\n|\r/',
			'/(\<\!\-\-)(.*?)\-\-\>/'
		];
		$replace = [
			'\\2',
			'',
			''
		];
		return preg_replace($find, $replace, $content);
	}
}


