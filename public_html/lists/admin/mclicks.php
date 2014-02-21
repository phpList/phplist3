<?php

# click stats per message
require_once dirname(__FILE__).'/accesscheck.php';

if (isset($_GET['id'])) {
  $id = sprintf('%d',$_GET['id']);
} else {
  $id = 0;
}
$some = 0;

$access = accessLevel('mclicks');
switch ($access) {
  case 'owner':
    $subselect = ' and owner = ' . $_SESSION["logindetails"]["id"];
    if ($id) {
      $allow = Sql_Fetch_Row_query(sprintf('select owner from %s where id = %d %s',$GLOBALS['tables']['message'],$id,$subselect));
      if ($allow[0] != $_SESSION["logindetails"]["id"]) {
        print $GLOBALS['I18N']->get('You do not have access to this page');
        return;
      }
    }
    break;
  case 'all':
    $subselect = '';
    break;
  case 'none':
  default:
    $subselect = ' where id = 0';
    print $GLOBALS['I18N']->get('You do not have access to this page');
    return;
    break;
}

$download = !empty($_GET['dl']);
if ($download) {
  ob_end_clean();
#  header("Content-type: text/plain");
  header('Content-type: text/csv');
  header('Content-disposition:  attachment; filename="phpList Campaign click statistics.csv"');
  ob_start();
}  

if (!$id) {
/*  $req = Sql_Query(sprintf('select distinct messageid, subject, sum(clicked) as totalclicks, count(distinct userid) as users, count(distinct linkid) as linkcount from %s as linktrack, %s as message
    where clicked and linktrack.messageid = message.id %s group by messageid order by entered desc limit 50',
    $GLOBALS['tables']['linktrack'],$GLOBALS['tables']['message'],$subselect));*/
  $req = Sql_Query(sprintf('select distinct messageid, subject,
    sum(total) as total, count(forwardid) as linkcount,sum(clicked) as totalclicks,
    sum(htmlclicked) as htmlclicked,sum(textclicked) as textclicked from %s as linktrack_ml, %s as message
    where clicked and linktrack_ml.messageid = message.id %s  group by messageid order by entered desc limit 50',
    $GLOBALS['tables']['linktrack_ml'],$GLOBALS['tables']['message'],$subselect));
  if (!Sql_Affected_Rows()) {
    print '<p class="information">'.$GLOBALS['I18N']->get('There are currently no messages to view').'</p>';
  }
  $ls = new WebblerListing($GLOBALS['I18N']->get('Available Messages'));
  while ($row = Sql_Fetch_Array($req)) {
    $some = 1;
    $totalusers = Sql_Fetch_Row_Query(sprintf('select count(userid) from %s where messageid = %d and status = "sent"',$GLOBALS['tables']['usermessage'],$row['messageid']));
    $totalclicked = Sql_Fetch_Row_Query(sprintf('select count(distinct userid) from %s where messageid = %d',$GLOBALS['tables']['linktrack_uml_click'],$row['messageid']));
    if ($totalusers[0] > 0) {
      $clickrate = sprintf('%0.2f',($totalclicked[0] / $totalusers[0] * 100));
    } else {
      $clickrate = $GLOBALS['I18N']->get('N/A');
    }
    if (!$download) {
      $element = '<!--'.$row['messageid']. '-->'.shortenTextDisplay($row['subject']);
    } else {
      $element = '<!--'.$row['messageid']. '-->'.$row['subject'];
    }
      
    $ls->addElement($element,PageURL2('mclicks&amp;id='.$row['messageid']));
    $ls->setClass($element,'row1');
    $ls->addColumn($element,$GLOBALS['I18N']->get('links'),$row['linkcount']);
#    $ls->addColumn($element,$GLOBALS['I18N']->get('sent'),$totalusers[0]);
    $ls->addColumn($element,$GLOBALS['I18N']->get('user clicks'),$totalclicked[0]);
    $ls->addColumn($element,$GLOBALS['I18N']->get('clickrate'),$clickrate);
    
    $ls->addColumn($element,$GLOBALS['I18N']->get('total clicks'),$row['totalclicks']);
#    $ls->addColumn($element,$GLOBALS['I18N']->get('total'),$row['total']);
#    $ls->addColumn($element,$GLOBALS['I18N']->get('users'),$row['users']);
    $ls->addRow($element,'','<div class="content listingsmall fright gray">'.$GLOBALS['I18N']->get('html').': '.$row['htmlclicked'].'</div><div class="content listingsmall fright gray">'.$GLOBALS['I18N']->get('text').': '.$row['textclicked'].'</div>');

    /* this one is the percentage of total links versus clicks. I guess that's too detailed for most people.
     * besides it'll be low
    $perc = sprintf('%0.2f',($row['totalclicks'] / $row['total'] * 100));
    $ls->addColumn($element,$GLOBALS['I18N']->get('rate'),$perc.' %');
    */
  }
  if ($some) {
    print '<div class="action">';
    print '<p>'.PageLinkButton('mclicks&dl=true',$GLOBALS['I18N']->get('Download as CSV file')).'</p>';
    print '</div>';
#    print '<p>'.$GLOBALS['I18N']->get('Select Message to view').'</p>';
    print $ls->display();
  }
  if ($download) {
    ob_end_clean();
    print $ls->tabDelimited();
  }
  return;
}

