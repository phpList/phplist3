<?php

// click stats per message
require_once dirname(__FILE__).'/accesscheck.php';

if (isset($_GET['id'])) {
    $id = sprintf('%d', $_GET['id']);
} else {
    $id = 0;
}
$start = 0;
$limit = ' limit 10';
if (isset($_GET['start'])) {
    $start = sprintf('%d', $_GET['start']);
    $limit = ' limit '.$start.', 10';
}

$addcomparison = 0;
$access = accessLevel('statsoverview');
$ownership = '';
$subselect = '';
$paging = '';

//print "Access Level: $access";
switch ($access) {
    case 'owner':
        $ownership = sprintf(' and owner = %d ', $_SESSION['logindetails']['id']);
        if ($id) {
            $allow = Sql_Fetch_Row_query(sprintf('select owner from %s where id = %d %s', $GLOBALS['tables']['message'],
                $id, $ownership));
            if ($allow[0] != $_SESSION['logindetails']['id']) {
                echo $GLOBALS['I18N']->get('You do not have access to this page');

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
        echo $GLOBALS['I18N']->get('You do not have access to this page');

        return;
        break;
}

$download = !empty($_GET['dl']);
if ($download) {
    ob_end_clean();
//  header("Content-type: text/plain");
    header('Content-type: text/csv');
    if (!$id) {
        header('Content-disposition:  attachment; filename="phpList Campaign statistics.csv"');
    }
    ob_start();
}

if (!$id) {

   // print '<iframe id="contentiframe" src="./?page=pageaction&action=statsoverview&ajaxed=true' . addCsrfGetToken() . '" scrolling="no" width="100%" height="500"></iframe>';

    //# for testing the loader allow a delay flag
    if (isset($_GET['delay'])) {
        $_SESSION['LoadDelay'] = sprintf('%d', $_GET['delay']);
    } else {
        unset($_SESSION['LoadDelay']);
    }

    // Load page content via AJAX
    echo '<div id="contentdiv"></div>';
    echo asyncLoadContent('./?page=pageaction&action=statsoverview&ajaxed=true&id='.$id.'&start='.$start.addCsrfGetToken());

    return;
}

//print '<h3>'.$GLOBALS['I18N']->get('Campaign statistics').'</h3>';
echo '<div class="pull-right">'.PageLinkButton('statsoverview', s('View all campaigns')).'</div><div class="clearfix"></div>';

$messagedata = loadMessageData($id);
//var_dump($messagedata);

if (empty($messagedata['subject'])) {
    Error(s('Campaign not found'));

    return;
}

echo '<h3>'.s('Campaign statistics').'</h3>';

$ls = new WebblerListing('');

$ls->setElementHeading($messagedata['campaigntitle']);

$element = ucfirst(s('Subject'));
$ls->addElement($element);
$ls->addColumn($element, '', PageLink2('message&id='.$id, shortenTextDisplay($messagedata['subject'], 30)));

$element = ucfirst(s('Date entered'));
$ls->addElement($element);
$ls->addColumn($element, '', formatDateTime($messagedata['entered']));

$element = ucfirst(s('Date sent'));
$ls->addElement($element);
$ls->addColumn($element, '', formatDateTime($messagedata['sent']));

$element = ucfirst(s('Sent as HTML'));
$ls->addElement($element);
$ls->addColumn($element, '', number_format($messagedata['astextandhtml']));

$element = ucfirst(s('Sent as text'));
$ls->addElement($element);
$ls->addColumn($element, '', number_format($messagedata['astext']));

$totalSent = 0;
$sentQ = Sql_Query(sprintf('select status,count(userid) as num from %s where messageid = %d group by status',
    $tables['usermessage'], $id));
while ($row = Sql_Fetch_Assoc($sentQ)) {
    $element = ucfirst($row['status']);
    $ls->addElement($element);
    $ls->addColumn($element, '',  number_format($row['num']));
    if ($row['status'] == 'sent') {
        $totalSent = $row['num'];
    }
}
/*
$element = ucfirst(s('Bounced'));
$ls->addElement($element);
$ls->addColumn($element,'&nbsp;',$messagedata['bouncecount']);
*/
//Bounced
$bounced = Sql_Fetch_Row_Query(sprintf('select count(distinct user) from %s where message = %d',
    $tables['user_message_bounce'], $id));
$element = ucfirst(s('Bounced'));
$ls->addElement($element);
$ls->addColumn($element, '', number_format ($bounced[0]) );
$totalBounced = $bounced[0];

$viewed = Sql_Fetch_Row_Query(sprintf('select count(userid) from %s where messageid = %d and status = "sent" and viewed is not null',
    $tables['usermessage'], $id));

// Number of views 
$element = ucfirst(s('Opened '));
$ls->addElement($element);
// Opened Rate 
$perc = sprintf('%0.2f', $viewed[0] / ($totalSent - $totalBounced) * 100);
$ls->addColumn($element, '', !empty($viewed[0]) ? PageLink2('mviews&id='.$id, number_format($viewed[0])).' ('. $perc .' %)' : '0');

$clicked = Sql_Fetch_Row_Query(sprintf('select sum(clicked) from %s where messageid= %d',
    $tables['linktrack_ml'], $id));


// Number of Total Clicks
$element = ucfirst(s('Clicked'));
$ls->addElement($element);
// Clicked Rate  
$perc = sprintf('%0.2f', $clicked[0] / ($totalSent - $totalBounced) * 100);
$ls->addColumn($element, '', !empty($clicked[0]) ? PageLink2('mclicks&id='.$id, number_format($clicked[0])).' ('. $perc .' %)': '0');

// Number of Unique Clicks
$uniqueclicked = Sql_Fetch_Row_Query(sprintf('select count( distinct userid) from %s where messageid = %d',
    $tables['linktrack_uml_click'], $id));
$element = ucfirst(s('Unique Clicks'));
// Unique Clicked Rate  
$perc = sprintf('%0.2f', $uniqueclicked[0] / ($totalSent - $totalBounced) * 100);
$ls->addElement($element);
$ls->addColumn($element,'' , !empty($uniqueclicked[0]) ? PageLink2('mclicks&id='.$id, number_format($uniqueclicked[0])).' ('. $perc .' %)' : '0');

// Click per view rate
$element = ucfirst(s('Click Per View Rate'));
$ls->addElement($element); 
if ($viewed[0]!=0) {
    $perc = sprintf('%0.2f', $uniqueclicked[0] / $viewed[0] * 100);
    $ls->addColumn($element, '', $perc.' %');

} else {
    $ls->addColumn($element, '','0');
}

//Forwarded
$fwded = Sql_Fetch_Row_Query(sprintf('select count(id) from %s where message = %d',
    $GLOBALS['tables']['user_message_forward'], $id));
$element = ucfirst(s('Forwarded'));
$ls->addElement($element);
$ls->addColumn($element, '', number_format( $fwded[0] ));

echo $ls->display();
