<?php
require_once dirname(__FILE__).'/accesscheck.php';

# export users from PHPlist

include dirname(__FILE__) .'/date.php';

function quoteEnclosed($value,$col_delim = "\t",$row_delim = "\n") {
  $enclose = 0;
  if (strpos($value,'"') !== false) {
    $value = str_replace('"','""',$value);
    $enclose = 1;
  }
  if (strpos($value,$col_delim) !== false) {
    $enclose = 1;
  }
  if (strpos($value,$row_delim) !== false) {
    $enclose = 1;
  }
  if ($enclose) {
    $value = '"'.$value .'"';
  }
  return $value;
}

$fromdate = '';
$todate = '';
$from = new date("from");
$to = new date("to");
if (isset($_REQUEST['list'])) {
  if (isset($_GET['list'])) {
    $list = sprintf('%d',$_GET['list']);
  } elseif (isset($_POST['column']) && $_POST['column'] == 'listentered') {
    $list = sprintf('%d',$_POST['list']);
  } else {
    $list = 0;
  }
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

require dirname(__FILE__). '/structure.php';
if (isset($_POST['processexport'])) {
  if (!verifyToken()) { ## csrf check
    print Error($GLOBALS['I18N']->get('Invalid security token. Please reload the page and try again.'));
    return;
  }
  if ($_POST['column'] == 'nodate') {
    ## fetch dates as min and max from user table
    if ($list) {
      $dates = Sql_Fetch_Row_Query(sprintf('select min(date(user.modified)),max(date(user.modified)) from %s where listid = %d %s',$querytables,$list,$subselect));
    } else {
      $dates = Sql_Fetch_Row_Query(sprintf('select min(date(user.modified)),max(date(user.modified)) from %s ',$querytables));
    }

    $fromdate = $dates[0];
    $todate = $dates[1];
  } else {
    $fromdate = $from->getDate("from");
    $todate =  $to->getDate("to");
  }
  if ($list) {
    $filename = sprintf($GLOBALS['I18N']->get('phpList Export on %s from %s to %s (%s).csv'),ListName($list),$fromdate,$todate,date("Y-M-d"));
  } else {
    $filename = sprintf($GLOBALS['I18N']->get('phpList Export from %s to %s (%s).csv'),$fromdate,$todate,date("Y-M-d"));
  }
  ob_end_clean();
  $filename = trim(strip_tags($filename));

 # header("Content-type: text/plain");
  header("Content-type: ".$GLOBALS["export_mimetype"].'; charset=UTF-8');
  header("Content-disposition:  attachment; filename=\"$filename\"");
  $col_delim = "\t";
  if (EXPORT_EXCEL) {
    $col_delim = ",";
  }
  $row_delim = "\n";

  if (is_array($_POST['cols'])) {
    while (list ($key,$val) = each ($DBstruct["user"])) {
      if (in_array($key,$_POST['cols'])) {
        if (strpos($val[1],"sys") === false) {
          print $val[1].$col_delim;
        } elseif (preg_match("/sysexp:(.*)/",$val[1],$regs)) {
          if ($regs[1] == 'ID') { # avoid freak Excel bug: http://mantis.phplist.com/view.php?id=15526
            $regs[1] = 'id';
          }
          print $regs[1].$col_delim;
        }
      }
    }
   }
  $attributes = array();
  if (is_array($_POST['attrs'])) {
    $res = Sql_Query("select id,name,type from {$tables['attribute']}");
    while ($row = Sql_fetch_array($res)) {
      if (in_array($row["id"],$_POST['attrs'])) {
        print trim(stripslashes($row["name"])) .$col_delim;
        array_push($attributes,array("id"=>$row["id"],"type"=>$row["type"]));
      }
    }
  }
  $exporthistory = 0;
  if ($_POST['column'] == 'listentered') {
    $column = 'listuser.entered';
  } elseif ($_POST['column'] == 'historyentry') {
    $column = 'user_history.date';
    $querytables .= ', '.$GLOBALS['tables']['user_history'].' user_history ';
    $subselect .= ' and user_history.userid = user.id and user_history.summary = "Profile edit"';
    print 'IP' .$col_delim;
    print 'Change Summary' .$col_delim;
    print 'Change Detail' .$col_delim;
    $exporthistory = 1;
  } else {
    switch ($_POST['column']) {
      case 'entered':$column = 'user.entered';break;
      default: $column = 'user.modified';break;
    }
  }
  if ($list) {
    $result = Sql_query(sprintf('select * from
      %s where user.id = listuser.userid and listuser.listid = %d and %s >= "%s 00:00:00" and %s  <= "%s 23:59:59" %s
      ',$querytables,$list,$column,$fromdate,$column,$todate,$subselect)
      );
  } else {
    $result = Sql_query(sprintf('
      select * from %s where %s >= "%s 00:00:00" and %s  <= "%s 23:59:59" %s',
      $querytables,$column,$fromdate,$column,$todate,$subselect));
  }

  print $GLOBALS['I18N']->get('List Membership').$row_delim;

# print Sql_Affected_Rows()." users apply<br/>";
#return;
  while ($user = Sql_fetch_array($result)) {
    ## re-verify the blacklist status
    if (empty($user['blacklisted']) && isBlackListed($user['email'])) {
      $user['blacklisted'] = 1;
      Sql_Query(sprintf('update %s set blacklisted = 1 where email = "%s"',$GLOBALS['tables']["user"],$user['email']));
    }
    
    set_time_limit(500);
    reset($_POST['cols']);
    while (list ($key,$val) = each ($_POST['cols']))
      print strtr($user[$val],$col_delim,",").$col_delim;
    reset($attributes);
    while (list($key,$val) = each ($attributes)) {
      $value = UserAttributeValue($user["id"],$val["id"]);
      print quoteEnclosed($value,$col_delim,$row_delim).$col_delim;
    }
    if ($exporthistory) {
      print quoteEnclosed($user['ip'],$col_delim,$row_delim).$col_delim;
      print quoteEnclosed($user['summary'],$col_delim,$row_delim).$col_delim;
      print quoteEnclosed($user['detail'],$col_delim,$row_delim).$col_delim;
    }

    $lists = Sql_query("select listid,name from
      {$tables['listuser']},{$tables['list']} where userid = ".$user["id"]." and
      {$tables['listuser']}.listid = {$tables['list']}.id $listselect_and");
    if (!Sql_Affected_rows($lists))
      print "No Lists";
    while ($list = Sql_fetch_array($lists)) {
      print stripslashes($list["name"])." ";
    }
    print $row_delim;
  }
  exit;
}

if ($list) {
  print sprintf($GLOBALS['I18N']->get('Export subscribers on %s'),ListName($list));
}

print formStart();
?>

<?php echo $GLOBALS['I18N']->get('What date needs to be used:');?><br/>
<input type="radio" name="column" value="nodate" /> <?php echo $GLOBALS['I18N']->get('Any date');?><br/>
<input type="radio" name="column" value="entered" checked="checked" /> <?php echo $GLOBALS['I18N']->get('When they signed up');?><br/>
<input type="radio" name="column" value="modified" /> <?php echo $GLOBALS['I18N']->get('When the record was changed');?><br/>
<input type="radio" name="column" value="historyentry" /> <?php echo $GLOBALS['I18N']->get('Based on changelog');?><br/>
<input type="radio" name="column" value="listentered" /> <?php echo $GLOBALS['I18N']->get('When they subscribed to');?> 
<select name="list">
<?php
$req = Sql_Query(sprintf('select * from %s %s',$GLOBALS['tables']['list'],$listselect_where));
while ($row = Sql_Fetch_Array($req)) {
  printf ('<option value=%d>%s</option>',$row['id'],$row['name']);
}
?>
</select>
<div id="exportdates">
<?php echo $GLOBALS['I18N']->get('Date From:');?> <?php echo $from->showInput("","",$fromdate);?>
<?php echo $GLOBALS['I18N']->get('Date To:');?>  <?php echo $to->showInput("","",$todate);?>
</div>


<?php echo $GLOBALS['I18N']->get('Select the columns to include in the export');?>

<?php
  $cols = array();
  while (list ($key,$val) = each ($DBstruct["user"])) {
    if (strpos($val[1],"sys") === false) {
      printf ("\n".'<br/><input type="checkbox" name="cols[]" value="%s" checked="checked" /> %s ',$key,$val[1]);
    } elseif (preg_match("/sysexp:(.*)/",$val[1],$regs)) {
      printf ("\n".'<br/><input type="checkbox" name="cols[]" value="%s" checked="checked" /> %s ',$key,$regs[1]);
    }
  }
  $res = Sql_Query("select id,name,tablename,type from {$tables['attribute']} order by listorder");
  $attributes = array();
  while ($row = Sql_fetch_array($res)) {
    printf ("\n".'<br/><input type="checkbox" name="attrs[]" value="%s" checked="checked" /> %s ',$row["id"],stripslashes($row["name"]));
  }

?>

<p class="submit"><input type="submit" name="processexport" id="processexport" value="<?php echo $GLOBALS['I18N']->get('Export'); ?>"></p></form>

