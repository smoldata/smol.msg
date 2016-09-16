<?php

function sms_command($usr_id, $rx_msg, $rx_id, $cmd) {

	// Call the command handler function
	$cmd_func = "sms_command_{$cmd['id']}";

	if (function_exists($cmd_func)) {
		$tx_msg = call_user_func($cmd_func, $usr_id, $cmd['args'], $rx_id);
	} else {
		$tx_msg = sms_command_unknown();
	}

	// Send the response (there should always be a response)
	if (! empty($tx_msg)) {
		msg_tx($usr_id, $tx_msg, $rx_id, "send now");
	} else {
		msg_tx($usr_id, xo('err_command_empty_tx'), $rx_id, "send now");
	}
}

function sms_command_help($usr_id, $cmd) {

	if (empty($cmd)) {
		// List the commands: /help
		return xo('cmd_help');
	}

	// Learn more about a command: /help [command]
	$help_msg = xo("cmd_help_{$cmd}");
	$cmd_func = "sms_command_{$cmd}";

	if (! empty($help_msg)) {
		// Ok, found the help message!
		return $help_msg;
	} else if (function_exists($cmd_func)) {
		// Hey, this means we forgot to write the help message!
		return xo('err_command_no_help', $cmd);
	} else {
		// Huh?
		return xo('err_command_unknown', $cmd);
	}
}

function sms_command_stop($usr_id, $args = null, $rx_id = null) {

	// Staaaahp, no more chat messages: /stop

	// Note: Twilio has its own built-in "STOP" message, which doesn't get
	// passed along. Which is sort of why command syntax uses a slash
	// prefix, beyond existing chat conventions.

	$usr = usr_get("id$usr_id");
	if ($usr) {
		usr_set_context($usr_id, 'stopped');
		$announcement = xo('cmd_stop_announce', $usr->name);
		msg_admin_tx($usr_id, "[$announcement]", $rx_id);
		return xo('cmd_stop');
	} else {
		return xo('err_command_unknown');
	}
}

function sms_command_start($usr_id, $args = null, $rx_id = null) {

	// Rejoin the chat: /start

	$rsp = usr_get_by_id($usr_id);
	util_ensure_rsp($rsp);
	$usr = $rsp['usr'];

	if ($usr->context == 'invited') {
		usr_set_context($usr_id, 'intro');
		return xo('ctx_intro');
	} else {
		usr_set_context($usr_id, 'chat');
		$count = xo_chat_count($usr_id);
		$announcement = xo('cmd_start_announce', $usr->name);
		msg_admin_tx($usr_id, "[$announcement]", $rx_id);
		return xo('cmd_start', $count);
	}
}

function sms_command_name($usr_id, $new_name = null, $rx_id = null) {

	// Change your name: /name [new name]

	$rsp = usr_set_name($usr_id, $new_name, $rx_id);
	if ($rsp['ok']) {
		$announcement = xo('cmd_name_announce', $rsp['old_name'], $rsp['new_name']);
		msg_admin_tx($usr_id, "[$announcement]", $rx_id);
		return xo('cmd_name_changed', $rsp['new_name']);
	} else {
		return xo($rsp['xo']);
	}
}

function sms_command_mute($usr_id, $mute_name = null, $rx_id = null) {

	// Stop getting messages from a particular user: /mute [name]

	$rsp = usr_set_mute($usr_id, $mute_name, true);
	if ($rsp['ok']) {
		$usr = usr_get($usr_id);
		$announcement = xo('cmd_mute_announce', $usr->name, $mute_name);
		msg_admin_tx($usr_id, "[$announcement]", $rx_id);
		return xo('cmd_muted', $mute_name, $mute_name);
	} else {
		return xo($rsp['xo']);
	}
}

function sms_command_unmute($usr_id, $mute_name = null) {

	// Start getting messages from a particular user: /unmute [name]

	$rsp = usr_set_mute($usr_id, $mute_name, false);
	if ($rsp['ok']) {
		$usr = usr_get($usr_id);
		$announcement = xo('cmd_unmute_announce', $usr->name, $mute_name);
		msg_admin_tx($usr_id, "[$announcement]", $rx_id);
		return xo('cmd_unmuted', $mute_name);
	} else {
		return xo($rsp['xo']);
	}
}

function sms_command_about($usr_id) {

	// Get info about the chat: /about

	include(__DIR__ . '/config.php');

	$active_users = usr_get_active();
	$user_count = count($active_users);

	return xo('cmd_about', $user_count, $website_url);
}

function sms_command_invite($usr_id, $invite_phone, $rx_id) {

	// Invite a friend to join the chat: /invite [phone]

	$rsp = usr_invite($usr_id, $invite_phone);
	if ($rsp['ok']) {
		$inviter = $rsp['inviter'];
		$invited_id = $rsp['invited_id'];
		$invitation = xo('cmd_invite_hello', $inviter->name, $inviter->phone);
		msg_tx($invited_id, $invitation, $rx_id);
		return xo('cmd_invite_sent');
	} else {
		return xo($rsp['xo']);
	}
}

function sms_command_login($usr_id, $login_code) {

	// Login from the website: /login [code]

	include(__DIR__ . '/config.php');



	$rsp = usr_complete_login($usr_id, $login_code);
	if ($rsp == OK) {
		return xo('cmd_login_success', $website_url);
	} else {
		return xo($rsp);
	}
}

function sms_command_hold($usr_id, $msg_id, $rx_id) {

	// TODO: write this /hold [msg id]
}

function sms_command_ban($usr_id, $who, $rx_id) {

	// Teh BAN HAMMER (admin users only)

	if (! usr_is_admin($usr_id)) {
		return xo('err_command_unknown');
	}

	$rsp = usr_set_status($who, 'banned');
	if ($rsp['ok']) {
		$tx_msg = xo('cmd_banned', $rsp['name']);
		msg_admin_tx($usr_id, "[$tx_msg]", $rx_id);
		return $tx_msg;
	} else {
		return xo($rsp['xo']);
	}
}

function sms_command_unban($usr_id, $who, $rx_id) {

	// Unban a user (admin users only)

	if (! usr_is_admin($usr_id)) {
		return xo('err_command_unknown');
	}

	$rsp = usr_set_status($who, 'user');
	if ($rsp['ok']) {
		$tx_msg = xo('cmd_unbanned', $rsp['name']);
		msg_admin_tx($usr_id, "[$tx_msg]", $rx_id);
		return $tx_msg;
	} else {
		return xo($rsp['xo']);
	}
}

function sms_command_admin($usr_id, $who, $rx_id) {

	// Make another user an admin (admin users only)

	if (! usr_is_admin($usr_id)) {
		return xo('err_command_unknown');
	}

	$rsp = usr_set_status($who, 'admin');
	if ($rsp['ok']) {
		$tx_msg = xo('cmd_admin', $rsp['name']);
		msg_admin_tx($usr_id, "[$tx_msg]", $rx_id);
		return $tx_msg;
	} else {
		return xo($rsp['xo']);
	}
}

function sms_command_mod($usr_id, $who, $rx_id) {

	// Make another user a moderator (admin users only)

	if (! usr_is_admin($usr_id)) {
		return xo('err_command_unknown');
	}

	$rsp = usr_set_status($who, 'mod');
	if ($rsp['ok']) {
		$tx_msg = xo('cmd_mod', $rsp['name']);
		msg_admin_tx($usr_id, "[$tx_msg]", $rx_id);
		return $tx_msg;
	} else {
		return xo($rsp['xo']);
	}
}

function sms_command_unknown($usr_id) {
	return xo('err_command_unknown');
}
