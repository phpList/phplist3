<?php
require_once dirname(__FILE__).'/accesscheck.php';

$processqueue_timer = new timer();
$domainthrottle = array();
$send_process_id = 0;
if (isset($_GET['reload'])) {
  $reload = sprintf('%d',$_GET['reload']);
} else {
  $reload = 0;
}

if (!$GLOBALS["commandline"]) {
  @ob_end_flush();
  if (!MANUALLY_PROCESS_QUEUE) {
    print "This page can only be called from the commandline";
    return;
  }
} else {
  @ob_end_clean();
  print ClineSignature();
  # check for other processes running
  if (isset($cline['f'])) {
    # force set, so kill other processes
    $send_process_id = getPageLock(1);
  } else {
    $send_process_id = getPageLock();
  }
  ob_start();
}

# once and for all get rid of those questions why they do not receive any emails :-)
if (TEST)
  print '<p class="information">'.$GLOBALS['I18N']->get('Running in testmode, no emails will be sent. Check your config file.').'</p>';

$num_per_batch = 0;
$batch_period = 0;
$script_stage = 0; # start
$someusers = $skipped = 0;

$maxbatch = -1;
$minbatchperiod = -1;
# check for batch limits
$ISPrestrictions = '';
$ISPlockfile = '';
//$rssitems = array(); //Obsolete by rssmanager plugin 
$user_attribute_query = '';
$lastsent = !empty($_GET['lastsent']) ? sprintf('%d',$_GET['lastsent']):0;
$lastskipped = !empty($_GET['lastskipped']) ? sprintf('%d',$_GET['lastskipped']):0;

if ($fp = @fopen("/etc/phplist.conf","r")) {
  $contents = fread($fp, filesize("/etc/phplist.conf"));
  fclose($fp);
  $lines = explode("\n",$contents);
  $ISPrestrictions = $GLOBALS['I18N']->get('The following restrictions have been set by your ISP:')."\n";
  foreach ($lines as $line) {
    list($key,$val) = explode("=",$line);

    switch ($key) {
      case "maxbatch": $maxbatch = sprintf('%d',$val);$ISPrestrictions .= "$key = $val\n";break;
      case "minbatchperiod": $minbatchperiod = sprintf('%d',$val);$ISPrestrictions .= "$key = $val\n";break;
      case "lockfile": $ISPlockfile = $val;
    }
  }
}

if (MAILQUEUE_BATCH_SIZE) {
  if ($maxbatch > 0) {
    $num_per_batch = min(MAILQUEUE_BATCH_SIZE,$maxbatch);
  } else {
    $num_per_batch = sprintf('%d',MAILQUEUE_BATCH_SIZE);
  }
} else {
  if ($maxbatch > 0) {
    $num_per_batch = $maxbatch;
  }
}

if (MAILQUEUE_BATCH_PERIOD) {
  if ($minbatchperiod > 0) {
    $batch_period = max(MAILQUEUE_BATCH_PERIOD,$minbatchperiod);
  } else {
    $batch_period = MAILQUEUE_BATCH_PERIOD;
  }
}

## force batch processing in small batches when called from the web interface
if (empty($GLOBALS['commandline'])) {
  $num_per_batch = min($num_per_batch,50);
  $batch_period = max($batch_period,10);
} elseif (isset($cline['m'])) {
  $cl_num_per_batch = sprintf('%d',$cline['m']);
  ## don't block when the param is not a number
  if (!empty($cl_num_per_batch)) {
    $num_per_batch = $cl_num_per_batch;
  }
  cl_output("Batch set with commandline to $num_per_batch");
}

$safemode = 0;
if (ini_get("safe_mode")) {
  # keep an eye on timeouts
  $safemode = 1;
  $num_per_batch = min(100,$num_per_batch);
#  Fatal_Error("Process queue will not work in safe mode");
#  return;
  print $GLOBALS['I18N']->get('Running in safe mode').'<br/>';
}

if ($num_per_batch && $batch_period) {
  # check how many were sent in the last batch period and take off that
  # amount from this batch
  $original_num_per_batch = $num_per_batch;
/*
  output(sprintf('select count(*) from %s where entered > date_sub(current_timestamp,interval %d second) and status = "sent"',
    $tables["usermessage"],$batch_period));
*/
  $recently_sent = Sql_Fetch_Row_Query(sprintf('select count(*) from %s where entered > date_sub(current_timestamp,interval %d second) and status = "sent"',
    $tables["usermessage"],$batch_period));
  output('Recently sent : '.$recently_sent[0]);
  $num_per_batch -= $recently_sent[0];

  # if this ends up being 0 or less, don't send anything at all
  if ($num_per_batch == 0) {
    $num_per_batch = -1;
  }
}
# output some stuff to make sure it's not buffered in the browser
for ($i=0;$i<10000; $i++) {
  print '  ';
  if ($i%100 == 0) print "\n";
}
flush();

#print '<script language="Javascript" src="js/progressbar.js" type="text/javascript"></script>';
#print '<script language="Javascript" type="text/javascript"> yposition = 10;document.write(progressmeter); start();</script>';
print '<div id="progressbar"></div>
<script type="text/javascript">
$("#progressbar").progressbar({
  value: 0;
});
</script>
';
flush();
if (empty($_GET['ajaxed'])) {
  print formStart('name="outputform" class="processqueueOutput" ').'<textarea name="output" rows="22" cols="75"></textarea></form>';
}

# report keeps track of what is going on
$report = "";
$nothingtodo = 0;
$cached = array(); # cache the message from the database to avoid reloading it every time

require_once dirname(__FILE__) .'/sendemaillib.php';
//obsolete, moved to rssmanager plugin 
//if (ENABLE_RSS) {
//  require_once dirname(__FILE__) .'/plugins/rssmanager/rsslib.php';
//}

