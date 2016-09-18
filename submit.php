<?php

include_once(__DIR__ . '/include/init.php');

// Default response: ???
$rsp = array(
	'ok' => 0,
	'error' => 'Hmm. Something strange and unexpected happened.'
);

if (! empty($_POST['msg']) &&
    ! empty($_SESSION['usr_id'])) {
	$rsp = msg_router($_SESSION['usr_id'], $_POST['msg']);
}

header('Content-Type: application/json');
echo json_encode($rsp);
exit;

?>
