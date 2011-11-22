<?php

if (!empty($_GET['email'])) {
  Sql_Query(sprintf('insert into %s (email,uniqid,htmlemail,entered) values("%s","%s",1,now())',$GLOBALS['tables']['user'],sql_escape($_GET['email']),getUniqid()),1);
  addUserHistory($_GET['email'],'Added by '.adminName(),'');
}
$status = $GLOBALS['I18N']->get('Email added');
