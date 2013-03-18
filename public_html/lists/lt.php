<?php
ob_start();
$er = error_reporting(0); # some ppl have warnings on
require_once dirname(__FILE__) .'/admin/commonlib/lib/unregister_globals.php';
require_once dirname(__FILE__) .'/admin/commonlib/lib/magic_quotes.php';

## none of our parameters can contain html for now
$_GET = removeXss($_GET);
$_POST = removeXss($_POST);
$_REQUEST = removeXss($_REQUEST);

if ($_SERVER["ConfigFile"] && is_file($_SERVER["ConfigFile"])) {
  include $_SERVER["ConfigFile"];
} elseif (is_file("config/config.php")) {
  include "config/config.php";
} else {
  print "Error, cannot find config file\n";
  exit;
}
#error_reporting($er);
require_once dirname(__FILE__).'/admin/init.php';
require_once dirname(__FILE__).'/admin/'.$GLOBALS["database_module"];
require_once dirname(__FILE__)."/texts/english.inc";
include_once dirname(__FILE__)."/texts/".$GLOBALS["language_module"];
include_once dirname(__FILE__)."/admin/languages.php";
require_once dirname(__FILE__)."/admin/defaultconfig.inc";
require_once dirname(__FILE__).'/admin/connect.php';
include_once dirname(__FILE__)."/admin/lib.php";
if (isset($GLOBALS["developer_email"]) && $GLOBALS['show_dev_errors']) {
  error_reporting(E_ALL);
} else {
  error_reporting(0);
}

$id = sprintf('%s',$_GET['id']);
if ($id != $_GET['id']) {
  print "Invalid Request";
  exit;
}

$track = base64_decode($id);
$track = $track ^ XORmask;
@list($msgtype,$fwdid,$messageid,$userid) = explode('|',$track);
$userid = sprintf('%d',$userid);
$fwdid = sprintf('%d',$fwdid);
$messageid = sprintf('%d',$messageid);
/*$linkdata = Sql_Fetch_array_query(sprintf('select * from %s where linkid = %d and userid = %d and messageid = %d',
  $GLOBALS['tables']['linktrack'],$linkid,$userid,$messageid));*/
$query = sprintf('select * from %s where id = ?', $GLOBALS['tables']['linktrack_forward']);
$rs = Sql_Query_Params($query, array($fwdid));
$linkdata = Sql_Fetch_array($rs);

if (!$fwdid || $linkdata['id'] != $fwdid || !$userid || !$messageid) {
  FileNotFound();
#  echo 'Invalid Request';
  # maybe some logging?
  exit;
}

## hmm a bit heavy to use here @@@optimise
$messagedata = loadMessageData($messageid);
$trackingcode = '';
#print "$track<br/>";
#print "User $userid, Mess $messageid, Link $linkid";

$query = sprintf('select * from %s where messageid = ? and forwardid = ?', $GLOBALS['tables']['linktrack_ml']);
$rs = Sql_Query_Params($query, array($messageid, $fwdid));
$ml = Sql_Fetch_Array($rs);

if (empty($ml['firstclick'])) {
  $query = sprintf('update %s set firstclick = current_timestamp, latestclick = current_timestamp, clicked = clicked + 1 where forwardid = ? and messageid = ?', $GLOBALS['tables']['linktrack_ml']);
  Sql_Query_Params($query, array($fwdid, $messageid));
} else {
  $query = sprintf('update %s set clicked = clicked + 1, latestclick = current_timestamp where forwardid = ? and messageid = ?', $GLOBALS['tables']['linktrack_ml']);
  Sql_Query_Params($query, array($fwdid, $messageid));
}

