<?php
require_once dirname(__FILE__).'/accesscheck.php';

# for now redirect to export

# it would be good to rewrite this to export the user search selection
# in the users page instead.

Header("Location: ./?page=export");
exit;

include dirname(__FILE__) . "/structure.php";

if (isset($_GET['list'])) {
  $list = sprintf('%d',$_GET['list']);
} else {
  $list = 0;
}

$access = accessLevel('export');
switch ($access) {
  case 'owner':
    $querytables = $GLOBALS['tables']['list'].' list ,'.$GLOBALS['tables']['user'].' user ,'.$GLOBALS['tables']['listuser'].' listuser ';
    $subselect = ' and listuser.listid = list.id and listuser.userid = user.id and list.owner = ' . $_SESSION['logindetails']['id'];
    $listselect_where = ' where owner = ' . $_SESSION['logindetails']['id'];
    $listselect_and = ' and owner = ' . $_SESSION['logindetails']['id'];
    break;
  case 'all':
    if ($list) {
      $querytables = $GLOBALS['tables']['user'].' user'.', '.$GLOBALS['tables']['listuser'].' listuser';
      $subselect = ' and listuser.userid = user.id ';
    } else {
      $querytables = $GLOBALS['tables']['user'].' user';
      $subselect = '';
    }
    $listselect_where = '';
    $listselect_and = '';
    break;
  case 'none':
  default:
    $querytables = $GLOBALS['tables']['user'].' user';
    $subselect = ' and user.id = 0';
    $listselect_where = ' where owner = 0';
    $listselect_and = ' and owner = 0';
    break;
}

if (!$list)
  $filename = $GLOBALS['I18N']->get('PHPList Users').' '.date("Y-M-d").'.csv';
else {
  $filename = $GLOBALS['I18N']->get('PHPList Users').' '.$GLOBALS['I18N']->get('on').' '.listName($list).' '.date("Y-M-d").'.csv';
}
ob_end_clean();
header("Content-type: ".$GLOBALS["export_mimetype"]);
header("Content-disposition:  attachment; filename=\"$filename\"");

$cols = array();
while (list ($key,$val) = each ($DBstruct["user"])) {
  if (!ereg("sys:",$val[1])) {
    print $val[1]."\t";
     array_push($cols,$key);
  } elseif (ereg("sysexp:(.*)",$val[1],$regs)) {
    print $regs[1]."\t";
     array_push($cols,$key);
  }
}
$res = Sql_Query("select id,name,tablename,type from {$tables['attribute']}");
$attributes = array();
while ($row = Sql_fetch_array($res)) {
  print trim($row["name"]) ."\t";

 array_push($attributes,array("id"=>$row["id"],"table"=>$row["tablename"],"type"
=>$row["type"]));
}
print $GLOBALS['I18N']->get('List Membership')."\n";

if ($list)
 $result = Sql_query("SELECT {$tables['user']}.* FROM
 {$tables['user']},{$tables['listuser']} where {$tables['user']}.id =
 {$tables['listuser']}.userid and {$tables['listuser']}.listid = $list");
else
  $result = Sql_query("SELECT * FROM {$tables['user']}");

while ($user = Sql_fetch_array($result)) {
  set_time_limit(500);
  reset($cols);
  while (list ($key,$val) = each ($cols))
    print strtr($user[$val],"\t",",")."\t";
  reset($attributes);
  while (list($key,$val) = each ($attributes)) {
    print strtr(UserAttributeValue($user["id"],$val["id"]),"\t",",")."\t";
  }
  $lists = Sql_query("SELECT listid,name FROM
    {$tables['listuser']},{$tables['list']} where userid = ".$user["id"]." and
    {$tables['listuser']}.listid = {$tables['list']}.id");
  if (!Sql_Affected_rows($lists))
    print $GLOBALS['I18N']->get('No Lists');
  while ($list = Sql_fetch_array($lists)) {
    print $list["name"]." ";
  }
  print "\n";
}
exit;
