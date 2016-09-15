<?php

global $xo_templates;
$xo_templates = array(
	'ctx_intro' =>            "Hello and welcome!\nPlease reply with the username you'd like to use. It doesn't have to be your actual name, get creative! You can change it later if you want.",
	'ctx_name' =>             "Thanks, %s.\nSend your first message out?\n“%s”\nPlease reply Y or N.",
	'ctx_first_msg' =>        "%s %s\nReply /stop to leave, or send /help for more commands. Archives are available at:\n%s",
	'ctx_first_msg_sent' =>   "Message sent!",
	'ctx_first_msg_saved' =>  "Message saved.",
	'ctx_first_msg_drop' =>   "Message NOT sent.",
	'ctx_first_msg_huh' =>    "Huh? Message NOT sent.",
	'ctx_chat_first' =>       "You are the first one in the chat.",
	'ctx_chat_pair' =>        "You are now chatting with 1 other user.",
	'ctx_chat_count' =>       "You are now chatting with %d others.",
	'ctx_stopped' =>          "Hi, welcome back!\nYou will now receive messages again. Reply /stop to leave.",
	'cmd_help' =>             "/stop to leave\n/name [name] to change your name\n/invite [phone] to invite a friend\n/mute [name] to mute someone\n/website for archives URL\n/help [cmd] for more",
	'cmd_help_help' =>        "Learn more about commands: \"/help\" or \"/help [command]\".\nEx: send \"/help name\" to learn more about the \"/name\" command.",
	'cmd_help_stop' =>        'Send "/stop" to leave the chat (or just plain "STOP"). You can rejoin with "/start" later if you want.',
	'cmd_help_start' =>       'Send "/start" to rejoin the chat.',
	'cmd_help_name' =>        'Send "/name susan" to set your username to "susan."',
	'cmd_help_invite' =>      'Send "/invite xxx-xxx-xxxx" to invite somebody by phone number. The dashes between numbers are optional.',
	'cmd_help_mute' =>        'Send "/mute chad" to stop getting messages from Chad.',
	'cmd_help_unmute' =>      'Send "/unmute chad" to stop muting messages from Chad.',
	'cmd_help_about' =>       'Send "/about" for info about the chat, including the archive URL.',
	'cmd_stop' =>             "You have left the chat.\nSend /start to rejoin.",
	'cmd_start' =>            "Hello again, welcome back! %s",
	'cmd_name_changed' =>     "Name changed: you are now known as %s.",
	'cmd_muted' =>            "Mute enabled: you will no longer receive messages from %s.\nSend \"/unmute %s\" to turn the mute off.",
	'cmd_unmuted' =>          "Mute disabled: you will now receive messages from %s.",
	'cmd_about' =>            "There are %d people in the chat. You can read the archives at:\n%s",
	'err_command_unknown' =>  "Hrm, I don't know the \"/%s\" command",
	'err_command_no_help' =>  "There is a \"/%s\" command, but it doesn't have any help info.",
	'err_command_empty_tx' => "Welp, we received your \"/%s\" command, but could not come up with anything useful to send you back.\nSo, instead you are seeing this. :shrug:",
	'err_name_format' =>      'Sorry, please keep your username to 16 letters, numbers, or underscores (no spaces).',
	'err_name_exists' =>      'Oops, somebody else already has that name.',
	'err_user_not_found' =>   "Huh, that user does not exist. Sorry!",
	'err_cannot_mute_self' => "Uhh, sorry you cannot mute yourself!",
	'err_invalid_phone' =>    "Hmm. That phone number doesn't look valid.",
	'err_login_invalid' =>    "Sorry, that login code didn't work. Try again?",
	'err_login_expired' =>    "Oops, that login code has expired. try again?"
);

function xo() {

	global $xo_templates;

	$args = func_get_args();
	$template_id = $args[0];

	if (isset($xo_templates[$template_id])) {
		$args[0] = $xo_templates[$template_id];
		$xo = call_user_func_array('sprintf', $args);
		if (DEBUG && mb_strlen($xo) > 160) {
			echo "[Warning] xo length > 160: $xo\n";
		}
		return $xo;
	} else {
		if (DEBUG) {
			echo "Could not find xo template $template_id.";
		}
		return null;
	}
}

function xo_chat_count($usr_id) {
	$rsp = usr_get_active($usr_id);
	$active = $rsp['active'];
	if (empty($rsp['active'])) {
		$count = xo('ctx_chat_first');
	} else if (count($rsp['active']) == 1) {
		$count = xo('ctx_chat_pair');
	} else {
		$num = count($rsp['active']);
		$count = xo('ctx_chat_count', $num);
	}
	return $count;
}
