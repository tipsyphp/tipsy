<?php

class LoginServiceMiddleware extends \Tipsy\Middleware {
	function __construct() {
		echo 'SERVICECONSTRUCT';
	}
	function run($test = null) {
		echo 'SERVICERUN'.$test['test'];
	}
}

class MiddlewareClassFailure extends \Tipsy\Middleware {
	function run($test = null) {
		return false;
	}
}

class MiddlewareClassDefault extends \Tipsy\Middleware {

}

class MiddlewareTest extends Tipsy_Test {

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use
	}

	public function testMiddlewareClass() {
		$_REQUEST['__url'] = '';
		$this->ob();
		$this->tip->middleware('LoginServiceMiddleware', ['test' => 'HI']);
		$check = $this->ob(false);
		$this->assertEquals('SERVICECONSTRUCT', $check);

		$this->ob();
		$this->tip->middleware('LoginServiceMiddleware');
		$this->tip->router()->home(function() {});
		$this->tip->start();
		$check = $this->ob(false);
		$this->assertEquals('SERVICERUNHI', $check);

		$this->ob();
		$class = get_class($this->tip->middleware('LoginServiceMiddleware'));
		$check = $this->ob(false);
		$this->assertEquals('LoginServiceMiddleware', $class);
		$this->assertEquals('', $check);
	}

	public function testMiddlewareTipsy() {
		$_REQUEST['__url'] = '';
		$this->ob();

		$this->tip->service('Tipsy\Service/LoginServiceTipsy', [
			run => function() {
				echo 'HI';
			},
			test => function() {
				return 'HELLO';
			}
		]);

		$this->tip->middleware('LoginServiceTipsy');
		$check = $this->ob(false);
		$this->assertEquals('', $check);
		$this->ob();

		$this->tip->router()->home(function() {});
		$this->tip->start();
		$check = $this->ob(false);
		$this->assertEquals('HI', $check);
		$this->ob();

		$check = $this->tip->service('LoginServiceTipsy')->test();
		$this->ob(false);
		$this->assertEquals('HELLO', $check);
	}

	public function testMiddlewareTipsyDirect() {
		$_REQUEST['__url'] = '';
		$this->ob();

		$this->tip->middleware('Tipsy\Service/LoginServiceTipsy', [
			run => function() {
				echo 'HI';
			},
			test => function() {
				return 'HELLO';
			}
		]);


		$check = $this->ob(false);
		$this->assertEquals('', $check);
		$this->ob();

		$this->tip->router()->home(function() {});
		$this->tip->start();
		$check = $this->ob(false);
		$this->assertEquals('HI', $check);
		$this->ob();

		$check = $this->tip->service('LoginServiceTipsy')->test();
		$this->ob(false);
		$this->assertEquals('HELLO', $check);
	}

	public function testMiddlewareFailure() {
		$_REQUEST['__url'] = '';

		$this->tip->service('Tipsy\Service/MiddlewareException', [
			run => function() {
				return false;
			}
		]);

		$this->tip->middleware('MiddlewareException');
		$this->tip->router()->home(function() {});
		try {
			$this->tip->start();
			$check = false;
		} catch (Exception $e) {
			$check = true;
		}
		$this->assertTrue($check);
	}

	public function testMiddlewareClassFailure() {
		$this->tip->middleware('MiddlewareClassFailure');
		$this->tip->router()->otherwise(function() {});
		try {
			$this->tip->start();
			$check = false;
		} catch (Exception $e) {
			$check = true;
		}
		$this->assertTrue($check);
	}

	public function testMiddlewareDefault() {
		$_REQUEST['__url'] = '';

		$this->tip->service('Tipsy\Service/MiddlewareDefault', [
		]);

		$this->tip->middleware('MiddlewareDefault');
		$this->tip->router()->home(function() {});
		try {
			$this->tip->start();
			$check = false;
		} catch (Exception $e) {
			$check = true;
		}
		$this->assertFalse($check);
	}

	public function testMiddlewareClassDefault() {
		$this->tip->middleware('MiddlewareClassDefault');
		$this->tip->router()->otherwise(function() {});
		try {
			$this->tip->start();
			$check = false;
		} catch (Exception $e) {
			$check = true;
		}
		$this->assertFalse($check);
	}


	/*
	@todo: need to communicate with eachother
	public function testMiddlewareToMiddlewareReference() {
		$_REQUEST['__url'] = '';
		$this->ob();

		$this->tip->middleware('Tipsy\Service/FirstService', [
			test => function() {
				return 'HELLO';
			}
		]);

		$this->tip->middleware('Tipsy\Service/SecondService', function($FirstService) {
			return [
				test => function() {
					return $FirstService->test();
				}
			];
		});

		$this->tip->router()->home(function() {});
		$this->tip->start();

		$check = $this->tip->middleware('SecondService')->test();
		$this->assertEquals('HELLO', $check);
	}
	*/
}
