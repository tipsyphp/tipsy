<?php

namespace Tipsy;

class Request {
	private $_properties;
	private $_rawRequest;
	private $_content;

	public function __construct($args = []) {
		$this->_properties = [];

		if ($args['tipsy']) {
			$this->_tipsy = $args['tipsy'];
		}

		if ($this->method()) {
			switch ($this->method()) {
				case 'PUT':
				case 'DELETE':
					if ($this->_contentType() === 'application/x-www-form-urlencoded') {
						parse_str($this->_getContent(), $this->_properties);

					} elseif ($this->_contentType() === 'application/json') {
						$content = $this->_getContent();
						$request = json_decode($content,'array');
						if (!$request) {
							$this->_properties = false;
						} else {
							$this->_properties = $request;
						}
					}
					break;

				case 'GET':
					if ($this->_contentType() === 'application/x-www-form-urlencoded' || !$this->_contentType()) {
						$this->_properties = $_GET;
					} elseif ($this->_contentType() === 'application/json') {
						$this->_properties = $this->_getRawRequest();
					}
					break;

				case 'POST':
					if ($this->_contentType() === 'application/json') {
						$this->_properties = json_decode($this->_getContent(), 'array');
						// } elseif ($_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded') {
					} else  {
						$this->_properties = $_POST;
					}
					break;
			}
		}

	}

	private function _contentType() {
		if (!isset($this->_contentType)) {
			$this->_contentType = explode(';',$_SERVER['CONTENT_TYPE'])[0];
		}
		return $this->_contentType;
	}

	public function base() {
		return $this->_base;
	}

	public function loc($piece = 0) {
		$paths = explode('/', $this->path());
		return $paths[$piece];
	}

	public function host() {
		return 'http://'.$_SERVER['HTTP_HOST'];
	}

	public function url() {
		return $this->host().$_SERVER['REQUEST_URI'];
	}

	public function path($url = null) {

		if (!isset($this->_path)) {
			if (!$url) {
				if ($_REQUEST['__url']) {
					$url = $_REQUEST['__url'];
				} else {
					$request = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
					$dir = dirname($_SERVER['SCRIPT_NAME']);
					$this->_base = substr($dir, -1) == '/' ? $dir : $dir.'/';
					$url = preg_replace('/^'.str_replace('/','\\/',''.$dir).'/','',$request);
					$url = substr($url, 0, 1) == '/' ? $url : '/'.$url;
				}
			}

			while (strpos($url, '//') !== false) {
				$url = str_replace('//', '/', $url);
			}

			if ($url{0} == '/') {
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

	private function _getContent() {
		if (!isset($this->_content)) {
			if (strlen(trim($this->_content = file_get_contents('php://input'))) === 0) {
				$this->_content = false;
			}
		}
		return $this->_content;
	}

	private function _getRawRequest() {
		if (!isset($this->_rawRequest)) {

			$request = trim($_SERVER['REQUEST_URI']);
			$request = substr($request,strpos($request,'?')+1);
			$request = urldecode($request);
			$request = json_decode($request,'array');

			if (!$request) {
				$this->_rawRequest = false;
			} else {
				$this->_rawRequest = $request;
			}
		}
		return $this->_rawRequest;
	}

	public function method() {
		return strtoupper($_SERVER['REQUEST_METHOD']);
	}

	public function &__get($name) {
		return $this->_properties[$name];
	}

	public function __set($name, $value) {
		return $this->_properties[$name] = $value;
	}

	public function &request() {
		return $this->_properties;
	}

	public function headers() {
		if (!isset($this->_headers)) {
			$this->_headers = getallheaders();
		}
		return $this->_headers;
	}
}