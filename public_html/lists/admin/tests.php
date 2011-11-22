<?php

error_reporting(E_ALL);
## test suite for certain elements of phplist
print '<h3>'.$GLOBALS['I18N']->get('phplist test suite').'</h3>';

if (empty($GLOBALS['developer_email'])) {
  print "Only available in developer mode";
  return;
}

$tests = array();
# generic class that's extended by all tests
include_once dirname(__FILE__).'/defaulttest.php';

$testdir = dirname(__FILE__).'/tests';
if (is_dir($testdir)) {
  if ($dh = opendir($testdir)) {
    while (($file = readdir($dh)) !== false) {
      if (preg_match("/\.php$/",$file) && is_file($testdir."/".$file)) {
        require_once ($testdir.'/'.$file);
        $class = basename($file,'.php');
        eval("\$test = new \$class();");
        $tests[$class] = $test;
      }
    }
    closedir($dh);
  }
}

if ($_GET['runtest'] && in_array($_GET['runtest'],array_keys($tests))) {
  print "<h3>Running test:  ".$tests[$_GET['runtest']]->name.'</h3>';
  $testresult = $tests[$_GET['runtest']]->runtest();
  if ($testresult) {
    print $GLOBALS['I18N']->get('Test passed');
  } else {
    print $GLOBALS['I18N']->get('Test failed');
  }
  print '<br/><br/>';
}

$ls = new WebblerListing($GLOBALS['I18N']->get('Tests available'));

foreach ($tests as $testclassname => $testclass) {
  $el = $GLOBALS['I18N']->get($testclass->name);
  $ls->addElement($el,PageUrl2('tests&runtest='.$testclassname));
  $ls->addColumn($el,$GLOBALS['I18N']->get('Purpose'),$GLOBALS['I18N']->get($testclass->purpose));
}
print $ls->display();
?>



