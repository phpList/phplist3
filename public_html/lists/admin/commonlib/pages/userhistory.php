<?php

print '<script language="Javascript" type="text/javascript" src="js/jslib.js"></script>';

if (!$_GET["id"]) {
  Fatal_Error($GLOBALS['I18N']->get('no such User'));
  return;
} else {
  $id = sprintf('%d',$_GET["id"]);
}

$access = accessLevel("user");
switch ($access) {
  case "owner":
    $subselect = " and ".$tables["list"].".owner = ".$_SESSION["logindetails"]["id"];break;
  case "all":
    $subselect = "";break;
  case "view":
    $subselect = "";
    if (sizeof($_POST) || $_GET["unblacklist"]) {
      print Error($GLOBALS['I18N']->get('you only have privileges to view this page, not change any of the information'));
      return;
    }
    break;
  case "none":
  default:
    $subselect = " and ".$tables["list"].".id = 0";break;
}

if (isset($_GET["unblacklist"])) {
  $unblacklist = sprintf('%d',$_GET["unblacklist"]);
  unBlackList($unblacklist);
  Redirect("userhistory&id=".$unblacklist);
}

$result = Sql_query("SELECT * FROM {$tables["user"]} where id = $id");
if (!Sql_Affected_Rows()) {
  Fatal_Error($GLOBALS['I18N']->get('no such User'));
  return;
}
$user = sql_fetch_array($result);

print '<h3>'.$GLOBALS['I18N']->get('user').' '.PageLink2("user&id=".$user["id"],$user["email"]).'</h3>';
print '<div class="actions">';
//printf('<a href="%s" class="button">%s</a>',getConfig("preferencesurl").
  //'&amp;uid='.$user["uniqid"],$GLOBALS['I18N']->get('update page'));
//printf('<a href="%s" class="button">%s</a>',getConfig("unsubscribeurl").'&amp;uid='.$user["uniqid"],$GLOBALS['I18N']->get('unsubscribe page'));
print PageLinkButton("user&amp;id=$id",$GLOBALS['I18N']->get('Details'));
if ($access != "view")
printf( "<a class=\"delete button\" href=\"javascript:deleteRec('%s');\">delete</a>",
  PageURL2("user","","delete=$id"));
print '</div>';

$bouncels = new WebblerListing($GLOBALS['I18N']->get('Bounces'));
$bouncelist = "";
$bounces = array();
# check for bounces
$req = Sql_Query(sprintf('select *,date_format(time,"%%e %%b %%Y %%T") as ftime from %s where user = %d',$tables["user_message_bounce"],$user["id"]));
if (Sql_Affected_Rows()) {
  while ($row = Sql_Fetch_Array($req)) {
    $bouncels->addElement($row["bounce"],PageURL2("bounce",$GLOBALS['I18N']->get('view'),"id=".$row["bounce"]));
    $bouncels->addColumn($row["bounce"],$GLOBALS['I18N']->get('msg'),$row["message"]);
    $bouncels->addColumn($row["bounce"],$GLOBALS['I18N']->get('time'),$row["ftime"]);
    $bounces[$row["message"]] = $row["ftime"];
  }
}

