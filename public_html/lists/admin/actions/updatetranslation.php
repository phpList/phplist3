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
   
    $translation_lines = explode("\n",$translationUpdate);
    $original = '';
    $translation = '';
    foreach ($translation_lines as $line) {
      if (preg_match('/^msgid "(.*)"/',$line,$regs)) {
        $original = $regs[1];
      } elseif (preg_match('/^msgstr "(.*)"/',$line,$regs)) {
      #  $status .= '<br/>'.$original.' '.$regs[1];
        $translation = $regs[1];
      } elseif (preg_match('/"(.*)"/',$line,$regs)) {# && !empty($translation)) {
        ## wrapped to multiple lines
        $translation .= $regs[1];
      } elseif (preg_match('/^#/',$line) || preg_match('/^\s+$/',$line)) {
        $original = $translation = '';
      }
      if (!empty($original) && !empty($translation)) {
        $translations[$original] = $translation;
      }
    }
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
  $status = sprintf(s('updated %d langague terms'),sizeof($translations));
} else {
  $status = Error(s('Network error updating language, please try again later'));
}
