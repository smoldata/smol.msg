<?php

include_once __DIR__ . '/init_local.php';

if ($argc == 1) {
	echo "Usage: php bin/smol.php message [phone]\n";
	echo "       phone defaults to +12125551212 for testing purposes\n";
	echo "Examples:\n";
	echo "  smol.sh \"hello\"\n";
	echo "  smol.sh \"/stop\" \"+12125551212\"\n";
	exit;
}

$msg = $argv[1];
$from = '+12125551212';

if (! empty($argv[2])) {
	$from = util_normalize_phone($argv[2]);
}

$post = array(
	'From' => $from,
	'Body' => $msg,
	'AccountSid' => $twilio_account_sid
);

$protocol = ($use_ssl) ? 'https' : 'http';
$url = "$protocol://$website_url/?debug=1";
$post_str = http_build_query($post);

echo "Sending '$msg' from $from...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_str);
$response = curl_exec($ch);
curl_close($ch);

echo "$response\n";
