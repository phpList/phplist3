<?php

require_once dirname(__FILE__).'/accesscheck.php';
$access = accessLevel('listbounces');

$listid = empty($_GET['id']) ? 0 : sprintf('%d', $_GET['id']);
$download = isset($_GET['type']) && $_GET['type'] == 'dl';
$isowner_and = '';
$isowner_where = '';

switch ($access) {
    case 'owner':
        if ($listid) {
            $req = Sql_Query(sprintf('select id from '.$tables['list'].' where owner = %d and id = %d',
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
    //# for testing the loader allow a delay flag
    if (isset($_GET['delay'])) {
        $_SESSION['LoadDelay'] = sprintf('%d', $_GET['delay']);
    } else {
        unset($_SESSION['LoadDelay']);
    }
    echo '<div id="contentdiv"></div>';
    echo asyncLoadContent('./?page=pageaction&action=listbounces&ajaxed=true&id='.$listid.addCsrfGetToken());

    return;
}
$query = sprintf('select lu.userid, count(umb.bounce) as numbounces from %s lu join %s umb on lu.userid = umb.user
  where ' .
//  now() < date_add(umb.time,interval 6 month) and
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
    $selectOtherlist->addButton(new Button(PageUrl2('listbounces').'&amp;id='.$list['id'],
        htmlspecialchars($list['name'])));
}

echo $selectOtherlist->show();
if ($total) {
    echo PageLinkButton('listbounces&amp;type=dl&amp;id='.$listid, s('Download addresses'),'','btn-primary pull-right btn-lg');
}

echo '<p>'.s('%s bounces to list %s', number_format($total), listName($listid)).'</p>';

$start = empty($_GET['start']) ? 0 : sprintf('%d', $_GET['start']);
if ($total > $numpp && !$download) {
    //  print Paging2('listbounces&amp;id='.$listid,$total,$numpp,'Page');
    // $listing = sprintf($GLOBALS['I18N']->get("Listing %s to %s"),$s,$s+$numpp);
    $limit = "limit $start,".$numpp;
    echo simplePaging('listbounces&amp;id='.$listid, $start, $total, $numpp);

    $query .= $limit;
    $req = Sql_Query($query);
}

if ($download) {
    ob_end_clean();
    header('Content-type: text/plain');
    $filename = 'Bounces on '.listName($listid).'.csv';
    header('Content-disposition:  attachment; filename="'.$filename.'"');
}

$ls = new WebblerListing($GLOBALS['I18N']->get('Bounces on').' '.listName($listid));
$ls->noShader();
$ls->setElementHeading('Subscriber ID');
while ($row = Sql_Fetch_Array($req)) {
    $userdata = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d',
        $GLOBALS['tables']['user'], $row['userid']));
    if (!empty($userdata['email'])) {
        if ($download) {
            echo $userdata['email']."\n";
        } else {
            $ls->addElement($row['userid'], PageUrl2('user&amp;id='.$row['userid']));
            $ls->addColumn($row['userid'], s('Subscriber address'), PageLink2('user&id='.$row['userid'], $userdata['email']));
            $ls->addColumn($row['userid'], s('Total bounces'),
                PageLink2('user&id='.$row['userid'], $row['numbounces']));
        }
    }
}
if (!$download) {
    echo $ls->display();
} else {
    exit;
}
