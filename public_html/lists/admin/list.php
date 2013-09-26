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
    $active = $active || listUsedInSubscribePage($key);
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
    $result = Sql_query('delete from '.$tables['listuser']." where listid = $delete");
    $result = Sql_query('delete from '.$tables['listmessage']." where listid = $delete");
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
$req = Sql_Query(sprintf('select distinct category from %s where category != "" %s ',$tables['list'],$subselect_and));
while ($row = Sql_Fetch_Row($req)) {
  array_push($aListCategories,$row[0]);
}
array_push($aListCategories,s('Uncategorised')); 

if (sizeof($aListCategories)) {
  if (isset($_GET['tab']) && in_array($_GET['tab'],$aListCategories)) {
    $current = $_GET['tab'];
  } elseif (isset($_SESSION['last_list_category']) && in_array($_SESSION['last_list_category'],$aListCategories)) {
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

print '<p class="total">'.$total .' '. $GLOBALS['I18N']->get('Lists').'</p>';
$limit = '';

$query
= ' select *'
. ' from ' . $tables['list']
. $subselect
. ' order by listorder '.$limit;

$result = Sql_query($query);
$numlists = Sql_Affected_Rows($result);

$ls = new WebblerListing(s('Lists'));

if ($numlists > 15) {
  Info(s('You seem to have quite a lot of lists, do you want to organise them in categories? ').' '.PageLinkButton('catlists',$GLOBALS['I18N']->get('Great idea!')));

  /* @@TODO add paging when there are loads of lists, because otherwise the page is very slow
  $limit = ' limit 50';
  $query
  = ' select *'
  . ' from ' . $tables['list']
  . $subselect
  . ' order by listorder '.$limit;
  $result = Sql_query($query);
  */

}

while ($row = Sql_fetch_array($result)) {
  
  ## we only consider confirmed and not blacklisted subscribers members of a list
  ## we assume "confirmed" to be 1 or 0, so that the sum gives the total confirmed
  ## could be incorrect, as 1000 is also "true" but will be ok (saves a few queries)
  
  ## same with blacklisted, but we're disregarding that for now, because blacklisted subscribers should not 
  ## be on the list at all. 
  ## @@TODO increase accuracy, without adding loads of queries.
  $query
  = ' select count(u.id) as total,'
  . ' sum(u.confirmed) as confirmed, '
  . ' sum(u.blacklisted) as blacklisted '
  . ' from ' . $tables['listuser']
  . ' lu, '.$tables['user'].' u where u.id = lu.userid and listid = ? ';
  
  $req = Sql_Query_Params($query, array($row["id"]));
  $membercount = Sql_Fetch_Assoc($req);
  
  $members = $membercount['confirmed'];
  $unconfirmedMembers = (int)($membercount['total'] - $members);
  $desc = stripslashes($row['description']);
  if ($unconfirmedMembers > 0) {
    $membersDisplay = '<span class="memberCount">'.$members.'</span> <span class="unconfirmedCount">('.$unconfirmedMembers. ')</span>';
  } else {
    $membersDisplay = '<span class="memberCount">'.$members.'</span>';
  }
 
  //## allow plugins to add columns
  // @@@ TODO review this
  //foreach ($GLOBALS['plugins'] as $plugin) {
    //$desc = $plugin->displayLists($row) . $desc;
  //}

  $element = '<!-- '.$row['id'].'-->'.stripslashes($row['name']);
  $ls->addElement($element);
  $ls->setClass($element,'rows row1');
  $ls->addColumn($element,
    $GLOBALS['I18N']->get('Members'),'<div style="display:inline-block;text-align:right;width:50%;float:left;">'.$membersDisplay. '</div><span class="view" style="text-align:left;display:inline-block;float:right;width:48%;"><a class="button " href="./?page=members&id='.$row["id"].'" title="'.$GLOBALS['I18N']->get('View Members').'">'.$GLOBALS['I18N']->get('View Members').'</a></span>');
    
  $ls->addColumn($element,
    $GLOBALS['I18N']->get('Public'),sprintf('<input type="checkbox" name="active[%d]" value="1" %s %s />',$row["id"],
  $row["active"] ? 'checked="checked"' : '',listUsedInSubscribePage($row["id"]) ? ' disabled="disabled" ':''));
/*  $owner = adminName($row['owner']);
  if (!empty($owner)) {
    $ls->addColumn($element,
      $GLOBALS['I18N']->get('Owner'),$GLOBALS['require_login'] ? adminName($row['owner']):$GLOBALS['I18N']->get('n/a'));
  }
  if (trim($desc) != '') {
    $ls->addRow($element,
      $GLOBALS['I18N']->get('Description'),$desc);
  }
  */
  $ls->addColumn($element,
    $GLOBALS['I18N']->get('Order'),
    sprintf('<input type="text" name="listorder[%d]" value="%d" size="3" class="listorder" />',$row['id'],$row['listorder']));

  $deletebutton = new ConfirmButton(
     s('Are you sure you want to delete this list?'),
     PageURL2("list&delete=".$row["id"]),
     s('delete this list'));
   
  $ls->addRow($element,'','<span class="edit-list"><a class="button" href="?page=editlist&amp;id='.$row["id"].'" title="'.$GLOBALS['I18N']->get('Edit this list').'"></a></span>'.'<span class="send-list">'.PageLinkButton('send&new=1&list='.$row['id'],$GLOBALS['I18N']->get('send'),'','',$GLOBALS['I18N']->get('start a new campaign targetting this list')).'</span>'.
    '<span class="add_member">'.PageLinkDialogOnly('importsimple&list='.$row["id"],$GLOBALS['I18N']->get('Add Members')).'</span>'.
    '<span class="delete">'.$deletebutton->show().'</span>'
    ,'','','actions nodrag');

  $some = 1;
}
$ls->addSubmitButton('update',$GLOBALS['I18N']->get('Save Changes'));

if (!$some) {
  echo $GLOBALS['I18N']->get('No lists, use Add List to add one');
}  else {
  print $ls->display('','draggable');
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
