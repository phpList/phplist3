

<script language="Javascript" type="text/javascript">
  function updateStatus(id,content) {
    var el = document.getElementById("messagestatus"+id);
    el.innerHTML = content;
  }

  function statusError(id) {
    var el = document.getElementById("messagestatus"+id);
    el.innerHTML = "Unable to fetch progress";
  }

  function fetchProgress(id) {
    var req = new AjaxRequest();
    AjaxRequest.get(
      {
        'id': id
        ,'url':'./?page=msgstatus'
        ,'onSuccess':function(req){ updateStatus(id,req.responseText) }
        ,'onError':function(req){ statusError(); }
      }
    );
  }

</script>


<hr/>

<?php

require_once dirname(__FILE__).'/accesscheck.php';

$subselect = $where = '';
$action_result = '';
$access = accessLevel('messages');

if( !$GLOBALS["require_login"] || $_SESSION["logindetails"]['superuser'] || $access == 'all'){
  $ownerselect_and = '';
  $ownerselect_where = '';
} else {
  $ownerselect_where = ' where owner = ' . $_SESSION["logindetails"]['id'];
  $ownerselect_and = ' and owner = ' . $_SESSION["logindetails"]['id'];
}
if (isset($_GET['start'])) {
  $start = sprintf('%d',$_GET['start']);
} else {
  unset($start);
}

# remember last one listed
if (!isset($_GET["tab"]) && !empty($_SESSION["lastmessagetype"])) {
  $_GET["tab"] = $_SESSION["lastmessagetype"];
} elseif (isset($_GET["tab"])) {
  $_SESSION["lastmessagetype"] = $_GET["tab"];
}

#print '<p class="x">'.PageLink2("messages&type=sent","Sent Messages").'&nbsp;&nbsp;&nbsp;';
#print PageLink2("messages&type=draft","Draft Messages").'&nbsp;&nbsp;&nbsp;';
#print PageLink2("messages&type=queue","Queued Messages").'&nbsp;&nbsp;&nbsp;';
#print PageLink2("messages&type=stat","Static Messages").'&nbsp;&nbsp;&nbsp;';
//obsolete, moved to rssmanager plugin
#if (ENABLE_RSS) {
#  print PageLink2("messages&type=rss","rss Messages").'&nbsp;&nbsp;&nbsp;';
#}
#print '</p>';

print '<div class="actions">';
print PageLinkActionButton('send&amp;new=1',$GLOBALS['I18N']->get('Start a new campaign'));
print '</div>';

### Print tabs
$tabs = new WebblerTabs();
$tabs->addTab($GLOBALS['I18N']->get("sent"),PageUrl2("messages&amp;tab=sent"),'sent');
$tabs->addTab($GLOBALS['I18N']->get("active"),PageUrl2("messages&amp;tab=active"),'active');
$tabs->addTab($GLOBALS['I18N']->get("draft"),PageUrl2("messages&amp;tab=draft"),'draft');
#$tabs->addTab($GLOBALS['I18N']->get("queued"),PageUrl2("messages&amp;tab=queued"));#
if (USE_PREPARE) {
  $tabs->addTab($GLOBALS['I18N']->get("static"),PageUrl2("messages&amp;tab=static"),'static');
}
//obsolete, moved to rssmanager plugin
#if (ENABLE_RSS) {
#  $tabs->addTab("rss",PageUrl2("messages&amp;tab=rss"));
#}
if (!empty($_GET['tab'])) {
  $tabs->setCurrent($_GET["tab"]);
} else {
  $_GET['tab'] = 'sent';
  $tabs->setCurrent('sent');
}

print $tabs->display();

### Process 'Action' requests
if (!empty($_GET["delete"])) {
  $todelete = array();
  if ($_GET["delete"] == "draft") {
    $req = Sql_Query(sprintf('select id from %s where status = "draft" and (subject = "" or subject = "(no subject)") %s',$GLOBALS['tables']["message"],$ownerselect_and));
    while ($row = Sql_Fetch_Row($req)) {
      array_push($todelete,$row[0]);
    }
  } else {
    array_push($todelete,sprintf('%d',$_GET["delete"]));
  }
  foreach ($todelete as $delete) {
    $action_result .= $GLOBALS['I18N']->get("Deleting")." $delete ...";
    $del = deleteMessage($delete);
    if ($del)
      $action_result .= "... ".$GLOBALS['I18N']->get("Done");
    else
      $action_result .= "... ".$GLOBALS['I18N']->get("failed");
    $action_result .= '<br/>';
  }
  $action_result .= "<hr /><br />\n";
}

