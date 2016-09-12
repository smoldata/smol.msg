<?php

$phone_number = '(718) 555-1212';
$website_url = 'sms.smalldata.coop';
$use_ssl = true;
$default_country_code = 1;
$offline_mode = 0;

// You can find the account SID / auth token at https://twilio.com/console
$twilio_account_sid = 'XXXXXXXXXXXXXXXXXX';
$twilio_auth_token = 'XXXXXXXXXXXXXXXXXX';

// Set up a new message service at https://www.twilio.com/console/sms/services
$twilio_messaging_service_sid = 'XXXXXXXXXXXXXXXXXX';

// MySQL settings
$db_host = 'localhost';
$db_user = 'user';
$db_pass = 'XXXXXXXXXX';
$db_name = 'smol_msg_svc';

date_default_timezone_set('America/New_York');
mb_internal_encoding('UTF-8');
