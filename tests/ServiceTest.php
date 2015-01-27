<?php

class LoginService extends \Tipsy\Service {
	function __construct() {
		$this->loggedin = 'YES';
	}
	function stuff($stuff) {
		return 'YES'.$stuff;
	}
}


class ServiceTest extends Tipsy_Test {
	
	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use
	}
	
	public function testServiceClass() {
		$_REQUEST['__url'] = '';
		$class = $this->tip->service('LoginService');

		$class = get_class($this->tip->service('LoginService'));

		$this->assertEquals('LoginService', $class);
	}
	/*
	public function testServiceClassController() {
		$_REQUEST['__url'] = '';
		$this->tip->service('LoginService');
		$check = $this->ob(false);

		$this->tip->router()
			->when('', function($LoginService) {
				echo get_class($LoginService);
			});
			
		$this->ob();
		$this->tip->start();
		$check = $this->ob(false);

		$this->assertEquals('LoginService', $check);
	}
	
	public function testServiceFunc() {
		$_REQUEST['__url'] = '';
		$this->tip->service('LoginService');

		$this->tip->router()
			->when('', function($LoginService) {
			die(get_class($LoginService));
				echo $LoginService->stuff('MAM');
			});
			
		$this->ob();
		$this->tip->start();
		$check = $this->ob(false);

		$this->assertEquals('YESMAM', $check);
	}
	
	public function testServiceConstruct() {
		$_REQUEST['__url'] = '';
		$this->tip->service('LoginService');

		$this->tip->router()
			->when('', function($LoginService) {
				echo $LoginService->loggedin;
			});
			
		$this->ob();
		$this->tip->start();
		$check = $this->ob(false);
		
		die($this->tip->service('LoginService')->loggedin);

		$this->assertEquals('YES', $check);
	}
	
	public function testServiceAccess() {
		$_REQUEST['__url'] = '';
		$this->tip->service('LoginService');
		$this->tip->service('LoginService')->testVar = 'YES';

		$this->tip->router()
			->when('', function($LoginService) {
				echo $LoginService->testVar;
			});
			
		$this->ob();
		$this->tip->start();
		$check = $this->ob(false);

		$this->assertEquals('YES', $check);
	}
	*/

}
