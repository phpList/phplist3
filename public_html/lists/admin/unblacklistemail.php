<?php

//ob_end_clean();
//# remove an email address from the blacklist from commandline

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

$unblacklistList = array();

if ($email != 'all') {
  $emailQ = Sql_Fetch_Row_Query(sprintf('select id,email from %s where uniqid = "%s" or email = "%s"  order by email desc',
      $GLOBALS['tables']['user'], sql_escape($uid), sql_escape($email)));
  $emailDB = $emailQ[1];

  if (empty($emailDB) && empty($email)) {
      cl_output('FAIL');
      exit;
  }
  $unblacklistSubscriberIDs[$emailQ[0]] = $emailQ[1];
} else {
  ## find all blacklisted subscribers
  $q = Sql_Query(sprintf('select id,u.email from %s bl, %s u where bl.email = u.email ;',$GLOBALS['tables']['user_blacklist'],$GLOBALS['tables']['user']));
  while ($row = Sql_Fetch_Row($q)) {
    $unblacklistSubscriberIDs[$row[0]] = $row[1];
  }
}

foreach ($unblacklistSubscriberIDs as $subscriberID => $subscriberEmail) {
  unBlackList($subscriberID);
  cl_output('OK '.$subscriberEmail);
}
exit;
