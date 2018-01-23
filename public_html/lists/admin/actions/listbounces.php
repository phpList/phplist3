<?php

verifyCsrfGetToken();

$access = accessLevel('listbounces');

$listid = empty($_GET['id']) ? 0 : sprintf('%d', $_GET['id']);
$download = isset($_GET['type']) && $_GET['type'] == 'dl';
$isowner_and = '';
$isowner_where = '';
$status = '';

switch ($access) {
    case 'owner':
        if ($listid) {
            $req = Sql_Query(sprintf('select id from '.$tables['list'].' where owner = %d and id = %d',
                $_SESSION['logindetails']['id'], $listid));
            if (!Sql_Affected_Rows()) {
                Fatal_Error(s('You do not have enough privileges to view this page'));

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
            Fatal_Error(s('You do not have enough privileges to view this page'));
            $isowner_and = sprintf(' list.owner = 0 and ');
            $isowner_where = sprintf(' where list.owner = 0 ');

            return;
        }
        break;
}
if (!empty($_SESSION['LoadDelay'])) {
    sleep($_SESSION['LoadDelay']);
}
$req = Sql_Query(sprintf('select listuser.listid,count(distinct userid) as numusers from %s list, %s listuser,
    %s umb, %s lm where %s list.id = listuser.listid and listuser.listid = lm.listid and listuser.userid = umb.user group by listuser.listid
    order by listuser.listid limit 250', $GLOBALS['tables']['list'], $GLOBALS['tables']['listuser'],
    $GLOBALS['tables']['user_message_bounce'], $GLOBALS['tables']['listmessage'], $isowner_and));
$ls = new WebblerListing(s('Choose a list'));
$ls->setElementHeading('List name');
$some = 0;
while ($row = Sql_Fetch_Array($req)) {
    $some = 1;
    $element = '<!--'.s('list').' '.$row['listid'].'-->'.listName($row['listid']);
    $ls->addElement($element, PageUrl2('listbounces&amp;id='.$row['listid']));
    //  $ls->addColumn($element,$GLOBALS['I18N']->get('name'),listName($row['listid']),PageUrl2('editlist&amp;id='.$row['listid']));
    $ls->addColumn($element, s('Total bounces'), number_format($row['numusers']));
}
if ($some) {
    $status = $ls->display();
} else {
    $status = '<p>'.s('None found').'</p>';
}
