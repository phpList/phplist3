<?php

require_once dirname(__FILE__).'/accesscheck.php';
if (!defined('PHPLISTINIT')) {
    exit;
}

if (!$_GET['id']) {
    Fatal_Error($GLOBALS['I18N']->get('no such User'));

    return;
} else {
    $id = sprintf('%d', $_GET['id']);
}

$access = accessLevel('user');
switch ($access) {
    case 'owner':
        $subselect = ' and '.$tables['list'].'.owner = '.$_SESSION['logindetails']['id'];
        break;
    case 'all':
        $subselect = '';
        break;
    case 'view':
        $subselect = '';
        if (count($_POST) || $_GET['unblacklist']) {
            echo Error($GLOBALS['I18N']->get('you only have privileges to view this page, not change any of the information'));

            return;
        }
        break;
    case 'none':
    default:
        $subselect = ' and '.$tables['list'].'.id = 0';
        break;
}

if (isset($_GET['unblacklist'])) {
    $unblacklist = sprintf('%d', $_GET['unblacklist']);
    unBlackList($unblacklist);
    Redirect('userhistory&id='.$unblacklist);
}

$result = Sql_query("SELECT * FROM {$tables['user']} where id = $id");
if (!Sql_Affected_Rows()) {
    Fatal_Error($GLOBALS['I18N']->get('no such User'));

    return;
}
$user = sql_fetch_array($result);

echo '<h3>'.$GLOBALS['I18N']->get('user').' '.PageLink2('user&id='.$user['id'], $user['email']).'</h3>';
echo '<div class="actions">';
//printf('<a href="%s" class="button">%s</a>',getConfig("preferencesurl").
//'&amp;uid='.$user["uniqid"],$GLOBALS['I18N']->get('update page'));
//printf('<a href="%s" class="button">%s</a>',getConfig("unsubscribeurl").'&amp;uid='.$user["uniqid"],$GLOBALS['I18N']->get('unsubscribe page'));
echo PageLinkButton("user&amp;id=$id", $GLOBALS['I18N']->get('Details'));
if ($access == 'all') {
    $delete = new ConfirmButton(
        htmlspecialchars(s('Are you sure you want to remove this subscriber from the system.')),
        PageURL2("user&delete=$id&amp;".addCsrfGetToken(), 'button', s('remove subscriber')),
        s('remove subscriber'));
    echo $delete->show();
}

echo '</div>';

