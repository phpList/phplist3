<?php

require_once 'accesscheck.php';

if (!empty($_GET['id'])) {
    $id = sprintf('%d', $_GET['id']);
} else {
    $id = 0;
}

if ( !isSuperUser()) {
    $access = accessLevel('editlist');
    switch ($access) {
        case 'owner':
            $subselect = ' where owner = '.$_SESSION['logindetails']['id'];
            $subselect_and = ' and owner = '.$_SESSION['logindetails']['id'];
            if ($id) {
                Sql_Query('select id from '.$GLOBALS['tables']['list'].$subselect." and id = $id");
                if (!Sql_Affected_Rows()) {
                    Error(s('You do not have enough privileges to view this page'));

                    return;
                }
            } else {
                $numlists = Sql_Fetch_Row_query("select count(*) from {$GLOBALS['tables']['list']} $subselect");
                if (!($numlists[0] < MAXLIST)) {
                    Error(s('You cannot create a new list because you have reached maximum number of lists.'));

                    return;
                }
            }
            break;
        case 'all':
            $subselect = '';
            $subselect_and = '';
            break;
        case 'none':
        default:
            $subselect_and = ' and owner = -1';
            if ($id) {
                Fatal_Error(s('You do not have enough privileges to view this page'));

                return;
            }
            $subselect = ' where id = 0';
            break;
    }
}

if ($id) {
    echo '<br />'.PageLinkButton('members', s('Members of this list'), "id=$id");
}

if (!empty($_POST['addnewlist']) && !empty($_POST['listname'])) {
    if (!isSuperUser()) {
        $owner = $_SESSION['logindetails']['id'];
    }
    if (!isset($_POST['active'])) {
        $_POST['active'] = listUsedInSubscribePage($id);
    }
    //# prefix isn't used any more
    $_POST['prefix'] = '';

    $categories = listCategories();
    if (isset($_POST['category']) && in_array($_POST['category'], $categories)) {
        $category = $_POST['category'];
    } else {
        $category = '';
    }

    if ($id) {
        $query = sprintf('update %s set name="%s",description="%s",category="%s",
    active=%d,listorder=%d,prefix = "%s", owner = %d
    where id=%d', $GLOBALS['tables']['list'], sql_escape(cleanListName($_POST['listname'])),
            sql_escape($_POST['description']), sql_escape($category), $_POST['active'], $_POST['listorder'],
            $_POST['prefix'], $_POST['owner'], $id);
    } else {
        $query = sprintf('insert into %s
      (name,description,entered,listorder,owner,prefix,active,category)
      values("%s","%s",now(),%d,%d,"%s",%d,"%s")',
            $GLOBALS['tables']['list'], sql_escape(cleanListName($_POST['listname'])), sql_escape($_POST['description']),
            $_POST['listorder'], $_POST['owner'], sql_escape($_POST['prefix']), $_POST['active'],
            sql_escape($category));
    }
//  print $query;
    $result = Sql_Query($query);
    if (!$id) {
        $id = sql_insert_id();

        $_SESSION['action_result'] = s('New list added').": $id";
        $_SESSION['newlistid'] = $id;
    } else {
        $_SESSION['action_result'] = s('Changes saved');
    }
    //# allow plugins to save their fields
    foreach ($GLOBALS['plugins'] as $plugin) {
        $result = $result && $plugin->processEditList($id);
    }
    echo '<div class="actionresult">'.$_SESSION['action_result'].'</div>';
    if ($_GET['page'] == 'editlist') {
        echo '<div class="actions">'.PageLinkButton('importsimple&amp;list='.$id,
                s('Add some subscribers')).' '.PageLinkButton('editlist', s('Add another list')).'</div>';
    }
    unset($_SESSION['action_result']);

    return;
    //# doing this, the action result disappears, which we don't want
    Redirect('list');
}

if (!empty($id)) {
    $result = Sql_Query('SELECT * FROM '.$GLOBALS['tables']['list']." where id = $id");
    $list = Sql_Fetch_Array($result);
} else {
    $list = array(
        'name' => '',
//    'rssfeed' => '',  //Obsolete by rssmanager plugin
        'active'      => 0,
        'listorder'   => 0,
        'description' => '',
    );
}



$deletebutton = new ConfirmButton(
    s('Are you sure you want to delete this list?').'\n'.s('This will NOT remove the subscribers that are on this list.').'\n'.s('You can reconnect subscribers to lists on the Reconcile Subscribers page.'),
    PageURL2('list&delete='.$id),
    s('delete this list'));
if (empty($list['category'])) {
    $list['category'] = '';
}
@ob_end_flush();

?>

<?php echo formStart(' class="editlistSave" ') ?>
<input type="hidden" name="id" value="<?php echo $id ?>"/>
<div class="label"><label for="listname"><?php echo s('List name'); ?>:</label></div>
<div class="field"><input type="text" name="listname"
                          value="<?php echo htmlspecialchars(stripslashes($list['name'])) ?>"/></div>

<div class="field"><input type="checkbox" name="active" value="1"
        <?php

        echo !empty($list['active']) ? 'checked="checked"' : '';
        if (listUsedInSubscribePage($id)) {
            echo ' disabled="disabled" ';
        }

        ?> /><label for="active"><?php echo s('Public list (listed on the frontend)'); ?></label>
</div>
<div class="label"><label for="listorder"><?php echo s('Order for listing'); ?></label></div>
<div class="field"><input type="text" name="listorder" value="<?php echo $list['listorder'] ?>" class="listorder"/>
</div>
<?php if (accessLevel('editlist') == 'all') {
    if (empty($list['owner'])) {
        $list['owner'] = $_SESSION['logindetails']['id'];
    }
    $admins = $GLOBALS['admin_auth']->listAdmins();
    if (count($admins) > 1) {
        echo '<div class="label"><label for="owner">'.s('Owner').'</label></div><div class="field"><select name="owner">';
        foreach ($admins as $adminid => $adminname) {
            printf('    <option value="%d" %s>%s</option>', $adminid,
                $adminid == $list['owner'] ? 'selected="selected"' : '', htmlentities($adminname));
        }
        echo '</select></div>';
    } else {
        echo '<input type="hidden" name="owner" value="'.$_SESSION['logindetails']['id'].'" />';
    }
} else {
    echo '<input type="hidden" name="owner" value="'.$_SESSION['logindetails']['id'].'" />';
}

$aListCategories = listCategories();
if (count($aListCategories)) {
    echo '<div class="label"><label for="category">'.s('Category').'</label></div>';
    echo '<div class="field"><select name="category">';
    echo '<option value="">-- '.s('choose category').'</option>';
    foreach ($aListCategories as $category) {
        $category = trim($category);
        printf('<option value="%s" %s>%s</option>', $category,
            $category == $list['category'] ? 'selected="selected"' : '', $category);
    }
    echo '</select></div>';
}

//## allow plugins to add rows
foreach ($GLOBALS['plugins'] as $plugin) {
    echo $plugin->displayEditList($list);
}

?>
<form>
    <label for="description"><?php echo s('List Description'); ?></label>
    <div class="field"><textarea name="description" cols="35" rows="5">
<?php echo htmlspecialchars(stripslashes($list['description'])) ?></textarea></div>
    <input class="submit" type="submit" name="addnewlist" value="<?php echo s('Save'); ?>"/>
    <?php echo PageLinkClass('list', s('Cancel'), '', 'button cancel',
        s('Do not save, and go back to the lists'));
    if($id!==0){
        echo '<span class="delete">'.$deletebutton->show().'</span>';} ?>
</form>