$ls = new WebblerListing($GLOBALS['I18N']->get('Messages'));
if (Sql_Table_Exists($tables["usermessage"])) {
  $msgs = Sql_Query(sprintf('select messageid,entered,viewed,(viewed = 0 or viewed is null) as notviewed,
    abs(unix_timestamp(entered) - unix_timestamp(viewed)) as responsetime from %s where userid = %d and status = "sent"',$tables["usermessage"],$user["id"]));
  $num = Sql_Affected_Rows();
} else {
  $num = 0;
}
printf('%d ' . $GLOBALS['I18N']->get('messages sent to this user') . '<br/>', $num);
if ($num) {
  $resptime = 0;
  $totalresp = 0;
  while ($msg = Sql_Fetch_Array($msgs)) {
    $ls->addElement($msg["messageid"],PageURL2("message",$GLOBALS['I18N']->get('view'),"id=".$msg["messageid"]));
    if (defined('CLICKTRACK') && CLICKTRACK) {
      $clicksreq = Sql_Fetch_Row_Query(sprintf('select sum(clicked) as numclicks from %s where userid = %s and messageid = %s',
        $GLOBALS['tables']['linktrack'],$user['id'],$msg['messageid']));
      $clicks = sprintf('%d',$clicksreq[0]);
      if ($clicks) {
        $ls->addColumn($msg["messageid"],$GLOBALS['I18N']->get('clicks'),
          PageLink2('userclicks&amp;userid='.$user['id'].'&amp;msgid='.$msg['messageid'],$clicks));
      } else {
        $ls->addColumn($msg["messageid"],$GLOBALS['I18N']->get('clicks'),0);
      }
    }

    $ls->addColumn($msg["messageid"],$GLOBALS['I18N']->get('sent'),formatDateTime($msg["entered"],1));
    if (!$msg['notviewed']) {
      $ls->addColumn($msg["messageid"],$GLOBALS['I18N']->get('viewed'),formatDateTime($msg["viewed"],1));
      $ls->addColumn($msg["messageid"],$GLOBALS['I18N']->get('responsetime'),$msg['responsetime']);
      $resptime += $msg['responsetime'];
      $totalresp += 1;
    }
    if (!empty($bounces[$msg["messageid"]])) {
      $ls->addColumn($msg["messageid"],$GLOBALS['I18N']->get('bounce'),$bounces[$msg["messageid"]]);
    }
  }
  if ($totalresp) {
    $avgresp = sprintf('%d',($resptime / $totalresp));
    $ls->addElement($GLOBALS['I18N']->get('average'));
    $ls->addColumn($GLOBALS['I18N']->get('average'),$GLOBALS['I18N']->get('responsetime'),$avgresp);
  }
}

print '<div class="tabbed">';
print '<ul>';
print '<li><a href="#messages">'.$GLOBALS['I18N']->get('Campaigns').'</a></li>';
if (count($bounces)) {
  print '<li><a href="#bounces">'.$GLOBALS['I18N']->get('Bounces').'</a></li>';
}  
print '<li><a href="#subscription">'.$GLOBALS['I18N']->get('Subscription').'</a></li>';
print '</ul>';

print '<div id="messages">';
print $ls->display();
print '</div>';
print '<div id="bounces">';
print $bouncels->display();
print '</div>';

if (isBlackListed($user["email"])) {
  print "<h3>" . $GLOBALS['I18N']->get('user is Blacklisted since') . " ";
  $blacklist_info = Sql_Fetch_Array_Query(sprintf('select * from %s where email = "%s"',
    $tables["user_blacklist"],$user["email"]));
  print $blacklist_info["added"]."</h3><br/>";
  print '';
  print "<a href=\"javascript:deleteRec2('" . htmlspecialchars($GLOBALS['I18N']->get('are you sure you want to delete this user from the blacklist')) . "?\\n"
  . htmlspecialchars($GLOBALS['I18N']->get('it should only be done with explicit permission from this user')) . "','./?page=userhistory&unblacklist={$user["id"]}&id={$user["id"]}')\">
  " . $GLOBALS['I18N']->get('remove User from Blacklist') . "</a>".'<br/><br/>';

  $ls = new WebblerListing($GLOBALS['I18N']->get('Blacklist Info'));
  $req = Sql_Query(sprintf('select * from %s where email = "%s"',
    $tables["user_blacklist_data"],$user["email"]));
  while ($row = Sql_Fetch_Array($req)) {
    $ls->addElement($row["name"]);
    $ls->addColumn($row["name"],$GLOBALS['I18N']->get('value'),stripslashes($row["data"]));
  }
  print $ls->display();
}


$ls = new WebblerListing($GLOBALS['I18N']->get('Subscription History'));
$req = Sql_Query(sprintf('select * from %s where userid = %d order by date desc',$tables["user_history"],$user["id"]));
if (!Sql_Affected_Rows())
  print $GLOBALS['I18N']->get('no details found');
while ($row = Sql_Fetch_Array($req)) {
  $ls->addElement($row["id"]);

  $ls->addColumn($row["id"],$GLOBALS['I18N']->get('ip'),$row["ip"]);
  $ls->addColumn($row["id"],$GLOBALS['I18N']->get('date'),$row["date"]);
  $ls->addColumn($row["id"],$GLOBALS['I18N']->get('summary'),$row["summary"]);
  $ls->addRow($row["id"],$GLOBALS['I18N']->get('detail'),nl2br(htmlspecialchars($row["detail"])));
  $ls->addRow($row["id"],$GLOBALS['I18N']->get('info'),nl2br($row["systeminfo"]));
}

print '<div id="subscription">';
print "<h3>" . $GLOBALS['I18N']->get('user subscription history') . "</h3>";
print $ls->display();
print '</div>';
print '</div>'; ## end of tabbed
?>
