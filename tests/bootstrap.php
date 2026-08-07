<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
putenv('PHPUNIT=1');

// Autoload files using Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

class Tipsy_Test extends PHPUnit\Framework\TestCase {
	protected bool $useOb = false;
	protected ?Tipsy\Tipsy $tip = null;

	protected function setUp(): void {
		$_REQUEST = [];
		$_GET = [];
		$_POST = [];
		$_SERVER['REQUEST_URI'] = '';
		$_SERVER['SCRIPT_NAME'] = '';
	}

	public function setupDb(Tipsy\Tipsy $tipsy): void {
		// Default DB is Postgres (Tipsy 2.0). Set DB=mysql for the MySQL matrix.
		$tipsy->config('tests/config.db.' . (getenv('TRAVIS') || getenv('CI') ? 'travis' : 'local') . '.' . (getenv('DB') ?: 'pgsql') . '.ini');
	}

	public function ob(bool $start = true): ?string {
		if (!$this->useOb) {
			return null;
		}
		if ($start) {
			ob_clean();
			ob_start();
			return null;
		} else {
			$check = ob_get_contents();
			if (!$this->useOb) {
				ob_end_flush();
			} else {
				ob_end_clean();
			}

			return $check ?: null;
		}
	}
}
