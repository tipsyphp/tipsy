<?php


class DBUrlTest extends Tipsy_Test {

	public function setUp() {
		$this->tip = new Tipsy\Tipsy;
		$this->useOb = true; // for debug use

		$this->tip->config('tests/config.ini');
		$env = getenv('TRAVIS') ? 'travis' : 'local';
		$this->tip->config('tests/config.db.'.$env.'.ini');

		$url = 'mysql://'.$this->tip->config()['db']['user'].($this->tip->config()['db']['pass'] ? ':'.$this->tip->config()['db']['pass'] : '').'@'.$this->tip->config()['db']['host'].'/'.$this->tip->config()['db']['database'].'?persistent=true&something=else';

		// rebuild
		$this->tip = new Tipsy\Tipsy;
		$this->tip->config('tests/config.ini');
		$this->tip->config([db => [url => $url]]);
	}

	public function testDbUrl() {
		$catch = false;
		try {
			$res = $this->tip->db()->query('select now() as d');
			foreach ($res as $r) {
			}
		} catch (Exception $e) {
			echo $e->getMessage();
			$catch = true;
		}
		$this->assertFalse($catch);
	}

	public function testDbFail() {
		$this->tip = new Tipsy\Tipsy;

		$catch = false;
		try {
			$res = $this->tip->db()->query('select now()');
			foreach ($res as $r) {
			}
		} catch (Exception $e) {
			$catch = true;
		}
		$this->assertTrue($catch);
	}
}
