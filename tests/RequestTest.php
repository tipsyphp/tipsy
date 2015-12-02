<?php

class RequestTest extends Tipsy_Test {

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
	}

	public function testLoc() {
		$_REQUEST['__url'] = 'request/basic';
		$this->tip->router()
			->otherwise(function($Request) use (&$res) {
				$res[0] = $Request->loc(0);
				$res[1] = $Request->loc(1);
			});
		$this->tip->start();
		$this->assertEquals('request', $res[0]);
		$this->assertEquals('basic', $res[1]);
	}

	public function testUrl() {
		$_SERVER['HTTP_HOST'] = 'tipsy.com';
		$_REQUEST['__url'] = 'request/basic';
		$this->tip->router()
			->otherwise(function($Request) use (&$res) {
				$res = $Request->url();
			});
		$this->tip->start();
		$this->assertEquals('http://tipsy.com/request/basic', $res);
	}

	public function testProperties() {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_REQUEST['__url'] = 'request/basic';
		$_GET['test'] = 'win';

		$this->tip->router()
			->otherwise(function($Request) use (&$res) {
				$res = $Request->test;
			});
		$this->tip->start();
		$this->assertEquals('win', $res);
	}

	public function testBase() {
		$_SERVER['HTTP_HOST'] = 'tipsy.com';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = 'request/basic';
		$_SERVER['SCRIPT_NAME'] = 'RouterTest.php';

		$this->tip->router()
			->otherwise(function($Request) use (&$res) {
				$res = $Request->base();
			});
		$this->tip->start();
		$this->assertEquals('./', $res);
	}
}