function my_shutdown () {
  global $script_stage,$reload;
#  output( "Script status: ".connection_status(),0); # with PHP 4.2.1 buggy. http://bugs.php.net/bug.php?id=17774
  output( $GLOBALS['I18N']->get('Script stage').': '.$script_stage,0);
  global $counters,$report,$send_process_id,$tables,$nothingtodo,$invalid,$processed,$failed_sent,$notsent,$sent,$unconfirmed,$num_per_batch,$batch_period,$num_users;
  $some = $processed; #$sent;# || $invalid || $notsent;
  if (!$some) {
    output($GLOBALS['I18N']->get('Finished, Nothing to do'),0);
    $nothingtodo = 1;
  }

  $totaltime = $GLOBALS['processqueue_timer']->elapsed(1);
  $msgperhour = (3600/$totaltime) * $sent;
  if ($sent)
    output(sprintf('%d %s %01.2f %s (%d %s)',$sent,$GLOBALS['I18N']->get('messages sent in'),
      $totaltime,$GLOBALS['I18N']->get('seconds'),$msgperhour,$GLOBALS['I18N']->get('msgs/hr')),$sent);
  if ($invalid)
    output(sprintf('%d %s',$invalid,$GLOBALS['I18N']->get('invalid emails')));
  if ($failed_sent) {
    output(sprintf('%d %s',$failed_sent,$GLOBALS['I18N']->get('emails failed (will retry later)')));
    foreach ($counters as $label => $value) {
      output(sprintf('%d %s',$value,$GLOBALS['I18N']->get($label)));
    }
  }
  if ($unconfirmed)
    output(sprintf('%d %s',$unconfirmed,$GLOBALS['I18N']->get('emails unconfirmed (not sent)')));

  foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
    $plugin->processSendStats($sent,$invalid,$failed_sent,$unconfirmed);
  }

  flushClickTrackCache();
  releaseLock($send_process_id);

  finish("info",$report,$script_stage);
  if ($script_stage < 5 && !$nothingtodo) {
    output ($GLOBALS['I18N']->get('Warning: script never reached stage 5')."\n".$GLOBALS['I18N']->get('This may be caused by a too slow or too busy server')." \n");
  } elseif( $script_stage == 5 && (!$nothingtodo || $GLOBALS["wait"]))  {
    # if the script timed out in stage 5, reload the page to continue with the rest
    $reload++;
    if (!$GLOBALS["commandline"] && $num_per_batch && $batch_period) {
      if ($sent + 10 < $GLOBALS["original_num_per_batch"] && !$GLOBALS["wait"]) {
        output($GLOBALS['I18N']->get('Less than batch size were sent, so reloading imminently'));
        $delaytime = 10000;
      } else {
        output(sprintf($GLOBALS['I18N']->get('Waiting for %d seconds before reloading'),$batch_period));
        $delaytime = $batch_period * 1000;
      }
      //output("Do not reload this page yourself, because the next batch would fail");
      printf( '<script language="Javascript" type="text/javascript">
        function reload() {
          var query = window.location.search;
          query = query.replace(/&reload=\d+/,"");
          query = query.replace(/&lastsent=\d+/,"");
          query = query.replace(/&lastskipped=\d+/,"");
          document.location = document.location.pathname + query + "&reload=%d&lastsent=%d&lastskipped=%d";
        }
        setTimeout("reload()",%d);
      </script>',$reload,$sent,$notsent,$delaytime);
    } else {
      printf( '<script language="Javascript" type="text/javascript">
        var query = window.location.search;
        query = query.replace(/&reload=\d+/,"");
        query = query.replace(/&lastsent=\d+/,"");
        query = query.replace(/&lastskipped=\d+/,"");
        document.location = document.location.pathname + query + "&reload=%d&lastsent=%d&lastskipped=%d";
      </script>',$reload,$sent,$notsent);
      output($GLOBALS['I18N']->get(($processed < $num_users)?'Reload required':''));
    }
  #  print '<script language="Javascript" type="text/javascript">alert(document.location)</script>';
  }  elseif ($script_stage == 6 || $nothingtodo) {
    foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
      $plugin->messageQueueFinished();
    }
    output($GLOBALS['I18N']->get('Finished, All done'),0);
  } else {
    output($GLOBALS['I18N']->get('Script finished, but not all messages have been sent yet.'));
  }
  if (empty($GLOBALS['commandline']) && empty($_GET['ajaxed'])) {
    include_once "footer.inc";
  } 
  exit;
}

register_shutdown_function("my_shutdown");

## some general functions
function finish ($flag,$message,$script_stage) {
  global $nothingtodo;
  if (!$GLOBALS["commandline"]) {
    print '<script language="Javascript" type="text/javascript"> finish(); </script>';
  }
  if ($flag == "error") {
    $subject = "Maillist Errors";
  } elseif ($flag == "info") {
    $subject = "Maillist Processing info";
  }
  if (!$nothingtodo) {
    output($GLOBALS['I18N']->get('Finished this run'));
  } 
  if (!TEST && !$nothingtodo && SEND_QUEUE_PROCESSING_REPORT) {
    foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
      $plugin->sendReport($subject,$message);
    }
  }
}

function ProcessError ($message) {
  global $report;
  $report .= $message;
  output( "$message");
#  finish("error",$message);
#  include "footer.inc";
  exit;
}

