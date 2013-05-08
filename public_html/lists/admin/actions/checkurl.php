<?php

## check url
$request_parameters = array(
  'timeout' => 10,
  'allowRedirects' => 1,
  'method' => 'HEAD',
);

if (empty($_GET['url'])) return;

$url = expandURL($_GET['url']);

$isOk = true;

if ($GLOBALS['can_fetchUrl']) {
  $code = testUrl($url);
  if ($code != 200) {
    if (!empty($url_append)) {
      $status = $GLOBALS['I18N']->get('Error fetching URL').' '.$GLOBALS['I18N']->get('Check your "remoteurl_append" setting.');
    } else {
      $status = $GLOBALS['I18N']->get('Error fetching URL');
    }
    $isOk = false;
  }
} else {
  $status = $GLOBALS['I18N']->get('Error fetching URL');
  $isOk = false;
}

if ($isOk) {
  $status = '<span class="pass">'.$GLOBALS['I18N']->get('URL is valid').'</span>';
} else {
  $status = '<span class="fail">'.$status.'</span>';
}
