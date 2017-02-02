<?php

require_once dirname(__FILE__).'/accesscheck.php';

//# @@TODO, finish and add to reconcile, and then document
//# merge the history of two subscriber profiles, that are the same on email, except for some extraneous characters like space, newline, carriage return or tab
ob_end_flush();
set_time_limit(600);

function mergeUsers($original, $duplicate)
{
    set_time_limit(60);
    echo '<br/>Merging '.$duplicate.' into '.$original;

    $umreq = Sql_Query(sprintf('select * from %s where userid = %d', $GLOBALS['tables']['usermessage'], $duplicate));
    while ($um = Sql_Fetch_Array($umreq)) {
        Sql_Query(sprintf('update %s set userid = %d, entered = "%s" where userid = %d and entered = "%s"',
            $GLOBALS['tables']['usermessage'], $original, $um['entered'], $duplicate, $um['entered']), 1);
    }
    $bncreq = Sql_Query(sprintf('select * from %s where user = %d', $GLOBALS['tables']['user_message_bounce'],
        $duplicate));
    while ($bnc = Sql_Fetch_Array($bncreq)) {
        Sql_Query(sprintf('update %s set user = %d, time = "%s" where user = %d and time = "%s"',
            $GLOBALS['tables']['user_message_bounce'], $original, $bnc['time'], $duplicate, $bnc['time']), 1);
    }
    $listreq = Sql_Query(sprintf('select * from %s where userid = %d', $GLOBALS['tables']['listuser'], $duplicate));
    while ($list = Sql_Fetch_Array($listreq)) {
        Sql_Query(sprintf('update %s set userid = %d, entered = "%s" where userid = %d and entered = "%s" and listid = %d',
            $GLOBALS['tables']['listuser'], $original, $list['entered'], $duplicate, $list['entered'], $list['listid']),
            1);
    }
    Sql_Query(sprintf('delete from %s where userid = %d', $GLOBALS['tables']['listuser'], $duplicate));
    Sql_Query(sprintf('delete from %s where user = %d', $GLOBALS['tables']['user_message_bounce'], $duplicate));
    Sql_Query(sprintf('delete from %s where userid = %d', $GLOBALS['tables']['usermessage'], $duplicate));
//  if (MERGE_DUPLICATES_DELETE_DUPLICATE) {
    deleteUser($duplicate);
//  }
    flush();
}

echo '<h2>Merge on spaces</h2>';
//# find duplicates by trimming the email (ie spaces)
$req = Sql_Verbose_Query(sprintf('select u1.id,u2.id from %s u1 left join %s u2
  on u1.email = trim(u2.email) where u1.id != u2.id', $GLOBALS['tables']['user'], $GLOBALS['tables']['user']));
while ($row = Sql_Fetch_Row($req)) {
    mergeUsers($row[0], $row[1]);
}

echo '<h2>Add HEX column</h2>';
//# add a hex column on email, for better indexing
Sql_Query('alter table phplist_user_user add column hexemail varchar(255) not null default ""');
Sql_Query('alter table phplist_user_user add index hexemailidx (hexemail)');
Sql_Query('update phplist_user_user set hexemail = hex(email)');

echo '<h2>Add HEX Test column</h2>';
//# add another column for the test value
Sql_Query('alter table phplist_user_user add column hexemailtest varchar(255) not null default ""');
Sql_Query('alter table phplist_user_user add index hexemailtestidx (hexemailtest)');
Sql_Query('update phplist_user_user set hexemailtest = concat(hexemail,"0D")');

//# this should render no results
//select u1.id,u2.id,hex(u1.email),hex(u2.email) from phplist_user_user u1 left join phplist_user_user u2 on u1.hexemail = u2.hexemail where u1.id != u2.id;

// find the ones that match on email with 0D added (\r -> carriage return)
echo '<h2>Merge on CR</h2>';
$req = Sql_Verbose_Query('select u1.id,u2.id from phplist_user_user u1
  left join phplist_user_user u2 on u1.hexemailtest = u2.hexemail where u1.id != u2.id');
while ($row = Sql_Fetch_Row($req)) {
    mergeUsers($row[0], $row[1]);
}

echo '<h2>Merge on NL</h2>';
// find the ones that match on email with 0A added (\n -> newline)
Sql_Query('update phplist_user_user set hexemailtest = concat(hexemail,"0A")');
$req = Sql_Verbose_Query('select u1.id,u2.id from phplist_user_user u1
  left join phplist_user_user u2 on u1.hexemailtest = u2.hexemail where u1.id != u2.id');
while ($row = Sql_Fetch_Row($req)) {
    mergeUsers($row[0], $row[1]);
}

echo '<h2>Merge on TAB</h2>';
// find the ones that match on email with 09 added (\t -> tab)
Sql_Query('update phplist_user_user set hexemailtest = concat(hexemail,"09")');
$req = Sql_Verbose_Query('select u1.id,u2.id from phplist_user_user u1
  left join phplist_user_user u2 on u1.hexemailtest = u2.hexemail where u1.id != u2.id');
while ($row = Sql_Fetch_Row($req)) {
    mergeUsers($row[0], $row[1]);
}

Sql_Query('alter table phplist_user_user drop index hexemailidx, drop index hexemailtestidx, drop column hexemail, drop column hexemailtest');
