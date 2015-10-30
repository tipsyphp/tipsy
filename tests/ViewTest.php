<?php


class ViewTest extends Tipsy_Test {

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use
	}

	public function testViewFail() {
		$_REQUEST['__url'] = 'router/view';

		$this->tip->config(['view' => [
			'path' => 'tests'
		]]);

		$this->ob();

		$this->tip->router()
			->when('router/view', function($View) use ($catch) {
				try {
					$View->display('Fail');
				} catch (Exception $e) {
					echo 'YAY';
				}
			});
		$this->tip->start();

		$res = $this->ob(false);
		$this->assertEquals('YAY', $res);
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

	public function testViewExtension() {
		$_REQUEST['__url'] = 'router/view';

		$this->tip->config(['view' => [
			'path' => 'tests'
		]]);

		$this->ob();

		$this->tip->router()
			->when('router/view', function($View, $Scope) {
				$Scope->test = 'ONE';
				$View->display('ScopeTest.phtml');
			});
		$this->tip->start();

		$res = $this->ob(false);

		$this->assertEquals('ONE', $res);
	}

	public function testViewAbsolute() {
		$_REQUEST['__url'] = 'router/view';

		$this->tip->config(['view' => [
			'path' => 'tests'
		]]);

		$this->ob();

		$this->tip->router()
			->when('router/view', function($View, $Scope) {
				$Scope->test = 'ONE';
				$View->display(realpath(__DIR__ . '/ScopeTest.phtml'));
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

	public function testViewMultiScope() {
		$_REQUEST['__url'] = 'router/view';

		$this->tip->config(['view' => [
			'path' => 'tests'
		]]);

		$this->ob();

		$this->tip->router()
			->when('router/view', function($View, $Scope) {
				$Scope->test = 'ONE';
				$View->display('MultiScopeTest');
			});
		$this->tip->start();

		$res = $this->ob(false);

		$this->assertEquals('ONETWOTHREEFOURFIVESIXTWOTWO', trim($res));
	}



}
