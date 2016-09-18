<?php

include 'include/init.php';

$channel = 'main';
if (! empty($_GET['channel'])) {
	$channel = $_GET['channel'];
}

if (! empty($_POST['after_id'])) {
	$after_id = intval($_POST['after_id']);
	$rsp = db_fetch("
		SELECT channel.id AS id,
		       usr.name AS name,
		       channel.msg AS msg,
		       channel.rx_id AS rx_id,
		       channel.created AS created
		FROM channel, usr
		WHERE channel.id > ?
		  AND channel.channel = ?
		  AND channel.usr_id = usr.id
		ORDER BY channel.created
		LIMIT 100
	", array($after_id, $channel));
} else {
	$rsp = db_fetch("
		SELECT channel.id AS id,
		       usr.name AS name,
		       channel.msg AS msg,
		       channel.rx_id AS rx_id,
		       channel.created AS created
		FROM channel, usr
		WHERE channel.channel = ?
		  AND channel.usr_id = usr.id
		ORDER BY channel.created
		LIMIT 100
	", array($channel));
}

if (! $rsp['ok']) {
	exit;
}

$msgs = $rsp['rows'];
$usr_ids = array();

foreach ($msgs as $msg) {
	$msg->id = intval($msg->id);
	$msg->msg = htmlentities($msg->name) . ': ' . htmlentities($msg->msg);
	$msg->timestamp = strtotime($msg->created);
}

if (! empty($_SESSION['usr_id'])) {
	// Mark the tx messages as delivered, so the user doesn't get an SMS
	// update for them.
	$msg_ids = array();
	foreach ($msgs as $msg) {
		$msg_ids[] = intval($msg->rx_id);
	}
	$msg_ids = implode(', ', $msg_ids);
	$batch_uuid = util_uuid();
	$usr_id = intval($_SESSION['usr_id']);
	$where = "
		rx_id IN ($msg_ids)
		AND usr_id = $usr_id
		AND transmit_batch IS NULL
	";
	$rsp = db_update('tx', array(
		'transmit_batch' => $batch_uuid
	), $where);

	usr_update_active_time($_SESSION['usr_id'], 'web_active');
}

header('Content-Type: application/json');
echo json_encode(array(
	'msgs' => $msgs
));