function output ($message,$logit = 1) {
  global $report,$shadecount;
  if (!isset($shadecount)) $shadecount = 0;
  if (is_array($message)) {
    $tmp = '';
    foreach ($message as $key => $val) {
      $tmp .= $key .'='.$val.'; ';
    }
    $message = $tmp;
  }
  if ($GLOBALS["commandline"]) {
    @ob_end_clean();
#    $mem = memory_get_usage(true);
    print $GLOBALS['installation_name'].' - '. strip_tags($message).' ['.$GLOBALS['processqueue_timer']->interval(1).'] ('.$GLOBALS["pagestats"]["number_of_queries"].')';
#    print ' '.formatBytes($mem) . ' '.$mem;
    print "\n";
    $infostring = '';
    ob_start();
  } else {
    $infostring = "[". date("D j M Y H:i",time()) . "] [" . $_SERVER["REMOTE_ADDR"] ."]";
    #print "$infostring $message<br/>\n";
    $lines = explode("\n",$message);
    foreach ($lines as $line) {
      $line = preg_replace('/"/','\"',$line);

      ## contribution in forums, http://forums.phplist.com/viewtopic.php?p=14648
      //Replace the "&rsquo;" which is not replaced by html_decode
      $line = preg_replace("/&rsquo;/","'",$line);
      //Decode HTML chars
      $line = html_entity_decode($line,ENT_QUOTES,'UTF-8');
      # hmm, language switch form is now in the page as well....
      if (empty($_GET['ajaxed'])) {
        print '<script language="Javascript" type="text/javascript">
            document.outputform.output.value += "'.$line.'";
            document.outputform.output.value += "\n";
        </script>'."\n";
      } else {
        print '<div class="output shade'.$shadecount.'">'.$line.'</div>';
        $shadecount = !$shadecount;
      }
    }
    flush();
  }

  $report .= "\n$infostring $message";
  if ($logit)
    logEvent($message);
  flush();
}

function sendEmailTest ($messageid,$email) {
  global $report;
  if (VERBOSE)
    output($GLOBALS['I18N']->get('(test)').' '.$GLOBALS['I18N']->get('Would have sent').' '. $messageid .$GLOBALS['I18N']->get('to').' '. $email);
  else
    $report .= "\n".$GLOBALS['I18N']->get('(test)').' '.$GLOBALS['I18N']->get('Would have sent').' '. $messageid.$GLOBALS['I18N']->get('to').' '. $email;
}

# we don not want to timeout or abort
$abort = ignore_user_abort(1);
set_time_limit(600);
flush();

output($GLOBALS['I18N']->get('Started'),0);
if (defined('SYSTEM_TIMEZONE')) {
  output($GLOBALS['I18N']->get('Time now ').date('Y-m-d H:i'));
}
# check for other processes running
if (!$send_process_id) {
  $send_process_id = getPageLock();
}

if ($ISPrestrictions != "") {
  output($ISPrestrictions);
}
if (is_file($ISPlockfile)) {
  ProcessError($GLOBALS['I18N']->get('Processing has been suspended by your ISP, please try again later'),1);
}

if ($num_per_batch > 0) {
  if ($safemode) {
    output($GLOBALS['I18N']->get('In safe mode, batches are set to a maximum of 100'));
  }
  if ($original_num_per_batch != $num_per_batch) {
    output($GLOBALS['I18N']->get('Sending in batches of').' '.$original_num_per_batch.' '. $GLOBALS['I18N']->get('emails'),0);
    $diff = $original_num_per_batch - $num_per_batch;
    if ($diff < 0) $diff = 0;
    output($GLOBALS['I18N']->get('This batch will be').' '. $num_per_batch.' '.$GLOBALS['I18N']->get('emails, because in the last').' '. $batch_period.' '.$GLOBALS['I18N']->get('seconds').' '. $diff.' '.$GLOBALS['I18N']->get('emails were sent'),0);
  } else {
    output($GLOBALS['I18N']->get('Sending in batches of').' '. $num_per_batch.' '.$GLOBALS['I18N']->get('emails'),0);
  }
} elseif ($num_per_batch < 0) {
  output($GLOBALS['I18N']->get('In the last').' '. $batch_period .' '.$GLOBALS['I18N']->get('seconds more emails were sent')." ($recently_sent[0]) ".$GLOBALS['I18N']->get('than is currently allowed per batch')." ($original_num_per_batch).",0);
  $processed = 0;
  $script_stage = 5;
  $GLOBALS["wait"] = $batch_period;
  return;
}

//$rss_content_threshold = sprintf('%d',getConfig("rssthreshold")); // obsolete, moved to rssmanager plugin
if ($reload) {
#  output("Reload count: $reload");
#  output("Total processed: ".$reload * $num_per_batch);
  output($GLOBALS['I18N']->get('Sent in last run').": $lastsent");
  output($GLOBALS['I18N']->get('Skipped in last run').": $lastskipped");
}

$script_stage = 1; # we are active
$notsent = $sent = $invalid = $unconfirmed = $cannotsend = 0;

## check for messages that need requeuing
$req = Sql_Query(sprintf('select id,requeueinterval,embargo < now() as inthepast from %s where requeueinterval > 0 and requeueuntil > now() and status = "sent"',$tables['message']));
while ($msg = Sql_Fetch_Assoc($req)) {
  if ($msg['inthepast']) {
    Sql_query(sprintf('update %s set status = "submitted",embargo = date_add(now(),interval %d minute) where id = %d',$GLOBALS['tables']['message'],$msg['requeueinterval'],$msg['id']));
  } else {
    Sql_query(sprintf('update %s set status = "submitted",embargo = date_add(embargo,interval %d minute) where id = %d',$GLOBALS['tables']['message'],$msg['requeueinterval'],$msg['id']));
  }
  ## @@@ need to update message data as well
}

$messagelimit = '';
## limit the number of campaigns to work on 
if (defined('MAX_PROCESS_MESSAGE')) {
  $messagelimit = sprintf(' limit %d ',MAX_PROCESS_MESSAGE);
}

$query
= " select id"
. " from ${tables['message']}"
. " where status not in ('draft', 'sent', 'prepared', 'suspended')"
. "   and embargo < current_timestamp"
. " order by entered ".$messagelimit;
if (VERBOSE) {
  output($query);
}
$messages = Sql_query($query);
$num_messages = Sql_Num_Rows($messages);
if (Sql_Has_Error($database_connection)) {  ProcessError(Sql_Error($database_connection)); }

