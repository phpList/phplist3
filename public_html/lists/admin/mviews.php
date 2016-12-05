<?php

# click stats per message
require_once dirname(__FILE__) . '/accesscheck.php';

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
#print "Access level: $access";
switch ($access) {
    case 'owner':
        $subselect = ' and owner = ' . $_SESSION['logindetails']['id'];
        if ($id) {
            $allow = Sql_Fetch_Row_query(sprintf('select owner from %s where id = %d %s', $GLOBALS['tables']['message'],
                $id, $subselect));
            if ($allow[0] != $_SESSION['logindetails']['id']) {
                print $GLOBALS['I18N']->get('You do not have access to this page');

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
        print $GLOBALS['I18N']->get('You do not have access to this page');

        return;
        break;
}

$download = !empty($_GET['dl']);

if (!$id) {
    print '<div id="contentdiv"></div>';
    print asyncLoadContent('./?page=pageaction&action=mviews&ajaxed=true&id='.$id.'&start='.$start . addCsrfGetToken());

    return;
}

if ($download) {
    ob_end_clean();
#  header("Content-type: text/plain");
    header('Content-type: text/csv');
    ob_start();
}
if (empty($start)) {
    print '<p>' . PageLinkButton('mviews&dl=true&id=' . $id . '&start=' . $start,
            $GLOBALS['I18N']->get('Download as CSV file')) . '</p>';
}

#print '<h3>'.$GLOBALS['I18N']->get('View Details for a Message').'</h3>';
$messagedata = Sql_Fetch_Array_query("SELECT * FROM {$tables['message']} where id = $id $subselect");
print '<table class="mviewsDetails">
<tr><td>' . $GLOBALS['I18N']->get('Subject') . '<td><td>' . $messagedata['subject'] . '</td></tr>
<tr><td>' . $GLOBALS['I18N']->get('Entered') . '<td><td>' . $messagedata['entered'] . '</td></tr>
<tr><td>' . $GLOBALS['I18N']->get('Sent') . '<td><td>' . $messagedata['sent'] . '</td></tr>
</table><hr/>';

if ($download) {
    header('Content-disposition:  attachment; filename="phpList Message open statistics for ' . $messagedata['subject'] . '.csv"');
}

$ls = new WebblerListing(ucfirst($GLOBALS['I18N']->get('Open statistics')));

$req = Sql_Query(sprintf('select um.userid
    from %s um,%s msg where um.messageid = %d and um.messageid = msg.id and um.viewed is not null %s
    group by userid',
    $GLOBALS['tables']['usermessage'], $GLOBALS['tables']['message'], $id, $subselect));

$total = Sql_Affected_Rows();
if (isset($start) && $start > 0) {
    $listing = sprintf($GLOBALS['I18N']->get('Listing user %d to %d'), $start, $start + MAX_USER_PP);
    $limit = "limit $start," . MAX_USER_PP;
} else {
    $listing = sprintf($GLOBALS['I18N']->get('Listing user %d to %d'), 1, MAX_USER_PP);
    $limit = 'limit 0,' . MAX_USER_PP;
    $start = 0;
    $limit = 'limit 0,' . MAX_USER_PP;
}

## hmm, this needs more work, as it'll run out of memory, because it's building the entire
## listing before pushing it out.
## would be best to not have a limit, but putting one to avoid that
if ($download) {
    $limit = ' limit 100000';
}

if ($id) {
    $url_keep = '&amp;id=' . $id;
} else {
    $url_keep = '';
}

if ($total) {
    $paging = simplePaging("mviews$url_keep", $start, $total, MAX_USER_PP, $GLOBALS['I18N']->get('Entries'));
    $ls->usePanel($paging);
}

$req = Sql_Query(sprintf('select userid,email,um.entered as sent,min(um.viewed) as firstview,
    max(um.viewed) as lastview, count(um.viewed) as viewcount,
    abs(unix_timestamp(um.entered) - unix_timestamp(um.viewed)) as responsetime
    from %s um, %s user, %s msg where um.messageid = %d and um.messageid = msg.id and um.userid = user.id and um.status = "sent" and um.viewed is not null %s
    group by userid %s',
    $GLOBALS['tables']['usermessage'], $GLOBALS['tables']['user'], $GLOBALS['tables']['message'], $id, $subselect,
    $limit));

$summary = array();
while ($row = Sql_Fetch_Array($req)) {
    if ($download) {
        ## with download, the 50 per page limit is not there.
        set_time_limit(60);
        $element = $row['email'];
    } else {
        $element = shortenTextDisplay($row['email'], 15);
    }
    $ls->addElement($element, PageUrl2('userhistory&amp;id=' . $row['userid']));
    $ls->setClass($element, 'row1');
    $ls->addRow($element,
        '<div class="listingsmall gray">' . $GLOBALS['I18N']->get('sent') . ': ' . formatDateTime($row['sent'],
            1) . '</div>', '');
    if ($row['viewcount'] > 1) {
        $ls->addColumn($element, $GLOBALS['I18N']->get('firstview'), formatDateTime($row['firstview'], 1));
        $ls->addColumn($element, $GLOBALS['I18N']->get('lastview'), formatDateTime($row['lastview']));
        $ls->addColumn($element, $GLOBALS['I18N']->get('views'), $row['viewcount']);
    } else {
        $ls->addColumn($element, $GLOBALS['I18N']->get('firstview'), formatDateTime($row['firstview'], 1));
        $ls->addColumn($element, $GLOBALS['I18N']->get('responsetime'), secs2time($row['responsetime']));
    }
}
if ($download) {
    ob_end_clean();
    print $ls->tabDelimited();
} else {
    print $ls->display();
}
