<?php

namespace Tipsy {
	class TestModelBase {
		public function test() {
			echo 'ONE';
		}
	}
}

namespace {
	
	
	class ModelTest extends Tipsy_Test {
	
		public static function setUpBeforeClass() {
	
	
	
		}
	
		public static function tearDownAfterClass() {
	
		}
		
		public function setUp() {
			$this->tip = new Tipsy\Tipsy;
			$this->useOb = true; // for debug use
			
			$this->tip->config('tests/config.ini');
			
	
			
	
	
		}
	
		public function testModelBasic() {
	
			$this->tip->model('TestModel', function() {
				$model = [
					'test' => function() {
						return 'YES';
					}
				];
				return $model;
			});
			
			$model = $this->tip->model('TestModel');
			$this->assertEquals('YES', $model->test());

		}

		public function testModelBasicExtend() {
	
			$this->tip->model('TestModelBase/TestModel', function() {
				$model = [
					'othertest' => function() {
						echo 'TWO';
					}
				];
				return $model;
			});
			
			$model = $this->tip->model('TestModel');
			print_r($model);
			die('asd');

			$this->tip->router()
				->otherwise(function($db, $TestModel) {
				});
	
			
	
	
			
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
}