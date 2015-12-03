<?php


class ClassResourceTestPg extends \Tipsy\Resource {
	function test() {
		return 'NO';
	}

	public function __construct($id = null) {
		$this->idVar('id_user')->table('test_user')->load($id);
	}
}





// transforms mysql queries to pgsql (kinda)
class Db extends \Tipsy\Db {
	public static function mysqlToPgsql($query, $args = []) {
		// replace backticks
		$query = str_replace('`','"', $query);

		// replace add single quotes to interval statements
		$query = preg_replace('/(interval) ([0-9]+) ([a-z]+)/i','\\1 \'\\2 \\3\'', $query);

		// replace unix_timestamp
		$query = preg_replace('/unix_timestamp( )?\((.*?)\)/i','extract(epoch FROM \\2)', $query);

		// replace date_sub
		$query = preg_replace('/(date_sub\((.*?),(.*?))\)/i','\\2 - \\3', $query);

		// replace date formats
		$query = preg_replace_callback('/date_format\(( )?(.*?),( )?("(.*?)"|\'(.*?)\')( )?\)/i',function($m) {
			$find = ['/\%Y/', '/\%m/', '/\%d/', '/\%H/', '/\%i/', '/\%s/', '/\%W/'];
			$replace = ['YYYY', 'MM', 'DD', 'HH24', 'MI', 'SS', 'D'];
			$format = preg_replace($find, $replace, $m[6] ? $m[6] : $m[5]);
			return 'to_char('.$m[2].', \''.$format.'\')';
		}, $query);


		if ($args) {
			foreach ($args as $k => $v) {
				if ($v === true) {
					$args[$k] = 'true';
				} elseif ($v === false) {
					$args[$k] = 'false';
				}
			}
		}
		return [query => $query, args => $args];
	}

	public function query($query, $args = []) {
		if (!$query) {
			throw new Exception('Query is emtpy');
		}
		list($query, $args) = self::mysqlToPgsql($query, $args);
		if (!$query) {
			throw new Exception('mysqlToPgsql Query is emtpy');
		}
		return parent::query($query, $args);
	}

	public function exec($query) {
		return parent::exec(self::mysqlToPgsql($query)['query']);
	}
}






class DBPostgreSQLTest extends Tipsy_Test {

	public static function setUpBeforeClass() {
	}

	public static function tearDownAfterClass() {
	}

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use

		$this->tip->config('tests/config.ini');

		$env = getenv('TRAVIS') ? 'travis' : 'local';

		$this->tip->config('tests/config.db.'.$env.'.pgsql.ini');

		$bs->service('Db');

	}


	public function testDBCreateTable() {

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
		$this->assertEquals('{"id":1,"age":0,"first_name":null,"last_name":"name","active":0}', $res);
	}
}
