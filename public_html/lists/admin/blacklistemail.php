<?php

ob_end_clean();
## blacklist an email from commandline

function cl_output($message) {
  @ob_end_clean();
  print strip_tags($message) . "\n";
  $infostring = '';
  ob_start();
}

var_dump($cline);

$email = $cline['e'];
if (empty($email)) {
  cl_output('No email'); exit;
}

if (isBlackListed($email)) {
  cl_output('Already blacklisted');
  exit;
}

addEmailToBlackList($email,'blacklisted due to spam complaints');

cl_output($email. ' blacklisted');
exit;
