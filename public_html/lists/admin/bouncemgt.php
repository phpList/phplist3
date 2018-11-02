<?php

require_once dirname(__FILE__).'/accesscheck.php';

$spb = '<li>';
$spe = '</li>';

echo '<ul style="list-style:none" class="navigation_list">';

echo $spb.PageLink2('bouncerules', s('List Bounce Rules')).$spe;
echo $spb.PageLink2('bounces', s('View Bounces')).$spe;
echo $spb.PageLink2('msgbounces', s('View Bounces per campaign')).$spe;
echo $spb.PageLink2('listbounces', s('View Bounces per list')).$spe;
echo $spb.PageLink2('checkbouncerules', s('Check Current Bounce Rules')).$spe;

echo $spb.PageLink2('processbounces', s('Process Bounces')).$spe;

echo '</ul><br />';

$numrules = Sql_Fetch_Row_Query(sprintf('select count(*) from %s', $GLOBALS['tables']['bounceregex']));
if (!$numrules[0]) {
    echo '<p class="information text-info"><big>'.s('You currently have no rules defined.      You can click "Generate Bounce Rules" in order to auto-generate rules from your existing bounces.      This will results in a lot of rules which you will need to review and activate.      It will however, not catch every single bounce, so it will be necessary to add new rules over      time when new bounces come in.').'</big></p>';
} else {
    echo '<p class="information text-warning"><big>'.s('You have already defined bounce rules in your system.      Be careful with generating new ones, because these may interfere with the ones that exist.').'</big></p>';
}
echo '<br /><p class="button">'.PageLink2('generatebouncerules', s('Generate Bounce Rules')).'</p>';
