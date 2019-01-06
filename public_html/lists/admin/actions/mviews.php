<?php

verifyCsrfGetToken();

if (isset($_GET['id'])) {
    $id = sprintf('%d', $_GET['id']);
} else {
    $id = 0;
}
if (isset($_GET['start'])) {
    $start = sprintf('%d', $_GET['start']);
} else {
    $start = 0;
}

$addcomparison = 0;
$access = accessLevel('mviews');
//print "Access level: $access";
switch ($access) {
    case 'owner':
        $subselect = ' and owner = '.$_SESSION['logindetails']['id'];
        if ($id) {
            $allow = Sql_Fetch_Row_query(sprintf('select owner from %s where id = %d %s', $GLOBALS['tables']['message'],
                $id, $subselect));
            if ($allow[0] != $_SESSION['logindetails']['id']) {
                echo s('You do not have access to this page');

                return;
            }
        }
        $addcomparison = 1;
        break;
    case 'all':
        $subselect = '';
        break;
    case 'none':
    default:
        $subselect = ' where id = 0';
        echo s('You do not have access to this page');

        return;
        break;
}

$status = '';

$download = !empty($_GET['dl']);

if (!$id) {
    if ($download) {
        ob_end_clean();
        //  header("Content-type: text/plain");
        header('Content-type: text/csv');
        header('Content-disposition:  attachment; filename="phpList Message open statistics.csv"');
        ob_start();
    }
    $status .= '<p class="pull-right">'.PageLinkButton('page=pageaction&action=mviews&dl=true', s('Download as CSV file')).'</p><div class="clearfix"></div>';
//  print '<p>'.s('Select Message to view').'</p>';
    $timerange = ' and msg.entered  > date_sub(now(),interval 12 month)';
    $timerange = '';
    $limit = 'limit 10';

    $req = Sql_Query(sprintf('select msg.id as messageid,count(um.viewed) as views, count(um.status) as total,
    subject,sent,bouncecount as bounced from %s um,%s msg
    where um.messageid = msg.id and um.status = "sent" %s %s
    group by msg.id order by msg.entered desc limit 50', $GLOBALS['tables']['usermessage'],
        $GLOBALS['tables']['message'], $subselect, $timerange));
    if (!Sql_Affected_Rows()) {
        $status .= '<p class="information">'.s('There are currently no messages to view').'</p>';
    }

    $ls = new WebblerListing(s('Available Messages'));
    while ($row = Sql_Fetch_Array($req)) {
        //  $element = $row['messageid'].' '.substr($row['subject'],0,50);
        $messagedata = loadMessageData($row['messageid']);
        if (!$download) {
            if ($messagedata['subject'] != $messagedata['campaigntitle']) {
                $element = '<!--'.$row['messageid'].'-->'.stripslashes($messagedata['campaigntitle']).'<br/><strong>'.shortenTextDisplay($messagedata['subject'],
                        30).'</strong>';
            } else {
                $element = '<!--'.$row['messageid'].'-->'.shortenTextDisplay($messagedata['subject'], 30);
            }
        } else {
            $element = $messagedata['subject'];
        }
        $ls->addElement($element, PageUrl2('mviews&amp;id='.$row['messageid']));
        $ls->setClass($element, 'row1');
        if (!empty($row['sent'])) {
            $ls->addRow($element,
                '<div class="listingsmall gray">'.s('date').': '.formatDate($row['sent'], true).'</div>', '');
        } else {
            $ls->addRow($element,
                '<div class="listingsmall gray">'.s('date').': '.s('in progress').'</div>',
                '');
        }
        $ls->addColumn($element, s('sent'), number_format($row['total']));
        //   $ls->addColumn($element,s('bounced'),$row['bounced']);
        $ls->addColumn($element, s('unique views'), number_format($row['views']),
            $row['views'] ? PageURL2('mviews&amp;id='.$row['messageid']) : '');
        $openrate = sprintf('%0.2f', ($row['views'] / $row['total'] * 100));
        $ls->addColumn($element, s('rate'), $openrate.' %');
        /*
            $bouncerate = sprintf('%0.2f',($row['bounced'] / $row['total'] * 100));
            $ls->addColumn($element,s('bounce rate'),$bouncerate.' %');

        */
    }
    if ($addcomparison) {
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
}
