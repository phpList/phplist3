<?php
require_once dirname(__FILE__).'/accesscheck.php';

/**
 * Create the html to show the number of list members in up to three totals.
 * Confirmed - subscriber is confirmed and not blacklisted
 * Not confirmed - subscriber is not confirmed and not blacklisted
 * Blacklisted - subscriber is blacklisted.
 *
 * @param int $listId the list id, or 0 for all subscribers
 *
 * @return string
 */
function listMemberCounts($listId)
{
    global $tables;

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
    $membersDisplay = sprintf(
        '<span class="memberCount" title="%s">%s</span>'.' ('
        .'<span class="unconfirmedCount" title="%s">%s</span>, '.' '
        .'<span class="blacklistedCount" title="%s">%s</span>'.')',
        s('Confirmed and not blacklisted members'),
        number_format($counts['confirmed']),
        s('Unconfirmed and not blacklisted members'),
        number_format($counts['notconfirmed']),
        s('Blacklisted members'),
        number_format($counts['blacklisted'])
    );

    return $membersDisplay;
}

echo formStart('class="listListing"');
$some = 0;
if (isset($_GET['start'])) {
    $s = sprintf('%d', $_GET['start']);
} else {
    $s = 0;
}
$baseurl = './?page=list';
$paging = '';

$actionresult = '';

if (isset($_POST['listorder']) && is_array($_POST['listorder'])) {
    foreach ($_POST['listorder'] as $key => $val) {
        $active = sprintf('%d', $_POST['active'][$key]);
        $active = $active || listUsedInSubscribePage($key);
        $query = sprintf('update %s set listorder = %d, active = %d where id = %d', $tables['list'], $val, $active,
            $key);
        Sql_Query($query);
    }
}

$access = accessLevel('list');
switch ($access) {
    case 'owner':
        $subselect = ' where owner = '.$_SESSION['logindetails']['id'];
        $subselect_and = ' and owner = '.$_SESSION['logindetails']['id'];
        break;
    case 'all':
        $subselect = '';
        $subselect_and = '';
        break;
    case 'none':
    default:
        $subselect = ' where id = 0';
        $subselect_and = ' and id = 0';
        break;
}
if (!empty($_POST['clear'])) {
    $_SESSION['searchlists'] = '';
    $_POST['search'] = '';
}
if (!isset($_SESSION['searchlists'])) {
    $_SESSION['searchlists'] = '';
}
if (isset($_POST['search'])) {
    $_SESSION['searchlists'] = $_POST['search'];
}

if (!empty($_SESSION['searchlists'])) {
    $searchLists = ' and name like "%'.sql_escape($_SESSION['searchlists']).'%" ';
} else {
    $searchLists = '';
}
echo '<div class="row"><div class="actions col-xs-12">';
echo '<span class="pull-left">'.PageLinkButton('catlists', $I18N->get('Categorise lists')).'</span>';
$canaddlist = false;
if ( !isSuperUser()) {
    $numlists = Sql_Fetch_Row_query("select count(*) from {$tables['list']} where owner = ".$_SESSION['logindetails']['id']);
    if ($numlists[0] < MAXLIST) {
        echo '<span class="pull-right">'.PageLinkButton('editlist', s('Add a list')).'</span>';
        $canaddlist = true;
    }
} else {
    echo '<span class="pull-right">'.PageLinkButton('editlist', s('Add a list')).'</span>';
    $canaddlist = true;
}
echo '</div></div>';

if (isset($_GET['delete'])) {
    verifyCsrfGetToken();
    $delete = sprintf('%d', $_GET['delete']);
    // delete the index in delete
    $actionresult = s('Deleting').' '.s('list')." $delete ..\n";
    $result = Sql_query(sprintf('delete from '.$tables['list'].' where id = %d %s', $delete, $subselect_and));
    $done = Sql_Affected_Rows();
    if ($done) {
        $result = Sql_query('delete from '.$tables['listuser']." where listid = $delete");
        $result = Sql_query('delete from '.$tables['listmessage']." where listid = $delete");
    }
    $actionresult .= '..'.s('Done')."<br /><hr /><br />\n";
    $_SESSION['action_result'] = $actionresult;
    Redirect('list');

    return;
//  print ActionResult($actionresult);
}

