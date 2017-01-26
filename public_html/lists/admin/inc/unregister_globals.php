<?php

/**
 * Turn register globals off, even if it's on
 * taken from Wordpress.
 *
 * @since 2.2.10
 */
function unregister_GLOBALS()
{
    if (!ini_get('register_globals')) {
        return;
    }

    //# https://mantis.phplist.com/view.php?id=16882
    //# no need to do this on commandline
    if (php_sapi_name() == 'cli') {
        return;
    }

    if (isset($_REQUEST['GLOBALS'])) {
        die('GLOBALS overwrite attempt detected');
    }

    // Variables that shouldn't be unset
    $noUnset = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');

    $input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES,
        isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());
    foreach ($input as $k => $v) {
        if (!in_array($k, $noUnset) && isset($GLOBALS[$k])) {
            $GLOBALS[$k] = null;
            unset($GLOBALS[$k]);
        }
    }
}

unregister_GLOBALS();
