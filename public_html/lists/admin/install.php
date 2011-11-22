<?php
require("installer/lib/install-texts.inc");
//require("installer/lib/mysql.inc");
require("mysql.inc");
require("installer/lib/steps-lib.inc");
require("languages.php");


@session_start();


// Trata de tomar el paso de la instalacion por POST, sino trae nada, lo intenta por GET
$page     = (isset($_POST["page"]))?$_POST["page"]:"";
$config['head'][0] = '<title>phpList installer</title>';
$submited = (isset($_POST["submited"]))?$_POST["submited"]:"";
$itype    = (isset($_GET["itype"]))?$_GET["itype"]:"";
$inTheSame= 0;


/////// Defining VERSION and NAME of this distribution and INSTALLATION type too ////////
if (is_file(dirname(__FILE__) .'/../../../VERSION')) {
   $fd = fopen (dirname(__FILE__) .'/../../../VERSION', "r");
   while ($line = fscanf ($fd, "%[a-zA-Z0-9,. ]=%[a-zA-Z0-9,. ]")) {
      list ($key, $val) = $line;

      if ($key == "VERSION") $version = $val . "-";
   }
   fclose($fd);
}
else{
   $version = "dev";
}

if (isset($_POST["insttype"]) && $_POST["insttype"] != ""){
 //!isset($_SESSION["installType"]) && $_SESSION["installType"] == ""){
   $_SESSION["installType"] = (isset($_POST["insttype"]) && $_POST["insttype"] != "")?$_POST["insttype"]:"BASIC";
}

define("VERSION",$version.'dev');
if (!defined("NAME")) define("NAME",'phplist');


/////// Defining CONFIG file for this installation ////////
/*
$nameConfigFile = "";

if (isset($_SERVER["ConfigFile"]) && is_file($_SERVER["ConfigFile"])) {
   $nameConfigFile = $_SERVER['ConfigFile'];
}
elseif (is_file("../config/config.php")) {
   $nameConfigFile = "../config/config.php";
}

$myConfigFile = fopen($nameConfigFile, 'a');
if (!isset($myConfigFile) || $myConfigFile == FALSE) {
   $myConfigFile = fopen($nameConfigFile, 'w'); // Try to open
   if (!isset($myConfigFile)) {
      print $GLOBALS["I18N"]->get(sprintf('<div class="wrong">%s (%s)</div>',$GLOBALS['strConfigNoOpen'], $nameConfigFile));
   }
}
*/
//echo $nameConfigFile;
//echo "FILE: $configfile<br/>";


/////// Other definitions ////////
if (!isset($page) || $page == ""){
   $page     = (isset($_GET["page"]))?$_GET["page"]:"";
   $submited = (isset($_GET["submited"]))?$_GET["submited"]:"";
}


///// Variable usada para controlar que lo ingresado como $page sea numerico
$control = ($page * 1)."";


include "pagetop.php";
include("header.inc");

if (!isset($page) || $page == "" || ($control != $page)){
   $page = ($control != $page)?"":$page;

   include("installer/home.php");
}
else{
   if ($page < 3 || ($page == 3 && !$submited))
   echo breadcrumb($page);

   include("installer/install$page.php");
}

//include('installer/lib/footer.inc');
include('footer.inc');
?>
