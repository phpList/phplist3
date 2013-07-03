<?php
require_once dirname(__FILE__).'/accesscheck.php';

if (isset($_GET['delete'])) {
  # delete the index in delete
  $delete = sprintf('%d',$_GET['delete']);
  print $GLOBALS['I18N']->get('Deleting')." $delete ...\n";
  $result = Sql_query("delete from ".$tables["template"]." where id = $delete");
  $result = Sql_query("delete from ".$tables["templateimage"]." where template = $delete");
  print "... ".$GLOBALS['I18N']->get('Done')."<br /><hr /><br />\n";
}
if (isset($_POST['defaulttemplate'])) {
  saveConfig('defaultmessagetemplate',sprintf('%d',$_POST['defaulttemplate']));
}
if (isset($_POST['systemtemplate'])) {
  saveConfig('systemmessagetemplate',sprintf('%d',$_POST['systemtemplate']));
}

?>

<?php

$req = Sql_Query("select * from {$tables["template"]} order by listorder");
if (!Sql_Affected_Rows())
  print '<p class="information">'.$GLOBALS['I18N']->get("No template have been defined").'</p>';

$defaulttemplate = getConfig('defaultmessagetemplate');
$systemtemplate = getConfig('systemmessagetemplate');
print formStart('name="templates" class="templatesEdit" ');
$ls = new WebblerListing($GLOBALS['I18N']->get("Existing templates"));
while ($row = Sql_fetch_Array($req)) {
  $element = $row['title'];
  $ls->addElement($element,PageUrl2('template&amp;id='.$row['id']));
  $ls->setClass($element,'row1');
  $ls->addColumn($element,$GLOBALS['I18N']->get('ID'),$row['id']);
  $ls->addRow($element,'','<span class="button">'.PageLinkDialogOnly("viewtemplate&amp;id=".$row["id"],$GLOBALS['img_view']).'</span>'.sprintf('<span class="delete"><a class="button" href="javascript:deleteRec(\'%s\');" title="'.$GLOBALS['I18N']->get('delete').'">%s</a>',PageUrl2("templates","","delete=".$row["id"]),$GLOBALS['I18N']->get('delete')));
#  $imgcount = Sql_Fetch_Row_query(sprintf('select count(*) from %s where template = %d',
#    $GLOBALS['tables']['templateimage'],$row['id']));
#  $ls->addColumn($element,$GLOBALS['I18N']->get('# imgs'),$imgcount[0]);
#  $ls->addColumn($element,$GLOBALS['I18N']->get('View'),);
  $ls->addColumn($element,$GLOBALS['I18N']->get('Campaign Default'),sprintf('<input type=radio name="defaulttemplate" value="%d" %s onchange="document.templates.submit();">',
    $row['id'],$row['id'] == $defaulttemplate ? 'checked':''));
  $ls->addColumn($element,$GLOBALS['I18N']->get('System'),sprintf('<input type=radio name="systemtemplate" value="%d" %s onchange="document.templates.submit();">',
    $row['id'],$row['id'] == $systemtemplate ? 'checked':''));

}
print $ls->display();

print '</form>';

print '<p class="button">'.PageLink2("template",$GLOBALS['I18N']->get('Add new Template'))."</p>";

$exists = Sql_Fetch_Row_Query(sprintf('select * from %s where title = "System Template"',$GLOBALS['tables']['template']));
if (empty($exists[0])) {
  print '<p class="button">'.PageLink2("defaultsystemtemplate",$GLOBALS['I18N']->get('Add default system template'))."</p>";
}


?>
