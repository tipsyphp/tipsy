<?php

declare(strict_types=1);

namespace Tipsy;


class RouteAlias extends Route {
	protected string $_to = '';
	protected string $_from = '';

	public function __construct(array $args = []) {
		$this->tipsy($args['tipsy'] ?? null);

		$this->_to = $args['to'] ?? '';
		$this->_from = $args['from'] ?? '';

		// Build parent route configuration
		parent::__construct([
			'route' => $args['from'] ?? '',
			'caseSensitive' => false,
			'method' => '*',
			'tipsy' => $args['tipsy'] ?? null,
		]);
	}

	public function match(string $page): string|static|false {
		if (parent::match($page)) {
			// remap params and return new route as string
			$to = explode('/', $this->_to);

			$params = $this->params();
			if ($params instanceof Scope) {
				foreach ($to as $k => $t) {
					foreach ($params->properties() as $key => $value) {
						$to[$k] = str_replace(':' . $key, (string)$value, $t);
					}
				}
			}
			return implode('/', $to);

		} else {
			return false;
		}
	}
}
