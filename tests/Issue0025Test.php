<?php

// this tests the behavior of static and object extending which behaves wierd


class UserOne extends \Tipsy\Resource {
	public function create($params = []) {
		return $this->table();
	}
	public function className() {
		return get_called_class();
	}
	public function __construct($id = null) {
		$this->idVar('id')->table('test_user')->load($id);
	}
}


class UserTwo extends \Tipsy\Resource {
	public function create() {
		return UserOne::create();
	}
	public function className() {
		return UserOne::className();
	}
	public function __construct($id = null) {
		$this->idVar('id')->table('test_user2')->load($id);
	}
}


class Issue0025Test extends Tipsy_Test {

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;

		$this->tip->config('tests/config.ini');
		$env = getenv('TRAVIS') ? 'travis' : 'local';
		$this->tip->config('tests/config.db.'.$env.'.ini');

		$this->tip->service('\UserOne');
		$this->tip->service('\UserTwo');
		$this->useOb = true; // for debug use
	}


	public function testTable() {
		$table = $this->tip->service('UserOne')->create();

		$this->assertEquals('test_user', $table);
	}

	public function testException() {
		if (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION < 6) {
			$this->markTestSkipped('Prior to PHP 5.6 this did not throw an exception');
			return;
		}

		$catch = false;

		try {
			$this->tip->service('UserTwo')->user();
		} catch (Exception $e) {
			$catch = true;
		}

		$this->assertTrue($catch);
	}

	public function testTableName() {
		if ((PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION >= 6) || PHP_MAJOR_VERSION > 5) {
			$this->markTestSkipped('After PHP 5.6 this throws an exception');
			return;
		}

		$table = $this->tip->service('UserTwo')->create();
		$class = $this->tip->service('UserTwo')->className();

		// note that this isnt really what is intended to happen likely for the user, but this is just how it works :/
		$this->assertEquals('test_user2', $table);
		$this->assertEquals('UserTwo', $class);
	}

}
