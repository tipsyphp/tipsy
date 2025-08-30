<?php

namespace Tipsy;

/**
 * Controller object
 */
class Controller extends DependencyInjector {
	protected $_scope;

	public function __construct($args = []) {
		parent::__construct($args);
		$this->_scope = new Scope;
		$this->_tipsy = $args['tipsy'];
	}
	public function init($args = []) {
		$this->tipsy()->view()->scope($this->_scope);

		if ($this->closure()) {
			return $this->inject($this->closure());
		}
	}
	public function inject($closure, $scope = null) {
		return parent::inject($closure, $this->_scope);
	}
}
