<?php

# click stats per url
require_once dirname(__FILE__).'/accesscheck.php';

if (isset($_GET['id'])) {
  $id = sprintf('%d',$_GET['id']);
} else {
  $id = 0;
}

$some = 0;
$access = accessLevel('uclicks');
switch ($access) {
  case 'owner':
    $select_tables = $GLOBALS['tables']['linktrack_ml']. ' as ml, '.$GLOBALS['tables']['message'].' as message, '.$GLOBALS['tables']['linktrack_forward']. ' as forward ';
    $owner_and = ' and message.id = ml.messageid and message.owner = '.$_SESSION['logindetails']['id'];
    break;
  case 'all':
    $select_tables = $GLOBALS['tables']['linktrack_ml']. ' as ml, '.$GLOBALS['tables']['linktrack_forward']. ' as forward ';
    $owner_and = '';
    break;
    break;
  case 'none':
  default:
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
    header('Content-disposition:  attachment; filename="phpList URL click statistics.csv"');
  }
  ob_start();
}  

if (!$id) {
  $req = Sql_Query(sprintf('select forward.id,url, sum(clicked) as numclicks, max(latestclick) as lastclicked, count(messageid) as msgs from %s
    where clicked %s and forward.id = ml.forwardid and latestclick > date_sub(now(),interval 12 month) group by url order by latestclick desc limit 50',
    $select_tables,$owner_and));
  $ls = new WebblerListing($GLOBALS['I18N']->get('Available URLs'));
  while ($row = Sql_Fetch_Array($req)) {
    $some = 1;
    $element = shortenTextDisplay($row['url'],15);
    $ls->addElement($element,PageURL2('uclicks&amp;id='.$row['id']));
    $ls->addColumn($element,$GLOBALS['I18N']->get('msgs'),$row['msgs']);
    $ls->addColumn($element,$GLOBALS['I18N']->get('last clicked'),formatDateTime($row['lastclicked'],1));
    $ls->addColumn($element,$GLOBALS['I18N']->get('clicks'),$row['numclicks']);
  }
  if ($download) {
    ob_end_clean();
    print $ls->tabDelimited();
  }
  if ($some) {
    print '<p>'.$GLOBALS['I18N']->get('Select URL to view').'</p>';
    print '<div class="actions">'.PageLinkButton('uclicks&dl=true',$GLOBALS['I18N']->get('Download as CSV file')).'</div>';
    print $ls->display();
  } else {
    print '<p class="information">'.$GLOBALS['I18N']->get('There are currently no statistics available').'</p>';
  }
  return;
}

print '<div class="actions">'.PageLinkButton('uclicks&dl=true&id='.$id,$GLOBALS['I18N']->get('Download as CSV file')).'</div>';

$ls = new WebblerListing($GLOBALS['I18N']->get('URL Click Statistics'));

$urldata = Sql_Fetch_Array_Query(sprintf('select url from %s where id = %d',
  $GLOBALS['tables']['linktrack_forward'],$id));
print '<h3>'.$GLOBALS['I18N']->get('Click Details for a URL').' <b>'.$urldata['url'].'</b></h3><br/>';

if ($download) {
  header('Content-disposition:  attachment; filename="phpList URL click statistics for '.$urldata['url'].'.csv"');
}
$req = Sql_Query(sprintf('select messageid,firstclick,date_format(latestclick,
  "%%e %%b %%Y %%H:%%i") as latestclick,total,clicked from %s where forwardid = %d and firstclick is not null order by firstclick desc
  ',$GLOBALS['tables']['linktrack_ml'],$id));
$summary = array();
$summary['totalsent'] = 0;
$summary['totalclicks'] = 0;
$summary['uniqueclicks'] = 0;

while ($row = Sql_Fetch_Array($req)) {
  $msgsubj = Sql_Fetch_Row_query(sprintf('select subject from %s where id = %d',$GLOBALS['tables']['message'],$row['messageid']));

  $element = shortenTextDisplay($msgsubj[0],30);
#  $element = $GLOBALS['I18N']->get('msg').' '.$row['messageid'].': '.substr($msgsubj[0],0,25). '...';
#  $element = sprintf('<a href="%s" target="_blank" class="url" title="%s">%s</a>',$row['url'],$row['url'],substr(str_replace('http://','',$row['url']),0,50));
#  $total = Sql_Verbose_Query(sprintf('select count(*) as total from %s where messageid = %d and url = "%s"',
#    $GLOBALS['tables']['linktrack'],$id,$row['url']));



  if (CLICKTRACK_SHOWDETAIL) {
    $uniqueclicks = Sql_Fetch_Array_Query(sprintf('select count(distinct userid) as users from %s
      where messageid = %d and forwardid = %d',
      $GLOBALS['tables']['linktrack_uml_click'],$row['messageid'],$id));
  }

  $ls->addElement($element,PageUrl2('mclicks&amp;id='.$row['messageid']));
  $ls->setClass($element,'row1');
  $ls->addColumn($element,$GLOBALS['I18N']->get('firstclick'),formatDateTime($row['firstclick'],1));
  $ls->addColumn($element,$GLOBALS['I18N']->get('latestclick'),$row['latestclick']);
  $ls->addRow($element,'<div class="listingsmall gray">'.$GLOBALS['I18N']->get('sent').': '.$row['total'].'</div>','');
  $ls->addColumn($element,$GLOBALS['I18N']->get('clicks'),$row['clicked'].'<span class="viewusers"><a class="button" href="'.PageUrl2('userclicks&amp;msgid='.$row['messageid'].'&amp;fwdid='.$id.'" title="'.$GLOBALS['I18N']->get('view users').'"></a></span>'));
  $perc = sprintf('%0.2f',($row['clicked'] / $row['total'] * 100));
  $ls->addColumn($element,$GLOBALS['I18N']->get('clickrate'),$perc.'%');
  $summary['totalsent'] += $row['total'];
  if (CLICKTRACK_SHOWDETAIL) {
    $ls->addColumn($element,$GLOBALS['I18N']->get('unique clicks'),$uniqueclicks['users']);
    $perc = sprintf('%0.2f',($uniqueclicks['users'] / $row['total'] * 100));
    $ls->addColumn($element,$GLOBALS['I18N']->get('unique click rate'),$perc.'%');
    $summary['uniqueclicks'] += $uniqueclicks['users'];
  }
  $summary['totalclicks'] += $row['clicked'];
}
$ls->addElement($GLOBALS['I18N']->get('total'));
$ls->setClass($GLOBALS['I18N']->get('total'),'rowtotal');
$ls->addColumn($GLOBALS['I18N']->get('total'),$GLOBALS['I18N']->get('clicks'),$summary['totalclicks']);
$perc = sprintf('%0.2f',($summary['totalclicks'] / $summary['totalsent'] * 100));
$ls->addColumn($GLOBALS['I18N']->get('total'),$GLOBALS['I18N']->get('clickrate'),$perc.'%');
if (CLICKTRACK_SHOWDETAIL) {
  $ls->addColumn($GLOBALS['I18N']->get('total'),$GLOBALS['I18N']->get('unique clicks'),$summary['uniqueclicks']);
  $perc = sprintf('%0.2f',($summary['uniqueclicks'] / $summary['totalsent'] * 100));
  $ls->addColumn($GLOBALS['I18N']->get('total'),$GLOBALS['I18N']->get('unique click rate'),$perc.'%');
}
print $ls->display();
if ($download) {
  ob_end_clean();
  print $ls->tabDelimited();
}

?>