if ($num_messages) {
  output($GLOBALS['I18N']->get('Processing has started,').' '.$num_messages.' '.$GLOBALS['I18N']->get('message(s) to process.'));
  clearPageCache();
  if (!$GLOBALS["commandline"]) {
    if (!$safemode) {
      if (!$num_per_batch) {
        output($GLOBALS['I18N']->get('It is safe to click your stop button now, report will be sent by email to').' '.getConfig("report_address"));
      } else {
        output($GLOBALS['I18N']->get('Please leave this window open. You have batch processing enabled, so it will reload several times to send the messages. Reports will be sent by email to').' '.getConfig("report_address"));
      }
    } else {
      output($GLOBALS['I18N']->get('Your webserver is running in safe_mode. Please keep this window open. It may reload several times to make sure all messages are sent.').' '.$GLOBALS['I18N']->get('Reports will be sent by email to').' '.getConfig("report_address"));
    }
  }
}

$script_stage = 2; # we know the messages to process
#include_once "footer.inc";
if (!isset($num_per_batch)) {
  $num_per_batch = 1000000;
}

$counters = array();

while ($message = Sql_fetch_array($messages)) {
  $counters['campaign']++;
  $failed_sent = 0;
  $throttlecount = 0;

  $messageid = $message["id"];
  
  if (!empty($getspeedstats)) output('start send '.$messageid);
  
//  $rssmessage = $message["rsstemplate"]; // obsolete, moved to rssmanager plugin

  $msgdata = loadMessageData($messageid);
  foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
    $plugin->campaignStarted($msgdata);
  }

  ## check the end date of the campaign
  $stopSending = false;
  if (!empty($msgdata['finishsending'])) {
    $finishSendingBefore = mktime($msgdata['finishsending']['hour'],$msgdata['finishsending']['minute'],0,$msgdata['finishsending']['month'],$msgdata['finishsending']['day'],$msgdata['finishsending']['year']);
    $secondsTogo = $finishSendingBefore - time();
    $stopSending = $secondsTogo < 0;
    output(sprintf($GLOBALS['I18N']->get('sending of this campaign will finish in %s'),secs2time($secondsTogo)));
  }

  $userselection = $msgdata["userselection"]; ## @@ needs more work
  ## load message in cache
  precacheMessage($messageid);

  if (!empty($getspeedstats)) output('message data loaded ');  
  if (VERBOSE) {
 //   output($msgdata);
  }
  if (!empty($msgdata['notify_start']) && !isset($msgdata['start_notified'])) {
    $notifications = explode(',',$msgdata['notify_start']);
    foreach ($notifications as $notification) {
      sendMail($notification,$GLOBALS['I18N']->get('Campaign started'),
        sprintf($GLOBALS['I18N']->get('phplist has started sending the campaign with subject %s'),$msgdata['subject']."\n\n".
        sprintf($GLOBALS['I18N']->get('to view the progress of this campaign, go to http://%s'),getConfig('website').$GLOBALS['adminpages'].'/?page=messages&amp;tab=active')));
    }
    Sql_Query(sprintf('insert ignore into %s (name,id,data) values("start_notified",%d,current_timestamp)',
      $GLOBALS['tables']['messagedata'],$messageid));
  }

  output($GLOBALS['I18N']->get('Processing message').' '. $messageid);
  
//  if(isset($GLOBALS['plugins']['rssmanager']) && $GLOBALS['plugins']['rssmanager']->enabled && $message["rsstemplate"]) {
// $processrss = 1;
//    output($GLOBALS['I18N']->get('Message').' '. $messageid.' '.$GLOBALS['I18N']->get('is an rss feed for').' '. $GLOBALS['I18N']->get($rssmessage));
//  } else {
//    $processrss = 0;
//  }

  flush();
  keepLock($send_process_id);
  $query
  = " update ${tables['message']}"
  . " set status = 'inprocess'"
  . " where id = ?";
  $status = Sql_Query_Params($query, array($messageid));
  $query
  = " update ${tables['message']}"
  . " set sendstart = current_timestamp"
  . " where sendstart is null and id = ?";
  $sendstart = Sql_Query_Params($query, array($messageid));
  output($GLOBALS['I18N']->get('Looking for users'));
  if (Sql_Has_Error($database_connection)) {  ProcessError(Sql_Error($database_connection)); }

  # make selection on attribute, users who at least apply to the attributes
  # lots of ppl seem to use it as a normal mailinglist system, and do not use attributes.
  # Check this and take anyone in that case.
  
  ## keep an eye on how long it takes to find users, and warn if it's a long time
  $findUserStart = $processqueue_timer->elapsed(1);
  
  $rs = Sql_Query('select count(*) from ' . $tables['attribute']);
  $numattr = Sql_Fetch_Row($rs);

  if ($userselection && $numattr[0]) {
    $res = Sql_Query($userselection);
    $num_users = Sql_Num_Rows($res);
    output($num_users.' '.$GLOBALS['I18N']->get('users apply for attributes, now checking lists'));
    $user_list = "";
    while ($row = Sql_Fetch_row($res)) {
      $user_list .= $row[0] . ",";
    }
    $user_list = substr($user_list,0,-1);
    if ($user_list)
      $user_attribute_query = " and listuser.userid in ($user_list)";
    else {
      output($GLOBALS['I18N']->get('No users apply for attributes'));
      $query
      = " update ${tables['message']}"
      . " set status = 'sent', sent = current_timestamp"
      . " where id = ?";
      $status = Sql_Query_Params($query, array($messageid));
      finish("info","Message $messageid: \nNo users apply for attributes, ie nothing to do");
      $script_stage = 6;
      # we should actually continue with the next message
      return;
    }
  }
  if ($script_stage < 3)
    $script_stage = 3; # we know the users by attribute

  # when using commandline we need to exclude users who have already received
  # the email
  # we don't do this otherwise because it slows down the process, possibly
  # causing us to not find anything at all
  $exclusion = "";
  $doneusers = array();
  $skipusers = array();

