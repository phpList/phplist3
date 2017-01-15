<?php

//# fetch a chart either from cache or remotely
//# used for google charts, so verify that's the one being used

$url = $_GET['url'];
$url = str_replace('&amp;', '&', $url);

if (strpos($url, 'chart.apis.google.com/chart') === false) {
    echo 'Error';
    exit;
}

//# cleanup once a month
Sql_Query(sprintf('delete from %s where month(added) < month(now())', $GLOBALS['tables']['gchartcache']));

$cache = Sql_Fetch_Row_Query(sprintf('select content from %s where url = "%s"', $GLOBALS['tables']['gchartcache'],
    $url), 1);

ob_end_clean();
ob_start();

if (empty($cache[0])) {
    $content = file_get_contents($url);
    Sql_Query(sprintf('insert into %s (url,content,added) values("%s","%s",now())', $GLOBALS['tables']['gchartcache'],
        $url, base64_encode($content)), 1);
} else {
    $content = base64_decode($cache[0]);
}
header('Content-type: image/png');
echo $content;

exit;