if (!empty($_POST['importcontent'])) {
    include dirname(__FILE__).'/importsimple.php';
}

$html = '';

$aConfiguredListCategories = listCategories();
$aListCategories = array();
$req = Sql_Query(sprintf('select distinct category from %s where category != "" %s ', $tables['list'], $subselect_and));
while ($row = Sql_Fetch_Row($req)) {
    array_push($aListCategories, $row[0]);
}
array_push($aListCategories, s('Uncategorised'));

if (count($aListCategories)) {
    if (isset($_GET['tab']) && in_array($_GET['tab'], $aListCategories)) {
        $current = $_GET['tab'];
    } elseif (isset($_SESSION['last_list_category']) && in_array($_SESSION['last_list_category'], $aListCategories)) {
        $current = $_SESSION['last_list_category'];
    } else {
        $current = '';
    }
    if (stripos($current, strtolower(s('Uncategorised'))) !== false) {
        $current = '';
    }
    /*
     *
     * hmm, if lists are marked for a category, which is then removed, this would
     * cause them to not show up
      if (!in_array($current,$aConfiguredListCategories)) {
        $current = '';#$aListCategories[0];
      }
    */
    $_SESSION['last_list_category'] = $current;

    if ($subselect == '') {
        $subselect = ' where category = "'.$current.'"';
    } else {
        $subselect .= ' and category = "'.$current.'"';
    }
    $tabs = new WebblerTabs();
    foreach ($aListCategories as $category) {
        $category = trim(htmlspecialchars($category));
        if ($category == '') {
            $category = s('Uncategorised');
        }

        $tabs->addTab($category, $baseurl.'&amp;tab='.urlencode($category));
    }
    if ($current != '') {
        $tabs->setCurrent($current);
    } else {
        $tabs->setCurrent(s('Uncategorised'));
    }
    if (count($aListCategories) > 1) {
        echo $tabs->display();
    }
}
$countquery
    = ' select *'
    .' from '.$tables['list']
    .$subselect.$searchLists;
$countresult = Sql_query($countquery);
$total = Sql_Num_Rows($countresult);

if ($total == 0 && count($aListCategories) && $current == '' && empty($_GET['tab'])) {
    //# reload to first category, if none found by default (ie all lists are categorised)
    if (!empty($aListCategories[0])) {
        Redirect('list&tab='.$aListCategories[0]);
    }
}

//echo '<p class="total">'.$total.' '.s('Lists').'</p>';
if ($total > 30 && empty($_SESSION['showalllists'])) {
    $paging = simplePaging('list', $s, $total, 10, '&nbsp;');
    $limit = " limit $s,10";
} else {
    $limit = '';
}

$result = Sql_query('select * from '.$tables['list'].' '.$subselect.$searchLists.' order by listorder '.$limit);
$numlists = Sql_Affected_Rows($result);

$searchValue = $_SESSION['searchlists'];

echo '<div> <input type="text" name="search" placeholder="&#128269;'.s('Search lists').'" value="'.htmlentities($searchValue).'" />';
echo '<button type="submit" name="go" id="filterbutton" >'.s('Go').'</button>
      <button type="submit" name="clear" id="filterclearbutton" value="1">'.s('Clear').'</button>';
echo '</div> ';
$ls = new WebblerListing($total.' '.s('Lists'));
$ls->usePanel($paging);

/* Always Show a "list" of all subscribers
 * https://mantis.phplist.com/view.php?id=17433
 * many users are confused when they have more subscribers than members of lists
 * this will avoid that confusion
 * we can only do this for superusers of course
 * */
if (SHOW_LIST_OFALL_SUBSCRIBERS && isSuperUser()) {
    $membersDisplay = listMemberCounts(0);
    $desc = s('All subscribers');

    $element = '<!-- '.$row['id'].'-->'.s('All subscribers').Help('allsubscribers.php');
    $ls->addElement($element);
    $ls->setClass($element, 'rows row1');
    $ls->addColumn($element,
        s('Members'),
        '<div style="display:inline-block;text-align:right;width:50%;float:left;">'.$membersDisplay.'</div><span class="view" style="text-align:left;display:inline-block;float:right;width:48%;"><a class="button " href="./?page=members&id=all" title="'.s('View Members').'">'.s('View Members').'</a></span>');

    $ls->addRow($element, '',
        '<span class="send-list">'.PageLinkButton('send&new=1&list=all',
            s('send'), '', '', s('start a new campaign targetting all lists')).'</span>'.
        '<span class="add_member">'.PageLink2('import', s('Add Members')).'</span>', '', '', 'actions nodrag');
    $some = 1;
}

