<?php

verifyCsrfGetToken();

$access = accessLevel('export');
$list = $_SESSION['export']['list'];

switch ($access) {
    case 'owner':
        if ($list) {
            $check = Sql_Fetch_Assoc_Query(sprintf('select id from %s where owner = %d and id = %d',
                $GLOBALS['tables']['list'], $_SESSION['logindetails']['id'], $list));
            if (empty($check['id'])) {
                echo Error(s('That is not your list'));

                return;
            }
        }

        $querytables = $GLOBALS['tables']['list'].' list INNER JOIN '.$GLOBALS['tables']['listuser'].' listuser ON listuser.listid = list.id'.
            ' INNER JOIN '.$GLOBALS['tables']['user'].' user ON listuser.userid = user.id';
        $subselect = ' and list.owner = '.$_SESSION['logindetails']['id'];
        $listselect_and = ' and owner = '.$_SESSION['logindetails']['id'];
        break;
    case 'all':
        if ($list) {
            $querytables = $GLOBALS['tables']['user'].' user'.' INNER JOIN '.$GLOBALS['tables']['listuser'].' listuser ON user.id = listuser.userid';
            $subselect = '';
        } else {
            $querytables = $GLOBALS['tables']['user'].' user';
            $subselect = '';
        }
        $listselect_and = '';
        break;
    case 'none':
    default:
        $querytables = $GLOBALS['tables']['user'].' user';
        $subselect = ' and user.id = 0';
        $listselect_and = ' and owner = 0';
        break;
}

if ($_SESSION['export']['column'] == 'nodate') {
    //# fetch dates as min and max from user table
    if ($list) {
        $dates = Sql_Fetch_Row_Query(sprintf('select date(min(user.modified)),date(max(user.modified)) from %s where listid = %d %s',
            $querytables, $list, $subselect));
    } else {
        //# this takes begin and end of all users, regardless of whether they are on the list of this admin @TODO
        $dates = Sql_Fetch_Row_Query(sprintf('select date(min(user.modified)),date(max(user.modified)) from %s ',
            $querytables));
    }

    $fromdate = $dates[0];
    $todate = $dates[1];
} else {
    $fromdate = $_SESSION['export']['fromdate'];
    $todate = $_SESSION['export']['todate'];
}
ob_end_clean();

if (!empty($_SESSION['export']['fileready']) && is_file($_SESSION['export']['fileready'])) {
    if ($list) {
        $filename = s('phpList Export on %s from %s to %s (%s).csv', ListName($list), $fromdate, $todate, date('Y-M-d'));
    } else {
        $filename = s('phpList Export from %s to %s (%s).csv', $fromdate, $todate, date('Y-M-d'));
    }
    $filename = trim(strip_tags($filename));
    header('Content-type: '.$GLOBALS['export_mimetype'].'; charset=UTF-8');
    header("Content-disposition:  attachment; filename=\"$filename\"");
    $fp = fopen($_SESSION['export']['fileready'], 'r');
    while (!feof($fp)) {
        echo fgets($fp);
    }
    fclose($fp);
    unlink($_SESSION['export']['fileready']);
    unset($_SESSION['export']);
    exit;
}

$exportfileName = tempnam($GLOBALS['tmpdir'], $GLOBALS['installation_name'].'-export'.time());
$exportfile = fopen($exportfileName, 'w');
$col_delim = "\t";
if (EXPORT_EXCEL) {
    $col_delim = ',';
}
$row_delim = "\n";

