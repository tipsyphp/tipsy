<?php

declare(strict_types=1);

namespace Tipsy;

class Request {
	private array $_properties = [];
	private array|false|null $_rawRequest = null;
	private string|false|null $_content = null;
	private ?array $_headers = null;
	private ?string $_path = null;
	private ?string $_base = null;
	private ?string $_contentType = null;
	private ?string $_method = null;

	public function __construct(array $args = []) {
		$method = $this->method();
		if ($method) {
			$contentType = $this->_contentType();
			$this->_properties = match ($method) {
				'GET' => $contentType === 'application/json' ? ($this->raw() ?: []) : ($_GET ?? []),
				'POST' => match ($contentType) {
					'application/json' => json_decode($this->content() ?: '', true) ?: [],
					'multipart/form-data' => $_REQUEST ?? [],
					default => $_POST ?? [],
				},
				default => $this->_parseBodyContent(),
			};

			if (!is_array($this->_properties)) {
				$this->_properties = [];
			}
		}
	}

	private function _parseBodyContent(): array {
		if ($this->_contentType() === 'application/x-www-form-urlencoded') {
			$result = [];
			parse_str($this->content() ?: '', $result);
			return $result;
		}

		if ($this->_contentType() === 'application/json') {
			$content = $this->content();
			if ($content) {
				$request = json_decode($content, true);
				return is_array($request) ? $request : [];
			}
		}

		return [];
	}

	private function _contentType(): ?string {
		if ($this->_contentType === null) {
			$this->_contentType = isset($_SERVER['CONTENT_TYPE'])
				? explode(';', $_SERVER['CONTENT_TYPE'])[0]
				: '';
		}
		return $this->_contentType ?: null;
	}

	public function base(): ?string {
		return $this->_base;
	}

	public function loc(int $piece = 0): string {
		$paths = explode('/', $this->path());
		return $paths[$piece] ?? '';
	}

	public function host(): string {
		$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
	}

	public function url(): string {
		return $this->host() . '/' . $this->path();
	}

	public function path(?string $url = null): string {

		if ($this->_path === null) {
			if (!$url) {
				if (!empty($_REQUEST['__url'])) {
					$url = $_REQUEST['__url'];
				} else {
					$request = explode('?', $_SERVER['REQUEST_URI'] ?? '', 2)[0];

					$script = $_SERVER['SCRIPT_NAME'] ?? '';
					$dir = dirname($script);

					// Strip script file then script directory prefixes without preg
					if ($script !== '' && str_starts_with($request, $script)) {
						$url = substr($request, strlen($script));
					} elseif ($dir !== '' && $dir !== '/' && $dir !== '\\' && str_starts_with($request, $dir)) {
						$url = substr($request, strlen($dir));
					} else {
						$url = $request;
					}

					// dirname('file.php') => '.' — preserve historical "./" base
					$this->_base = str_ends_with($dir, '/') ? $dir : $dir . '/';
				}
			}

			$url = trim(str_replace('//', '/', $url), " \t\n\r\0\x0B/");
			// Second pass covers residual triples like /// → /
			if (str_contains($url, '//')) {
				$url = preg_replace('#/+#', '/', $url) ?? $url;
				$url = trim($url, '/');
			}

			$this->_path = $url;
		}

		return $this->_path;
	}

	public function content(): string|false {
		if ($this->_content === null) {
			$input = $_ENV['TESTS_PHP_INPUT'] ?? 'php://input';
			$raw = file_get_contents($input);
			$this->_content = ($raw !== false && strlen(trim($raw)) > 0) ? $raw : false;
		}
		return $this->_content;
	}

	public function raw(): array|false {
		if ($this->_rawRequest === null) {
			$request = trim($_SERVER['REQUEST_URI'] ?? '');
			$request = substr($request, strpos($request, '?') + 1);
			$request = urldecode($request);
			$decoded = json_decode($request, true);

			$this->_rawRequest = is_array($decoded) ? $decoded : false;
		}
		return $this->_rawRequest ?: false;
	}

	public function method(): string {
		if ($this->_method === null) {
			$this->_method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
		}
		return $this->_method;
	}

	public function &__get(string $name): mixed {
		return $this->_properties[$name];
	}

	public function __set(string $name, mixed $value): void {
		$this->_properties[$name] = $value;
	}

	public function &request(): array {
		return $this->_properties;
	}

	public function headers(): array {
		if ($this->_headers === null) {
			$this->_headers = getallheaders() ?: [];
		}
		return $this->_headers;
	}
}
