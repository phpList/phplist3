<?php
if (!defined('PHPLISTINIT')) exit;

$filterpanel = $countpanel = $paging = '';

if (!isset ($_SESSION["userlistfilter"]) || !$_SESSION["userlistfilter"]) {
  $_SESSION["userlistfilter"] = array ();
}
if (isset ($_GET['sortby'])) {
  $sortby = removeXss($_GET['sortby']);
  ## only allow spaces and word chars
  $sortby = preg_replace('/[^\w ]+/','',$sortby);
} else {
  $sortby = '';
}

if (isset($_GET["delete"])){
   $delete = sprintf("%d", $_GET["delete"]);
}
else $delete = 0;
#print $_GET["delete"].' '.$delete .isSuperUser();exit;

if (isset($_GET["start"])){
   $start = sprintf("%d", $_GET["start"]);
}
else $start = 0;

$searchdone = 1;
if (!empty ($_GET['start'])) {
  $start = sprintf('%d', $_GET['start']);
} else {
  $start = 0;
}
$unconfirmed = !empty ($_GET['unconfirmed']) ? sprintf('%d', $_GET['unconfirmed']) : 0;
$blacklisted = !empty ($_GET['blacklisted']) ? sprintf('%d', $_GET['blacklisted']) : 0;
if (isset ($_GET['sortorder'])) {
  if ($_GET['sortorder'] == 'asc') {
    $sortorder = 'asc';
  } else {
    $sortorder = 'desc';
  }
} else {
  $sortorder = 'desc';
}
if (isset ($_GET['listid'])) {
  $listid = sprintf('%d', $_GET['listid']);
} else {
  $listid = 0;
}
if (isset ($_GET["find"])) {
  if (!isset ($_GET['findby'])) {
    $_GET['findby'] = '';
  }

  if ($_GET["find"] == "NULL") {
    $_SESSION["userlistfilter"]["find"] = "";
    $_SESSION["userlistfilter"]["findby"] = "";
  } else {
    $_SESSION["userlistfilter"]["find"] = removeXss($_GET["find"]);
    $_SESSION["userlistfilter"]["findby"] = removeXss($_GET["findby"]);
  }
} else {
  $_SESSION["userlistfilter"]["find"] = "";
  $_SESSION["userlistfilter"]["findby"] = "";
}

$find = $_SESSION["userlistfilter"]["find"];
$findby = $_SESSION["userlistfilter"]["findby"];
if (!$findby) {
  $findby = "email";
}

# hmm interesting, if they select a findby but not a find, use the Sql wildcard:
if ($findby && !$find)
  # this is very slow, so instead erase the findby.
  #  $find = '%';
  $findby = '';

