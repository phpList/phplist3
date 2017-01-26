<?php

require_once dirname(__FILE__).'/accesscheck.php';

ob_end_flush();
$limit = ' limit 100';
$numperrun = 500;
$bouncerules = loadBounceRules();

$req = Sql_Fetch_Row_query(sprintf('select count(*) from %s  where comment != "not processed"',
    $GLOBALS['tables']['bounce']));
$total = $req[0];
if (isset($_GET['s'])) {
    $s = sprintf('%d', $_GET['s']);
    $e = $s + $numperrun;
} else {
    $s = 0;
    $e = $numperrun;
}
$limit = ' limit '.$s.', '.$numperrun;

if ($total > $numperrun && $e < $total) {
    $next = '<p class="button">'.PageLink2('checkbouncerules&s='.$e,
            sprintf($GLOBALS['I18N']->get('Process Next %d'), $numperrun)).'</p>';
} else {
    $next = '';
}

$unmatched = 0;
$matched = 0;
$req = Sql_Query(sprintf('select * from %s where comment != "not processed" %s', $GLOBALS['tables']['bounce'], $limit));
while ($row = Sql_Fetch_Array($req)) {
    $action = matchBounceRules($row['header']."\n\n".$row['data'], $bouncerules);
    if ($action) {
        //  print $row['comment']. " Match: $action<br/>";
        ++$matched;
    } else {
        ++$unmatched;
        echo $GLOBALS['I18N']->get('No match').': '.$row['id'].' '.PageLink2('bounce&amp;id='.$row['id'],
                $row['comment']).'<br/>';
    }
    flush();
}

echo '<br/>'.$unmatched.' '.$GLOBALS['I18N']->get('bounces did not match any current active rule');
echo '<br/>'.$matched.' '.$GLOBALS['I18N']->get('bounce matched current active rules');
echo $next;
