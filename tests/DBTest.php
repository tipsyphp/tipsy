<?php


class DBTest extends Tipsy_Test {

	public static function setUpBeforeClass() {



	}

	public static function tearDownAfterClass() {

	}
	
	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use
		
		$this->tip->config('tests/config.ini');
		
		
		/*

		
		$this->tip->model('DBO/TestModel', function() {
			$model = [
				'testmodel' => function() {
					die('testing');
				}
			];
			return $model;
		});
		
		$this->tip->model('DBO/FileModel', function() {
			$model = [
				'filemodel' => function() {
					die('testing');
				},

//				'construct' => function() {
//					$this->_id_var = 'id';
//					$this->_table = 'file';
//				},

				'id' => 'id',
				'table' => 'upload'
			];

		
			return $model;
		});
		*/
		
		


	}

	public function testRawr() {
		$this->assertTrue(true);
	}
/*
	public function testModelDBOExtend() {
		$this->tip->model('Tipsy\DBO/TestModel', function() {
			$model = [
				'test' => function() {
					return 'DBOTEST';
				}
			];
			return $model;
		});
		$model = $this->tip->model('TestModel');
		$this->assertEquals('DBOTEST', $model->test());
	}

	public function testModelDBOExtendCall() {	
		$this->tip->model('Tipsy\DBO/TestModel', function() {
			$model = [

			];
			return $model;
		});
		$model = $this->tip->model('TestModel');
		$model->test = 'YES';
		$this->assertEquals('YES', $model->property('test'));
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


$this->tip->start();

*/