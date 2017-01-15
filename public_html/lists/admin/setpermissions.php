<?php

require_once dirname(__FILE__).'/accesscheck.php';

$GLOBALS['disallowpages'] = array(
    'accesscheck',
    'addprefix',
    'classlistmailer',
    'connect',
    'date',
    'defaultplugin',
    'defaulttest',
    'emailtest',
    'init',
    'languages',
    'lib',
    'mimePart',
    'pluginlib',
    'preparesend',
    'readtestmail',
    'rsslib',
    'segmentation',
    'send_core',
    'sendemaillib2',
    'sendemaillib',
    'sessionlib',
    'sidebar',
    'sorbs',
    'stresstest',
    'structure',
    'subscribelib2',
);

if (!empty($_SESSION['logindetails']['superuser'])) {
    return;
}

if (!empty($_SESSION['privileges'])) {
    $removeSections = array('system', 'plugins', 'develop');
    foreach ($_SESSION['privileges'] as $priv_category => $enabled) {
        switch ($priv_category) {
            //# map the privileges to the above pagecategories
            case 'subscribers':
                if (!$enabled) {
                    $removeSections[] = 'subscribers';
                }
                break;
            case 'campaigns':
                if (!$enabled) {
                    $removeSections[] = 'campaigns';
                }
                break;
            case 'statistics':
                if (!$enabled) {
                    $removeSections[] = 'statistics';
                }
                break;
            case 'settings':
                if (!$enabled) {
                    $removeSections[] = 'config';
                }
                break;
        }
    }
    foreach ($removeSections as $removeSection) {
        //    print '<h2>Removing '.$removeSection.' '.$priv_category.'</h2>';
        if (empty($GLOBALS['pagecategories'][$removeSection]['pages'])) {
            continue;
        }
        foreach ($GLOBALS['pagecategories'][$removeSection]['pages'] as $sectionPage) {
            //    print '<h2>Disallow '.$sectionPage.'</h2>';
            $GLOBALS['disallowpages'][] = $sectionPage;
        }
        unset($GLOBALS['pagecategories'][$removeSection]);
    }
}
//var_dump($GLOBALS['disallowpages']);
#var_dump($GLOBALS['pagecategories']);
