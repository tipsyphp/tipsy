<?php


class ClassResourceTest extends \Tipsy\Resource {
	function test() {
		return 'NO';
	}

	public function __construct($id = null) {
		$this->idVar('id')->table('test_user')->load($id);
	}
}


class DBTest extends Tipsy_Test {

	public static function setUpBeforeClass() {
	}

	public static function tearDownAfterClass() {
	}

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true;

		$this->tip->config('tests/config.ini');
		$this->setupDb($this->tip);
	}


	public function testDBCreateTable() {
		$this->tip->service('Tipsy\Resource/TestUser', [
			_id => 'id',
			_table => 'test_user',
			_fields => [
				id => (object)[
					'field' => 'id',
					'type' => 'int',
					'null' => false,
					'auto' => true,
					'length' => 11,
					'unsigned' => true
				],
				name => (object)[
					'field' => 'name',
					'type' => 'char',
					'null' => true,
					'length' => 255,
					'default' => 'user'
				],
				username => (object)[
					'field' => 'username',
					'type' => 'char',
					'null' => true
				],
				active => (object)[
					'field' => 'active',
					'type' => 'bool',
					'null' => false,
					'default' => true
				]
			]
		]);
		$this->tip->service('TestUser')->dropTable();

		$this->tip->service('Tipsy\Resource/TestUser2', [
			_id => 'id',
			_table => 'test_user2',
			_fields => [
				id => (object)[
					'field' => 'id',
					'type' => 'int',
					'null' => false,
					'auto' => true,
					'length' => 11,
					'unsigned' => true
				],
				name => (object)[
					'field' => 'name',
					'type' => 'char',
					'null' => true,
					'length' => 255,
					'default' => 'user'
				],
				username => (object)[
					'field' => 'username',
					'type' => 'char',
					'null' => true
				]
			]
		]);
		$this->tip->db()->exec('drop table if exists test_user2');
		$this->tip->service('TestUser2')->dropTable();

		$this->tip->service('TestUser')->fields();
		$this->tip->service('TestUser2')->fields();

/*
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
*/

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

		$this->tip->service('Tipsy\Resource/TestModel', [
			test => function($user) {
				return $this->test;
			},
			_id => 'id',
			_table => 'test_user'
		]);

		$this->tip->router()
			->when('user/create', function($Params, $TestModel) {
				$u = $TestModel->load([
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

	public function testModelDBOAutoTable() {
		$_REQUEST['__url'] = 'user/1';

		$this->tip->db()->query('DROP TABLE IF EXISTS `test_auto_user`');

		$this->tip->service('Tipsy\Resource/TestUser', [
			_id => 'id',
			_table => 'test_auto_user',
			_fields => [
				id => (object)[
					'field' => 'id',
					'type' => 'int',
					'null' => false,
					'auto' => true,
					'length' => 11,
					'unsigned' => true
				],
				age => (object)[
					'field' => 'age',
					'type' => 'int',
					'default' => 0
				],
				first_name => (object)[
					'field' => 'first_name',
					'type' => 'char',
					'null' => true,
					'length' => 255,
					'default' => 'user'
				],
				last_name => (object)[
					'field' => 'last_name',
					'type' => 'char',
					'null' => true
				],
				active => (object)[
					'field' => 'active',
					'type' => 'bool',
					'null' => false,
					'default' => false
				]
			]
		]);

		$this->tip->router()
			->when('user/:id', function($Params, $TestUser) use (&$res) {
				$u = $TestUser->create([
					last_name => 'name'
				]);
				$id = $u->id;
				$u = null;
				$u = $TestUser->load($id);
				$res = $u->json();
			});

		$this->tip->start();
		$this->assertEquals('{"id":1,"age":0,"first_name":null,"last_name":"name","active":false}', $res);
	}


	public function testResourceGetGet() {
		$this->tip->service('Tipsy\Resource/TestModel', [
			_id => 'id',
			_table => 'test_user2'
		]);

		$m = $this->tip->service('TestModel');
		$o = $m->q('select * from '.$m->table().' limit 1')->get(0)->get(0)->get(0);
		$this->assertTrue($o->id ? true : false);
	}

	public function testResourceCreateTableFail() {
		$this->tip->service('Tipsy\Resource/TestModel', [
			_id => 'id',
			_table => 'test_user_fail'
		]);

		try {
			$this->tip->service('TestModel')->fields();
		} catch (Exception $e) {
			$m = $e->getMessage();
		}
		$this->assertEquals('Could not create table "test_user_fail"', $m);
	}

	public function testResourceCreateObject() {
		$o = new ClassResourceTest((object)[
			name => 'devin'
		]);
		$this->assertEquals('devin', $o->name);
	}

	public function testResourceStaticO() {
		$o = ClassResourceTest::o(1);
		$this->assertEquals(1, $o->id);
	}

	public function testResourceStaticQ() {
		$o = ClassResourceTest::q('select * from test_user limit 1')->get(0);
		$this->assertEquals(1, $o->id);
	}


	public function testResourceReload() {
		$o = ClassResourceTest::q('select * from test_user limit 1')->get(0);
		$name = $o->name;
		$o->name = 'new';
		$o->load();
		$this->assertEquals($name, $o->name);
	}

	public function testResourceSerialize() {
		$o = ClassResourceTest::q('select * from test_user limit 1')->get(0);
		$o->serialize([
			name => 'newarray'
		]);
		$this->assertEquals('newarray', $o->name);
	}

	public function testResourceSerializeObject() {
		$o = ClassResourceTest::q('select * from test_user limit 1')->get(0);
		$o->serialize((object)[
			name => 'newobject'
		]);
		$this->assertEquals('newobject', $o->name);
	}

}
