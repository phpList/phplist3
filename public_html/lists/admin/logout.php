<?php
require_once dirname(__FILE__).'/accesscheck.php';

foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
  $plugin->logout();
}

$_SESSION["adminloggedin"] = "";
$_SESSION["logindetails"] = "";
session_destroy();
