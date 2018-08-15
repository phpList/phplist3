<?php

require_once dirname(__FILE__).'/../accesscheck.php';
if (!defined('PHPLISTINIT')) {
    exit;
}

if (!$_GET['id']) {
    Fatal_Error(s('no such User'));

    return;
} else {
    $id = sprintf('%d', $_GET['id']);
}
$status = '';

$access = accessLevel('user');

switch ($access) {
    case 'owner':
        $subselect = ' and '.$GLOBALS['tables']['list'].'.owner = '.$_SESSION['logindetails']['id'];
        break;
    case 'all':
        $subselect = '';
        break;
    case 'view':
        $subselect = '';
        if (count($_POST) || $_GET['unblacklist']) {
            echo Error(s('you only have privileges to view this page, not change any of the information'));

            return;
        }
        break;
    case 'none':
    default:
        $subselect = ' and '.$GLOBALS['tables']['list'].'.id = 0';
        break;
}



$result = Sql_query("SELECT * FROM {$GLOBALS['tables']['user']} where id = $id");
if (!Sql_Affected_Rows()) {
    Fatal_Error(s('no such User'));

    return;
}
$user = sql_fetch_array($result);

if (isBlackListed($user['email'])) {
    echo '<h3>'.s('subscriber is blacklisted since').' ';
    $blacklist_info = Sql_Fetch_Array_Query(sprintf('select * from %s where email = "%s"',
        $GLOBALS['tables']['user_blacklist'], $user['email']));
    echo formatDateTime($blacklist_info['added']).'</h3><br/>';
    echo '';

    $isSpamReport = false;
    $ls = new WebblerListing(s('Blacklist info'));
    $req = Sql_Query(sprintf('select * from %s where email = "%s"',
        $GLOBALS['tables']['user_blacklist_data'], $user['email']));
    while ($row = Sql_Fetch_Array($req)) {
        $ls->addElement(s($row['name']));
        $isSpamReport = $isSpamReport || $row['data'] == 'blacklisted due to spam complaints';
        $ls->addColumn(s($row['name']), s('value'), stripslashes($row['data']));
    }
    $ls->addElement('<!-- remove -->');
    if (!$isSpamReport) {
        $button = new ConfirmButton(
            htmlspecialchars(s('are you sure you want to delete this subscriber from the blacklist')).'?\\n'.htmlspecialchars(s('it should only be done with explicit permission from this subscriber')),
            PageURL2("user&unblacklist={$user['id']}&id={$user['id']}", 'button',
                s('remove subscriber from blacklist')),
            s('remove subscriber from blacklist'));

        $ls->addRow('<!-- remove -->', s('remove'), $button->show());
    } else {
        $ls->addRow('<!-- remove -->', s('remove'),
            s('For this subscriber to be removed from the blacklist, you need to ask them to re-subscribe using the phpList subscribe page'));
    }
    echo $ls->display();
}

$ls = new WebblerListing(s('Subscription History'));
$ls->setElementHeading(s('Event'));
$req = Sql_Query(sprintf('select * from %s where userid = %d order by id desc', $GLOBALS['tables']['user_history'], $user['id']));
if (!Sql_Affected_Rows()) {
    echo s('no details found');
}
while ($row = Sql_Fetch_Array($req)) {
    $ls->addElement($row['id']);
    $ls->setClass($row['id'], 'row1');
    $ls->addColumn($row['id'], s('ip'), $row['ip']);
    $ls->addColumn($row['id'], s('date'), formatDateTime($row['date']));
    $ls->addColumn($row['id'], s('summary'), $row['summary']);
    $ls->addRow(
        $row['id']
        , "<div class='gray'><strong>".s('detail').'</strong></div>'
        , "<div class='tleft'>".
        nl2br(
            htmlspecialchars(
                $row['detail']
            )
        ).'</div>'
    );
// nl2br inserts leading <br/> elements and unnecessary whitespace; preg_replace removes this
    $ls->addRow(
        $row['id']
        , "<div class='gray'><strong>".s('info').'</strong></div>'
        , "<div class='tleft'>".
        preg_replace(
            "|^(?:<br />[\n\r]+)*(.*?)(?:<br />[\n\r]+)*$|s"
            , '$1'
            , nl2br(
                htmlspecialchars_decode(
                    $row['systeminfo']
                )
            )
        ).'</div>'
    );
}
echo $ls->display();