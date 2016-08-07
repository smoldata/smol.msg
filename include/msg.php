<?php

define('MSG_MUTED_STATUS', -1);

function msg_rx($usr, $msg) {
	$db = db_setup();
	$received = date('Y-m-d H:i:s');
	$post_json = json_encode($_POST, JSON_PRETTY_PRINT);
	$query = $db->prepare("
		INSERT INTO rx
		(usr_id, msg, received, post_json)
		VALUES (?, ?, ?, ?)
	");
	$query->execute(array(
		$usr->id,
		$msg,
		$received,
		$post_json
	));
	$error_code = $query->errorCode();
	if ($error_code != '00000') {
		if (DEBUG) {
			echo "Error rx $error_code:\n";
			print_r($query->errorInfo());
		}
		return null;
	}
	return $db->lastInsertId();
}

function msg_tx($rx_id, $usr_id, $msg, $send_now = false) {
	
	if (is_array($msg)) {
		foreach ($msg as $msg_part) {
			msg_tx($rx_id, $usr_id, $msg_part, $send_now);
		}
		return;
	}
	
	$db = db_setup();
	$queued = date('Y-m-d H:i:s');
	$query = $db->prepare("
		INSERT INTO tx
		(rx_id, usr_id, msg, queued)
		VALUES (?, ?, ?, ?)
	");
	$query->execute(array(
		$rx_id,
		$usr_id,
		$msg,
		$queued
	));
	$tx_id = $db->lastInsertId();
	if (! empty($send_now)) {
		msg_send_sms($tx_id);
	}
	return $tx_id;
}

function msg_send_pending() {
	$db = db_setup();
	$query = $db->query("
		SELECT tx.id AS id,
		       tx.usr_id AS usr_id,
		       rx.usr_id AS sender_id,
		       usr.phone AS phone,
		       tx.msg AS msg
		FROM tx, rx, usr
		WHERE tx.transmitted IS NULL
		  AND tx.rx_id = rx.id
		  AND tx.usr_id = usr.id
		ORDER BY tx.queued
	");
	while ($tx = $query->fetchObject()) {
		msg_send_sms($tx);
	}
}

function msg_send_sms($tx) {
	include 'config.php';

	global $msg_last_send;

	if (! empty($msg_last_send)) {
		$time_since_last_send = microtime(true) - $msg_last_send;
		if ($time_since_last_send < 1000) {
			$sleep_time = 1000 - $time_since_last_send;
			if (DEBUG) {
				echo "Waiting {$sleep_time}ms to avoid rate limit...\n";
			}
			usleep($sleep_time);
		}
	}

	$db = db_setup();
	
	if (is_numeric($tx)) {
		$query = $db->prepare("
			SELECT tx.id AS id,
			       tx.usr_id AS usr_id,
			       rx.usr_id AS sender_id,
			       usr.phone AS phone,
			       tx.msg AS msg
			FROM tx, rx, usr
			WHERE tx.id = ?
			  AND tx.rx_id = rx.id
			  AND tx.usr_id = usr.id
		");
		$query->execute(array(
			$tx
		));
		$tx = $query->fetchObject();
	} else if (empty($tx->id)) {
		if (DEBUG) {
			echo "Cannot send SMS, no tx record.";
		}
		return false;
	}

	if (usr_check_if_muted($tx)) {
		$transmitted = date('Y-m-d H:i:s');
		$query = $db->prepare("
			UPDATE tx
			SET ok = ?,
			    transmitted = ?
			WHERE id = ?
		");
		$query->execute(array(
			MSG_MUTED_STATUS,
			$transmitted,
			$tx->id
		));
		return false;
	}

	$post = array();
	$post['Body'] = $tx->msg;
	$post['MessagingServiceSid'] = $twilio_messaging_service_sid;
	$post['To'] = $tx->phone;
	$post_str = http_build_query($post);
	$post_json = json_encode($post);
	$response = array();

	if (DEBUG) {
		echo "Sending SMS to $tx->phone...";
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.twilio.com/2010-04-01/Accounts/$twilio_account_sid/Messages");
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_str);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, "$twilio_account_sid:$twilio_auth_token");

	$msg_last_send = microtime(true);
	$response['rsp'] = curl_exec($ch);

	if (curl_errno($ch)) {
		$ok = 0;
		$response['error'] = curl_errno($ch) . ': ' . curl_error($ch);
	} else {
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$ok = ($http_code == 201) ? 1 : 0;
		$response['info'] = curl_getinfo($ch);
	}
	
	if (DEBUG) {
		if ($ok) {
			echo " ok\n";
		} else {
			echo " fail\n";
		}
	}

	curl_close($ch);

	$response_json = json_encode($response);
	$transmitted = date('Y-m-d H:i:s');

	$query = $db->prepare("
		UPDATE tx
		SET ok = ?,
		    post_json = ?,
		    response_json = ?,
		    transmitted = ?
		WHERE id = ?
	");
	$query->execute(array(
		$ok,
		$post_json,
		$response_json,
		$transmitted,
		$tx->id
	));

	if (DEBUG) {
		echo "tx object:\n";
		print_r($tx);
		echo "POST data:\n";
		print_r($post);
		echo "Response:\n";
		print_r($response);
	}

	return $tx->id;
}

function msg_signed_format($usr, $msg) {
	if (mb_strlen("$usr->name: $msg") <= 160) {
		return "$usr->name: $msg";
	}
	$msg_parts = array();
	$words = mb_split(' ', $msg);
	$part = "$usr->name:";
	foreach ($words as $word) {
		if (mb_strlen("$part $word") > 159) {
			$msg_parts[] = $part . '…';
			$part = "$usr->name: …";
		}
		$part .= " $word";
	}
	$msg_parts[] = $part;
	return $msg_parts;
}

function msg_command($usr, $rx_id, $cmd) {
	
	include 'config.php';
	
	$cmd = strtolower($cmd);
	$cmd = trim($cmd);
	if (substr($cmd, 0, 1) != '/') {
		return false;
	}
	$cmd = substr($cmd, 1);

	if ($cmd == 'help') {
		$msg = "/stop to leave\n/name [name] to change your name\n/invite [phone] to invite a friend\n/mute [name] to mute someone\n/website for archives URL\n/help [cmd] for more";
	} else if (preg_match('/^help (stop|start|name|mute|admin|website|help)$/', $cmd, $matches)) {
		$help_msgs = array(
			'stop' => 'Send "/stop" to leave the chat. You can rejoin with /start at any time.',
			'start' => 'Send "/start" to rejoin the chat.',
			'name' => 'Send "/name susan" to set your name to "susan."',
			'invite' => 'Send "/invite 7185551212" to invite a friend by phone number.',
			'mute' => 'Send "/mute chad" to stop getting messages from Chad.',
			'unmute' => 'Send "/unmute chad" to stop muting messages from Chad.',
			'website' => 'Send "/website" if you want to get the chat archive URL.',
			'help' => 'Send "/help website" to learn more about the "/website" command.'
		);
		$help_cmd = $matches[1];
		$msg = $help_msgs[$help_cmd];
	} else if ($cmd == 'stop') {
		usr_set_context($usr, 'stopped');
		$msg = "You have left the chat. Send /start to rejoin.";
	} else if ($cmd == 'start' ||
	           $cmd == 'join') {
		$msg = 'You have rejoined the chat. Welcome back!';
		usr_set_context($usr, 'chat');
	} else if (preg_match('/^name (.+)$/', $cmd, $matches)) {
		$rsp = usr_set_name($usr, $matches[1]);
		if ($rsp == OK) {
			$msg = "From now on you will be known as {$usr->name}.";
		} else {
			global $usr_errors;
			$msg = $usr_errors[$rsp];
		}
	} else if (preg_match('/^(un)?mute (.+)$/', $cmd, $matches)) {
		$active = ($matches[1] == 'un') ? false : true;
		$mute_name = $matches[2];
		$rsp = usr_set_mute($usr, $mute_name, $active);
		if ($rsp == USR_MUTED) {
			$msg = "You will no longer receive messages from $mute_name.\nSend \"/unmute $mute_name\" to turn the mute off.";
		} else if ($rsp == USR_UNMUTED) {
			$msg = "Mute disabled, you will now receive messages from $mute_name.";
		} else {
			global $usr_errors;
			$msg = $usr_errors[$rsp];
		}
	} else if ($cmd == 'website') {
		$msg = "Uh, this feature doesn't exist yet. Soon!";
	//	$msg = "You can read the chat archives at:\n$website_url";
	} else if (preg_match('/invite (.+)$/', $cmd, $matches)) {
		$phone = $matches[1];
		usr_invite($usr, $rx_id, $phone);
	} else {
		// This isn't a command we know about
		return false;
	}

	msg_tx($rx_id, $usr->id, $msg, "send now");
	return true;
}

function msg_add_to_channel($rx_id, $usr_id, $msg) {
	$db = db_setup();
	$created = date('Y-m-d H:i:s');
	$query = $db->prepare("
		INSERT INTO channel
		(rx_id, usr_id, msg, created)
		VALUES (?, ?, ?, ?)
	");
	$query->execute(array(
		$rx_id,
		$usr_id,
		$msg,
		$created
	));
}
