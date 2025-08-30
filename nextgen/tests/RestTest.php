<?php


class RestTest extends Tipsy_Test {

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use

		$this->tip->config('tests/config.ini');
		$this->setupDb($this->tip);
	}

	public function testMultipartFormPost() {
		$_REQUEST['__url'] = 'test';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['CONTENT_TYPE'] = 'multipart/form-data; separater blah blah';
		$_REQUEST['data'] = 'blah';

		$this->tip->service('TestModel', []);

		$this->tip->router()
			->post('test',function($TestModel, $Request) {
				$TestModel->test = 'hi';
				$TestModel->data = $Request->data;
				echo $TestModel->json();
			});

		$this->ob();
		$this->tip->run();
		$check = $this->ob(false);
		$this->assertEquals(json_encode(['test' => 'hi', 'data' => 'blah']), $check);
	}


	public function testFormPost() {
		$_REQUEST['__url'] = 'test';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
		$_POST['data'] = 'blah';

		$this->tip->service('TestModel', []);

		$this->tip->router()
			->post('test',function($TestModel, $Request) {
				$TestModel->test = 'hi';
				$TestModel->data = $Request->data;
				echo $TestModel->json();
			});

		$this->ob();
		$this->tip->run();
		$check = $this->ob(false);
		$this->assertEquals(json_encode(['test' => 'hi', 'data' => 'blah']), $check);
	}

	public function testFormGet() {
		$_REQUEST['__url'] = 'test';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
		$_GET['data'] = 'blah';

		$this->tip->service('TestModel', []);

		$this->tip->router()
			->get('test',function($TestModel, $Request) {
				$TestModel->test = 'hi';
				$TestModel->data = $Request->data;
				echo $TestModel->json();
			});

		$this->ob();
		$this->tip->run();
		$check = $this->ob(false);
		$this->assertEquals(json_encode(['test' => 'hi', 'data' => 'blah']), $check);
	}

	public function testFormDelete() {
		$_REQUEST['__url'] = 'test/1';
		$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$_ENV['TESTS_PHP_INPUT'] = '/tmp/phpinput';
		file_put_contents($_ENV['TESTS_PHP_INPUT'], '');

		$this->tip->service('TestModel', []);

		$this->tip->router()
			->delete('test/:id',function($Params, $TestModel) {
				$TestModel->test = 'hi';
				$TestModel->id = $Params->id;
				echo $TestModel->json();
			});

		$this->ob();
		$this->tip->run();
		$check = $this->ob(false);
		$this->assertEquals(json_encode(['test' => 'hi', 'id' => '1']), $check);
	}

	public function testFormGeneric() {
		$_REQUEST['__url'] = 'test';
		$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
		$_SERVER['REQUEST_METHOD'] = 'GENERIC';
		$_ENV['TESTS_PHP_INPUT'] = '/tmp/phpinput';
		file_put_contents($_ENV['TESTS_PHP_INPUT'], 'data=blah&something=else');

		$this->tip->service('TestModel', []);

		$this->tip->router()
			->generic('test',function($TestModel, $Request) {
				$TestModel->test = 'hi';
				$TestModel->data = $Request->data;
				echo $TestModel->json();
			});

		$this->ob();
		$this->tip->run();
		$check = $this->ob(false);
		$this->assertEquals(json_encode(['test' => 'hi', 'data' => 'blah']), $check);
	}

	public function testJsonPost() {
		$_REQUEST['__url'] = 'test';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['CONTENT_TYPE'] = 'application/json';
		$_ENV['TESTS_PHP_INPUT'] = '/tmp/phpinput';
		file_put_contents($_ENV['TESTS_PHP_INPUT'], '{"data": "blah"}');

		$this->tip->service('TestModel', []);

		$this->tip->router()
			->post('test',function($TestModel, $Request) {
				$TestModel->test = 'hi';
				$TestModel->data = $Request->data;
				echo $TestModel->json();
			});

		$this->ob();
		$this->tip->run();
		$check = $this->ob(false);
		$this->assertEquals(json_encode(['test' => 'hi', 'data' => 'blah']), $check);
	}

	public function testJsonGet() {
		$_REQUEST['__url'] = 'test';
		$_SERVER['REQUEST_URI'] = '/?{"data": "blah"}';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['CONTENT_TYPE'] = 'application/json';

		$this->tip->service('TestModel', []);

		$this->tip->router()
			->get('test',function($TestModel, $Request) {
				$TestModel->test = 'hi';
				$TestModel->data = $Request->data;
				echo $TestModel->json();
			});

		$this->ob();
		$this->tip->run();
		$check = $this->ob(false);
		$this->assertEquals(json_encode(['test' => 'hi', 'data' => 'blah']), $check);
	}

	public function testJsonDelete() {
		$_REQUEST['__url'] = 'test/1';
		$_SERVER['CONTENT_TYPE'] = 'application/json';
		$_SERVER['REQUEST_METHOD'] = 'DELETE';

		$this->tip->service('TestModel', []);

		$this->tip->router()
			->delete('test/:id',function($Params, $TestModel) {
				$TestModel->test = 'hi';
				$TestModel->id = $Params->id;
				echo $TestModel->json();
			});

		$this->ob();
		$this->tip->run();
		$check = $this->ob(false);
		$this->assertEquals(json_encode(['test' => 'hi', 'id' => '1']), $check);
	}

	public function testJsonGeneric() {
		$_REQUEST['__url'] = 'test';
		$_SERVER['CONTENT_TYPE'] = 'application/json';
		$_SERVER['REQUEST_METHOD'] = 'GENERIC';
		$_ENV['TESTS_PHP_INPUT'] = '/tmp/phpinput';
		file_put_contents($_ENV['TESTS_PHP_INPUT'], '{"data": "blah"}');

		$this->tip->service('TestModel', []);

		$this->tip->router()
			->generic('test',function($TestModel, $Request) {
				$TestModel->test = 'hi';
				$TestModel->data = $Request->data;
				echo $TestModel->json();
			});

		$this->ob();
		$this->tip->run();
		$check = $this->ob(false);
		$this->assertEquals(json_encode(['test' => 'hi', 'data' => 'blah']), $check);
	}

	public function testPostSave() {
		$_REQUEST['__url'] = 'drink/1';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
		$_POST['name'] = 'maitai';

		$this->tip->service('Tipsy\Resource/Drink', [
			_id => 'id',
			_table => 'test_user'
		]);

		$this->tip->post('drink/:id',function($Drink, $Request, $Params) {
			$Drink
				->load($Params->id)
				->serialize($Request->request())
				->save();
        		echo $Drink->json();
		});

		$this->ob();
		$this->tip->run();
		$check = $this->ob(false);
		$this->assertEquals(json_encode(['id' => 1, 'name' => 'maitai', 'username' => null, 'active' => false]), $check);
	}
}
