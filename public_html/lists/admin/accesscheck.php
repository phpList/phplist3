<?php

if (!defined('PHPLISTINIT') && !isset($GLOBALS["installer"])) {
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

  ## for non-supers we only allow owner views
  ## this is likely to need tweaking
  return 'owner';
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

