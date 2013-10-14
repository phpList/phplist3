<?php
require_once dirname(__FILE__).'/accesscheck.php';

# domain stats

$totalreq = Sql_Fetch_Row_Query(sprintf('select count(*) from %s',$GLOBALS['tables']['user']));
$total = $totalreq[0];

$download = !empty($_GET['dl']);
if ($download) {
  ob_end_clean();
#  header("Content-type: text/plain");
  header('Content-type: text/csv');
  header('Content-disposition:  attachment; filename="phpList Domain statistics.csv"');
  ob_start();
}  

print '<div class="actions">'.PageLinkButton('domainstats&dl=true',$GLOBALS['I18N']->get('Download as CSV file')).'</div>';

$confirmed = array();
$req = Sql_Query(sprintf('select lcase(substring_index(email,"@",-1)) as domain,count(email) as num from %s where confirmed group by domain order by num desc limit 50',$GLOBALS['tables']['user']));
$ls = new WebblerListing($GLOBALS['I18N']->get('Top 50 domains with more than 5 emails'));
while ($row = Sql_Fetch_Array($req)) {
  if ($row['num'] > 5) {
    $ls->addElement($row['domain']);
    $confirmed[$row['domain']] = $row['num'];
    $ls->addColumn($row['domain'],$GLOBALS['I18N']->get('confirmed'),$row['num']);
    $perc = sprintf('%0.2f',($row['num'] / $total * 100));
    $ls->addColumn($row['domain'],'<!-conf-->'.$GLOBALS['I18N']->get('perc'),$perc);

  }
}
$req = Sql_Query(sprintf('select lcase(substring_index(email,"@",-1)) as domain,count(email) as num from %s where !confirmed group by domain order by num desc limit 50',$GLOBALS['tables']['user']));
while ($row = Sql_Fetch_Array($req)) {
/*  if (!in_array($confirmed,$row['domain'])) {
    $ls->addElement($row['domain']);
  }*/
  if (in_array($row['domain'],array_keys($confirmed))) {
    if ($row['num'] > 5) {
      $ls->addColumn($row['domain'],$GLOBALS['I18N']->get('unconfirmed'),$row['num']);
      $perc = sprintf('%0.2f',($row['num'] / $total * 100));
      $ls->addColumn($row['domain'],'<!--unc-->'.$GLOBALS['I18N']->get('perc'),$perc);
    }
    $ls->addColumn($row['domain'],$GLOBALS['I18N']->get('num'),$row['num'] + $confirmed[$row['domain']]);
    $perc = sprintf('%0.2f',(($row['num'] + $confirmed[$row['domain']]) / $total * 100));
    $ls->addColumn($row['domain'],$GLOBALS['I18N']->get('perc'),$perc);
  }

}
if ($download) {
  ob_end_clean();
  print $ls->tabDelimited();
}

print $ls->display();

print '<br /><br />';

$req = Sql_Query(sprintf('select lcase(substring_index(email,"@",1)) as preat,count(email) as num from %s where confirmed group by preat order by num desc limit 25',$GLOBALS['tables']['user']));
$ls = new WebblerListing($GLOBALS['I18N']->get('Top 25 pre-@ of email addresses'));
while ($row = Sql_Fetch_Array($req)) {
  if ($row['num'] > 10) {
    $ls->addElement($row['preat']);
    $ls->addColumn($row['preat'],s('amount'),$row['num']);
    $perc = sprintf('%0.2f',$row['num'] / $total * 100);
    $ls->addColumn($row['preat'],$GLOBALS['I18N']->get('perc'),$perc);
  }
}
print $ls->display();
