<?php

//#
if (!$GLOBALS['commandline']) {
    @ob_end_flush();
    echo '<p class="information">'.$GLOBALS['I18N']->get('Hint: this page also works from commandline').'</p>';
    $limit = 10000;
} else {
    //# when on cl, doit immediately
    $_GET['doit'] = 'yes';
    //# on commandline handle more
    $limit = 50000;
}

function output($message)
{
    if ($GLOBALS['commandline']) {
        @ob_end_clean();
        echo strip_tags($message)."\n";
        ob_start();
    } else {
        echo $message."\n";
        flushbuffer();
        flush();
    }
    flush();
}

function flushbuffer()
{
    for ($i = 0; $i < 10000; ++$i) {
        echo " \n";
    }
    flush();
}

include dirname(__FILE__).'/structure.php';
set_time_limit(60000);
if (!Sql_Table_exists($GLOBALS['tables']['linktrack_forward']) ||
    !Sql_Table_exists($GLOBALS['tables']['linktrack_ml']) ||
    !Sql_Table_exists($GLOBALS['tables']['linktrack_uml_click'])
) {
    output('creating tables');
    Sql_Query('drop table '.$GLOBALS['tables']['linktrack_forward']);
    Sql_Query('drop table '.$GLOBALS['tables']['linktrack_ml']);
    Sql_Query('drop table '.$GLOBALS['tables']['linktrack_uml_click']);

    Sql_Create_Table($GLOBALS['tables']['linktrack_ml'], $DBstruct['linktrack_ml']);
    Sql_Create_Table($GLOBALS['tables']['linktrack_forward'], $DBstruct['linktrack_forward']);
    Sql_Create_Table($GLOBALS['tables']['linktrack_uml_click'], $DBstruct['linktrack_uml_click']);
    output(s('creating tables done'));
}

$process_id = getPageLock();
if (empty($process_id)) {
    return;
}

//# only convert up to a week ago.
$lastweek = date('Y-m-d', time() - 24 * 7 * 3600);
cl_output(sprintf('select count(*) from %s lt, %s m where lt.messageid = m.id and m.entered < "%s"',
    $GLOBALS['tables']['linktrack'], $GLOBALS['tables']['message'], $lastweek));
$num = Sql_Fetch_Row_Query(sprintf('select count(*) from %s lt, %s m where lt.messageid = m.id and m.entered < "%s"',
    $GLOBALS['tables']['linktrack'], $GLOBALS['tables']['message'], $lastweek));
output(s('%d entries still to convert', $num[0]).'<br/>');

$c = 0;
$req = Sql_Query(sprintf('select lt.* from %s lt, %s m where lt.messageid = m.id and m.entered < "%s" limit %d',
    $GLOBALS['tables']['linktrack'], $GLOBALS['tables']['message'], $lastweek, $limit));
$total = Sql_Affected_Rows();
if ($total) {
    output(s('converting data').'<br/>');
}

while ($row = Sql_Fetch_Array($req)) {
    if (checkLock($process_id)) {
        keepLock($process_id);
    } else {
        output(s('processing cancelled'));
        break;
    }

    $exists = Sql_Fetch_Row_Query(sprintf('select id from %s where urlhash = "%s"', $GLOBALS['tables']['linktrack_forward'],
        md5($row['url'])));
    if (!$exists[0]) {
        $personalise = preg_match('/uid=/', $row['forward']);
        Sql_Query(sprintf('insert into %s (url, urlhash, personalise) values("%s","%s", %d)', $GLOBALS['tables']['linktrack_forward'],
            $row['url'], md5($row['url']), $personalise));
        $fwdid = Sql_Insert_id();
    } else {
        $fwdid = $exists[0];
    }
    set_time_limit(600);
    $messageid = $row['messageid'];
    $userid = $row['userid'];

    Sql_query(sprintf(
        'insert into %s
    set total = 1, forwardid = %d, messageid = %d
    ON DUPLICATE KEY UPDATE total = total + 1',
        $GLOBALS['tables']['linktrack_ml'], $fwdid, $messageid
    ));

    if (!empty($row['firstclick'])) {
        $result = Sql_Query(sprintf(
            'select data, count(*) as count
        from %s 
        where name = "Message Type" and linkid = %d
        group by data',
            $GLOBALS['tables']['linktrack_userclick'], $row['linkid']
        ));

        while ($ucRow = Sql_Fetch_Array($result)) {
            $count = $ucRow['count'];

            if ($ucRow['data'] == 'HTML') {
                $updateFormatClicked = ", htmlclicked = htmlclicked + $count";
                $setFormatClicked = ", htmlclicked = $count";
            } elseif ($ucRow['data'] == 'Text') {
                $updateFormatClicked = ", textclicked = textclicked + $count";
                $setFormatClicked = ", textclicked = $count";
            } else {
                $updateFormatClicked = '';
                $setFormatClicked = '';
            }

            Sql_Query(sprintf(
                'update %s
          set clicked = clicked + %d %s,
          firstclick = COALESCE(LEAST(firstclick, "%s"), "%s"),
          latestclick = COALESCE(GREATEST(latestclick, "%s"), "%s")
          where forwardid = %d and messageid = %d',
                $GLOBALS['tables']['linktrack_ml'], $count, $updateFormatClicked,
                $row['firstclick'], $row['firstclick'], $row['latestclick'], $row['latestclick'], $fwdid, $messageid
            ));

            Sql_Query(sprintf(
                'insert into %s
          set forwardid = %d, messageid = %d, userid = %d,
          firstclick = "%s", latestclick = "%s", 
          clicked = %d %s
          ON DUPLICATE KEY UPDATE clicked = clicked + %d %s',
                $GLOBALS['tables']['linktrack_uml_click'], $fwdid, $messageid, $userid,
                $row['firstclick'], $row['latestclick'],
                $count, $setFormatClicked, $count, $updateFormatClicked
            ));
        }
    }

    ++$c;
    if ($c % 100 == 0) {
        echo ". \n";
        flushbuffer();
    }
    if ($c % 1000 == 0) {
        output("$c/$total<br/> ");
        flushbuffer();
    }

    flush();
    Sql_Query(sprintf('delete from %s where linkid = %d', $GLOBALS['tables']['linktrack'], $row['linkid']));
}
set_time_limit(6000);

output($GLOBALS['I18N']->get('Optimizing table to recover space').'.<br/>');
Sql_Query(sprintf('optimize table %s', $GLOBALS['tables']['linktrack']));
output($GLOBALS['I18N']->get('Finished').'.<br/>');

if (!$GLOBALS['commandline']) {
    echo PageLink2('convertstats', $GLOBALS['I18N']->get('Convert some more'));
}
releaseLock($process_id);

return;
