<?php

//# list member count
## load asynchronous, to speed up page loads
if (!defined('PHPLISTINIT')) {
    die();
}
verifyCsrfGetToken();

$status = ' ';
$now = time();

if (empty($_SESSION['adminloggedin'])) return;

$listid = sprintf('%d',$_GET['listid']);
if (!isset($_SESSION['listmembercount'])) {
  $_SESSION['listmembercount'] = array();
}
function listMemberCounts($listId)
{
    global $tables,$now;

    if ($listId) {
        $join =
        "JOIN {$tables['listuser']} lu ON u.id = lu.userid
        WHERE lu.listid = $listId";
    } else {
        $join = '';
    }
    $req = Sql_Query(
        "SELECT
        SUM(1) AS total,
        SUM(IF(u.confirmed = 1 && u.blacklisted = 0, 1, 0)) AS confirmed,
        SUM(IF(u.confirmed = 0 && u.blacklisted = 0, 1, 0)) AS notconfirmed,
        SUM(IF(u.blacklisted = 1, 1, 0)) AS blacklisted
        FROM {$tables['user']} u
        $join"
    );
    $counts = Sql_Fetch_Assoc($req);
    if (empty($counts)) {
      $counts = [
        'confirmed' => 0,
        'notconfirmed' => 0,
        'blacklisted' => 0,
      ];
    } 
    $membersDisplay = sprintf(
        '<span class="memberCount text-success" title="%s">%s</span>'.' ('
        .'<span class="unconfirmedCount text-warning" title="%s">%s</span>, '.' '
        .'<span class="blacklistedCount text-danger" title="%s">%s</span>'.')',
        s('Confirmed and not blacklisted members'),
        number_format($counts['confirmed']),
        s('Unconfirmed and not blacklisted members'),
        number_format($counts['notconfirmed']),
        s('Blacklisted members'),
        number_format($counts['blacklisted'])
    );

    return $membersDisplay;
}

$cacheTimeout = rand(900,15000); ## randomly timeout the cache

if (isset($_SESSION['listmembercount'][$listid]['content']) && (($now - $_SESSION['listmembercount'][$listid]['lastupdate']) < $cacheTimeout)) {
  $status = '<!-- cached -->'.$_SESSION['listmembercount'][$listid]['content'];
} else {
  $status = listMemberCounts($listid);
  $_SESSION['listmembercount'][$listid] = [
    'content' => $status,
    'lastupdate' => $now
  ];
  $status = '<!-- not cached -->'.$status;
}

