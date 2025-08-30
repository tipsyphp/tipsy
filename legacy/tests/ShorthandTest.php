<?php

use Tipsy\Tipsy;

class ShorthandTest extends Tipsy_Test {
	public function testShorty() {
		$_REQUEST['__url'] = 'test';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		Tipsy::get('test', function() use (&$res) {
			$res = true;
		});
		Tipsy::run();
		$this->assertTrue($res);
	}
}
