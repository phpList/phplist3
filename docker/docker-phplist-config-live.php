<?php

$database_host = getenv('DB_HOST');
$database_name = getenv('DB_NAME');
$database_user = getenv('DB_USER');
$database_password = getenv('DB_PASSWORD');
$mailhost = getenv('MAILHOST');
define('PHPMAILERHOST', $mailhost);
define('TEST', 0);
define('HASH_ALGO', 'sha256');
define('UPLOADIMAGES_DIR','images');
define ('MANUALLY_PROCESS_BOUNCES',1);
define ('MANUALLY_PROCESS_QUEUE',0);
define('PHPMAILER_SECURE',0);
define('CHECK_REFERRER',false);
