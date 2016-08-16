<?php

include 'include/init.php';

$db = db_setup();

if (! empty($_POST['after_id'])) {
	$after_id = intval($_POST['after_id']);
	$query = $db->prepare("
		SELECT id, rx_id, msg, created
		FROM channel
		WHERE id > ?
		ORDER BY created
		LIMIT 100
	");
	$query->execute(array($after_id));
} else {
	$query = $db->query("
		SELECT id, rx_id, msg, created
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

if (! empty($_SESSION['usr_id'])) {
	// Mark the tx messages as delivered, so that they don't get an SMS
	// update for them.
	$msg_ids = array();
	foreach ($msgs as $msg) {
		$msg_ids[] = intval($msg->rx_id);
	}
	$msg_ids = implode(', ', $msg_ids);
	$batch_uuid = util_uuid();
	$query = $db->prepare("
		UPDATE tx
		SET transmit_batch = ?
		WHERE rx_id IN ($msg_ids)
		  AND usr_id = ?
		  AND transmit_batch IS NULL
	");
	$query->execute(array(
		$batch_uuid,
		$_SESSION['usr_id']
	));

	usr_update_active_time($_SESSION['usr_id'], 'web_active');
}

header('Content-Type: application/json');
echo json_encode(array(
	'msgs' => $msgs
));