$bouncels = new WebblerListing($GLOBALS['I18N']->get('Bounces'));
$bouncels->setElementHeading('Bounce ID');
$bouncelist = '';
$bounces = array();
// check for bounces
$req = Sql_Query(sprintf('
select 
    message_bounce.id
    , message_bounce.message
    , time
    , bounce
    , date_format(time,"%%e %%b %%Y %%T") as ftime
from 
    %s as message_bounce
where 
    user = %d', $tables['user_message_bounce'], $user['id']));

if (Sql_Affected_Rows()) {
    while ($row = Sql_Fetch_Array($req)) {
        $messagedata = loadMessageData($row['message']);
        $bouncels->addElement($row['bounce'],
            PageURL2('bounce', $GLOBALS['I18N']->get('view'), 'id='.$row['bounce']));
        $bouncels->addColumn($row['bounce'], $GLOBALS['I18N']->get('Campaign title'), stripslashes($messagedata['campaigntitle']));
        $bouncels->addColumn($row['bounce'], $GLOBALS['I18N']->get('time'), $row['ftime']);
        $bounces[$row['message']] = $row['ftime'];
    }
}

$ls = new WebblerListing($GLOBALS['I18N']->get('Message'));
if (Sql_Table_Exists($tables['usermessage'])) {
    $msgs = Sql_Query(sprintf('select messageid,entered,viewed,(viewed = 0 or viewed is null) as notviewed,
    abs(unix_timestamp(entered) - unix_timestamp(viewed)) as responsetime from %s where userid = %d and status = "sent" order by entered desc',
        $tables['usermessage'], $user['id']));
    $num = Sql_Affected_Rows();
} else {
    $num = 0;
}
printf('%d '.$GLOBALS['I18N']->get('messages sent to this user').'<br/>', $num);
if ($num) {
    $resptime = 0;
    $totalresp = 0;
    $ls->setElementHeading($GLOBALS['I18N']->get('Campaign Id'));

    while ($msg = Sql_Fetch_Array($msgs)) {
        $ls->addElement($msg['messageid'],
            PageURL2('message', $GLOBALS['I18N']->get('view'), 'id='.$msg['messageid']));
        if (defined('CLICKTRACK') && CLICKTRACK) {
            $clicksreq = Sql_Fetch_Row_Query(sprintf('select sum(clicked) as numclicks from %s where userid = %s and messageid = %s',
                $GLOBALS['tables']['linktrack_uml_click'], $user['id'], $msg['messageid']));
            $clicks = sprintf('%d', $clicksreq[0]);
            if ($clicks) {
                $ls->addColumn($msg['messageid'], $GLOBALS['I18N']->get('clicks'),
                    PageLink2('userclicks&amp;userid='.$user['id'].'&amp;msgid='.$msg['messageid'], $clicks));
            } else {
                $ls->addColumn($msg['messageid'], $GLOBALS['I18N']->get('clicks'), 0);
            }
        }

        $ls->addColumn($msg['messageid'], $GLOBALS['I18N']->get('sent'), formatDateTime($msg['entered'], 1));
        if (!$msg['notviewed']) {
            $ls->addColumn($msg['messageid'], $GLOBALS['I18N']->get('viewed'), formatDateTime($msg['viewed'], 1));
            $ls->addColumn($msg['messageid'], $GLOBALS['I18N']->get('Response time'), $msg['responsetime']);
            $resptime += $msg['responsetime'];
            $totalresp += 1;
        }
        if (!empty($bounces[$msg['messageid']])) {
            $ls->addColumn($msg['messageid'], $GLOBALS['I18N']->get('bounce'), $bounces[$msg['messageid']]);
        }
    }
    if ($totalresp) {
        $avgresp = sprintf('%d', ($resptime / $totalresp));
        $ls->addElement($GLOBALS['I18N']->get('average'));
        $ls->setClass($GLOBALS['I18N']->get('average'), 'row1');
        $ls->addColumn($GLOBALS['I18N']->get('average'), $GLOBALS['I18N']->get('responsetime'), $avgresp);
    }
}

echo '<div class="tabbed">';
echo '<ul>';
echo '<li><a href="#messages">'.ucfirst($GLOBALS['I18N']->get('Campaigns')).'</a></li>';
if (count($bounces)) {
    echo '<li><a href="#bounces">'.ucfirst($GLOBALS['I18N']->get('Bounces')).'</a></li>';
}
echo '<li><a href="#subscription">'.ucfirst($GLOBALS['I18N']->get('Subscription')).'</a></li>';
echo '</ul>';

echo '<div id="messages">';
echo $ls->display();
echo '</div>';
echo '<div id="bounces">';
echo $bouncels->display();
echo '</div>';
echo '<div id="subscription">';

if (isBlackListed($user['email'])) {
    echo '<h3>'.$GLOBALS['I18N']->get('subscriber is blacklisted since').' ';
    $blacklist_info = Sql_Fetch_Array_Query(sprintf('select * from %s where email = "%s"',
        $tables['user_blacklist'], $user['email']));
    echo $blacklist_info['added'].'</h3><br/>';
    echo '';

    $isSpamReport = false;
    $ls = new WebblerListing($GLOBALS['I18N']->get('Blacklist info'));
    $req = Sql_Query(sprintf('select * from %s where email = "%s"',
        $tables['user_blacklist_data'], $user['email']));
    while ($row = Sql_Fetch_Array($req)) {
        $ls->addElement($row['name']);
        $isSpamReport = $isSpamReport || $row['data'] == 'blacklisted due to spam complaints';
        $ls->addColumn($row['name'], $GLOBALS['I18N']->get('value'), stripslashes($row['data']));
    }
    $ls->addElement('<!-- remove -->');
    if (!$isSpamReport) {
        $button = new ConfirmButton(
            htmlspecialchars($GLOBALS['I18N']->get('are you sure you want to delete this subscriber from the blacklist')).'?\\n'.htmlspecialchars($GLOBALS['I18N']->get('it should only be done with explicit permission from this subscriber')),
            PageURL2("userhistory&unblacklist={$user['id']}&id={$user['id']}", 'button',
                s('remove subscriber from blacklist')),
            s('remove subscriber from blacklist'));

        $ls->addRow('<!-- remove -->', s('remove'), $button->show());
    } else {
        $ls->addRow('<!-- remove -->', s('remove'),
            s('For this subscriber to be removed from the blacklist, you need to ask them to re-subscribe using the phpList subscribe page'));
    }
    echo $ls->display();
}

$ls = new WebblerListing($GLOBALS['I18N']->get('Subscription History'));
$ls->setElementHeading($GLOBALS['I18N']->get('Event'));
$req = Sql_Query(sprintf('select * from %s where userid = %d order by id desc', $tables['user_history'], $user['id']));
if (!Sql_Affected_Rows()) {
    echo $GLOBALS['I18N']->get('no details found');
}
while ($row = Sql_Fetch_Array($req)) {
    $ls->addElement($row['id']);
    $ls->setClass($row['id'], 'row1');
    $ls->addColumn($row['id'], $GLOBALS['I18N']->get('ip'), $row['ip']);
    $ls->addColumn($row['id'], $GLOBALS['I18N']->get('date'), $row['date']);
    $ls->addColumn($row['id'], $GLOBALS['I18N']->get('summary'), $row['summary']);
    $ls->addRow($row['id'], "<div class='gray'>".$GLOBALS['I18N']->get('detail').': </div>',
        "<div class='tleft'>".nl2br(htmlspecialchars($row['detail'])).'</div>');
    $ls->addRow($row['id'], "<div class='gray'>".$GLOBALS['I18N']->get('info').': </div>',
        "<div class='tleft'>".nl2br($row['systeminfo']).'</div>');
}

echo $ls->display();
echo '</div>';
echo '</div>'; ## end of tabbed
