<?php

namespace Tipsy;


class RouteAlias extends Route {
	protected $_to;
	protected $_from;

	public function __construct($args = []) {
		$this->tipsy($args['tipsy']);

		$this->_to = $args['to'];
		$this->_from = $args['from'];

		$this->_caseSensitive = false;
		$this->_route = preg_replace('/^\/?(.*?)\/?$/i','\\1', $args['from']);
		$this->_tipsy = $args['tipsy'];
		$this->_method = '*';
		$this->_routeParams = new RouteParams;
	}

	public function match($page) {
		if (parent::match($page)) {
			// remap params and return new route as string
			$to = explode('/', $this->_to);

			foreach ($to as $k => $t) {
				foreach ($this->_routeParams->properties() as $key => $value) {
					$to[$k] = str_replace(':'.$key, $value, $t);
				}
			}
			$to = implode('/', $to);
			return $to;

		} else {
			return false;
		}
	}

}
