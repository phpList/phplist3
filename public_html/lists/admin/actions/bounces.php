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
$status = "";

$result = Sql_query("SELECT * FROM {$GLOBALS['tables']['user']} where id = $id");
if (!Sql_Affected_Rows()) {
    Fatal_Error(s('no such User'));

    return;
}
$user = sql_fetch_array($result);

$bouncels = new WebblerListing(s('Bounces'));
$bouncels->setElementHeading('Bounce ID');
$bouncelist = '';
// check for bounces
$req = Sql_Query(sprintf('
select
message_bounce.id
, message_bounce.message
, time
, bounce
from
%s as message_bounce
where
user = %d', $GLOBALS['tables']['user_message_bounce'], $user['id']));

if (Sql_Affected_Rows()) {
    while ($row = Sql_Fetch_Array($req)) {
        $messagedata = loadMessageData($row['message']);
        $bouncels->addElement($row['bounce'],
            PageURL2('bounce', s('view'), 'id=' . $row['bounce']));
        if (!empty($messagedata['id'])) {
            $bouncels->addColumn($row['bounce'], s('Campaign title'), stripslashes($messagedata['campaigntitle']));
        } else {
            $bouncels->addColumn($row['bounce'], s('Campaign title'), s('Transactional message'));
        }
        $bouncels->addColumn($row['bounce'], s('time'), formatDateTime($row['time'], true));
    }
    echo $bouncels->display();
} else {
    echo s("Bounces data not found");
}
