<?php

verifyCsrfGetToken();
if (!empty($_SESSION['LoadDelay'])) {
    sleep($_SESSION['LoadDelay']);
}
// domain stats
$status = '';

// Fetch all subscribers' data
$totalreq = Sql_Fetch_Row_Query(sprintf(
    'select
    count(*)
from
    %s', $GLOBALS['tables']['user']));

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

// Count the number of confirmed/unconfirmed/blacklisted users per domain and return them in descending order
$req = Sql_Query(sprintf(
    'select
    lcase( substring_index( email,"@",-1 ) ) as domain,
    sum(if(confirmed = 1 && blacklisted = 0, 1, 0)) as domain_confirmed,
    sum(if(confirmed = 0 && blacklisted = 0, 1, 0)) as domain_unconfirmed,
    sum(if(blacklisted = 1, 1, 0)) AS domain_blacklisted,
    count(email) as domain_total
from
    %s
group by
    domain
having
    domain_total > 5
order by
    domain_total desc
limit
    50', $GLOBALS['tables']['user']));

$ls = new WebblerListing(s('Top 50 domains with more than 5 subscribers'));
$ls->setElementHeading('Domain');
$columnFormat = '<strong>%s</strong> (%s%%)';

if (Sql_Num_Rows($req) > 0) {
    while ($row = Sql_Fetch_Array($req)) {
        $ls->addElement($row['domain']);

        // Calculate the number of confirmed subs on this domain as a percentage of all subscribers
        $perc = round($row['domain_confirmed'] / $total * 100);
        $ls->addColumn(
            $row['domain'],
            s('confirmed'),
            sprintf($columnFormat, number_format($row['domain_confirmed']), $perc)
        );

        // Calculate the number of unconfirmed subs on this domain as a percentage of all subscribers
        $percentUnconfirmed = round($row['domain_unconfirmed'] / $total * 100);
        $ls->addColumn(
            $row['domain'],
            s('unconfirmed'),
            sprintf($columnFormat, number_format($row['domain_unconfirmed']), $percentUnconfirmed)
        );

        // Calculate the number of blacklisted subs on this domain as a percentage of all subscribers
        $percentBlacklisted = round($row['domain_blacklisted'] / $total * 100);
        $ls->addColumn(
            $row['domain'],
            s('blacklisted'),
            sprintf($columnFormat, number_format($row['domain_blacklisted']), $percentBlacklisted)
        );

        // Calculate the number subs on this domain as a percentage of all subscribers
        $percentTotal = round($row['domain_total'] / $total * 100);
        $ls->addColumn(
            $row['domain'],
            s('total'),
            sprintf($columnFormat, number_format($row['domain_total']), $percentTotal)
        );
    }

    // Print download button
    $status .= '<div class="actions pull-right">'.PageLinkButton('page=pageaction&action=domainstats&dl=true',
            s('Download as CSV file')).'</div><div class="clearfix"></div>';
} else {
    // Print missing data notice
    $status .= '<h3>'.s('Once you have some more subscribers, this page will list statistics on the domains of your subscribers. It will list domains that have 5 or more subscribers.').'</h3>';
}

// If download was requested, send CSV
if ($download) {
    ob_end_clean();
    echo $ls->tabDelimited();
    exit;
}

$status .= $ls->display();

$status .= '<br /><br />';

// Fetch top 25 domains ordered by total unconfirmed descending
$query = Sql_Query(sprintf(
    'select
    lcase(substring_index(email,"@",-1)) as domain
    , count(email) as domain_total
    , sum(if(confirmed = 1 && blacklisted = 0, 1, 0)) as domain_confirmed
    , sum(if(confirmed = 0 && blacklisted = 0, 1, 0)) as domain_unconfirmed
    , sum(if(blacklisted = 1, 1, 0)) AS domain_blacklisted
from
    %s
group by
    domain
having
    domain_unconfirmed > 0
order by
    domain_unconfirmed DESC
    , domain_total DESC
limit
    25', $GLOBALS['tables']['user']));

// Only print table if results are found
if (Sql_Num_Rows($query) > 0) {
    $ls = new WebblerListing(s('Domains with most unconfirmed subscribers'));
    $ls->setElementHeading('Domain');

    // Loop through each domain result
    while ($row = Sql_Fetch_Assoc($query)) {
        $ls->addElement($row['domain']);

        // Calculate the number of confirmed subs on this domain as a percentage of all subscribers
        $percentConfirmed = round($row['domain_confirmed'] / $total * 100);
        $ls->addColumn(
            $row['domain'],
            s('confirmed'),
            sprintf($columnFormat, number_format($row['domain_confirmed']), $percentConfirmed)
        );

        // Calculate the number of unconfirmed subs on this domain as a percentage of all subscribers
        $percentUnconfirmed = round($row['domain_unconfirmed'] / $total * 100);
        $ls->addColumn(
            $row['domain'],
            s('unconfirmed'),
            sprintf($columnFormat, number_format($row['domain_unconfirmed']), $percentUnconfirmed)
        );
        // Calculate the number of blacklisted subs on this domain as a percentage of all subscribers
        $percentBlacklisted = round($row['domain_blacklisted'] / $total * 100);
        $ls->addColumn(
            $row['domain'],
            s('blacklisted'),
            sprintf($columnFormat, number_format($row['domain_blacklisted']), $percentBlacklisted)
        );

        // Calculate the number subs on this domain as a percentage of all subscribers
        $percentTotal = round($row['domain_total'] / $total * 100);
        // Show the total subscribers using this domain
        $ls->addColumn(
            $row['domain'],
            s('total'),
            sprintf($columnFormat, number_format($row['domain_total']), $percentTotal)
        );
    }

    // Print table
    $status .= $ls->display();
    $status .= '<br /><br />';
}

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
    num desc, preat asc
limit
    25', $GLOBALS['tables']['user']));

$ls = new WebblerListing(s('Top 25 local-parts of email addresses'));
$ls->setElementHeading('Local-part');

while ($row = Sql_Fetch_Array($req)) {
    if ($row['num'] > 0) {
        $ls->addElement($row['preat']);
        $percentTotal = round($row['num'] / $total * 100);
        $ls->addColumn(
            $row['preat'],
            s('total'),
            sprintf($columnFormat, number_format($row['num']), $percentTotal)
        );
    }
}
$status .= $ls->display();
$status .= '<br /><br />';

$ls = new WebblerListing(s('Top 25 domains with the highest number of bounces'));
$ls->setElementHeading('Domain');
$req = Sql_Query(sprintf('
SELECT COUNT(lcase(substring_index(u.email, "@", -1))) num,
       lcase(substring_index(u.email, "@", -1)) domain
FROM %s AS u
RIGHT JOIN %s AS b ON u.id = b.user
GROUP BY domain
ORDER BY num DESC
LIMIT 25;
', $GLOBALS['tables']['user'],
    $GLOBALS['tables']['user_message_bounce']));

while ($row = Sql_Fetch_Array($req)) {
    $ls->addElement($row['domain'],  PageURL2("domainbounces&amp;domain=".$row['domain'])."&amp;bounces=".$row['num']);
    $ls->addColumn(
        $row['domain'],
        s('Bounces'),
        sprintf( number_format($row['num']),'')
    );
}
$status .= $ls->display();