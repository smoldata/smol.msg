<?php

if (!empty($_POST['msg'])) {
	
	// Set the time, if we're off by more than 15sec of the client
	if (abs(time() - $_POST['time']) > 15) {
		$date = date('Y-m-d H:i:s', $_POST['time']);
		exec("/bin/date -s \"$date\"");
	}
	
	$id = number_format(microtime(true), 4, '.', '');
	$filename = "msg/$id.json";
	$color = '#ccc';
	if (preg_match('/^#[0-9a-f]{6}$/', $_POST['avatar_color'])) {
		$color = $_POST['avatar_color'];
	}
	$position = '0px 0px';
	if (preg_match('/^-?\d+px -?\d+px$/', $_POST['avatar_position'])) {
		$position = $_POST['avatar_position'];
	}
	$icon = 'white';
	if ($_POST['avatar_icon'] == 'black') {
		$icon = 'black';
	}
	$img = $_POST['img'];
	if (substr($img, 0, 22) != 'data:image/jpeg;base64') {
		$img = '';
	}
	
	$msg = json_encode(array(
		'id'  => $id,
		'msg' => htmlentities($_POST['msg'], ENT_HTML5, 'UTF-8'),
		'img' => $img,
		'ip'  => $_SERVER['REMOTE_ADDR'],
		'avatar' => array(
			'color' => $color,
			'position' => $position,
			'icon' => $icon
		)
	));
	file_put_contents($filename, $msg);
	header('Content-Type: application/json');
	echo $msg;
}

?>
