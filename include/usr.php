<?php

define('USR_LOGIN_TTL', 15 * 60 * 60);

function usr_get($who) {

	// This is a shortcut to either usr_get_by_id or usr_get_by_name.
	// Instead of returning a $rsp, this will return a usr object or null.

	if (substr($who, 0, 2) == 'id') {
		$id = substr($who, 2);
		$id = intval($id);
		$rsp = usr_get_by_id($id);
	} else {
		$rsp = usr_get_by_name($who);
	}
	if (! empty($rsp['usr'])) {
		return $rsp['usr'];
	} else {
		return null;
	}
}

function usr_get_by_phone($phone) {

	// Of the usr_get_by_* functions, this one is different in that it will
	// create a new usr record if one doesn't exist already.

	$phone = util_normalize_phone($phone);

	$rsp = db_single("
		SELECT *
		FROM usr
		WHERE phone = ?
	", array($phone));
	if (! $rsp['ok']) {
		return $rsp;
	}

	if (! empty($rsp['row'])) {
		// Found an existing record, all done!
		$rsp['usr'] = $rsp['row'];
		return $rsp;
	}

	$last_four = substr($phone, -4, 4);
	$name = $last_four;
	$alias = 0;
	$rsp = usr_get_by_name($name);

	// Keep looking until we find a unique name
	while (! empty($rsp['row'])) {
		$alias++;
		$name = "{$last_four}_{$alias}";
		$rsp = usr_get_by_name($name);
	}

	// Create a new user record
	$context = 'intro';
	$now = date('Y-m-d H:i:s');
	$rsp = db_insert('usr', array(
		'phone'   => $phone,
		'name'    => $name,
		'context' => $context,
		'joined'  => $now,
		'active'  => $now
	));
	if (! $rsp['ok']) {
		return $rsp;
	}
	$usr_id = $rsp['insert_id'];

	// Return the newly created record
	return usr_get_by_phone($phone);
}

function usr_get_by_id($id) {
	$rsp = db_single("
		SELECT *
		FROM usr
		WHERE id = ?
	", array($id));
	if (! $rsp['ok']) {
		return $rsp;
	}

	$rsp['usr'] = $rsp['row'];
	return $rsp;
}

function usr_get_by_name($name) {
	$rsp = db_single("
		SELECT *
		FROM usr
		WHERE name = ?
	", array($name));
	if (! $rsp['ok']) {
		return $rsp;
	}

	$rsp['usr'] = $rsp['row'];
	return $rsp;
}

function usr_set_context($usr_id, $context) {
	return db_update('usr', array(
		'context' => $context
	), "id = $usr_id");
}

function usr_set_name($usr_id, $name, $rx_id) {

	$name = trim($name);
	$usr_id = intval($usr_id);

	if (empty($name)) {
		return array(
			'ok' => 0,
			'xo' => 'err_name_empty'
		);
	}

	if (! preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
		return array(
			'ok' => 0,
			'xo' => 'err_name_format'
		);
	}

	$rsp = usr_get_by_name($name);
	util_ensure_rsp($rsp);

	$existing = $rsp['usr'];

	if (! empty($existing)) {
		if ($existing->id == $usr_id) {
			return array(
				'ok' => 0,
				'xo' => 'err_name_unchanged'
			);
		} else {
			return array(
				'ok' => 0,
				'xo' => 'err_name_exists'
			);
		}
	}

	$rsp = usr_get_by_id($usr_id);
	util_ensure_rsp($rsp);
	$usr = $rsp['usr'];

	$rsp = db_update('usr', array(
		'name' => $name
	), "id = $usr_id");
	util_ensure_rsp($rsp);

	return array(
		'ok' => 1,
		'new_name' => $name,
		'old_name' => $usr->name
	);
}

function usr_get_first_msg($usr_id, $formatted = false) {
	$rsp = db_single("
		SELECT *
		FROM rx
		WHERE usr_id = ?
		ORDER BY received
		LIMIT 1
	", array($usr_id));
	if (! $rsp['ok']) {
		return $rsp;
	}
	$first_msg = $rsp['row'];
	$msg = $first_msg->msg;

	$rsp = usr_get_by_id($usr_id);
	if (! $rsp['ok']) {
		return $rsp;
	}
	$usr = $rsp['usr'];

	if (! empty($formatted)) {
		$msg = msg_signed_format($usr, $first_msg->msg, 50);
	}

	$rsp = db_value("
		SELECT COUNT(rx_id)
		FROM rx_hold
		WHERE rx_id = ?
		  AND active = 1
	", array($first_msg->id));
	$held = $rsp['value'];

	return array(
		'ok' => 1,
		'first_msg' => $msg,
		'first_msg_id' => $first_msg->id,
		'first_msg_held' => $held
	);
}

function usr_set_status($who, $status) {

	$usr = usr_get($who);
	if (! $usr) {
		return array(
			'ok' => 0,
			'xo' => xo('err_user_not_found')
		);
	}
	$usr_id = intval($usr->id);

	$rsp = db_update('usr', array(
		'status' => $status
	), "id = $usr_id");
	if (! $rsp['ok']) {
		return $rsp;
	}

	$rsp['name'] = $usr->name;
	return $rsp;
}

function usr_set_ban($usr, $ban_active = true) {
	if ($ban_active) {
		usr_set_status($usr, 'banned');
	} else {
		usr_set_status($usr, 'user');
	}
	return OK;
}

function usr_is_admin($usr_id) {

	$usr = usr_get("id$usr_id");
	if (! $usr) {
		if (DEBUG) {
			echo "Unknown user $usr_id!\n";
		}
		return false;
	}

	return ($usr->status == 'admin');
}

function usr_is_first() {
	$rsp = db_value("
		SELECT COUNT(id)
		FROM usr
		WHERE status = 'admin'
	");
	return ($rsp['value'] == 0);
}

function usr_set_mute($usr_id, $name, $mute) {

	$rsp = usr_get_by_name($name);
	if (empty($rsp['usr'])) {
		return array(
			'ok' => 0,
			'xo' => 'err_user_not_found'
		);
	}
	$mute_usr = $rsp['usr'];
	$mute_usr_id = intval($mute_usr->id);
	$usr_id = intval($usr_id);

	if ($mute_usr_id == $usr_id) {
		return array(
			'ok' => 0,
			'xo' => 'err_cannot_mute_self'
		);
	}

	$rsp = db_single("
		SELECT *
		FROM usr_mute
		WHERE usr_id = ?
		  AND muted_id = ?
	", array(
		$usr_id,
		$mute_usr_id
	));
	$exists = $rsp['row'];

	if ($mute) {
		if (empty($exists)) {
			$created = date('Y-m-d H:i:s');
			return db_insert('usr_mute', array(
				'usr_id' => $usr_id,
				'muted_id' => $mute_usr_id,
				'created' => $created
			));
		} else {
			$created = date('Y-m-d H:i:s');
			$where = "usr_id = $usr_id AND muted_id = $mute_usr_id";
			return db_update('usr_mute', array(
				'active' => 1,
				'created' => $created
			), $where);
		}
	} else {
		if (! empty($exists)) {
			$deleted = date('Y-m-d H:i:s');
			$where = "usr_id = $usr_id AND muted_id = $mute_usr_id";
			return db_update('usr_mute', array(
				'active' => 0,
				'deleted' => $deleted
			), $where);
		}
		return array('ok' => 1);
	}
}

function usr_get_active($curr_usr_id = 0) {
	$rsp = db_column("
		SELECT id
		FROM usr
		WHERE id != ?
		  AND context = 'chat'
		  AND status != 'banned'
	", array($curr_usr_id));
	if (! $rsp['ok']) {
		return $rsp;
	}

	return array(
		'ok' => 1,
		'active' => $rsp['column']
	);
}

function usr_get_web_active() {

	// Within the last 60 seconds
	$recent_cutoff = date('Y-m-d H:i:s', time() - 60);

	$db = db_setup();
	$query = $db->prepare("
		SELECT id
		FROM usr
		WHERE web_active > ?
	");
	$query->execute(array(
		$recent_cutoff
	));
	$active = array();
	while ($id = $query->fetchColumn(0)) {
		$active[] = $id;
	}
	return $active;
}

function usr_get_admins($usr_id) {
	$rsp = db_column("
		SELECT id
		FROM usr
		WHERE id != ?
		  AND context = 'chat'
		  AND status = 'admin'
	", array($usr_id));
	if (! $rsp['ok']) {
		return $rsp;
	}

	$rsp['admins'] = $rsp['column'];
	return $rsp;
}

function usr_get_count() {
	$rsp = db_value("
		SELECT COUNT(id)
		FROM usr
	");
	if (! $rsp['ok']) {
		return $rsp;
	}

	$rsp['usr_count'] = $rsp['value'];
	return $rsp;
}

function usr_check_if_muted($tx) {
	global $usr_mutes;
	if (empty($usr_mutes)) {
		$rsp = db_fetch("
			SELECT *
			FROM usr_mute
			WHERE active = 1
		");
		$usr_mutes = array();
		foreach ($rsp['rows'] as $mute) {
			if (empty($usr_mutes[$mute->usr_id])) {
				$usr_mutes[$mute->usr_id] = array();
			}
			array_push($usr_mutes[$mute->usr_id], $mute->muted_id);
		}
	}
	if (empty($usr_mutes[$tx->usr_id])) {
		return false;
	}
	if (DEBUG) {
		if (in_array($tx->sender_id, $usr_mutes[$tx->usr_id])) {
			echo "yes\n";
		} else {
			echo "no\n";
		}
	}
	return in_array($tx->sender_id, $usr_mutes[$tx->usr_id]);
}

function usr_update_active_time($usr_id, $column = 'active') {

	if ($column != 'active' &&
	    $column != 'web_active') {
		die('usr_update_active_time called with unexpected column');
	}

	$db = db_setup();
	$active = date('Y-m-d H:i:s');
	$query = $db->prepare("
		UPDATE usr
		SET $column = ?
		WHERE id = ?
	");
	$query->execute(array(
		$active,
		$usr_id
	));
}

function usr_invite($inviter_id, $invited_phone) {

	$invited_phone = util_normalize_phone($invited_phone);
	if (! $invited_phone) {
		return array(
			'ok' => 0,
			'xo' => 'err_invalid_phone'
		);
	}

	$rsp = usr_get_by_id($inviter_id);
	util_ensure_rsp($rsp);
	$inviter = $rsp['usr'];

	// First make sure the phone number is a new one.
	$rsp = db_single("
		SELECT *
		FROM usr
		WHERE phone = ?
	", array($invited_phone));

	if (empty($rsp['row'])) {
		$rsp = db_insert('usr', array(
			'phone' => $invited_phone,
			'name' => substr($invited_phone, -4, 4),
			'context' => 'invited',
			'joined' => date('Y-m-d H:i:s'),
			'invited_by' => $inviter_id
		));
		util_ensure_rsp($rsp);
		return array(
			'ok' => 1,
			'inviter' => $inviter,
			'invited_id' => $rsp['insert_id']
		);
	} else {
		return array(
			'ok' => 0,
			'xo' => 'err_invited_already_exists'
		);
	}
}

function usr_create_login() {

	include 'config.php';
	$db = db_setup();

	$login_code = mt_rand(0, 99999);
	$login_code = str_pad("$login_code", 5, '0', STR_PAD_LEFT);
	$created = date('Y-m-d H:i:s');

	$query = $db->prepare("
		INSERT INTO usr_login
		(login_code, created)
		VALUES (?, ?)
	");
	$query->execute(array(
		$login_code,
		$created
	));

	$rsp = array(
		'ok' => 1,
		'login_code' => $login_code
	);
	return $rsp;
}

function usr_delete_login($login_code) {
	$db = db_setup();
	$query = $db->prepare("
		DELETE FROM usr_login
		WHERE login_code = ?
	");
	$query->execute(array(
		$login_code
	));
}

function usr_get_login($login_code) {

	$db = db_setup();

	$query = $db->prepare("
		SELECT *
		FROM usr_login
		WHERE login_code = ?
	");
	$query->execute(array(
		$login_code
	));
	return $query->fetchObject();
}

function usr_complete_login($usr_id, $login_code) {
	$login = usr_get_login($login_code);
	$ttl_cutoff = time() - USR_LOGIN_TTL;
	if (empty($login)) {
		return 'err_login_invalid';
	} else if (strtotime($login->created) < $ttl_cutoff) {
		return 'err_login_expired';
	}

	$db = db_setup();
	$query = $db->prepare("
		UPDATE usr_login
		SET usr_id = ?
		WHERE login_code = ?
	");
	$query->execute(array(
		$usr_id,
		$login_code
	));
	return OK;
}
