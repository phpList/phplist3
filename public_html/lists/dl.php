<?php
ob_start();
$er = error_reporting(0); # some ppl have warnings on

require_once dirname(__FILE__) .'/admin/commonlib/lib/unregister_globals.php';
require_once dirname(__FILE__) .'/admin/commonlib/lib/magic_quotes.php';
require_once dirname(__FILE__).'/admin/init.php';
## none of our parameters can contain html for now
$_GET = removeXss($_GET);
$_POST = removeXss($_POST);
$_REQUEST = removeXss($_REQUEST);

if ($_SERVER["ConfigFile"] && is_file($_SERVER["ConfigFile"])) {
#  print '<!-- using '.$_SERVER["ConfigFile"].'-->'."\n";
  include $_SERVER["ConfigFile"];
} elseif (is_file("config/config.php")) {
#  print '<!-- using config/config.php -->'."\n";
  include "config/config.php";
} else {
  print "Error, cannot find config file\n";
  exit;
}
#error_reporting($er);
if (isset($GLOBALS["developer_email"]) && $GLOBALS['show_dev_errors']) {
  error_reporting(E_ALL);
} else {
  error_reporting(0);
}

require_once dirname(__FILE__).'/admin/'.$GLOBALS["database_module"];
require_once dirname(__FILE__)."/texts/english.inc";
include_once dirname(__FILE__)."/texts/".$GLOBALS["language_module"];
require_once dirname(__FILE__)."/admin/defaultconfig.inc";
require_once dirname(__FILE__).'/admin/connect.php';
include_once dirname(__FILE__)."/admin/languages.php";

$id = sprintf('%d',$_GET["id"]);

$data = Sql_Fetch_Row_Query("select filename,mimetype,remotefile,description,size from {$tables["attachment"]} where id = $id");
if (is_file($attachment_repository. "/".$data[0])) {
  $file = $attachment_repository. "/".$data[0];
} elseif (is_file($data[2]) && filesize($data[2])) {
  $file = $data[2];
} else {
  $file = "";
}

if ($file && is_file($file)) {
  ob_end_clean();
  if ($data[1]) {
    header("Content-type: $data[1]");
  } else {
    header("Content-type: application/octetstream");
  }

  list($fname,$ext) = explode(".",basename($data[2]));
  header ('Content-Disposition: attachment; filename="'.basename($data[2]).'"');
  if ($data[4])
    $size = $data[4];
  else
    $size = filesize($file);

  if ($size) {
    header ("Content-Length: " . $size);
    $fsize = $size;
  }
  else
    $fsize = 4096;
  $fp = fopen($file,"r");
  while ($buffer = fread($fp,$fsize)) {
    print $buffer;
  flush();
  }
  fclose ($fp);
  exit;
} else {
  FileNotFound();
}

