<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * View engine - handles template rendering and layout management
 * Optimized for PHP 8.3+ performance
 */
class View {
	private string $_layout = 'layout';
	private array $_headers = [];
	private bool $_rendering = false;
	private ?array $_stack = null;
	private string $_path = '';
	private App $_tipsy;
	private array $_filters = [];
	private string $_extension = '.phtml';
	private ?Scope $_scope = null;

	public function __construct(array $args = []) {
		$this->_headers = [];
		$this->config($args);
		$this->_tipsy = $args['tipsy'];
		$this->_scope = $args['scope'] ?? null;
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
		if ($this->_stack !== null) {
			return $this->_stack;
		}
		
		$stack = $this->tipsy()->config()['view']['stack'] ?? null;
		return $stack ?: [''];
	}

	public function mtime(string $file): int|false {
		$filePath = $this->file($file);
		return $filePath ? filemtime($filePath) : false;
	}

	public function file(string $src): ?string {
		$stack = $this->stack();

		// Handle absolute path
		if (str_starts_with($src, '/') && file_exists($src)) {
			return $src;
		}

		foreach ($stack as $dir) {
			// Try with extension first
			$path = self::joinPaths($this->_path, $dir, $src . $this->_extension);
			if (file_exists($path) && is_file($path)) {
				return $path;
			}
			
			// Try without extension
			$path = self::joinPaths($this->_path, $dir, $src);
			if (file_exists($path) && is_file($path)) {
				return $path;
			}
		}

		return null;
	}

	private static function joinPaths(...$args): string {
		$paths = [];
		foreach ($args as $arg) {
			$paths = array_merge($paths, (array)$arg);
		}

		$paths = array_map(fn($p) => trim($p, '/'), $paths);
		$paths = array_filter($paths);
		return implode('/', $paths);
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
			throw new Exception('Could not find view file: "' . $view . '" in "' . implode(',', $this->stack()) . '"');
		}
		
		$layout = $this->layout();
		$scopeProperties = $this->scope()->properties();

		extract($scopeProperties, EXTR_REFS);

		$definedVars = get_defined_vars();

		$include = function(string $view, array $scope = []) use ($definedVars, $scopeProperties): string {
			$use = [];

			foreach ($scope as $k => $var) {
				if (($scope[$k] ?? null) !== ($definedVars[$k] ?? null) && !in_array($k, ['Request', 'definedVars', 'include'], true)) {
					$use[$k] = $var;
				}
			}

			return $this->render($view, $use);
		};

		// @todo: add all the other services
		$Request = $this->tipsy()->request();

		if ($this->_rendering || $display) {
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

		return $page;
	}

	public function display(string $view, ?array $params = null): void {
		echo $this->render($view, $params, true);
	}

	public function filterContent(string $content): string {
		foreach ($this->_filters as $filter) {
			if (is_callable($filter['filter'])) {
				$content = $filter['filter']($content, $filter['arguments']);
			} elseif (is_string($filter['filter']) && class_exists($filter['filter'])) {
				$filterInstance = new $filter['filter']();
				$content = $filterInstance->filter($content, $filter['arguments']);
			} else {
				throw new Exception('Filter class "' . $filter['filter'] . '" does not exist.');
			}
		}
		return $content;
	}

	public function tipsy(): App {
		return $this->_tipsy;
	}

	public function scope(?Scope $scope = null): ?Scope {
		if ($scope !== null) {
			$this->_scope = $scope;
		}
		return $this->_scope;
	}

	public function filter(callable|string $filter, array $arguments = []): self {
		if ($filter) {
			$this->_filters[] = [
				'filter' => $filter,
				'arguments' => $arguments
			];
		}
		return $this;
	}
}
