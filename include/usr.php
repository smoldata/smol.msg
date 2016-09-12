<?php

define('E_USR_NAME_FORMAT', 1);
define('E_USR_NAME_EXISTS', 2);

define('USR_MUTED', 3);
define('USR_UNMUTED', 4);

define('E_USR_DOES_NOT_EXIST', 5);
define('E_USR_NOT_MUTED', 6);
define('E_USR_CANT_MUTE_SELF', 7);

define('E_USR_INVALID_PHONE', 8);
define('E_USR_LOGIN_INVALID', 9);
define('E_USR_LOGIN_EXPIRED', 10);

define('USR_LOGIN_TTL', 15 * 60 * 60);

$usr_errors = array(
	E_USR_NAME_FORMAT => 'Sorry, please keep it to 16 letters, numbers, or _ (no spaces).',
	E_USR_NAME_EXISTS => 'Oops, somebody else already has that name.',
	E_USR_DOES_NOT_EXIST => 'Huh, that user does not exist. Sorry!',
	E_USR_NOT_MUTED => 'Weird, it seems you weren\'t muting that user. In any case, you will get their messages now!',
	E_USR_CANT_MUTE_SELF => 'Uhh, sorry you cannot mute yourself!',
	E_USR_INVALID_PHONE => 'Hmm. That phone number doesn\'t seem to work?'
);

function usr_get_by_phone($phone) {

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

	// Return the newly created record
	return usr_get_by_phone($phone);
}

function usr_get_by_id($id) {
	$rsp = db_single("
		SELECT *
		FROM usr
		WHERE id = ?
	", array($name));
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
	$usr->context = $context;
	return db_update('usr', array(
		'context' => $context
	), "id = $usr_id");
}

function usr_set_name($usr, $rx_id, $name) {
	$name = trim($name);
	if (! preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
		return E_USR_NAME_FORMAT;
	}
	$db = db_setup();
	
	$query = $db->prepare("
		SELECT id
		FROM usr
		WHERE name = ?
	");
	$query->execute(array(
		$name
	));
	$existing = $query->fetchObject();
	if (! empty($existing) &&
	    $existing->id != $usr->id) {
		return E_USR_NAME_EXISTS;
	}
	
	$query = $db->prepare("
		UPDATE usr
		SET name = ?
		WHERE id = ?
	");
	$query->execute(array(
		$name,
		$usr->id
	));

	msg_admin_tx($usr->id, "[$usr->name is now known as $name]", $rx_id);

	$usr->name = $name;

	return OK;
}

function usr_set_status($usr, $status) {
	$db = db_setup();
	$query = $db->prepare("
		UPDATE usr
		SET status = ?
		WHERE id = ?
	");
	$query->execute(array(
		$status,
		$usr->id
	));
}

function usr_set_ban($usr, $ban_active = true) {
	if ($ban_active) {
		usr_set_status($usr, 'banned');
	} else {
		usr_set_status($usr, 'user');
	}
	return OK;
}

function usr_is_admin($usr) {
	return ($usr->status == 'admin');
}

function usr_set_mute($usr, $name, $mute) {
	$db = db_setup();
	$query = $db->prepare("
		SELECT id
		FROM usr
		WHERE name = ?
	");
	$query->execute(array(
		$name
	));
	$mute_usr = $query->fetchObject();
	if (empty($mute_usr)) {
		return E_USR_DOES_NOT_EXIST;
	}

	if ($mute_usr->id == $usr->id) {
		return E_USR_CANT_MUTE_SELF;
	}

	$query = $db->prepare("
		SELECT *
		FROM usr_mute
		WHERE usr_id = ?
		  AND muted_id = ?
	");
	$query->execute(array(
		$usr->id,
		$mute_usr->id
	));
	$exists = $query->fetchObject();
	
	if ($mute) {
		if (empty($exists)) {
			$created = date('Y-m-d H:i:s');
			$query = $db->prepare("
				INSERT INTO usr_mute
				(usr_id, muted_id, created)
				VALUES (?, ?, ?)
			");
			$query->execute(array(
				$usr->id,
				$mute_usr->id,
				$created
			));
		}
		return USR_MUTED;
	} else {
		if (! empty($exists)) {
			$query = $db->prepare("
				DELETE FROM usr_mute
				WHERE usr_id = ?
				  AND muted_id = ?
			");
			$query->execute(array(
				$usr->id,
				$mute_usr->id
			));
			return USR_UNMUTED;
		} else {
			return E_USR_NOT_MUTED;
		}
	}
}

function usr_get_active($usr) {
	$db = db_setup();
	$query = $db->prepare("
		SELECT id
		FROM usr
		WHERE id != ?
		  AND context = 'chat'
	");
	$query->execute(array(
		$usr->id
	));
	$active = array();
	while ($id = $query->fetchColumn(0)) {
		$active[] = $id;
	}
	return $active;
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
	if (DEBUG) {
		echo "Found admins:\n";
		print_r($rsp);
	}

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
		$usr_mutes = array();
		$db = db_setup();
		$query = $db->query("
			SELECT *
			FROM usr_mute
		");
		while ($mute = $query->fetchObject()) {
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

function usr_invite($usr, $rx_id, $phone) {
	$phone = util_normalize_phone($phone);
	if (! $phone) {
		return E_USR_INVALID_PHONE;
	}

	$db = db_setup();
	$query = $db->prepare("
		SELECT *
		FROM usr
		WHERE phone = ?
	");
	$query->execute(array(
		$phone
	));
	$exists = $query->fetchObject();
	
	if (empty($exists)) {
		$query = $db->prepare("
			INSERT INTO usr
			(phone, name, context, joined)
			VALUES (?, ?, ?, ?)
		");
		$query->execute(array(
			$phone,
			substr($phone, -4, 4),
			'invited',
			date('Y-m-d H:i:s')
		));
		$invited_id = $db->lastInsertId();
		$msg = "Hello! $usr->name has invited you to an SMS chat. Reply \"ok\" to join.";
		msg_tx($invited_id, $msg, $rx_id, "send now");
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

function usr_complete_login($usr, $login_code) {
	$login = usr_get_login($login_code);
	$ttl_cutoff = time() - USR_LOGIN_TTL;
	if (empty($login)) {
		return E_USR_LOGIN_INVALID;
	} else if (strtotime($login->created) < $ttl_cutoff) {
		return E_USR_LOGIN_EXPIRED;
	}
	
	$db = db_setup();
	$query = $db->prepare("
		UPDATE usr_login
		SET usr_id = ?
		WHERE login_code = ?
	");
	$query->execute(array(
		$usr->id,
		$login_code
	));
	return OK;
}
