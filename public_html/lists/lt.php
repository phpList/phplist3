<?php

ob_start();
$er = error_reporting(0);
require_once dirname(__FILE__) . '/admin/inc/unregister_globals.php';
require_once dirname(__FILE__) . '/admin/inc/magic_quotes.php';

## none of our parameters can contain html for now
$_GET = removeXss($_GET);
$_POST = removeXss($_POST);
$_REQUEST = removeXss($_REQUEST);

if (isset($_SERVER['ConfigFile']) && is_file($_SERVER['ConfigFile'])) {
    include $_SERVER['ConfigFile'];
} elseif (is_file('config/config.php')) {
    include 'config/config.php';
} else {
    print "Error, cannot find config file\n";
    exit;
}

require_once dirname(__FILE__) . '/admin/init.php';

$GLOBALS['database_module'] = basename($GLOBALS['database_module']);
$GLOBALS['language_module'] = basename($GLOBALS['language_module']);

require_once dirname(__FILE__) . '/admin/' . $GLOBALS['database_module'];

# load default english and language
include_once dirname(__FILE__) . '/texts/english.inc';
# Allow customisation per installation
if (is_file($_SERVER['DOCUMENT_ROOT'] . '/' . $GLOBALS['language_module'])) {
    include_once $_SERVER['DOCUMENT_ROOT'] . '/' . $GLOBALS['language_module'];
}

include_once dirname(__FILE__) . '/admin/languages.php';
require_once dirname(__FILE__) . '/admin/defaultconfig.php';
require_once dirname(__FILE__) . '/admin/connect.php';
include_once dirname(__FILE__) . '/admin/lib.php';

$id = sprintf('%s', $_GET['id']);
if ($id != $_GET['id']) {
    print 'Invalid Request';
    exit;
}

$track = base64_decode($id);
$track = $track ^ XORmask;
$elements = explode('|', $track);
if (sizeof($elements) != 4) {
    FileNotFound();
}
$msgtype = substr($elements[0],0,1);
$userid = sprintf('%d', $elements[3]);
$fwdid = sprintf('%d', $elements[1]);
$messageid = sprintf('%d', $elements[2]);

$linkdata = Sql_Fetch_array_query(sprintf('select * from %s where id = %d', $GLOBALS['tables']['linktrack_forward'],
    $fwdid));

if (!$fwdid || $linkdata['id'] != $fwdid || !$userid || !$messageid) {
    ## try the old table to avoid breaking links
    $linkdata = Sql_Fetch_array_query(sprintf('select * from %s where linkid = %d and userid = %d and messageid = %d',
        $GLOBALS['tables']['linktrack'], $fwdid, $userid, $messageid));
    if (!empty($linkdata['forward'])) {
        ## we're not recording clicks, but at least links in older newsletters won't break.
        header('HTTP/1.0 303 See Other', true, 303);
        header('Location: ' . $linkdata['forward']);
        exit;
    }
#  echo 'Invalid Request';
    # maybe some logging?
    FileNotFound();
    exit;
}

## verify that this subscriber actually received this message, otherwise they're not allowed
## allow admins on test messages
$allowed = Sql_Fetch_Row_Query(sprintf('select userid from %s where userid = %d and messageid = %d',
    $GLOBALS['tables']['usermessage'], $userid, $messageid));
if (($allowed[0] != $userid || !$allowed[0]) && empty($_SESSION['adminloggedin'])) {
    ## has this campaign be sent yet, if not, it's most likely an admin testing
    $sent = Sql_Fetch_Row_Query(sprintf('select count(userid) from %s where messageid = %d',
        $GLOBALS['tables']['usermessage'], $messageid));
    if (empty($sent[0])) {
        FileNotFound('<br/><i>' . s('Links in test campaigns only work when you are logged in as an administrator.') . '</i><br/>');
    } else {
        FileNotFound();
    }
}

## hmm a bit heavy to use here @@@optimise
$messagedata = loadMessageData($messageid);
$trackingcode = '';
#print "$track<br/>";
#print "User $userid, Mess $messageid, Link $linkid";

$ml = Sql_Fetch_Array_Query(sprintf('select * from %s where messageid = %d and forwardid = %d',
    $GLOBALS['tables']['linktrack_ml'], $messageid, $fwdid));

