<?php

function sms_handler($usr_id, $rx_msg, $rx_id, $usr_context) {

	// Based on the user context, invoke a handler function.

	if ($usr_context == 'handler') {
		die("Bailing out before we hit infinite recursion.");
	}

	$handler_func = "sms_{$usr_context}";

	if (DEBUG) {
		echo "Handler: $handler_func\n";
	}

	if (function_exists($handler_func)) {
		call_user_func($handler_func, $usr_id, $rx_msg, $rx_id);
	} else if (DEBUG) {
		echo "No handler function found for '$usr_context' context.\n";
	}
}

function sms_intro($usr_id, $rx_msg, $rx_id) {

	// 'intro' is the default context for new users, assigned after they've
	// sent their first message.

	$usr = usr_get_by_id($usr_id);

	// Send the first incoming message out to admins for
	// moderation.
	$admin_options = "[/hold {$rx_id} /ban $usr->name]";
	$admin_msg = "$rx_msg\n$admin_options";
	$signed_msg = msg_signed_format($usr, $admin_msg);
	msg_admin_tx($usr_id, $signed_msg, $rx_id);

	// Transition to name context.
	usr_set_context($usr_id, 'name');
	msg_tx($usr_id, xo('ctx_intro'), $rx_id, "send now");
}

function sms_invited($usr_id, $rx_msg, $rx_id) {

	// 'invited' is the status a user gets assigned when their friend sends
	// an "/invite [phone]" command.

	// Transition to name context.
	usr_set_context($usr_id, 'name');
	msg_tx($usr_id, $rx_id, xo('ctx_intro'), "send now");

	// TODO: inform person who invited
}
	
function sms_name($usr_id, $rx_msg, $rx_id) {

	// 'name' context is when we're prompting the user for theif first
	// username.

	include(__DIR__ . '/config.php');

	$rsp = usr_set_name($usr, $rx_msg);
	if ($rsp == OK) {
		usr_set_context($usr_id, 'chat');
		$tx_msg = xo('ctx_name', $website_url);
		msg_tx($usr_id, $tx_msg, $rx_id, "send now");
	} else {
		$tx_msg = xo($rsp);
		$tx_msg .= "\nPlease try again.";
		msg_tx($usr_id, $tx_msg, $rx_id, "send now");
	}
}

function sms_stopped($usr_id, $rx_msg, $rx_id) {

	// 'stopped' is when the user has left the chat with the "/stop"
	// command. We will send their message and transition to 'chat' context.

	msg_tx($usr_id, xo('ctx_stopped'), $rx_id, "send now");
	msg_chat($usr_id, $rx_msg, $rx_id);
}

function sms_chat($usr_id, $rx_msg, $rx_id) {

	// 'chat' is when the user is sending messages to other users.

	msg_chat($usr_id, $rx_msg, $rx_id);
}
