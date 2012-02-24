<?php

#ob_end_clean();
## blacklist an email from commandline

if (!$GLOBALS['commandline']) {
  print 'Error, this can only be called from commandline'."\n";
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

if (empty($email) && !empty($uid)) {
  $emailQ = Sql_Fetch_Row_Query(sprintf('select email from %s where uniqid = "%s"',$GLOBALS['tables']['user'],Sql_escape($uid)));
  $email = $emailQ[0];
  if (empty($email)) {
    cl_output('FAIL'); exit;
  }
}

if (isBlackListed($email)) {
  cl_output('OK');
  exit;
}
addEmailToBlackList($email,'blacklisted due to spam complaints',$date);
cl_output('OK '.$email);
exit;
