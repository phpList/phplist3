<?php
require_once dirname(__FILE__).'/accesscheck.php';

if (isset($_GET['remember_find'])) {
    $remember_find = (string) $_GET['remember_find'];
} else {
    $remember_find = '';
}

$external = $require_login && !is_a($GLOBALS['admin_auth'], 'phpListAdminAuthentication');
$start = isset($_GET['start']) ? sprintf('%d', $_GET['start']) : 0;
$listid = isset($_GET['id']) ? sprintf('%d', $_GET['id']) : 0;
$find = isset($_REQUEST['find']) ? $_REQUEST['find'] : '';

if (!empty($find)) {
    $remember_find = '&find='.urlencode($find);
} else {
    $remember_find = '';
}

// with external admins we simply display information
if ($external) {
    $admins = $GLOBALS['admin_auth']->listAdmins();
    $total = count($admins);
    $found = $total;
    $ls = new WebblerListing(s('Administrators'));
    foreach ($admins as $adminid => $adminname) {
        $ls->addElement($adminname); //,PageUrl2("admin",s('Show'),"id=".$adminid));
    }
    echo $ls->display();

    return;
}

echo '<div class="button">'.PageLink2('importadmin', s('Import list of admins')).'</div>';
echo '<div class="pull-right fright">'.PageLinkActionButton('admin', s('Add new admin'), "start=$start".$remember_find).'</div><div class="clearfix"></div>';

if (isset($_GET['delete']) && $_GET['delete']) {
    // delete the index in delete
    if ($_GET['delete'] == $_SESSION['logindetails']['id']) {
        echo s('You cannot delete yourself')."\n";
    } else {
        echo s('Deleting')." $delete ..\n";
        Sql_query(sprintf('delete from %s where id = %d', $GLOBALS['tables']['admin'], $_GET['delete']));
        Sql_query(sprintf('delete from %s where adminid = %d', $GLOBALS['tables']['admin_attribute'],
            $_GET['delete']));
        Sql_query(sprintf('delete from %s where adminid = %d', $GLOBALS['tables']['admin_task'], $_GET['delete']));
        echo '..'.s('Done')."<br /><hr><br />\n";
        Redirect("admins&start=$start");
    }
}

ob_end_flush();

if (isset($add)) {
    if (isset($new)) {
        $query = 'insert into '.$tables['admin']." (email,entered) values(\"$new\",now())";
        $result = Sql_query($query);
        $userid = Sql_insert_id();
        $query = 'insert into '.$tables['listuser']." (userid,listid,entered) values($userid,$id,now())";
        $result = Sql_query($query);
    }
    echo '<br/>'.s('Admin added').'<br/>';
}

if (!$find) {
    $result = Sql_query('SELECT count(*) FROM '.$tables['admin']);
} else {
    $result = Sql_query('SELECT count(*) FROM '.$tables['admin']." where loginname like \"%$find%\" or email like \"%$find%\"");
}
$totalres = Sql_fetch_Row($result);
$total = $totalres[0];

echo '<p class="info">'.$total.' '.s('Administrators');
echo $find ? ' '.s('found').'</p>' : '</p>';

$paging = '';
$limit = '';

if ($total > MAX_USER_PP) {
    $paging = simplePaging("admins$remember_find", $start, $total, MAX_USER_PP, s('Administrators'));
    $limit = "limit $start,".MAX_USER_PP;
}
if ($find) {
    $result = Sql_query('SELECT id,loginname,email, superuser, disabled FROM '.$tables['admin'].' where loginname like "%'.sql_escape($find).'%" or email like "%'.sql_escape($find)."%\" order by loginname $limit");
} else {
    $result = Sql_query('SELECT id,loginname,email, superuser, disabled FROM '.$tables['admin']." order by loginname $limit");
}

?>
<table>
    <tr>
        <td colspan=4><?php echo formStart('action=""') ?><input type="hidden" name="id" value="<?php echo $listid ?>">
            <?php echo s('Find an admin') ?>: <input type=text name="find"
                                                                         value="<?php echo htmlentities($find) ?>"
                                                                         size="40"><input type="submit"
                                                                                          value="<?php echo s('Go') ?>">
            </form></td>
    </tr>
</table>
<?php
$ls = new WebblerListing(s('Administrators'));
$ls->usePanel($paging);
$ls->setElementHeading('Login name');
while ($admin = Sql_fetch_array($result)) {
    $delete_url = sprintf("<a href=\"javascript:deleteRec('%s');\">".s('del').'</a>',
        PageURL2('admins', 'Delete', "start=$start&amp;delete=".$admin['id']));
    $ls->addElement(htmlentities($admin['loginname']),
        PageUrl2('admin', s('Show'), "start=$start&amp;id=".$admin['id'].$remember_find));
    $ls->addColumn($admin['loginname'], s('Id'), $admin['id']);
    $ls->addColumn($admin['loginname'], s('email'), htmlspecialchars($admin['email']));
    $ls->addColumn($admin['loginname'], s('Super Admin'), $admin['superuser'] ? s('Yes') : s('No'));
    $ls->addColumn($admin['loginname'], s('Disabled'), $admin['disabled'] ? s('Yes') : s('No'));
    if ($_SESSION['logindetails']['superuser'] && $admin['id'] != $_SESSION['logindetails']['id']) {
        $ls->addColumn($admin['loginname'], s('Del'), $delete_url);
    }
}
echo $ls->display();
echo '<br/><hr class="hidden-lg hidden-md hidden-sm hidden-xs" />';
