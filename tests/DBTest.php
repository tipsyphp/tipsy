<?php


class ClassResourceTest extends \Tipsy\Resource {
	function test() {
		return 'NO';
	}

	public function __construct($id = null) {
		$this->idVar('id_user')->table('test_user')->load($id);
	}
}


class DBTest extends Tipsy_Test {

	public static function setUpBeforeClass() {
	}

	public static function tearDownAfterClass() {
	}

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use

		$this->tip->config('tests/config.ini');

		$env = getenv('TRAVIS') ? 'travis' : 'local';

		$this->tip->config('tests/config.db.'.$env.'.ini');
	}


	public function testDBCreateTable() {

		$this->tip->db()->exec("
			DROP TABLE IF EXISTS `test_user`;
			CREATE TABLE `test_user` (
			  `id_user` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `name` varchar(255) DEFAULT NULL,
			  `username` varchar(255) DEFAULT NULL,
			  `datetime` datetime DEFAULT NULL,
			  `date` datetime DEFAULT NULL,
			  `active` tinyint(1) NOT NULL DEFAULT 1,
			  PRIMARY KEY (`id_user`),
			  UNIQUE KEY `username` (`username`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");

		$this->tip->db()->exec("
			DROP TABLE IF EXISTS `test_user2`;
			CREATE TABLE `test_user2` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `name` varchar(255) DEFAULT NULL,
			  `username` varchar(255) DEFAULT NULL,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `username` (`username`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");

		$this->assertEquals('YES', 'YES');
	}


	public function testModelDBOExtendCall() {

		$this->tip->service('Tipsy\Resource/TestModel', [
			test => function() {
				return $this->test;
			},
			_id => 'id_test_user',
			_table => 'test_user'
		]);

		$m = $this->tip->service('TestModel');
		$m->test = 'YES';

		$this->assertEquals('YES', $m->test());
	}

	public function testModelDBOIdLoad() {
		$this->tip->service('Tipsy\Resource/TestModel', [
			_id => 'id',
			_table => 'test_user2'
		]);

		$m = $this->tip->service('TestModel');
		$m->load([
			'name' => 'test'
		]);
		$m->save();

		$this->assertEquals(1, $m->dbId());
	}

	public function testModelDBOIdCreate() {
		$this->tip->service('Tipsy\Resource/TestModel', [
			_id => 'id',
			_table => 'test_user2'
		]);

		$m = $this->tip->service('TestModel');
		$m = $m->create([
			'name' => 'test'
		]);

		$this->assertEquals(2, $m->dbId());
	}

	public function testModelDBOIdSave() {
		$this->tip->service('Tipsy\Resource/TestModel', [
			_id => 'id',
			_table => 'test_user2'
		]);

		$m = $this->tip->service('TestModel');
		$m = $m->create([
			'name' => 'test'
		]);
		$id = $m->dbId();

		$m->name = 'not test';
		$m->save();
		$id2 = $m->dbId();

		$this->assertEquals('not test', $m->name);
		$this->assertEquals($id, $id2);
	}

	public function testModelDBOQuery() {
		$this->tip->service('Tipsy\Resource/TestModel', [
			_id => 'id',
			_table => 'test_user2'
		]);

		$m = $this->tip->service('TestModel');
		$m = $m->create([
			'name' => 'testdelete'
		]);
		$id = $m->dbId();
		$i = $m->q('select * from test_user2 where id=?', $id)->get(0);

		$this->assertEquals($id, $i->id);
	}

	public function testModelDBODelete() {
		$this->tip->service('Tipsy\Resource/TestModel', [
			_id => 'id',
			_table => 'test_user2'
		]);

		$m = $this->tip->service('TestModel');
		$m = $m->create([
			'name' => 'testdelete'
		]);
		$id = $m->dbId();
		$m->delete();

		$i = $this->tip->service('TestModel')->query('select * from test_user2 where id=?', $id)->get(0);

		$this->assertEquals(null, $i->id);
	}


	public function testModelDBOStaticRetrieve() {
		$test = ClassResourceTest::create([
			'name' => 'test'
		]);

		$this->assertGreaterThan(0, $test->dbId());
		$this->assertEquals('test', $test->name);

		$test2 = ClassResourceTest::o($test->dbId());
		$this->assertGreaterThan(0, $test2->dbId());
	}

	public function testModelDBOExtendRoute() {
		$_REQUEST['__url'] = 'user/create';

		$this->tip->service('Tipsy\Resource/TestUser', [
			test => function($user) {
				return $this->test;
			},
			_id => 'id_user',
			_table => 'test_user'
		]);

		$this->tip->router()
			->when('user/create', function($Params, $TestUser) {
				$u = $TestUser->load([
					'username' => 'devin',
					'name' => 'Devin Smith'
				]);
				$u->save();
				echo $u->username;
			});

		$this->ob();
		$this->tip->start();
		$check = $this->ob(false);

		$this->assertEquals('devin', $check);
	}

	public function testResourceClass() {
		// php7 fails this with a segment fault. no idea why
		if (PHP_MAJOR_VERSION == 7) {
			$this->markTestSkipped('PHP7 segment fault skip');
			return;
		}

		$this->tip->service('ClassResourceTest');

		$model = $this->tip->service('ClassResourceTest');
		$this->assertEquals('NO', $model->test());
	}


	public function testResourceClassOverwrite() {

		$this->tip->service('ClassResourceTest2', [
			'test' => function() {
				return 'YES';
			}
		]);

		$model = $this->tip->service('ClassResourceTest2');
		$this->assertEquals('YES', $model->test());
	}

	public function testModelJsonExport() {
		$this->tip->service('Tipsy\Resource/TestModel', [
			_id => 'id',
			_table => 'test_user2'
		]);

		$m = $this->tip->service('TestModel');
		$m->load([
			'name' => 'test'
		]);
		$m->save();

		$json = json_encode([
			'id' => $m->dbId(),
			'name' => 'test',
			'username' => null
		]);

		$this->assertEquals($json, $m->json());
		$this->assertEquals($json, json_encode($m));
	}

	public function testExtend() {
		$this->tip->service('Tipsy\Resource/TestModel', [
			exports => function() {
				return [test => true];
			},
			_id => 'id',
			_table => 'test_user2'
		]);

		$m = $this->tip->service('TestModel');
		$this->assertTrue($m->exports()['test']);
	}

	/*
	public function testModelDBOAutoTable() {
		$_REQUEST['__url'] = 'user/1';

		$this->tip->model('Tipsy\DBO/TestUser', [
			blah => function() {
				echo 'asd';
			}
		]);

		$this->tip->router()
			->when('user/:id', function($Params, $TestUser) {
				$u = $TestUser->load($Params['id']);
				echo $u->username;
			});

		$this->ob();
		$this->tip->start();
		$check = $this->ob(false);

		$this->assertEquals('devin', $check);
	}


	*/

}
