<?php

if (!defined('PHPLISTINIT')) {
    //	print backtrace();
    echo 'Invalid Request';
    exit;
}

function accessLevel($page)
{
    global $tables, $access_levels;
    if (!empty($GLOBALS['firsttime']) || !empty($_SESSION['firstinstall'])) {
      return 'all';
    }

    if (isSuperUser()) {
        return 'all';
    }
    if (!isset($_SESSION['adminloggedin'])) {
        return "none";
    }
    if (!is_array($_SESSION['logindetails'])) {
        return "none";
    }

    //# for non-supers we only allow owner views
    //# this is likely to need tweaking
    return 'owner';
}

function requireAccessLevel($page, $level)
{
    $adminlevel = accessLevel($page);

    return $adminlevel == $level;
}

function isSuperUser()
{
    //# for now mark webbler admins superuser
    if (defined('WEBBLER') || defined('IN_WEBBLER')) {
        return true;
    }
    if (!empty($GLOBALS['firsttime'])) {
      return true;
    }
    if (!empty($GLOBALS['commandline'])) {
        return true;
    }
    global $tables;
    $issuperuser = 0;
//  if (!isset($_SESSION["adminloggedin"])) return 0;
    // if (!is_array($_SESSION["logindetails"])) return 0;
    if (isset($_SESSION['logindetails']['superuser'])) {
        return $_SESSION['logindetails']['superuser'];
    }
    if (isset($_SESSION['logindetails']['id'])) {
        if (is_object($GLOBALS['admin_auth'])) {
            $issuperuser = $GLOBALS['admin_auth']->isSuperUser($_SESSION['logindetails']['id']);
        } else {
            $req = Sql_Fetch_Row_Query(sprintf('select superuser from %s where id = %d', $tables['admin'],
                $_SESSION['logindetails']['id']));
            $issuperuser = $req[0];
        }
        $_SESSION['logindetails']['superuser'] = $issuperuser;
    }

    return !empty($issuperuser);
}
