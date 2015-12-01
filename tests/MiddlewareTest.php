<?php

class LoginServiceMiddleware extends \Tipsy\Middleware {
	function __construct() {
		echo 'SERVICECONSTRUCT';
	}
	function run($test) {
		echo 'SERVICERUN'.$test['test'];
	}
}

class MiddleWareTestFail {
	function run() {
		return false;
	}
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

		$this->tip->router()->home(function() {});
		$this->tip->start();
		$check = $this->ob(false);
		$this->assertEquals('HI', $check);

		$check = $this->tip->service('LoginServiceTipsy')->test();
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

		$this->tip->router()->home(function() {});
		$this->tip->start();
		$check = $this->ob(false);
		$this->assertEquals('HI', $check);

		$check = $this->tip->service('LoginServiceTipsy')->test();
		$this->assertEquals('HELLO', $check);
	}

	public function testMiddlewareFailure() {
		$_REQUEST['__url'] = '';
		$this->ob();

		$this->tip->service('Tipsy\Service/LoginServiceException', [
			run => function() {
				return false;
			}
		]);

		$this->tip->middleware('LoginServiceException');
		$this->tip->router()->home(function() {});
		try {
			$this->tip->start();
			$check = false;
		} catch (Exception $e) {
			$check = true;
		}
		$this->assertTrue($check);
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
