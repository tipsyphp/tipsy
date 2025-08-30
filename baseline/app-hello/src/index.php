<?php

require_once __DIR__ . '/../vendor/autoload.php';

$hello = new Tipsy\Tipsy;

$hello->router()
	->home(function() {
		echo 'Hello world! - Tipsy Framework 2014-2015 Baseline';
	})
	->get('about', function() {
		echo 'About Tipsy: A minimalist PHP framework from 2014-2015';
	})
	->get('status', function() {
		echo json_encode([
			'status' => 'operational',
			'framework' => 'Tipsy',
			'php_version' => phpversion(),
			'timestamp' => date('Y-m-d H:i:s'),
			'memory_usage' => memory_get_usage(true)
		]);
	})
	->get('test/params', function() {
		echo 'GET parameters: ' . print_r($_GET, true);
	})
	->get('user/:id', function($Params) {
		echo "User ID: " . $Params->id;
	})
	->get('user/:id/profile', function($Params) {
		echo "Profile for user ID: " . $Params->id;
	})
	->post('echo', function() {
		echo 'POST data received: ' . file_get_contents('php://input');
	})
	->get('html', function() {
		echo '<!DOCTYPE html>
<html>
<head><title>Tipsy Framework Test</title></head>
<body>
	<h1>Tipsy Framework Routes Test</h1>
	<ul>
		<li><a href="/">Home</a></li>
		<li><a href="/about">About</a></li>
		<li><a href="/status">Status (JSON)</a></li>
		<li><a href="/test/params?foo=bar&baz=qux">Test Parameters</a></li>
		<li><a href="/user/123">User 123</a></li>
		<li><a href="/user/456/profile">User 456 Profile</a></li>
		<li><a href="/html">This Page</a></li>
	</ul>
</body>
</html>';
	})
	->otherwise(function() {
		echo '404 - Not Found';
	});

$hello->run();