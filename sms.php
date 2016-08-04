<?php

if (! file_exists('config.php')) {
	die('You forgot config.php!');
}

include 'config.php';

$db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);

if (empty($_POST['Body']) ||
    empty($_POST['From'])) {
	die('No Body or From in the POST vars.');
}

$phone = $_POST['From'];
$msg = $_POST['Body'];
$now = date('Y-m-d H:i:s');

$query = $db->prepare("
	SELECT *
	FROM usr
	WHERE phone = ?
");
$query->execute(array($phone));
$usr = $query->fetchObject();

if (empty($usr)) {
	$name = substr($phone, -4, 4);
	$query = $db->prepare("
		INSERT INTO usr
		(phone, name, joined)
		VALUES (?, ?, ?)
	");
	$query->execute(array($phone, $name, $now));
	$query = $db->prepare("
		SELECT *
		FROM usr
		WHERE phone = ?
	");
	$query->execute(array($phone));
	$usr = $query->fetchObject();
}

if (empty($usr->context)) {
	$context = 'intro';
	$visible = 0;
} else {
	$visible = 1;
}

$raw = json_encode($_POST, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$query = $db->prepare("
	INSERT INTO rx
	(usr_id, msg, received, raw, visible)
	VALUES (?, ?, ?, ?, ?, ?)
");

$query->execute(array($usr->id, $msg, $now, $raw, $visible, $context));
