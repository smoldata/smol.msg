<?php

include(__DIR__ . '/include/init.php');
include(__DIR__ . '/sms_commands.php');
include(__DIR__ . '/sms_handlers.php');

// At a minimum, we need to know the 'what' and the 'who.' These are provided
// by the Twilio service as POST variables Body & From.
if (empty($_POST['Body']) ||
    empty($_POST['From'])) {
	die("Please use a POST request with 'Body' and 'From' arguments.");
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
$usr_context = $usr->context;

$rx_msg = $_POST['Body'];

// Record the incoming message in the rx database table.
$rsp = msg_rx($usr_id, $rx_msg);
if (! $rsp['ok']) {
	echo "Error: could not rx {$rx_msg} from usr {$usr_id}\n";
	print_r($rsp);
	exit;
}
$rx_id = $rsp['insert_id'];

// Now we know who sent the message ($usr_id) and have made a record of its
// receipt ($rx_id). Next we will:
//   1. Check to see if the message matches known commands (e.g., "/stop")
//   2. Proceed with a handler function, according to the user's context

$cmd = msg_is_command($rx_msg);
if ($cmd) {
	// User is trying to issue a command
	// See: sms_commands.php
	sms_command($usr_id, $rx_msg, $rx_id, $cmd);
} else {
	// Not a command, proceed according to the $usr_context
	// See: sms_handlers.php
	sms_handler($usr_id, $rx_msg, $rx_id, $usr_context);
}

// Update the user's active time
if ($usr_context != 'intro') {
	usr_update_active_time($usr_id);
}
