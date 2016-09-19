<?php

include_once(__DIR__ . '/include/init.php');

// At a minimum, we need to know the 'what' and the 'who.' These are provided
// by the Twilio service as POST variables Body & From.
if (empty($_POST['Body']) ||
    empty($_POST['From'])) {
	die("Please use a POST request with 'Body' and 'From' arguments.");
}

if (empty($_POST['AccountSid']) ||
    $_POST['AccountSid'] != $twilio_account_sid) {
	die("Oops, your Twilio AccountSid argument didn't match what we were expecting.");
}

// Load an existing $usr object for the phone number, or create one
// if it doesn't exist yet.
$rsp = usr_get_by_phone($_POST['From']);
if (! $rsp['ok']) {
	echo "Error: could not load user for {$_POST['From']}\n";
	print_r($rsp);
	exit;
}
$usr = $rsp['usr'];
$usr_id = $usr->id;
$rx_msg = $_POST['Body'];

$rsp = msg_router($usr_id, $rx_msg);
if (! $rsp['ok']) {
	if (DEBUG) {
		print_r($rsp);
	}
	exit;
}

if (! empty($rsp['tx_msg'])) {
	// Send a message, if there's a response to send
	msg_tx($usr_id, $rsp['tx_msg'], $rsp['rx_id'], "send now");
}

// Update the user's active time
if ($usr->context != 'intro') {
	usr_update_active_time($usr_id);
}
