<?php

class HttpTest extends Tipsy_Test {
	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true;
	}

	public function testGetJson() {
		$http = (new Tipsy\Http())->get('https://maps.googleapis.com/maps/api/geocode/json?address=1600+Amphitheatre+Parkway,+Mountain+View,+CA')->complete(function($a) use (&$res) {
			$res = $a->status == 'OK';
		});
		$this->assertEquals(true, $res);
	}
}
