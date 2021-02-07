<?php

// click stats per message
require_once dirname(__FILE__).'/accesscheck.php';

if (isset($_GET['id'])) {
    $id = sprintf('%d', $_GET['id']);
} else {
    $id = 0;
}
$some = 0;

$access = accessLevel('mclicks');
switch ($access) {
    case 'owner':
        $subselect = ' and owner = '.$_SESSION['logindetails']['id'];
        if ($id) {
            $allow = Sql_Fetch_Row_query(sprintf('select owner from %s where id = %d %s', $GLOBALS['tables']['message'],
                $id, $subselect));
            if ($allow[0] != $_SESSION['logindetails']['id']) {
                echo s('You do not have access to this page');

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
        echo s('You do not have access to this page');

        return;
        break;
}

$download = !empty($_GET['dl']);
if ($download) {
    ob_end_clean();
    header('Content-type: text/csv');
    header('Content-disposition:  attachment; filename="phpList Campaign click statistics.csv"');
    ob_start();
}

// If and ID is provided, and we're viewing details for a specific campaign, load an ajaxed page version
if (!$id) {
    echo '<div id="contentdiv"></div>';
    echo asyncLoadContent('./?page=pageaction&action=mclicks&ajaxed=true&id='.$id.addCsrfGetToken());

    return;
}

echo
    '<div class="actions pull-right">'.PageLinkButton(
        'mclicks&id='.$id.'&dl=1'
        , s('Download as CSV file')
    ).'</div><div class="clearfix"></div>';

echo '<h3>'.s('Click Details for a Message').'</h3>';
$messagedata = Sql_Fetch_Array_query("SELECT * FROM {$tables['message']} where id = $id $subselect");
$totalusers = Sql_Fetch_Row_Query(sprintf('select count(userid) from %s where messageid = %d and status = "sent"',
    $GLOBALS['tables']['usermessage'], $id));
$totalbounced = Sql_Fetch_Row_Query(sprintf('select count(user) from %s where message = %d',
    $GLOBALS['tables']['user_message_bounce'], $id));
//unique clicks
$totalclicked = Sql_Fetch_Row_Query(sprintf('select count(distinct userid) from %s where messageid = %d',
    $GLOBALS['tables']['linktrack_uml_click'], $id));
//total clicks
$totalclicks = Sql_Fetch_Row_Query(sprintf('select sum(clicked) from %s where messageid = %d',
    $GLOBALS['tables']['linktrack_ml'], $id));
if (($totalusers[0] - $totalbounced[0]) > 0) {
    $clickperc = sprintf('%0.2f', ($totalclicked[0] / ($totalusers[0] - $totalbounced[0]) * 100));
} else {
    $clickperc = $GLOBALS['I18N']->get('N/A');
}

echo '<table class="mclicksDetails">
<tr><td>' .s('Subject').'</td><td>'.$messagedata['subject'].'</td></tr>
<tr><td>' .s('Entered').'</td><td>'.formatDateTime($messagedata['entered']).'</td></tr>
<tr><td>' .s('Date sent').'</td><td>'.formatDateTime($messagedata['sent']).'</td></tr>
<tr><td>' .s('Sent to').'</td><td>'.number_format($totalusers[0]).' '.s('Subscribers').'</td></tr>';
if ($totalusers[0] > 0) {
    echo '<tr><td>'.s('Bounced').'</td><td>'.number_format($totalbounced[0]).' (';
    echo sprintf('%0.2f', ($totalbounced[0] / $totalusers[0] * 100));
    echo '%)</td></tr>';
}
echo '<tr><td>'.s('Unique subscribers who clicked').'</td><td>'.number_format($totalclicked[0]).' <span style="margin-left:10px">'.PageLinkButton('userclicks&msgid='.$id,
        s('View subscribers')).'</span></td></tr>
<tr><td>' .s('Total clicks').'</td><td>'.number_format($totalclicks[0]).' </td></tr>
<tr><td>' .s('Click rate').'</td><td>'.$clickperc.' %</td></tr>


</table><hr/>';

$ls = new WebblerListing(s('Campaign click statistics'));
$ls->setElementHeading(s('Link URL'));

$query = '
    SELECT
        url,
        firstclick,
        latestclick,
        total,
        clicked,
        htmlclicked,
        textclicked,
        forwardid
    FROM
        '.$GLOBALS['tables']['linktrack_ml'].' ml,
        '.$GLOBALS['tables']['linktrack_forward'].' forward
    WHERE
        ml.messageid = '.sprintf($id, '%d').'
        AND ml.forwardid = forward.id';

$req = Sql_Query($query);
//# put a limit on (when there are too many distinct URLs, eg when personalised URLs have been used)
$num = Sql_Affected_Rows();
if ($num > 50) {
    $query .= ' and total > 0 limit 50';
    $req = Sql_Query($query);
}

$summary = array();
$summary['totalclicks'] = 0;
$summary['totalsent'] = 0;
$summary['uniqueclicks'] = 0;

while ($row = Sql_Fetch_Array($req)) {

    $uniqueclicks = Sql_Fetch_Array_Query('
        SELECT
            COUNT(DISTINCT userid) AS users
        FROM
            '.$GLOBALS['tables']['linktrack_uml_click'].'
        WHERE
            messageid = '.sprintf($id, '%d').'
            AND forwardid = '.sprintf($row['forwardid'], '%d')
    );

    if (!$download) {
        $element = shortenTextDisplay($row['url']);
    } else {
        $element = $row['url'];
    }
    $ls->addElement($element, PageURL2('uclicks&id='.$row['forwardid']));
    $ls->setClass($element, 'row1');
//  $ls->addColumn($element,$GLOBALS['I18N']->get('firstclick'),formatDateTime($row['firstclick'],1));
//  $ls->addColumn($element,$GLOBALS['I18N']->get('latestclick'),$row['latestclick']);

//# set is confusing, as it is total links sent, not total users sent, https://mantis.phplist.com/view.php?id=17057
//# remove
    // $ls->addColumn($element,$GLOBALS['I18N']->get('sent'),$row['total']);
    //$ls->addColumn($element,$GLOBALS['I18N']->get('clicks'),$row['clicked'].'<span class="viewusers"><a class="button" href="'.PageUrl2('userclicks&amp;msgid='.$id.'&amp;fwdid='.$row['forwardid']).'" title="'.$GLOBALS['I18N']->get('view users').'"></a></span>');
    //$perc = sprintf('%0.2f',($row['clicked'] / $row['total'] * 100));
    //$ls->addColumn($element,$GLOBALS['I18N']->get('clickrate'),$perc.'%');
//  if (CLICKTRACK_SHOWDETAIL) {

    $ls->addColumn(
        $element
        , s('unique clicks')
        , number_format($uniqueclicks['users']).'<span class="viewusers"><a class="button" href="'.PageUrl2('userclicks&amp;msgid='.$id.'&amp;fwdid='.$row['forwardid']).'" title="'.s('view subscribers who clicked').'"></a></span>');

    $perc = sprintf('%0.2f', ($uniqueclicks['users'] / $totalusers[0] * 100));
    $ls->addColumn($element, s('clickrate'), $perc.'%');
    $summary['uniqueclicks'] += $uniqueclicks['users'];

    $moreInfo1 = '
      <div class="content listingsmall fright gray">' .s('html').': '.number_format($row['htmlclicked']).'</div>'.'
      <div class="content listingsmall fright gray">' .s('text').': '.number_format($row['textclicked']).'</div>'.'
    ';
    $moreInfo2 = '
      <div class="content listingsmall fright gray">' .s('firstclick').': '.formatDateTime($row['firstclick']).'</div>'.'
      <div class="content listingsmall fright gray">' .s('latestclick').': '.formatDateTime($row['latestclick']).'</div>'.'
    ';

    //# @@TODO the totals for HTML+text will now not match the total clicks
    $ls->addRow($element, $moreInfo1, $moreInfo2);
//  }
    $summary['totalclicks'] += $row['clicked'];
    $summary['totalsent'] += $row['total'];
}
$ls->addElement('Total');
$ls->setClass('Total', 'rowtotal');
$ls->addColumn('Total', s('unique clicks'), number_format($summary['uniqueclicks']));
$perc = sprintf('%0.2f', ($summary['uniqueclicks'] / $totalusers[0] * 100));
$ls->addColumn('Total', s('clickrate'), $perc.'%');

if ($download) {
    ob_end_clean();
    $status .= $ls->tabDelimited();
}

echo $ls->display();
