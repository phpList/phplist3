<?php
require_once dirname(__FILE__).'/accesscheck.php';

$id = !empty($_GET['id']) ? sprintf('%d',$_GET['id']) : 0;
ob_end_flush();

if (!$id) {
  Fatal_Error($GLOBALS['I18N']->get('No such attribute:')." $id");
  return;
}

if (!isset($tables["attribute"])) {
  $tables["attribute"] = "attribute";
  $tables["user_attribute"]  = "user_attribute";
}
if (!isset($table_prefix )) {
  $table_prefix = 'phplist_';
}

$res = Sql_Query("select * from $tables[attribute] where id = $id");
$data = Sql_Fetch_array($res);
$table = $table_prefix ."listattr_".$data["tablename"];
switch ($data['type']) {
  case 'checkboxgroup':
  case 'select':
  case 'radio':
    break;
  default:
    print $GLOBALS['I18N']->get('This datatype does not have editable values');
    return;
}

?>
<div class="panel"><div class="header"></div><!-- ENDOF .header -->
<div class="content">
<h3 id="attribute-name"><?php echo $data["name"]?></h3>
<div class="actions">
<?php
 print PageLinkButton("attributes",s('Back to attributes'),"");

 if (!isset($_GET['action']) || $_GET['action'] != 'new') echo PageLinkButton("editattributes",$GLOBALS['I18N']->get('add new'),"id=$id&amp;action=new");

  $button = new ConfirmButton(
     s('Are you sure you want to delete all values?'),
     PageURL2("editattributes",s('delete all'),"id=$id&amp;deleteall=yes"),
     s('Delete all'));
   
  print $button->show();
?>
</div>
<hr/>
<?php echo formStart(' class="editattributesAdd" ')?>
<input type="hidden" name="action" value="add" />
<input type="hidden" name="id" value="<?php echo $id?>" />



<?php

if (isset($_POST["addnew"])) {
  $items = explode("\n", $_POST["itemlist"]);
  $query = sprintf('select max(listorder) as listorder from %s',$table);
  $maxitem = Sql_Fetch_Row_Query($query);
  if (!Sql_Affected_Rows() || !is_numeric($maxitem[0])) {
    $listorder = 1; # insert the listorder as it's in the textarea / start with 1 '
  }
  else {
    $listorder = $maxitem[0]+1; # One more than the maximum
  }
  while (list($key,$val) = each($items)) {
    $val = clean($val);
    if ($val != "") {
      $query = sprintf('insert into %s (name,listorder) values("%s","%s")',$table,$val,$listorder);
      $result = Sql_query($query);
    }
    $listorder++;
  }
}

if (isset($_POST["listorder"]) && is_array($_POST["listorder"])) {
  foreach ($_POST["listorder"] as $key => $val) {
    Sql_Query(sprintf('update %s set listorder = %d where id = %d',sql_escape($table),$val,$key));
  }
}

function giveAlternative($table,$delete,$attributeid) {
  print $GLOBALS['I18N']->get('Alternatively you can replace all values with another one:').formStart(' class="editattributesAlternatives" ');
  print '<select name="replace"><option value="0">-- '.$GLOBALS['I18N']->get('Replace with').'</option>';
  $req = Sql_Query("select * from $table order by listorder,name");
  while ($row = Sql_Fetch_array($req))
    if ($row["id"] != $delete)
      printf('<option value="%d">%s</option>',$row["id"],$row["name"]);
  print "</select>";
  printf('<input type="hidden" name="delete" value="%d" />',$delete);
  printf('<input type="hidden" name="id" value="%d" />',$attributeid);
  printf('<input class="submit" type="submit" name="deleteandreplace" value="%s" /><hr class="line" />',$GLOBALS['I18N']->get('Delete and replace'));
}

