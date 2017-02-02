<?php

$id = sprintf('%d', $_GET['id']);
if (!$id) {
    return '';
}
$message = Sql_Fetch_Assoc_Query(sprintf('select *,unix_timestamp(embargo) - unix_timestamp(now()) as secstowait from %s where id = %d',
    $GLOBALS['tables']['message'], $id));
$messagedata = loadMessageData($id);
$pqchoice = getConfig('pqchoice');

$totalsent = $messagedata['astext'] +
    $messagedata['ashtml'] +
    $messagedata['astextandhtml'] +
    $messagedata['aspdf'] +
    $messagedata['astextandpdf'] +
    $messagedata['sentastest'];

$num_users = 0;
if (isset($messagedata['to process'])) {
    $num_users = $messagedata['to process'];
}

$sent = $totaltime = $sampletime = $samplesent = 0;
if (!isset($messagedata['sampletime'])) { //# take a "sample" of the send speed, to calculate msg/hr
    $sampletime = time();
    $samplesent = $totalsent;
    setMessageData($id, 'sampletime', $sampletime);
    setMessageData($id, 'samplesent', $samplesent);
} else {
    $totaltime = time() - $messagedata['sampletime'];
    $sent = $totalsent - $messagedata['samplesent'];
    if ($totaltime > MESSAGE_SENDSTATUS_SAMPLETIME) { //# refresh speed sampling
        setMessageData($id, 'sampletime', time());
        setMessageData($id, 'samplesent', $totalsent);
    }
}

if ($sent > 0 && $totaltime > 0) {
    $msgperhour = (int) (3600 / $totaltime) * $sent;
    $secpermsg = $totaltime / $sent;
    $timeleft = ($num_users - $sent) * $secpermsg;
    $eta = date('D j M H:i', time() + $timeleft);
} else {
    $msgperhour = 0;
    $secpermsg = 0;
    $timeleft = 0;
    $eta = $GLOBALS['I18N']->get('unknown');
}

//# 16850 - convert to string, to avoid an SQL error
$msgperhour = "$msgperhour ";

setMessageData($id, 'ETA', $eta);
setMessageData($id, 'msg/hr', $msgperhour);

if ($message['status'] != 'inprocess') {
    $html = $GLOBALS['I18N']->get($message['status']);

    if ($message['secstowait'] > 0) {
        $secstowait = secs2time($message['secstowait']);
        $html .= '<br/>'.sprintf($GLOBALS['I18N']->get('%s left until embargo'), $secstowait);
    }
    foreach ($GLOBALS['plugins'] as $plname => $plugin) {
        $html .= $plugin->messageStatus($id, $message['status']);
    }

    if ($message['status'] != 'submitted' && $message['status'] != 'draft') {
        $html .= '<br/>'.PageLinkButton('messages', $GLOBALS['I18N']->get('requeue'), 'resend='.$message['id'], '',
                s('Requeue'));
    }
    if (!empty($messagedata['to process'])) {
        $html .= '<br/>'.$messagedata['to process'].' '.$GLOBALS['I18N']->get('still to process').'<br/>'.
            $GLOBALS['I18N']->get('sent').': '.$totalsent;
    }
} else {
    if (empty($messagedata['last msg sent'])) {
        $messagedata['last msg sent'] = 0;
    }
    if (empty($messagedata['to process'])) {
        $messagedata['to process'] = $GLOBALS['I18N']->get('Unknown');
    }

    $active = time() - $messagedata['last msg sent'];
    $html = $GLOBALS['I18N']->get($message['status']).'<br/>';
    if ($messagedata['to process'] > 0) {
        $html .= $messagedata['to process'].' '.$GLOBALS['I18N']->get('still to process').'<br/>';
    }
    $pluginhtml = '';
    foreach ($GLOBALS['plugins'] as $plname => $plugin) {
        $pluginhtml .= $plugin->messageStatus($id, $message['status']);
    }

    //# if the plugins don't return anything do the speed calculation
    //# otherwise just what the plugins retunr
    if (empty($pluginhtml)) {
        //# not sure this calculation is accurate
        //  $html .= $GLOBALS['I18N']->get('sent').': '.$totalsent.'<br/>';
        $recently_sent = Sql_Fetch_Row_Query(sprintf('select count(*) from %s where entered > date_sub(now(),interval %d second) and status = "sent"',
            $tables['usermessage'], MAILQUEUE_BATCH_PERIOD));
        if (MAILQUEUE_BATCH_PERIOD && MAILQUEUE_BATCH_SIZE && $recently_sent[0] >= MAILQUEUE_BATCH_SIZE) {
            $html .= '<h4>'.$GLOBALS['I18N']->get('limit reached').'</h4>';
            foreach ($GLOBALS['plugins'] as $plname => $plugin) {
                $html .= $plugin->messageStatusLimitReached($recently_sent[0]);
            }
            $nextbatch = Sql_Fetch_Row_Query(sprintf('select now(),date_add(entered,interval %d second) from %s where entered > date_sub(now(),interval %d second) and status = "sent" order by entered desc limit 1',
                MAILQUEUE_BATCH_PERIOD + 60, $tables['usermessage'], MAILQUEUE_BATCH_PERIOD));
            $html .= '<p>'.sprintf($GLOBALS['I18N']->get('next batch of %s in %s'), MAILQUEUE_BATCH_SIZE,
                    timeDiff($nextbatch[0], $nextbatch[1])).'</p>';
        } elseif ($msgperhour <= 0 || $active > MESSAGE_SENDSTATUS_INACTIVETHRESHOLD) {
            if (MANUALLY_PROCESS_QUEUE) {
                $html .= $GLOBALS['I18N']->get('Waiting');
                if ($pqchoice == 'local') {
                    $html .= PageLinkButton('processqueue', s('Send the queue'));
                } elseif ($pqchoice == 'phplistdotcom') {
                    $html .= '<a href="https://www.phplist.com/myaccount" target="_blank" class="button">'.s('Check status').'</a>';
                }
            } else {
                $html .= $GLOBALS['I18N']->get('Processing');
            }
        } else {
            $html .=
                $GLOBALS['I18N']->get('ETA').': '.$eta.'<br/>'.
                $GLOBALS['I18N']->get('Processing').' '.sprintf('%d',
                    $msgperhour).' '.$GLOBALS['I18N']->get('msgs/hr');
        }
    } else {
        $html .= $pluginhtml;
    }
}

if (!empty($GLOBALS['developer_email1'])) {
    if (isset($messagedata['sampletime'])) {
        $html .= '<br/>ST: '.$messagedata['sampletime'];
    }
    if (isset($messagedata['samplesent'])) {
        $html .= '<br/>SS: '.$messagedata['samplesent'];
    }
    if (isset($totaltime)) {
        $html .= '<br/>TT: '.$totaltime;
    }
    if (isset($sent)) {
        $html .= '<br/>TS: '.$sent;
    }
}

$status = $html;
#exit;
