<?php
/**
 *
 *
 */


verifyCsrfGetToken();

$access = accessLevel('msgbounces');

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

$status = '';
$req = Sql_Query(sprintf('select msg.id as messageid, msg.subject, count(msgbounce.bounce) as totalbounces from %s msg,%s msgbounce
    where msg.id = msgbounce.message %s
    group by msg.id order by messageid', $GLOBALS['tables']['message'],
    $GLOBALS['tables']['user_message_bounce'], $subselect));
if (!Sql_Affected_Rows()) {
    $status .= '<p class="information">'.s('There are currently no data to view').'</p>';
}

$ls = new WebblerListing(s('Choose a campaign'));
$ls->setElementHeading('Campaign name');
$some = 0;
while ($row = Sql_Fetch_Array($req)) {

    $messagedata = loadMessageData($row['messageid']);
    if (empty($download)) {
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
