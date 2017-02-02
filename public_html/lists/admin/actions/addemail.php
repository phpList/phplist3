<?php

if (empty($_SESSION['last_addemail'])) {
    $_SESSION['last_addemail'] = 0;
}
if (!defined('PHPLISTINIT')) {
    die();
}
verifyCsrfGetToken();

if (!empty($_GET['email'])) {
    $delay = time() - $_SESSION['last_addemail'];
    if (!validateEmail($_GET['email'])) {
        $status = s('That is not a valid email address');
    } elseif ($delay > ADD_EMAIL_THROTTLE) {
        $_SESSION['last_addemail'] = time();
        Sql_Query(sprintf('insert into %s (email,uniqid,htmlemail,entered,uuid) values("%s","%s",1,now(),"%s")',
            $GLOBALS['tables']['user'], sql_escape($_GET['email']), getUniqid(), (string) uuid::generate(4)), 1);
        addUserHistory($_GET['email'], s('Added by %s', adminName()), s('Added with add-email on test'));
        $status = s('Email address added');
    } else {
        // pluginsCall('processError','Error adding email address, throttled');
        foreach ($GLOBALS['plugins'] as $plname => $plugin) {
            $plugin->processError('Add email throttled '.$delay);
        }
        $status = s('Adding email address failed, try again later');
    }
}
