<?php
verifyCsrfGetToken();

if (!empty($_SESSION['LoadDelay'])) {
    sleep($_SESSION['LoadDelay']);
}
$status = '';

if (isset($_GET['domain'])) {
    //Replace everything except alphanumerical chars and period.
    $domain = preg_replace('@[^0-9a-z\.]+@i', ' ',  $_GET['domain']);
}
if (isset($_GET['bounces'])) {
    //make sure that bounces is integer.
    $bounces = (int) $_GET['bounces'];
}

$ls = new WebblerListing(s('Domain statistics'));
$ls->setElementHeading('Domain');

$sent = Sql_Query(sprintf("SELECT
  COUNT(*)
FROM
  (
  SELECT
    COUNT(email)
  FROM
    %s AS u
  INNER JOIN
    %s AS m ON u.id = m.userid
  WHERE
STATUS
  = 'sent' AND email LIKE '%%$domain'
GROUP BY
  email
) t", $GLOBALS['tables']['user'],
    $GLOBALS['tables']['usermessage'], 'sent'));

while ($row = Sql_Fetch_Row($sent)) {
    $sent = $row[0];
}

$viewed = Sql_Query(sprintf("SELECT
  COUNT(*)
FROM
  (
  SELECT
    COUNT(email)
  FROM
    %s AS u
  INNER JOIN
    %s AS m ON u.id = m.userid
  WHERE
STATUS
  IS NOT NULL AND email LIKE '%%$domain'
GROUP BY
  email
) t", $GLOBALS['tables']['user'],
    $GLOBALS['tables']['usermessage']));

while ($row = Sql_Fetch_Row($viewed)) {
    $viewed = $row[0];
}

$bounceRate=  sprintf('%0.2f', $bounces / $sent * 100)."%";
$viewRate=  sprintf('%0.2f', $viewed / $sent * 100)."%";

$ls->addElement($domain);
$ls->addColumn(
    $domain,
    s('Total bounced emails'),
    number_format($bounces));
$ls->addColumn(
    $domain,
    s('Total sent emails'),
    number_format($sent));
$ls->addColumn(
    $domain,
    s('Bounce rate'),
    $bounceRate);
$ls->addColumn(
    $domain,
    s('View rate').Help("viewrate"),
    $viewRate);
$status .= $ls->display();
