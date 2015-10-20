<?php

if (!defined('PHPLISTINIT')) {
    die();
}
verifyCsrfGetToken();

if (isset($_GET['id'])) {
    $userid = sprintf('%d', $_GET['id']);
}

if (empty($userid)) {
    return;
}

if (!empty($_GET['blacklist'])) {
    $email = Sql_Fetch_Row_Query(sprintf('select email from %s where id = %d', $GLOBALS['tables']['user'], $userid));
    if (!empty($email[0])) {
        addUserToBlackList($email[0], s('Manually blacklisted by %s', $_SESSION['logindetails']['adminname']));
        $status = 'OK';
    }
} elseif (!empty($_GET['unblacklist'])) {
    $email = Sql_Fetch_Row_Query(sprintf('select email from %s where id = %d', $GLOBALS['tables']['user'], $userid));
    if (!empty($email[0])) {
        unBlackList($userid);
        $status = 'OK';
    }
}
