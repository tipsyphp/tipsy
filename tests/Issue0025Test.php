<?php

// this tests the behavior of static and object extending which behaves wierd


class UserOne extends \Tipsy\Resource {
	public function create($params = []) {
		return $this->table();
	}

	public function __construct($id = null) {
		$this->idVar('id')->table('test_user')->load($id);
	}
}


class UserTwo extends \Tipsy\Resource {
	public function user() {
		$this->_user = UserOne::create([]);
		return $this->_user;
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
		$catch = false;

		try {
			$this->tip->service('UserTwo')->user();
		} catch (Exception $e) {
			$catch = true;
		}

		$this->assertTrue($catch);
	}




}
