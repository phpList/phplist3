<?php
require_once dirname(__FILE__).'/accesscheck.php';

## fetch updated translation
#var_dump($LANGUAGES);

if (!Sql_Table_exists($GLOBALS['tables']['i18n'])) {
  include dirname(__FILE__).'/structure.php';
  Sql_Create_Table($GLOBALS['tables']['i18n'],$DBstruct['i18n']);
}
if (isset($_GET['lan'])) { ## Non-JS version
  include 'actions/updatetranslation.php';
}

$LU = getTranslationUpdates();
if (!$LU || !is_object($LU)) {
  print Error(s('Unable to fetch list of languages, please check your network or try again later'));
  return;
}

#var_dump($LU);
print '<ul>';
foreach ($LU->translation as $lan) {
#  var_dump($lan);
  $lastupdated = getConfig('lastlanguageupdate-'.$lan->iso);
  if (!empty($LANGUAGES[(string)$lan->iso])) {
    $lan_name = $LANGUAGES[(string)$lan->iso][0];
  } else {
    $lan_name = $lan->name;
  }
  if ($lan->lastmodified > $lastupdated) {
    $updateLink = pageLinkAjax('updatetranslation&lan='.$lan->iso,$lan_name);
  } else {
    $updateLink = $lan_name;
  }
  if (empty($lastupdated)) {
    $lastupdated = s('Never');
  } else {
    $lastupdated = date('Y-m-d',$lastupdated);
  }
  
  printf ('<li>%s %s: %s, %s: %s</li>',$updateLink,s('Last updated'),$lastupdated,s('Last modified'),date('Y-m-d',(int)$lan->lastmodified));
}
print '</ul>';
