<?php

require_once dirname(__FILE__).'/accesscheck.php';

# domain stats

$totalreq = Sql_Fetch_Row_Query(sprintf('select count(*) from %s', $GLOBALS['tables']['user']));
$total = $totalreq[0];

$download = !empty($_GET['dl']);
if ($download) {
    ob_end_clean();
#  header("Content-type: text/plain");
  header('Content-type: text/csv');
    header('Content-disposition:  attachment; filename="phpList Domain statistics.csv"');
    ob_start();
}

$some = 0;
$confirmed = array();

// Count the number of confirmed users per domain and return them in descending order
$req = Sql_Query(sprintf(
'select
    lcase( substring_index( email,"@",-1 ) ) as domain
    ,count(email) as num
from
    %s
where
    confirmed group by domain
order by
    num desc
limit
    50'
, $GLOBALS['tables']['user']));

$ls = new WebblerListing($GLOBALS['I18N']->get('Top 50 domains with more than 5 emails'));

// Loop through the resulting top 50 domains and fetch extra data
while ($row = Sql_Fetch_Array($req)) {
    if ($row['num'] > 5) {
        $some = 1;
        $ls->addElement($row['domain']);
        $confirmed[$row['domain']] = $row['num'];
        // Calculate the number of confirmed subs on this domain as a percentage of all subs
        $perc = sprintf('%0.2f', ($row['num'] / $total * 100));
        // Add data to the table
        $ls->addColumn($row['domain'], $GLOBALS['I18N']->get('confirmed'), $row['num'].' ('.$perc.'%)');
    }
}

if ($some) {
    print '<div class="actions">'.PageLinkButton('domainstats&dl=true', $GLOBALS['I18N']->get('Download as CSV file')).'</div>';
} else {
    print '<h3>'.s('Once you have some more subscribers, this page will list statistics on the domains of your subscribers. It will list domains that have 5 or more subscribers.').'</h3>';
}

// Count the number of unconfirmed users per domain and return them in descending order
$req = Sql_Query(sprintf(
'select 
    lcase(substring_index(email,"@",-1)) as domain
    , count(email) as num 
from 
    %s 
where 
    !confirmed 
group by 
    domain 
order by 
    num desc 
limit 
    50'
, $GLOBALS['tables']['user']));

// Loop through the resulting top 50 domains and fetch extra data
while ($row = Sql_Fetch_Array($req)) {
    
  // Add data for the unconfirmed subscribers to the domain info already retrieved for the confirmed subscribers
  if (in_array($row['domain'], array_keys($confirmed))) {
      if ($row['num'] > 5) {
          // Calculate the number of unconfirmed subs on this domain as a percentage of all subs
          $perc = sprintf('%0.2f', ($row['num'] / $total * 100));
          $ls->addColumn($row['domain'], $GLOBALS['I18N']->get('unconfirmed'), $row['num'].' ('.$perc.'%)');
      }
      $perc = sprintf('%0.2f', (($row['num'] + $confirmed[$row['domain']]) / $total * 100));
      $ls->addColumn($row['domain'], $GLOBALS['I18N']->get('total'), $row['num'] + $confirmed[$row['domain']].' ('.$perc.'%)');
      $ls->addColumn($row['domain'], $GLOBALS['I18N']->get('perc'), $perc);
  }
}

// If download was requested, send CSV
if ($download) {
    ob_end_clean();
    print $ls->tabDelimited();
}

print $ls->display();

print '<br /><br />';

$req = Sql_Query(sprintf('select lcase(substring_index(email,"@",1)) as preat,count(email) as num from %s where confirmed group by preat order by num desc limit 25', $GLOBALS['tables']['user']));
$ls = new WebblerListing($GLOBALS['I18N']->get('Top 25 pre-@ of email addresses'));
while ($row = Sql_Fetch_Array($req)) {
    if ($row['num'] > 10) {
        $ls->addElement($row['preat']);
        $ls->addColumn($row['preat'], s('amount'), $row['num']);
        $perc = sprintf('%0.2f', $row['num'] / $total * 100);
        $ls->addColumn($row['preat'], $GLOBALS['I18N']->get('perc'), $perc);
    }
}
print $ls->display();