if (isset($_GET['resend'])) {
  $resend = sprintf('%d',$_GET['resend']);
  # requeue the message in $resend
  $action_result .=  $GLOBALS['I18N']->get("Requeuing")." $resend ..";
  $result = Sql_Query("update ${tables['message']} set status = 'submitted', sendstart = null where id = $resend");
  $suc6 = Sql_Affected_Rows();
  # only send it again to users, if we are testing, otherwise only to new users
  if (TEST)
    $result = Sql_query("delete from ${tables['usermessage']} where messageid = $resend");
  if ($suc6) {
    $action_result .=  "... ".$GLOBALS['I18N']->get("Done");
    foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
      $plugin->messageReQueued($resend);
    }
  } else {
    $action_result .=  "... ".$GLOBALS['I18N']->get("failed");
  }
  $action_result .=  '<br /><hr /><br />';
}

if (isset($_GET['suspend'])) {
  $suspend = sprintf('%d',$_GET['suspend']);
  $action_result .=  $GLOBALS['I18N']->get('Suspending')." $suspend ..";
  $result = Sql_query(sprintf('update %s set status = "suspended" where id = %d and (status = "inprocess" or status = "submitted") %s',$tables["message"],$suspend,$ownerselect_and));
  $suc6 = Sql_Affected_Rows();
  if ($suc6)
    $action_result .=  "... ".$GLOBALS['I18N']->get("Done");
  else
    $action_result .=  "... ".$GLOBALS['I18N']->get("failed");
  $action_result .= '<br /><hr /><br />';
}
#0012081: Add new 'Mark as sent' button
if (isset($_GET['markSent'])) {
  $markSent = sprintf('%d',$_GET['markSent']);
  $action_result .=  $GLOBALS['I18N']->get('Marking as sent ')." $markSent ..";
  $result = Sql_query(sprintf('update %s set status = "sent" where id = %d and (status = "suspended") %s',$tables["message"],$markSent,$ownerselect_and));
  $suc6 = Sql_Affected_Rows();
  if ($suc6)
    $action_result .=  "... ".$GLOBALS['I18N']->get("Done");
  else
    $action_result .=  "... ".$GLOBALS['I18N']->get("Failed");
  $action_result .=  '<br /><hr /><br />';
}

if (isset($_GET['action'])) {
  switch ($_GET['action']) {
    case 'suspall':
      $action_result .=  $GLOBALS['I18N']->get('Suspending all')." ..";
      $result = Sql_query(sprintf('update %s set status = "suspended" where (status = "inprocess" or status = "submitted") %s',$tables["message"],$ownerselect_and));
      $suc6 = Sql_Affected_Rows();
      if ($suc6)
        $action_result .=  "... $suc6".$GLOBALS['I18N']->get("Done");
      else
        $action_result .=  "... ".$GLOBALS['I18N']->get("Failed");
      $action_result .= '<br /><hr /><br />';
      break;
    case 'markallsent':
      $action_result .=  $GLOBALS['I18N']->get('Marking all as sent ')."  ..";
      $result = Sql_query(sprintf('update %s set status = "sent" where (status = "suspended") %s',$tables["message"],$markSent,$ownerselect_and));
      $suc6 = Sql_Affected_Rows();
      if ($suc6)
        $action_result .=  "... $suc6".$GLOBALS['I18N']->get("Done");
      else
        $action_result .=  "... ".$GLOBALS['I18N']->get("Failed");
      $action_result .=  '<br /><hr /><br />';
      break;
  }
}

  if (!empty($action_result)) {
    #print ActionResult($action_result);
    $_SESSION['action_result'] = $action_result;
    Redirect('messages');
    exit;
  }

$cond = array();
### Switch tab
switch ($_GET["tab"]) {
  case "queued":
#    $subselect = ' status in ("submitted") and (rsstemplate is NULL or rsstemplate = "") ';
    $cond[] = " status in ('submitted', 'suspended') ";
    $url_keep = '&amp;tab=queued';
    break;
  case "static":
    $cond[] = " status in ('prepared') ";
    $url_keep = '&amp;tab=static';
    break;
#  case "rss":
#    $subselect = ' rsstemplate != ""';
#    $url_keep = '&amp;tab=sent';
#    break;
  case "draft":
    $cond[] = " status in ('draft') ";
    $url_keep = '&amp;tab=draft';
    break;
  case "active":
    $cond[] = " status in ('inprocess','submitted', 'suspended') ";
    $url_keep = '&amp;tab=active';
    break;
  case "sent":
  default:
    $cond[] = " status in ('sent') ";
    $url_keep = '&amp;tab=sent';
    break;
}

### Query messages from db
if ($GLOBALS['require_login'] && !$_SESSION['logindetails']['superuser'] || $access != 'all') {
  $cond[] = ' owner = ' . $_SESSION['logindetails']['id'];
}
$where = ' where ' . join(' and ', $cond);

$req = Sql_query('select count(*) from ' . $tables['message']. $where);
$total_req = Sql_Fetch_Row($req);
$total = $total_req[0];

