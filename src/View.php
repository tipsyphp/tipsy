<?php

declare(strict_types=1);

namespace Tipsy;

class View {
	private string $_layout = 'layout';
	private ?array $_headers = null;
	private bool $_rendering = false;
	private ?array $_stack = null;
	private string $_path = '';
	private ?App $_tipsy = null;
	private array $_filters = [];
	private string $_extension = '.phtml';
	private ?Scope $_scope = null;
	public ?string $content = null;

	public function __construct(array $args = []) {
		$this->_headers = [];

		$this->config($args);

		$this->_tipsy = $args['tipsy'] ?? null;
	}

	public function config(array $args = []): void {
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

	public function stack(): array {
		$stack = $this->tipsy()?->config()['view']['stack'] ?? null;
		if (!$stack) {
			$stack = [''];
		}
		return $stack;
	}

	public function mtime(string $file): int|false {
		$resolved = $this->file($file);
		return $resolved ? filemtime($resolved) : false;
	}

	public function file(string $src): ?string {
		$stack = $this->stack();

		// absolute path
		if (isset($src[0]) && $src[0] === '/' && file_exists($src)) {
			return $src;
		}

		foreach ($stack as $dir) {
			$path = self::joinPaths($this->_path, $dir, $src . $this->_extension);
			if (file_exists($path) && is_file($path)) {
				return $path;
			}
			$path = self::joinPaths($this->_path, $dir, $src);
			if (file_exists($path) && is_file($path)) {
				return $path;
			}
		}

		return null;
	}

	private static function joinPaths(string ...$parts): string {
		$paths = [];
		$isAbsolute = isset($parts[0]) && str_starts_with($parts[0], '/');
		foreach ($parts as $arg) {
			$paths[] = trim($arg, '/');
		}
		$paths = array_filter($paths);
		$path = implode('/', $paths);
		return $isAbsolute ? '/' . $path : $path;
	}

	public function layout(): ?string {
		return $this->file($this->_layout);
	}

	public function render(string $view, ?array $params = null, bool $display = false): string {
		if ($params !== null) {
			foreach ($params as $key => $value) {
				$this->scope()->{$key} = $value;
			}
		}

		$file = $this->file($view);
		if (!$file) {
			throw new Exception('Could not find view file: "' . $view . '" in "' . (implode(',', $this->stack())) . '"');
		}
		$layout = $this->layout();


		$p = $this->scope()->properties();

		extract($this->scope()->properties(), EXTR_REFS);

		$difVars = get_defined_vars();

		$include = function(string $view, array $scope = []) use ($difVars, $p): string {
			$use = [];

			foreach ($scope as $k => $var) {
				if (($scope[$k] ?? null) !== ($difVars[$k] ?? null) && !in_array($k, ['Request', 'difVars', 'include'], true)) {
					$use[$k] = $var;
				}
			}

			return $this->render($view, $use);
		};

		// @todo: add all the other services
		$Request = $this->tipsy()?->request();

		if ($this->_rendering || !isset($display)) {

			ob_start();
			include($file);
			$page = $this->filterContent(ob_get_contents() ?: '');
			ob_end_clean();

		} else {

			$this->_rendering = true;
			ob_start();
			include($file);
			$this->content = $this->filterContent(ob_get_contents() ?: '');
			ob_end_clean();

			if ($layout) {
				ob_start();
				include($layout);
				$page = $this->filterContent(ob_get_contents() ?: '');
				ob_end_clean();
				$this->_rendering = false;
			} else {
				$page = $this->content;
			}
		}

		return $page ?? '';
	}

	public function display(string $view, ?array $params = null): void {
		echo $this->render($view, $params, true);
	}

	public function filterContent(string $content): string {
		foreach ($this->_filters as $filter) {
			if (is_callable($filter['filter'])) {
				$content = $filter['filter']($content, $filter['arguments']);
			} elseif (is_string($filter['filter'])) {
				if (class_exists($filter['filter'])) {
					$content = $filter['filter']::filter($content, $filter['arguments']);
				} else {
					throw new Exception('Filter class "' . $filter['filter'] . '" does not exist.');
				}
			} else {
				throw new Exception('Invalid filter.');
			}
		}
		return $content;
	}

	public function tipsy(): ?App {
		return $this->_tipsy;
	}

	public function scope(?Scope &$scope = null): Scope {
		if ($scope !== null) {
			$this->_scope = $scope;
		}
		if ($this->_scope === null) {
			$this->_scope = new Scope();
		}
		return $this->_scope;
	}

	public function filter(callable|string|null $filter, array $arguments = []): mixed {
		if ($filter) {
			$this->_filters[] = [
				'filter' => $filter,
				'arguments' => $arguments
			];
		}
		return $this->_filters;
	}
}
