<?php

$database_host_legacy = getenv('DB_HOST');
$database_name_legacy = getenv('DB_NAME');
$database_user_legacy = getenv('DB_USER');
$database_password_legacy = getenv('DB_PASSWORD');

$database_host = getenv('PHPLIST_DATABASE_HOST');
$database_port = getenv('PHPLIST_DATABASE_PORT');
$database_driver = getenv('PHPLIST_DATABASE_DRIVER'); ## only pdo_mysql at the moment
$database_name = getenv('PHPLIST_DATABASE_NAME');
$database_user = getenv('PHPLIST_DATABASE_USER');
$database_password = getenv('PHPLIST_DATABASE_PASSWORD');
$phplist_secret = getenv('PHPLIST_SECRET');

if (empty($database_host)) {
    $database_host = $database_host_legacy;
}
if (empty($database_name)) {
    $database_name = $database_name_legacy;
}
if (empty($database_user)) {
    $database_user = $database_user_legacy;
}
if (empty($database_password)) {
    $database_password = $database_password_legacy;
}

$mailhost = getenv('MAILHOST');

define('PHPMAILERHOST', $mailhost);
define('TEST', 0);
define('HASH_ALGO', 'sha256');
define('UPLOADIMAGES_DIR','images');
define ('MANUALLY_PROCESS_BOUNCES',1);
define ('MANUALLY_PROCESS_QUEUE',0);
define('PHPMAILER_SECURE',0);
define('CHECK_REFERRER',false);
define('PHPLIST_POWEREDBY_URLROOT','https://d3u7tsw7cvar0t.cloudfront.net/images');

$addonsUpdater = [
    'work' => '/var/tmp/phplistupdate',
];
$updaterConfig = [
    'work' => '/var/tmp/phplistupdate',
];