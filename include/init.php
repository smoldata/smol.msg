<?php

session_start();

define('OK', 0);
if (! empty($_GET['debug'])) {
	define('DEBUG', true);
} else {
	define('DEBUG', false);
}

include dirname(__DIR__) . '/config.php';
include __DIR__ . '/db.php';
include __DIR__ . '/msg.php';
include __DIR__ . '/usr.php';
include __DIR__ . '/util.php';
