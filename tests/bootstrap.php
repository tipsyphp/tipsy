<?php

error_reporting(E_ALL ^ (E_NOTICE | E_STRICT));
ini_set('display_errors',true);
putenv('PHPUNIT=1');

if (trim(`whoami`) == 'arzynik') {
	ini_set('mysqli.default_socket','/Applications/MAMP/tmp/mysql/mysql.sock');
}

// Autoload files using Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

//putenv('DB=pgsql');

class Tipsy_Test extends PHPUnit_Framework_TestCase {
	public function setupDb($tipsy) {
		$tipsy->config('tests/config.db.'.(getenv('TRAVIS') ? 'travis' : 'local').'.'.(getenv('DB') ? getenv('DB') : 'mysql' ).'.ini');

		if (getenv('DB') == 'pgsql') {
			$this->tip->service('Db', 'Tipsy\Db\MysqlToPgsql');
		}
	}

	public function ob($start = true) {
		if (!$this->useOb) {
			return;
		}
		if ($start) {
			ob_clean();
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
