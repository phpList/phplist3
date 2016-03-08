<?php

require_once dirname(__FILE__).'/accesscheck.php';

# domain stats

// Fetch all subscribers' data
$totalreq = Sql_Fetch_Row_Query(sprintf(
'select
    count(*)
from
    %s'
, $GLOBALS['tables']['user']));

$total = $totalreq[0];

// Check if download flag is set (for downloading CSV)
$download = !empty($_GET['dl']);

// if download requested
if ($download) {
    ob_end_clean();
    header('Content-type: text/csv');
    header('Content-disposition:  attachment; filename="phpList Domain statistics.csv"');
    ob_start();
}

// zero out counter to check if results were returned
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

$ls = new WebblerListing($GLOBALS['I18N']->get('Top 50 domains with more than 5 subscribers'));

// Loop through the resulting top 50 domains and fetch extra data
while ($row = Sql_Fetch_Array($req)) {
    if ($row['num'] > 5) {
        $some = 1;
        $ls->addElement($row['domain']);
        $confirmed[$row['domain']] = $row['num'];
        // Calculate the number of confirmed subs on this domain as a percentage of all subs
        $perc = sprintf('%0.2f', ($row['num'] / $total * 100));
        // Add data to the table
        $ls->addColumn($row['domain'], $GLOBALS['I18N']->get('confirmed'), '<strong>' . number_format( $row['num'] ).'</strong> ('.$perc.'%)');
    }
}

// If confirmed subscribers were found
if ($some) {
    // Print download button
    print '<div class="actions">'.PageLinkButton('domainstats&dl=true', $GLOBALS['I18N']->get('Download as CSV file')).'</div>';
} else {
    // Print missing data notice
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
          // Calculate the number of unconfirmed subs on this domain as a percentage of all subs on this domain
          $percentUnconfirmed = sprintf('%0.2f', ($row['num'] / $total * 100));
          $ls->addColumn($row['domain'], $GLOBALS['I18N']->get('unconfirmed'), '<strong>' . number_format( $row['num'] ).'</strong> ('.$percentUnconfirmed.'%)');
      }

      // Calculate the number subs on this domain as a percentage of all subs
      $percentTotal = sprintf('%0.2f', (($row['num'] + $confirmed[$row['domain']]) / $total * 100));
      $ls->addColumn($row['domain'], $GLOBALS['I18N']->get('total'), '<strong>' . number_format( $row['num'] + $confirmed[$row['domain']] ).'</strong> ('.$percentTotal.'%)');

  }
}

// If download was requested, send CSV
if ($download) {
    ob_end_clean();
    print $ls->tabDelimited();
}

print $ls->display();

print '<br /><br />';

// Print top 50 domains ordered by total bounces descending

// Fetch top 50 domains ordered by total unconfirmed descending
$req = Sql_Query(sprintf(
'select
    lcase(substring_index(email,"@",-1)) as domain
    , count(email) as total
    , sum(confirmed = 0) AS unconfirmed
    , sum(confirmed = 1) AS confirmed
    , sum(blacklisted = 1) AS blacklisted
from
    %s
group by
    domain
order by
    unconfirmed DESC
    , total DESC
limit
    25'
, $GLOBALS['tables']['user']));

$ls = new WebblerListing($GLOBALS['I18N']->get('Domains with most unconfirmed subscribers'));
while ($row = Sql_Fetch_Array($req)) {
    $ls->addElement($row['domain']);

    // Calculate the number of confirmed subs on this domain as a percentage of all subs using that domain
    $percentConfirmed = sprintf('%0.2f', ($row['confirmed'] / $row['total'] * 100));
    $ls->addColumn($row['domain'], $GLOBALS['I18N']->get('confirmed'), '<strong>'.number_format( $row['confirmed'] ).'</strong> ('.$percentConfirmed.'%)');

    // Calculate the number of unconfirmed subs on this domain as a percentage of all subs using that domain
    $percentUnconfirmed = sprintf('%0.2f', ($row['unconfirmed'] / $row['total'] * 100));
    $ls->addColumn($row['domain'], $GLOBALS['I18N']->get('unconfirmed'), '<strong>'.number_format( $row['unconfirmed'] ).'</strong> ('.$percentUnconfirmed.'%)');
// Calculate the number of blacklisted subs on this domain as a percentage of all subs using that domain
    $percentBlacklisted = sprintf('%0.2f', ($row['blacklisted'] / $row['total'] * 100));
    $ls->addColumn($row['domain'], $GLOBALS['I18N']->get('blacklisted'), '<strong>'.number_format( $row['blacklisted'] ).'</strong> ('.$percentBlacklisted.'%)');

    // Calculate the number subs on this domain as a percentage of all subs
    $percentTotal = sprintf('%0.2f', ($row['total'] / $total * 100));
    // Show the total subscribers using this domain
    $ls->addColumn($row['domain'], $GLOBALS['I18N']->get('total'), '<strong>'.number_format( $row['total'] ).'</strong> ('.$percentTotal.'%)');
}
print $ls->display();

print '<br /><br />';

$req = Sql_Query(sprintf(
'select
    lcase(substring_index(email,"@",1)) as preat
    , count(email) as num
from
    %s
where
    confirmed
group by
    preat
order by
    num desc
limit
    25'
, $GLOBALS['tables']['user']));

$ls = new WebblerListing($GLOBALS['I18N']->get('Top 25 pre-@ of email addresses'));
while ($row = Sql_Fetch_Array($req)) {
    if ($row['num'] > 0) {
        $ls->addElement($row['preat']);
        $percentTotal = sprintf('%0.2f', $row['num'] / $total * 100);
        $ls->addColumn($row['preat'], s('total'), '<strong>' . $row['num'] . '</strong> (' . $percentTotal . '%)');
    }
}
print $ls->display();
