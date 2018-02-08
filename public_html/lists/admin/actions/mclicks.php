<?php

verifyCsrfGetToken();

if (isset($_GET['id'])) {
    $id = sprintf('%d', $_GET['id']);
} else {
    $id = 0;
}
$some = 0;
$status = '';

$access = accessLevel('mclicks');
switch ($access) {
    case 'owner':
        $subselect = ' and owner = '.$_SESSION['logindetails']['id'];
        if ($id) {
            $allow = Sql_Fetch_Row_query(sprintf('select owner from %s where id = %d %s', $GLOBALS['tables']['message'],
                $id, $subselect));
            if ($allow[0] != $_SESSION['logindetails']['id']) {
                echo $GLOBALS['I18N']->get('You do not have access to this page');

                return;
            }
        }
        break;
    case 'all':
        $subselect = '';
        break;
    case 'none':
    default:
        $subselect = ' where id = 0';
        echo $GLOBALS['I18N']->get('You do not have access to this page');

        return;
        break;
}

$download = !empty($_GET['dl']);
if ($download) {
    ob_end_clean();
//  header("Content-type: text/plain");
    header('Content-type: text/csv');
    header('Content-disposition:  attachment; filename="phpList Campaign click statistics.csv"');
    ob_start();
}
/*  $req = Sql_Query(sprintf('select distinct messageid, subject, sum(clicked) as totalclicks, count(distinct userid) as users, count(distinct linkid) as linkcount from %s as linktrack, %s as message
where clicked and linktrack.messageid = message.id %s group by messageid order by entered desc limit 50',
$GLOBALS['tables']['linktrack'],$GLOBALS['tables']['message'],$subselect));*/
$req = Sql_Query(sprintf('select distinct messageid, subject,
    sum(total) as total, count(forwardid) as linkcount,sum(clicked) as totalclicks,
    sum(htmlclicked) as htmlclicked,sum(textclicked) as textclicked from %s as linktrack_ml, %s as message
    where clicked and linktrack_ml.messageid = message.id %s  group by messageid order by entered desc limit 50',
    $GLOBALS['tables']['linktrack_ml'], $GLOBALS['tables']['message'], $subselect));
if (!Sql_Affected_Rows()) {
    $status .= '<p class="information">'.s('There are currently no messages to view').'</p>';
}
$ls = new WebblerListing(s('List of Available Messages'));
$ls->setElementHeading(s('Message'));
while ($row = Sql_Fetch_Array($req)) {
    $some = 1;
    $messagedata = loadMessageData($row['messageid']);
    $totalusers = Sql_Fetch_Row_Query(sprintf('select count(userid) from %s where messageid = %d and status = "sent"',
        $GLOBALS['tables']['usermessage'], $row['messageid']));
    $totalclicked = Sql_Fetch_Row_Query(sprintf('select count(distinct userid) from %s where messageid = %d',
        $GLOBALS['tables']['linktrack_uml_click'], $row['messageid']));
    if ($totalusers[0] > 0) {
        $clickrate = sprintf('%0.2f', ($totalclicked[0] / $totalusers[0] * 100));
    } else {
        $clickrate = s('N/A');
    }
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

    $ls->addElement($element, PageURL2('mclicks&amp;id='.$row['messageid']));
    $ls->setClass($element, 'row1');
    $ls->addColumn($element, s('links'), $row['linkcount']);
//    $ls->addColumn($element,$GLOBALS['I18N']->get('sent'),$totalusers[0]);
    $ls->addColumn($element, s('subscribers'), $totalclicked[0]);
    $ls->addColumn($element, s('clickrate'), $clickrate);

    $ls->addColumn($element, s('clicks'), PageLink2('userclicks&msgid='.$row['messageid'], $row['totalclicks']));
//    $ls->addColumn($element,$GLOBALS['I18N']->get('total'),$row['total']);
//    $ls->addColumn($element,$GLOBALS['I18N']->get('users'),$row['users']);
    $ls->addRow($element, '',
        '<div class="content listingsmall fright gray">'.$GLOBALS['I18N']->get('html').': '.$row['htmlclicked'].'</div><div class="content listingsmall fright gray">'.$GLOBALS['I18N']->get('text').': '.$row['textclicked'].'</div>');

    /* this one is the percentage of total links versus clicks. I guess that's too detailed for most people.
     * besides it'll be low
    $perc = sprintf('%0.2f',($row['totalclicks'] / $row['total'] * 100));
    $ls->addColumn($element,$GLOBALS['I18N']->get('rate'),$perc.' %');
    */
}
if ($some) {
    $status .= '<div class="action">';
    $status .= '<p class="pull-right">'.PageLinkButton('mclicks&dl=true', $GLOBALS['I18N']->get('Download as CSV file')).'</p>';
    $status .= '</div><div class="clearfix"></div>';
//    print '<p>'.$GLOBALS['I18N']->get('Select Message to view').'</p>';
    $status .= $ls->display();
}
if ($download) {
    ob_end_clean();
    $status .= $ls->tabDelimited();
}
