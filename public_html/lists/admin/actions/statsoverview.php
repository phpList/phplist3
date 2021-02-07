<?php
// print '<p>'.s('Select Message to view').'</p>';
verifyCsrfGetToken();

if (isset($_GET['id'])) {
    $id = sprintf('%d', $_GET['id']);
} else {
    $id = 0;
}

$start = 0;
$limit = ' limit 10';
if (isset($_GET['start'])) {
    $start = sprintf('%d', $_GET['start']);
    $limit = ' limit '.$start.', 10';
}

$status = '';
$access = accessLevel('statsoverview');
$ownership = '';
$subselect = '';
$paging = '';

//print "Access Level: $access";
switch ($access) {
    case 'owner':
        $ownership = sprintf(' and owner = %d ', $_SESSION['logindetails']['id']);
        if ($id) {
            $allow = Sql_Fetch_Row_query(sprintf('select owner from %s where id = %d %s', $GLOBALS['tables']['message'],
                $id, $ownership));
            if ($allow[0] != $_SESSION['logindetails']['id']) {
                $status .= s('You do not have access to this page');

                return;
            }
        }
        $addcomparison = 1;
        break;
    case 'all':
        break;
    case 'none':
    default:
        $ownership = ' and msg.id = 0';
        $status .= s('You do not have access to this page');

        return;
        break;
}

$download = !empty($_GET['dl']);
if ($download) {
    ob_end_clean();
//  header("Content-type: text/plain");
    header('Content-type: text/csv');
    if (!$id) {
        header('Content-disposition:  attachment; filename="phpList Campaign statistics.csv"');
    }
    ob_start();
}

if (empty($start)) {
    $status .= '<div class="actions pull-right">'.PageLinkButton('pageaction&action=statsoverview&dl=true',
            s('Download as CSV file')).'</div><div class="clearfix"></div>';
}
if (!empty($_SESSION['LoadDelay'])) {
    sleep($_SESSION['LoadDelay']);
}
$timerange = ' and msg.entered > date_sub(now(),interval 12 month)';
$timerange = '';

$query = sprintf('select msg.owner,msg.id as messageid, subject, sent, bouncecount as bounced
    from %s msg where msg.status = "sent" %s %s %s
    group by msg.id order by msg.sent desc',
    $GLOBALS['tables']['message'], $subselect, $timerange, $ownership);
$req = Sql_Query($query);
$total = Sql_Num_Rows($req);
if ($total > 10) {
    //print Paging(PageUrl2('statsoverview'),$start,$total,10);
    $paging = simplePaging('statsoverview', $start, $total, 10);
    // Increase the record limit for exported files
    if ($download) {
        $limit = ' limit 1000';
    }

    $query .= $limit;
    $req = Sql_Query($query);
}

if (!Sql_Affected_Rows()) {
    $status .= '<p class="information">'.s('There are currently no campaigns to view').'</p>';
}

$ls = new WebblerListing('');
$ls->usePanel($paging);
while ($row = Sql_Fetch_Array($req)) {
    //  $element = '<!--'.$row['messageid'].'-->'.shortenTextDisplay($row['subject'],30);
    $messagedata = loadMessageData($row['messageid']);

    if ($messagedata['subject'] != $messagedata['campaigntitle']) {
        $element = '<!--'.$row['messageid'].'-->'
            .stripslashes(shortenTextDisplay($messagedata['campaigntitle'], 30)).'<br/><strong>'.stripslashes(shortenTextDisplay($messagedata['subject'], 30)).'</strong>';
    } else {
        $element = '<!--'.$row['messageid'].'-->'
            .stripslashes(shortenTextDisplay($messagedata['subject'], 30));
    }

    $fwded = Sql_Fetch_Row_Query(sprintf('select count(id) from %s where message = %d',
    $GLOBALS['tables']['user_message_forward'], $row['messageid']));
    $views = Sql_Fetch_Row_Query(sprintf('select count(viewed) from %s where messageid = %d
           and status = "sent"',
    $GLOBALS['tables']['usermessage'], $row['messageid']));
    $totls = Sql_Fetch_Row_Query(sprintf('select count(status) from %s where messageid = %d
           and status = "sent"',
    $GLOBALS['tables']['usermessage'], $row['messageid']));

    $totalclicked = Sql_Fetch_Row_Query(sprintf('select count(distinct userid) from %s where messageid = %d',
    $GLOBALS['tables']['linktrack_uml_click'], $row['messageid']));

    $totalclicks = Sql_Fetch_Row_Query(sprintf('select count( userid) from %s where messageid = %d',
        $GLOBALS['tables']['linktrack_uml_click'], $row['messageid']));

    $percentBouncedFormatted = $percentViewedFormatted = $percentClickedFormatted = '';
    if ($row['bounced'] > 0 && $totls[0] > 0) {
        $percentBouncedFormatted = ' ('.sprintf('%0.2f', ($row['bounced'] / $totls[0] * 100)).' %)';
    }
    if ($views[0] > 0 && $totls[0] > 0) {
        $percentViewedFormatted = ' ('.sprintf('%0.2f', ($views[0] / ($totls[0] - $row['bounced']) * 100)).' %)';
    }
    if ($totalclicked[0] > 0 && $totls[0] > 0) {
        $percentClickedFormatted = ' ('.sprintf('%0.2f', ($totalclicked[0] / ($totls[0] - $row['bounced']) * 100)).' %)';
    }

    $ls->setElementHeading(s('Campaign'));
    $ls->addElement($element,
        PageURL2('statsoverview&amp;id='.$row['messageid'])); //,PageURL2('message&amp;id='.$row['messageid']));
    $ls->setClass($element, 'row1');
    //   $ls->addColumn($element,s('owner'),$row['owner']);
    $ls->addColumn($element, s('Date sent'), formatDate($row['sent'], true));
    $ls->addColumn($element, s('sent'), number_format((int)$totls[0]));
    $ls->addColumn($element, s('bncs').Help("bounces"), number_format((int)$row['bounced']).$percentBouncedFormatted);
    $ls->addColumn($element, s('fwds').Help("forwards"), number_format((int)$fwded[0]));
    $ls->addColumn($element, s('Unique views').Help("uniqueviews"), number_format((int)$views[0]).$percentViewedFormatted,
    $views[0] ? PageURL2('mviews&amp;id='.$row['messageid']) : '');
    $ls->addColumn($element, s('Total Clicks').Help("totalclicks"), number_format((int)$totalclicks[0]),
        $totalclicks[0] ? PageURL2('mclicks&id='.$row['messageid']) : '');

    $ls->addColumn($element, s('Unique Clicks').Help("uniqueclicks"), number_format((int)$totalclicked[0]).$percentClickedFormatted,
        $totalclicked[0] ? PageURL2('mclicks&id='.$row['messageid']) : '');
}
//# needs reviewing
if (false && $addcomparison) {
    $total = Sql_Fetch_Array_Query(sprintf('select count(entered) as total from %s um where um.status = "sent"',
        $GLOBALS['tables']['usermessage']));
    $viewed = Sql_Fetch_Array_Query(sprintf('select count(viewed) as viewed from %s um where um.status = "sent"',
        $GLOBALS['tables']['usermessage']));
    $overall = s('Comparison to other admins');
    $ls->addElement($overall);
    $ls->addColumn($overall, s('views'), $viewed['viewed']);
    $perc = sprintf('%0.2f', ($viewed['viewed'] / $total['total'] * 100));
    $ls->addColumn($overall, s('rate'), $perc.' %');
}
if ($download) {
    ob_end_clean();
    $status .= $ls->tabDelimited();
}

$status .= $ls->display();
