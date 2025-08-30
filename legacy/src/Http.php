<?php

namespace Tipsy;

class Http {
	public function __call($name, $args) {
		if (is_array($args[0])) {
			$args[0]['method'] = $name;
		} elseif (count($args) == 3) {
			$args[2]['method'] = $name;
		} else {
			$args[2] = ['method' => $name];
		}
		return call_user_func_array([$this, 'request'], $args);
	}

	public function request() {
		$fn = func_get_args();

		if (is_string($fn[0])) {
			$url = $fn[0];
		} elseif (is_array($fn[0])) {
			$args = $fn[0];
		}
		if (count($fn) >= 2) {
			$data = $fn[1];
		}
		if (count($fn) == 3) {
			$args = $fn[2];
		}

		if (!$url && $args['url']) {
			$url = $args['url'];
		}

		if (!$data && $args['data']) {
			$data = $args['data'];
		}

		$method = strtolower($args['method'] ? $args['method'] : 'get');
		$dataType = strtolower($args['type'] == 'json' ? 'json' : 'form');

		if ($dataType == 'json' && $method == 'post') {
			$data = json_encode($data);
		} elseif (is_array($data)) {
			if (is_array($data)) {
				$data = http_build_query($data);
			}
		}

		if ($method == 'get') {
			$ch = curl_init($url.'?'.$data);
		} else {
			$ch = curl_init($url);
		}

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

		if ($method == 'post') {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}

		if ($method == 'post' && $dataType == 'form') {
			curl_setopt($ch, CURLOPT_POST, true);
		} elseif ($method == 'get' && $dataType == 'form') {
			curl_setopt($ch, CURLOPT_HTTPGET, true);
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);

		$headers = [
			'User-Agent: PHP/Tipsy/Http'
		];

		if ($dataType == 'json' && $method == 'post') {
			$headers[] = 'Content-Type: application/json';
			$headers[] = 'Content-Length: ' . strlen($data);
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$body = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);

		while (strpos($body, 'HTTP') === 0) {
			$sep = strpos($body, "\r\n\r\n") === false ? "\n\n" : "\r\n\r\n";
			list($head, $body) = explode($sep, $body, 2);
		}

		$heads = [];
		foreach (explode("\r\n", $head) as $i => $line) {
			if ($i === 0) {
				$heads['http_code'] = $line;
			} else {
				list ($key, $value) = explode(': ', $line);
			}
			if ($key) {
				$heads[$key] = $value;
			}
        }

		return new Http\Response($error, $body, $heads);
	}

	/** dont this this is needed anymore
	private function _parse($headers) {
		$ret = [];
		foreach (explode("\n",$headers) as $header) {
			if (preg_match('/HTTP\//i',$header)) {
				$header = explode(' ',$header);
				$this->headers[$header[0]] = $header[1];
			} else {
				$header = explode(':',$header, 2);
				$this->headers[$header[0]] = $header[1];
			}
		}
	}
	**/
}
