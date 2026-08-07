<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Tipsy\Tipsy;

$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/index.php';

ob_start();
Tipsy::get('/', function () {
	echo '{"ok":true}';
});
Tipsy::start();
ob_end_clean();

echo 'peak_bytes=' . memory_get_peak_usage(true) . PHP_EOL;
echo 'peak_mb=' . round(memory_get_peak_usage(true) / 1024 / 1024, 3) . PHP_EOL;
