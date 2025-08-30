<?php

require_once __DIR__ . '/../vendor/autoload.php';

$hello = new Tipsy\Tipsy;

$hello->router()
	->home(function() {
		echo 'Hello world! - Tipsy Framework NextGen (PHP 8.3+ Modernized)';
	})
	->get('about', function() {
		echo 'About Tipsy NextGen: Modernized PHP 8.3+ version of the minimalist framework originally from 2014-2015. Features modern PHP syntax, strict typing, and performance optimizations while maintaining the original API.';
	})
	->get('status', function() {
		echo json_encode([
			'status' => 'operational',
			'framework' => 'Tipsy NextGen',
			'version' => 'v2.0-modernized',
			'php_version' => phpversion(),
			'timestamp' => date('Y-m-d H:i:s'),
			'memory_usage' => memory_get_usage(true),
			'features' => [
				'strict_typing',
				'modern_php_syntax',
				'opcache_optimized',
				'performance_enhanced'
			]
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
<head><title>Tipsy Framework NextGen Test</title></head>
<body>
	<h1>Tipsy Framework NextGen Routes Test</h1>
	<p><strong>Version:</strong> NextGen (PHP 8.3+ Modernized)</p>
	<p><strong>PHP Version:</strong> ' . phpversion() . '</p>
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