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

	$msg = strtolower($rx_msg);
	$msg = trim($msg);

	if (mb_substr($msg, 0, 2) == 'ok') {
		// Great, transition to name context.
		usr_set_context($usr_id, 'name');
		msg_tx($usr_id, xo('ctx_intro'), $rx_id, "send now");
	} else {
		msg_tx($usr_id, xo('ctx_invite_sorry'), $rx_id, "send now");
	}

	// TODO: inform person who invited
}

function sms_name($usr_id, $rx_msg, $rx_id) {

	// 'name' context is when we're prompting the user for theif first
	// username.

	include(__DIR__ . '/config.php');

	$rsp = usr_set_name($usr_id, $rx_msg, $rx_id);
	if ($rsp['ok']) {

		$name = $rsp['new_name'];

		$rsp = usr_get_by_id($usr_id);
		util_ensure_rsp($rsp);
		$usr = $rsp['usr'];

		if (! empty($usr->invited_by)) {
			// If the user was *invited*, just jump into the chat.
			usr_set_context($usr_id, 'chat');
			$count = xo_chat_count($usr_id);
			$tx_msg = xo('ctx_first_msg', $count, $website_url);

			// Announce that the user has joined the chat
			$announcement = xo('cmd_start_announce', $usr->name);
			msg_admin_tx($usr_id, "[$announcement]", $rx_id);
		} else {
			// Ask to send out the first message.
			$rsp = usr_get_first_msg($usr_id, 'formatted');
			util_ensure_rsp($rsp);
			$first_msg = $rsp['first_msg'];
			$first_msg_held = $rsp['first_msg_held'];

			if ($first_msg_held) {
				// On second thought, the message was held, so
				// no need to ask about it.

				// Just join the chat!
				usr_set_context($usr_id, 'chat');

				// Announce that the user has joined the chat
				$announcement = xo('cmd_start_announce', $usr->name);
				msg_admin_tx($usr_id, "[$announcement]", $rx_id);

				$tx_msg = xo_first_msg_held($usr_id);
			} else {
				usr_set_context($usr_id, 'first_msg');
				$tx_msg = xo('ctx_name', $name, $first_msg);
			}
		}

	} else {
		// Invalid name
		$tx_msg = xo($rsp['xo']);
		$tx_msg .= "\nPlease try again.";
	}

	msg_tx($usr_id, $tx_msg, $rx_id, "send now");
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

	$usr = usr_get("id$usr_id");
	if (! $usr) {
		if (DEBUG) {
			echo "Couldn't find user $usr_id!\n";
		}
		return;
	}

	// Transition into chat
	usr_set_context($usr_id, 'chat');
	
	if (DEBUG) {
		echo "set context to chat\n";
	}

	// Announce that the user has joined the chat
	$announcement = xo('cmd_start_announce', $usr->name);
	msg_admin_tx($usr_id, "[$announcement]", $rx_id);
	
	if (DEBUG) {
		echo "sent announcement\n";
	}

	// First, figure out $count: how many people are in the chat?

	$count = xo_chat_count($usr_id);
	
	if (DEBUG) {
		echo "count: $count\n";
	}

	// Next: send or not send the message? Result is stored in $sent.

	$yes_no = strtolower($rx_msg);
	$yes_no = trim($yes_no);
	$yes_no = mb_substr($yes_no, 0, 1);
	
	if (DEBUG) {
		echo "yes_no: $yes_no\n";
	}

	if ($yes_no == 'y') {

		// Ok, send it!!
		$rsp = usr_get_first_msg($usr_id);
		util_ensure_rsp($rsp);
		$first_msg = $rsp['first_msg'];
		$first_msg_id = $rsp['first_msg_id'];
		$first_msg_held = $rsp['first_msg_held'];

		if ($first_msg_held) {

			// On second thought, the message was held, so I guess
			// we will just bail out now.

			// Instead, join the chat!
			usr_set_context($usr_id, 'chat');

			// Announce that the user has joined the chat
			$announcement = xo('cmd_start_announce', $usr->name);
			msg_admin_tx($usr_id, "[$announcement]", $rx_id);

			$tx_msg = xo_first_msg_held($usr_id);
			msg_tx($usr_id, $tx_msg, $rx_id, "send now");
			return;
		}

		// This is the part where we actually send the message: *whoosh*
		msg_chat($usr_id, $first_msg, $first_msg_id);

		// Yep, "we sent it."
		if ($count == xo('ctx_chat_first')) {
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
	if (DEBUG) {
		echo "sent: $sent\n";
	}
	
	
	// Send the response message
	$tx_msg = xo('ctx_first_msg', "$sent $count", $website_url);
	
	if (DEBUG) {
		echo "$tx_msg\n";
	}
	
	msg_tx($usr_id, $tx_msg, $rx_id, "send now");
	
	// Is this the first user? If so, make 'em the admin.
	if (usr_is_first()) {
		usr_set_status($usr_id, 'admin');
		msg_tx($usr_id, xo('ctx_intro_admin'), $rx_id);
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
