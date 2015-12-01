<?php

class HeaderEmptyTest extends Tipsy_Test {

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use
	}

	public function testHeaderEmpty() {
		$_REQUEST['__url'] = 'router/basic';
		// i dont know when this would happen, but it could perhaps
		$_SERVER = [];

		$res = null;
		$this->tip->router()
			->when('router/basic', function($Request) use (&$res) {
				$res = $Request->headers();
			});
		$this->tip->start();
		$this->assertEquals([], $res);
	}
}
