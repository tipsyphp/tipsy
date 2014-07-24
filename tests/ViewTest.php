<?php


class ViewTest extends Tipsy_Test {

	public static function setUpBeforeClass() {
		// create a config file


	}

	public static function tearDownAfterClass() {
		// delete a config file
	}
	
	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use


	}
	
	public function testViewNoLayout() {
		$_REQUEST['__url'] = 'router/view';

		$this->tip->config(['view' => [
			'path' => 'tests'
		]]);

		$this->ob();
		
		$this->tip->router()
			->when('router/view', function($View, $Scope) {
				$Scope->test = 'ONE';
				$View->display('ScopeTest');
			});
		$this->tip->start();
		
		$res = $this->ob(false);
		
		$this->assertEquals('ONE', $res);
	}
	
	public function testViewLayout() {
		$_REQUEST['__url'] = 'router/view';
		
		$this->tip->config([
			'view' => [
				'layout' => 'LayoutTest',
				'path' => 'tests'
			]
		]);

		
		$this->ob();
		
		$this->tip->router()
			->when('router/view', function($View, $Scope) {
				$Scope->test = 'ONE';
				$View->display('ScopeTest');
			});
		$this->tip->start();
		
		$res = $this->ob(false);
		
		$this->assertEquals('HEADERONEFOOTER', $res);
	}
	
	public function testViewScope() {
		$_REQUEST['__url'] = 'router/view';
		
		$this->tip->config(['view' => [
			'path' => 'tests'
		]]);
		
		$this->ob();
		
		$this->tip->router()
			->when('router/view', function($View, $Scope) {
				$Scope->test = 'ONE';
				$View->render('ScopeTest');
				echo $Scope->test;
			});
		$this->tip->start();
		
		$res = $this->ob(false);
		
		$this->assertEquals('TWO', $res);
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
/*

$this->tip->router()

	->when('file/:id', function($db, $FileModel) {

//		$res = $db->fetch('select * from upload');
//		foreach ($res as $r) {
//			print_r($r);
//		}

	
		// get a new instance of the filemodel by id
	
		
		$test = $FileModel->create([
			'uid' => 'bacon'	
		]);
		$test = $FileModel->get(1);
		
		echo $test->uid;
		$test->uid = rand(1,2345454);
		$test->save();
		echo $test->uid;
		
		$FileModel->q('select * from upload where uid=?','bacon')->delete();
	
	exit;
		$File->fetch(1);
		$this->model('File')->fetch(1);
		$this->model('File')->query('select * from file where id=1');
		$file = File::o($this->route()->param('id'));
		print_r($file);
	})
	->when('view', [
		'controller' => 'ViewController',
		'view' => 'test.phtml'
	])
	->when('instance', [
		'controller' => $test
	])

$this->tip->start();

*/