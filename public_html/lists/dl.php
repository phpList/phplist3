<?php

ob_start();
$er = error_reporting(0);

require_once dirname(__FILE__).'/admin/inc/unregister_globals.php';
require_once dirname(__FILE__).'/admin/inc/magic_quotes.php';
//# none of our parameters can contain html for now
$_GET = removeXss($_GET);
$_POST = removeXss($_POST);
$_REQUEST = removeXss($_REQUEST);

if (isset($_SERVER['ConfigFile']) && is_file($_SERVER['ConfigFile'])) {
    include $_SERVER['ConfigFile'];
} elseif (is_file('config/config.php')) {
    include 'config/config.php';
} else {
    echo "Error, cannot find config file\n";
    exit;
}

require_once dirname(__FILE__).'/admin/init.php';
if (isset($GLOBALS['developer_email']) && $GLOBALS['show_dev_errors']) {
    error_reporting(E_ALL);
    ini_set('show_errors', 'on');
} else {
    error_reporting(0);
}

$GLOBALS['database_module'] = basename($GLOBALS['database_module']);
$GLOBALS['language_module'] = basename($GLOBALS['language_module']);

require_once dirname(__FILE__).'/admin/'.$GLOBALS['database_module'];

// load default english and language
include_once dirname(__FILE__).'/admin/defaultFrontendTexts.php';
// Allow customisation per installation
if (is_file($_SERVER['DOCUMENT_ROOT'].'/'.$GLOBALS['language_module'])) {
    include_once $_SERVER['DOCUMENT_ROOT'].'/'.$GLOBALS['language_module'];
}

require_once dirname(__FILE__).'/admin/inc/random_compat/random.php';
include_once dirname(__FILE__).'/admin/languages.php';
require_once dirname(__FILE__).'/admin/defaultconfig.php';
require_once dirname(__FILE__).'/admin/connect.php';
include_once dirname(__FILE__).'/admin/lib.php';

$id = sprintf('%d', $_GET['id']);
$userid = 0;
if (isset($_GET['uid'])) {
    $uid = preg_replace('/\W/', '', $_GET['uid']);
    ## @@TODO, add a check that this subscriber was actually sent any mails with this attachment. We're only checking that the subscriber exists
    $userid = Sql_Fetch_Row_Query(sprintf('select id from %s where uniqid = "%s"',$GLOBALS['tables']['user'], $uid));
}

## this will have an issue in upgrade. When a campaign has been sent on an older version without the "uid", it will stop providing the document.
## and throw a 404. This only applies to attachments in text versions of the campaigns (very limited audience, presumably)
if (empty($userid)) {
    FileNotFound();
}

$data = Sql_Fetch_Row_Query("select filename,mimetype,remotefile,description,size from {$tables['attachment']} where id = $id");
if (is_file($attachment_repository.'/'.$data[0])) {
    $file = $attachment_repository.'/'.$data[0];
} elseif (is_file($data[2]) && filesize($data[2])) {
    $file = $data[2];
} else {
    $file = '';
}

if ($file && is_file($file)) {
    ob_end_clean();
    if ($data[1]) {
        header("Content-type: $data[1]");
    } else {
        header('Content-type: application/octetstream');
    }

    list($fname, $ext) = explode('.', basename($data[2]));
    $undirect_mime_types = array('pdf');
    $the_mime_types = explode("/", $data[1]);
    if ($the_mime_types[0] == 'application' && in_array($the_mime_types[1], $undirect_mime_types))
        header ('Content-Disposition: inline; filename="'.basename($data[2]).'"');
    else
        header('Content-Disposition: attachment; filename="'.basename($data[2]).'"');
    if ($data[4]) {
        $size = $data[4];
    } else {
        $size = filesize($file);
    }

    if ($size) {
        header('Content-Length: '.$size);
        $fsize = $size;
    } else {
        $fsize = 4096;
    }
    $fp = fopen($file, 'r');
    while ($buffer = fread($fp, $fsize)) {
        echo $buffer;
        flush();
    }
    fclose($fp);
    exit;
} else {
    FileNotFound();
}
