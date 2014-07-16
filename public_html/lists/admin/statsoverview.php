<?php

# click stats per message
require_once dirname(__FILE__).'/accesscheck.php';

if (isset($_GET['id'])) {
  $id = sprintf('%d',$_GET['id']);
} else {
  $id = 0;
}
$start = 0;
$limit = ' limit 10';
if (isset($_GET['start'])) {
  $start = sprintf('%d',$_GET['start']);
  $limit = ' limit '.$start. ', 10';
}

$addcomparison = 0;
$access = accessLevel('statsoverview');
$ownership = '';
$subselect = '';
$paging = '';

#print "Access Level: $access";
switch ($access) {
  case 'owner':
    $ownership = sprintf(' and owner = %d ', $_SESSION['logindetails']['id']);
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
    $ownership = ' and msg.id = 0';
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
 # print '<p>'.$GLOBALS['I18N']->get('Select Message to view').'</p>';
  
  if (empty($start)) {
    print '<div class="actions">'.PageLinkButton('statsoverview&dl=true',$GLOBALS['I18N']->get('Download as CSV file')).'</div>';
  }

  $timerange = ' and msg.entered > date_sub(current_timestamp,interval 12 month)';
  $timerange = '';

  $query = sprintf('select msg.owner,msg.id as messageid,count(um.viewed) as views, 
    count(um.status) as total,subject,date_format(sent,"%%e %%b %%Y") as sent,
    bouncecount as bounced from %s um,%s msg where um.messageid = msg.id and um.status = "sent" %s %s %s
    group by msg.id order by msg.entered desc',
    $GLOBALS['tables']['usermessage'],$GLOBALS['tables']['message'],$subselect,$timerange,$ownership);
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

  $ls = new WebblerListing('');
  $ls->usePanel($paging);
  while ($row = Sql_Fetch_Array($req)) {
    $element = '<!--'.$row['messageid'].'-->'.shortenTextDisplay($row['subject'],30);

    $fwded = Sql_Fetch_Row_Query(sprintf('select count(id) from %s where message = %d',$GLOBALS['tables']['user_message_forward'],$row['messageid']));
    
    $ls->addElement($element,PageURL2('statsoverview&amp;id='.$row['messageid']));#,PageURL2('message&amp;id='.$row['messageid']));
    $ls->setClass($element,'row1');
 #   $ls->addColumn($element,$GLOBALS['I18N']->get('owner'),$row['owner']);
    $ls->addColumn($element,$GLOBALS['I18N']->get('sent'),$row['total']);
    $ls->addColumn($element,$GLOBALS['I18N']->get('bncs'),$row['bounced']);
    $ls->addColumn($element,$GLOBALS['I18N']->get('fwds'),sprintf('%d',$fwded[0]));
    $ls->addColumn($element,$GLOBALS['I18N']->get('views'),$row['views'],$row['views'] ? PageURL2('mviews&amp;id='.$row['messageid']):'');
    $perc = sprintf('%0.2f',($row['views'] / ($row['total'] - $row['bounced']) * 100));
    
    
    $ls->addRow($element,'',"<div class='content listingsmall fright gray'>".$GLOBALS['I18N']->get('rate').": ".$perc.' %'."</div>".
                            "<div class='content listingsmall fright gray'>".$GLOBALS['I18N']->get('date').": ".$row['sent']."</div>");
  }
  ## needs reviewing
  if (false && $addcomparison) {
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

#print '<h3>'.$GLOBALS['I18N']->get('Campaign statistics').'</h3>';
print PageLinkButton('statsoverview',s('View all campaigns'));

$messagedata = loadMessageData($id);
//var_dump($messagedata);

if (empty($messagedata['subject'])) {
  Error(s('Campaign not found'));
  return;
}

print '<h3>'.$messagedata['subject']. '</h3>';

$ls = new WebblerListing('');

$element = ucfirst(s('Subject'));
$ls->addElement($element);
$ls->addColumn($element,'&nbsp;',shortenTextDisplay($messagedata['subject'],30));

$element = ucfirst(s('Date entered'));
$ls->addElement($element);
$ls->addColumn($element,'&nbsp;',$messagedata['entered']);

$element = ucfirst(s('Date sent'));
$ls->addElement($element);
$ls->addColumn($element,'&nbsp;',$messagedata['sent']);

$element = ucfirst(s('Sent as HTML'));
$ls->addElement($element);
$ls->addColumn($element,'&nbsp;',$messagedata['astextandhtml']);

$element = ucfirst(s('Sent as text'));
$ls->addElement($element);
$ls->addColumn($element,'&nbsp;',$messagedata['astext']);

$totalSent = 0;
$sentQ = Sql_Query(sprintf('select status,count(userid) as num from %s where messageid = %d group by status',$tables['usermessage'],$id));
while ($row = Sql_Fetch_Assoc($sentQ)) {
  $element = ucfirst($row['status']);
  $ls->addElement($element);
  $ls->addColumn($element,'&nbsp;',$row['num']);
  if ($row['status'] == 'sent') {
    $totalSent = $row['num'];
  }
}
/*
$element = ucfirst(s('Bounced'));
$ls->addElement($element);
$ls->addColumn($element,'&nbsp;',$messagedata['bouncecount']);
*/

$bounced = Sql_Fetch_Row_Query(sprintf('select count(distinct user) from %s where message = %d',$tables['user_message_bounce'],$id));
$element = ucfirst(s('Bounced'));
$ls->addElement($element);
$ls->addColumn($element,'&nbsp;',$bounced[0]);
$totalBounced = $bounced[0];

$viewed = Sql_Fetch_Row_Query(sprintf('select count(userid) from %s where messageid = %d and status = "sent" and viewed is not null',$tables['usermessage'],$id));
$element = ucfirst(s('Opened'));
$ls->addElement($element);
$ls->addColumn($element,'&nbsp;',!empty($viewed[0]) ? PageLink2('mviews&id='.$id,$viewed[0]): '0');

$perc = sprintf('%0.2f',$viewed[0] / ($totalSent - $totalBounced) * 100);
$element = ucfirst(s('% Opened'));
$ls->addElement($element);
$ls->addColumn($element,'&nbsp;',$perc);

$clicked = Sql_Fetch_Row_Query(sprintf('select count(userid) from %s where messageid = %d',$tables['linktrack_uml_click'],$id));
$element = ucfirst(s('Clicked'));
$ls->addElement($element);
$ls->addColumn($element,'&nbsp;',!empty($clicked[0]) ? PageLink2('mclicks&id='.$id,$clicked[0]): '0');

$perc = sprintf('%0.2f',$clicked[0] / ($totalSent - $totalBounced) * 100);
$element = ucfirst(s('% Clicked'));
$ls->addElement($element);
$ls->addColumn($element,'&nbsp;',$perc);

$fwded = Sql_Fetch_Row_Query(sprintf('select count(id) from %s where message = %d',$GLOBALS['tables']['user_message_forward'],$id));
$element = ucfirst(s('Forwarded'));
$ls->addElement($element);
$ls->addColumn($element,'&nbsp;',$fwded[0]);

print $ls->display();


