<?php

#updatetranslation
#sleep(20);
$lan = '';
if (isset($_GET['lan'])) {
  $lan = $_GET['lan'];
  $lan = preg_replace('/[^\w_]/','',$lan);
}
$LU = getTranslationUpdates();
if (!$LU || !is_object($LU)) {
  print Error(s('Unable to fetch list of languages, please check your network or try again later'));
  return;
}

$translations = array();
foreach ($LU->translation as $update) {
  if ($update->iso == $lan) {
  #  $status = $update->updateurl;
    $translationUpdate = fetchUrl($update->updateurl);
    $translations = parsePo($translationUpdate);
  }
}
#  $status = $lan;

$status = '';
if (sizeof($translations)) {
  foreach ($translations as $orig => $trans) {
    $status .= $orig .' =&gt; '.$trans.'<br/>';
    Sql_Replace($GLOBALS['tables']['i18n'],array('lan' => $lan,'original' => $orig,'translation' => $trans),'');
  }
  saveConfig('lastlanguageupdate-'.$lan,time(),0);
  $status = sprintf(s('updated %d language terms'),sizeof($translations));
} else {
  $status = Error(s('Network error updating language, please try again later'));
}
