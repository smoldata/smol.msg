<?php

define('OK', 0);
if (! empty($_GET['debug'])) {
	define('DEBUG', true);
} else {
	define('DEBUG', false);
}

//$last_rx = print_r($_POST, true);
//file_put_contents('last-msg.txt', $last_rx);

include 'include/db.php';
include 'include/msg.php';
include 'include/usr.php';

if (! empty($_POST['Body']) &&
    ! empty($_POST['From'])) {
	$usr = usr_get_by_phone($_POST['From']);
	$rx_id = msg_rx($usr, $_POST['Body']);
	$context = usr_get_context($usr);

	if (! msg_command($usr, $rx_id, $_POST['Body'])) {
		if ($context == 'intro') {
			$rsp = "Thanks for your message! To chat with others who replied, choose a name (or chat handle):";
			msg_tx($rx_id, $usr->id, $rsp, "send now");
			usr_set_context($usr, 'name');
		} else if ($context == 'invited') {
			$rsp = "Welcome to the chat! Please choose a name (or chat handle):";
			msg_tx($rx_id, $usr->id, $rsp, "send now");
			usr_set_context($usr, 'name');
		} else if ($context == 'name') {
			$rsp = usr_set_name($usr, $_POST['Body']);
			if ($rsp == OK) {
				$msg = "Welcome to the chat! Reply /stop to leave, or send /help for more commands.";
				msg_tx($rx_id, $usr->id, $msg, "send now");
				usr_set_context($usr, 'chat');
			} else {
				global $usr_name_errors;
				$error = $usr_name_errors[$rsp];
				$msg = "$error Choose again:";
				msg_tx($rx_id, $usr->id, $msg, "send now");
			}
		} else if ($context == 'stopped') {
			$msg = "Oops, you have left the chat. Send /start to rejoin.";
			msg_tx($rx_id, $usr->id, $msg, "send now");
		} else if ($context == 'chat') {
			$active_usrs = usr_get_active($usr);
			if (DEBUG) {
				echo "Active users:\n";
				print_r($active_usrs);
			}
			$msg = msg_signed_format($usr, $_POST['Body']);
			foreach ($active_usrs as $usr_id) {
				msg_tx($rx_id, $usr_id, $msg);
			}
		} else {
			if (DEBUG) {
				echo "User has unknown context: $context\n";
			}
		}
	}
	
	if ($context != 'intro') {
		usr_update_active_time($usr);
	}
}

if (DEBUG) {
	echo "Sending pending messages.\n";
}
msg_send_pending();
