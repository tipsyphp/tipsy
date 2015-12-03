<?php

class ViewFilterForTest extends Tipsy\View\Filter {
	public static function filter($content, $arguments = [] ) {
		return $arguments[0].$content.$arguments[1];
	}
}


class ViewFilterTest extends Tipsy_Test {

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true;
		$this->tip->config(['view' => [
			'path' => 'tests'
		]]);
	}

	public function testViewFilterStrip() {
		$_REQUEST['__url'] = 'router/view';

		$this->tip->config(['view' => [
			'path' => 'tests',
			'filters' => ['Tipsy\View\Filter\StripWhite']
		]]);

		$this->ob();

		$this->tip->router()
			->when('router/view', function($View, $Scope) {
				$Scope->test = 'multi
				line
				stuff';
				$View->display('ViewFilterTest');
			});
		$this->tip->start();

		$res = $this->ob(false);

		$this->assertEquals('multilinestuffmultilinestuffsome other testing<br>lotsmoretesting', trim($res));
	}

	public function testViewFilterArgs() {
		$_REQUEST['__url'] = 'router/view';

		$this->ob();

		$this->tip->view()->filter('ViewFilterForTest', ['A', 'B']);

		$this->tip->router()
			->when('router/view', function($View, $Scope) {
				$Scope->test = 'CONTENT';
				$View->display('ScopeTest');
			});
		$this->tip->start();

		$res = $this->ob(false);

		$this->assertEquals('ACONTENTB', trim($res));
	}

	public function testViewFilterFunction() {
		$_REQUEST['__url'] = 'router/view';

		$this->ob();

		$this->tip->view()->filter(function($content) {
			return 'FUNCTION'.$content;
		});

		$this->tip->router()
			->when('router/view', function($View, $Scope) {
				$Scope->test = 'CONTENT';
				$View->display('ScopeTest');
			});
		$this->tip->start();

		$res = $this->ob(false);

		$this->assertEquals('FUNCTIONCONTENT', trim($res));
	}

	public function testViewFilterClassFail() {
		$_REQUEST['__url'] = 'router/view';

		$this->tip->view()->filter('FAIL');

		$this->tip->router()
			->when('router/view', function($View) use (&$res) {
				try {
					$View->display('ScopeTest');
				} catch (Exception $e) {
					$res = $e->getMessage();
				}
			});
		$this->tip->start();

		$this->assertEquals('Filter class "FAIL" doest not exist.', trim($res));
	}

	public function testViewFilterFail() {
		$_REQUEST['__url'] = 'router/view';

		$this->tip->view()->filter(12);

		$this->tip->router()
			->when('router/view', function($View) use (&$res) {
				try {
					$View->display('ScopeTest');
				} catch (Exception $e) {
					$res = $e->getMessage();
				}
			});
		$this->tip->start();

		$this->assertEquals('Invalid filter.', trim($res));
	}
}
