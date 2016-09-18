<?php

define('MSG_MUTED_STATUS', -1);
define('E_MSG_NOT_FOUND', 1);
define('E_MSG_ALREADY_APPROVED', 2);
define('E_MSG_SENDER_NOT_FOUND', 3);
define('E_MSG_USER_BANNED', 4);

function msg_rx($usr_id, $rx_msg) {
	$received = date('Y-m-d H:i:s');
	$post_json = json_encode($_POST, JSON_PRETTY_PRINT);
	return db_insert('rx', array(
		'usr_id'    => $usr_id,
		'msg'       => $rx_msg,
		'received'  => $received,
		'post_json' => $post_json
	));
}

function msg_tx($usr_id, $tx_msg, $rx_id, $send_now = false) {

	if (is_array($tx_msg)) {
		// Send a multi-part message one part at a time.
		foreach ($tx_msg as $tx_msg_part) {
			$rsp = msg_tx($usr_id, $tx_msg_part, $rx_id, $send_now);
		}
		return OK;
	}

	$queued = date('Y-m-d H:i:s');
	$rsp = db_insert('tx', array(
		'rx_id' => $rx_id,
		'usr_id' => $usr_id,
		'msg' => $tx_msg,
		'queued' => $queued
	));
	if (! $rsp['ok']) {
		return $rsp;
	}

	$tx_id = $rsp['insert_id'];
	if (! empty($send_now)) {
		if (DEBUG) {
			echo "Sending now!\n";
		}
		$tx_batch = util_uuid();
		db_update('tx', array(
			'transmit_batch' => $tx_batch
		), "id = $tx_id");
		msg_send_sms($tx_id);
	}

	return OK;
}

function msg_mod_tx($usr_id, $msg, $rx_id) {

	if (is_array($msg)) {
		foreach ($msg as $msg_part) {
			$rsp = msg_mod_tx($usr_id, $msg_part, $rx_id);
			if (! $rsp['ok']) {
				return $rsp;
			}
		}
		return $rsp;
	}

	$rsp = usr_get_mods($usr_id);
	if (! $rsp['ok']) {
		return $rsp;
	}

	$admins = $rsp['admins'];
	$bulk_values = array();
	$queued = date('Y-m-d H:i:s');
	foreach ($admins as $admin_id) {
		$bulk_values[] = array(
			'rx_id'  => $rx_id,
			'usr_id' => $admin_id,
			'msg'    => $msg,
			'queued' => $queued
		);
	}
	return db_insert_bulk('tx', $bulk_values);
}

function msg_router($usr_id, $rx_msg) {
	$rsp = usr_get_by_id($usr_id);
	if (! $rsp['ok']) {
		return $rsp;
	}
	$usr = $rsp['usr'];
	$usr_context = $usr->context;

	$rsp = msg_rx($usr_id, $rx_msg);
	if (! $rsp['ok']) {
		return $rsp;
	}
	$rx_id = $rsp['insert_id'];

	$cmd = msg_is_command($rx_msg);

	if ($cmd) {
		// User is trying to issue a command
		// See: sms_commands.php
		$rsp = sms_command($usr_id, $rx_msg, $rx_id, $cmd);
	} else {
		// Not a command, proceed according to the $usr_context
		// See: sms_handlers.php
		$rsp = sms_handler($usr_id, $rx_msg, $rx_id, $usr_context);
	}

	$rsp['rx_id'] = $rx_id;
	return $rsp;
}

function msg_send_pending() {

	if (DEBUG) {
		echo "msg_send_pending\n";
	}

	$tx_batch = util_uuid();

	if (DEBUG) {
		echo "batch: $tx_batch\n";
	}

	// Delay sending SMS messages to recently-active web users (don't need
	// to both SMS and show them messages on the website)
	$web_active = usr_get_web_active();
	$where_clause = 'transmit_batch IS NULL';
	if (! empty($web_active)) {
		$web_active = implode(', ', $web_active);
		$where_clause .= " AND usr_id NOT IN ($web_active)";
	}

	$rsp = db_update('tx', array(
		'transmit_batch' => $tx_batch
	), $where_clause);

	if (! $rsp['ok']) {
		if (DEBUG) {
			echo "Error updating transmit_batch\n";
			print_r($rsp);
		}
		return $rsp;
	}

	$rsp = db_fetch("
		SELECT tx.id AS id,
		       tx.usr_id AS usr_id,
		       rx.usr_id AS sender_id,
		       usr.phone AS phone,
		       tx.msg AS msg
		FROM tx, rx, usr
		WHERE tx.transmit_batch = ?
		  AND tx.rx_id = rx.id
		  AND tx.usr_id = usr.id
		ORDER BY tx.queued
	", array($tx_batch));
	if (! $rsp['ok']) {
		return $rsp;
	}

	$count = 0;
	foreach ($rsp['rows'] as $tx) {
		if (msg_send_sms($tx)) {
			$count++;
		}
	}
	return array(
		'ok' => 1,
		'sent_msg_count' => $count
	);
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
			echo "Cannot send SMS, no tx record.\n";
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
		if (DEBUG) {
			echo "Not sending SMS, because user is muted.\n";
		}
		return false;
	}

	if (! empty($offline_mode)) {
		if (DEBUG) {
			echo "[Offline mode] would have sent SMS\n";
			print_r($tx);
		}
		return false;
	}

	$post = array();
	$post['Body'] = $tx->msg;
	$post['MessagingServiceSid'] = $twilio_messaging_service_sid;
	$post['To'] = $tx->phone;
	$post_str = http_build_query($post);
	$post_json = json_encode($post);
	$response = array();

	if (preg_match('/5551212$/', $tx->phone)) {
		if (DEBUG) {
			echo "Would have sent SMS to $tx->phone, but it looks like a test number.\n";
		}
		$response = array(
			'debug' => true
		);
		$ok = 1;
	} else {

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

		curl_close($ch);
	}

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
		echo "Updated tx record:\n";
		print_r($tx);
	}

	return $tx->id;
}

