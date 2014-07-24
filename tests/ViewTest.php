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
		
		//$this->tip->config('../config.ini');
		
		
/*
$this->tip->config([
	'db' => [
		'host' => 'blah'
	]
], true);
*/



	}

	public function testRouterBasic() {
		$_REQUEST['__url'] = 'router/basic';
		
		$this->ob();

		$this->tip->router()
			->when('router/basic', function() {
				echo 'YES';
			});
		$this->tip->start();
		
		$check = $this->ob(false);
		
		$this->assertTrue($check == 'YES');
	}
	
	public function ob($start = true) {
		if (!$this->useOb) {
			return;
		}
		if ($start) {
			ob_start();
		} else {
			$check = ob_get_contents();
			if (!$this->useOb) {
				ob_end_flush();
			} else {
				ob_end_clean();
			}
			
			return $check;
		}
	}





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