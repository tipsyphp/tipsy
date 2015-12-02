<?php

namespace Tipsy\Http;

class Response {
	private $_status;
	private $_body;
	private $_header;

	public function __construct($status, $body, $headers) {
		$this->_body = $body;
		$this->_headers = $headers;
		$this->_status = $status;
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
	}
	public function error($fn) {
		if (!$this->_status) {
			$this->complete($fn);
		}
	}
	public function success($fn) {
		if ($this->_status) {
			$this->complete($fn);
		}
	}
}
