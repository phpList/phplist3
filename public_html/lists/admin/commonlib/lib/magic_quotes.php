<?php
# experiment, see whether we can correct the magic quotes centrally

function addSlashesArray($array) {
  foreach ($array as $key => $val) {
    if (is_array($val)) {
      $array[$key] = addSlashesArray($val);
    } else {
      $array[$key] = addslashes($val);
    }
  }
  return $array;
}
if (!ini_get("magic_quotes_gpc") || ini_get("magic_quotes_gpc") == "off") {
  $_POST = addSlashesArray($_POST);
  $_GET = addSlashesArray($_GET);
  $_REQUEST = addSlashesArray($_REQUEST);
  $_COOKIE = addSlashesArray($_COOKIE);
}

function removeXss($string) {
  if (is_array($string)) {
    $return = array();
    foreach ($string as $key => $val) {
      $return[removeXss($key)] = removeXss($val);
    }
    return $return;
  }
  #$string = preg_replace('/<script/im','&lt;script',$string);
  $string = htmlspecialchars($string);
  return $string;
}

/*
foreach ($_POST as $key => $val) {
  print "POST: $key = $val<br/>";
}
foreach ($_GET as $key => $val) {
  print "GET: $key = $val<br/>";
}
foreach ($_REQUEST as $key => $val) {
  print "REQ: $key = $val<br/>";
}
foreach ($_REQUEST as $key => $val) {
  print "COOKIE: $key = $val<br/>";
}
*/
?>
