<?php

require_once dirname(__FILE__).'/accesscheck.php';

echo '<ul class="dashboard_button">';

echo "<li class='statistics'>".PageLink2('statsoverview', $GLOBALS['I18N']->get('Overview')).'</li>';
echo "<li class='statistics'>".PageLink2('uclicks', $GLOBALS['I18N']->get('View Clicks by URL')).'</li>';
echo "<li class='statistics'>".PageLink2('mclicks', $GLOBALS['I18N']->get('View Clicks by Message')).'</li>';
echo "<li class='statistics'>".PageLink2('mviews', $GLOBALS['I18N']->get('View Opens by Message')).'</li>';
echo "<li class='statistics'>".PageLink2('domainstats', $GLOBALS['I18N']->get('Domain Statistics')).'</li>';
echo '</ul>';
$num = Sql_Fetch_Row_Query(sprintf('select count(*) from %s', $GLOBALS['tables']['linktrack']));
if ($num[0] > 0) {
    echo '<p class="information">'.$GLOBALS['I18N']->get('The clicktracking system has changed').'</p>';
    printf($GLOBALS['I18N']->get('You have %s entries in the old statistics table'), $num[0]);
    echo "<div class='clear'></div><div class='button'>".PageLink2('convertstats',
            $GLOBALS['I18N']->get('Convert Old data to new')).'</div>';
    echo '<p class="information">'.$GLOBALS['I18N']->get('To avoid overloading the system, this will convert 10000 records at a time').'</p>';
}
