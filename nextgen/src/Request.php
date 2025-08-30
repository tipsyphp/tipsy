<?php

declare(strict_types=1);

namespace Tipsy;

/**
 * Request handler - manages HTTP requests and data parsing
 * Optimized for PHP 8.3+ with modern content type handling
 */
class Request {
	private array|false $_properties;
	private ?string $_rawRequest = null;
	private ?string $_content = null;
	private ?array $_headers = null;
	private ?string $_path = null;

	public function __construct(array $args = []) {
		$this->_properties = [];
		$this->parseRequestData();
	}

	private function parseRequestData(): void {
		if (!$this->method()) {
			return;
		}

		$contentType = $this->_contentType();
		
		match ($this->method()) {
			'GET' => $this->parseGetRequest($contentType),
			'POST' => $this->parsePostRequest($contentType),
			'PUT', 'DELETE' => $this->parsePutDeleteRequest($contentType),
			default => $this->parseDefaultRequest($contentType)
		};
	}

	private function parseGetRequest(?string $contentType): void {
		$this->_properties = match ($contentType) {
			'application/json' => $this->raw(),
			default => $_GET
		};
	}

	private function parsePostRequest(?string $contentType): void {
		$this->_properties = match ($contentType) {
			'application/json' => json_decode($this->content(), true) ?: [],
			'multipart/form-data' => $_REQUEST,
			default => $_POST
		};
	}

	private function parsePutDeleteRequest(?string $contentType): void {
		match ($contentType) {
			'application/x-www-form-urlencoded' => parse_str($this->content(), $this->_properties),
			'application/json' => $this->parseJsonContent(),
			default => $this->_properties = []
		};
	}

	private function parseDefaultRequest(?string $contentType): void {
		$this->parsePutDeleteRequest($contentType);
	}

	private function parseJsonContent(): void {
		$content = $this->content();
		$request = json_decode($content, true);
		$this->_properties = $request ?: false;
	}

	private function _contentType(): ?string {
		if (!isset($this->_contentType)) {
			$this->_contentType = isset($_SERVER['CONTENT_TYPE']) 
				? explode(';', $_SERVER['CONTENT_TYPE'])[0] 
				: null;
		}
		return $this->_contentType;
	}

	public function base(): ?string {
		return $this->_base ?? null;
	}

	public function loc(int $piece = 0): string {
		$paths = explode('/', $this->path());
		return $paths[$piece] ?? '';
	}

	public function host(): string {
		return 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
	}

	public function url(): string {
		return $this->host() . '/' . $this->path();
	}

	public function path(?string $url = null): string {
		if (!isset($this->_path)) {
			if (!$url) {
				if (isset($_REQUEST['__url'])) {
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

			// Clean up multiple slashes
			while (str_contains($url, '//')) {
				$url = str_replace('//', '/', $url);
			}

			// Clean and normalize the URL
			$url = trim(ltrim(rtrim(trim($url), '/'), '/'));

			$this->_path = $url;
		}

		return $this->_path;
	}

	public function content(): string|false {
		if (!isset($this->_content)) {
			$input = $_ENV['TESTS_PHP_INPUT'] ?? 'php://input';
			$content = file_get_contents($input);
			$this->_content = strlen(trim($content)) === 0 ? false : $content;
		}
		return $this->_content;
	}

	public function raw(): array {
		if (!isset($this->_rawRequest)) {
			$request = trim($_SERVER['REQUEST_URI'] ?? '');
			$queryStart = strpos($request, '?');
			
			if ($queryStart !== false) {
				$request = substr($request, $queryStart + 1);
				$request = urldecode($request);
				$decoded = json_decode($request, true);
				$this->_rawRequest = $decoded ?: [];
			} else {
				$this->_rawRequest = [];
			}
		}
		return $this->_rawRequest;
	}

	public function method(): string {
		return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
	}

	public function headers(): array {
		if (!isset($this->_headers)) {
			$this->_headers = getallheaders() ?: [];
		}
		return $this->_headers;
	}

	public function &__get(string $name): mixed {
		return $this->_properties[$name];
	}

	public function __set(string $name, mixed $value): void {
		$this->_properties[$name] = $value;
	}

	public function &request(): array|false {
		return $this->_properties;
	}
}
