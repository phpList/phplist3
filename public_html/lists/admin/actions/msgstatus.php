<?php

$id = sprintf('%d',$_GET['id']);
if (!$id) {
  return '';
}
$message = Sql_Fetch_Assoc_Query(sprintf('select *,unix_timestamp(embargo) - unix_timestamp(now()) as secstowait from %s where id = %d',$GLOBALS['tables']['message'],$id));
$messagedata = loadMessageData($id);

$totalsent = $messagedata['astext'] + 
  $messagedata['ashtml'] + 
  $messagedata['astextandhtml'] + 
  $messagedata['aspdf'] + 
  $messagedata['astextandpdf'];
  
$num_users = 0;
if (isset($messagedata['to process'])) {
 $num_users = $messagedata['to process'];
}

$sent = $totaltime = $sampletime = $samplesent = 0;
if (!isset($messagedata['sampletime'])) { ## take a "sample" of the send speed, to calculate msg/hr
  $sampletime = time();
  $samplesent = $totalsent;
  setMessageData($id,'sampletime',$sampletime);
  setMessageData($id,'samplesent',$samplesent);
} else {
  $totaltime = time() - $messagedata['sampletime'];
  $sent = $totalsent - $messagedata['samplesent'];
  if ($totaltime > MESSAGE_SENDSTATUS_SAMPLETIME) { ## refresh speed sampling
    setMessageData($id,'sampletime',time());
    setMessageData($id,'samplesent',$totalsent);
  }
}

if ($sent > 0 && $totaltime > 0) {
  $msgperhour = (3600/$totaltime) * $sent;
  $secpermsg = $totaltime / $sent;
  $timeleft = ($num_users - $sent) * $secpermsg;
  $eta = date('D j M H:i',time()+$timeleft);
} else {
  $msgperhour = 0;
  $secpermsg = 0;
  $timeleft = 0;
  $eta = $GLOBALS['I18N']->get('unknown');
}
setMessageData($id,'ETA',$eta);
setMessageData($id,'msg/hr',$msgperhour);

if ($message['status'] != 'inprocess') {
  $html = $GLOBALS['I18N']->get($message['status']);
  
  if ($message['secstowait'] > 0) {
    $secstowait = secs2time($message['secstowait']);
    $html .= '<br/>'.sprintf($GLOBALS['I18N']->get('%s left until embargo'),$secstowait);
  }
  foreach ($GLOBALS['plugins'] as $plname => $plugin) {
    $html .= $plugin->messageStatus($id,$message['status']);
  }
  
  if ($message['status'] != 'submitted' && $message['status'] != 'draft') {
    $html .= '<br/>'.PageLinkButton("messages",$GLOBALS['I18N']->get("requeue"),"resend=".$message["id"]);
  }
  if (!empty($messagedata['to process'])) {
    $html .= '<br/>'.$messagedata['to process'].' '.$GLOBALS['I18N']->get('still to process').'<br/>'.
    $GLOBALS['I18N']->get('sent').': '.$totalsent;
  }
} else {
  if (empty($messagedata['last msg sent'])) $messagedata['last msg sent'] = 0;
  if (empty($messagedata['to process'])) $messagedata['to process'] = $GLOBALS['I18N']->get('Unknown');
  
  $active = time() - $messagedata['last msg sent'];
  $html = $GLOBALS['I18N']->get($message['status']).'<br/>';
  if ($messagedata['to process'] > 0) {
    $html .= $messagedata['to process'].' '.$GLOBALS['I18N']->get('still to process').'<br/>';
  }
  foreach ($GLOBALS['plugins'] as $plname => $plugin) {
    $html .= $plugin->messageStatus($id,$message['status']);
  }
  ## not sure this calculation is accurate
#  $html .= $GLOBALS['I18N']->get('sent').': '.$totalsent.'<br/>';
  $recently_sent = Sql_Fetch_Row_Query(sprintf('select count(*) from %s where entered > date_sub(current_timestamp,interval %d second) and status = "sent"',
    $tables["usermessage"],MAILQUEUE_BATCH_PERIOD));
  if (MAILQUEUE_BATCH_PERIOD && $recently_sent[0] >= MAILQUEUE_BATCH_SIZE) {
    $html .= '<h4>'.$GLOBALS['I18N']->get('limit reached').'</h4>';
    foreach ($GLOBALS['plugins'] as $plname => $plugin) {
      $html .= $plugin->messageStatusLimitReached($recently_sent[0]);
    }
    $nextbatch = Sql_Fetch_Row_Query(sprintf('select current_timestamp,date_add(entered,interval %d second) from %s where entered > date_sub(current_timestamp,interval %d second) and status = "sent"',
      MAILQUEUE_BATCH_PERIOD,$tables["usermessage"],MAILQUEUE_BATCH_PERIOD));
    $html .= '<p>'.sprintf($GLOBALS['I18N']->get('next batch of %s in %s'),MAILQUEUE_BATCH_SIZE,timeDiff($nextbatch[0],$nextbatch[1])).'</p>';
    
  } elseif ($msgperhour<= 0 || $active > MESSAGE_SENDSTATUS_INACTIVETHRESHOLD) {
    $html .= $GLOBALS['I18N']->get('Stalled');
  } else {
    $html .= 
    $GLOBALS['I18N']->get('ETA').': '.$eta.'<br/>'.
    $GLOBALS['I18N']->get('Processing').' '.sprintf('%d',$msgperhour).' '.$GLOBALS['I18N']->get('msgs/hr');
  }
}

if (!empty($GLOBALS['developer_email1'])) {
  if (isset($messagedata['sampletime'])) $html .= '<br/>ST: '.$messagedata['sampletime'];
  if (isset($messagedata['samplesent'])) $html .= '<br/>SS: '.$messagedata['samplesent'];
  if (isset($totaltime)) $html .= '<br/>TT: '.$totaltime;
  if (isset($sent)) $html .= '<br/>TS: '.$sent;
}

$status = $html;
#exit;
?>
