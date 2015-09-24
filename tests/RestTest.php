<?php


class RestTest extends Tipsy_Test {
	
	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use
		
		$this->tip->config('tests/config.ini');
	}
	
	public function testPost() {
		$_REQUEST['__url'] = 'test';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['data'] = 'blah';
		
		$this->tip->service('TestModel', []);

		$this->tip->router()
			->post('test',function($TestModel, $Request) {
				$TestModel->test = 'hi';
				$TestModel->data = $Request->data;
				echo $TestModel->json();
			});
		
		$this->ob();
		$this->tip->start();
		$check = $this->ob(false);
		$this->assertEquals(json_encode(['test' => 'hi', 'data' => 'blah']), $check);
	}
	
	public function testGet() {
		$_REQUEST['__url'] = 'test';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_GET['data'] = 'blah';
		
		$this->tip->service('TestModel', []);

		$this->tip->router()
			->get('test',function($TestModel, $Request) {
				$TestModel->test = 'hi';
				$TestModel->data = $Request->data;
				echo $TestModel->json();
			});
		
		$this->ob();
		$this->tip->start();
		$check = $this->ob(false);
		$this->assertEquals(json_encode(['test' => 'hi', 'data' => 'blah']), $check);
	}
	
	public function testDelete() {
		$_REQUEST['__url'] = 'test/1';
		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		
		$this->tip->service('TestModel', []);

		$this->tip->router()
			->delete('test/:id',function($Params, $TestModel) {
				$TestModel->test = 'hi';
				$TestModel->id = $Params->id;
				echo $TestModel->json();
			});
		
		$this->ob();
		$this->tip->start();
		$check = $this->ob(false);
		$this->assertEquals(json_encode(['test' => 'hi', 'id' => '1']), $check);
	}
}
