<?php
require_once dirname(__FILE__).'/accesscheck.php';

if (!$GLOBALS["commandline"]) {
  if (!empty($_GET['secret'])) {
    if (isset($_GET['ack']) && $_GET['ack'] == 1) {
      @ob_end_clean();
      print 'ACK';
      exit;
    }
    include dirname(__FILE__).'/actions/processqueue.php';
    return;
  }
  if (!MANUALLY_PROCESS_QUEUE) {
    print "This page can only be called from the commandline";
    return;
  }
    
} else {
  @ob_end_clean();
  print ClineSignature();
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

if (isset($_GET['pqchoice'])) {
  if ($_GET['pqchoice'] == 'local') {
    SaveConfig('pqchoice','local',0);
  } elseif ($_GET['pqchoice'] == 'reset') {
    SaveConfig('pqchoice','',0);
  }
}

if (SHOW_PQCHOICE) {
  $pqChoice = getConfig('pqchoice');
} else {
  $pqChoice = 'local';
}

if (empty($pqChoice)) {
  print '<h3>'.s('To send your queue, you can now use the phpList.com service').'</h3>';
  
  print '<strong>The options are:</strong>';
  print '<h4>1. Use the service from phpList.com</h4>
      <p>The service has a free trial and low cost.</p>
      <p><strong>Advantage</strong>: No need to keep your computer switched on and your browser open. <strong>Sending will happen automatically</strong>.</p>
      <p><strong>Disadvantage</strong>: We can\'t think of any.</p>
      <a href="./?page=hostedprocessqueuesetup" class="button">'.s('Set up using the service').'</a>
      <p><i>You can change your mind at any time.</i></p>
      <p>OR</p>
      <p></p>
      <h4> 2. Run the queue manually in your browser</h4>
      <p><strong>Advantage</strong>: No external dependency, no additional cost.</p>
      <p><strong>Disadvantage</strong>: The need to keep your computer running, and your browser open, until everything has been sent.</p>
      <a href="./?page=processqueue&pqchoice=local" class="button">'.s('Use local processing').'</a>
  ';
}

if ($pqChoice == 'local') {
  print '
  <div class="panel">
    <h2>'.s('Processing queued campaigns').'</h2>
    <div class="content">
      <div class="wrapper">
        <div id="spinner"></div>
        <div id="processqueuecontrols">';
        print '<a href="#" id="stopqueue" class="button">'.snbr('stop processing').'</a>';
        print '<a href="./?page=processqueue" id="resumequeue" class="button hidden">'.snbr('resume processing').'</a>';
        print '<div id="progressmeterold"><div id="progresscount"></div><div id="progress">&nbsp;</div></div>';
  print '</div>
      </div>
      <div id="processqueueoutput">
        <div id="processqueuesummary"></div>
        <div id="processqueueprogress"></div>
        <iframe id="processqueueiframe" src="./?page=pageaction&action=processqueue&ajaxed=true'.addCsrfGetToken().'" scrolling="no"></iframe>
      </div>
    </div>
  </div>';
  
  if (SHOW_PQCHOICE) {
    print s('Using local processing'). ' <p><a href="./?page=processqueue&pqchoice=reset" class="button">'.s('Reset').'</a></p>';
  }
  
} elseif ($pqChoice == 'phplistdotcom') {
  print '<h3>'.s('To send your queue, you use the service from phpList.com').'</h3>';
  print '<p><a href="./?page=messages&tab=active" class="button">'.s('View progress').'</a></p>';
  print '<p><a href="./?page=hostedprocessqueuesetup" class="button">'.s('Change settings').'</a></p>';
  print '<p><a href="./?page=processqueue&pqchoice=reset" class="button">'.s('Reset choice').'</a></p>';
}

