<?php
global $HTTP_POST_VARS,$HTTP_ENV_VARS,$HTTP_GET_VARS,$HTTP_SESSION_VARS;

if ($HTTP_GET_VARS) {
  if (!isset($_GET))
  	$_GET = $HTTP_GET_VARS;
  reset($HTTP_GET_VARS);
  while (list($key,$val) = each ($HTTP_GET_VARS)) {
    $$key = $val;
  }
}

if ($HTTP_POST_VARS) {
  if (!isset($_POST))
  	$_POST = $HTTP_POST_VARS;
  reset($HTTP_POST_VARS);
  while (list($key,$val) = each ($HTTP_POST_VARS)) {
    $$key = $val;
  }
}
$_REQUEST = array_merge($_GET,$_POST);

if ($HTTP_SESSION_VARS) {
  #print "SESSION_VARS";
  if (!is_array($_SESSION))
	  $_SESSION = array();
  reset($HTTP_SESSION_VARS);
  while (list($key,$val) = each ($HTTP_SESSION_VARS)) {
    $_SESSION[$key] = $val;
  }
}
?>
