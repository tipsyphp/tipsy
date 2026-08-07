<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Tipsy\Tipsy;

Tipsy::get('/', function () {
	header('Content-Type: application/json');
	echo '{"ok":true}';
});

Tipsy::start();