if (is_array($_SESSION['export']['cols'])) {
    foreach ($DBstruct['user'] as $key => $val) {
        if (in_array($key, $_SESSION['export']['cols'])) {
            if (strpos($val[1], 'sys') === false) {
                fwrite($exportfile, $val[1].$col_delim);
            } elseif (preg_match('/sysexp:(.*)/', $val[1], $regs)) {
                if ($regs[1] == 'ID') { // avoid freak Excel bug: http://mantis.phplist.com/view.php?id=15526
                    $regs[1] = 'id';
                }
                fwrite($exportfile, $regs[1].$col_delim);
            }
        }
    }
}
$attributes = array();
if (is_array($_SESSION['export']['attrs'])) {
    $res = Sql_Query("select id,name,type from {$tables['attribute']}");
    while ($row = Sql_fetch_array($res)) {
        if (in_array($row['id'], $_SESSION['export']['attrs'])) {
            fwrite($exportfile, trim(stripslashes($row['name'])).$col_delim);
            array_push($attributes, array('id' => $row['id'], 'type' => $row['type']));
        }
    }
}
$exporthistory = 0;
if ($_SESSION['export']['column'] == 'listentered') {
    $column = 'listuser.entered';
} elseif ($_SESSION['export']['column'] == 'historyentry') {
    $column = 'user_history.date';
    $querytables .= ', '.$GLOBALS['tables']['user_history'].' user_history ';
    $subselect .= ' and user_history.userid = user.id and user_history.summary = "Profile edit"';
    fwrite($exportfile, 'IP'.$col_delim);
    fwrite($exportfile, 'Change Summary'.$col_delim);
    fwrite($exportfile, 'Change Detail'.$col_delim);
    $exporthistory = 1;
} else {
    switch ($_SESSION['export']['column']) {
        case 'entered':
            $column = 'user.entered';
            break;
        default:
            $column = 'user.modified';
            break;
    }
}

//#$subselect .= ' limit 500'; // just to test the progress meter

if ($list) {
    $result = Sql_query(sprintf('select * from
    %s where user.id = listuser.userid and listuser.listid = %d and %s >= "%s 00:00:00" and %s  <= "%s 23:59:59" %s
    ', $querytables, $list, $column, $fromdate, $column, $todate, $subselect)
    );
} else {
    $result = Sql_query(sprintf('
    select * from %s where %s >= "%s 00:00:00" and %s  <= "%s 23:59:59" %s',
        $querytables, $column, $fromdate, $column, $todate, $subselect));
}

$todo = Sql_Affected_Rows();
$done = 0;

fwrite($exportfile, $GLOBALS['I18N']->get('List Membership').$row_delim);

while ($user = Sql_fetch_array($result)) {
    //# re-verify the blacklist status
    if (empty($user['blacklisted']) && isBlackListed($user['email'])) {
        $user['blacklisted'] = 1;
        Sql_Query(sprintf('update %s set blacklisted = 1 where email = "%s"', $GLOBALS['tables']['user'],
            $user['email']));
    }

    set_time_limit(500);
    if ($done % 50 == 0) {
        echo '<script type="text/javascript">
    var parentJQuery = window.parent.jQuery;
    parentJQuery("#progressbar").updateProgress("' .$done.','.$todo.'");
    </script>';
        flush();
    }
    ++$done;
    reset($_SESSION['export']['cols']);
    foreach ($_SESSION['export']['cols'] as $key => $val) {
        fwrite($exportfile, strtr($user[$val], $col_delim, ',').$col_delim);
    }
    reset($attributes);
    foreach ($attributes as $key => $val) {
        $value = UserAttributeValue($user['id'], $val['id']);
        fwrite($exportfile, quoteEnclosed($value, $col_delim, $row_delim).$col_delim);
    }
    if ($exporthistory) {
        fwrite($exportfile, quoteEnclosed($user['ip'], $col_delim, $row_delim).$col_delim);
        fwrite($exportfile, quoteEnclosed($user['summary'], $col_delim, $row_delim).$col_delim);
        fwrite($exportfile, quoteEnclosed($user['detail'], $col_delim, $row_delim).$col_delim);
    }

    $lists = Sql_query("select listid,name from
    {$tables['listuser']},{$tables['list']} where userid = ".$user['id']." and
    {$tables['listuser']}.listid = {$tables['list']}.id $listselect_and");
    if (!Sql_Affected_rows($lists)) {
        fwrite($exportfile, 'No Lists');
    }
    while ($list = Sql_fetch_array($lists)) {
        fwrite($exportfile, stripslashes($list['name']).'; ');
    }
    fwrite($exportfile, $row_delim);
}

echo '<script type="text/javascript">
var parentJQuery = window.parent.jQuery;
parentJQuery("#busyimage").show();
parentJQuery("#progressbar").updateProgress("' .$todo.','.$todo.'");
parentJQuery("#busyimage").hide();
parentJQuery("#progresscount").html("' .s('All done').'");
</script>';
flush();
$_SESSION['export']['fileready'] = $exportfileName;
echo '<script type="text/javascript">
document.location = document.location;
</script>';
