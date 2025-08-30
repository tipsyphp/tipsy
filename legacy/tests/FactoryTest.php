<?php

class kindOfResource extends Tipsy\Model {
	public function idVar() {
		return 'id';
	}
	public function dbId() {
		return $this->{$this->idVar()};
	}
}

class factoryResource extends \Tipsy\Resource {
	public function __construct($id = null) {
		$this->idVar('id')->table('test_user')->load($id);
		parent::__construct($id);
	}
}

class FactoryTest extends Tipsy_Test {
	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->tip->config('tests/config.ini');
		$this->setupDb($this->tip);
	}

	public function testBasicStorage() {
		$m = new Tipsy\Model;
		$m->a = 1;
		$this->tip->factory($m, 1);

		$m = new Tipsy\Model;
		$m->a = 2;
		$this->tip->factory($m, 2);

		$this->assertEquals(1, $this->tip->factory('Tipsy\\Model', 1)->a);
		$this->assertEquals(2, $this->tip->factory('Tipsy\\Model', 2)->a);
	}

	public function testResource() {
		$m = new factoryResource(1);
		$this->tip->factory($m);

		$m = new factoryResource(2);
		$this->tip->factory($m);

		$this->assertEquals(1, $this->tip->factory('factoryResource', 1)->dbId());
		$this->assertEquals(2, $this->tip->factory('factoryResource', 2)->dbId());
	}
/*
	public function testStaticAuto() {
		$m = factoryResource::o(1);
		$m->first = true;
		$m = factoryResource::o(2);
		$m->second = true;
		$this->assertEquals(1, $this->tip->factory('factoryResource')->dbId());
		$this->assertEquals(2, $this->tip->factory('factoryResource')->dbId());
	}

	public function testIdvarAuto() {
		$m = new kindOfResource;
		$m->id = 1;
		$this->tip->factory($m);

		$m = new kindOfResource;
		$m->id = 2;
		$this->tip->factory($m);

		$this->assertEquals(1, $this->tip->factory('kindOfResource')->dbId());
		$this->assertEquals(2, $this->tip->factory('kindOfResource')->dbId());
	}
*/
	public function testIdvarUser() {
		$m = new kindOfResource;
		$m->id = 1;
		$this->tip->factory($m);

		$m = new kindOfResource;
		$m->id = 2;
		$this->tip->factory($m);

		$this->assertEquals(1, $this->tip->factory('kindOfResource', 1)->dbId());
		$this->assertEquals(2, $this->tip->factory('kindOfResource', 2)->dbId());
	}

	public function testStaticRef() {
		$m = factoryResource::o(1);
		$m->first = true;
		$m = factoryResource::o(2);
		$m->second = true;

		$m = factoryResource::o(1);
		$this->assertEquals(1, $m->dbId());
		$this->assertEquals(true, $m->first);

		$m = factoryResource::o(2);
		$this->assertEquals(2, $m->dbId());
		$this->assertEquals(true, $m->second);
	}

	public function testStaticRefMulti() {
		$this->markTestSkipped('Test incomplete');
		$l = factoryResource::o(1,2,[id => 3, name => 'new']);

		$this->assertEquals(1, $l->dbId());
		$this->assertEquals(2, $l->dbId());
		$this->assertEquals(3, $l->dbId());
	}

	public function testObjRefMulti() {
		$this->markTestSkipped('Test incomplete');
		$m = new factoryResource;
		$l = $m->o(1,2,[id => 3, name => 'new']);

		$this->assertEquals(1, $l->dbId());
		$this->assertEquals(2, $l->dbId());
		$this->assertEquals(3, $l->dbId());
	}

	public function testObjAuto() {
		return;
		$m = new factoryResource;
		$a = $m->o(1);
		$a->first = true;
		$b = $m->o(2);
		$b->second = true;

		$a = $m->o(1);
		$this->assertEquals(1, $a->dbId());
		$this->assertEquals(true, $a->first);

		$b = $m->o(2);
		$this->assertEquals(2, $b->dbId());
		$this->assertEquals(true, $b->second);
	}
}
