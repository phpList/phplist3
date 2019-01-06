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
, date_format(time,"%%e %%b %%Y %%T") as ftime
from
%s as message_bounce
where
user = %d', $GLOBALS['tables']['user_message_bounce'], $user['id']));

if (Sql_Affected_Rows()) {
    while ($row = Sql_Fetch_Array($req)) {
        $messagedata = loadMessageData($row['message']);
        $bouncels->addElement($row['bounce'],
            PageURL2('bounce', s('view'), 'id=' . $row['bounce']));
        $bouncels->addColumn($row['bounce'], s('Campaign title'), stripslashes($messagedata['campaigntitle']));
        $bouncels->addColumn($row['bounce'], s('time'), $row['ftime']);
    }
    echo $bouncels->display();
} else {
    echo s("Bounces data not found");
}
