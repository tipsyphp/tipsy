<?php

namespace Tipsy;

class Request {
	private $_properties;
	private $_rawRequest;
	private $_content;
	private $_headers;
	private $_path;

	public function __construct($args = []) {
		$this->_properties = [];

		/** dont think this is needed, but leaving here just in case
		if ($args['tipsy']) {
			$this->_tipsy = $args['tipsy'];
		}
		**/

		if ($this->method()) {
			switch ($this->method()) {
				case 'GET':
					if ($this->_contentType() === 'application/x-www-form-urlencoded' || !$this->_contentType()) {
						$this->_properties = $_GET;
					} elseif ($this->_contentType() === 'application/json') {
						$this->_properties = $this->raw();
					}
					break;

				case 'POST':
					if ($this->_contentType() === 'application/json') {
						$this->_properties = json_decode($this->content(), 'array');
					} elseif ($this->_contentType() === 'multipart/form-data') {
						$this->_properties = $_REQUEST;
					} else  {
						$this->_properties = $_POST;
					}
					break;

				case 'PUT':
				case 'DELETE':
				default:
					if ($this->_contentType() === 'application/x-www-form-urlencoded') {
						parse_str($this->content(), $this->_properties);

					} elseif ($this->_contentType() === 'application/json') {
						$content = $this->content();
						$request = json_decode($content,'array');
						if (!$request) {
							$this->_properties = false;
						} else {
							$this->_properties = $request;
						}
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
		return $this->host().'/'.$this->path();
	}

	public function path($url = null) {

		if (!isset($this->_path)) {
			if (!$url) {
				if ($_REQUEST['__url']) {
					$url = $_REQUEST['__url'];
				} else {

					$request = explode('?', $_SERVER['REQUEST_URI'], 2)[0];

					$dir = $_SERVER['SCRIPT_NAME'];
					$url = preg_replace('/^'.str_replace('/','\\/',''.$dir).'/','',$request);

					$dir = dirname($_SERVER['SCRIPT_NAME']);
					$url = preg_replace('/^'.str_replace('/','\\/',''.$dir).'/','',$url);

					$url = substr($url, 0, 1) == '/' ? $url : '/'.$url;
					$this->_base = substr($dir, -1) == '/' ? $dir : $dir.'/';
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

	public function content() {
		if (!isset($this->_content)) {
			if (strlen(trim($this->_content = file_get_contents(!is_null($_ENV['TESTS_PHP_INPUT']) ? $_ENV['TESTS_PHP_INPUT'] : 'php://input'))) === 0) {
				$this->_content = false;
			}
		}
		return $this->_content;
	}

	public function raw() {
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
