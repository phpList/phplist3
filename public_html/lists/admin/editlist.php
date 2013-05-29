<?php

require_once 'accesscheck.php';

if (!empty($_GET['id'])) {
  $id = sprintf('%d',$_GET["id"]);
} else {
  $id = 0;
}

if ($GLOBALS["require_login"] && !isSuperUser()) {
  $access = accessLevel("editlist");
  switch ($access) {
    case "owner":
      $subselect = " where owner = ".$_SESSION["logindetails"]["id"];
      $subselect_and = " and owner = ".$_SESSION["logindetails"]["id"];
      if ($id) {
        Sql_Query("select id from ".$GLOBALS['tables']["list"]. $subselect . " and id = $id");
        if (!Sql_Affected_Rows()) {
          Error($GLOBALS['I18N']->get('You do not have enough priviliges to view this page'));
          return;
        }
      } else {
        $numlists = Sql_Fetch_Row_query("select count(*) from {$GLOBALS['tables']['list']} $subselect");
        if (!($numlists[0] < MAXLIST)) {
          Error($GLOBALS['I18N']->get('You cannot create a new list because you have reached maximum number of lists.'));
          return;
        }
      }
      break;
    case "all":
      $subselect = ""; 
      $subselect_and = "";
      break;
    case "none":
    default:
      $subselect_and = " and owner = -1";
      if ($id) {
        Fatal_Error($GLOBALS['I18N']->get('You do not have enough priviliges to view this page'));
        return;
      }
      $subselect = " where id = 0";
      break;
  }
}

if ($id) {
  echo "<br />".PageLinkButton("members",s('Members of this list'),"id=$id");
}

if (!empty($_POST["addnewlist"]) && !empty($_POST["listname"])) {
  if ($GLOBALS["require_login"] && !isSuperUser()) {
    $owner = $_SESSION["logindetails"]["id"];
  }
  if (!isset($_POST["active"])) $_POST["active"] = 0;
  $_POST['listname'] = removeXss($_POST['listname']);
  ## prefix isn't used any more
  $_POST['prefix'] = '';
  
  $categories = listCategories();
  if (isset($_POST['category']) && in_array($_POST['category'],$categories)) {
    $category = $_POST['category'];
  } else {
    $category = '';
  }

  if ($id) {
    $query
    = ' update %s'
    . ' set name = ?, description = ?, active = ?,'
    . '     listorder = ?, prefix = ?, owner = ?, category = ?'
    . ' where id = ?';
    $query = sprintf($query, $GLOBALS['tables']['list']);
    $result = Sql_Query_Params($query, array($_POST['listname'],
       $_POST['description'], $_POST['active'], $_POST['listorder'],
       $_POST['prefix'], $_POST['owner'], $category, $id));
  } else {
    $query
    = ' insert into %s'
    . '    (name, description, entered, listorder, owner, prefix, active, category)'
    . ' values'
    . '    (?, ?, current_timestamp, ?, ?, ?, ?, ?)';
    $query = sprintf($query, $GLOBALS['tables']['list']);
#  print $query;
    $result = Sql_Query_Params($query, array($_POST['listname'],
       $_POST['description'], $_POST['listorder'], $_POST['owner'],
       $_POST['prefix'], $_POST['active'], $category));
  }
  if (!$id) {
    $id = Sql_Insert_Id($GLOBALS['tables']['list'], 'id');

    $_SESSION['action_result'] = s('New list added') . ": $id";
    $_SESSION['newlistid'] = $id;
  } else {
    $_SESSION['action_result'] = s('Changes saved');
  }
  ## allow plugins to save their fields
  foreach ($GLOBALS['plugins'] as $plugin) {
    $result = $result && $plugin->processEditList($id);
  }
  print '<div class="actionresult">'.$_SESSION['action_result'].'</div>';
  if ($_GET['page'] == 'editlist') {
    print '<div class="actions">'.PageLinkButton('importsimple&amp;list='.$id,s('Add some subscribers')).'</div>';
  }
  unset($_SESSION['action_result']);
  return;
  ## doing this, the action result disappears, which we don't want
  Redirect('list');
}

if (!empty($id)) {
  $result = Sql_Query("SELECT * FROM ".$GLOBALS['tables']['list']." where id = $id");
  $list = Sql_Fetch_Array($result);
} else {
  $list = array(
    'name' => '',
//    'rssfeed' => '',  //Obsolete by rssmanager plugin
    'active' => 0,
    'listorder' => 0,
    'description' => '',
  );
}
if (empty($list['category'])) {
  $list['category'] = '';
}
@ob_end_flush();

?>

<?php echo formStart(' class="editlistSave" ')?>
<input type="hidden" name="id" value="<?php echo $id ?>" />
<div class="label"><label for="listname"><?php echo $GLOBALS['I18N']->get('List name'); ?>:</label></div>
<div class="field"><input type="text" name="listname" value="<?php echo  htmlspecialchars(StripSlashes($list["name"]))?>" /></div>
<div class="field"><input type="checkbox" name="active" value="1" <?php echo $list["active"] ? 'checked="checked"' : ''; ?> /><label for="active"><?php echo $GLOBALS['I18N']->get('Public list (listed on the frontend)'); ?></label></div>
<div class="label"><label for="listorder"><?php echo $GLOBALS['I18N']->get('Order for listing'); ?></label></div>
<div class="field"><input type="text" name="listorder" value="<?php echo $list["listorder"] ?>" class="listorder" /></div>
<?php if ($GLOBALS["require_login"] && (isSuperUser() || accessLevel("editlist") == "all")) {
  if (empty($list["owner"])) {
    $list["owner"] = $_SESSION["logindetails"]["id"];
  }
  print '<div class="label"><label for="owner">' . $GLOBALS['I18N']->get('Owner') . '</label></div><div class="field"><select name="owner">';
  $admins = $GLOBALS["admin_auth"]->listAdmins();
  foreach ($admins as $adminid => $adminname) {
    printf ('    <option value="%d" %s>%s</option>',$adminid,$adminid == $list["owner"]? 'selected="selected"':'',$adminname);
  }
  print '</select></div>';
} else {
  print '<input type="hidden" name="owner" value="'.$_SESSION["logindetails"]["id"].'" />';
}

$aListCategories = listCategories();
if (sizeof($aListCategories)) {
  print '<div class="label"><label for="category">'.$GLOBALS['I18N']->get('Category').'</label></div>';
  print '<div class="field"><select name="category">';
  print '<option value="">-- '.$GLOBALS['I18N']->get('choose category').'</option>';
  foreach ($aListCategories as $category) {
    $category = trim($category);
    printf('<option value="%s" %s>%s</option>',$category,$category == $list['category'] ? 'selected="selected"':'',$category);
  }
  print '</select></div>';
}

  ### allow plugins to add rows
  foreach ($GLOBALS['plugins'] as $plugin) {
    print $plugin->displayEditList($list);
  }

?>
<label for="description"><?php echo $GLOBALS['I18N']->get('List Description'); ?></label>
<div class="field"><textarea name="description" cols="35" rows="5">
<?php echo htmlspecialchars(stripslashes($list["description"])) ?></textarea></div>
<input class="submit" type="submit" name="addnewlist" value="<?php echo $GLOBALS['I18N']->get('Save'); ?>" />
<?php print PageLinkClass('list',$GLOBALS['I18N']->get('Cancel'),'','button cancel',$GLOBALS['I18N']->get('Do not save, and go back to the lists')); ?>
</form>
