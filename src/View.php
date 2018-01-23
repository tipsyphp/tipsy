<?php

namespace Tipsy;

class View {
	private $_layout = 'layout';
	private $_headers;
	private $_rendering = false;
	private $_stack;
	private $_path = '';
	private $_tipsy;
	private $_filters = [];
	private $_extension = '.phtml';

	public function __construct ($args = []) {
		$this->headers = [];

		$this->config($args);

		$this->_tipsy = $args['tipsy'];
		$this->_scope = $scope;
	}

	public function config($args = null) {
		if (isset($args['layout'])) {
			$this->_layout = $args['layout'];
		}

		if (isset($args['stack'])) {
			$this->_stack = $args['stack'];
		}

		if (isset($args['path'])) {
			$this->_path = $args['path'];
		}

		if (isset($args['filters'])) {
			foreach ($args['filters'] as $filter) {
				$this->filter($filter);
			}
		}
	}

	public function stack() {
		$stack = $this->tipsy()->config()['view']['stack'];
		if (!$stack) {
			$stack = [''];
		}
		return $stack;
	}

	public function mtime($file) {
		return filemtime($this->file($file));
	}

	public function file($src) {
		$stack = $this->stack();

		// absolute path
		if ($src{0} == '/' && file_exists($src)) {
			return $src;
		}

		foreach ($stack as $dir) {
			$path = self::joinPaths($this->_path, $dir, $src.$this->_extension);
			if (file_exists($path) && is_file($path)) {
				$file = $path;
				break;
			}
			$path = self::joinPaths($this->_path, $dir, $src);
			if (file_exists($path) && is_file($path)) {
				$file = $path;
				break;
			}
		}

		return $file;
	}

	private static function joinPaths() {
		$args = func_get_args();
		$paths = [];
		foreach ($args as $arg) {
			$paths = array_merge($paths, (array)$arg);
		}

		$paths = array_map(function($p) {
			return trim($p, '/');
		}, $paths);
		$paths = array_filter($paths);
		return join('/', $paths);
	}

	public function layout() {
		return $this->file($this->_layout);
	}

	public function render($view, $params = null, $display = false) {
		if (isset($params)) {
			foreach ($params as $key => $value) {
				$this->scope()->{$key} = $value;
			}
		}

		$file = $this->file($view);
		if (!$file) {
			throw new Exception('Could not find view file: "'.$view.'" in "'.(implode(',',$this->stack())).'"');
		}
		$layout = $this->layout();


		$p = $this->scope()->properties();

		extract($this->scope()->properties(), EXTR_REFS);

		$difVars = get_defined_vars();

		$include = function($view, $scope = []) use ($difVars, $p) {
			$use = [];

			foreach ($scope as $k => $var) {
				if ($scope[$k] != $difVars[$k] && !in_array($k, ['Request', 'difVars', 'include'])) {
					$use[$k] = $var;
				}
			}

			return $this->render($view, $use);
		};

		// @todo: add all the other services
		$Request = $this->tipsy()->request();

		if ($this->_rendering || !isset($display)) {

			ob_start();
			include($file);
			$page = $this->filterContent(ob_get_contents());
			ob_end_clean();

		} else {

			$this->_rendering = true;
			ob_start();
			include($file);
			$this->content = $this->filterContent(ob_get_contents());
			ob_end_clean();

			if ($layout) {
				ob_start();
				include($layout);
				$page = $this->filterContent(ob_get_contents());
				ob_end_clean();
				$this->_rendering = false;
			} else {
				$page = $this->content;
			}
		}

		/* directly modify view variables. i dont think we need this
		if (isset($params['var'])) {
			$this->{$params['var']} = $page;
		}
		*/
		return $page;
	}

	public function display($view, $params = null) {
		echo $this->render($view, $params, true);
	}

	public function filterContent($content) {
		foreach ($this->_filters as $filter) {
			if (is_callable($filter['filter'])) {
				$content = $filter['filter']($content, $filter['arguments']);
			} else if (is_string($filter['filter'])) {
				if (class_exists($filter['filter'])) {
					$content = $filter['filter']::filter($content, $filter['arguments']);
				} else {
					throw new Exception('Filter class "'.$filter['filter'].'" doest not exist.');
				}
			} else {
				throw new Exception('Invalid filter.');
			}
		}
		return $content;
	}

	public function tipsy() {
		return $this->_tipsy;
	}

	public function scope(&$scope = null) {
		if ($scope) {
			$this->_scope = $scope;
		}
		return $this->_scope;
	}

	public function filter($filter, $arguments = []) {
		if ($filter) {
			$this->_filters[] = [
				'filter' => $filter,
				'arguments' => $arguments
			];
		}
		return $this->_filter;
	}
}
