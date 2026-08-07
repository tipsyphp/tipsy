<?php

declare(strict_types=1);

namespace Tipsy;

class Service extends DependencyInjector {
	public function init(array $args = []): mixed {
		if ($this->closure()) {
			return $this->inject($this->closure(), $this->_scope ?? null);
		}
		return null;
	}
}