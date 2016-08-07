<?php

include 'config.php';
include 'include/db.php';

$db = db_setup();

if (! empty($_POST['after_id'])) {
	$after_id = intval($_POST['after_id']);
	$query = $db->prepare("
		SELECT id, msg, created
		FROM channel
		WHERE id > ?
		ORDER BY created
		LIMIT 100
	");
	$query->execute(array($after_id));
} else {
	$query = $db->query("
		SELECT id, msg, created
		FROM channel
		ORDER BY created
		LIMIT 100
	");
}

$msgs = $query->fetchAll();
foreach ($msgs as $msg) {
	$msg->id = intval($msg->id);
	$msg->msg = htmlentities($msg->msg);
	$msg->timestamp = strtotime($msg->created);
}

header('Content-Type: application/json');
echo json_encode(array(
	'msgs' => $msgs
));
