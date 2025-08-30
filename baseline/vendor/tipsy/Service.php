<?php

namespace Tipsy;

class Service extends DependencyInjector {
	public function init($args = []) {
		if ($this->closure()) {
			return $this->inject($this->closure(), $this->_scope);
		}
	}
}