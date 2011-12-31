
<?php
require_once dirname(__FILE__).'/accesscheck.php';

if (isset($_POST["default"]) && $_POST['default']) {
  saveConfig("defaultsubscribepage",$_POST["default"]);
}

$default = getConfig("defaultsubscribepage");

$subselect = '';
if ($GLOBALS["require_login"] && !isSuperUser()) {
  $access = accessLevel("list");
  switch ($access) {
    case "owner":
      $subselect = " where owner = ".$_SESSION["logindetails"]["id"];break;
    case "all":
      $subselect = "";break;
    case "none":
    default:
      $subselect = " where id = 0";break;
  }
}

if (isset($_REQUEST['delete'])) {
  $delete = sprintf('%d',$_REQUEST['delete']);
} else {
  $delete = 0;
}
if ($delete) {
  Sql_Query(sprintf('delete from %s where id = %d',
    $tables["subscribepage"],$delete));
  Sql_Query(sprintf('delete from %s where id = %d',
    $tables["subscribepage_data"],$delete));
   Info($GLOBALS['I18N']->get('Deleted')." $delete");
}
print formStart('name="pagelist" class="spageEdit" ');
$ls = new WebblerListing($GLOBALS['I18N']->get('subscribe pages'));

$req = Sql_Query(sprintf('select * from %s %s order by title',$tables["subscribepage"],$subselect));
while ($p = Sql_Fetch_Array($req)) {
  $ls->addElement($p["id"]);
  $ls->addColumn($p["id"],$GLOBALS['I18N']->get('title'),$p["title"]);
  $ls->addColumn($p["id"],$GLOBALS['I18N']->get('edit'),sprintf('<a href="%s&amp;id=%d">%s</a>',PageURL2("spageedit",""),$p["id"],$GLOBALS['I18N']->get('edit')));
  $ls->addColumn($p["id"],$GLOBALS['I18N']->get('del'),sprintf('<a href="javascript:deleteRec(\'%s\');">%s</a>',PageURL2("spage","","delete=".$p["id"]),$GLOBALS['I18N']->get('del')));
  $ls->addColumn($p["id"],$GLOBALS['I18N']->get('view'),sprintf('<a href="%s&amp;id=%d">%s</a>',getConfig("subscribeurl"),$p["id"],$GLOBALS['I18N']->get('view')));
  $ls->addColumn($p["id"],$GLOBALS['I18N']->get('status'),$p["active"]? $GLOBALS['I18N']->get('active'):$GLOBALS['I18N']->get('not active'));
  if (($require_login && isSuperUser()) || !$require_login) {
    $ls->addColumn($p["id"],$GLOBALS['I18N']->get('owner'),adminName($p["owner"]));
    if ($p["id"] == $default) {
      $checked = 'checked="checked"';
    } else {
      $checked = "";
    }
    $ls->addColumn($p["id"],$GLOBALS['I18N']->get('default'),sprintf('<input type="radio" name="default" value="%d" %s onchange="document.pagelist.submit()" />',$p["id"],$checked));
  } else {
    $adminname = "";
    $isdefault = "";
  }
}
print $ls->display();
print '<p class="button">'.PageLink2("spageedit",$GLOBALS['I18N']->get('Add a new one')).'</p>';
?>
</form>
