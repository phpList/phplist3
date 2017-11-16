<?php

// click stats per message
require_once dirname(__FILE__).'/accesscheck.php';

if (isset($_GET['id'])) {
    $id = sprintf('%d', $_GET['id']);
} else {
    $id = 0;
}
if (isset($_GET['start'])) {
    $start = sprintf('%d', $_GET['start']);
} else {
    $start = 0;
}

$addcomparison = 0;
$access = accessLevel('mviews');
//print "Access level: $access";
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
        $addcomparison = 1;
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

if (!$id) {
    echo '<div id="contentdiv"></div>';
    echo asyncLoadContent('./?page=pageaction&action=mviews&ajaxed=true&id='.$id.'&start='.$start.addCsrfGetToken());

    return;
}

if ($download) {
    ob_end_clean();
//  header("Content-type: text/plain");
    header('Content-type: text/csv');
    ob_start();
}
if (empty($start)) {
    echo '<p class="pull-right">'.PageLinkButton('mviews&dl=true&id='.$id.'&start='.$start,
            s('Download as CSV file')).'</p><div class="clearfix"></div>';
}

//print '<h3>'.s('View Details for a Message').'</h3>';
$messagedata = Sql_Fetch_Array_query("SELECT * FROM {$tables['message']} where id = $id $subselect");
echo '<table class="mviewsDetails">
<tr><td>' .s('Subject').'<td><td>'.$messagedata['subject'].'</td></tr>
<tr><td>' .s('Entered').'<td><td>'.$messagedata['entered'].'</td></tr>
<tr><td>' .s('Sent').'<td><td>'.$messagedata['sent'].'</td></tr>
</table><hr/>';

if ($download) {
    header('Content-disposition:  attachment; filename="phpList Message open statistics for '.$messagedata['subject'].'.csv"');
}

$ls = new WebblerListing(s('Open statistics'));
$ls->setElementHeading(s('Subscriber'));

$req = Sql_Query(sprintf('select um.userid
    from %s um,%s msg where um.messageid = %d and um.messageid = msg.id and um.viewed is not null %s
    group by userid',
    $GLOBALS['tables']['usermessage'], $GLOBALS['tables']['message'], $id, $subselect));

$total = Sql_Affected_Rows();
if (isset($start) && $start > 0) {
    $listing = s('Listing user %d to %d', $start, $start + MAX_OPENS_PP);
    $limit = "limit $start,".MAX_OPENS_PP;
} else {
    $listing = sprintf(s('Listing user %d to %d'), 1, MAX_OPENS_PP);
    $limit = 'limit 0,'.MAX_OPENS_PP;
    $start = 0;
    $limit = 'limit 0,'.MAX_OPENS_PP;
}

//# hmm, this needs more work, as it'll run out of memory, because it's building the entire
//# listing before pushing it out.
//# would be best to not have a limit, but putting one to avoid that
if ($download) {
    $limit = ' limit 100000';
}

if ($id) {
    $url_keep = '&amp;id='.$id;
} else {
    $url_keep = '';
}

if ($total) {
    $paging = simplePaging("mviews$url_keep", $start, $total, MAX_OPENS_PP, s('Entries'));
    $ls->usePanel($paging);
}

$req = Sql_Query(
    sprintf(
        'select 
            userid
            , email
            , um.entered as sent
            , min(um.viewed) as firstview
            , max(um.viewed) as lastview
            , count(um.viewed) as viewcount
            , abs(unix_timestamp(um.entered) - unix_timestamp(um.viewed)) as responsetime
        from 
            %s um
            , %s user
            , %s msg 
        where 
            um.messageid = %d 
            and um.messageid = msg.id 
            and um.userid = user.id 
            and um.status = "sent" 
            and um.viewed is not null 
            %s
        group by 
            userid
        order by
            lastview desc 
        %s'
        , $GLOBALS['tables']['usermessage']
        , $GLOBALS['tables']['user']
        , $GLOBALS['tables']['message']
        , $id
        , $subselect
        , $limit
    )
);

$summary = array();
while ($row = Sql_Fetch_Array($req)) {
    if ($download) {
        //# with download, the 50 per page limit is not there.
        set_time_limit(60);
        $element = $row['email'];
    } else {
        $element = shortenTextDisplay($row['email'], 15);
    }
    $ls->addElement($element, PageUrl2('userhistory&amp;id='.$row['userid']));
    $ls->setClass($element, 'row1');
    $viewList = '';
    if ($row['viewcount'] > 1) { // that will never happen as usermessage only has one entry per user-message
        $ls->addColumn($element, s('firstview'), formatDateTime($row['firstview'], 1));
        $ls->addColumn($element, s('lastview'), formatDateTime($row['lastview']));
        $ls->addColumn($element, s('views'), $row['viewcount']);
    } else {
        $ls->addColumn($element, s('firstview'), formatDateTime($row['firstview'], 1));
        $ls->addColumn($element, s('Response time'), secs2time($row['responsetime']));

        if (TRACK_TOTAL_VIEWS_PER_SUBSCRIBER) {
            $allViewsReq = Sql_Query(sprintf('select * from %s where userid = %d and messageid = %d order by viewed',$GLOBALS['tables']['user_message_view'],$row['userid'],$id));
            $totalViews = Sql_Affected_Rows();

            if ($totalViews > 1) {
                $viewList = '';
                while ($row2 = Sql_Fetch_Assoc($allViewsReq)) {
                    $viewList .= formatDateTime($row2['viewed'], 1) . '<br/>';
                }

//                $ls->addRow($element, '',
//                    '<div class="listingsmall gray">Total views ' . $totalViews . '<br/>' . $viewList . '</div>');
            }
            $viewList = s('Total views').' ' . $totalViews . '<br/>' . $viewList;
        }

    }
    $ls->addRow(
        $element
        , '<div class="listingsmall gray">'.s('sent').': '.formatDateTime($row['sent'], 1).'</div>'
        , $viewList
    );
}
if ($download) {
    ob_end_clean();
    echo $ls->tabDelimited();
} else {
    echo $ls->display();
}
