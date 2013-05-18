<?php

$id = 0;
if (!empty($_GET['id'])) {
  $id = sprintf('%d',$_GET['id']);
} 
if (!$id) {
  return;
}

if (isset($_REQUEST['sendmethod']) && $_REQUEST['sendmethod'] == 'inputhere') {
  $_REQUEST['sendurl'] = '';
}

if (!empty($_REQUEST['sendurl'])) {
  if (!$GLOBALS["can_fetchUrl"]) {
    print Warn($GLOBALS['I18N']->get('You are trying to send a remote URL, but PEAR::HTTP_Request is not available, so this will fail'));
  } else {
    ## hard overwrite the message content, wipe all that was there.
    ## check there's a protocol
    ## @@@ do we want to allow other than http and https? Can't imagine, ppl would want to use ftp or something

    if ($_REQUEST['sendurl'] == 'e.g. http://www.phplist.com/testcampaign.html') {
      $_REQUEST['sendurl'] = '';
    } else {
      if (!preg_match('/^https?:\/\//i',$_REQUEST['sendurl']) && !preg_match('/testcampaign/i',$_REQUEST['sendurl'])) {
        $_REQUEST['sendurl'] = 'http://'.$_REQUEST['sendurl'];
      }
    
      $_REQUEST["message"] = '[URL:'.$_REQUEST['sendurl'].']';
    }
  }
} 

## checkboxes cannot be detected when unchecked, so they need registering in the "cb" array
## to be identified as listed, but not checked
## find the "cb" array and uncheck all checkboxes in it
## then the processing below will re-check them, if they were
if (isset($_REQUEST['cb']) && is_array($_REQUEST['cb'])) {
  foreach ($_REQUEST['cb'] as $cbname => $cbval) {
    ## $cbval is a dummy
    setMessageData($id,$cbname,'0');
  }
}
## remember all data entered
foreach ($_REQUEST as $key => $val) {
/*
  print $key .' '.$val;
*/
  setMessageData($id,$key,$val);
  if (get_magic_quotes_gpc()) {
    if (is_string($val)) {
      $messagedata[$key] = stripslashes($val);
    } else {
      $messagedata[$key] = $val;
    }
  } else {
    $messagedata[$key] = $val;
  }
}
unset($GLOBALS['MD']);

$messagedata = loadMessageData($id);

/*
if (!empty($_REQUEST["criteria_attribute"])) {
  include dirname(__FILE__).'/addcriterion.php';
}
*/

/*
print '<hr/>';
var_dump($messagedata);
#exit;
*/

$status = 'OK';
