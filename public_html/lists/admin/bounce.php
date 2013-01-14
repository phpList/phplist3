<?php

require_once dirname(__FILE__).'/accesscheck.php';

if (isset($_GET['id'])) {
  $id = sprintf('%d',$_GET["id"]);
} else {
  $id = 0;
}
if (isset($_GET['delete'])) {
  $delete = sprintf('%d',$_GET["delete"]);
} else {
  $delete = 0;
}
$actionresult = '';

$useremail = isset($_GET["useremail"]) && is_email($_GET["useremail"]) ? $_GET["useremail"] : ''; ## @TODO sanitize
$deletebounce = isset($_GET["deletebounce"]); #BUGFIX #15286 - nickyoung
$amount = isset($_GET["amount"]) ? sprintf('%d',$_GET["amount"]) : ''; #BUGFIX #15286 - CS2 
$unconfirm = isset($_GET["unconfirm"]); #BUGFIX #15286 - CS2 
$maketext = isset($_GET["maketext"]); #BUGFIX #15286 - CS2 
$deleteuser = isset($_GET["deleteuser"]);  #BUGFIX #15286 - CS2 
if (!$id && !$delete) {
  Fatal_Error($GLOBALS['I18N']->get('No such record'));
  exit;
}

if ($GLOBALS["require_login"] && !isSuperUser()) {
  $access = accessLevel("bounce");
  switch ($access) {
    case "all":
      $subselect = "";break;
    case "none":
    default:
      $subselect = " and ".$tables["list"].".id = 0";break;
  }
}
if (isset($start))
  echo "<br />".PageLink2("bounces",$GLOBALS['I18N']->get('Back to the list of bounces'),"start=$start")."\n";


if (isset($_GET["doit"]) && (($GLOBALS["require_login"] && isSuperUser()) || !$GLOBALS["require_login"])) {
  if ($useremail) {
    $req = Sql_Fetch_Row_Query(sprintf('select id from %s where email = "%s"',
      $tables["user"],sql_escape($useremail)));
     $userid = $req[0];
    if (!$userid) {
      $actionresult .= "$useremail => ".$GLOBALS['I18N']->get('Not Found')."<br />";
    }
  }
  if (isset($userid) && $amount) {
    Sql_Query(sprintf('update %s set bouncecount = bouncecount + %d where id = %d',
      $tables["user"],$amount,$userid));
     if (Sql_Affected_Rows()) {
      $actionresult .= sprintf($GLOBALS['I18N']->get('Added %s to bouncecount for subscriber %s')."<br />",$amount,$userid)."\n";
    } else {
      $actionresult .= sprintf($GLOBALS['I18N']->get('Added %s to bouncecount for subscriber %s')."<br />",$amount,$userid)."\n";
    }
  }

  if ($userid && $unconfirm) {
    Sql_Query(sprintf('update %s set confirmed = 0 where id = %d',
      $tables["user"],$userid));
     $actionresult .= sprintf($GLOBALS['I18N']->get('Made subscriber %s unconfirmed')."<br />", $userid);
  }

  if ($userid && $maketext) {
    Sql_Query(sprintf('update %s set htmlemail = 0 where id = %d',
      $tables["user"],$userid));
     $actionresult .= sprintf($GLOBALS['I18N']->get('Made subscriber %d to receive text')."<br />", $userid);
  }

  if ($userid && $deleteuser) {
    deleteUser($userid);
    $actionresult .= sprintf($GLOBALS['I18N']->get('Deleted subscriber %d')."<br />", $userid);
  }

  if (ALLOW_DELETEBOUNCE && $deletebounce) {
    $actionresult .= sprintf($GLOBALS['I18N']->get('Deleting bounce %d .. ')."\n", $id);
    Sql_query("delete from {$tables["bounce"]} where id = $id");
    $actionresult .= $GLOBALS['I18N']->get('..Done, loading next bounce..')."<br /><hr/><br />\n";
    $actionresult .= PageLink2("bounces",$GLOBALS['I18N']->get('Back to the list of bounces'));
    $next = Sql_Fetch_Row_query(sprintf('select id from %s where id > %d',$tables["bounce"],$id));
    $id = $next[0];
    if (!$id) {
      $next = Sql_Fetch_Row_query(sprintf('select id from %s order by id desc limit 0,5',$tables["bounce"],$id));
      $id = $next[0];
    }
  }
  print '<div id="actionresult" class="result">'.$actionresult .'</div>';
}