print '<h3>'.$GLOBALS['I18N']->get('Click Details for a Message').'</h3>';
$messagedata = Sql_Fetch_Array_query("SELECT * FROM {$tables['message']} where id = $id $subselect");
$totalusers = Sql_Fetch_Row_Query(sprintf('select count(userid) from %s where messageid = %d and status = "sent"',$GLOBALS['tables']['usermessage'],$id));
$totalbounced = Sql_Fetch_Row_Query(sprintf('select count(user) from %s where message = %d',$GLOBALS['tables']['user_message_bounce'],$id));
$totalclicked = Sql_Fetch_Row_Query(sprintf('select count(distinct userid) from %s where messageid = %d',$GLOBALS['tables']['linktrack_uml_click'],$id));
if (($totalusers[0] - $totalbounced[0]) > 0) {
  $clickperc = sprintf('%0.2f',($totalclicked[0] / ($totalusers[0] - $totalbounced[0]) * 100));
} else {
  $clickperc = $GLOBALS['I18N']->get('N/A');
}
print '<table class="mclicksDetails">
<tr><td>'.$GLOBALS['I18N']->get('Subject').'<td><td>'.$messagedata['subject'].'</td></tr>
<tr><td>'.$GLOBALS['I18N']->get('Entered').'<td><td>'.$messagedata['entered'].'</td></tr>
<tr><td>'.$GLOBALS['I18N']->get('Date sent').'<td><td>'.$messagedata['sent'].'</td></tr>
<tr><td>'.$GLOBALS['I18N']->get('Sent to').'<td><td>'.$totalusers[0].' '.$GLOBALS['I18N']->get('Subscribers').'</td></tr>';
if ($totalusers[0] > 0) {
  print '<tr><td>'.$GLOBALS['I18N']->get('Bounced').'<td><td>'.$totalbounced[0].' (';
  print sprintf('%0.2f',($totalbounced[0] / $totalusers[0] * 100));
  print '%)</td></tr>';
}
print '<tr><td>'.$GLOBALS['I18N']->get('Clicks').'<td><td>'.$totalclicked[0].'</td></tr>
<tr><td>'.$GLOBALS['I18N']->get('Click rate').'<td><td>'.$clickperc.' %</td></tr>
</table><hr/>';

$ls = new WebblerListing($GLOBALS['I18N']->get('Campaign click statistics'));