$system_findby = array (
  "email",
  "foreignkey"
);
if ($findby && $find && !in_array($findby, $system_findby)) {
  $find_url = '&amp;find=' . urlencode($find) . "&amp;findby=" . urlencode($findby);
  $findatt = Sql_Fetch_Array_Query("select id,tablename,type,name from {$tables["attribute"]} where id = $findby");
  switch ($findatt["type"]) {
    case "textline" :
    case "hidden" :
      $findtables = ',' . $tables["user_attribute"];
      $findbyselect = sprintf(' %s.userid = %s.id and
              %s.attributeid = %d and %s.value like "%%%s%%"', $tables["user_attribute"], $tables["user"], $tables["user_attribute"], $findby, $tables["user_attribute"], $find);
      $findfield = $tables["user_attribute"] . ".value as display, " . $tables["user"] . ".bouncecount";
      $findfieldname = $findatt["name"];
      break;
    case "select" :
    case "radio" :
      $findtables = ',' . $tables["user_attribute"] . ',' . $table_prefix . 'listattr_' . $findatt["tablename"];
      $findbyselect = sprintf(' %s.userid = %s.id and
              %s.attributeid = %d and %s.value = %s.id and
              %s.name like "%%%s%%"', $tables["user_attribute"], $tables["user"], $tables["user_attribute"], $findby, $tables["user_attribute"], $table_prefix .
      'listattr_' . $findatt["tablename"], $table_prefix .
      'listattr_' . $findatt["tablename"], $find);
      $findfield = $table_prefix . 'listattr_' . $findatt["tablename"] . ".name as display, " . $tables["user"] . ".bouncecount";
      $findfieldname = $findatt["name"];
      break;
  }
} else {
  $findtables = '';
  $findbyselect = sprintf(' %s like "%%%s%%"', $findby, $find);
  ;
  $findfield = $tables["user"] . ".bouncecount," . $tables["user"] . ".rssfrequency," . $tables["user"] . ".foreignkey";
  $findfieldname = "Email";
  $find_url = '&amp;find=' . urlencode($find);
}

if ($require_login && !isSuperUser()) {
  $access = accessLevel("users");
  switch ($access) {
    case "owner" :
      $table_list = $tables["user"] . ',' . $tables["listuser"] . ',' . $tables["list"] . $findtables;
      $subselect = "{$tables["user"]}.id = {$tables["listuser"]}.userid and {$tables["listuser"]}.listid = {$tables["list"]}.id and {$tables["list"]}.owner = " . $_SESSION["logindetails"]["id"];
      if ($find) {
        $listquery = "select DISTINCT {$tables["user"]}.email,{$tables["user"]}.id,$findfield,confirmed from " . $table_list . " where $subselect and $findbyselect";
        $count = Sql_query("SELECT count({$tables["user"]}.id) FROM " . $table_list . " where $subselect and $findbyselect");
        $unconfirmedcount = Sql_query("SELECT count({$tables["user"]}.id) FROM " . $table_list . " where $subselect and !confirmed and $findbyselect");
      } else {
        $listquery = "SELECT DISTINCT {$tables["user"]}.email,{$tables["user"]}.id,$findfield,confirmed FROM " . $table_list . " WHERE $subselect";
        $count = Sql_query("SELECT count({$tables["user"]}.id) FROM " . $table_list . " WHERE $subselect");
        $unconfirmedcount = Sql_query("SELECT count({$tables["user"]}.id) FROM " . $table_list . " WHERE !confirmed and $subselect");
      }
      if ($unconfirmed)
        $listquery .= ' and !confirmed ';
      if ($blacklisted)
        $listquery .= ' and blacklisted ';
      break;
    case "all" :
    case "view" :
      $table_list = $tables["user"] . $findtables;
      if ($find) {
        $listquery = "select DISTINCT {$tables["user"]}.email,{$tables["user"]}.id,$findfield,{$tables["user"]}.confirmed from " . $table_list . " where $findbyselect";
        $count = Sql_query("SELECT count(*) FROM " . $table_list . " where $findbyselect");
        $unconfirmedcount = Sql_query("SELECT count(*) FROM " . $table_list . " where !confirmed && $findbyselect");
        if ($unconfirmed)
          $listquery .= ' and !confirmed ';
        if ($blacklisted)
          $listquery .= ' and blacklisted ';
      } else {
        $listquery = "select DISTINCT {$tables["user"]}.email,{$tables["user"]}.id,$findfield,{$tables["user"]}.confirmed from " . $table_list;
        $count = Sql_query("SELECT count(*) FROM " . $table_list);
        $unconfirmedcount = Sql_query("SELECT count(*) FROM " . $table_list . " where !confirmed");
        $searchdone = 0;
      }
      $delete_message = '<br />' . $GLOBALS['I18N']->get('Delete will delete user and all listmemberships') . '<br />';
      break;
    case "none" :
    default :
      print Error($GLOBALS['I18N']->get('Your privileges for this page are insufficient'));
      return;
  }
  $delete_message = '<br />' . $GLOBALS['I18N']->get('Delete will delete user from the list') . '<br />';
} else {
  ## is superuser
  $table_list = $tables["user"] . $findtables;
  if ($find) {
    $listquery = "select {$tables["user"]}.email,{$tables["user"]}.id,$findfield,{$tables["user"]}.confirmed from " . $table_list . " where $findbyselect";
    $count = Sql_query("SELECT count(*) FROM " . $table_list . " where $findbyselect");
    $unconfirmedcount = Sql_query("SELECT count(*) FROM " . $table_list . " where !confirmed and $findbyselect");
    if ($unconfirmed)
      $listquery .= ' and !confirmed ';
    if ($blacklisted)
      $listquery .= ' and blacklisted ';
  } else {
    $listquery = "select {$tables["user"]}.email,{$tables["user"]}.id,$findfield,{$tables["user"]}.confirmed from " . $table_list;
    $count = Sql_query("SELECT count(*) FROM " . $table_list);
    $unconfirmedcount = Sql_query("SELECT count(*) FROM " . $table_list . " where !confirmed");

    if ($unconfirmed || $blacklisted) {
      $listquery .= ' where ';
      if ($unconfirmed && $blacklisted) {
        $listquery .= ' !confirmed and blacklisted ';
      }
      elseif ($unconfirmed) {
        $listquery .= ' !confirmed ';
      } else {
        $listquery .= ' blacklisted';
      }
    } else {
      $searchdone = 0;
    }
  }
  $delete_message = '<br />' . $GLOBALS['I18N']->get('Delete will delete user and all listmemberships') . '<br />';
}

$totalres = Sql_fetch_Row($unconfirmedcount);
$totalunconfirmed = $totalres[0];
$totalres = Sql_fetch_Row($count);
$total = $totalres[0];

if (!empty($delete) && isSuperUser()) {
  # delete the index in delete
  $action_result = $GLOBALS['I18N']->get('deleting') . " $delete ..\n";
  deleteUser($delete);

  $action_result .= '..' . $GLOBALS['I18N']->get('Done') . '<br/><hr/>';
  $previous_search = '';
  if (!$find == '') {
    $previous_search = "&start=$start&find=$find&findby=$findby";
  }

  $_SESSION['action_result'] = $action_result;
  Redirect("users$previous_search");
} elseif (!empty($delete)) {
  print ActionResult($GLOBALS['I18N']->get('Sorry, only super users can delete users'));
}

#ob_end_flush();

if (isset ($add)) {
  if (isset ($new)) {
    $query = "insert into " . $tables["user"] . " (email,entered) values(\"$new\",now())";
    $result = Sql_query($query);
    $userid = Sql_insert_id();
    $query = "insert into " . $tables["listuser"] . " (userid,listid,entered) values($userid,$id,now())";
    $result = Sql_query($query);
  }
  echo ActionResult($GLOBALS['I18N']->get('User added'));
}

$countpanel .= sprintf($GLOBALS['I18N']->get('%s users in total'), $total);
/*
if ($find && !$findby && !$total) { # a search for an email has been done and not found
  print "<hr/><h4>" . $GLOBALS['I18N']->get('Add this user') . "</h4>";
  $req = Sql_Query(sprintf('select * from %s where active', $tables["subscribepage"]));
  if (Sql_Affected_Rows()) {
    print $GLOBALS['I18N']->get('Click on a link to use the corresponding public subscribe page to add this user:');
    while ($row = Sql_Fetch_Array($req)) {
      printf('<p><a href="%s&amp;id=%d&amp;email=%s">%s</a></p>', getConfig("subscribeurl"), $row["id"], $find, $row["title"]);
    }
  } else {
    print $GLOBALS['I18N']->get('Click this link to use the public subscribe page to add this user:');
    printf('<p><a href="%s&amp;email=%s">%s</a></p>', getConfig("subscribeurl"), $find, $GLOBALS["strSubscribeTitle"]);
  }
  print '<hr/>';
}
*/

$countpanel .= "<br/>" . $GLOBALS['I18N']->get('Users marked <span class="highlight">red</span> are unconfirmed') . " ($totalunconfirmed)<br/>";

$url = getenv("REQUEST_URI");
if ($unconfirmed) {
  $unc = 'checked="checked"';
} else {
  $unc = "";
}
if ($blacklisted) {
  $bll = 'checked="checked"';
} else {
  $bll = "";
}
if (!isset ($start)) {
  $start = 0;
}

$filterpanel .= '<div class="filter">';
$filterpanel .= sprintf('<form method="get" name="listcontrol" action="">
  <input type="hidden" name="page" value="users" />
  <input type="hidden" name="start" value="%d" />
  <input type="hidden" name="find" value="%s" />
  <input type="hidden" name="findby" value="%s" />
  <label for="unconfirmed">%s:<input type="checkbox" name="unconfirmed" value="1" %s /></label>
  <label for="blacklisted">%s:<input type="checkbox" name="blacklisted" value="1" %s /></label>', 
       $start, 
       htmlspecialchars(stripslashes($find)), 
       htmlspecialchars(stripslashes($findby)), 
       $GLOBALS['I18N']->get('Show only unconfirmed users'), 
       $unc, 
       $GLOBALS['I18N']->get('Show only blacklisted users'), 
       $bll);
#print '</td><td valign="top">';
$select = '';
foreach (array (
    "email",
    "bouncecount",
    "entered",
    "modified",
    "foreignkey"
  ) as $item) {
  $select .= sprintf('     <option value="%s" %s>%s</option>', $item, $item == $sortby ? 'selected="selected"' : '', $GLOBALS['I18N']->get($item));
}

$filterpanel .= sprintf('
  <label for="sortby">%s: <select name="sortby" onchange="document.listcontrol.submit();">
  <option value="0">-- default</option>
  %s
  </select></label>
  <label for="sortdesc">%s: <input type="radio" name="sortorder" id="sortdesc" value="desc" %s onchange="document.listcontrol.submit();" /></label>
  <label for="sortasc">%s: <input type="radio" name="sortorder" id="sortasc" value="asc" %s onchange="document.listcontrol.submit();" /></label>
  <input class="submit" type="submit" name="change" value="%s" />
  ', 
       $GLOBALS['I18N']->get('Sort by'), $select, 
       $GLOBALS['I18N']->get('desc'), $sortorder == "desc" ? 'checked="checked"' : '', 
       $GLOBALS['I18N']->get('asc'), $sortorder == "asc" ? 'checked="checked"' : '', 
       $GLOBALS['I18N']->get('Go'));
$filterpanel .= '</div>';

$order = '';
if ($sortby) {
  $order = ' order by ' .$tables["user"].'.'. $sortby;
  if ($sortorder == "asc") {
    $order .= ' asc';
  } else {
    $order .= ' desc';
  }
}
$find_url .= "&amp;sortby=$sortby&amp;sortorder=$sortorder&amp;unconfirmed=$unconfirmed&amp;blacklisted=$blacklisted";

$listing = '';
$dolist = 1;
if ($total > MAX_USER_PP) {
  if (isset ($start) && $start) {
    $listing = sprintf($GLOBALS['I18N']->get('Listing user %d to %d'), $start, $start +MAX_USER_PP);
    $limit = "limit $start," . MAX_USER_PP;
  } else {
    if ($total < USERSPAGE_MAX || $searchdone) {
      $listing = sprintf($GLOBALS['I18N']->get('Listing user %d to %d'), 1, 50);
      $limit = "limit 0,50";
      $start = 0;
      $dolist = 1;
    } else {
      $dolist = 0;
    }
  }
  #  if ($_GET["unconfirmed"])
  #     $find_url .= "&unconfirmed=".$_GET["unconfirmed"];
  if ($dolist) {
/*
    printf('<table class="usersListing" border="1">
<tr><td colspan="4" align="center">%s</td></tr>
<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>
</table></p><hr/>', $listing, 
           PageLink2("users", "&lt;&lt;", 'start=0'. $find_url), 
           PageLink2("users", "&lt;", sprintf('start=%d', max(0, $start -MAX_USER_PP)).$find_url), 
           PageLink2("users", "&gt;", sprintf('start=%d', min($total-MAX_USER_PP, $start +MAX_USER_PP)).$find_url), 
           PageLink2("users", "&gt;&gt;", sprintf('start=%d', $total -MAX_USER_PP).$find_url));
*/
    $paging = simplePaging("users".$find_url,$start,$total,MAX_USER_PP,$GLOBALS['I18N']->get('Subscribers'));
    $result = Sql_query("$listquery $order $limit");
  } else {
    print Info($GLOBALS['I18N']->get('too many subscribers, use a search query to list some'),1);
    $result = 0;
  }
} else {
  $result = Sql_Query("$listquery $order");
}

$filterpanel .= '
<div class="usersFind">
<input type="hidden" name="id" value="'.$listid.'" />';

$filterpanel .= '<label for="find">'.$GLOBALS['I18N']->get('Find a user').'</label>';
$filterpanel .= '<input type="text" name="find" value="';
$filterpanel .= $find != '%' ? htmlspecialchars(stripslashes($find)) : "";
$filterpanel .= '" size="30" />';

$filterpanel .=  '<select name="findby">';

$filterpanel .= '<option value="email" ';
$filterpanel .= $findby == "email" ? 'selected="selected"':'';
$filterpanel .= '>'. $GLOBALS['I18N']->get('Email').'</option>';
$filterpanel .= '<option value="foreignkey" ';
$filterpanel .= $findby == "foreignkey"? 'selected="selected"':'';
$filterpanel .= '>'. $GLOBALS['I18N']->get('Foreign Key').'</option>';

$att_req = Sql_Query("select id,name from " . $tables["attribute"] . " where type = \"hidden\" or type = \"textline\" or type = \"select\"");
while ($row = Sql_Fetch_Array($att_req)) {
  $filterpanel .= sprintf('<option value="%d" %s>%s</option>', $row["id"], $row["id"] == $findby ? 'selected="selected"' : '', substr($row["name"], 0, 20));
}

$filterpanel .= '</select><input class="submit" type="submit" value="Go" />&nbsp;&nbsp;<a href="./?page=users&amp;find=NULL" class="reset">'. $GLOBALS['I18N']->get('reset').'</a>';
$filterpanel .= '</form></div>';
//$filterpanel .= '<tr><td colspan="4"></td></tr>
//</table>';

print Info($countpanel);


$panel = new UIPanel($GLOBALS['I18N']->get('Find subscribers'),$filterpanel);
print $panel->display();

#if (($require_login && isSuperUser()) || !$require_login)
print '<div class="actions">';
print '<div id="add-csv-button">' . PageLinkButton("dlusers", $GLOBALS['I18N']->get('Download all users as CSV file'), "nocache=" . uniqid("")) . '</div>' ;
print '<div id="add-user-button">' . PageLinkButton("user", $GLOBALS['I18N']->get('Add a User')) . '</div>' ;
print '</div>';

$some = 0;

$ls = new WebblerListing("users");
$ls->usePanel($paging);
if ($result)
  while ($user = Sql_fetch_array($result)) {
    $some = 1;
    $ls->addElement($user["email"], PageURL2("user&amp;start=$start&amp;id=" . $user["id"] . $find_url));
    $ls->setClass($user["email"],"row1");
    
    ## we make one column with the subscriber status being "on" or "off"
    ## two columns are too confusing and really unnecessary
    # ON = confirmed &&  !blacklisted
    
#    $ls->addColumn($user["email"], $GLOBALS['I18N']->get('confirmed'), $user["confirmed"] ? $GLOBALS["img_tick"] : $GLOBALS["img_cross"]);
 #   if (in_array("blacklist", $columns)) {
      $onblacklist = isBlackListed($user["email"]);
  #    $ls->addColumn($user["email"], $GLOBALS['I18N']->get('bl l'), $onblacklist ? $GLOBALS["img_tick"] : $GLOBALS["img_cross"]);
  #  }
  
    if ($user['confirmed'] && !$onblacklist) {
      $ls_confirmed=$GLOBALS["img_tick"];
    } else {
      $ls_confirmed=$GLOBALS["img_cross"];
    }
    
    $ls_del="";
#    $ls->addColumn($user["email"], $GLOBALS['I18N']->get('del'), sprintf('<a href="%s" onclick="return deleteRec(\'%s\');">del</a>',PageUrl2('users'.$find_url), PageURL2("users&start=$start&delete=" .$user["id"])));
    if (isSuperUser()) {
      $ls_del=sprintf('<a href="javascript:deleteRec(\'%s\');" class="del">del</a>',PageURL2("users&start=$start&find=$find&findby=$findby&delete=" .$user["id"]));
    }
/*    if (isset ($user['foreignkey'])) {
      $ls->addColumn($user["email"], $GLOBALS['I18N']->get('key'), $user["foreignkey"]);
    }
    if (isset ($user["display"])) {
      $ls->addColumn($user["email"], "&nbsp;", $user["display"]);
    }
*/    if (in_array("lists", $columns)) {
      $lists = Sql_query("SELECT count(*) FROM " . $tables["listuser"] . "," . $tables["list"] . " where userid = " . $user["id"] . " and " . $tables["listuser"] . ".listid = " . $tables["list"] . ".id");
      $membership = Sql_fetch_row($lists);
      $ls->addColumn($user["email"], $GLOBALS['I18N']->get('lists'), $membership[0]);
    }


    if (in_array("messages", $columns)) {
      $msgs = Sql_query("SELECT count(*) FROM " . $tables["usermessage"] . " where userid = " . $user["id"]);
      $nummsgs = Sql_fetch_row($msgs);
      $ls_msgs=$GLOBALS['I18N']->get('msgs').":&nbsp;".$nummsgs[0];
    }    
//obsolete, moved to rssmanager plugin 
//    if (ENABLE_RSS && in_array("rss", $columns)) {
//      $rss = Sql_query("SELECT count(*) FROM " . $tables["rssitem_user"] . " where userid = " . $user["id"]);
//      $nummsgs = Sql_fetch_row($rss);
//      $ls->addColumn($user["email"], $GLOBALS['I18N']->get('rss'), $nummsgs[0]);
//      if (isset ($user["rssfrequency"]))
//        $ls->addColumn($user["email"], $GLOBALS['I18N']->get('rss freq'), $user["rssfrequency"]);
//      $last = Sql_Fetch_Row_Query("select last from {$tables["user_rss"]} where userid = " . $user["id"]);
//      if ($last[0])
//        $ls->addColumn($user["email"], $GLOBALS['I18N']->get('last sent'), $last[0]);
//    }

    ### allow plugins to add columns
    if (isset($GLOBALS['plugins']) && is_array($GLOBALS['plugins'])) {
      foreach ($GLOBALS['plugins'] as $plugin) {
        if (method_exists($plugin,'displayUsers')) {
          $plugin->displayUsers($user,  $user['email'], $ls);
        }
      }
    }
    
    if (in_array("bounces", $columns)) {
      $ls_bncs=$GLOBALS['I18N']->get('bncs').": ".$user["bouncecount"];
    }
    $ls->addRow($user["email"],"<div class='listinghdname gray'>".$ls_msgs."<br />".$ls_bncs."</div>",$ls_del.'&nbsp;'.$ls_confirmed);

  }
print $ls->display();
if (!$some && !$total) {
  $p = new UIPanel($GLOBALS['I18N']->get('no results'),$GLOBALS['I18N']->get('No users apply'));
  print $p->display();
}
?>


