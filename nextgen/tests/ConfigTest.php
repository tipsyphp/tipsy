<?php

class ConfigTest extends Tipsy_Test {
	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use
	}

	public function testConfigInternal() {
		$this->tip->config([
			'test' => [
				'beef' => 'cake'
			]
		], true);
		$this->assertEquals('cake', $this->tip->config()['test']['beef']);
	}
	
	public function testConfigFile() {
		$this->tip->config('tests/config.ini');
		$this->assertEquals('cake', $this->tip->config()['test']['beef']);
	}
	
	public function testConfigFileDir() {
		$this->tip->config('tests/conf/*.ini');
		$this->assertEquals('VERY', $this->tip->config()['tipsy']['howtipsy']);
	}
}