$req = Sql_Query(sprintf('select url,firstclick,date_format(latestclick,
  "%%e %%b %%Y %%H:%%i") as latestclick,total,clicked,htmlclicked,textclicked,forwardid from %s ml, %s forward  where ml.messageid = %d and ml.forwardid = forward.id',$GLOBALS['tables']['linktrack_ml'],$GLOBALS['tables']['linktrack_forward'],$id));
$summary = array();
$summary['totalclicks'] = 0;
$summary['totalsent'] = 0;
$summary['uniqueclicks'] = 0;
while ($row = Sql_Fetch_Array($req)) {

#  if (CLICKTRACK_SHOWDETAIL) {
    $uniqueclicks = Sql_Fetch_Array_Query(sprintf('select count(distinct userid) as users from %s
      where messageid = %d and forwardid = %d',
      $GLOBALS['tables']['linktrack_uml_click'],$id,$row['forwardid']));
#  }
#  $element = sprintf('<a href="%s" target="_blank" class="url" title="%s">%s</a>',$row['url'],$row['url'],substr(str_replace('http://','',$row['url']),0,50));

  if (!$download) {
    $element = shortenTextDisplay($row['url']);
  } else {
    $element = $row['url'];
  }
  $ls->addElement($element,PageURL2('uclicks&id='.$row['forwardid']));
  $ls->setClass($element,'row1');
#  $ls->addColumn($element,$GLOBALS['I18N']->get('firstclick'),formatDateTime($row['firstclick'],1));
#  $ls->addColumn($element,$GLOBALS['I18N']->get('latestclick'),$row['latestclick']);

## set is confusing, as it is total links sent, not total users sent, https://mantis.phplist.com/view.php?id=17057
## remove
 # $ls->addColumn($element,$GLOBALS['I18N']->get('sent'),$row['total']);
  //$ls->addColumn($element,$GLOBALS['I18N']->get('clicks'),$row['clicked'].'<span class="viewusers"><a class="button" href="'.PageUrl2('userclicks&amp;msgid='.$id.'&amp;fwdid='.$row['forwardid']).'" title="'.$GLOBALS['I18N']->get('view users').'"></a></span>');
  //$perc = sprintf('%0.2f',($row['clicked'] / $row['total'] * 100));
  //$ls->addColumn($element,$GLOBALS['I18N']->get('clickrate'),$perc.'%');
#  if (CLICKTRACK_SHOWDETAIL) {
    $ls->addColumn($element,$GLOBALS['I18N']->get('clicks'),$uniqueclicks['users']);
    $perc = sprintf('%0.2f',($uniqueclicks['users'] / $totalusers[0] * 100));
    $ls->addColumn($element,$GLOBALS['I18N']->get('clickrate'),$perc.'%');
    $summary['uniqueclicks'] += $uniqueclicks['users'];
    
    $moreInfo1 = '
      <div class="content listingsmall fright gray">'.s('html').': '.$row['htmlclicked'].'</div>'.'
      <div class="content listingsmall fright gray">'.s('text').': '.$row['textclicked'].'</div>'.'
    ';
    $moreInfo2 = '
      <div class="content listingsmall fright gray">'.s('firstclick').': '.formatDateTime($row['firstclick']).'</div>'.'
      <div class="content listingsmall fright gray">'.s('latestclick').': '.$row['latestclick'].'</div>'.'
    ';
    
    ## @@TODO the totals for HTML+text will now not match the total clicks
    $ls->addRow($element,$moreInfo1,$moreInfo2);
#  }
  $summary['totalclicks'] += $row['clicked'];
  $summary['totalsent'] += $row['total'];
}
$ls->addElement('total');
$ls->setClass('total','rowtotal');
//$ls->addColumn('total',$GLOBALS['I18N']->get('clicks'),$summary['totalclicks']);
//$perc = sprintf('%0.2f',($summary['totalclicks'] / $summary['totalsent'] * 100));
//$ls->addColumn('total',$GLOBALS['I18N']->get('clickrate'),$perc.'%');
#if (CLICKTRACK_SHOWDETAIL) {
  $ls->addColumn('total',$GLOBALS['I18N']->get('clicks'),$summary['uniqueclicks']);
  $perc = sprintf('%0.2f',($summary['uniqueclicks'] / $totalusers[0] * 100));
  $ls->addColumn('total',$GLOBALS['I18N']->get('clickrate'),$perc.'%');
#}
print $ls->display();
