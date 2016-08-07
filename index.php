<?php

include 'config.php';
$topic = 'smol msg svc';

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=0">
		<title><?php echo $topic; ?></title>
		<link rel="stylesheet" href="css/smol-msg-svc.css">
	</head>
	<body>
		<div id="page">
			<div id="msgs"></div>
			<form action="submit.php" method="post">
				<input type="hidden" name="image" value="">
				<input type="hidden" name="avatar_color" value="">
				<input type="hidden" name="avatar_position" value="">
				<input type="hidden" name="avatar_icon" value="">
				<input type="hidden" name="time" value="">
				<!--<div id="avatar" class="avatar">
					<div class="relative">
						<div class="icon"></div>
					</div>
				</div>
				<textarea name="msg" placeholder="Type a message here, then press [return]" cols="80" rows="3"></textarea>-->
				<p class="sms-only">
					<strong><?php echo $prompt; ?></strong><br>
					Send an SMS to <?php echo $phone_number; ?> to join the chat.
				</p>
			</form>
		</div>
		<script src="js/jquery-1.12.1.min.js"></script>
		<script src="js/tinycolor-min.js"></script>
		<script src="js/smol-msg-svc.js"></script>
	</body>
</html>
