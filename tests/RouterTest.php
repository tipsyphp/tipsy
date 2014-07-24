<?php

// class for library controller test
class LibraryController extends Tipsy\Controller {
	public function init() {
		echo 'LIBRARY';
	}
}

// class for instance controller test
class InstanceController extends Tipsy\Controller {
	public function init() {
		echo 'INSTANCE';
	}
}
		
		

class RouterTest extends Tipsy_Test {

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use
	}

	public function testRouterBasic() {
		$_REQUEST['__url'] = 'router/basic';
		
		$this->ob();

		$this->tip->router()
			->when('router/basic', function() {
				echo 'BASIC';
			});
		$this->tip->start();
		
		$check = $this->ob(false);
		$this->assertEquals('BASIC', $check);
	}
	
	public function testRouterId() {
		$_REQUEST['__url'] = 'router/file/BACON';
		
		$this->ob();

		$this->tip->router()
			->when('router/file/:id', function($params) {
				echo $params['id'];
			});
		$this->tip->start();
		
		$check = $this->ob(false);
		$this->assertEquals('BACON', $check);
	}
	
	public function testRouterIdSub() {
		$_REQUEST['__url'] = 'router/file/BACON/eat';
		
		$this->ob();

		$this->tip->router()
			->when('router/file/:id/eat', function($params) {
				echo 'SUB';
			});
		$this->tip->start();
		
		$check = $this->ob(false);
		$this->assertEquals('SUB', $check);
	}
	
	public function testRouterLibraryController() {
		$_REQUEST['__url'] = 'router/library';

		$this->ob();

		$this->tip->router()
			->when('router/library', [
				'controller' => 'LibraryController'
			]);
		$this->tip->start();
		
		$check = $this->ob(false);
		$this->assertEquals('LIBRARY', $check);
	}
	
	public function testRouterInternalController() {
		$_REQUEST['__url'] = 'router/internal';
		
		$this->tip->controller('InternalController', function() {
			echo 'INTERNAL';
		});

		$this->ob();

		$this->tip->router()
			->when('router/internal', [
				'controller' => 'InternalController'
			]);
		$this->tip->start();
		
		$check = $this->ob(false);
		$this->assertEquals('INTERNAL', $check);
	}

	public function testRouterInstanceController() {
		$_REQUEST['__url'] = 'router/instance';
		$test = new InstanceController;

		$this->ob();

		$this->tip->router()
			->when('router/instance', [
				'controller' => $test
			]);
		$this->tip->start();
		
		$check = $this->ob(false);
		$this->assertEquals('INSTANCE', $check);
	}

	
	public function testRouterError() {
		$_REQUEST['__url'] = 'router/errorme';

		$this->ob();

		$this->tip->router()
			->otherwise(function() {
				echo '404';
			});
		$this->tip->start();
		
		$check = $this->ob(false);
		$this->assertEquals('404', $check);
	}

	/*
	public function testRouterViewController() {
		$_REQUEST['__url'] = 'router/view';

		$this->tip->controller('ViewController', function() {
			$this->scope->test = 'YES';
		});
		
		ob_start();

		$this->tip->router()
			->when('router/views', function() {
				echo 'YES';
			})
			->when('router/view', [
				'controller' => 'ViewController'
			]);
		$this->tip->start();
		
		$check = ob_get_contents();
		ob_end_clean();
		
		$this->assertTrue($check == 'YES');
	}
	*/




}
