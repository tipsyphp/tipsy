<?php

declare(strict_types=1);

namespace Tipsy\Http;

class Response {
	private readonly bool $_status;
	private readonly string $_httpStatus;
	private readonly string $_errors;
	private string|object|null $_body;
	private readonly array $_headers;

	public function __construct(string $errors, string $body, array $headers) {
		$this->_errors = $errors;
		$this->_headers = $headers;
		$this->_httpStatus = explode(' ', $headers['http_code'] ?? '')[1] ?? '';

		$this->_status = !($this->_errors || (isset($this->_httpStatus[0]) && ($this->_httpStatus[0] === '4' || $this->_httpStatus[0] === '5')));

		$type = explode(';', $headers['Content-Type'] ?? '')[0];

		if ($type === 'application/json') {
			$this->_body = json_decode($body);
		} else {
			$this->_body = $body;
		}
	}

	public function headers(): array {
		return $this->_headers;
	}

	public function body(): string|object|null {
		return $this->_body;
	}

	public function complete(callable $fn): static {
		$fn($this->body(), $this->headers());
		return $this;
	}

	public function error(callable $fn): static {
		if ($this->_status === false) {
			$this->complete($fn);
		}
		return $this;
	}

	public function success(callable $fn): static {
		if ($this->_status !== false) {
			$this->complete($fn);
		}
		return $this;
	}
}
