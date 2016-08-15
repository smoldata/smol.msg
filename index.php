<?php

include 'include/init.php';
$topic = 'smol msg svc';

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
				<!--<div class="relative">
					<div id="about">
						<div class="relative">
							<a href="#" class="about-link">close</a>
							<h1>Heading</h1>
							<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
							<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
							<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
						</div>
					</div>
				</div>-->
				<p class="sms-only">
					<?php if (! empty($_GET['eo1'])) { ?>
						<span class="prompt">SMS <strong><?php echo $phone_number; ?></strong> to join the chat.</span>
						<span class="website"><?php echo $website_url; ?></span>
						<br class="clear">
					<?php } else { ?>
						<span class="prompt">SMS <a href="sms:<?php echo util_normalize_phone_number($phone_number); ?>"><?php echo $phone_number; ?></a> to chat.</span>
						<!--<a href="#about" class="about-link">About</a>-->
						<br class="clear">
					<?php } ?>
				</p>
			</form>
		</div>
		<script src="js/jquery-1.12.1.min.js"></script>
		<script src="js/tinycolor-min.js"></script>
		<script src="js/smol-msg-svc.js"></script>
	</body>
</html>
