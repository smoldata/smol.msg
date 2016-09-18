<?php

global $xo_templates;
$xo_templates = array(
	'ctx_intro' =>            "Welcome!\nPlease reply with the username you'd like to use. It doesn't have to be your actual name, get creative! You can change it later if you want.",
	'ctx_intro_admin' =>      "You are the first admin user! Parts [in brackets] are admin-only messages. Use /makeadmin or /makemod to promote someone else.",
	'ctx_name' =>             "Thanks, %s.\nBy proceeding, you agree to the Code of Conduct, posted at %s.\nReply \"ok\" to proceed.",
	'ctx_coc_nope' =>         "Sorry, you have to agree to the Code of Conduct (%s) before proceeding.\nReply \"ok\" to agree.",
	'ctx_coc_mod' =>          "Reply \"/mod\" if you ever need help from a moderator.",
	'ctx_coc_first_msg' =>    "%s\nSend out your first message?\n“%s”\nPlease reply Y or N.",
	'ctx_first_msg' =>        "%s\nReply /stop to leave, or send /help for more commands. Archives are available at:\n%s",
	'ctx_first_msg_sent' =>   "Message sent!",
	'ctx_first_msg_saved' =>  "Message saved.",
	'ctx_first_msg_drop' =>   "Message NOT sent.",
	'ctx_first_msg_held' =>   "Your first message was held for moderation.",
	'ctx_first_msg_huh' =>    "Huh? Message NOT sent.",
	'ctx_chat_first' =>       "You are the only one in the chat so far.",
	'ctx_chat_pair' =>        "You are now chatting with 1 other user.",
	'ctx_chat_count' =>       "You are now chatting with %d others.",
	'ctx_stopped' =>          "Hi, welcome back!\nYou will now receive messages again. Reply /stop to leave.",
	'ctx_invite_sorry' =>     "Sorry to bother you! If you change your mind, send /start to join the chat.",
	'cmd_help' =>             "/stop to leave\n/name [name] to change your name\n/invite [phone] to invite a friend\n/mute [name] to mute someone\n/about for chat info\n/help [cmd] for more",
	'cmd_help_help' =>        "Learn more about commands: \"/help\" or \"/help [command]\".\nEx: send \"/help name\" to learn more about the \"/name\" command.",
	'cmd_help_stop' =>        'Send "/stop" to leave the chat (or just plain "STOP"). You can rejoin with "/start" later if you want.',
	'cmd_help_start' =>       'Send "/start" to rejoin the chat.',
	'cmd_help_name' =>        'Send "/name susan" to set your username to "susan."',
	'cmd_help_invite' =>      'Send "/invite xxx-xxx-xxxx" to invite somebody by phone number. The dashes between the numbers are optional.',
	'cmd_help_mute' =>        'Send "/mute chad" to stop getting messages from user "chad."',
	'cmd_help_unmute' =>      'Send "/unmute chad" to resume receiving messages from user "chad."',
	'cmd_help_about' =>       'Send "/about" for info about the chat, including the archive URL.',
	'cmd_stop' =>             "You have left the chat.\nSend /start to rejoin.",
	'cmd_stop_announce' =>    "User %s has left the chat.",
	'cmd_start' =>            "Hello again, welcome back! %s",
	'cmd_start_announce' =>   "User %s has joined the chat.",
	'cmd_name_changed' =>     "Name changed: you are now known as %s.",
	'cmd_name_announce' =>    "User %s is now known as %s.",
	'cmd_muted' =>            "Mute enabled: you will no longer receive messages from %s.\nSend \"/unmute %s\" to turn the mute off.",
	'cmd_unmuted' =>          "Mute disabled: you will now receive messages from %s.",
	'cmd_mute_announce' =>    "User %s has muted %s.",
	'cmd_unmute_announce' =>  "User %s has UNmuted %s.",
	'cmd_about' =>            "There are %d people in the chat. You can read the archives at:\n%s",
	'cmd_invite_hello' =>     "Hello! Someone with the name %s (%s) has invited you to an SMS chat.\nReply \"ok\" to join. (Or just ignore this.)",
	'cmd_invite_sent' =>      "Your invitation has been sent!",
	'cmd_banned' =>           "User %s has been banned.",
	'cmd_unbanned' =>         "User %s has been UNbanned.",
	'cmd_makeadmin' =>        "User %s is now an admin.",
	'cmd_makemod' =>          "User %s is now a moderator.",
	'cmd_hold_created' =>     "Message %d has been held.\n Use \"/approve %d\" to send it.",
	'cmd_hold_exists' =>      "Someone beat you to it. Message %d is already held.\nUse \"/approve %d\" to send it.",
	'cmd_hold_announce' =>    "User %s has held message %d.\nUse \"/approve %d\" to send it.",
	'cmd_login_success' =>    "You are now logged into %s. You can also post replies via SMS if you'd like.",
	'cmd_channel' =>          "You are now in the %s channel. %s\nReply /leave to return to the main chat.",
	'cmd_channel_announce' => "New channel: %s created by %s.",
	'cmd_mod_confirm' =>      "Request help from a moderator?\nReply \"ok\" to proceed. (Note: your phone number will be visible to the moderator).",
	'cmd_mod_again' =>        "Sorry if you haven't heard from a moderator yet. Somebody should be in touch ASAP.",
	'cmd_mod_cancel' =>       "Request cancelled. If you do need help, don't hesitate to get in touch via hello@smalldata.coop.\nResuming chat, send /stop to leave.",
	'cmd_mod_sent' =>         "Ok, you should hear from a moderator soon. She or he will contact you directly via SMS.\nReply /start to resume chatting.",
	'cmd_mod_request' =>      "User %s has requested assistance from a moderator.\nReply /mod %s if you can help.",
	'cmd_mod_announce' =>     "Moderator %s is helping %s.",
	'cmd_mod_details' =>      "Thanks for taking this.\Please SMS with %s directly: %s.",
	'err_command_unknown' =>  "Hrm, I don't know the \"/%s\" command",
	'err_command_no_help' =>  "There is a \"/%s\" command, but it doesn't have any help info.",
	'err_command_empty_tx' => "Welp, we received your \"/%s\" command, but could not come up with anything useful to send you back.\nSo, instead you are seeing this. :shrug:",
	'err_name_format' =>      'Sorry, please keep your username to 16 letters, numbers, or underscores (no spaces).',
	'err_name_empty' =>       'Sorry, your name cannot be empty!',
	'err_name_exists' =>      'Oops, somebody else already has that name.',
	'err_name_unchanged' =>   'Oops, that is already your current name!',
	'err_user_not_found' =>   "Huh, that user does not exist. Sorry!",
	'err_cannot_mute_self' => "Uhh, sorry you cannot mute yourself!",
	'err_invalid_phone' =>    "Hmm. That phone number doesn't look valid.",
	'err_login_invalid' =>    "Sorry, that login code didn't work. Try again?",
	'err_login_expired' =>    "Oops, that login code has expired. Try again?",
	'err_channel_format' =>   'Sorry, channel names must be limited to 16 letters, numbers, or underscores (no spaces).',
	'err_msg_unknown' =>      "Huh, message with rx ID %d does not exist.",
	'err_db' =>               "Oh no! There was a database-related error."
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
			echo "Could not find xo template '$template_id'.";
		}
		return null;
	}
}

function xo_chat_count($usr_id, $channel = 'main') {
	$rsp = usr_get_active($usr_id, $channel);
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

function xo_first_msg_held($usr_id) {

	include(__DIR__ . '/config.php');

	$first_msg = xo('ctx_first_msg_held');
	$count = xo_chat_count($usr_id);
	$tx_msg = xo('ctx_first_msg', "$first_msg $count", $website_url);

	return $xo;
}
