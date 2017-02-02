<?php

require_once dirname(__FILE__).'/accesscheck.php';

@ob_end_clean();
$id = sprintf('%d', $_GET['id']);
if (!$id) {
    return '';
}
$message = Sql_Fetch_Assoc_Query(sprintf('select * from %s where id = %d', $GLOBALS['tables']['message'], $id));
$messagedata = loadMessageData($id);

if ($message['status'] != 'inprocess') {
    $html = $message['status'];
    $html .= '<br/>'.PageLink2('messages', $GLOBALS['I18N']->get('requeue'), 'resend='.$message['id']);
} else {
    $active = time() - $messagedata['last msg sent'];
    if ($active > 60) {
        $html = $GLOBALS['I18N']->get('Stalled');
    } else {
        $totalsent = $messagedata['astext'] +
            $messagedata['ashtml'] +
            $messagedata['astextandhtml'] +
            $messagedata['aspdf'] +
            $messagedata['astextandpdf'];
        $html = $GLOBALS['I18N']->get($message['status']).'<br/>'.
            $messagedata['to process'].' '.$GLOBALS['I18N']->get('still to process').'<br/>'.
            $GLOBALS['I18N']->get('ETA').': '.$messagedata['ETA'].'<br/>'.
            $GLOBALS['I18N']->get('sent').': '.$totalsent.'<br/>'.
            $GLOBALS['I18N']->get('Processing').' '.sprintf('%d',
                $messagedata['msg/hr']).' '.$GLOBALS['I18N']->get('msgs/hr');
    }
}

echo $html;
exit;