if ($numlists > 15) {
    Info(s('You seem to have quite a lot of lists, do you want to organise them in categories? ').' '.PageLinkButton('catlists',
            s('Great idea!')));

    /* @@TODO add paging when there are loads of lists, because otherwise the page is very slow
     * $limit = ' limit 50';
     * $query
     * = ' select *'
     * . ' from ' . $tables['list']
     * . $subselect
     * . ' order by listorder '.$limit;
     * $result = Sql_query($query);
     */
}

while ($row = Sql_fetch_array($result)) {
    $membersDisplay = listMemberCounts($row['id']);
    $desc = stripslashes($row['description']);

    //## allow plugins to add columns
    // @@@ TODO review this
    //foreach ($GLOBALS['plugins'] as $plugin) {
    //$desc = $plugin->displayLists($row) . $desc;
    //}

    $element = '<!-- '.
    $row['id'].'-->'.'<a href="./?page=members&id='.
    $row['id'].'" title="'.
    s('View Members').'">'.
    stripslashes(cleanListName($row['name']).'</a>');

    $ls->addElement($element);
    $ls->setClass($element, 'rows row1');
    $ls->addColumn($element,
        s('Members'),
        '<div style="display:inline-block;text-align:right;width:50%;float:left;">'.$membersDisplay.'</div><span class="view" style="text-align:left;display:inline-block;float:right;width:48%;"><a class="button " href="./?page=members&id='.$row['id'].'" title="'.s('View Members').'">'.s('View Members').'</a></span>');

    $ls->addColumn($element,
        s('Public').Help('publiclist'),
        sprintf('<input type="checkbox" name="active[%d]" value="1" %s %s />', $row['id'],
            $row['active'] ? 'checked="checked"' : '',
            listUsedInSubscribePage($row['id']) ? ' disabled="disabled" ' : ''));
    $ls->addColumn($element,
        s('Order'),
        sprintf('<input type="text" name="listorder[%d]" value="%d" size="3" class="listorder" />', $row['id'],
            $row['listorder']));

    $deletebutton = new ConfirmButton(
        s('Are you sure you want to delete this list?').'\n'.s('This will NOT remove the subscribers that are on this list.').'\n'.s('You can reconnect subscribers to lists on the Reconcile Subscribers page.'),
        PageURL2('list&delete='.$row['id']),
        s('delete this list'));

    $ls->addRow($element, '',
        '<span class="edit-list"><a class="button" href="?page=editlist&amp;id='.$row['id'].'" title="'.s('Edit this list').'"></a></span>'.'<span class="send-list">'.PageLinkButton('send&new=1&list='.$row['id'],
            s('send'), '', '',
            s('start a new campaign targetting this list')).'</span>'.
        '<span class="add_member">'.PageLinkDialogOnly('importsimple&list='.$row['id'],
            s('Add Members')).'</span>'.
        '<span class="delete">'.$deletebutton->show().'</span>', '', '', 'actions nodrag');

    $some = 1;
}
$ls->addSubmitButton('update', s('Save Changes'));

if (!$some) {
    echo s('No lists, use Add List to add one');
} else {
    echo $ls->display('', 'draggable');
}
/*
  echo '<table class="x" border="0">
      <tr>
        <td>'.s('No').'</td>
        <td>'.s('Name').'</td>
        <td>'.s('Order').'</td>
        <td>'.s('Functions').'</td>
        <td>'.s('Active').'</td>
        <td>'.s('Owner').'</td>
        <td>'.$html . '
    <tr>
        <td colspan="6" align="center">
        <input type="submit" name="update" value="'.s('Save Changes').'"></td>
      </tr>
    </table>';
}
*/
?>

</form>
<p class="hidden-lg hidden-md hidden-sm hidden-xs">
    <?php
    if ($canaddlist) {
        echo PageLinkButton('editlist', s('Add a list'));
    }
    ?>
</p>
