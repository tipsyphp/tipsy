<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Controller object - handles MVC controller logic with dependency injection
 * Optimized for PHP 8.3+ performance
 */
class Controller extends DependencyInjector {
	protected Scope $_scope;

	public function __construct(array $args = []) {
		parent::__construct($args);
		$this->_scope = new Scope();
		$this->_tipsy = $args['tipsy'];
	}

	public function init(array $args = []): mixed {
		$this->tipsy()->view()->scope($this->_scope);

		if ($this->closure()) {
			return $this->inject($this->closure());
		}
		
		return null;
	}

	public function inject(\Closure $closure, mixed $scope = null): mixed {
		return parent::inject($closure, $this->_scope);
	}
}
