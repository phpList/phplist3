<?php

// wrapper to allow maintenance commands in the actions folder

require_once dirname(__FILE__).'/accesscheck.php';

if (empty($GLOBALS['commandline'])) {
    echo s('This page can only be called from the commandline');
    return;
}

$status = s('Failed');
if (!empty($_GET['command'])) {
    $action = basename($_GET['command']);
    if (is_file(dirname(__FILE__) . '/actions/' . $action . '.php')) {
        include dirname(__FILE__) . '/actions/' . $action . '.php';
    } else {
        $status = s('Invalid command');
    }
}

cl_output(s('Command result').' :'.$status);