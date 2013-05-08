<?php
require_once dirname(__FILE__).'/accesscheck.php';

$id = sprintf('%d',$_GET["id"]);
if (!$id) {
  print $GLOBALS['I18N']->get('Please select a message to display') . "\n";
  exit;
}

$access = accessLevel('message');
#print "Access: $access";
switch ($access) {
  case 'owner':
    $subselect = ' where owner = ' . $_SESSION["logindetails"]["id"];
    $owner_select_and = ' and owner = ' . $_SESSION["logindetails"]["id"];
    break;
  case 'all':
    $subselect = '';
    $owner_select_and = '';
    break;
  case 'none':
  default:
    $subselect = ' where id = 0';
    $owner_select_and = ' and owner = 0';
    break;
}

if (!empty($_POST['resend']) && is_array($_POST['list'])) {
  if (!empty($_POST['list']['all'])) {
    $res = Sql_query("select * from $tables[list]");
    while($list = Sql_fetch_array($res))
      if ($list["active"]) {
        $result = Sql_query(sprintf('insert into %s (messageid,listid,entered) values(%d,%d,current_timestamp)',$tables['listmessage'],$id,$list['id']));
      }
  } else {
    foreach($_POST['list'] as $key => $val) {
      if ($val == 'signup') {
        $result = Sql_query(sprintf('insert into %s (messageid,listid,entered) values(%d,%d,current_timestamp)',$tables['listmessage'],$id,$key));
      }
    }
  }
  Sql_Query("update $tables[message] set status = \"submitted\" where id = $id");
  $_SESSION['action_result'] = $GLOBALS['I18N']->get('campaign requeued');
  $messagedata = loadMessageData($id);
  $finishSending = mktime($messagedata['finishsending']['hour'],$messagedata['finishsending']['minute'],0,
    $messagedata['finishsending']['month'],$messagedata['finishsending']['day'],$messagedata['finishsending']['year']);
  if ($finishSending < time()) {
    $_SESSION['action_result'] .= '<br />'.s('This campaign is scheduled to stop sending in the past. No mails will be sent.');
    $_SESSION['action_result'] .= '<br />'.PageLinkButton('send&amp;id='.$messagedata['id'].'&amp;tab=Scheduling',s('Review Scheduling'));
  }
  
  Redirect('messages&tab=active');
  exit;  
}


require $coderoot . 'structure.php';

# This adds a return link. Should be replaced by uniformbreadcrumtrail
## non-functional using RG
if (isset($returnpage)) {
  if ($returnoption) {
    $more = "&amp;option=".$returnoption;
   }
  echo "<br/>".PageLink2("$returnpage$more","Return to $returnpage");
  $returnurl = "returnpage=$returnpage&returnoption=$returnoption";
}

$result = Sql_query("SELECT * FROM {$tables['message']} where id = $id $owner_select_and");
if (!Sql_Num_Rows($result)) {
  print $GLOBALS['I18N']->get('No such message');
  return;
}


$msgdata = loadMessageData($id);

if ($msgdata['status'] == 'draft' || $msgdata['status'] == 'suspended') {
  print '<div class="actions">';
  print '<p>'.PageLinkButton('send&amp;id='.$id,$GLOBALS['I18N']->get('Edit this message')).'</p>';
  print '</div>';
}

$content = '<table class="messageView">';
## optimise this, use msgdata above

while ($msg = Sql_fetch_array($result)) {
  foreach($DBstruct["message"] as $field => $val) {
    # Correct for bug 0009687
    # Skip 'astextandhtml' and add this count to 'as html'
    # change the name of sendformat
    if ($field != 'ashtml') {
      if ($field == 'astextandhtml') {
        $field = 'ashtml';
        $msg[$field] += $msg['astextandhtml'];
      };
      if ($field == 'sendformat' and $msg[$field] = 'text and HTML')
        $msg[$field] = 'HTML';
      if (!empty($msg[$field])) {
        $content .= sprintf('<tr><td valign="top" class="dataname">%s</td><td valign="top">%s</td></tr>',$GLOBALS['I18N']->get($field),$msg["htmlformatted"]?stripslashes($msg[$field]):nl2br(stripslashes($msg[$field])));
      }
    }  
  }
}

