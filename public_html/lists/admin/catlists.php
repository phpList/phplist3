<?php
require_once dirname(__FILE__).'/accesscheck.php';

$access = accessLevel('list');
switch ($access) {
  case 'owner':
    $subselect = ' where owner = ' . $_SESSION['logindetails']['id'];
    break;
  case 'all':
    $subselect = '';
    break;
  case 'none':
  default:
    $subselect = ' where id = 0';
    break;
}
print formStart('name="categoryedit"');

if (!empty($subselect)) {
  $subselect .= ' and ';
} else {  
  $subselect .= ' where ';
}
$subselect .= '(category is null or category = "")';

$categories = listCategories();

if (!sizeof($categories)) {
  print '<p>'.$I18N->get('No list categories have been defined').'</p>';
  print '<p>'.$I18N->get('Once you have set up a few categories, come back to this page to classify your lists with your categories.').'</p>';
  print '<p>'.PageLinkButton('configure&id=list_categories',$I18N->get('Configure Categories')).'</p>';
  print '<br/>';
  return;
}

if (!empty($_POST['category']) && is_array($_POST['category'])) {
  foreach ($_POST['category'] as $key => $val) {
    Sql_Query(sprintf('update %s set category = "%s" %s and id = %d ',$tables['list'],sql_escape($val),$subselect,$key));
  }
  print Info($I18N->get('Categories saved'));
}

$req = Sql_Query(sprintf('select * from %s %s',$tables['list'],$subselect));

if (!Sql_Affected_Rows()) {
  print Info($GLOBALS['I18N']->get('All lists have already been assigned a category'),true);
}

$ls = new WebblerListing($I18N->get('Categorise lists'));
$aListCategories = listCategories();
if (sizeof($aListCategories)) {
  while ($row = Sql_Fetch_Assoc($req)) {
    $ls->addELement($row['id']);
    $ls->addColumn($row['id'],$GLOBALS['I18N']->get('Name'),$row['name']);
    $catselect = '<select name="category['.$row['id'].']">';
    $catselect .= '<option value="">-- '.$GLOBALS['I18N']->get('choose category').'</option>';
    foreach ($aListCategories as $category) {
      $category = trim($category);
      $catselect .= sprintf('<option value="%s" %s>%s</option>',$category,$category == $row['category'] ? 'selected="selected"':'',$category);
    }
    $catselect .= '</select>';
    $ls->addColumn($row['id'],$GLOBALS['I18N']->get('Category'),$catselect);
  }  
}
$ls->addButton('save','javascript:document.categoryedit.submit();');

print $ls->display();
print '</form>';
