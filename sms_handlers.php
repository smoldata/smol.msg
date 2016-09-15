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

	$rsp = usr_get_by_id($usr_id);
	if (! $rsp['ok']) {
		if (DEBUG) {
			print_r($rsp);
		}
		exit;
	}

	$usr = $rsp['usr'];

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

	$rsp = usr_set_name($usr_id, $rx_msg, $rx_id);
	if ($rsp['ok']) {
		$name = $rsp['name'];
		usr_set_context($usr_id, 'first_msg');
		$rsp = usr_get_first_msg($usr_id, 'formatted');
		util_ensure_rsp($rsp);
		$first_msg = $rsp['first_msg'];
		$tx_msg = xo('ctx_name', $name, $first_msg);
		msg_tx($usr_id, $tx_msg, $rx_id, "send now");
	} else {
		$tx_msg = xo($rsp['xo']);
		$tx_msg .= "\nPlease try again.";
		msg_tx($usr_id, $tx_msg, $rx_id, "send now");
	}
}

function sms_first_msg($usr_id, $rx_msg, $rx_id) {

	// 'first_msg' context is when the user is deciding whether they want
	// to send out their first message.

	// This is also where we transition into the chat, so we try to offer as
	// much context as possible.

	// For example:
	// Message sent! You are now chatting with 17 others.
	// Reply /stop to leave, or /help for more commands...

	include(__DIR__ . '/config.php');

	// First, figure out $count: how many people are in the chat?

	$rsp = usr_get_active($usr_id);
	$active = $rsp['active'];
	if (empty($rsp['active'])) {
		$count = xo('ctx_first_msg_first');
	} else if (count($rsp['active']) == 1) {
		$count = xo('ctx_first_msg_pair');
	} else {
		$num = count($rsp['active']);
		$count = xo('ctx_first_msg_count', $num);
	}

	// Next: send or not send the message? Result is stored in $sent.

	$yes_no = strtolower($rx_msg);
	$yes_no = trim($yes_no);
	$yes_no = mb_substr($yes_no, 0, 1);

	if ($yes_no == 'y') {

		// Ok, send it!!
		$rsp = usr_get_first_msg_id($usr_id);
		util_ensure_rsp($rsp);
		$first_msg_id = $rsp['first_msg_id'];

		$rsp = usr_get_first_msg($usr_id);
		util_ensure_rsp($rsp);
		$first_msg = $rsp['first_msg'];

		// This is the part where we actually send the message: *whoosh*
		msg_chat($usr_id, $first_msg, $first_msg_id);

		// Yep, "we sent it."
		if (empty($active)) {
			$sent = xo('ctx_first_msg_saved'); // "saved." (archived)
		} else {
			$sent = xo('ctx_first_msg_sent');  // vs. "sent!"
		}

	} else if ($yes_no == 'n') {
		// Nope, "didn't send."
		$sent = xo('ctx_first_msg_drop');
	} else {
		// Huh? "Didn't send."
		$sent = xo('ctx_first_msg_huh');
	}

	// Send the response message
	$tx_msg = xo('ctx_first_msg', $sent, $count, $website_url);
	msg_tx($usr_id, $tx_msg, $rx_id, "send now");

	// Transition into chat
	usr_set_context($usr_id, 'chat');
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
