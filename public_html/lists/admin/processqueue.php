<?php
require_once dirname(__FILE__).'/accesscheck.php';

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
  include dirname(__FILE__).'/actions/processqueue.php';
  return;
}

# once and for all get rid of those questions why they do not receive any emails :-)
if (TEST) {
  print Info('<strong>'.$GLOBALS['I18N']->get('Running in testmode, no emails will be sent. Check your config file.'),1).'</strong>';
}
print '<noscript>
<div class="error">'.s('This page requires Javascript to be enabled.').'</div>
</noscript>';

print '
<div class="panel">
  <h2>'.s('Processing queued campaigns').'</h2>
  <div class="content">
    <div class="wrapper">
      <div id="spinner"></div>
      <div id="processqueuecontrols">';
      print '<a href="#" id="stopqueue" class="button">'.s('stop processing').'</a>';
      print '<div id="progressmeter"><div id="progresscount"></div><div id="progress">&nbsp;</div></div>';
print '</div>
    </div>
    <div id="processqueueoutput">
      <div id="processqueuesummary"></div>
      <div id="processqueueprogress"></div>
      <iframe id="processqueueiframe" src="./?page=pageaction&action=processqueue&ajaxed=true" scrolling="no"></iframe>
    </div>
  </div>
</div>';
