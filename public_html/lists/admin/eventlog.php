<?php

require_once 'accesscheck.php';

$find_url = '';
$where = '';
$filter = '';
$exclude = '';
$s = 0;
if (isset($_GET['s'])) {
    $s = sprintf('%d', $_GET['s']);
}
$start = !empty($_GET['start']) ? sprintf('%d', $_GET['start']) : 0;

if (isset($_GET['filter'])) {
    $filter = removeXss($_GET['filter']);
    if (isset($_GET['exclude'])) {
        $exclude = $_GET['exclude'];
        $where = ' where page not like "%'.$filter.'%" and entry not like "%'.$filter.'%"';
        $exclude_url = '&exclude='.sprintf('%d', $_GET['exclude']);
    } else {
        $where = ' where page like "%'.$filter.'%" or entry like "%'.$filter.'%"';
        $exclude_url = '';
    }
    $find_url = '&amp;filter='.urlencode($filter).$exclude_url;
}
$order = ' order by entered desc, id desc';

if (isset($_GET['delete']) && $_GET['delete']) {
    // delete the index in delete
    $delete = sprintf('%d', $_GET['delete']);
    $_SESSION['action_result'] = $GLOBALS['I18N']->get('Deleting').' '.$delete."..\n";
    if ($require_login && !isSuperUser()) {
    } else {
        Sql_query(sprintf('delete from %s where id = %d', $tables['eventlog'], $delete));
    }
    $_SESSION['action_result'] .= $GLOBALS['I18N']->get('Done');
    Redirect('eventlog');
}

if (isset($_GET['action']) && $_GET['action']) {
    switch ($_GET['action']) {
        case 'deleteprocessed':
            Sql_Query(sprintf('delete from %s where date_add(entered,interval 2 month) < now()', $tables['eventlog']));
            $_SESSION['action_result'] = $GLOBALS['I18N']->get('Deleted all entries older than 2 months');
            Redirect('eventlog'.$find_url);
            break;
        case 'deleteall':
            Sql_Query(sprintf('delete from %s %s', $tables['eventlog'], $where));
            $_SESSION['action_result'] = $GLOBALS['I18N']->get('Deleted all entries');
            Redirect('eventlog'.$find_url);
            break;
    }
}

// view events
$count = Sql_Query("select count(*) from {$tables['eventlog']} $where");
$totalres = Sql_fetch_Row($count);
$total = $totalres[0];

print number_format($total) . ' ' . $GLOBALS['I18N']->get('Events') . '<br/>';
if ($total > MAX_USER_PP) {
    if (isset($start) && $start) {
        $limit = "limit $start,".MAX_USER_PP;
    } else {
        $limit = 'limit 0,50';
        $start = 0;
    }
    echo simplePaging("eventlog$find_url", $start, $total, MAX_USER_PP);
    $result = Sql_query(sprintf('select * from %s %s order by entered desc, id desc %s', $tables['eventlog'], $where,
        $limit));
} else {
    $result = Sql_Query(sprintf('select * from %s %s order by entered desc, id desc', $tables['eventlog'], $where));
}

$buttons = new ButtonGroup(new Button(PageURL2('eventlog'), 'delete'));
$buttons->addButton(
    new ConfirmButton(
        $GLOBALS['I18N']->get('Are you sure you want to delete all events older than 2 months?'),
        PageURL2('eventlog', 'Delete', "start=$start&action=deleteprocessed"),
        $GLOBALS['I18N']->get('Delete all (&gt; 2 months old)')));
$buttons->addButton(
    new ConfirmButton(
        $GLOBALS['I18N']->get('Are you sure you want to delete all events matching this filter?'),
        PageURL2('eventlog', 'Delete', "start=$start&action=deleteall$find_url"),
        $GLOBALS['I18N']->get('Delete all')));
echo $buttons->show();

if (!Sql_Affected_Rows()) {
    echo '<p class="information">'.$GLOBALS['I18N']->get('No events available').'</p>';
}
printf('<form method="get" action="">
<input type="hidden" name="page" value="eventlog" />
<input type="hidden" name="start" value="%d" />
%s: <input type="text" name="filter" value="%s" /> %s <input type="checkbox" name="exclude" value="1" %s />
</form><br/>', $start,
    $GLOBALS['I18N']->get('Filter'),
    htmlspecialchars(stripslashes($filter)),
    $GLOBALS['I18N']->get('Exclude filter'),
    $exclude == 1 ? 'checked="checked"' : '');

$ls = new WebblerListing($GLOBALS['I18N']->get('Events'));
$ls->setElementHeading(s('Event'));

// @@@@ Looks like there are a few del, page, date, message which may not be i18nable.

while ($event = Sql_fetch_array($result)) {
    $ls->addElement($event['id']);
    $ls->setClass($event['id'], 'row1');
    $ls->addColumn($event['id'], $GLOBALS['I18N']->get('date'), formatDateTime($event['entered']));
    $ls->addColumn($event['id'], $GLOBALS['I18N']->get('message'), strip_tags($event['entry']));
    $delete_url = sprintf('<a href="javascript:deleteRec(\'%s\');" class="del" >%s</a>',
        PageURL2('eventlog', 'delete', "start=$start&amp;delete=".$event['id']), $GLOBALS['I18N']->get('del'));
    $ls->addRow($event['id'],
        '<div class="listingsmall">'.$GLOBALS['I18N']->get('page').': '.$event['page'].'</div>',
        '<div class="fright">'.$delete_url.'&nbsp;&nbsp;</div>');
}
echo $ls->display();
