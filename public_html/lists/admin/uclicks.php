<?php

// click stats per url
require_once dirname(__FILE__).'/accesscheck.php';

if (isset($_GET['id'])) {
    $id = sprintf('%d', $_GET['id']);
} else {
    $id = 0;
}

$some = 0;
$access = accessLevel('uclicks');
switch ($access) {
    case 'owner':
        $select_tables = $GLOBALS['tables']['linktrack_ml'].' as ml, '.$GLOBALS['tables']['message'].' as message, '.$GLOBALS['tables']['linktrack_forward'].' as forward ';
        $owner_and = ' and message.id = ml.messageid and message.owner = '.$_SESSION['logindetails']['id'];
        break;
    case 'all':
        $select_tables = $GLOBALS['tables']['linktrack_ml'].' as ml, '.$GLOBALS['tables']['linktrack_forward'].' as forward ';
        $owner_and = '';
        break;
        break;
    case 'none':
    default:
        print s('You do not have access to this page');

        return;
        break;
}

$download = !empty($_GET['dl']);
if ($download) {
    ob_end_clean();
//  header("Content-type: text/plain");
    header('Content-type: text/csv');
    if (!$id) {
        header('Content-disposition:  attachment; filename="phpList URL click statistics.csv"');
    }
    ob_start();
}

if (!$id) {
    $req = Sql_Query(sprintf('
        SELECT forward.id,
        url,
        SUM(clicked) AS numclicks,
        MAX(latestclick) AS lastclicked,
        COUNT(messageid) AS msgs
        FROM %s
        WHERE clicked %s AND forward.id = ml.forwardid AND latestclick > DATE_SUB(NOW(),INTERVAL 12 MONTH)
        GROUP BY forward.id
        ORDER BY lastclicked DESC
        LIMIT 50',
        $select_tables, $owner_and));
    $ls = new WebblerListing(s('Available URLs'));
    while ($row = Sql_Fetch_Array($req)) {
        $some = 1;
        if (!$download) {
            $element = shortenTextDisplay($row['url'], 30);
        } else {
            $element = $row['url'];
        }
        $ls->addElement($element, PageURL2('uclicks&amp;id='.$row['id']));
        $ls->addColumn($element, s('msgs'), $row['msgs']);
        $ls->addColumn($element, s('last clicked'), formatDateTime($row['lastclicked'], 1));
        $ls->addColumn($element, s('clicks'), $row['numclicks']);
    }
    if ($download) {
        ob_end_clean();
        echo $ls->tabDelimited();
    }
    if ($some) {
        echo '<p>'.s('Select URL to view').'</p>';
        echo '<div class="actions pull-right">'.PageLinkButton('uclicks&dl=true',
                s('Download as CSV file')).'</div><div class="clearfix"></div>';
        echo $ls->display();
    } else {
        echo '<p class="information">'.s('There are currently no statistics available').'</p>';
    }

    return;
}

echo '<div class="actions">'.PageLinkButton('uclicks&dl=true&id='.$id,
        s('Download as CSV file')).'</div>';

$ls = new WebblerListing(s('URL Click Statistics'));

$urldata = Sql_Fetch_Array_Query(sprintf('select url from %s where id = %d',
    $GLOBALS['tables']['linktrack_forward'], $id));
echo '<h3>'.s('Click details for a URL').': <b><a href="'.htmlspecialchars( $urldata['url'] ).'" target="_blank"><span aria-hidden="true" class="glyphicon glyphicon-new-window"></span> '.htmlspecialchars( $urldata['url'] ).'</a></b></h3><br/>';
echo PageLinkButton('userclicks&fwdid='.$id, s('View subscribers'));
if ($download) {
    header('Content-disposition:  attachment; filename="phpList URL click statistics for '.$urldata['url'].'.csv"');
}
$req = Sql_Query(sprintf('select messageid,firstclick,latestclick,total,clicked
    from %s where forwardid = %d and firstclick is not null order by firstclick desc',
    $GLOBALS['tables']['linktrack_ml'], $id));
