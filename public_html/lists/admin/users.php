<hr/>

<?php
####################################################################
# This file is a placeholder. Functionality is passed to commonlib #
####################################################################

require_once dirname(__FILE__).'/accesscheck.php';

$columns = array("messages","lists","bounces","blacklist");

include dirname(__FILE__).'/commonlib/pages/users.php';
return;

//
//if (!$_SESSION["userlistfilter"]) {
//  $_SESSION["userlistfilter"] = array();
//}
//
//if (isset($_GET["find"])) {
//  if ($_GET["find"] == "NULL") {
//    $_SESSION["userlistfilter"]["find"] = "";
//    $_SESSION["userlistfilter"]["findby"] = "";
//  } else {
//    $_SESSION["userlistfilter"]["find"] = $_GET["find"];
//    $_SESSION["userlistfilter"]["findby"] = $_GET["findby"];
//   }
//}
//$find = $_SESSION["userlistfilter"]["find"];
//$findby = $_SESSION["userlistfilter"]["findby"];
//if (!$findby) {
//  $findby = "email";
//}
//# hmm interesting, if they select a findby but not a find, use the Sql wildcard:
//if ($findby && !$find)
//  $find = '%';
//
//$system_findby = array("email","foreignkey");
//if ($findby && $find && !in_array($findby,$system_findby) ) {
//  $find_url = '&find='.urlencode($find)."&findby=".urlencode($findby);
//  $findatt = Sql_Fetch_Array_Query("select id,tablename,type,name from {$tables["attribute"]} where id = $findby");
//  switch ($findatt["type"]) {
//    case "textline":
//    case "hidden":
//      $findtables = ','.$tables["user_attribute"];
//      $findbyselect = sprintf(' %s.userid = %s.id and
//        %s.attributeid = %d and %s.value like "%%%s%%"',
//        $tables["user_attribute"],
//        $tables["user"],
//        $tables["user_attribute"],
//        $findby,
//        $tables["user_attribute"],
//        $find
//      );
//      $findfield = $tables["user_attribute"].".value as display, ".$tables["user"].".bouncecount";
//      $findfieldname = $findatt["name"];
//      break;
//    case "select":
//    case "radio":
//      $findtables = ','.$tables["user_attribute"].','.$table_prefix.'listattr_'.$findatt["tablename"];
//      $findbyselect = sprintf(' %s.userid = %s.id and
//        %s.attributeid = %d and %s.value = %s.id and
//        %s.name like "%%%s%%"',
//        $tables["user_attribute"],
//        $tables["user"],
//        $tables["user_attribute"],
//        $findby,
//        $tables["user_attribute"],
//        $table_prefix.'listattr_'.$findatt["tablename"],
//        $table_prefix.'listattr_'.$findatt["tablename"],
//        $find);
//      $findfield = $table_prefix.'listattr_'.$findatt["tablename"].".name as display, ".$tables["user"].".bouncecount";
//      $findfieldname = $findatt["name"];
//      break;
//  }
//} else {
//  $findtables = '';
//  $findbyselect = sprintf(' %s like "%%%s%%"',$findby,$find);;
//  $findfield = $tables["user"].".bouncecount,".$tables["user"].".rssfrequency,".$tables["user"].".foreignkey";
//  $findfieldname = "Email";
//  $find_url = '&find='.urlencode($find);
//}
//
//if ($require_login && !isSuperUser()) {
//  $access = accessLevel("users");
//  switch ($access) {
//    case "owner":
//      $table_list = $tables["user"].','.$tables["listuser"].','.$tables["list"].$findtables;
//      $subselect = "{$tables["user"]}.id = {$tables["listuser"]}.userid and {$tables["listuser"]}.listid = {$tables["list"]}.id and {$tables["list"]}.owner = ".$_SESSION["logindetails"]["id"];
//      if ($find) {
//        $listquery = "select {$tables["user"]}.email,{$tables["user"]}.id,$findfield,confirmed from ".$table_list." where $subselect and $findbyselect";
//        $count = Sql_query("SELECT count({$tables["user"]}.id) FROM ".$table_list ." where $subselect and $findbyselect");
//        $unconfirmedcount = Sql_query("SELECT count({$tables["user"]}.id) FROM ".$table_list ." where $subselect and !confirmed and $findbyselect");
//      } else {
//        $listquery = "select {$tables["user"]}.email,{$tables["user"]}.id,$findfield,confirmed from ".$table_list." where $subselect";
//        $count = Sql_query("SELECT count({$tables["user"]}.id) FROM ".$table_list ." where $subselect");
//        $unconfirmedcount = Sql_query("SELECT count({$tables["user"]}.id) FROM ".$table_list ." where !confirmed and $subselect");
//      }
//      if ($_GET["unconfirmed"])
//        $listquery .= ' and !confirmed ';
//      if ($_GET["blacklisted"])
//        $listquery .= ' and blacklisted ';
//      break;
//    case "all":
//    case "view":
//      $table_list = $tables["user"].$findtables;
//      if ($find) {
//        $listquery = "select {$tables["user"]}.email,{$tables["user"]}.id,$findfield,{$tables["user"]}.confirmed from ".$table_list." where $findbyselect";
//        $count = Sql_query("SELECT count(*) FROM ".$table_list ." where $findbyselect");
//        $unconfirmedcount = Sql_query("SELECT count(*) FROM ".$table_list ." where !confirmed && $findbyselect");
//        if ($_GET["unconfirmed"])
//          $listquery .= ' and !confirmed ';
//        if ($_GET["blacklisted"])
//          $listquery .= ' and blacklisted ';
//      } else {
//        $listquery = "select {$tables["user"]}.email,{$tables["user"]}.id,$findfield,{$tables["user"]}.confirmed from ".$table_list;
//        $count = Sql_query("SELECT count(*) FROM ".$table_list);
//        $unconfirmedcount = Sql_query("SELECT count(*) FROM ".$table_list." where !confirmed");
//      }
//      $delete_message = '<br />Delete will delete user and all listmemberships<br />';
//      break;
//    case "none":
//    default:
//      print Error("Your privileges for this page are insufficient");
//      return;
//  }
//  $delete_message = '<br />Delete will delete user from the list<br />';
//} else {
//  $table_list = $tables["user"].$findtables;
//  if ($find) {
//    $listquery = "select {$tables["user"]}.email,{$tables["user"]}.id,$findfield,{$tables["user"]}.confirmed from ".$table_list." where $findbyselect";
//    $count = Sql_query("SELECT count(*) FROM ".$table_list ." where $findbyselect");
//    $unconfirmedcount = Sql_query("SELECT count(*) FROM ".$table_list ." where !confirmed and $findbyselect");
//    if ($_GET["unconfirmed"])
//      $listquery .= ' and !confirmed ';
//    if ($_GET["blacklisted"])
//      $listquery .= ' and blacklisted ';
//  } else {
//    $listquery = "select {$tables["user"]}.email,{$tables["user"]}.id,$findfield,{$tables["user"]}.confirmed from ".$table_list;
//    $count = Sql_query("SELECT count(*) FROM ".$table_list);
//    $unconfirmedcount = Sql_query("SELECT count(*) FROM ".$table_list." where !confirmed");
//    if ($_GET["unconfirmed"])
//      $listquery .= ' where !confirmed';
//    if ($_GET["blacklisted"])
//      $listquery .= ' and blacklisted ';
//  }
//  $delete_message = '<br />Delete will delete user and all listmemberships<br />';
//}
//
//$totalres = Sql_fetch_Row($unconfirmedcount);
//$totalunconfirmed = $totalres[0];
//$totalres = Sql_fetch_Row($count);
//$total = $totalres[0];
//
//if (isset($delete)) {
//  # delete the index in delete
//  print "deleting $delete ..\n";
//  deleteUser($delete);
//
//  print "..Done<br/><hr/><br/>\n";
//  Redirect("users&start=$start");
//}
//ob_end_flush();
//
//if (isset($add)) {
//  if (isset($new)) {
//    $query = "insert into ".$tables["user"]." (email,entered) values(\"$new\",current_timestamp)";
//    $result = Sql_query($query);
//    $userid = Sql_Insert_Id($tables['user'], 'id');
//    $query = "insert into ".$tables["listuser"]." (userid,listid,entered) values($userid,$id,current_timestamp)";
//    $result = Sql_query($query);
//  }
//  echo "<br/>User added<br/>";
//}
//
//print "$total Users";
//print $find ? " found": "";
//if ($find && !$findby && !$total) { # a search for an email has been done and not found
//  print "<hr/><h4>Add this user</h4>";
//  $req = Sql_Query(sprintf('select * from %s where active',$tables["subscribepage"]));
//  if (Sql_Affected_Rows()) {
//    print "Click on a link to use the corresponding public subscribe page to add this user:";
//    while ($row = Sql_Fetch_Array($req)) {
//      printf('<p class="x"><a href="%s&amp;id=%d&email=%s">%s</a></p>',getConfig("subscribeurl"),$row["id"],$find,$row["title"]);
//     }
//  } else {
//    print "Click this link to use the public subscribe page to add this user:";
//    printf('<p class="x"><a href="%s&amp;email=%s">%s</a></p>',getConfig("subscribeurl"),$find,$GLOBALS["strSubscribeTitle"]);
//  }
//  print '<hr/>';
//}
//
//print "<br/>Users marked red are unconfirmed ($totalunconfirmed)<br/>";
//
//$url = getenv("REQUEST_URI");
//if ($_GET["unconfirmed"]) {
//  $unc = "checked";
//} else {
//  $unc = "unchecked";
//}
//if ($_GET["blacklisted"]) {
//  $bll = "checked";
//} else {
//  $bll = "unchecked";
//}
//
//print '<table class="x"><tr><td valign=top>';
//printf ('<form method="get" name="listcontrol">
//  <input type="hidden" name="page" value="users">
//  <input type="hidden" name="start" value="%s">
//  <input type="hidden" name="find" value="%s">
//  <input type="hidden" name="findby" value="%s"><br/>Show only unconfirmed users:
//  <input type="checkbox" name="unconfirmed" value="on" %s><br/>Show only blacklisted users:
//  <input type="checkbox" name="blacklisted" value="on" %s>',
//  $start,$find,$findby,$unc,$bll);
//print '</td><td valign=top>';
//foreach (array("email","bouncecount","entered","modified","foreignkey") as $item) {
//  $select .= sprintf('<option value="%s" %s>%s</option>',
//    $item,$item == $sortby ? 'selected="selected"':'',$item);
//}
//
//printf ('
//  <br/>Sort by:
//  <select name="sortby" onchange="document.listcontrol.submit();">
//  <option value="0">-- default</option>
//  %s
//  </select>
//  D: <input type=radio name="sortorder" value="desc" %s>
//  A: <input type=radio name="sortorder" value="asc" %s>
//  <p class="submit"><input type="submit" name="change" value="Go"></p>
//  ',
//  $select,$sortorder == "desc"? "checked":"",$sortorder == "asc"? "checked":"");
//print '</td></tr></table>';
//
//if ($sortby) {
//  $order = ' order by '.$sortby;
//  if ($sortorder == "asc") {
//    $order .= ' asc';
//  } else {
//    $order .= ' desc';
//  }
//  $find_url .= "&sortby=$sortby&sortorder=$sortorder&unconfirmed=$unconfirmed";
//}
//
//if ($total > MAX_USER_PP) {
//  if (isset($start) && $start) {
//    $listing = "Listing user $start to " . ($start + MAX_USER_PP);
//    $limit = "limit $start,".MAX_USER_PP;
//  } else {
//    $listing = "Listing user 1 to 50";
//    $limit = "limit 0,50";
//    $start = 0;
//  }
//  if ($_GET["unconfirmed"])
//     $find_url .= "&unconfirmed=".$_GET["unconfirmed"];
//  printf ('<table class="x" border=1><tr><td colspan=4 align=center>%s</td></tr><tr><td>%s</td><td>%s</td><td>
//          %s</td><td>%s</td></tr></table><p class="x"><hr/>',
//          $listing,
//          PageLink2("users","&lt;&lt;","start=0".$find_url),
//          PageLink2("users","&lt;",sprintf('start=%d',max(0,$start-MAX_USER_PP)).$find_url),
//          PageLink2("users","&gt;",sprintf('start=%d',min($total,$start+MAX_USER_PP)).$find_url),
//          PageLink2("users","&gt;&gt;",sprintf('start=%d',$total-MAX_USER_PP).$find_url));
//  $result = Sql_query("$listquery $order $limit");
//} else {
//  $result = Sql_Query("$listquery $order");
//}
//?>
//<table class="x" border=0>
//<tr><td colspan=4><input type="hidden" name=id value="<?php echo $listid?>">
//Find a user: <input type=text name=find value="<?php echo $find != '%' ? $find : ""?>" size=30>
//<select name="findby"><option value="email" <?php echo $findby == "email"? 'selected="selected"':''?>>Email</option>
//<option value="foreignkey" <?php echo $findby == "foreignkey"? 'selected="selected"':''?>>Foreign Key</option>
//<?php
//  $att_req = Sql_Query("select id,name from ".$tables["attribute"]." where type = \"hidden\" or type = \"textline\" or type = \"select\"");
//  while ($row = Sql_Fetch_Array($att_req)) {
//    printf('<option value="%d" %s>%s</option>',$row["id"],$row["id"] == $findby ? 'selected="selected"':'',substr($row["name"],0,20));
//  }
//?></select><p class="submit"><input type="submit" value="Go"></p>&nbsp;&nbsp;<a href="./?page=users&find=NULL">reset</a>
//</form></td></tr>
//<tr><td colspan=4>
//<?php
//#if (($require_login && isSuperUser()) || !$require_login)
//  print PageLink2("dlusers","Download all users as CSV file","nocache=".uniqid(""));
//?></td></tr>
//</table>
//
//<?php
//
//$some = 0;
//$ls = new WebblerListing("users");
//while ($user = Sql_fetch_array($result)) {
//  $some = 1;
//  $lists = Sql_query("SELECT count(*) FROM ".$tables["listuser"].",".$tables["list"]." where userid = ".$user["id"]." and ".$tables["listuser"].".listid = ".$tables["list"].".id");
//  $membership = Sql_fetch_row($lists);
//  $msgs = Sql_query("SELECT count(*) FROM ".$tables["usermessage"]." where userid = ".$user["id"]);
//  $nummsgs = Sql_fetch_row($msgs);
//  $onblacklist = isBlackListed($user["email"]);
//  $ls->addElement($user["email"],PageURL2("user&start=$start&amp;id=".$user["id"].$find_url));
//  $ls->addColumn($user["email"],"confirmed",
//    $user["confirmed"]?$GLOBALS["img_tick"]:$GLOBALS["img_cross"]);
//  $ls->addColumn($user["email"],"bl l",
//    $onblacklist?$GLOBALS["img_tick"]:$GLOBALS["img_cross"]);
//  $ls->addColumn($user["email"],"del",sprintf("<a href=\"javascript:deleteRec('%s');\">del</a>",
//     PageURL2("users","delete","start=$start&amp;delete=".$user["id"])));
//   $ls->addColumn($user["email"],"key",$user["foreignkey"]);
//   $ls->addColumn($user["email"],"&nbsp;",$user["display"]);
//  $ls->addColumn($user["email"],"lists",$membership[0]);
//  $ls->addColumn($user["email"],"msgs",$nummsgs[0]);
//  if (ENABLE_rss) {
//    $rss = Sql_query("SELECT count(*) FROM ".$tables["rssitem_user"]." where userid = ".$user["id"]);
//    $nummsgs = Sql_fetch_row($rss);
//    $ls->addColumn($user["email"],"rss",$nummsgs[0]);
//    if ($user["rssfrequency"])
//      $ls->addColumn($user["email"],"rss freq",$user["rssfrequency"]);
//    $last = Sql_Fetch_Row_Query("select last from {$tables["user_rss"]} where userid = ".$user["id"]);
//    if ($last[0])
//      $ls->addColumn($user["email"],"last sent",$last[0]);
//  }
//
//  $ls->addColumn($user["email"],"bncs",$user["bouncecount"]);
//}
//print $ls->display();
//if (!$some) {
//  print "<p class="x">No users apply</p>";
//}
?>


