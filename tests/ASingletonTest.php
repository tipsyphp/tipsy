<?php

// this class has to be named A to start in order to create the singleton
class ASingletonTest extends Tipsy_Test {
	public function testSelfCreate() {
		Tipsy\Tipsy::config(['test' => 'me']);
		$this->assertEquals('me', Tipsy\Tipsy::config()['test']);
	}
}
