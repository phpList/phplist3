<?php

if (!function_exists("checkAccess") && !isset($GLOBALS["installer"])) {
#	print backtrace();
  print "Invalid Request";
  exit;
}

function accessLevel($page) {
  global $tables,$access_levels;
  if (!$GLOBALS["require_login"] || isSuperUser())
    return "all";
  if (!isset($_SESSION["adminloggedin"])) return 0;
  if (!is_array($_SESSION["logindetails"])) return 0;
  # check whether it is a page to protect
  Sql_Query("select id from {$tables["task"]} where page = \"$page\"");
  if (!Sql_Affected_Rows())
    return "all";
  $req = Sql_Query(sprintf('select level from %s,%s where adminid = %d and page = "%s" and %s.taskid = %s.id',
    $tables["task"],$tables["admin_task"],$_SESSION["logindetails"]["id"],$page,$tables["admin_task"],$tables["task"]));
  $row = Sql_Fetch_Row($req);
  return $access_levels[$row[0]];
}

function requireAccessLevel($page,$level) {
  $adminlevel = accessLevel($page);
  return $adminlevel == $level;
}

function isSuperUser() {
  ## for now mark webbler admins superuser
  if (defined('WEBBLER') || defined('IN_WEBBLER')) return 1;
  global $tables;
  $issuperuser = 0;
#  if (!isset($_SESSION["adminloggedin"])) return 0;
 # if (!is_array($_SESSION["logindetails"])) return 0;
  if (isset($_SESSION["logindetails"]["superuser"])) {
    return $_SESSION["logindetails"]["superuser"];
  }
  if (isset($_SESSION["logindetails"]["id"])) {
    if (is_object($GLOBALS["admin_auth"]) ) {
      $issuperuser = $GLOBALS["admin_auth"]->isSuperUser($_SESSION["logindetails"]["id"]);
    } else {
      $query
      = ' select superuser '
      . ' from %s'
      . ' where id = ?';
      $query = sprintf($query, $tables['admin']);
      $req = Sql_Query_Params($query, array($_SESSION['logindetails']['id']));
      $req = Sql_Fetch_Row($req);
      $issuperuser = $req[0];
    }
    $_SESSION["logindetails"]["superuser"] = $issuperuser;
  }
  return $issuperuser;
}
