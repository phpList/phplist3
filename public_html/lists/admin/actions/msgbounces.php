<?php
/**
 *
 *
 */


verifyCsrfGetToken();

$access = accessLevel('actions/msgbounces');

$messageid = empty($_GET['id']) ? 0 : sprintf('%d', $_GET['id']);
$isowner_and = '';
$isowner_where = '';
$status = '';

switch ($access) {
    case 'owner':
        if ($messageid) {
            $req = Sql_Query(sprintf('select id from '.$tables['message'].' where owner = %d and id = %d',
                $_SESSION['logindetails']['id'], $messageid));
            if (!Sql_Affected_Rows()) {
                Fatal_Error(s('You do not have enough privileges to view this page'));

                return;
            }
        }
        $isowner_and = sprintf(' message.owner = %d and ', $_SESSION['logindetails']['id']);
        $isowner_where = sprintf(' where message.owner = %d ', $_SESSION['logindetails']['id']);
        break;
    case 'all':
    case 'view':
        break;
    case 'none':
    default:
        if ($messageid) {
            Fatal_Error(s('You do not have enough privileges to view this page'));
            $isowner_and = sprintf(' message.owner = 0 and ');
            $isowner_where = sprintf(' where message.owner = 0 ');

            return;
        }
        break;
}
if (!empty($_SESSION['LoadDelay'])) {
    sleep($_SESSION['LoadDelay']);
}
$req = Sql_Query(sprintf('select msg.id as messageid, msg.subject, count(msgbounce.bounce) as totalbounces from %s msg,%s msgbounce
    where msg.id = msgbounce.message 
    group by msg.id order by messageid', $GLOBALS['tables']['message'],
    $GLOBALS['tables']['user_message_bounce']));
if (!Sql_Affected_Rows()) {
    $status .= '<p class="information">'.s('There are currently no data to view').'</p>';
}

$ls = new WebblerListing(s('Choose a campaign'));
$ls->setElementHeading('Campaign name');
$some = 0;
while ($row = Sql_Fetch_Array($req)) {

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
    $some = 1;
    $ls->addElement($element, PageUrl2('msgbounces&amp;id='.$row['messageid']));
    $ls->addColumn($element, s('Total bounces'), number_format($row['totalbounces']));
}
if ($some) {
    $status = $ls->display();
} else {
    $status = '<p>'.s('None found').'</p>';
}