if ($msgtype == 'H') {
  $query = sprintf('update %s set htmlclicked = htmlclicked + 1 where forwardid = ? and messageid = ?', $GLOBALS['tables']['linktrack_ml']);
  Sql_Query_Params($query, array($fwdid, $messageid));
  $trackingcode = 'utm_source=emailcampaign'.$messageid.'&utm_medium=phpList&utm_content=HTMLemail&utm_campaign='.urlencode($messagedata["subject"]);
} elseif ($msgtype == 'T') {
  $query = sprintf('update %s set textclicked = textclicked + 1 where forwardid = ? and messageid = ?', $GLOBALS['tables']['linktrack_ml']);
  Sql_Query_Params($query, array($fwdid, $messageid));
  $trackingcode = 'utm_source=emailcampaign'.$messageid.'&utm_medium=phpList&utm_content=textemail&utm_campaign='.urlencode($messagedata["subject"]);
} 

$query = sprintf('select viewed from %s where messageid = ? and userid = ?', $GLOBALS['tables']['usermessage']);
$rs = Sql_Query_Params($query, array($messageid, $userid));
$viewed = Sql_Fetch_Row($rs);
if (!$viewed[0]) {
  $query = sprintf('update %s set viewed = current_timestamp where messageid = ? and userid = ?', $GLOBALS['tables']['usermessage']);
  Sql_Query_Params($query, array($messageid, $userid));
  $query = sprintf('update %s set viewed = viewed + 1 where id = ?', $GLOBALS['tables']['message']);
  Sql_Query_Params($query, array($messageid));
}

$query = sprintf('select * from %s where messageid = ? and forwardid = ? and userid = ?', $GLOBALS['tables']['linktrack_uml_click']);
$rs = Sql_Query_Params($query, array($messageid, $fwdid, $userid));
$uml = Sql_Fetch_Array($rs);

if (empty($uml['firstclick'])) {
  $query
  = ' insert into ' . $GLOBALS['tables']['linktrack_uml_click']
  . '    (firstclick, forwardid, messageid, userid)'
  . ' values'
  . '    (current_timestamp, ?, ?, ?)';
  Sql_Query_Params($query, array($fwdid, $messageid, $userid));
}
$query = sprintf('update %s set clicked = clicked + 1, latestclick = current_timestamp where forwardid = ? and messageid = ? and userid = ?', $GLOBALS['tables']['linktrack_uml_click']);
Sql_Query_Params($query, array($fwdid, $messageid, $userid));

if ($msgtype == 'H') {
  $query = sprintf('update %s set htmlclicked = htmlclicked + 1 where forwardid = ? and messageid = ? and userid = ?', $GLOBALS['tables']['linktrack_uml_click']);
  Sql_Query_Params($query, array($fwdid, $messageid, $userid));
} elseif ($msgtype == 'T') {
  $query = sprintf('update %s set textclicked = textclicked + 1 where forwardid = ? and messageid = ? and userid = ?', $GLOBALS['tables']['linktrack_uml_click']);
  Sql_Query_Params($query, array($fwdid, $messageid, $userid));
}

$url = $linkdata['url'];
if ($linkdata['personalise']) {
  $query = sprintf('select uniqid from %s where id = ?', $GLOBALS['tables']['user']);
  $rs = Sql_Query_Params($query, array($userid));
  $uid = Sql_Fetch_Row($rs);
  if ($uid[0]) {
    if (strpos($url,'?')) {
      $url .= '&uid='.$uid[0];
    } else {
      $url .= '?uid='.$uid[0];
    }
  }
}
#print "$url<br/>";
if (!isset($_SESSION['entrypoint'])) {
  $_SESSION['entrypoint'] = $url;
}

if (!empty($messagedata['google_track'])) {
  ## take off existing tracking code, if found
  if (strpos($url,'utm_medium') !== false) {
    $url = preg_replace('/utm_(\w+)\=[^&]+/','',$url);
  }
  if (strpos($url,'?')) {
    $url = $url.'&'.$trackingcode;
  } else {
    $url = $url.'?'.$trackingcode;
  }
}

header("Location: " . $url);
exit;
