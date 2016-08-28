<?php

include 'include/init.php';

$rsp = array(
	'ok' => 0,
	'error' => 'Hmm. Something strange and unexpected happened.'
);

if (! empty($_POST['msg']) &&
    ! empty($_SESSION['usr_id'])) {
	$rsp = usr_get_by_id($_SESSION['usr_id']);
	if (! $rsp['ok']) {
		return $rsp;
	}

	$usr = $rsp['usr'];
	$rsp = msg_rx($usr->id, $_POST['msg']);
	if (! $rsp['ok']) {
		print_r($rsp);
		die("Error: could not rx {$_POST['msg']}");
	}
	$rx_id = $rsp['insert_id'];
	if (msg_command($usr, $rx_id, $_POST['msg'])) {
		$rsp = array(
			'ok' => 1,
			'command' => 1
		);
	} else {
		$id = msg_chat($usr, $rx_id);
		$channel_msg = htmlentities("$usr->name: {$_POST['msg']}");
		$rsp = array(
			'ok' => 1,
			'id' => $id,
			'msg' => $channel_msg,
			'timestamp' => time()
		);
	}
}

header('Content-Type: application/json');
echo json_encode($rsp);

?>
