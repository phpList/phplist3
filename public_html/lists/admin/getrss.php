<?php
require_once 'accesscheck.php';
if (!$GLOBALS['commandline']) {
  ob_end_flush();
  if (!MANUALLY_PROCESS_RSS) {
    print $GLOBALS['I18N']->get('This page can only be called from the commandline');
    return;
  }
} else {
  ob_end_clean();
  print ClineSignature();
  print $GLOBALS['I18N']->get('Getting and Parsing the RSS sources') . "\n";
  ob_start();
}

# @@@@ Not sure if this is 118nable.
function ProcessError ($message) {
  print "$message";
  logEvent("Error: $message");
  finish("error",$message);
  exit;
}

function output($line) {
  if ($GLOBALS['commandline']) {
    ob_end_clean();
    print strip_tags($line)."\n";
    ob_start();
  } else {
    print "$line<br/>\n";
  }
  flush();
}

register_shutdown_function('finish');

function finish ($flag = "info",$message = 'finished') {
  global $nothingtodo,$failreport,$mailreport,$process_id;

  if ($flag == 'error') {
    $subject = $GLOBALS['I18N']->get('Rss Errors');
  } else {
    $subject = $GLOBALS['I18N']->get('Rss Results');
  }

  releaseLock($process_id);

  if (!TEST && !$nothingtodo) {
    if ($mailreport)
      sendReport($subject,$mailreport);
    if ($failreport)
      sendReport($GLOBALS['I18N']->get('Rss Failure report'),$failreport);
  }
}

# we don not want to timeout or abort
$abort = ignore_user_abort(1);
set_time_limit(600);

include 'onyxrss/onyx-rss.php';
error_reporting(0);
$nothingtodo = 1;
$mailreport = '';
$process_id = getPageLock();

$req = Sql_Query("select rssfeed,id from {$tables['list']} where rssfeed != \"\" order by listorder");
while ($feed = Sql_Fetch_Row($req)) {
  $nothingtodo = 0;
  output( '<hr/>' . $GLOBALS['I18N']->get('Parsing') . ' ' . $feed[0] . '..');
  flush();
  $report = $GLOBALS['I18N']->get('Parsing') . ' ' . $feed[0];
  $mailreport .= "\n$feed[0] ";
  $itemcount = 0;
  $newitemcount = 0;
  $rss =& new ONYX_RSS();
  $rss->setDebugMode(false);
  $rss->setCachePath($tmpdir);
  keepLock($process_id);

  $parseresult = $rss->parse($feed[0],"rss-cache".$GLOBALS["database_name"].$feed[1]);
  if ($parseresult) {
    $report .= ' ' . $GLOBALS['I18N']->get('ok') . "\n";
   $mailreport .= " 'ok ";
    output( '..' . $GLOBALS['I18N']->get('ok') . '<br />');
  } else {
   $report .= ' ' . $GLOBALS['I18N']->get('failed') . "\n";
   output( '..' . $GLOBALS['I18N']->get('failed') . '<br />');
    $mailreport .= ' ' . $GLOBALS['I18N']->get('failed') . "\n";
    $mailreport .= $rss->lasterror;
    $failreport .= "\n" . $feed[0] . ' ' . $GLOBALS['I18N']->get('failed') . "\n" . $rss->lasterror;
  }
  flush();
  if ($parseresult) {
    while ($item = $rss->getNextItem()) {
      set_time_limit(60);
      $alive = checkLock($process_id);
      if ($alive)
        keepLock($process_id);
      else
        ProcessError($GLOBALS['I18N']->get('Process Killed by other process'));
      $itemcount++;
      Sql_Query(sprintf('select * from %s where title = "%s" and link = "%s"',
        $tables["rssitem"],addslashes(substr($item["title"],0,100)),addslashes(substr($item["link"],0,100))));
      if (!Sql_Affected_Rows()) {
        $newitemcount++;
        Sql_Query(sprintf('insert into %s (title,link,source,list,added)
          values("%s","%s","%s",%d,now())',
          $tables["rssitem"],addslashes($item["title"]),addslashes($item['link']),addslashes($feed[0]),$feed[1]));
        $itemid = Sql_Insert_Id();
        foreach ($item as $key => $val) {
          if ($item != 'title' && $item != 'link') {
            Sql_Query(sprintf('insert into %s (itemid,tag,data)
              values("%s","%s","%s")',
              $tables["rssitem_data"],$itemid,$key,addslashes($val)));
          }
        }
      }
    }
    output(sprintf('<br/>%d %s, %d %s',$itemcount,$GLOBALS['I18N']->get('items'),$newitemcount,$GLOBALS['I18N']->get('new items')));
    $report .= sprintf('%d items, %d new items'."\n",$itemcount,$newitemcount);
    $mailreport .= sprintf('-> %d items, %d new items'."\n",$itemcount,$newitemcount);
  }
  flush();
  Sql_Query(sprintf('insert into %s (listid,type,entered,info) values(%d,"retrieval",now(),"%s")',
    $tables["listrss"],$feed[1],$report));
  logEvent($report);
}
if ($nothingtodo) {
  print $GLOBALS['I18N']->get('Nothing to do');
}


?>
