<?php

//# check url
$request_parameters = array(
    'timeout'        => 10,
    'allowRedirects' => 1,
    'method'         => 'HEAD',
);

if (empty($_GET['url'])) {
    return;
}

$url = expandURL($_GET['url']);

$isOk = true;
$code = -1;

$code = testUrl($url);
if ($code != 200) {
    if (!empty($url_append)) {
        $status = $GLOBALS['I18N']->get('Error fetching URL').' '.$GLOBALS['I18N']->get('Check your "remoteurl_append" setting.');
    } else {
        $status = $GLOBALS['I18N']->get('Error fetching URL');
    }
    $isOk = false;
}

if ($isOk) {
    $status = '<span class="pass">'.s('URL is valid').'</span>';
} else {
    $status = '<span class="fail">'.$status.'. '.s('Please verify that the URL entered is correct.').'</span>';
}
