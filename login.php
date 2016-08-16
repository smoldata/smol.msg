<?php

include 'include/init.php';

$rsp = array(
	'ok' => 0,
	'error' => 'Hmm. Something strange and unexpected happened.'
);

if (! empty($_SESSION['usr_id'])) {
	$usr = usr_get_by_id($_SESSION['usr_id']);
	$rsp = array(
		'ok' => 1,
		'usr_name' => $usr->name
	);
} else if (empty($_SESSION['login_code'])) {
	$rsp = usr_create_login();
	if (! empty($rsp['ok'])) {
		$_SESSION['login_code'] = $rsp['login_code'];
	}
} else {
	$ttl_cutoff = time() - USR_LOGIN_TTL;
	$login = usr_get_login($_SESSION['login_code']);
	if (empty($login)) {
		// Session is invalid?
		$rsp = usr_create_login();
		if (! empty($rsp['ok'])) {
			$_SESSION['login_code'] = $rsp['login_code'];
		}
	} else if (strtotime($login->created) < $ttl_cutoff) {
		// Expired, try again
		usr_delete_login($login->login_code);
		$rsp = usr_create_login();
		if (! empty($rsp['ok'])) {
			$_SESSION['login_code'] = $rsp['login_code'];
		}
	} else if (! empty($login->usr_id)) {
		// Hey, we are logged in!
		$usr = usr_get_by_id($login->usr_id);
		$_SESSION['usr_id'] = $login->usr_id;
		$_SESSION['login_code'] = null;
		usr_delete_login($login->login_code);
		usr_set_status($usr, 'chat');
		$rsp = array(
			'ok' => 1,
			'usr_name' => $usr->name
		);
	} else {
		// Ok, it still works, but we're still waiting.
		$rsp = array(
			'ok' => 1,
			'login_code' => $_SESSION['login_code']
		);
	}
}

$rsp['phone'] = $phone_number;
$rsp['phone_normalized'] = util_normalize_phone_number($phone_number);

header('Content-Type: application/json');
echo json_encode($rsp);
