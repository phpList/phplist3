<?php

if (empty($_SESSION['last_addemail'])) {
  $_SESSION['last_addemail'] = 0;
}
verifyCsrfGetToken();

if (!empty($_GET['email'])) {
  $delay = time() - $_SESSION['last_addemail'];
  if ($delay > ADD_EMAIL_THROTTLE) {
    $_SESSION['last_addemail'] = time();
    Sql_Query(sprintf('insert into %s (email,uniqid,htmlemail,entered) values("%s","%s",1,now())',$GLOBALS['tables']['user'],sql_escape($_GET['email']),getUniqid()),1);
    addUserHistory($_GET['email'],'Added by '.adminName(),'');
    $status = $GLOBALS['I18N']->get('Email address added');
  } else {
   # pluginsCall('processError','Error adding email address, throttled');
    foreach ($GLOBALS['plugins'] as $plname => $plugin) {
      $plugin->processError('Add email throttled '.$delay);
    }
    $status = $GLOBALS['I18N']->get('Adding email address failed');
  }
}
