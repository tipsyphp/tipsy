<?php

namespace Tipsy\Http;

class Response {
	private $_status;
	private $_httpStatus;
	private $_errors;
	private $_body;
	private $_header;

	public function __construct($errors, $body, $headers) {
		$this->_body = $body;
		$this->_headers = $headers;
		$this->_errors = $errors;
		$this->_httpStatus = explode(' ', $headers['http_code'])[1];

		if ($this->_errors || $this->_httpStatus{0} == '4' || $this->_httpStatus{0} == '5') {
			$this->_status = false;
		} else {
			$this->_status = true;
		}

		$type = explode(';',$headers['Content-Type'])[0];

		if ($type == 'application/json') {
			$this->_body = json_decode($this->_body);
		}
	}

	public function headers() {
		return $this->_headers;
	}
	public function body() {
		return $this->_body;
	}
	public function complete($fn) {
		$fn($this->body(), $this->headers());
		return $this;
	}
	public function error($fn) {
		if ($this->_status === false) {
			$this->complete($fn);
		}
		return $this;
	}
	public function success($fn) {
		if ($this->_status !== false) {
			$this->complete($fn);
		}
		return $this;
	}
}