function deleteItem($table,$attributeid,$delete) {
  global $tables;
  if (isset($_REQUEST['replace'])) {
    $replace = sprintf('%d',$_REQUEST['replace']);
  } else {
    $replace = 0;
  }
  # delete the index in delete
  $valreq = Sql_Fetch_Row_query("select name from $table where id = $delete");
  $val = $valreq[0];

  # check dependencies
  $dependencies = array();
  $result = Sql_query("select distinct userid from $tables[user_attribute] where
  attributeid = $attributeid and value = $delete");
  while ($row =  Sql_fetch_array($result)) {
    array_push($dependencies,$row["userid"]);
  }

  if (sizeof($dependencies) == 0)
    $result = Sql_query("delete from $table where id = $delete");
  else if ($replace) {
    $result = Sql_Query("update $tables[user_attribute] set value = $replace where value = $delete");
    $result = Sql_query("delete from $table where id = $delete");
  } else {
    print $GLOBALS["I18N"]->get("Cannot delete");
    print " <b>$val</b><br />";
    print $GLOBALS["I18N"]->get("The following subscriber(s) are dependent on this value<br />Update the subscriber profiles to not use this attribute value and try again");

    for ($i=0;$i<sizeof($dependencies);$i++) {
      print PageLink2("user",$GLOBALS["I18N"]->get("user")." ".$dependencies[$i],"id=$dependencies[$i]")."<br />\n";
      if ($i>10) {
        print $GLOBALS['I18N']->get('* Too many to list, total dependencies:')."
 ".sizeof($dependencies)."<br /><br />";
        giveAlternative($table,$delete,$attributeid);
        return 0;
      }
    }
    print "<br />";
    giveAlternative($table,$delete,$attributeid);

  }
  return 1;
}

if (isset($_GET["delete"])) {
  deleteItem($table,$id,sprintf('%d',$_GET["delete"]));
} elseif(isset($_GET["deleteall"])) {
  $count = 0;
  $errcount = 0;
  $res = Sql_Query("select id from $table");
  while ($row = Sql_Fetch_Row($res)) {
    if (deleteItem($table,$id,$row[0])) {
      $count++;
    } else {
      $errcount++;
      if ($errcount > 10) {
        print $GLOBALS['I18N']->get('* Too many errors, quitting')."<br /><br /><br />\n";
        break;
      }
    }
  }
}

if (isset($_GET["action"]) && $_GET["action"] == "new") {

  // ??
  ?>

  <p><?php echo $GLOBALS["I18N"]->get("Add new")." ".$data["name"].', '.$GLOBALS["I18N"]->get("one per line") ?></p>
  <textarea name="itemlist" rows="20" cols="50"></textarea>
  <input class="submit" type="submit" name="addnew" value="<?php echo $GLOBALS["I18N"]->get("Add new")." ".$data["name"] ?>" /><br />
  <hr />
<?php
}

$req = Sql_query("SELECT * FROM $table order by listorder,name");
$num = Sql_Affected_Rows();
if ($num < 100 && $num > 25)
  printf('<input class="submit" type="submit" name="action" value="%s" /><br /><br />',$GLOBALS["I18N"]->get("Change order"));

while ($row = Sql_Fetch_array($req)) {
  printf( '<div class="row-value"><span class="delete"><a href="javascript:deleteRec(\'%s\');">'.$GLOBALS['I18N']->get('delete').'</a></span>',PageURL2("editattributes","","id=$id&amp;delete=".$row["id"]));
  if ($num < 100)
    printf(' <input type="text" name="listorder[%d]" value="%s" size="5" class="listorder" />',$row["id"],$row["listorder"]);
  printf(' %s %s </div>', $row["name"],($row["name"] == $data["default_value"]) ? '('.$GLOBALS['I18N']->get('default').')':"");
}
if ($num && $num < 100)
  printf('<br /><input class="submit" type="submit" name="action" value="%s" />',$GLOBALS["I18N"]->get("Change order"));

?>
</form>

</div> <!-- eo content -->
</div> <!-- eo panel -->
