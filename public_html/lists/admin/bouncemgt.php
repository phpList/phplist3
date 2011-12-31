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
  print '<p class="information">'.$GLOBALS['I18N']->get('You currently have no rules defined.      You can click "Generate Bounce Rules" in order to auto-generate rules from your existing bounces.      This will results in a lot of rules which you will need to review and activate.      It will however, not catch every single bounce, so it will be necessary to add new rules over      time when new bounces come in.').'</p>';
} else {
  print '<p class="information">'.$GLOBALS['I18N']->get('You have already defined bounce rules in your system.      Be careful with generating new ones, because these may interfere with the ones that exist.').'</p>';
}
print '<p class="button">'.PageLink2("generatebouncerules",$GLOBALS['I18N']->get('Generate Bounce Rules')).'</p>';

?>
