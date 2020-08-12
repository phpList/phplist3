<?php

// initialisation stuff
/* init sets all the defaults
 * it is called after config, so that constants not defined in config can be set here
 * it cannot use the DB contents, as the DB connection hasn't been established yet
 */
define('PHPLISTINIT', true);
error_reporting(0);

//## remove on rollout ###
if (is_file(dirname(__FILE__).'/../../../VERSION')) {
    $fd = fopen(dirname(__FILE__).'/../../../VERSION', 'r');
    while ($line = fscanf($fd, '%[a-zA-Z0-9,. ]=%[a-zA-Z0-9,. ]')) {
        list($key, $val) = $line;
        if ($key == 'VERSION') {
            $version = $val;
        }
    }
    fclose($fd);
} else {
    $version = 'dev';
}

if (!defined('VERSION')) {
    if (!ini_get('open_basedir') && is_dir(dirname(__FILE__).'/../../../.git')) {
        define('VERSION', $version.'-dev');
        define('DEVVERSION', true);
    } else {
        define('VERSION', $version);
        define('DEVVERSION', false);
    }
} else {
    define('DEVVERSION', false);
}

if (empty($GLOBALS['commandline']) && isset($GLOBALS['developer_email']) && $_SERVER['HTTP_HOST'] != 'dev.phplist.com' && !empty($GLOBALS['show_dev_errors'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    foreach ($_REQUEST as $key => $val) {
        unset($$key);
    }
}
//## end remove on rollout ###
if (!defined('DEVVERSION')) {
    define('DEVVERSION', false);
}

ini_set('session.name','phpListSession');
ini_set('session.cookie_samesite','Strict');
ini_set('session.use_only_cookies',1);
ini_set('session.cookie_httponly',1);

// record the start time(usec) of script
$now = gettimeofday();
$GLOBALS['pagestats'] = array();
$GLOBALS['pagestats']['time_start'] = $now['sec'] * 1000000 + $now['usec'];
$GLOBALS['pagestats']['number_of_queries'] = 0;

// taken from Joomla, prepare for PHP5.6 and up
// see https://github.com/phpList/phplist3/pull/3
if (function_exists('iconv') || ((!strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && dl('iconv.so')))) {
    if (version_compare(PHP_VERSION, '5.6', '>=')) {
        @ini_set('default_charset', 'UTF-8');
    } elseif (function_exists('iconv_set_encoding')) {
        iconv_set_encoding('input_encoding', 'UTF-8');
        iconv_set_encoding('internal_encoding', 'UTF-8');
        iconv_set_encoding('output_encoding', 'UTF-8');
    }
}

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

$IsCommandlinePlugin = '';
$zlib_compression = ini_get('zlib.output_compression');
// hmm older versions of PHP don't have this, but then again, upgrade php instead?
if (function_exists('ob_list_handlers')) {
    $handlers = ob_list_handlers();
} else {
    $handlers = array();
}
$gzhandler = 0;
foreach ($handlers as $handler) {
    $gzhandler = $gzhandler || $handler == 'ob_gzhandler';
}
// @@@ needs more work
$GLOBALS['compression_used'] = $zlib_compression || $gzhandler;

// make sure these are set correctly, so they cannot be injected due to the PHP Globals Problem,
// http://www.hardened-php.net/globals-problem
$GLOBALS['language_module'] = $language_module;
$GLOBALS['database_module'] = $database_module;

//# this is mostly useful when using commandline, and the language is not detected
//# with the browser
if (!isset($GLOBALS['default_system_language'])) {
    $GLOBALS['default_system_language'] = 'en';
}

if (!isset($table_prefix)) {
    $table_prefix = 'phplist_';
}
if (!isset($usertable_prefix)) {
    $usertable_prefix = 'phplist_user_';
}

/* this can probably go */
if (isset($GLOBALS['design'])) {
    $GLOBALS['ui'] = $GLOBALS['design'];
//@todo
//  $GLOBALS['design'] = basename($GLOBALS['design']);
}

if (!isset($GLOBALS['ui']) || !is_dir(dirname(__FILE__).'/ui/'.$GLOBALS['ui'])) {
    if (is_dir(dirname(__FILE__).'/ui/phplist-ui-bootlist')) {
        $GLOBALS['ui'] = 'phplist-ui-bootlist';
    } elseif (is_dir(dirname(__FILE__).'/ui/dressprow')) {
        $GLOBALS['ui'] = 'dressprow';
    } elseif (is_dir(dirname(__FILE__).'/ui/default')) {
        $GLOBALS['ui'] = 'default';
    } else {
        ## pick the first available (this should really only be in dev mode)
        $themes = glob(dirname(__FILE__).'/ui/*',GLOB_ONLYDIR);
        $GLOBALS['ui'] = basename($themes[0]);
    }
}
if (!is_file(dirname(__FILE__).'/ui/'.$GLOBALS['ui'].'/pagetop.php')) {
    print 'Error: unable to initialise theme'; exit;
}

include_once dirname(__FILE__).'/structure.php';

if (!isset($tables)) {
    $tables = array();
}
foreach ($GLOBALS['DBstructuser'] as $tablename => $tablecolumns) {
    $tables[$tablename] = $usertable_prefix.$tablename;
}
foreach ($GLOBALS['DBstructphplist'] as $tablename => $tablecolumns) {
    $tables[$tablename] = $table_prefix.$tablename;
}
// unset the struct arrays, DBStruct and tables globals remain for the rest of the program
unset($GLOBALS['DBstructuser']);
unset($GLOBALS['DBstructphplist']);

$GLOBALS['show_dev_errors'] = $show_dev_errors;

if (empty($GLOBALS['language_module'])) {
    $GLOBALS['language_module'] = 'english.inc';
}
if (empty($GLOBALS['database_module']) || !is_file(dirname(__FILE__).'/'.$GLOBALS['database_module'])) {
    $GLOBALS['database_module'] = 'mysqli.inc';
}
if (!isset($database_port)) {
    $database_port = null;
}
if (!isset($database_socket)) {
    $database_socket = null;
}
if (!isset($database_connection_compression)) {
    $database_connection_compression = false;
}
if (!isset($database_connection_ssl)) {
    $database_connection_ssl = false;
}

//# @@ would be nice to move this to the config file at some point
// http://mantis.phplist.com/view.php?id=15521
//# set it on the fly, although that will probably only work with Apache
//# we need to save this in the DB, so that it'll work on commandline
$GLOBALS['scheme'] = (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on')) ? 'https' : 'http';
if (defined('ADMIN_PROTOCOL')) {
    $GLOBALS['admin_scheme'] = ADMIN_PROTOCOL;
} else {
    $GLOBALS['admin_scheme'] = $GLOBALS['scheme'];
}
if (defined('PUBLIC_PROTOCOL')) {
    $GLOBALS['public_scheme'] = PUBLIC_PROTOCOL;
} else {
    $GLOBALS['public_scheme'] = $GLOBALS['scheme'];
}
if (!defined('HTTP_HOST')) { // allow overriding of _SERVER['HTTP_HOST']
    define('HTTP_HOST', false); // but default to not, and take that value
}

if (!isset($bounce_protocol)) {
    $bounce_protocol = 'pop';
}
if (!isset($bounce_unsubscribe_threshold)) {
    $bounce_unsubscribe_threshold = 3;
}
//# spelling mistake in earlier version, make sure to set it correctly
if (!isset($bounce_unsubscribe_threshold) && isset($bounce_unsubscribe_treshold)) {
    $bounce_unsubscribe_threshold = $bounce_unsubscribe_treshold;
}
if (!isset($bounce_mailbox_purge)) {
    $bounce_mailbox_purge = true;
}
if (!isset($bounce_mailbox_purge_unprocessed)) {
    $bounce_mailbox_purge_unprocessed = true;
}

// set some defaults if they are not specified
if (!defined('REGISTER')) {
    define('REGISTER', 1);
}
if (!defined('USE_PDF')) {
    define('USE_PDF', 0);
}
if (!defined('VERBOSE')) {
    define('VERBOSE', 0);
}
if (!defined('TEST')) {
    define('TEST', 1);
}
if (!defined('DEVSITE')) {
    define('DEVSITE', 0);
}
define('TRANSLATIONS_XML', 'https://translate.phplist.org/translations.xml');

//define('TLD_AUTH_LIST','http://data.iana.org/TLD/tlds-alpha-by-domain.txt');
//define('TLD_AUTH_MD5','http://data.iana.org/TLD/tlds-alpha-by-domain.txt.md5');
define('TLD_AUTH_LIST', 'https://www.phplist.com/files/tlds-alpha-by-domain.txt');
define('TLD_AUTH_MD5', 'https://www.phplist.com/files/tlds-alpha-by-domain.txt.md5');
define('TLD_REFETCH_TIMEOUT', 2592000); //# 30 days
if (!defined('PQAPI_URL')) {
    define('PQAPI_URL', 'https://pqapi.phplist.com/1/t/pqapi');
}
if (!defined('SHOW_PQCHOICE')) {
    define('SHOW_PQCHOICE', false);
}
if (!defined('PHPLISTNEWSROOT')) {
    define('PHPLISTNEWSROOT', 'https://www.phplist.com/downloadnews/');
}

if (!defined('ALLOW_ATTACHMENTS')) {
    define('ALLOW_ATTACHMENTS', 0);
}
if (!defined('EMAILTEXTCREDITS')) {
    define('EMAILTEXTCREDITS', 0);
}
if (!defined('PAGETEXTCREDITS')) {
    define('PAGETEXTCREDITS', 0);
}
if (!defined('USEFCK')) {
    define('USEFCK', 1);
}
if (!defined('USECK')) {
    define('USECK', 0);
} //# ckeditor integration, not finished yet
if (!defined('ASKFORPASSWORD')) {
    define('ASKFORPASSWORD', 0);
}
if (!defined('SILENT_RESUBSCRIBE')) {
    define('SILENT_RESUBSCRIBE', true);
}
if (!defined('UNSUBSCRIBE_REQUIRES_PASSWORD')) {
    define('UNSUBSCRIBE_REQUIRES_PASSWORD', 0);
}
if (!defined('UNSUBSCRIBE_JUMPOFF')) {
    define('UNSUBSCRIBE_JUMPOFF', 1);
}
if (!defined('SHOW_PREFERENCESLINK')) {
    define('SHOW_PREFERENCESLINK', true);
}
if (!defined('SHOW_UNSUBSCRIBELINK')) {
    define('SHOW_UNSUBSCRIBELINK', true);
}
if (!defined('SHOW_SUBSCRIBELINK')) {
    define('SHOW_SUBSCRIBELINK', true);
}
if (!defined('UNSUBSCRIBE_CONFIRMATION')) {
    define('UNSUBSCRIBE_CONFIRMATION', true);
}
if (ASKFORPASSWORD && defined('ENCRYPTPASSWORD') && ENCRYPTPASSWORD) {
    //#https://mantis.phplist.com/view.php?id=16787
    // passwords are encrypted, so we need to stick to md5 to keep working

    //# we also need some "update" mechanism to handle an algo change
    if (!defined('ENCRYPTION_ALGO')) {
        define('ENCRYPTION_ALGO', 'md5');
    }
}

if (ASKFORPASSWORD && !defined('ENCRYPTPASSWORD')) {
    //# we now always encrypt
    define('ENCRYPTPASSWORD', 1);
}
if (!defined('ENCRYPTPASSWORD')) {
    //# old method to encrypt, used to be with md5, keep like this for backward compat.
    if (!defined('ENCRYPTION_ALGO')) {
        define('ENCRYPTION_ALGO', 'md5');
    }
//  define("ENCRYPTPASSWORD",0);
}

if (!defined('ENCRYPTION_ALGO')) {
    if (function_exists('hash_algos') && in_array('sha256', hash_algos())) {
        define('ENCRYPTION_ALGO', 'sha256');
    } else {
        define('ENCRYPTION_ALGO', 'md5');
    }
}
if (!defined('HASH_ALGO')) {
    // keep previous hashalg. @@TODO force an update of hash method, many may still be on md5.
    if (defined('ENCRYPTION_ALGO')) {
        define('HASH_ALGO', ENCRYPTION_ALGO);
    } elseif (function_exists('hash_algos') && in_array('sha256', hash_algos())) {
        define('HASH_ALGO', 'sha256');
    } else {
        define('HASH_ALGO', 'md5');
    }
}
//# remember the length of a hashed string
$hash_length = strlen(hash(HASH_ALGO, 'some text'));

if (!defined('PHPMAILER')) {
    define('PHPMAILER', 1);
}
if (!defined('WARN_ABOUT_PHP_SETTINGS')) {
    define('WARN_ABOUT_PHP_SETTINGS', 1);
}
if (!defined('NUMATTACHMENTS')) {
    define('NUMATTACHMENTS', 1);
}
if (!defined('PLUGIN_ROOTDIR')) {
    define('PLUGIN_ROOTDIR', 'plugins');
} //#17270 - this will point to the plugins subdir of admin
if (!defined('PLUGIN_ROOTDIRS')) {
    define('PLUGIN_ROOTDIRS', '');
}
if (!defined('PHPMAILERHOST')) {
    define('PHPMAILERHOST', '');
}
if (!defined('MANUALLY_PROCESS_QUEUE')) {
    define('MANUALLY_PROCESS_QUEUE', 1);
}
if (!defined('PROCESSCAMPAIGNS_PARALLEL')) {
    define('PROCESSCAMPAIGNS_PARALLEL', false);
}
if (!defined('CHECK_SESSIONIP')) {
    define('CHECK_SESSIONIP', 1);
}
if (!defined('FILESYSTEM_ATTACHMENTS')) {
    define('FILESYSTEM_ATTACHMENTS', 0);
}
if (!defined('MIMETYPES_FILE')) {
    define('MIMETYPES_FILE', '/etc/mime.types');
}
if (!defined('DEFAULT_MIMETYPE')) {
    define('DEFAULT_MIMETYPE', 'application/octet-stream');
}
if (!defined('USE_REPETITION')) {
    define('USE_REPETITION', 0);
}
if (!defined('USE_EDITMESSAGE')) {
    define('USE_EDITMESSAGE', 0);
}
if (!defined('FCKIMAGES_DIR')) {
    define('FCKIMAGES_DIR', 'uploadimages');
}
if (!defined('UPLOADIMAGES_DIR')) {
    define('UPLOADIMAGES_DIR', 'images');
}
if (!defined('USE_MANUAL_TEXT_PART')) {
    define('USE_MANUAL_TEXT_PART', 0);
}
if (!defined('ALLOW_NON_LIST_SUBSCRIBE')) {
    define('ALLOW_NON_LIST_SUBSCRIBE', 0);
}
if (!defined('MAILQUEUE_BATCH_SIZE')) {
    define('MAILQUEUE_BATCH_SIZE', 1000);
}
if (!defined('MAILQUEUE_BATCH_PERIOD')) {
    define('MAILQUEUE_BATCH_PERIOD', 30);
}
if (!defined('MAILQUEUE_THROTTLE')) {
    define('MAILQUEUE_THROTTLE', 0);
}
if (!defined('MAILQUEUE_AUTOTHROTTLE')) {
    define('MAILQUEUE_AUTOTHROTTLE', 0);
}
if (!defined('NAME')) {
    define('NAME', 'phpList');
}
if (!defined('USE_OUTLOOK_OPTIMIZED_HTML')) {
    define('USE_OUTLOOK_OPTIMIZED_HTML', 0);
}
if (!defined('EXPORT_EXCEL')) {
    define('EXPORT_EXCEL', 0);
}
if (!defined('USE_PREPARE')) {
    define('USE_PREPARE', 0);
}
if (!defined('HTMLEMAIL_ENCODING')) {
    define('HTMLEMAIL_ENCODING', 'quoted-printable');
}
if (!defined('TEXTEMAIL_ENCODING')) {
    define('TEXTEMAIL_ENCODING', '7bit');
}
if (!defined('USE_LIST_EXCLUDE')) {
    define('USE_LIST_EXCLUDE', 0);
}
if (!defined('WARN_SAVECHANGES')) {
    define('WARN_SAVECHANGES', 1);
}
if (!defined('STACKED_ATTRIBUTE_SELECTION')) {
    define('STACKED_ATTRIBUTE_SELECTION', 0);
}
if (!defined('ATTRIBUTEVALUE_REORDER_LIMIT')) {
    define('ATTRIBUTEVALUE_REORDER_LIMIT', 100);
}
if (!defined('REMOTE_URL_REFETCH_TIMEOUT')) {
    define('REMOTE_URL_REFETCH_TIMEOUT', 3600);
}
if (!defined('CLICKTRACK')) {
    define('CLICKTRACK', 1);
}
if (!defined('CLICKTRACK_SHOWDETAIL')) {
    define('CLICKTRACK_SHOWDETAIL', 0);
}
if (!defined('USETINYMCEMESG')) {
    define('USETINYMCEMESG', 0);
}
if (!defined('USETINYMCETEMPL')) {
    define('USETINYMCETEMPL', 0);
}
if (!defined('TINYMCEPATH')) {
    define('TINYMCEPATH', '');
}
if (!defined('STATS_INTERVAL')) {
    define('STATS_INTERVAL', 'weekly');
}
if (!defined('USE_DOMAIN_THROTTLE')) {
    define('USE_DOMAIN_THROTTLE', 0);
}
if (!defined('DOMAIN_BATCH_SIZE')) {
    define('DOMAIN_BATCH_SIZE', 1);
}
if (!defined('DOMAIN_BATCH_PERIOD')) {
    define('DOMAIN_BATCH_PERIOD', 120);
}
if (!defined('DOMAIN_AUTO_THROTTLE')) {
    define('DOMAIN_AUTO_THROTTLE', 0);
}
if (!defined('LANGUAGE_SWITCH')) {
    define('LANGUAGE_SWITCH', 1);
}
if (!defined('THEME_SWITCH')) {
    define('THEME_SWITCH', true);
}
//if (!defined('USE_ADVANCED_BOUNCEHANDLING')) define('USE_ADVANCED_BOUNCEHANDLING',1);
if (!defined('DATE_START_YEAR')) {
    define('DATE_START_YEAR', 1900);
}
if (!defined('DATE_END_YEAR')) {
    define('DATE_END_YEAR', 0);
}
if (!defined('ALLOW_IMPORT')) {
    define('ALLOW_IMPORT', 1);
}
if (!defined('EMPTY_VALUE_PREFIX')) {
    define('EMPTY_VALUE_PREFIX', '--');
}
if (!defined('USE_ADMIN_DETAILS_FOR_MESSAGES')) {
    define('USE_ADMIN_DETAILS_FOR_MESSAGES', 1);
}
if (!defined('SEND_ONE_TESTMAIL')) {
    define('SEND_ONE_TESTMAIL', 1);
}
if (!defined('USE_SPAM_BLOCK')) {
    define('USE_SPAM_BLOCK', 1);
}
if (!defined('NOTIFY_SPAM')) {
    define('NOTIFY_SPAM', 1);
}
if (!defined('CLICKTRACK_LINKMAP')) {
    define('CLICKTRACK_LINKMAP', 0);
}
if (!defined('SIGN_WITH_HMAC')) {
    define('SIGN_WITH_HMAC', false);
}
if (!defined('ALWAYS_ADD_USERTRACK')) {
    define('ALWAYS_ADD_USERTRACK', 1);
}
if (!defined('MERGE_DUPLICATES_DELETE_DUPLICATE')) {
    define('MERGE_DUPLICATES_DELETE_DUPLICATE', 1);
}
if (!defined('USE_PERSONALISED_REMOTEURLS')) {
    define('USE_PERSONALISED_REMOTEURLS', 1);
}
if (!defined('USE_LOCAL_SPOOL')) {
    define('USE_LOCAL_SPOOL', 0);
}
if (!defined('SEND_LISTADMIN_COPY')) {
    define('SEND_LISTADMIN_COPY', false);
}
if (!defined('EMAIL_ADDRESS_VALIDATION_LEVEL')) {
    define('EMAIL_ADDRESS_VALIDATION_LEVEL', 3);
}
if (!defined('BLACKLIST_EMAIL_ON_BOUNCE')) {
    define('BLACKLIST_EMAIL_ON_BOUNCE', 50);
}
if ($bounce_unsubscribe_threshold > BLACKLIST_EMAIL_ON_BOUNCE) {
    $bounce_unsubscribe_threshold = BLACKLIST_EMAIL_ON_BOUNCE;
}

if (!defined('UNBLACKLIST_IN_PROFILE')) {
    define('UNBLACKLIST_IN_PROFILE', false);
}
if (!defined('MANUALLY_PROCESS_BOUNCES')) {
    define('MANUALLY_PROCESS_BOUNCES', 1);
}
if (!defined('PASSWORD_CHANGE_TIMEFRAME')) {
    define('PASSWORD_CHANGE_TIMEFRAME', '1 day');
}
if (!defined('MAX_SENDPROCESSES')) {
    define('MAX_SENDPROCESSES', 1);
}
if (!defined('MAXLIST')) {
    define('MAXLIST', 1);
}
if (!defined('SHOW_LIST_OFALL_SUBSCRIBERS')) {
    define('SHOW_LIST_OFALL_SUBSCRIBERS', false);
}
if (!defined('SENDPROCESS_SERVERNAME')) {
    define('SENDPROCESS_SERVERNAME', 'localhost');
}
if (!defined('CHECK_REFERRER')) {
    define('CHECK_REFERRER', true);
}
// if (!defined('PHPMAILER_PATH')) define ('PHPMAILER_PATH',dirname(__FILE__) . '/PHPMailer/PHPMailerAutoload.php');
// if (!defined('PHPMAILER_PATH')) define ('PHPMAILER_PATH',dirname(__FILE__) . '/PHPMailer_v5.1/class.phpmailer.php');
if (!defined('DB_TRANSLATION')) {
    define('DB_TRANSLATION', 0);
}
if (!defined('MAX_PROCESS_MESSAGE')) {
    define('MAX_PROCESS_MESSAGE', 5);
} //# how many campaigns to work on at the same time
if (!defined('ALLOW_DELETEBOUNCE')) {
    define('ALLOW_DELETEBOUNCE', 1);
}
if (!defined('REPORT_DELETED_BOUNCES')) {
    define('REPORT_DELETED_BOUNCES', 0);
}
if (!defined('MESSAGE_SENDSTATUS_INACTIVETHRESHOLD')) {
    define('MESSAGE_SENDSTATUS_INACTIVETHRESHOLD', 120);
}
if (!defined('MESSAGE_SENDSTATUS_SAMPLETIME')) {
    define('MESSAGE_SENDSTATUS_SAMPLETIME', 600);
}
if (!defined('SEND_QUEUE_PROCESSING_REPORT')) {
    define('SEND_QUEUE_PROCESSING_REPORT', true);
}
if (!defined('MAX_AVATAR_SIZE')) {
    define('MAX_AVATAR_SIZE', 2000);
}
if (!defined('DEFAULT_MESSAGEAGE')) {
    define('DEFAULT_MESSAGEAGE', 604800);
} //# 7 days in seconds
if (!defined('ADD_EMAIL_THROTTLE')) {
    define('ADD_EMAIL_THROTTLE', 1);
} //# seconds between addemail ajax requests
if (!defined('SENDTEST_THROTTLE')) {
    define('SENDTEST_THROTTLE', 1);
} //# seconds between send test
if (!defined('SENDTEST_MAX')) {
    define('SENDTEST_MAX', 999);
} //# max number of emails in a send test
if (!defined('RESETSTATS_MAX')) {
    define('RESETSTATS_MAX', 10);
} //# do not reset statistics if more than this number have been sent
if (!defined('MAX_PROCESSQUEUE_TIME')) {
    define('MAX_PROCESSQUEUE_TIME', 99999);
}
if (!defined('LANGUAGE_AUTO_UPDATE')) {
    define('LANGUAGE_AUTO_UPDATE', true);
}
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 1800);
}
if (!defined('MAX_MAILSIZE')) {
    define('MAX_MAILSIZE', 209715200);
} // in bytes, 200Mb
if (!defined('INTERFACELIB')) {
    define('INTERFACELIB', 1);
}
if (!defined('PHPMAILERBLASTHOST') && defined('PHPMAILERHOST')) {
    define('PHPMAILERBLASTHOST', PHPMAILERHOST);
}
if (!defined('PHPMAILERBLASTPORT') && defined('PHPMAILERPORT')) {
    define('PHPMAILERBLASTPORT', PHPMAILERPORT);
}
if (!defined('PHPMAILERTESTHOST') && defined('PHPMAILERHOST')) {
    define('PHPMAILERTESTHOST', PHPMAILERHOST);
}
if (!defined('PHPMAILER_SECURE')) {
    define('PHPMAILER_SECURE', 'auto');
}
if (!defined('PHPMAILER_SMTP_DEBUG')) {
    define('PHPMAILER_SMTP_DEBUG', 0);
}
if (!defined('POP_BEFORE_SMTP')) {
    define('POP_BEFORE_SMTP', '');
}
if (!defined('POPBEFORESMTP_DEBUG')) {
    define('POPBEFORESMTP_DEBUG', '');
}
if (!defined('USERSPAGE_MAX')) {
    define('USERSPAGE_MAX', 1000);
}
 if (!defined('GOOGLE_SENDERID')) {
    define('GOOGLE_SENDERID', '');
}
// if false, it will disable the automatic updater.
if (!defined ('ALLOW_UPDATER')){
    define('ALLOW_UPDATER', true);
}
if (!defined ('USE_REPLY_TO')){
    define('USE_REPLY_TO', false);
}
if (!isset($plugins_disabled) || !is_array($plugins_disabled)) {
    $plugins_disabled = array();
}

if (!isset($GLOBALS['installation_name'])) {
    $GLOBALS['installation_name'] = 'phpList';
}
if (!defined('SESSIONNAME')) {
    define('SESSIONNAME', 'phpList'.$GLOBALS['installation_name'].'session');
}
//# this doesn't yet work with the FCKEditor
//ini_set('session.name',str_replace(' ','',SESSIONNAME));

if (!defined('USE_AMAZONSES')) {
    define('USE_AMAZONSES', defined('AWS_ACCESSKEYID') && AWS_ACCESSKEYID && function_exists('curl_init'));
}
if (!defined('AWS_POSTURL')) {
    define('AWS_POSTURL', 'https://email.us-east-1.amazonaws.com/');
}
if (!defined('PHPLIST_POWEREDBY_URLROOT')) {
    if ($GLOBALS['public_scheme'] == 'https') {
        define('PHPLIST_POWEREDBY_URLROOT', 'https://d3u7tsw7cvar0t.cloudfront.net/images');
    } else {
        define('PHPLIST_POWEREDBY_URLROOT', 'http://powered.phplist.com/images');
    }
}

if (!isset($allowed_referrers) || !is_array($allowed_referrers)) {
    $allowed_referrers = array();
}
if (!defined('ACCESS_CONTROL_ALLOW_ORIGIN')) {
    define('ACCESS_CONTROL_ALLOW_ORIGIN', $GLOBALS['scheme'].'://'.$_SERVER['HTTP_HOST']);
}

if (!defined('PREFERENCEPAGE_SHOW_PRIVATE_LISTS')) {
    define('PREFERENCEPAGE_SHOW_PRIVATE_LISTS', false);
}
//https://mantis.phplist.com/view.php?id=15603
if (!defined('WORDWRAP_HTML')) {
    define('WORDWRAP_HTML', 0);
}
if (!defined('USE_PRECEDENCE_HEADER')) {
    define('USE_PRECEDENCE_HEADER', true);
}
if (!defined('RFC_DIRECT_DELIVERY')) {
    define('RFC_DIRECT_DELIVERY', false);
}  //# Request for Confirmation, delivery with SMTP

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/PEAR');
$GLOBALS['has_curl'] = function_exists('curl_init');

$GLOBALS['jQuery'] = 'jquery-3.3.1.min.js';

$system_tmpdir = ini_get('upload_tmp_dir');
if (!isset($GLOBALS['tmpdir']) && !empty($system_tmpdir)) {
    $GLOBALS['tmpdir'] = $system_tmpdir;
}
if (!isset($GLOBALS['tmpdir'])) {
    $GLOBALS['tmpdir'] = '/tmp';
}
if (!is_dir($GLOBALS['tmpdir']) || !is_writable($GLOBALS['tmpdir']) && !empty($system_tmpdir)) {
    $GLOBALS['tmpdir'] = $system_tmpdir;
}
if (!isset($attachment_repository)) {
    $attachment_repository = $tmpdir;
}

if (isset($pageroot)) {
    if ($pageroot == '/') {
        $pageroot = '';
    }
} else {
    $pageroot = '/lists';
}
// as the "admin" in adminpages is hardcoded, don't put it in the config file
$adminpages = $GLOBALS['pageroot'].'/admin';

$GLOBALS['homepage'] = 'home';

if (!isset($systemroot)) {
    $systemroot = dirname(__FILE__);
}
if (!isset($documentRoot)) {
    $documentRoot = $_SERVER['DOCUMENT_ROOT'];
}
if (!defined('FORWARD_ALTERNATIVE_CONTENT')) {
    define('FORWARD_ALTERNATIVE_CONTENT', 0);
}
if (!defined('KEEPFORWARDERATTRIBUTES')) {
    define('KEEPFORWARDERATTRIBUTES', 0);
}
if (!defined('FORWARD_EMAIL_COUNT')) {
    define('FORWARD_EMAIL_COUNT', 1);
}
if (!defined('FORWARD_FRIEND_COUNT_ATTRIBUTE')) {
    define('FORWARD_FRIEND_COUNT_ATTRIBUTE', 0);
}
//# when click track links are detected, block sending
//# if false, will only show warning. For now defaulting to false, but may change that later
if (!defined('BLOCK_PASTED_CLICKTRACKLINKS')) {
    define('BLOCK_PASTED_CLICKTRACKLINKS', false);
}

if (FORWARD_EMAIL_COUNT < 1) {
    echo 'Config Error: FORWARD_EMAIL_COUNT must be > (int) 0';
    exit;
}
// allows FORWARD_EMAIL_COUNT forwards per user per period in mysql interval terms
if (!defined('FORWARD_EMAIL_PERIOD')) {
    define('FORWARD_EMAIL_PERIOD', '1 minute');
}
if (!defined('FORWARD_PERSONAL_NOTE_SIZE')) {
    define('FORWARD_PERSONAL_NOTE_SIZE', 0);
}
if (!defined('EMBEDUPLOADIMAGES')) {
    define('EMBEDUPLOADIMAGES', 0);
}
if (!defined('EMBEDEXTERNALIMAGES')) {
    define('EMBEDEXTERNALIMAGES', false);
}
if (!defined('IMPORT_FILESIZE')) {
    define('IMPORT_FILESIZE', 5);
}
if (!defined('SMTP_TIMEOUT')) {
    define('SMTP_TIMEOUT', 5);
}
//# experimental, mark mails "todo" in the DB and process the "todo" list, to avoid the user query being run every queue run
if (!defined('MESSAGEQUEUE_PREPARE')) {
    //# with a multi-process config, we need the queue prepare mechanism and memcache
    if (MAX_SENDPROCESSES > 1) {
        define('MESSAGEQUEUE_PREPARE', true);
    } else {
        define('MESSAGEQUEUE_PREPARE', false);
    }
}
if (!defined('USE_PHPMAILER6')) {
    define('USE_PHPMAILER6', false);
}
if (!isset($GLOBALS['export_mimetype'])) {
    $GLOBALS['export_mimetype'] = 'application/csv';
}
if (!isset($GLOBALS['admin_auth_module'])) {
    $GLOBALS['admin_auth_module'] = 'phplist_auth.inc';
}
// unset the default admin_auth_module, to use the plugin version
if ($GLOBALS['admin_auth_module'] == 'phplist_auth.inc') {
    $GLOBALS['admin_auth_module'] = '';
}
if (!isset($GLOBALS['noteditableconfig'])) {
    $GLOBALS['noteditableconfig'] = array();
}
if (!defined('WORKAROUND_OUTLOOK_BUG') && defined('USE_CARRIAGE_RETURNS')) {
    define('WORKAROUND_OUTLOOK_BUG', USE_CARRIAGE_RETURNS);
}
if (!isset($GLOBALS['blacklist_gracetime'])) {
    $GLOBALS['blacklist_gracetime'] = 5;
}
if (!isset($GLOBALS['message_envelope'])) {
    $GLOBALS['message_envelope'] = '';
}

if (!isset($GLOBALS['pageheader']) || !is_array($GLOBALS['pageheader'])) {
    $GLOBALS['pageheader'] = array();
}
if (!isset($GLOBALS['pagefooter']) || !is_array($GLOBALS['pagefooter'])) {
    $GLOBALS['pagefooter'] = array();
}
if (!isset($GLOBALS['check_for_host'])) {
    $GLOBALS['check_for_host'] = 0;
}

//# experimental, use minified JS and CSS
if (!defined('USE_MINIFIED_ASSETS')) {
    define('USE_MINIFIED_ASSETS', true);
}
$firstInstallButton = '';

//# set up a memcached global object, and test it
$GLOBALS['MC'] = null;

if (defined('MEMCACHED')) {
    include_once dirname(__FILE__).'/class.memcached.php';
    if (class_exists('phpListMC')) {
        $GLOBALS['MC'] = new phpListMC();
        list($mc_server, $mc_port) = explode(':', MEMCACHED);
        $MC->addServer($mc_server, $mc_port);

        /* check that the MC connection is ok
        $MC->add('Hello','World');
        $test = $MC->get('Hello');
        if ($test != 'World') {
          unset($MC);
        }
        */
    }
}

//# global counters array to keep track of things
$counters = array(
    'campaign'                        => 0,
    'num_users_for_message'           => 0,
    'batch_count'                     => 0,
    'batch_total'                     => 0,
    'sendemail returned false total'  => 0,
    'send blocked by domain throttle' => 0,
    'add attachment error'            => 0,
    'sendemail returned false'        => 0,
    'sentastest'                      => 0,
    'invalid'                         => 0,
    'failed_sent'                     => 0,
    'sent'                            => 0,
    'num_per_batch'                   => 0,
);

$repetitionLabels = array(
    60    => 'hour',  // can't use s() here yet
    1440  => 'day',
    10080 => 'week',
    20160 => 'fortnight',
    40320 => 'four weeks',
);

$LANGUAGES = array();
$THEMES = array();

$GLOBALS['disallowpages'] = array();
// list of pages and categorisation in the system
//# old version
$system_pages = array(
    'system' => array(
        'adminattributes' => 'none',
        'attributes'      => 'none',
        'upgrade'         => 'none',
        'configure'       => 'none',
        'spage'           => 'owner',
        'spageedit'       => 'owner',
        'defaultconfig'   => 'none',
        'defaults'        => 'none',
        'initialise'      => 'none',
        'bounces'         => 'none',
        'bounce'          => 'none',
        'processbounces'  => 'none',
        'eventlog'        => 'none',
        'reconcileusers'  => 'none',
        'getrss'          => 'owner',
        'viewrss'         => 'owner',
        'purgerss'        => 'none',
        'setup'           => 'none',
        'dbcheck'         => 'none',

    ),
    'list' => array(
        'list'     => 'owner',
        'editlist' => 'owner',
        'members'  => 'owner',
    ),
    'user' => array(
        'user'           => 'none',
        'users'          => 'none',
        'dlusers'        => 'none',
        'editattributes' => 'none',
        'usercheck'      => 'none',
        'import1'        => 'none',
        'import2'        => 'none',
        'import3'        => 'none',
        'import4'        => 'none',
        'import'         => 'none',
        'export'         => 'none',
        'massunconfirm'  => 'none',

    ),
    'message' => array(
        'message'      => 'owner',
        'messages'     => 'owner',
        'processqueue' => 'none',
        'send'         => 'owner',
        'preparesend'  => 'none',
        'sendprepared' => 'all',
        'template'     => 'none',
        'templates'    => 'none',
    ),
    'clickstats' => array(
        'statsmgt'      => 'owner',
        'mclicks'       => 'owner',
        'uclicks'       => 'owner',
        'userclicks'    => 'owner',
        'mviews'        => 'owner',
        'statsoverview' => 'owner',

    ),
    'admin' => array(
        'admins' => 'none',
        'admin'  => 'owner',
    ),
);