## 8478, avoid building large array in memory, when sending large amounts of users.

/*
  $req = Sql_Query("select userid from {$tables["usermessage"]} where messageid = $messageid");
  $skipped = Sql_Affected_Rows();
  if ($skipped < 10000) {
    while ($row = Sql_Fetch_Row($req)) {
      $alive = checkLock($send_process_id);
      if ($alive)
        keepLock($send_process_id);
      else
        ProcessError($GLOBALS['I18N']->get('Process Killed by other process'));
      array_push($doneusers,$row[0]);
    }
  } else {
    output($GLOBALS['I18N']->get('Warning, disabling exclusion of done users, too many found'));
    logEvent($GLOBALS['I18N']->get('Warning, disabling exclusion of done users, too many found'));
  }

  # also exclude unconfirmed users, otherwise they'll block the process
  # will give quite different statistics than when used web based
#  $req = Sql_Query("select id from {$tables["user"]} where !confirmed");
#  while ($row = Sql_Fetch_Row($req)) {
#    array_push($doneusers,$row[0]);
#  }
  if (sizeof($doneusers))
    $exclusion = " and listuser.userid not in (".join(",",$doneusers).")";
*/

  if (USE_LIST_EXCLUDE) {
    if (VERBOSE) {
      output($GLOBALS['I18N']->get('looking for users who can be excluded from this mailing'));
    }
    if (count($msgdata['excludelist'])) {
/*
      $query
      = " select userid"
      . " from ". $GLOBALS['tables']['listuser']
      . " where listid in (".join(',',$msgdata['excludelist']).")";
      if (VERBOSE) {
        output('Exclude query '.$query);
      }
      $req = Sql_Query($query);
      while ($row = Sql_Fetch_Row($req)) {
        array_push($skipusers,$row[0]);
      }
*/
      $exclusion = sprintf(' and listuser.listid not in (%s)',join(',',$msgdata['excludelist']));
    }
/*
    if (sizeof($skipusers))
      $exclusion .= " and listuser.userid not in (".join(",",$skipusers).")";
*/
  }

