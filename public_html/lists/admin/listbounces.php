<?php

require_once dirname(__FILE__) . '/accesscheck.php';
$access = accessLevel('listbounces');

$listid = empty($_GET['id']) ? 0 : sprintf('%d', $_GET['id']);
$download = isset($_GET['type']) && $_GET['type'] == 'dl';
$isowner_and = '';
$isowner_where = '';

switch ($access) {
    case 'owner':
        if ($listid) {
            $req = Sql_Query(sprintf('select id from ' . $tables['list'] . ' where owner = %d and id = %d',
                $_SESSION['logindetails']['id'], $listid));
            if (!Sql_Affected_Rows()) {
                Fatal_Error($GLOBALS['I18N']->get('You do not have enough privileges to view this page'));

                return;
            }
        }
        $isowner_and = sprintf(' list.owner = %d and ', $_SESSION['logindetails']['id']);
        $isowner_where = sprintf(' where list.owner = %d ', $_SESSION['logindetails']['id']);
        break;
    case 'all':
    case 'view':
        break;
    case 'none':
    default:
        if ($listid) {
            Fatal_Error($GLOBALS['I18N']->get('You do not have enough privileges to view this page'));
            $isowner_and = sprintf(' list.owner = 0 and ');
            $isowner_where = sprintf(' where list.owner = 0 ');

            return;
        }
        break;
}
if (!$listid) {
    ## for testing the loader allow a delay flag
    if (isset($_GET['delay'])) {
        $_SESSION['LoadDelay'] = sprintf('%d', $_GET['delay']);
    } else {
        unset($_SESSION['LoadDelay']);
    }
    print '<div id="contentdiv"></div>';
    print '<script type="text/javascript">

        var loadMessage = \''.sjs('Please wait, your request is being processed. Do not refresh this page.').'\';
        var loadMessages = new Array(); 
        loadMessages[5] = \''.sjs('Content loading &mdash; if there is a lot of data to process, this may take some time').'\';
        loadMessages[30] = \''.sjs('Content loading &mdash; thank you for your patience').'\';
        loadMessages[60] = \''.sjs('Content loading').'\';
        loadMessages[90] = \''.sjs('Content loading').'\';
        loadMessages[120] = \''.sjs('Content loading').'\';
        loadMessages[150] = \''.sjs('Content loading').'\';
        loadMessages[180] = \''.sjs('Content loading').'\';
        loadMessages[210] = \''.sjs('Content loading &mdash; either a very large amount of data is being processed or the system is experiencing high demand').'\';
        loadMessages[240] = \''.sjs('Thank you for your patience &mdash; if the content fails to load in the next few minutes please report the issue').'\';
        var contentdivcontent = "./?page=pageaction&action=listbounces&ajaxed=true&id='.$listid. addCsrfGetToken() . '";
     </script>';
    return;
}
$query = sprintf('select lu.userid, count(umb.bounce) as numbounces from %s lu join %s umb on lu.userid = umb.user
  where ' .
#  now() < date_add(umb.time,interval 6 month) and
    ' lu.listid = %d
  group by lu.userid
  ', $GLOBALS['tables']['listuser'], $GLOBALS['tables']['user_message_bounce'], $listid);

$req = Sql_Query($query);

$total = Sql_Affected_Rows();
$limit = '';
$numpp = 150;

$selectOtherlist = new buttonGroup(new Button(PageUrl2('listbounces'), $GLOBALS['I18N']->get('Select another list')));
$lists = Sql_Query(sprintf('select id,name from %s list %s order by listorder', $tables['list'], $isowner_where));
while ($list = Sql_Fetch_Assoc($lists)) {
    $selectOtherlist->addButton(new Button(PageUrl2('listbounces') . '&amp;id=' . $list['id'],
        htmlspecialchars($list['name'])));
}

print $selectOtherlist->show();
if ($total) {
    print PageLinkButton('listbounces&amp;type=dl&amp;id=' . $listid, 'Download emails');
}

print '<p>' . s('%d bounces to list %s', $total, listName($listid)) . '</p>';

$start = empty($_GET['start']) ? 0 : sprintf('%d', $_GET['start']);
if ($total > $numpp && !$download) {
    #  print Paging2('listbounces&amp;id='.$listid,$total,$numpp,'Page');
    # $listing = sprintf($GLOBALS['I18N']->get("Listing %s to %s"),$s,$s+$numpp);
    $limit = "limit $start," . $numpp;
    print simplePaging('listbounces&amp;id=' . $listid, $start, $total, $numpp);

    $query .= $limit;
    $req = Sql_Query($query);
}

if ($download) {
    ob_end_clean();
    Header('Content-type: text/plain');
    $filename = 'Bounces on ' . listName($listid);
    header("Content-disposition:  attachment; filename=\"$filename\"");
}

$ls = new WebblerListing($GLOBALS['I18N']->get('Bounces on') . ' ' . listName($listid));
$ls->noShader();
$ls->setElementHeading('Subscriber ID');
while ($row = Sql_Fetch_Array($req)) {
    $userdata = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d',
        $GLOBALS['tables']['user'], $row['userid']));
    if (!empty($userdata['email'])) {
        if ($download) {
            print $userdata['email'] . "\n";
        } else {
            $ls->addElement($row['userid'], PageUrl2('user&amp;id=' . $row['userid']));
            $ls->addColumn($row['userid'], s('Subscriber address'), $userdata['email']);
            $ls->addColumn($row['userid'], s('Total bounces'),
                PageLink2('userhistory&id=' . $row['userid'], $row['numbounces']));
        }
    }
}
if (!$download) {
    print $ls->display();
} else {
    exit;
}
