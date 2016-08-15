<?php

include 'include/init.php';

if (! empty($_POST['Body']) &&
    ! empty($_POST['From'])) {
	$usr = usr_get_by_phone($_POST['From']);
	if (! $usr) {
		die("Error: could not find a usr for {$_POST['From']}");
	}
	$rx_id = msg_rx($usr, $_POST['Body']);
	if (! $rx_id) {
		die("Error: could not rx {$_POST['Body']}");
	}
	$context = usr_get_context($usr);

	if (! msg_command($usr, $rx_id, $_POST['Body'])) {
		if ($context == 'intro') {
			$msg = msg_signed_format($usr, "{$_POST['Body']}\n[/approve {$rx_id}]");
			$admin_count = msg_admin_tx($rx_id, $usr, $msg);
			usr_set_context($usr, 'name');
			if ($admin_count == 0) {
				usr_set_status($usr, 'admin');
				$rsp = "Hi, you are the first one here. Choose a name (or chat handle):";
				msg_tx($rx_id, $usr->id, $rsp, "send now");
			} else {
				$rsp = "Thanks for your message! To chat with others who replied, choose a name (or chat handle):";
				msg_tx($rx_id, $usr->id, $rsp, "send now");
			}
		} else if ($context == 'invited') {
			$rsp = "Welcome to the chat! Please choose a name (or chat handle):";
			msg_tx($rx_id, $usr->id, $rsp, "send now");
			usr_set_context($usr, 'name');
		} else if ($context == 'name') {
			$rsp = usr_set_name($usr, $rx_id, $_POST['Body']);
			if ($rsp == OK) {
				$msg = "Reply /stop to leave, or /help for more commands. Welcome, $usr->name!";
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
			msg_chat($usr, $rx_id);
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