function msg_signed_format($usr, $msg, $max_length = 160) {
	if (mb_strlen("$usr->name: $msg") <= $max_length) {
		return "$usr->name: $msg";
	}
	$msg_parts = array();
	$words = mb_split(' ', $msg);
	$part = "$usr->name:";
	foreach ($words as $word) {
		if (mb_strlen("$part $word") > ($max_length - 1)) {
			$msg_parts[] = $part . '…';
			$part = "$usr->name: …";
		}
		$part .= " $word";
	}
	$msg_parts[] = $part;
	return $msg_parts;
}

function msg_is_command($msg) {

	// Normalize and detect command formats: /stop or 12345 (for login)
	$msg = strtolower($msg);
	$msg = trim($msg);
	if (substr($msg, 0, 1) != '/' &&                 // Slash command
	    ! (is_numeric($msg) && strlen($msg) == 5)) { // Login code
		return false;
	}

	$cmd = $msg;

	if (is_numeric($cmd)) {
		$cmd = "login $cmd";
	} else {
		// Strip the leading slash
		$cmd = substr($cmd, 1);
	}

	if (DEBUG) {
		echo "cmd: $cmd\n";
	}

	if (preg_match('/^\s*([a-z]+)\s*(.*)$/', $cmd, $matches)) {
		return array(
			'id' => $matches[1],
			'args' => $matches[2]
		);
	} else {
		return array(
			'id' => 'unknown'
		);
	}
}

function msg_chat($usr_id, $rx_msg, $rx_id) {

	$rsp = usr_get_by_id($usr_id);
	util_ensure_rsp($rsp);
	$usr = $rsp['usr'];

	if ($usr->status == 'banned') {
		// If the user is banned, just send their message to the admins.
		$banned_msg = msg_signed_format($usr, "[banned] $rx_msg");
		msg_mod_tx($usr->id, $banned_msg, $rx_id);
		return array(
			'ok' => 1,
			'id' => rand(32, 64),
			'msg' => $rx_msg
		);
	}

	$rsp = msg_add_to_chat($rx_id, $usr->id, $rx_msg, $usr->channel);
	if (! $rsp['ok']) {
		return $rsp;
	}
	$chat_id = $rsp['insert_id'];

	$rsp = usr_get_active($usr->id, $usr->channel);
	if (! $rsp['ok']) {
		return $rsp;
	}

	if (empty($rsp['active'])) {
		// If there are no active users, we are kinda done.
		// TODO: should we tell the user they're alone in the chat?
		if (DEBUG) {
			echo "Chat is empty.\n";
		}
		return array(
			'ok' => 1,
			'msg' => $rx_msg
		);
	}

	$signed_msg = msg_signed_format($usr, $rx_msg);
	$active_usrs = $rsp['active'];
	foreach ($active_usrs as $tx_usr_id) {
		msg_tx($tx_usr_id, $signed_msg, $rx_id);
	}

	if (DEBUG) {
		echo "Sent message to " . count($active_usrs) . " active users.\n";
	}

	$enc_msg = htmlentities("$usr->name: $rx_msg");

	return array(
		'ok' => 1,
		'id' => $chat_id,
		'msg' => $enc_msg
	);
}

function msg_add_to_chat($rx_id, $usr_id, $msg, $channel = 'main') {
	$db = db_setup();
	$created = date('Y-m-d H:i:s');
	return db_insert('chat', array(
		'channel' => $channel,
		'usr_id' => $usr_id,
		'msg' => $msg,
		'rx_id' => $rx_id,
		'created' => $created
	));
}
