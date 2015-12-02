<?php



class Db extends \Tipsy\Db {
	public function query($query, $args = []) {
		if (!$query) {
			throw new Exception('Query is emtpy');
		}
		$query = 'select now()';
		return parent::query($query, $args);
	}
}


class ExtendBuiltInServiceTest extends Tipsy_Test {

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use

		$this->tip->config('tests/config.ini');
		$env = getenv('TRAVIS') ? 'travis' : 'local';
		$this->tip->config('tests/config.db.'.$env.'.ini');
	}

	public function testDbExtend() {

		$this->tip->service('Db');

		$this->tip->service('Tipsy\Resource/TestModel', [

		]);


		$_REQUEST['__url'] = 'test';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
		$_POST['data'] = 'blah';

		$this->tip->service('TestModel', []);

		$this->tip->router()
			->post('test',function($TestModel, $Request) {
				$TestModel->load(1);
				echo $TestModel->json();
			});

		$this->ob();
		$this->tip->run();
		$check = $this->ob(false);
		// should be empty because we are overwrite the query
		$this->assertEquals('{"":""}', $check);
	}

}
