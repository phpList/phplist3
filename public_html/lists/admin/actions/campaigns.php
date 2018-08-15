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

$ls = new WebblerListing(s('Campaigns'));
if (Sql_Table_Exists($GLOBALS['tables']['usermessage'])) {
    $msgs = Sql_Query(sprintf('select messageid,entered,viewed,(viewed = 0 or viewed is null) as notviewed,
abs(unix_timestamp(entered) - unix_timestamp(viewed)) as responsetime from %s where userid = %d and status = "sent" order by entered desc',
        $GLOBALS['tables']['usermessage'], $user['id']));
    $num = Sql_Affected_Rows();
} else {
    $num = 0;
}
printf('%d '.s('messages sent to this user').'<br/>', $num);
if ($num) {
    $resptime = 0;
    $totalresp = 0;
    $ls->setElementHeading(s('Campaign Id'));

    while ($msg = Sql_Fetch_Array($msgs)) {
        $ls->addElement($msg['messageid'],
            PageURL2('message', s('view'), 'id='.$msg['messageid']));
        if (defined('CLICKTRACK') && CLICKTRACK) {
            $clicksreq = Sql_Fetch_Row_Query(sprintf('select sum(clicked) as numclicks from %s where userid = %s and messageid = %s',
                $GLOBALS['tables']['linktrack_uml_click'], $user['id'], $msg['messageid']));
            $clicks = sprintf('%d', $clicksreq[0]);
            if ($clicks) {
                $ls->addColumn($msg['messageid'], s('clicks'),
                    PageLink2('userclicks&amp;userid='.$user['id'].'&amp;msgid='.$msg['messageid'], $clicks));
            } else {
                $ls->addColumn($msg['messageid'], s('clicks'), 0);
            }
        }

        $ls->addColumn($msg['messageid'], s('sent'), formatDateTime($msg['entered'], 1));
        if (!$msg['notviewed']) {
            $ls->addColumn($msg['messageid'], s('viewed'), formatDateTime($msg['viewed'], 1));
            $ls->addColumn($msg['messageid'], s('Response time'), secs2time($msg['responsetime']));
            $resptime += $msg['responsetime'];
            $totalresp += 1;
        }
        if (!empty($bounces[$msg['messageid']])) {
            $ls->addColumn($msg['messageid'], s('bounce'), $bounces[$msg['messageid']]);
        }
    }
    if ($totalresp) {
        $avgresp = sprintf('%d', ($resptime / $totalresp));
        $ls->addElement('<strong>'.s('Average response time: ').'</strong>'.secs2time($avgresp));
    }
}

echo $ls->display();
