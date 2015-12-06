<?php

class ServicePHP7 extends Tipsy_Test {

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true;
	}

	public function testServiceAnonymousClass() {
		$_REQUEST['__url'] = 'router/library';
		$this->ob();

		$this->tip->service('Test', new class() extends \Tipsy\Service {
			public function test($args = null) {
				echo 'TEST';
			}
		});

		$this->tip->router()
			->when('router/library', function($Test) {
				$Test->test();
			});
		$this->tip->start();

		$check = $this->ob(false);
		$this->assertEquals('TEST', $check);
	}

	public function testMiddlewareAnonymousClass() {
		$this->ob();

		$this->tip->middleware(new class() extends \Tipsy\Middleware {
			public function run($args = null) {
				echo 'MIDDLEWARE';
			}
		});

		$this->tip->router()
			->otherwise(function() {

			});
		$this->tip->start();
		$check = $this->ob(false);
		$this->assertEquals('MIDDLEWARE', $check);
	}

	public function testServiceAnonymousClassFail() {
		try {
			$this->tip->service('Test', new class() {
			});
			$catch = false;
		} catch (Exception $e) {
			$catch = true;
		}
		$this->assertTrue ($catch);
	}

	public function testMiddlewareAnonymousClassFail() {
		try {
			$this->tip->middleware(new class() {
			});
			$catch = false;
		} catch (Exception $e) {
			$catch = true;
		}
		$this->assertTrue ($catch);
	}

}
