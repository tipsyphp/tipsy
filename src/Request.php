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

	public function __construct(array $args = []) {
		if ($this->method()) {
			$this->_properties = match ($this->method()) {
				'GET' => match (true) {
					$this->_contentType() === 'application/json' => $this->raw() ?: [],
					default => $_GET ?? [],
				},
				'POST' => match (true) {
					$this->_contentType() === 'application/json' => json_decode($this->content() ?: '', true) ?: [],
					$this->_contentType() === 'multipart/form-data' => $_REQUEST ?? [],
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

					$dir = $_SERVER['SCRIPT_NAME'] ?? '';
					$url = preg_replace('/^' . str_replace('/', '\\/', $dir) . '/', '', $request);

					$dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
					$url = preg_replace('/^' . str_replace('/', '\\/', $dir) . '/', '', $url);

					$url = str_starts_with($url, '/') ? $url : '/' . $url;
					$this->_base = str_ends_with($dir, '/') ? $dir : $dir . '/';
				}
			}

			// Collapse double slashes
			while (str_contains($url, '//')) {
				$url = str_replace('//', '/', $url);
			}

			if (isset($url[0]) && $url[0] === '/') {
				$url = substr($url, 1);
			}
			$url = trim($url);
			$url = ltrim($url, '/');
			$url = rtrim($url, '/');
			$url = trim($url);

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
		return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
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
