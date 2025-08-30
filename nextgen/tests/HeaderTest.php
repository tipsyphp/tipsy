<?php

class HeaderTest extends Tipsy_Test {

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use
	}

	public function testHeaderSet() {
		$_REQUEST['__url'] = 'router/basic';
		$_SERVER['HTTP_TEST'] = 'win';

		$this->ob();

		$this->tip->router()
			->when('router/basic', function($Request) {
				echo $Request->headers()['Test'];
			});
		$this->tip->start();

		$check = $this->ob(false);
		$this->assertEquals('win', $check);
	}
}