$summary = array();
$summary['totalsent'] = 0;
$summary['totalclicks'] = 0;
$summary['uniqueclicks'] = 0;

while ($row = Sql_Fetch_Array($req)) {
    $messagedata = loadMessageData($row['messageid']);
    if (!$download) {
        if ($messagedata['subject'] != $messagedata['campaigntitle']) {
            $element = '<!--'.$row['messageid'].'-->'.stripslashes($messagedata['campaigntitle']).'<br/><strong>'.shortenTextDisplay($messagedata['subject'],
                    30).'</strong>';
        } else {
            $element = '<!--'.$row['messageid'].'-->'.shortenTextDisplay($messagedata['subject'], 30);
        }
    } else {
        $element = $messagedata['subject'];
    }
//  $element = s('msg').' '.$row['messageid'].': '.substr($msgsubj[0],0,25). '...';
//  $element = sprintf('<a href="%s" target="_blank" class="url" title="%s">%s</a>',$row['url'],$row['url'],substr(str_replace('http://','',$row['url']),0,50));
//  $total = Sql_Verbose_Query(sprintf('select count(*) as total from %s where messageid = %d and url = "%s"',
//    $GLOBALS['tables']['linktrack'],$id,$row['url']));

    // if (CLICKTRACK_SHOWDETAIL) {
    $uniqueclicks = Sql_Fetch_Array_Query(sprintf('select count(distinct userid) as users from %s
      where messageid = %d and forwardid = %d',
        $GLOBALS['tables']['linktrack_uml_click'], $row['messageid'], $id));
//  }

    $ls->addElement($element, PageUrl2('mclicks&amp;id='.$row['messageid']));
    $ls->setClass($element, 'row1');
    $ls->addColumn($element, s('firstclick'), formatDateTime($row['firstclick'], 1));
    $ls->addColumn($element, s('latestclick'), formatDateTime($row['latestclick'], 1));
    $ls->addRow($element,
        '<div class="listingsmall gray">'.s('sent').': '.$row['total'].'</div>', '');
//  $ls->addColumn($element,s('clicks'),$row['clicked'].'<span class="viewusers"><a class="button" href="'.PageUrl2('userclicks&amp;msgid='.$row['messageid'].'&amp;fwdid='.$id.'" title="'.s('view users').'"></a></span>'));
//  $perc = sprintf('%0.2f',($row['clicked'] / $row['total'] * 100));
// $ls->addColumn($element,s('clickrate'),$perc.'%');
    $summary['totalsent'] += $row['total'];
//  if (CLICKTRACK_SHOWDETAIL) {
    $ls->addColumn($element, s('clicks'),
        number_format($uniqueclicks['users']).'<span class="viewusers"><a class="button" href="'.PageUrl2('userclicks&amp;msgid='.$row['messageid'].'&amp;fwdid='.$id).'" title="'.s('view subscribers who clicked').'"></a></span>');
    $perc = sprintf('%0.2f', ($uniqueclicks['users'] / $row['total'] * 100));
    $ls->addColumn($element, s('click rate'), $perc.'%');
    $summary['uniqueclicks'] += $uniqueclicks['users'];
//  }
    $summary['totalclicks'] += $row['clicked'];
}
$ls->addElement(s('total'));
$ls->setClass(s('total'), 'rowtotal');
//$ls->addColumn(s('total'),s('clicks'),$summary['totalclicks']);
//$perc = sprintf('%0.2f',($summary['totalclicks'] / $summary['totalsent'] * 100));
//$ls->addColumn(s('total'),s('clickrate'),$perc.'%');
//if (CLICKTRACK_SHOWDETAIL) {
$ls->addColumn(s('total'), s('clicks'), $summary['uniqueclicks']);
$perc = sprintf('%0.2f', ($summary['uniqueclicks'] / $summary['totalsent'] * 100));
$ls->addColumn(s('total'), s('click rate'), $perc.'%');
//}
echo $ls->display();
if ($download) {
    ob_end_clean();
    echo $ls->tabDelimited();
}