## Browse buttons table
$limit = MAX_MSG_PP;
$offset = 0;
if (isset($start) && $start > 0) {
  $offset = $start;
} else {
  $start = 0;
}

$paging = '';
if ($total > MAX_MSG_PP) {
  $paging = simplePaging("messages$url_keep",$start,$total,MAX_MSG_PP,$GLOBALS['I18N']->get("Campaigns"));
}
  
$ls = new WebblerListing($I18N->get('messages'));
$ls->usePanel($paging);

## messages table
if ($total) {
  $result = Sql_query("SELECT * FROM ".$tables["message"]." $where order by status,entered desc limit $limit offset $offset");
  while ($msg = Sql_fetch_array($result)) {
    $editlink = '';
    $listingelement = '<!--'.$msg['id'].'-->'.stripslashes($msg["subject"]);
    if ($msg['status'] == 'draft') {
      $editlink = PageUrl2("send&id=".$msg["id"]);
    }
    
    $ls->addElement($listingelement,$editlink);

    $uniqueviews = Sql_Fetch_Row_Query("select count(userid) from {$tables["usermessage"]} where viewed is not null and messageid = ".$msg["id"]);

    $clicks = Sql_Fetch_Row_Query("select sum(clicked) from {$tables["linktrack_ml"]} where messageid = ".$msg["id"]);
#    $clicks = array(0);

    $messagedata = loadMessageData($msg['id']);

/*
    foreach ($messagedata as $key => $val) {
      $ls->addColumn($listingelement,$key,$val);
    }
    
*/
    $ls->addColumn($listingelement,$GLOBALS['I18N']->get("Entered"), formatDateTime($msg["entered"]));

    $_GET['id'] = $msg['id'];
    $statusdiv = '<div id="messagestatus'.$msg['id'].'">';
    include 'actions/msgstatus.php';
    $statusdiv .= $status;
    $statusdiv .= '</div>';
    $statusdiv .= '
    <script type="text/javascript">
      messageStatusUpdate('.$msg['id'].');
    </script>
    ';
    if ($msg['status'] == 'sent') {
      $statusdiv = $GLOBALS['I18N']->get("Sent").": ".$msg['sent'];
    }
    $ls->addColumn($listingelement,$GLOBALS['I18N']->get("Status"),$statusdiv);

    if ($msg['status'] != 'draft') {
#    $ls->addColumn($listingelement,$GLOBALS['I18N']->get("total"), $msg['astext'] + $msg['ashtml'] + $msg['astextandhtml'] + $msg['aspdf'] + $msg['astextandpdf']);
#    $ls->addColumn($listingelement,$GLOBALS['I18N']->get("text"), $msg['astext']);
#    $ls->addColumn($listingelement,$GLOBALS['I18N']->get("html"), $msg["ashtml"] + $msg["astextandhtml"]);
#    if (!empty($msg['aspdf'])) {
#      $ls->addColumn($listingelement,$GLOBALS['I18N']->get("PDF"), $msg['aspdf']);
#    }
#    if (!empty($msg["astextandpdf"])) {
#      $ls->addColumn($listingelement,$GLOBALS['I18N']->get("both"), $msg["astextandpdf"]);
#    }
      $resultStats = '<table class="messagesendstats">
      <tr><td>'.s('Viewed').'</td><td>'.$msg['viewed'].'</td></tr>
      <tr><td>'.s('Unique Views').'</td><td>'.$uniqueviews[0].'</td></tr>';
      if ($clicks[0]) {
        $resultStats .= '
           <tr><td>'.s('Clicks').'</td><td>'.$clicks[0].'</td></tr>';
      }
      $resultStats .= '
         <tr><td>'.s('Bounced').'</td><td>'.$msg['bouncecount'].'</td></tr>';
      $resultStats .= '</table>';
      
      $ls->addColumn($listingelement,s('Results'),$resultStats);


      //$ls->addColumn($listingelement,$GLOBALS['I18N']->get("Viewed"), $msg["viewed"]);
      //$ls->addColumn($listingelement,$GLOBALS['I18N']->get("Unique Views"), $uniqueviews[0]);
      //if ($clicks[0]) {
        //$ls->addColumn($listingelement,$GLOBALS['I18N']->get("Clicks"), $clicks[0]);
      //}
      //$ls->addColumn($listingelement,$GLOBALS['I18N']->get("Bounced"), $msg["bouncecount"]);
    } 

    if ($msg['status'] == 'sent') {
      $timetosend = $GLOBALS['I18N']->get("Time to send").': '.timeDiff($msg["sendstart"],$msg["sent"]);
    } else {
      $timetosend = '';
    }

    $colspan = 3;
    if (!empty($msg['aspdf'])) $colspan++;
    if (!empty($msg['astextandpdf'])) $colspan++;
    $clicksrow = $bouncedrow = '';

    //if ($clicks[0]) {
      //$clicksrow = sprintf('<tr><td colspan="%d">%s</td><td>%d</td></tr>',
        //$colspan-1,$GLOBALS['I18N']->get("Clicks"),$clicks[0]);
    //}
    //if ($msg["bouncecount"]) {
      //$bouncedrow = sprintf('<tr><td colspan="%d">%s</td><td>%d</td></tr>',
        //$colspan-1,$GLOBALS['I18N']->get("Bounced"),$msg["bouncecount"]);
    //}
    
    $sendstats =
      sprintf('<table class="messagesendstats">
      %s
      <tr><td>'.$GLOBALS['I18N']->get("total").'</td><td>'.$GLOBALS['I18N']->get("text").'</td><td>'.$GLOBALS['I18N']->get("html").'</td>
        %s%s
      </tr>
      <tr><td><b>%d</b></td><td><b>%d</b></td><td><b>%d</b></td>
        %s %s %s %s
      </tr>
      </table>',
      !empty($timetosend) ? '<tr><td colspan="'.$colspan.'">'.$timetosend.'</td></tr>' : '',
      !empty($msg['aspdf']) ? '<td>'.$GLOBALS['I18N']->get("PDF").'</td>':'',
      !empty($msg['astextandpdf']) ? '<td>'.$GLOBALS['I18N']->get("both").'</td>':'',
      $msg['astext'] + $msg['ashtml'] + $msg['astextandhtml'] + $msg['aspdf'] + $msg['astextandpdf'],
      $msg["astext"],
      $msg["ashtml"] + $msg["astextandhtml"], //bug 0009687
      !empty($msg['aspdf']) ?  '<td><b>'.$msg["aspdf"].'</b></td>':'',
      !empty($msg['astextandpdf']) ? '<td><b>'.$msg["astextandpdf"].'</b></td>':'',
      $clicksrow,$bouncedrow
    );
    if ($msg['status'] != 'draft') {
      $ls->addColumn($listingelement,$GLOBALS['I18N']->get("Statistics"), $sendstats);
    }

    $actionbuttons = '';
    if ($msg['status'] == 'inprocess' || $msg['status'] == 'submitted') {
      $actionbuttons .= '<span class="suspend">'.PageLinkButton('messages&suspend='.$msg['id'],$GLOBALS['I18N']->get('Suspend')).'</span>';
    } elseif ($msg['status'] != 'draft') {
      $actionbuttons .= '<span class="resend">'.PageLinkButton("messages",$GLOBALS['I18N']->get("Requeue"),"resend=".$msg["id"]).'</span>';
    }
    #0012081: Add new 'Mark as sent' button
    if ($msg['status'] == 'suspended') {
      $actionbuttons .= '<span class="marksent">'.PageLinkButton('messages&amp;markSent='.$msg['id'],$GLOBALS['I18N']->get('Mark&nbsp;sent')).'</span>';
      $actionbuttons .= '<span class="edit">'.PageLinkButton("send",$GLOBALS['I18N']->get("Edit"),"id=".$msg["id"]).'</span>'; 
    }
    
    if ($msg['status'] == 'draft') {
      ## only draft messages should be deletable, the rest isn't
      $actionbuttons .= sprintf('<span class="delete"><a href="javascript:deleteRec(\'%s\');" class="button" title="'.$GLOBALS['I18N']->get("delete").'">'.$GLOBALS['I18N']->get("delete").'</a></span>',
PageURL2("messages$url_keep","","delete=".$msg["id"]));
      $actionbuttons .= '<span class="edit">'.PageLinkButton("send",$GLOBALS['I18N']->get("Edit"),"id=".$msg["id"]).'</span>'; 
    }
    $actionbuttons .= '<span class="view">'.PageLinkButton("message",$GLOBALS['I18N']->get("View"),"id=".$msg["id"]).'</span>';

    if ($clicks[0] && CLICKTRACK) {
      $actionbuttons .= '<span class="stats">'.PageLinkButton("mclicks",$GLOBALS['I18N']->get("click stats"),"id=".$msg["id"]).'</span>';
    }

    $ls->addColumn($listingelement,$GLOBALS['I18N']->get("Action"), '<div class="messageactions">'.$actionbuttons.'</div>');


    ## allow plugins to add information
    foreach ($GLOBALS['plugins'] as $plugin) {
      if (method_exists($plugin,'displayMessages')) {
        $plugin->displayMessages($msg, $status);
      }
    }
  }
}

print $ls->display();

if ($total > 5 && $_GET['tab'] == 'active') {
  print PageLinkButton("messages",$GLOBALS['I18N']->get("Suspend All"),"action=suspall");
  print PageLinkButton("messages",$GLOBALS['I18N']->get("Mark All Sent"),"action=markallsent");
}


?>