$guessedemail = '';
if ($id) {
  $result = Sql_query("SELECT * FROM {$tables["bounce"]} where id = $id");
  if (!Sql_Affected_Rows()) {
    Fatal_Error($GLOBALS['I18N']->get('This bounce no longer exists in the database.'));
    return;
  }
  $bounce = sql_fetch_array($result);
 #printf( "<br /><li><a href=\"javascript:deleteRec('%s');\">Delete</a>\n",PageURL2("bounce","","delete=$id"));
  if (preg_match("#([\d]+) bouncecount increased#",$bounce["comment"],$regs)) {
    $guessedid = $regs[1];
    $emailreq = Sql_Fetch_Row_Query(sprintf('select email from %s where id = %d',
      $tables["user"],$guessedid));
    $guessedemail = $emailreq[0];
  }
  
  $newruleform = '<form method=post action="./?page=bouncerules">';
  $newruleform .= '<table class="bounceListing">';
  $newruleform .= sprintf('<tr><td>%s</td><td><input type="text" name="newrule" size="30" /></td></tr>',$GLOBALS['I18N']->get('Regular Expression'));
  $newruleform .= sprintf('<tr><td>%s</td><td><select name="action">',$GLOBALS['I18N']->get('Action'));
  foreach ($GLOBALS['bounceruleactions'] as $action => $desc) {
    $newruleform .= sprintf('<option value="%s" %s>%s</option>',$action,'',$desc);
  }
  $newruleform .= '</select></td></tr>';
  $newruleform .= sprintf('<tr><td colspan="2">%s</td></tr><tr><td colspan="2"><textarea name="comment" rows=10 cols=65></textarea></td></tr>',
    $GLOBALS['I18N']->get('Memo for this rule'));
  $newruleform .= '<tr><td colspan="2"><p class="submit"><input type="submit" name="add" value="'.$GLOBALS['I18N']->get('Add new Rule').'" /></p></td></tr>';
  $newruleform .= '</table></form>';

  $actionpanel = '';
  $actionpanel .= '<form method="get">';
  $actionpanel .= '<input type="hidden" name=page value="'.$page.'" />';
  $actionpanel .= '<input type="hidden" name=id value="'.$id.'" />';
  $actionpanel .= '<table class="bounceActions">';
  $actionpanel .= '<tr><td>'.$GLOBALS['I18N']->get('For subscriber with email').'</td><td><input type="text" name="useremail" value="'.$guessedemail.'" size="35" /></td></tr>';
  $actionpanel .= '<tr><td>'.$GLOBALS['I18N']->get('Increase bouncecount with').'<br />'.$GLOBALS['I18N']->get('(use negative numbers to decrease)').'</td><td><input type="text" name="amount" value="1" size="5" /></td></tr>';
  $actionpanel .= '<tr><td>'.$GLOBALS['I18N']->get('Mark subscriber as unconfirmed').'<br />'.$GLOBALS['I18N']->get('(so you can resend the request for confirmation)').' </td><td><input type="checkbox" name="unconfirm" value="1" /></td></tr>';
  $actionpanel .= '<tr><td>'.$GLOBALS['I18N']->get('Set subscriber to receive text instead of HTML').' </td><td><input type="checkbox" name="maketext" value="1" /></td></tr>';
  $actionpanel .= '<tr><td>'.$GLOBALS['I18N']->get('Delete subscriber').' </td><td><input type="checkbox" name="deleteuser" value="1" /></td></tr>';
  if (ALLOW_DELETEBOUNCE) {
    $actionpanel .= '<tr><td>'.$GLOBALS['I18N']->get('Delete this bounce and go to the next').' </td><td><input type="checkbox" name="deletebounce" value="1" checked="checked" /></td></tr>';
  }
  $actionpanel .= '<tr><td class="bgwhite"><input class="submit" type="submit" name="doit" value="'.$GLOBALS['I18N']->get('Do the above').'" /></td></tr>';
  $actionpanel .= "</table></form>";
  if (USE_ADVANCED_BOUNCEHANDLING) {
    $actionpanel .= '<p class="button"><a href="#newrule">'.$GLOBALS['I18N']->get('Create New Rule based on this bounce').'</a></p>';
  }
  
  $p = new UIPanel($GLOBALS['I18N']->get('Possible Actions:'),$actionpanel);
  print $p->display();
  
  $bouncedetail = sprintf ('
  <div class="fleft"><div class="label">'.$GLOBALS['I18N']->get('ID').'</div><div class="content">%d</div></div>
  <div class="fleft"><div class="label">'.$GLOBALS['I18N']->get('Date').'</div><div class="content">%s</div></div>
  <div class="fleft"><div class="label">'.$GLOBALS['I18N']->get('Status').'</div><div class="content">%s</div></div>
  <div class="clear"></div><br />
  <div class="label">'.$GLOBALS['I18N']->get('Comment').'</div><div class="content">%s</div><br />
  <div class="label">'.$GLOBALS['I18N']->get('Header').'</div><div class="content">%s</div><br />
  <div class="label">'.$GLOBALS['I18N']->get('Body').'</div><div class="content">%s</div>',$id,
  $bounce["date"],$bounce["status"],$bounce["comment"],
  nl2br(htmlspecialchars($bounce["header"])),nl2br(htmlspecialchars($bounce["data"])));
#   print '<tr><td colspan="2"><p class="submit"><input type="submit" name=change value="Save Changes"></p>';

  $p = new UIPanel(s('Bounce Details'),$bouncedetail);
  print $p->display();
  
  if (USE_ADVANCED_BOUNCEHANDLING) {
    $p = new UIPanel(s('New Rule').'<a name="newrule"></a>',$newruleform);
    print $p->display();
  }
}

