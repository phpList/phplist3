<?php
require_once dirname(__FILE__).'/accesscheck.php';

print formStart('class="listListing"');
$some = 0;
if (isset($_GET['s'])) {
  $s = sprintf('%d',$_GET['s']);
} else {
  $s = 0;
}
$baseurl = './?page=list';

$actionresult = '';

if (isset($_POST['listorder']) && is_array($_POST['listorder']))
  while (list($key,$val) = each ($_POST['listorder'])) {
    $active = empty($_POST['active'][$key]) ? '0' : '1';
    $query
    = ' update %s'
    . ' set listorder = ?, active = ?'
    . ' where id = ?';
    $query = sprintf($query, $tables['list']);
    Sql_Query_Params($query, array($val, $active, $key));
  }

$access = accessLevel('list');
switch ($access) {
  case 'owner':
    $subselect = ' where owner = ' . $_SESSION['logindetails']['id'];
    $subselect_and = ' and owner = ' . $_SESSION['logindetails']['id'];
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

print '<div class="actions">';
print PageLinkButton('catlists',$I18N->get('Categorise lists'));
$canaddlist = false;
if ($GLOBALS['require_login'] && !isSuperUser()) {
  $numlists = Sql_Fetch_Row_query("select count(*) from {$tables['list']} where owner = " . $_SESSION['logindetails']['id']);
  if ($numlists[0] < MAXLIST) {
    print PageLinkButton("editlist",$GLOBALS['I18N']->get('Add a list'));
    $canaddlist = true;
  }
} else {
  print PageLinkButton('editlist',$GLOBALS['I18N']->get('Add a list'));
  $canaddlist = true;
}
print '</div>';

if (isset($_GET['delete'])) {
  $delete = sprintf('%d',$_GET['delete']);
  # delete the index in delete
  $actionresult = $GLOBALS['I18N']->get('Deleting') . ' '.$GLOBALS['I18N']->get('list')." $delete ..\n";
  $result = Sql_query(sprintf('delete from '.$tables['list'].' where id = %d %s',$delete,$subselect_and));
  $done = Sql_Affected_Rows();
  if ($done) {
    $result = Sql_query('delete from '.$tables['listuser']." where listid = $delete $subselect_and");
    $result = Sql_query('delete from '.$tables['listmessage']." where listid = $delete $subselect_and");
  }
  $actionresult .= '..' . $GLOBALS['I18N']->get('Done') . "<br /><hr /><br />\n";
  $_SESSION['action_result'] = $actionresult;
  Redirect('list');
  return;
#  print ActionResult($actionresult);
}

if (!empty($_POST['importcontent'])) {
  include dirname(__FILE__).'/importsimple.php';
}

$html = '';

$aConfiguredListCategories = listCategories();
$aListCategories = array();
$req = Sql_Query(sprintf('select distinct category from %s',$tables['list']));
while ($row = Sql_Fetch_Row($req)) {
  array_push($aListCategories,$row[0]);
}
array_push($aListCategories,s('Uncategorised')); 

if (sizeof($aListCategories)) {
  if (isset($_GET['tab']) && in_array($_GET['tab'],$aListCategories)) {
    $current = $_GET['tab'];
  } elseif (isset($_SESSION['last_list_category'])) {
    $current = $_SESSION['last_list_category'];
  } else {
    $current = '';
  }
  if (stripos($current,strtolower(s('Uncategorised'))) !== false) {
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
    $category = trim($category);
    if ($category == '') {
      $category = $GLOBALS['I18N']->get('Uncategorised');
    }

    $tabs->addTab($category,$baseurl.'&amp;tab='.urlencode($category));
  }
  if ($current != '') {
    $tabs->setCurrent($current);
  } else {
    $tabs->setCurrent(s('Uncategorised'));
  }
  print $tabs->display();
}
$countquery
= ' select *'
. ' from ' . $tables['list']
. $subselect;
$countresult = Sql_query($countquery);
$total = Sql_Num_Rows($countresult);

if ($total == 0 && sizeof($aListCategories) && $current == '' && empty($_GET['tab'])) {
  ## reload to first category, if none found by default (ie all lists are categorised)
  if (!empty($aListCategories[0])) {
    Redirect('list&tab='.$aListCategories[0]);
  }
}

print '<p>'.$total .' '. $GLOBALS['I18N']->get('Lists').'</p>';
$limit = '';

$query
= ' select *'
. ' from ' . $tables['list']
. $subselect
. ' order by listorder '.$limit;

$result = Sql_query($query);
$ls = new WebblerListing($GLOBALS['I18N']->get('Lists'));

$numlists = Sql_Affected_Rows();
if ($numlists > 15) {
  Info($GLOBALS['I18N']->get('You seem to have quite a lot of lists, do you want to organise them in categories? ').' '.PageLinkButton('catlists',$GLOBALS['I18N']->get('Great idea!')));
}

while ($row = Sql_fetch_array($result)) {
  
  /*
   * this is rather demanding with quite a few lists/subscribers
   * find a better way
   */
  
  $query
  = ' select count(*)'
  . ' from ' . $tables['listuser']
  . ' where listid = ?';
  $rsc = Sql_Query_Params($query, array($row["id"]));
  $membercount = Sql_Fetch_Row($rsc);
  if ($membercount[0]<=0) {
    $members = $GLOBALS['I18N']->get('None yet');
  } else {
    $members = $membercount[0];
  }

/*
  $query = sprintf('
  select count(distinct userid) as bouncecount from %s listuser,
  %s umb where listuser.userid = umb.user and listuser.listid = ? ',
  $GLOBALS['tables']['listuser'],$GLOBALS['tables']['user_message_bounce'],$row['id'])

  print $query;
*/
  $bouncecount =
    Sql_Fetch_Row_Query(sprintf('select count(distinct userid) as bouncecount from %s listuser, %s umb where listuser.userid = umb.user and listuser.listid = %s ',$GLOBALS['tables']['listuser'],$GLOBALS['tables']['user_message_bounce'],$row['id']));
  if ($bouncecount[0]<=0) {
    $bounces = $GLOBALS['I18N']->get('None yet');
  } else {
    $bounces = $bouncecount[0];
  }

  $desc = stripslashes($row['description']);

  ## allow plugins to add columns
  foreach ($GLOBALS['plugins'] as $plugin) {
    $desc = $plugin->displayLists($row) . $desc;
  }

  $element = '<!-- '.$row['id'].'-->'.$row['name'];
  $ls->addElement($element,PageUrl2("editlist&amp;id=".$row["id"]));
  
  $ls->addColumn($element,
    $GLOBALS['I18N']->get('Members'),
    PageLink2("members",'<span class="membercount">'.$members.'</span>',"id=".$row["id"]).' '.PageLinkDialog('importsimple&list='.$row["id"],$GLOBALS['I18N']->get('add')));
  $ls->addColumn($element,
    $GLOBALS['I18N']->get('Bounces'),
    PageLink2("listbounces",'<span class="bouncecount">'.$bounces.'</span>',"id=".$row["id"]));#.' '.PageLink2('listbounces&id='.$row["id"],$GLOBALS['I18N']->get('view'))
  $ls->addColumn($element,
    $GLOBALS['I18N']->get('Public'),sprintf('<input type="checkbox" name="active[%d]" value="1" %s />',$row["id"],
  $row["active"] ? 'checked="checked"' : ''));
  $owner = adminName($row['owner']);
  if (!empty($owner)) {
    $ls->addColumn($element,
      $GLOBALS['I18N']->get('Owner'),$GLOBALS['require_login'] ? adminName($row['owner']):$GLOBALS['I18N']->get('n/a'));
  }
  if (trim($desc) != '') {
    $ls->addRow($element,
      $GLOBALS['I18N']->get('Description'),$desc);
  }
  $ls->addColumn($element,
    $GLOBALS['I18N']->get('Order'),
    sprintf('<input type="text" name="listorder[%d]" value="%d" size="3" class="listorder" />',$row['id'],$row['listorder']));

  
  $delete_url = sprintf('<a href="javascript:deleteRec2(\'%s\',\'%s\');" title="%s">%s</a>',$GLOBALS['I18N']->get('Are you sure you want to delete this list?'),PageURL2("list&delete=".$row["id"]),$GLOBALS['I18N']->get('delete this list'),$GLOBALS['I18N']->get('del'));

  $ls->addColumn($element,$GLOBALS['I18N']->get('del'),$delete_url);
  $ls->addColumn($element,$GLOBALS['I18N']->get('send'),PageLinkButton('send&new=1&list='.$row['id'],$GLOBALS['I18N']->get('new campaign'),'','',$GLOBALS['I18N']->get('start a new campaign targetting this list')));


  $some = 1;
}
$ls->addSubmitButton('update',$GLOBALS['I18N']->get('Save Changes'));

if (!$some) {
  echo $GLOBALS['I18N']->get('No lists, use Add List to add one');
}  else {
  print $ls->display();
}
/*
  echo '<table class="x" border="0">
      <tr>
        <td>'.$GLOBALS['I18N']->get('No').'</td>
        <td>'.$GLOBALS['I18N']->get('Name').'</td>
        <td>'.$GLOBALS['I18N']->get('Order').'</td>
        <td>'.$GLOBALS['I18N']->get('Functions').'</td>
        <td>'.$GLOBALS['I18N']->get('Active').'</td>
        <td>'.$GLOBALS['I18N']->get('Owner').'</td>
        <td>'.$html . '
    <tr>
        <td colspan="6" align="center">
        <input type="submit" name="update" value="'.$GLOBALS['I18N']->get('Save Changes').'"></td>
      </tr>
    </table>';
}
*/
?>

</form>
<p>
<?php
if ($canaddlist) {
  print PageLinkButton('editlist',$GLOBALS['I18N']->get('Add a list'));
}
?>
</p>
