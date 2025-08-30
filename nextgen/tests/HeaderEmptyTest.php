<?php

class HeaderEmptyTest extends Tipsy_Test {
	public function testHeaderEmpty() {
		$_REQUEST['__url'] = 'router/basic';
		// i dont know when this would happen, but it could perhaps
		$_SERVER = null;

		$this->tip = new Tipsy\Tipsy;

		$res = null;
		$this->tip->router()
			->when('router/basic', function($Request) use (&$res) {
				$res = $Request->headers();
			});
		$this->tip->start();
		$this->assertEquals([], $res);
	}
}
