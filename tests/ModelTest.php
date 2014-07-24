<?php


class TestModelBase extends Tipsy\Model {
	public function test() {
		return 'ONE';
	}
}

class TestModelBaseProtected extends Tipsy\Model {
	protected function test() {
		return 'ONE';
	}
}


class ModelTest extends Tipsy_Test {
	
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
	
	public function testModelCustomExtend() {
		$this->tip->model('TestModelBaseProtected/TestModel', function() {
			$model = [
				'test' => function() {
					return 'TWO';
				}
			];
			return $model;
		});
		
		$model = $this->tip->model('TestModel');
		$this->assertEquals('TWO', $model->test());
	}
	
	public function testModelCustomExtendCall() {
		$this->tip->model('TestModelBase/TestModel', function() {
			$model = [

			];
			return $model;
		});
		
		$model = $this->tip->model('TestModel');
		$this->assertEquals('ONE', $model->test());
	}

	/*

		$this->tip->router()
			->otherwise(function($db, $TestModel) {
			});

		


		
		$this->assertTrue($check == 'YES');
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
