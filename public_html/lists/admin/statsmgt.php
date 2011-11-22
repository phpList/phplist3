<?php
require_once dirname(__FILE__).'/accesscheck.php';

$spb ='<div>';
$spe = '</div>';

print $spb.PageLink2("statsoverview",$GLOBALS['I18N']->get('Overview')).$spe;
print $spb.PageLink2("uclicks",$GLOBALS['I18N']->get('View Clicks by URL')).$spe;
print $spb.PageLink2("mclicks",$GLOBALS['I18N']->get('View Clicks by Message')).$spe;
print $spb.PageLink2("mviews",$GLOBALS['I18N']->get('View Opens by Message')).$spe;
print $spb.PageLink2("domainstats",$GLOBALS['I18N']->get('Domain Statistics')).$spe;

$num = Sql_Fetch_Row_Query(sprintf('select count(*) from %s',$GLOBALS['tables']['linktrack']));
if ($num[0] > 0) {
  print '<p class="information">'.$GLOBALS['I18N']->get('The clicktracking system has changed').'</p>';
  printf($GLOBALS['I18N']->get('You have %s entries in the old statistics table'),$num[0]);
  print $spb.PageLink2("convertstats",$GLOBALS['I18N']->get('Convert Old data to new')).$spe;
  print '<p class="information">'.$GLOBALS['I18N']->get('To avoid overloading the system, this will convert 10000 records at a time').'</p>';
}

?>
