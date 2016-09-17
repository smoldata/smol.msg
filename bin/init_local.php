<?php

define('OK', 0);
define('DEBUG', true);

$base_dir = dirname(__DIR__);
$include_dir =  "$base_dir/include";

include_once("$base_dir/config.php");
include_once("$base_dir/xo.php");
include_once("$base_dir/sms_commands.php");
include_once("$base_dir/sms_handlers.php");
include_once("$include_dir/db.php");
include_once("$include_dir/msg.php");
include_once("$include_dir/usr.php");
include_once("$include_dir/util.php");
