<?php

namespace Tipsy;

class Middleware extends Service {
	public function init($args = []) {
		if ($this->closure()) {
			return $this->inject($this->closure(), $this->_scope);
		}
	}
	public function run($args = null) {
		// dont need to do anything for now
	}

	public static function _start($middleware, $tipsy) {
		$m = $tipsy->service($middleware['service']);

		if ($m->hasMethod('run') || method_exists($m, 'run')) {
			$status = $m->run($middleware['args']);
			if ($status === false) {
				throw new Exception('Middleware "'.$middleware['service'].'" failed to start');
			}
		}
	}
}
