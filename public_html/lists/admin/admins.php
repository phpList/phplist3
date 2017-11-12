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

echo '<div class="button">'.PageLink2('importadmin', $GLOBALS['I18N']->get('Import list of admins')).'</div>';

// with external admins we simply display information
if (!$external) {
    echo '<div class="pull-right fright">'.PageLinkActionButton('admin', $GLOBALS['I18N']->get('Add new admin'), "start=$start".$remember_find).'</div><div class="clearfix"></div>';

    if (isset($_GET['delete']) && $_GET['delete']) {
        // delete the index in delete
        if ($_GET['delete'] == $_SESSION['logindetails']['id']) {
            echo $GLOBALS['I18N']->get('You cannot delete yourself')."\n";
        } else {
            echo $GLOBALS['I18N']->get('Deleting')." $delete ..\n";
            Sql_query(sprintf('delete from %s where id = %d', $GLOBALS['tables']['admin'], $_GET['delete']));
            Sql_query(sprintf('delete from %s where adminid = %d', $GLOBALS['tables']['admin_attribute'],
                $_GET['delete']));
            Sql_query(sprintf('delete from %s where adminid = %d', $GLOBALS['tables']['admin_task'], $_GET['delete']));
            echo '..'.$GLOBALS['I18N']->get('Done')."<br /><hr><br />\n";
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
        echo '<br/>'.$GLOBALS['I18N']->get('Admin added').'<br/>';
    }
}

if ($external) {
    $admins = $GLOBALS['admin_auth']->listAdmins();
    $total = count($admins);
    $found = $total;
    $ls = new WebblerListing($GLOBALS['I18N']->get('Administrators'));
    foreach ($admins as $adminid => $adminname) {
        $ls->addElement($adminname); //,PageUrl2("admin",$GLOBALS['I18N']->get('Show'),"id=".$adminid));
    }
    echo $ls->display();

    return;
} else {
    if (!$find) {
        $result = Sql_query('SELECT count(*) FROM '.$tables['admin']);
    } else {
        $result = Sql_query('SELECT count(*) FROM '.$tables['admin']." where loginname like \"%$find%\" or email like \"%$find%\"");
    }
    $totalres = Sql_fetch_Row($result);
    $total = $totalres[0];
}

echo '<p class="info">'.$total.' '.$GLOBALS['I18N']->get('Administrators');
echo $find ? ' '.$GLOBALS['I18N']->get('found').'</p>' : '</p>';

$paging = '';
if ($total > MAX_USER_PP) {
    $paging = simplePaging("admins$remember_find", $start, $total, MAX_USER_PP,
        $GLOBALS['I18N']->get('Administrators'));
}
$limit = '';
if ($total > MAX_USER_PP) {
    if (isset($start) && $start) {
        $limit = "limit $start,".MAX_USER_PP;
    } else {
        $limit = 'limit 0,50';
        $start = 0;
    }
}
if ($find) {
    $result = Sql_query('SELECT id,loginname,email FROM '.$tables['admin'].' where loginname like "%'.sql_escape($find).'%" or email like "%'.sql_escape($find)."%\" order by loginname $limit");
} else {
    $result = Sql_query('SELECT id,loginname,email FROM '.$tables['admin']." order by loginname $limit");
}

?>
<table>
    <tr>
        <td colspan=4><?php echo formStart('action=""') ?><input type="hidden" name="id" value="<?php echo $listid ?>">
            <?php echo $GLOBALS['I18N']->get('Find an admin') ?>: <input type=text name="find"
                                                                         value="<?php echo htmlspecialchars($find) ?>"
                                                                         size="40"><input type="submit"
                                                                                          value="<?php echo $GLOBALS['I18N']->get('Go') ?>">
            </form></td>
    </tr>
</table>
<?php
$ls = new WebblerListing($GLOBALS['I18N']->get('Administrators'));
$ls->usePanel($paging);
while ($admin = Sql_fetch_array($result)) {
    $delete_url = sprintf("<a href=\"javascript:deleteRec('%s');\">".$GLOBALS['I18N']->get('del').'</a>',
        PageURL2('admins', 'Delete', "start=$start&amp;delete=".$admin['id']));
    $ls->addElement($admin['loginname'],
        PageUrl2('admin', $GLOBALS['I18N']->get('Show'), "start=$start&amp;id=".$admin['id'].$remember_find));
    if (!$external && $admin['id'] != $_SESSION['logindetails']['id']) {
        $ls->addColumn($admin['loginname'], $GLOBALS['I18N']->get('Del'), $delete_url);
    }
}
echo $ls->display();
echo '<br/><hr class="hidden-lg hidden-md hidden-sm hidden-xs" />';

?>
