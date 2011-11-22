<?php

## check url
$request_parameters = array(
  'timeout' => 10,
  'allowRedirects' => 1,
  'method' => 'HEAD',
);

if (empty($_GET['url'])) return;

$url = expandURL($_GET['url']);

$headreq = new HTTP_Request($url,$request_parameters);
$headreq->addHeader('User-Agent', 'phplist v'.VERSION.' (http://www.phplist.com)');
if (!PEAR::isError($headreq->sendRequest(false))) {
  $code = $headreq->getResponseCode();
  if ($code != 200) {
    if (!empty($url_append)) {
      $status = $GLOBALS['I18N']->get('Error fetching URL').' '.$GLOBALS['I18N']->get('Check your "remoteurl_append" setting.');
    } else {
      $status = $GLOBALS['I18N']->get('Error fetching URL');
    }
    return;
  }
} else {
  $status = $GLOBALS['I18N']->get('Error fetching URL');
  return;
}

$status = '<span class="pass">'.$GLOBALS['I18N']->get('URL is valid').'</span>';
