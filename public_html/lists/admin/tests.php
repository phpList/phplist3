<?php

require_once dirname(__FILE__).'/accesscheck.php';

//# some kind of attempt to make a test suite for certain elements of phplist

echo '<h3>'.$GLOBALS['I18N']->get('phplist test suite').'</h3>';

if (empty($GLOBALS['developer_email'])) {
    echo 'Only available in developer mode';

    return;
}

$tests = array();
// generic class that's extended by all tests
include_once dirname(__FILE__).'/defaulttest.php';

$testdir = dirname(__FILE__).'/tests';
if (is_dir($testdir)) {
    if ($dh = opendir($testdir)) {
        while (($file = readdir($dh)) !== false) {
            if (preg_match("/\.php$/", $file) && is_file($testdir.'/'.$file)) {
                require_once $testdir.'/'.$file;
                $class = basename($file, '.php');
                eval('$test = new $class();');
                if (method_exists($test, 'runtest')) {
                    $tests[$class] = $test;
                }
            }
        }
        closedir($dh);
    }
}

if (!empty($_GET['runtest']) && in_array($_GET['runtest'], array_keys($tests))) {
    echo '<h3>Running test:  '.$tests[$_GET['runtest']]->name.'</h3>';
    $testresult = $tests[$_GET['runtest']]->runtest();
    if ($testresult) {
        echo $GLOBALS['I18N']->get('Test passed');
    } else {
        echo $GLOBALS['I18N']->get('Test failed');
    }
    echo '<br/><br/>';
}

$ls = new WebblerListing($GLOBALS['I18N']->get('Tests available'));

foreach ($tests as $testclassname => $testclass) {
    $el = $GLOBALS['I18N']->get($testclass->name);
    $ls->addElement($el, PageUrl2('tests&runtest='.$testclassname));
    $ls->addColumn($el, $GLOBALS['I18N']->get('Purpose'), $GLOBALS['I18N']->get($testclass->purpose));
}
echo $ls->display();
