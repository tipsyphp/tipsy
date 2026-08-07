<?php

declare(strict_types=1);

namespace Tipsy;

class Middleware extends Service {
	public function init(array $args = []): mixed {
		if ($this->closure()) {
			return $this->inject($this->closure(), $this->_scope ?? null);
		}
		return null;
	}

	public function run(mixed $args = null): mixed {
		// dont need to do anything for now
		return null;
	}

	public static function _start(array $middleware, App $tipsy): void {
		$m = $tipsy->service($middleware['service']);

		if ($m->hasMethod('run') || method_exists($m, 'run')) {
			$status = $m->run($middleware['args']);
			if ($status === false) {
				throw new Exception('Middleware "' . $middleware['service'] . '" failed to start');
			}
		}
	}
}