if (empty($ml['firstclick'])) {
    Sql_query(sprintf('update %s set firstclick = now(),latestclick = now(),clicked = clicked + 1 where forwardid = %d and messageid = %d',
        $GLOBALS['tables']['linktrack_ml'], $fwdid, $messageid));
} else {
    Sql_query(sprintf('update %s set clicked = clicked + 1, latestclick = now() where forwardid = %d and messageid = %d',
        $GLOBALS['tables']['linktrack_ml'], $fwdid, $messageid));
}

if ($msgtype == 'H') {
    Sql_query(sprintf('update %s set htmlclicked = htmlclicked + 1 where forwardid = %d and messageid = %d',
        $GLOBALS['tables']['linktrack_ml'], $fwdid, $messageid));
    $trackingcode = 'utm_source=phplist' . $messageid . '&utm_medium=email&utm_content=HTML&utm_campaign=' . urlencode($messagedata['subject']);
} elseif ($msgtype == 'T') {
    Sql_query(sprintf('update %s set textclicked = textclicked + 1 where forwardid = %d and messageid = %d',
        $GLOBALS['tables']['linktrack_ml'], $fwdid, $messageid));
    $trackingcode = 'utm_source=phplist' . $messageid . '&utm_medium=email&utm_content=text&utm_campaign=' . urlencode($messagedata['subject']);
} else {
    ## we don't know any other option
    FileNotFound();
    exit;
}

$viewed = Sql_Fetch_Row_query(sprintf('select viewed from %s where messageid = %d and userid = %d',
    $GLOBALS['tables']['usermessage'], $messageid, $userid));
if (!$viewed[0]) {
    Sql_Query(sprintf('update %s set viewed = now() where messageid = %d and userid = %d',
        $GLOBALS['tables']['usermessage'], $messageid, $userid));
    Sql_Query(sprintf('update %s set viewed = viewed + 1 where id = %d',
        $GLOBALS['tables']['message'], $messageid));
}

$uml = Sql_Fetch_Array_Query(sprintf('select * from %s where messageid = %d and forwardid = %d and userid = %d',
    $GLOBALS['tables']['linktrack_uml_click'], $messageid, $fwdid, $userid));

if (empty($uml['firstclick'])) {
    Sql_query(sprintf('insert into %s set firstclick = now(), forwardid = %d, messageid = %d, userid = %d',
        $GLOBALS['tables']['linktrack_uml_click'], $fwdid, $messageid, $userid));
}
Sql_query(sprintf('update %s set clicked = clicked + 1, latestclick = now() where forwardid = %d and messageid = %d and userid = %d',
    $GLOBALS['tables']['linktrack_uml_click'], $fwdid, $messageid, $userid));

if ($msgtype == 'H') {
    Sql_query(sprintf('update %s set htmlclicked = htmlclicked + 1 where forwardid = %d and messageid = %d and userid = %d',
        $GLOBALS['tables']['linktrack_uml_click'], $fwdid, $messageid, $userid));
} elseif ($msgtype == 'T') {
    Sql_query(sprintf('update %s set textclicked = textclicked + 1 where forwardid = %d and messageid = %d and userid = %d',
        $GLOBALS['tables']['linktrack_uml_click'], $fwdid, $messageid, $userid));
}

$url = $linkdata['url'];
if ($linkdata['personalise']) {
    $uid = Sql_Fetch_Row_Query(sprintf('select uniqid from %s where id = %d', $GLOBALS['tables']['user'], $userid));
    if ($uid[0]) {
        if (strpos($url, '?')) {
            $url .= '&uid=' . $uid[0];
        } else {
            $url .= '?uid=' . $uid[0];
        }
    }
}
#print "$url<br/>";
if (!isset($_SESSION['entrypoint'])) {
    $_SESSION['entrypoint'] = $url;
}

if (!empty($messagedata['google_track'])) {
    ## take off existing tracking code, if found
    if (strpos($url, 'utm_medium') !== false) {
        $url = preg_replace('/utm_(\w+)\=[^&]+/', '', $url);
    }
    ## 16894 make sure to keep the fragment value at the end of the URL
    if (strpos($url, '#')) {
        list($tmplink, $fragment) = explode('#', $url);
        $url = $tmplink;
        unset($tmplink);
        $fragment = '#' . $fragment;
    } else {
        $fragment = '';
    }
    if (strpos($url, '?')) {
        $url = $url . '&' . $trackingcode . $fragment;
    } else {
        $url = $url . '?' . $trackingcode . $fragment;
    }
}

header('HTTP/1.0 303 See Other', true, 303); ## use 303, because Location only uses 302, which gets indexed
header('Location: ' . $url);
exit;
