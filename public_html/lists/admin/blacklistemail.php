<?php

//ob_end_clean();
//# blacklist an email from commandline

if (!$GLOBALS['commandline']) {
    echo 'Error, this can only be called from commandline'."\n";
    exit;
}

$email = $date = $uid = '';

if (isset($cline['e'])) {
    $email = $cline['e'];
}
if (isset($cline['u'])) {
    $uid = $cline['u'];
}
if (isset($cline['d'])) {
    $date = $cline['d'];
}

$emailQ = Sql_Fetch_Row_Query(sprintf('select email from %s where uniqid = "%s" or email = "%s"  order by email desc',
    $GLOBALS['tables']['user'], sql_escape($uid), sql_escape($email)));
$emailDB = $emailQ[0];

if (empty($emailDB) && empty($email)) {
    cl_output('FAIL');
    exit;
}

if (isBlackListed($emailDB)) {
    //# do this anyway, just to be sure
    Sql_Query(sprintf('update %s set blacklisted = 1 where email = "%s"', $GLOBALS['tables']['user'], $emailDB));
    cl_output('OK');
    exit;
}

if (!empty($emailDB)) {
    //# do this immediately
    Sql_Query(sprintf('update %s set blacklisted = 1 where email = "%s"', $GLOBALS['tables']['user'], $emailDB));

    addEmailToBlackList($emailDB, 'blacklisted due to spam complaints', $date);
} else {
    addEmailToBlackList($email, 'blacklisted due to spam complaints', $date);
}

cl_output('OK '.$emailDB);

exit;
