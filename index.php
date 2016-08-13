<?php

include 'config.php';
$topic = 'smol msg svc';

function normalize_phone_number($phone) {
	$phone = preg_replace('/\D/', '', $phone);
	$phone = "+1$phone";
	return $phone;
}

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<?php if (empty($_GET['eo1'])) { ?>
			<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=0">
		<?php } else { ?>
			<meta name="viewport" content="height=1920, width=1080">
		<?php } ?>
		<title><?php echo $topic; ?></title>
		<link rel="stylesheet" href="css/smol-msg-svc.css">
	</head>
	<body<?php if (! empty($_GET['eo1'])) { ?> class="eo1"<?php } ?>>
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
					<?php if (! empty($_GET['eo1'])) { ?>
						SMS <strong><?php echo $phone_number; ?></strong> to join the chat.
						<span class="website"><?php echo $website_url; ?></span>
					<?php } else { ?>
						SMS <a href="tel:<?php echo normalize_phone_number($phone_number); ?>"><?php echo $phone_number; ?></a> to join the chat.
					<?php } ?>
				</p>
			</form>
		</div>
		<script src="js/jquery-1.12.1.min.js"></script>
		<script src="js/tinycolor-min.js"></script>
		<script src="js/smol-msg-svc.js"></script>
	</body>
</html>
