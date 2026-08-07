<?php

class AnonymousControllerTest extends Tipsy_Test {

	public function setUp(): void {
		parent::setUp();
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true;
	}

	public function testRouterAnonymousClass() {
		$_REQUEST['__url'] = 'router/library';

		$this->ob();

		$this->tip->router()
			->when('router/library', new class() extends Tipsy\Controller {
				public function init(array $args = []): mixed {
					echo 'ANONY';
					return null;
				}
			});
		$this->tip->start();

		$check = $this->ob(false);
		$this->assertEquals('ANONY', $check);
	}

}
