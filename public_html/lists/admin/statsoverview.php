<?php

# click stats per message
require_once dirname(__FILE__).'/accesscheck.php';

if (isset($_GET['id'])) {
  $id = sprintf('%d',$_GET['id']);
} else {
  $id = 0;
}

$addcomparison = 0;
$access = accessLevel('statsoverview');
$and = '';
$subselect = '';
$paging = '';
$and_params = array();
#print "Access Level: $access";
switch ($access) {
  case 'owner':
    $and = ' and owner = ?';
    $and_params[] = $_SESSION['logindetails']['id'];
    if ($id) {
      $query = sprintf('select owner from %s where id = ? and owner = ?', $GLOBALS['tables']['message']);
      $rs = Sql_Query_Params($query, array($id, $_SESSION['logindetails']['id']));
      $allow = Sql_Fetch_Row($rs);
      if ($allow[0] != $_SESSION["logindetails"]["id"]) {
        print $GLOBALS['I18N']->get('You do not have access to this page');
        return;
      }
    }
    $addcomparison = 1;
    break;
  case 'all':
    break;
  case 'none':
  default:
    $and = ' and id = ?';
    $and_params[] = 0;
    print $GLOBALS['I18N']->get('You do not have access to this page');
    return;
    break;
}

$download = !empty($_GET['dl']);
if ($download) {
  ob_end_clean();
#  header("Content-type: text/plain");
  header('Content-type: text/csv');
  if (!$id) {
    header('Content-disposition:  attachment; filename="phpList Campaign statistics.csv"');
  }
  ob_start();
}  

if (!$id) {
#  print '<p>'.$GLOBALS['I18N']->get('Select Message to view').'</p>';
  print '<div class="actions">'.PageLinkButton('statsoverview&dl=true',$GLOBALS['I18N']->get('Download as CSV file')).'</div>';

  $timerange = ' and msg.entered > date_sub(current_timestamp,interval 12 month)';
  #$timerange = '';
  $limit = ' limit 10';
  $start = 0;
  if (isset($_GET['start'])) {
    $start = sprintf('%d',$_GET['start']);
    $limit = ' limit '.$start. ', 10';
  }

  $query = sprintf('select msg.owner,msg.id as messageid,count(um.viewed) as views, 
    count(um.status) as total,subject,date_format(sent,"%%e %%b %%Y") as sent,
    bouncecount as bounced from %s um,%s msg where um.messageid = msg.id and um.status = "sent" %s %s
    group by msg.id order by msg.entered desc',
    $GLOBALS['tables']['usermessage'],$GLOBALS['tables']['message'],$subselect,$timerange);
  $req = Sql_Query($query);
  $total = Sql_Num_Rows($req);
  if ($total > 10 && !$download) {
    #print Paging(PageUrl2('statsoverview'),$start,$total,10);
    $paging = simplePaging('statsoverview',$start,$total,10);
    $query .= $limit;
    $req = Sql_Query($query);
  }

  if (!Sql_Affected_Rows()) {
    print '<p class="information">'.$GLOBALS['I18N']->get('There are currently no messages to view').'</p>';
  }

  $ls = new WebblerListing($GLOBALS['I18N']->get('Campaigns in the last year'));
  $ls->usePanel($paging);
  while ($row = Sql_Fetch_Array($req)) {
    $element = $row['messageid'].' '.substr($row['subject'],0,50);

    $fwded = Sql_Fetch_Row_Query(sprintf('select count(id) from %s where message = %d',$GLOBALS['tables']['user_message_forward'],$row['messageid']));
    
    $ls->addElement($element,PageURL2('mviews&amp;id='.$row['messageid']));#,PageURL2('message&amp;id='.$row['messageid']));
    $ls->setClass($element,'row1');
 #   $ls->addColumn($element,$GLOBALS['I18N']->get('owner'),$row['owner']);
    $ls->addColumn($element,$GLOBALS['I18N']->get('sent'),$row['total']);
    $ls->addColumn($element,$GLOBALS['I18N']->get('bncs'),$row['bounced']);
    $ls->addColumn($element,$GLOBALS['I18N']->get('fwds'),sprintf('%d',$fwded[0]));
    $ls->addColumn($element,$GLOBALS['I18N']->get('views'),$row['views'],$row['views'] ? PageURL2('mviews&amp;id='.$row['messageid']):'');
    $perc = sprintf('%0.2f',($row['views'] / $row['total'] * 100));
    $ls->addRow($element,'',"<div class='content listingsmall fright gray'>".$GLOBALS['I18N']->get('rate').": ".$perc.' %'."</div>".
                            "<div class='content listingsmall fright gray'>".$GLOBALS['I18N']->get('date').": ".$row['sent']."</div>");
  }
  if ($addcomparison) {
    $total = Sql_Fetch_Array_Query(sprintf('select count(entered) as total from %s um where um.status = "sent"', $GLOBALS['tables']['usermessage']));
    $viewed = Sql_Fetch_Array_Query(sprintf('select count(viewed) as viewed from %s um where um.status = "sent"', $GLOBALS['tables']['usermessage']));
    $overall = $GLOBALS['I18N']->get('Comparison to other admins');
    $ls->addElement($overall);
    $ls->addColumn($overall,$GLOBALS['I18N']->get('views'),$viewed['viewed']);
    $perc = sprintf('%0.2f',($viewed['viewed'] / $total['total'] * 100));
    $ls->addColumn($overall,$GLOBALS['I18N']->get('rate'),$perc.' %');
  }
  if ($download) {
    ob_end_clean();
    print $ls->tabDelimited();
  }

  print $ls->display();
  return;
}