if (ALLOW_ATTACHMENTS) {
  $content .=  '<tr><td colspan="2"><h3>' . $GLOBALS['I18N']->get('Attachments for this message') . '</h3></td></tr>';
  $req = Sql_Query("select * from {$tables["message_attachment"]},{$tables["attachment"]}
    where {$tables["message_attachment"]}.attachmentid = {$tables["attachment"]}.id and
    {$tables["message_attachment"]}.messageid = $id");
  if (!Sql_Num_Rows($req))
    $content .= '<tr><td colspan="2">' . $GLOBALS['I18N']->get('No attachments') . '</td></tr>';
  while ($att = Sql_Fetch_array($req)) {
    $content .=sprintf ('<tr><td>%s:</td><td>%s</td></tr>', $GLOBALS['I18N']->get('Filename') ,$att["remotefile"]);
    $content .=sprintf ('<tr><td>%s:</td><td>%s</td></tr>', $GLOBALS['I18N']->get('Size'), formatBytes($att["size"]));
    $content .=sprintf ('<tr><td>%s:</td><td>%s</td></tr>', $GLOBALS['I18N']->get('Mime Type'),$att["mimetype"]);
    $content .=sprintf ('<tr><td>%s:</td><td>%s</td></tr>', $GLOBALS['I18N']->get('Description'), $att["description"]);
  }
 # print '</table>';
}

$content .= '<tr><td colspan="2"><h4>' . $GLOBALS['I18N']->get('This campaign has been sent to subscribers, who are member of the following lists') . ':</h4></td></tr>';

$lists_done = array();
$result = Sql_Query("select l.name, l.id from $tables[listmessage] lm, $tables[list] l where lm.messageid = $id and lm.listid = l.id");
if (!Sql_Num_Rows($result))
  $content .= '<tr><td colspan="2">' . $GLOBALS['I18N']->get('None yet') . '</td></tr>';
while ($lst = Sql_fetch_array($result)) {
  array_push($lists_done,$lst['id']);
  $content .= sprintf ('<tr><td>%d</td><td>%s</td></tr>',$lst['id'],$lst['name']);
}

if ($msgdata['excludelist']) {
  $content .= '<tr><td colspan="2"><h4>' . $GLOBALS['I18N']->get('Except when they were also member of these lists') . ':</h4></td></tr>';
  $result = Sql_Query(sprintf('select l.name, l.id from %s l where id in (%s)',$tables['list'],join(',',$msgdata['excludelist'])));
  while ($lst = Sql_fetch_array($result)) {
    $content .= sprintf ('<tr><td>%d</td><td>%s</td></tr>',$lst['id'],$lst['name']);
  }
}
$content .= '</table>';

$panel = new UIPanel($msgdata['subject'],$content);
print $panel->display();
?>

<a name="resend"></a><p class="information"><?php echo $GLOBALS['I18N']->get('Send this (same) message to (a) new list(s)'); ?>:</p>
<?php echo formStart(' class="messageResend" ')?>
<input type="hidden" name="id" value="<?php echo $id?>" />

<?php
$messlis = '';
$result = Sql_query("SELECT * FROM $tables[list] $subselect");
while ($row = Sql_fetch_array($result)) {
  if (!in_array($row['id'],$lists_done)) {
    $messlis .= '<li><input type="checkbox" name="list[' . $row["id"] . ']" value="signup" ';
    if (isset($_POST['list'][$row["id"]]) && $_POST['list'][$row["id"]] == 'signup')
      $messlis .= 'checked="checked"';
    $messlis .= " />".$row['name'];
    if ($row["active"])
      $messlis .= ' (' . $GLOBALS['I18N']->get('List is Active') . ')';
    else
      $messlis .= ' (' . $GLOBALS['I18N']->get('List is not Active') . ')';
    $some = 1;
    $messlis .= '</li>';
  }
}

if ($messlis == '')
  print $GLOBALS['I18N']->get('<b>Note:</b> this message has already been sent to all lists. To resend it to new users use the "Requeue" function.');
else
  print '<ul class="messageList">'.$messlis.'</ul><input class="submit" type="submit" name="resend" value="'.$GLOBALS['I18N']->get('Resend').'" /></form>';

?>
