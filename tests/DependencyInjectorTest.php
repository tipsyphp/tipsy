<?php

class DependencyInjectorTest extends Tipsy_Test {

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use

		$this->tip->config('tests/config.ini');
		$this->setupDb($this->tip);
	}

	public function testBaseInjectors() {
		$_REQUEST['__url'] = 'router/basic';
		$this->ob();

		$this->tip->router()
			->when('router/basic', function($Db, $Route, $Headers, $Params, $Tipsy, $View, $Scope, $Request, $RootScope) {
				foreach (func_get_args() as $k => $arg) {
					if ((!$arg && $k != 2) || ($k == 2 && !is_array($arg))) {
						$fail = true;
						echo 'no argument #'.$k."\n";
					}
				}
				if (!$fail) {
					echo 'YAY';
				}
			});
		$this->tip->start();

		$check = $this->ob(false);
		$this->assertEquals('YAY', $check);
	}

	public function testEmptyInjector() {
		$_REQUEST['__url'] = 'router/basic';
		$this->ob();

		$this->tip->router()
			->when('router/basic', function($Empty) {
				if ($Empty) {
					echo 'FAIL';
				} else {
					echo 'YAY';
				}
			});
		$this->tip->start();

		$check = $this->ob(false);
		$this->assertEquals('YAY', $check);
	}
}
