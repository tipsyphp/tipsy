<?php

class ClassStaticTest extends \Tipsy\Resource {
	public static $_id = 'id_test_user';
	public static $_table = 'test_user';

	function test() {
		return 'NO';
	}
}


class StaticyTest extends Tipsy_Test {

	public function setUp() {
		$this->useOb = true; // for debug use
	}
	
	public function testObject() {
		$this->ob();
		
		$tipsy = new Tipsy\Tipsy;

		$tipsy->router()
			->home(function() {
				echo 'object';
			});
		$tipsy->start();
		
		$check = $this->ob(false);
		$this->assertEquals('object', $check);
	}

	public function testStatic() {
		$this->ob();

		Tipsy\Tipsy::router()
			->home(function() {
				echo 'static';
			});
		Tipsy\Tipsy::start();
		
		$check = $this->ob(false);
		$this->assertEquals('static', $check);
	}
	
	public function testCombined() {
		$this->ob();
		
		$tipsy = new Tipsy\Tipsy;

		Tipsy\Tipsy::router()
			->home(function() {
				echo 'combined';
			});
		$tipsy->start();
		
		$check = $this->ob(false);
		$this->assertEquals('combined', $check);
	}
	
	public function testCombinedForwards() {
		$this->ob();

		Tipsy\Tipsy::router()
			->home(function() {
				echo 'combined';
			});
		Tipsy\Tipsy::app()->start();
		
		$check = $this->ob(false);
		$this->assertEquals('combined', $check);
	}
	/*
	@todo: do it
	public function testStaticResourceQuery() {
		$this->ob();

		$u = ClassStaticTest::q('select * from test_user');
		print_r($u);
		$this->assertEquals('combined', $check);
	}
	*/

}
