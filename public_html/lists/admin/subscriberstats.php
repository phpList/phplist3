<?php

require_once dirname(__FILE__).'/accesscheck.php';

$req = Sql_Query(sprintf('select * from %s where date_add(from_unixtime(unixdate),interval 12 month) > now() order by unixdate',
    $GLOBALS['tables']['userstats']));
$ls = new WebblerListing($GLOBALS['I18N']->get('Statistics'));
while ($row = Sql_Fetch_Array($req)) {
    $element = $GLOBALS['I18N']->get($row['item']);
    $ls->addElement($element);
    switch (STATS_INTERVAL) {
        case 'monthly':
            $date = date('M y', $row['unixdate']);
            break;
    }
    $ls->addColumn($element, $date, $row['value']);
}
echo $ls->display();
