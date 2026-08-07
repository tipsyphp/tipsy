<?php

declare(strict_types=1);

namespace Tipsy;

class Http {
	public function __call(string $name, array $args): Http\Response {
		if (is_array($args[0])) {
			$args[0]['method'] = $name;
		} elseif (count($args) === 3) {
			$args[2]['method'] = $name;
		} else {
			$args[2] = ['method' => $name];
		}
		return $this->request(...$args);
	}

	public function request(string|array $urlOrArgs = '', mixed $data = null, array $args = []): Http\Response {
		$url = '';

		if (is_string($urlOrArgs)) {
			$url = $urlOrArgs;
		} elseif (is_array($urlOrArgs)) {
			$args = $urlOrArgs;
		}

		if (!$url && !empty($args['url'])) {
			$url = $args['url'];
		}

		if (!$data && !empty($args['data'])) {
			$data = $args['data'];
		}

		$method = strtolower($args['method'] ?? 'get');
		$dataType = strtolower(($args['type'] ?? '') === 'json' ? 'json' : 'form');

		if ($dataType === 'json' && $method === 'post') {
			$data = json_encode($data);
		} elseif (is_array($data)) {
			$data = http_build_query($data);
		}

		if ($method === 'get' && $data) {
			$ch = curl_init($url . '?' . $data);
		} else {
			$ch = curl_init($url);
		}

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

		if ($method === 'post' && $data) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}

		if ($method === 'post' && $dataType === 'form') {
			curl_setopt($ch, CURLOPT_POST, true);
		} elseif ($method === 'get' && $dataType === 'form') {
			curl_setopt($ch, CURLOPT_HTTPGET, true);
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, !empty($args['skipSslVerify']) ? false : true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, !empty($args['skipSslVerify']) ? 0 : 2);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);

		$headers = [
			'User-Agent: PHP/Tipsy/Http'
		];

		if ($dataType === 'json' && $method === 'post') {
			$headers[] = 'Content-Type: application/json';
			$headers[] = 'Content-Length: ' . strlen((string)$data);
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$body = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);

		$head = '';
		while (is_string($body) && str_starts_with($body, 'HTTP')) {
			$sep = str_contains($body, "\r\n\r\n") ? "\r\n\r\n" : "\n\n";
			[$head, $body] = explode($sep, $body, 2);
		}

		$heads = [];
		foreach (explode("\r\n", $head) as $i => $line) {
			if ($i === 0) {
				$heads['http_code'] = $line;
			} else {
				[$key, $value] = array_pad(explode(': ', $line, 2), 2, '');
				if ($key) {
					$heads[$key] = $value;
				}
			}
		}

		return new Http\Response($error ?: '', $body ?: '', $heads);
	}
}
