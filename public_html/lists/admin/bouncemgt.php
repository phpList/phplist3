<?php
require_once dirname(__FILE__).'/accesscheck.php';

$spb ='<div>';
$spe = '</div>';

print $spb.PageLink2("bouncerules",$GLOBALS['I18N']->get('List Bounce Rules')).$spe;
print $spb.PageLink2("bounces",$GLOBALS['I18N']->get('View Bounces')).$spe;
print $spb.PageLink2("listbounces",$GLOBALS['I18N']->get('View Bounces per list')).$spe;
print $spb.PageLink2("checkbouncerules",$GLOBALS['I18N']->get('Check Current Bounce Rules')).$spe;

print $spb.PageLink2("processbounces",$GLOBALS['I18N']->get('Process Bounces')).$spe;

$numrules = Sql_Fetch_Row_Query(sprintf('select count(*) from %s',$GLOBALS['tables']['bounceregex']));
if (!$numrules[0]) {
  print '<p class="information">'.$GLOBALS['I18N']->get('norulesyet').'</p>';
} else {
  print '<p class="information">'.$GLOBALS['I18N']->get('rulesexistwarning').'</p>';
}
print '<p class="button">'.PageLink2("generatebouncerules",$GLOBALS['I18N']->get('Generate Bounce Rules')).'</p>';

?>
