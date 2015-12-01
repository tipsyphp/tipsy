<?php

class SingletonTest extends Tipsy_Test {
	public function testSelfCreate() {
		Tipsy\Tipsy::config(['test' => 'me']);
		$this->assertEquals('me', Tipsy\Tipsy::config()['test']);
	}
}
