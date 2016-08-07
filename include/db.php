<?php

function db_setup() {
	global $db;
	if (! file_exists('config.php')) {
		die('You forgot config.php!');
	}
	if (! empty($db)) {
		return $db;
	}
	include 'config.php';
	$dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
	$db = new PDO($dsn, $db_user, $db_pass);
	$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
	return $db;
}