print '<h3>'.$GLOBALS['I18N']->get('View Details for a Message').'</h3>';
$query = "select * from ${tables['message']} where id = ? $and";
$params = array_merge(array($id), $and_params);
$rs = Sql_Query_Params($query, $params);
$messagedata = Sql_Fetch_Array($rs);
print '<table class="statsDetail">
<tr><td>'.$GLOBALS['I18N']->get('Subject').'<td><td>'.$messagedata['subject'].'</td></tr>
<tr><td>'.$GLOBALS['I18N']->get('Entered').'<td><td>'.$messagedata['entered'].'</td></tr>
<tr><td>'.$GLOBALS['I18N']->get('Sent').'<td><td>'.$messagedata['sent'].'</td></tr>
</table><hr/>';


$ls = new WebblerListing($GLOBALS['I18N']->get('Message Open Statistics'));

// TODO Use join syntax.
$query
= ' select um.userid'
. ' from %s um, %s msg'
. ' where um.messageid = ?'
. '   and um.messageid = msg.id'
. '   and um.viewed is not null'
. '   and um.status = "sent"'
. '%s'
. ' group by userid';
$query = sprintf($query, $GLOBALS['tables']['usermessage'], $GLOBALS['tables']['message'], $and);
$params = array_merge(array($id), $and_params);
$req = Sql_Query_Params($query, $params);

$total = Sql_Affected_Rows();
$start = sprintf('%d',$_GET['start']);
$offset = 0;
if (isset($start) && $start > 0) {
  $listing = sprintf($GLOBALS['I18N']->get("Listing user %d to %d"),$start,$start + MAX_USER_PP);
  $offset = $start;
} else {
  $listing =  sprintf($GLOBALS['I18N']->get("Listing user %d to %d"),1,MAX_USER_PP);
  $start = 0;
}
if ($id) {
  $url_keep = '&amp;id='.$id;
} else {
  $url_keep = '';
}
print $total. " ".$GLOBALS['I18N']->get("Entries")."</p>";
if ($total) {
  printf ('<table class="statsListing" border="1"><tr><td colspan="4" align="center">%s</td></tr><tr><td>%s</td><td>%s</td><td>
          %s</td><td>%s</td></tr></table><hr/>',
          $listing,
          PageLink2("mviews$url_keep","&lt;&lt;","start=0"),
          PageLink2("mviews$url_keep","&lt;",sprintf('start=%d',max(0,$start-MAX_USER_PP))),
          PageLink2("mviews$url_keep","&gt;",sprintf('start=%d',min($total,$start+MAX_USER_PP))),
          PageLink2("mviews$url_keep","&gt;&gt;",sprintf('start=%d',$total-MAX_USER_PP)));
}

// BUG here with unix_timestamp.
// TODO Use join syntax.
$query
= 'select userid, email, um.entered as sent, min(um.viewed) as firstview'
. '  , max(um.viewed) as lastview, count(um.viewed) as viewcount'
. '  , abs(unix_timestamp(um.entered) - unix_timestamp(um.viewed)) as responsetime'
. 'from %s um, %s user, %s msg'
. 'where um.messageid = ?'
. ' and um.messageid = msg.id'
. ' and um.userid = user.id'
. ' and um.viewed is not null'
. '%s'
. ' group by userid'
. ' limit ' . MAX_USER_PP
. ' offset ?';
$query = sprintf($query, $GLOBALS['tables']['usermessage'], $GLOBALS['tables']['user'], $GLOBALS['tables']['message'], $and);
$params = array_merge(array($id), $and_params, array($offset));
$req = Sql_Query_Params($query, $params);
$summary = array();
while ($row = Sql_Fetch_Array($req)) {
  $element = '<!--'.$row['userid'].'-->'.$row['email'];
  $ls->addElement($element,PageUrl2('userhistory&amp;id='.$row['userid']));
  $ls->addColumn($element,$GLOBALS['I18N']->get('sent'),formatDateTime($row['sent']));
  if ($row['viewcount'] > 1) {
    $ls->addColumn($element,$GLOBALS['I18N']->get('firstview'),formatDateTime($row['firstview'],1));
    $ls->addColumn($element,$GLOBALS['I18N']->get('lastview'),formatDateTime($row['lastview']));
    $ls->addColumn($element,$GLOBALS['I18N']->get('views'),$row['viewcount']);
  } else {
    $ls->addColumn($element,$GLOBALS['I18N']->get('firstview'),formatDateTime($row['firstview'],1));
    $ls->addColumn($element,$GLOBALS['I18N']->get('responsetime'),$row['responsetime'].' '.$GLOBALS['I18N']->get('sec'));
  }
}
print $ls->display();
?>