/*
  ## 8478
  $query = sprintf('select distinct user.id from
    %s as listuser,
    %s as user,
    %s as listmessage
    where
    listmessage.messageid = %d and
    listmessage.listid = listuser.listid and
    user.id = listuser.userid %s %s %s',
    $tables['listuser'],$tables["user"],$tables['listmessage'],
    $messageid,
    $userconfirmed,
    $exclusion,
    $user_attribute_query);*/
  $query
  = ' select distinct u.id'
  . ' from %s as listuser'
  . '    cross join %s as u'
  . '    cross join %s as listmessage'
  . '    left join %s as um'
  . '       on (um.messageid = ? and um.userid = listuser.userid)'
  . ' where true'
  . '   and listmessage.messageid = ?'
  . '   and listmessage.listid = listuser.listid'
  . '   and u.id = listuser.userid'
  . '   and um.userid IS NULL'
  . '   and u.confirmed = 1 and u.blacklisted = 0'
  . ' %s %s';
  $query = sprintf($query, $tables['listuser'], 
  $tables['user'], $tables['listmessage'], $tables['usermessage'], 
  $exclusion, $user_attribute_query);

  if (VERBOSE) {
    output('User select query '.$query);
  }

  $userids = Sql_Query_Params($query, array($messageid, $messageid));
  if (Sql_Has_Error($database_connection)) {  ProcessError(Sql_Error($database_connection)); }

  # now we have all our users to send the message to
  $num_users = Sql_Num_Rows($userids);
  if ($skipped >= 10000) {
    $num_users -= $skipped;
  }
  
  $findUserEnd = $processqueue_timer->elapsed(1);

  if ($findUserEnd - $findUserStart > 300 && !$GLOBALS["commandline"]) {
    output($GLOBALS['I18N']->get('Warning, finding the subscribers to send out to takes a long time, consider changing to commandline sending'));
  }
  
  output($GLOBALS['I18N']->get('Found them').': '.$num_users.' '.$GLOBALS['I18N']->get('to process'));
  setMessageData($messageid,'to process',$num_users);

  if ($num_per_batch) {
    # send in batches of $num_per_batch users
    $batch_total = $num_users;
    if ($num_per_batch > 0) {
      $query .= sprintf(' limit 0,%d',$num_per_batch);
      if (VERBOSE) {
        output($num_per_batch .'  query -> '.$query);
      }
      $userids = Sql_Query_Params($query, array($messageid, $messageid));
      if (Sql_Has_Error($database_connection)) {  ProcessError(Sql_Error($database_connection)); }
    } else {
      output($GLOBALS['I18N']->get('No users to process for this batch'));
      $userids = Sql_Query("select * from ${tables['user']} where id = 0");
    }
  }
  $affrows = Sql_Num_Rows($userids);
  output($GLOBALS['I18N']->get('Processing batch of ').': '.$affrows);

  while ($userdata = Sql_Fetch_Row($userids)) {
    $failure_reason = '';
    if ($num_per_batch && $sent >= $num_per_batch) {
      output($GLOBALS['I18N']->get('batch limit reached').": $sent ($num_per_batch)");
      $GLOBALS["wait"] = $batch_period;
      return;
    }
    $userid = $userdata[0];    # id of the user
    if (!empty($getspeedstats)) output('-----------------------------------'."\n".'start process user '.$userid);  
    $some = 1;
    set_time_limit(120);

    $secondsTogo = $finishSendingBefore - time();
    $stopSending = $secondsTogo < 0;

    # check if we have been "killed"
    $alive = checkLock($send_process_id);

    ## check for max-process-queue-time
    $elapsed = $GLOBALS['processqueue_timer']->elapsed(1);
    if (defined('MAX_PROCESSQUEUE_TIME') && MAX_PROCESSQUEUE_TIME > 0 && $elapsed > MAX_PROCESSQUEUE_TIME && $sent > 0) {
      output($GLOBALS['I18N']->get('queue processing time has exceeded max processing time ').MAX_PROCESSQUEUE_TIME);
      break;
    } elseif ($alive && !$stopSending) {
      keepLock($send_process_id);
    } elseif ($stopSending) {
      output($GLOBALS['I18N']->get('Campaign sending timed out, is past date to process until'));
      break;
    } else {
      ProcessError($GLOBALS['I18N']->get('Process Killed by other process'));
    }

    # check if the message we are working on is still there and in process
    $status = Sql_Fetch_Array_query("select id,status from {$tables['message']} where id = $messageid");
    if (!$status['id']) {
      ProcessError($GLOBALS['I18N']->get('Message I was working on has disappeared'));
    } elseif ($status['status'] != 'inprocess') {
      ProcessError($GLOBALS['I18N']->get('Sending of this message has been suspended'));
    }
    flush();

    # check whether the user has already received the message
    if (!empty($getspeedstats)) output('verify message can go out to '.$userid);  
    
    $um = Sql_query("select entered from {$tables['usermessage']} where userid = $userid and messageid = $messageid");
    if (!Sql_Num_Rows($um)) {
      ## mark this message that we're working on it, so that no other process will take it
      ## between two lines ago and here, should hopefully be quick enough
      $userlock = Sql_Replace($tables['usermessage'], array('entered' => 'current_timestamp', 'userid' => $userid, 'messageid' => $messageid, 'status' => "active"), array('userid', 'messageid'), false);

      if ($script_stage < 4)
        $script_stage = 4; # we know a user
      $someusers = 1;
      $users = Sql_query("select id,email,uniqid,htmlemail,rssfrequency,confirmed,blacklisted from {$tables['user']} where id = $userid");

      # pick the first one (rather historical from before email was unique)
      $user = Sql_fetch_array($users); 
      if ($user[5] && is_email($user[1])) {
        $userid = $user[0];    # id of the user
        $useremail = $user[1]; # email of the user
        $userhash = $user[2];  # unique string of the user
        $htmlpref = $user[3];  # preference for HTML emails
//        $rssfrequency = $user[4];
        $confirmed = $user[5];
        $blacklisted = $user[6];

//obsolete, moved to rssmanager plugin 
//        if (ENABLE_RSS && $pxrocessrss) {
//          if ($rssfrequency == $message["rsstemplate"]) {
//            # output("User matches message frequency");
//            $rssitems = rssmanager::rssUserHasContent($userid,$messageid,$rssfrequency);
//            $cansend = sizeof($rssitems) && (sizeof($rssitems) > $rss_content_threshold);
//#            if (!$cansend)
//#              output("No content to send for this user ".sizeof($rssitems));
//          } else {
//            $cansend = 0;
//          }
//        } else {
          $cansend = !$blacklisted;
//        }

/*
## Ask plugins if they are ok with sending this message to this user
*/
      if (!empty($getspeedstats)) output('start check plugins ');  

      reset($GLOBALS['plugins']);
      while ($cansend && $plugin = current($GLOBALS['plugins']) ) {
        $cansend = $plugin->canSend($msgdata, $user);
        if (!$cansend) {
          $failure_reason .= 'Sending blocked by plugin '.$plugin->name;
          $counters['send blocked by '.$plugin->name]++;
        }

        next($GLOBALS['plugins']);
      } 
      if (!empty($getspeedstats)) output('end check plugins ');  

####################################
# Throttling

        $throttled = 0;
        if ($cansend && USE_DOMAIN_THROTTLE) {
          list($mailbox,$domainname) = explode('@',$useremail);
          $now = time();
          $interval = $now - ($now % DOMAIN_BATCH_PERIOD);
          if (!is_array($domainthrottle[$domainname])) {
            $domainthrottle[$domainname] = array();
          } elseif (isset($domainthrottle[$domainname]['interval']) && $domainthrottle[$domainname]['interval'] == $interval) {
            $throttled = $domainthrottle[$domainname]['sent'] >= DOMAIN_BATCH_SIZE;
            if ($throttled) {
              $counters['send blocked by domain throttle']++;
              $domainthrottle[$domainname]['attempted']++;
              if (DOMAIN_AUTO_THROTTLE
                && $domainthrottle[$domainname]['attempted'] > 25 # skip a few before auto throttling
                && $num_messages <= 1 # only do this when there's only one message to process otherwise the other ones don't get a change
                && $num_users < 1000 # and also when there's not too many left, because then it's likely they're all being throttled
              ) {
                $domainthrottle[$domainname]['attempted'] = 0;
                logEvent(sprintf($GLOBALS['I18N']->get('There have been more than 10 attempts to send to %s that have been blocked for domain throttling.'),$domainname));
                logEvent($GLOBALS['I18N']->get('Introducing extra delay to decrease throttle failures'));
                if (VERBOSE) {
                  output($GLOBALS['I18N']->get('Introducing extra delay to decrease throttle failures'));
                }
                if (!isset($running_throttle_delay)) {
                  $running_throttle_delay = (int)(MAILQUEUE_THROTTLE + (DOMAIN_BATCH_PERIOD / (DOMAIN_BATCH_SIZE * 4)));
                } else {
                  $running_throttle_delay += (int)(DOMAIN_BATCH_PERIOD / (DOMAIN_BATCH_SIZE * 4));
                }
                #output("Running throttle delay: ".$running_throttle_delay);
              } elseif (VERBOSE) {
                output(sprintf($GLOBALS['I18N']->get('%s is currently over throttle limit of %d per %d seconds').' ('.$domainthrottle[$domainname]['sent'].')',$domainname,DOMAIN_BATCH_SIZE,DOMAIN_BATCH_PERIOD));
              }
            }
          }
        }

        if ($cansend) {
          $success = 0;
          if (!TEST) {
            reset($GLOBALS['plugins']);
            while (!$throttled && $plugin = current($GLOBALS['plugins']) ) {
              $throttled = $plugin->throttleSend($msgdata, $user);
              if ($throttled) {
                $counters['send throttled by plugin '.$plugin->name]++;
                $failure_reason .= 'Sending throttled by plugin '.$plugin->name;
              }
              next($GLOBALS['plugins']);
            } 
            if (!$throttled) {
              if (VERBOSE)
                output($GLOBALS['I18N']->get('Sending').' '. $messageid.' '.$GLOBALS['I18N']->get('to').' '. $useremail);
              $emailSentTimer = new timer();
              $success = sendEmail($messageid,$useremail,$userhash,$htmlpref); // $rssitems Obsolete by rssmanager plugin
              if (!$success) {
                $counters['sendemail returned false']++;
              }
              if (VERBOSE) {
                output($GLOBALS['I18N']->get('It took').' '.$emailSentTimer->elapsed(1).' '.$GLOBALS['I18N']->get('seconds to send'));
              }
            } else {
              $throttlecount++;
            }
          } else {
            $success = sendEmailTest($messageid,$useremail);
          }

          #############################
          # tried to send email , process succes / failure
          if ( $success) {
            if (USE_DOMAIN_THROTTLE) {
              list($mailbox,$domainname) = explode('@',$useremail);
              if ($domainthrottle[$domainname]['interval'] != $interval) {
                $domainthrottle[$domainname]['interval'] = $interval;
                $domainthrottle[$domainname]['sent']=0;
              } else {
                $domainthrottle[$domainname]['sent']++;
              }
            }
            $sent++;
            $um = Sql_Replace($tables['usermessage'], array('entered' => 'current_timestamp', 'userid' => $userid, 'messageid' => $messageid, 'status' => "sent"), array('userid', 'messageid'), false);

//obsolete, moved to rssmanager plugin 
//            if (ENABLE_RSS && $pxrocessrss) {
//              foreach ($rssitems as $rssitemid) {
//                $status = Sql_query("update {$tables['rssitem']} set processed = processed +1 where id = $rssitemid");
//                $um = Sql_query("replace into {$tables['rssitem_user']} (userid,itemid) values($userid,$rssitemid)");
//              }
//              Sql_Query("replace into {$tables["user_rss"]} (userid,last) values($userid,date_sub(current_timestamp,interval 15 minute))");
//
//              }
           } else {
             $failed_sent++;
             ## need to check this, the entry shouldn't be there in the first place, so no need to delete it
             ## might be a cause for duplicated emails
             #Sql_Query_Params(sprintf('delete from %s where userid = ? and messageid = ?',$tables['usermessage']), array($userid,$messageid));
             if (VERBOSE) {
               output($GLOBALS['I18N']->get('Failed sending to').' '. $useremail);
               logEvent("Failed sending message $messageid to $useremail");
             }
             # make sure it's not because it's an invalid email
             # unconfirm this user, so they're not included next time
             if (!$throttled && !validateEmail($useremail)) {
               $unconfirmed++;
               $counters['email address invalidated']++;               
               logEvent("invalid email $useremail user marked unconfirmed");
               Sql_Query(sprintf('update %s set confirmed = 0 where email = "%s"',
                 $GLOBALS['tables']['user'],$useremail));
             }
           }
             
           if ($script_stage < 5) {
             $script_stage = 5; # we have actually sent one user
           }
           if (isset($running_throttle_delay)) {
             sleep($running_throttle_delay);
             if ($sent % 5 == 0) {
               # retry running faster after some more messages, to see if that helps
               unset($running_throttle_delay);
             }
           } elseif (MAILQUEUE_THROTTLE) {
             usleep(MAILQUEUE_THROTTLE * 1000000);
           } elseif (MAILQUEUE_BATCH_SIZE && MAILQUEUE_AUTOTHROTTLE) {
             $totaltime = $GLOBALS['processqueue_timer']->elapsed(1);
             $msgperhour = (3600/$totaltime) * $sent;
             $msgpersec = $msgperhour / 3600;
             $secpermsg = $totaltime / $sent;
             $target = (MAILQUEUE_BATCH_PERIOD / MAILQUEUE_BATCH_SIZE) * $sent;
             $delay = $target - $totaltime;
#             output("Sent: $sent mph $msgperhour mps $msgpersec secpm $secpermsg target $target actual $actual d $delay");

             if ($delay > 0) {
               if (VERBOSE) {
/* output($GLOBALS['I18N']->get('waiting for').' '.$delay.' '.$GLOBALS['I18N']->get('seconds').' '.
                   $GLOBALS['I18N']->get('to make sure we don\'t exceed our limit of ').MAILQUEUE_BATCH_SIZE.' '.
                   $GLOBALS['I18N']->get('messages in ').' '.MAILQUEUE_BATCH_PERIOD.$GLOBALS['I18N']->get('seconds')); */
                output(sprintf($GLOBALS['I18N']->get('waiting for %.1f seconds to meet target of %s seconds per message'),
                        $delay, (MAILQUEUE_BATCH_PERIOD / MAILQUEUE_BATCH_SIZE)
                ));
               }
               usleep($delay * 1000000);
             }
           }
        } else {
          $cannotsend++;
          # mark it as sent anyway, because otherwise the process will never finish
          if (VERBOSE) {
            output($GLOBALS['I18N']->get('not sending to ').$useremail);
          }
          $um = Sql_query("replace into {$tables['usermessage']} (entered,userid,messageid,status) values(current_timestamp,$userid,$messageid,\"not sent\")");
        }

        # update possible other users matching this email as well,
        # to avoid duplicate sending when people have subscribed multiple times
        # bit of legacy code after making email unique in the database
#        $emails = Sql_query("select * from {$tables['user']} where email =\"$useremail\"");
#        while ($email = Sql_fetch_row($emails))
#          Sql_query("replace into {$tables['usermessage']} (userid,messageid) values($email[0],$messageid)");
      }  else {
        # some "invalid emails" are entirely empty, ah, that is because they are unconfirmed

        ## this is quite old as well, with the preselection that avoids unconfirmed users
        # it is unlikely this is every processed.

        if (!$user[5]) {
          if (VERBOSE)
            output($GLOBALS['I18N']->get('Unconfirmed user').': '."$userid $user[1], $user[0]");
          $unconfirmed++;
          # when running from commandline we mark it as sent, otherwise we might get
          # stuck when using batch processing
         # if ($GLOBALS["commandline"]) {
            $um = Sql_query("replace into {$tables['usermessage']} (entered,userid,messageid,status) values(current_timestamp,$userid,$messageid,\"unconfirmed user\")");
         # }
        } elseif ($user[1] || $user[0]) {
          if (VERBOSE)
            output("Invalid email: $user[1], $user[0]");
          logEvent("Invalid email, userid $user[0], email $user[1]");
          # mark it as sent anyway
          if ($userid)
            $um = Sql_query("replace into {$tables['usermessage']} (entered,userid,messageid,status) values(current_timestamp,$userid,$messageid,\"invalid email\")");
          $invalid++;
        }
      }
    } else {

      ## and this is quite historical, and also unlikely to be every called
      # because we now exclude users who have received the message from the
      # query to find users to send to
      
      ## when trying to send the message, it was already marked for this user
      ## June 2010, with the multiple send process extension, that's quite possible to happen again

      $um = Sql_Fetch_Row($um);
      $notsent++;
      if (VERBOSE) {
        output($GLOBALS['I18N']->get('Not sending to').' '. $userdata[0].', '.$GLOBALS['I18N']->get('already sent').' '.$um[0]);
      }
    }
    $status = Sql_query("update {$tables['message']} set processed = processed + 1 where id = $messageid");
    $processed = $notsent + $sent + $invalid + $unconfirmed + $cannotsend + $failed_sent;
    #if ($processed % 10 == 0) {
    if (0) {
      output('AR'.$affrows.' N '.$num_users.' P'.$processed.' S'.$sent.' N'.$notsent.' I'.$invalid.' U'.$unconfirmed.' C'.$cannotsend.' F'.$failed_sent);
      $rn = $reload * $num_per_batch;
      output('P '.$processed .' N'. $num_users .' NB'.$num_per_batch .' BT'.$batch_total .' R'.$reload.' RN'.$rn);
    }
    /* 
     * don't calculate this here, but in the "msgstatus" instead, so that
     * the total speed can be calculated, eg when there are multiple send processes
     */
     
    /*  
    $totaltime = $GLOBALS['processqueue_timer']->elapsed(1);
    if ($sent > 0) {
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
    setMessageData($messageid,'ETA',$eta);
    setMessageData($messageid,'msg/hr',$msgperhour);
    */
    setMessageData($messageid,'to process',$num_users - $sent);
    setMessageData($messageid,'last msg sent',time());
  #  setMessageData($messageid,'totaltime',$GLOBALS['processqueue_timer']->elapsed(1));
    if (!empty($getspeedstats)) output('end process user '."\n".'-----------------------------------'."\n".$userid);  
  }
  $processed = $notsent + $sent + $invalid + $unconfirmed + $cannotsend + $failed_sent;
  output($GLOBALS['I18N']->get('Processed').' '. $processed.' '.$GLOBALS['I18N']->get('out of').' '. $num_users .' '.$GLOBALS['I18N']->get('users'));

  if ($num_users - $sent <= 0 || $stopSending) {
    # this message is done
    if (!$someusers)
      output($GLOBALS['I18N']->get('Hmmm, No users found to send to'));
    if (!$failed_sent) {
      repeatMessage($messageid);
      $status = Sql_query(sprintf('update %s set status = "sent",sent = current_timestamp where id = %d',$GLOBALS['tables']['message'],$messageid));
            
      if (!empty($msgdata['notify_end']) && !isset($msgdata['end_notified'])) {
        $notifications = explode(',',$msgdata['notify_end']);
        foreach ($notifications as $notification) {
          sendMail($notification,$GLOBALS['I18N']->get('Message Campaign finished'),
            sprintf($GLOBALS['I18N']->get('phplist has finished sending the campaign with subject %s'),$msgdata['subject'])."\n\n".
            sprintf($GLOBALS['I18N']->get('to view the results of this campaign, go to http://%s'),getConfig('website').$GLOBALS['adminpages'].'/?page=messages&amp;tab=sent')
            );
        }
        Sql_Query(sprintf('insert ignore into %s (name,id,data) values("end_notified",%d,current_timestamp)',
          $GLOBALS['tables']['messagedata'],$messageid));
      }
      $query
      = " select sent, sendstart"
      . " from ${tables['message']}"
      . " where id = ?";
      $rs = Sql_Query_Params($query, array($messageid));
      $timetaken = Sql_Fetch_Row($rs);
      output($GLOBALS['I18N']->get('It took').' '.timeDiff($timetaken[0],$timetaken[1]).' '.$GLOBALS['I18N']->get('to send this message'));
      sendMessageStats($messageid);
    }
    ## flush cached message track stats to the DB
    if (isset($GLOBALS['cached']['linktracksent'])) {
      flushClicktrackCache();
      # we're done with $messageid, so get rid of the cache
      unset($GLOBALS['cached']['linktracksent'][$messageid]);
    }

  } else {
    if ($script_stage < 5)
      $script_stage = 5;
  }
}

if (!$num_messages)
  $script_stage = 6; # we are done
# shutdown will take care of reporting  
?>
