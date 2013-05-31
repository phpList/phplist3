<?php
ob_start();
$er = error_reporting(0); 
require_once dirname(__FILE__) .'/admin/commonlib/lib/unregister_globals.php';
require_once dirname(__FILE__) .'/admin/commonlib/lib/magic_quotes.php';

## none of our parameters can contain html for now
$_GET = removeXss($_GET);
$_POST = removeXss($_POST);
$_REQUEST = removeXss($_REQUEST);
$_COOKIE = removeXss($_COOKIE);

if (isset($_SERVER["ConfigFile"]) && is_file($_SERVER["ConfigFile"])) {
  include $_SERVER["ConfigFile"];
} elseif (is_file("config/config.php")) {
  include 'config/config.php';
} else {
  print "Error, cannot find config file\n";
  exit;
}

require_once dirname(__FILE__).'/admin/init.php';

$GLOBALS["database_module"] = basename($GLOBALS["database_module"]);
$GLOBALS["language_module"] = basename($GLOBALS["language_module"]);

require_once dirname(__FILE__).'/admin/'.$GLOBALS["database_module"];

# load default english and language
include_once dirname(__FILE__)."/texts/english.inc";
# Allow customisation per installation
if (is_file($_SERVER['DOCUMENT_ROOT'].'/'.$GLOBALS["language_module"])) {
  include_once $_SERVER['DOCUMENT_ROOT'].'/'.$GLOBALS["language_module"];
}

include_once dirname(__FILE__)."/admin/languages.php";
require_once dirname(__FILE__)."/admin/defaultconfig.php";
require_once dirname(__FILE__).'/admin/connect.php';
include_once dirname(__FILE__)."/admin/lib.php";

if (!empty($_GET["u"]) && !empty($_GET["m"])) {
  $_GET['u'] = preg_replace('/\W/','',$_GET['u']);
  $query = sprintf('select id from %s where uniqid = ?', $GLOBALS['tables']['user']);
  $rs = Sql_Query_Params($query, array($_GET['u']));
  $useridrow = Sql_Fetch_Row($rs);
  $userid = $useridrow[0];
  $messageid = sprintf('%d',$_GET['m']);
} elseif (!empty($_GET['x'])) {
  ## new method, that also tracks forward-opens, not active yet.
  $track = base64_decode($_GET['x']);
  $track = $track ^ XORmask;
  @list($userhash,$messageid,$userid) = explode('|',$track);
}

if ($userid) {
  $query
  = ' update %s set viewed = current_timestamp'
  . ' where messageid = ? and userid = ? and status = "sent"';
  $query = sprintf($query, $GLOBALS['tables']['usermessage']);
  
  Sql_Query_Params($query, array($messageid,$userid ));
  if (Sql_Affected_Rows()) {
    $query
    = ' update %s set viewed = viewed + 1'
    . ' where id = ?';
    $query = sprintf($query, $GLOBALS['tables']['message']);
    Sql_Query_Params($query, array($messageid));
  }
}

@ob_end_clean();
header("Content-Type: image/png");
print base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAABGdBTUEAALGPC/xhBQAAAAZQTFRF////AAAAVcLTfgAAAAF0Uk5TAEDm2GYAAAABYktHRACIBR1IAAAACXBIWXMAAAsSAAALEgHS3X78AAAAB3RJTUUH0gQCEx05cqKA8gAAAApJREFUeJxjYAAAAAIAAUivpHEAAAAASUVORK5CYII=');
