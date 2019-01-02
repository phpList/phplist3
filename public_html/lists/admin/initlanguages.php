<?php

if (!defined('PHPLISTINIT')) {
    die();
}

if (empty($GLOBALS['commandline'])) {
    echo $GLOBALS['I18N']->get('This page can only be called from the commandline');

    return;
}
$locale_root = dirname(__FILE__).'/locale/';

$force = isset($cline['f']);

if (is_dir($locale_root)) {
    $dir = opendir($locale_root);
    while ($lan = readdir($dir)) {
        if (is_file($locale_root.'/'.$lan.'/phplist.po')) {
            cl_output($lan);
            $lastUpdate = getConfig('lastlanguageupdate-'.$lan);
            $thisUpdate = filemtime($locale_root.'/'.$lan.'/phplist.po');
            if ($force || $thisUpdate > $lastUpdate) {
                cl_output(s('Initialising language').' '.$lan);
                $GLOBALS['I18N']->initFSTranslations($lan);
            } else {
                cl_output(s('Up to date'));
            }
        }
    }
}
