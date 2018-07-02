<?php

require_once dirname(__FILE__).'/accesscheck.php';
$access = accessLevel('msgbounces');

$messageid = empty($_GET['id']) ? 0 : sprintf('%d', $_GET['id']);

$isowner_and = '';
$isowner_where = '';

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
if (!$messageid) {
    //# for testing the loader allow a delay flag
    if (isset($_GET['delay'])) {
        $_SESSION['LoadDelay'] = sprintf('%d', $_GET['delay']);
    } else {
        unset($_SESSION['LoadDelay']);
    }
    echo '<div id="contentdiv"></div>';
    echo asyncLoadContent('./?page=pageaction&action=msgbounces&ajaxed=true&id='.$messageid.addCsrfGetToken());

    return;
}

$userTable = $GLOBALS['tables']['user'];

$messageBounceTable = $GLOBALS['tables']['user_message_bounce'];
$query= "select  u.id as userid, u.email, mb.time from $messageBounceTable mb join $userTable u
on u.id = mb.user where mb.message = '$messageid' ";
var_dump($query);

$req = Sql_Query($query);

$total = Sql_Affected_Rows();
$limit = '';
$numpp = 150;
$start = empty($_GET['start']) ? 0 : sprintf('%d', $_GET['start']);
if ($total > $numpp ) {
    $limit = "limit $start,".$numpp;
    echo simplePaging('msgbounces&amp;id='.$messageid, $start, $total, $numpp);

    $query .= $limit;
    $req = Sql_Query($query);


}
$messagedata = loadMessageData($messageid);
$bouncels = new WebblerListing(s('Bounces on').' '.shortenTextDisplay($messagedata['subject'], 30));
$bouncels->setElementHeading('Subscriber ID');
while ($row = Sql_Fetch_Array($req)) {

    $bouncels->addElement($row['userid'], PageUrl2('user&amp;id='.$row['userid']));
    $bouncels->addColumn($row['userid'], s('Subscriber address'), PageLink2('user&id='.$row['userid'], $row['email']));
    $bouncels->addColumn($row['userid'], s('Time'), $row['time']);



}
echo $bouncels->display();
