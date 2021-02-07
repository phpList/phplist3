<?php

require_once dirname(__FILE__).'/accesscheck.php';

$result = '';

if (isset($_REQUEST['delete']) && $_REQUEST['delete']) {
    $result .= s('deleting bounce %d', $_REQUEST['delete'])."..\n";
    if (!isSuperUser()) {
    } else {
        deleteBounce($_REQUEST['delete']);
    }
    $result .= $GLOBALS['I18N']->get('done');
    echo ActionResult($result);
}

$tabs = new WebblerTabs();
$tabs->addTab(s('processed'), PageUrl2('bounces&tab=processed'), 'processed');
$tabs->addTab(s('unidentified'), PageUrl2('bounces&tab=unidentified'), 'unidentified');

if (!isset($_GET['tab'])) {
    $_GET['tab'] = 'processed';
}
$currentTab = 'processed';
switch ($_GET['tab']) {
    case 'unidentified':
        $status_compare = '=';
        $status = 'unidentified';
        $currentTab = 'unidentified';
        break;
    case 'processed':
    default:
        $status_compare = '!=';
        $status = 'processed';
        break;
}
$tabs->setCurrent($currentTab);

if (ALLOW_DELETEBOUNCE && isset($_GET['action']) && $_GET['action']) {
    verifyCsrfGetToken();
    switch ($_GET['action']) {
        case 'deleteunidentified':
            $req = Sql_Query(sprintf('delete from %s where status = "unidentified bounce" and date_add(date,interval 2 month) < now()',
                $tables['bounce']));
            $count = Sql_Affected_Rows($req);
            $actionresult = s('%d unidentified bounces older than 2 months have been deleted', $count);
            break;
        case 'deleteprocessed':
            $req = Sql_Query(sprintf('delete from %s where comment != "not processed" and date_add(date,interval 2 month) < now()',
                $tables['bounce']));
            $count = Sql_Affected_Rows($req);
            $actionresult = s('%d processed bounces older than 2 months have been deleted', $count);
            break;
        case 'deleteall':
            Sql_Query(sprintf('truncate %s', $tables['bounce']));
            $actionresult = s('All bounces have been deleted');
            break;
        case 'reset':
            Sql_Query(sprintf('update %s set bouncecount = 0', $tables['user']));
            Sql_Query(sprintf('update %s set bouncecount = 0', $tables['message']));
            Sql_Query(sprintf('truncate %s', $tables['bounce']));
            Sql_Query(sprintf('truncate %s', $tables['user_message_bounce']));
    }
}

// view bounces
$count = Sql_Query(sprintf('select count(*) from %s where status '.$status_compare.' "unidentified bounce"',
    $tables['bounce']));
$totalres = Sql_fetch_Row($count);
$total = $totalres[0];
$find_url = '';
if (isset($_GET['start'])) {
    $start = sprintf('%d', $_GET['start']);
} else {
    $start = 0;
}
$offset = $start;
$baseurl = "bounces&start=$start&tab=$currentTab";
$limit = MAX_USER_PP;
if (!empty($actionresult)) {
    $_SESSION['action_result'] = $actionresult;
    header('Location: ./?page='.$baseurl);
    exit;
//  print '<div id="actionresult" class="result">'.$actionresult .'</div>';
}

if ($total > MAX_USER_PP) {
    $paging = simplePaging("bounces&amp;tab=$currentTab", $start, $total, MAX_USER_PP,
        $status.' '.$GLOBALS['I18N']->get('bounces'));
    $query = sprintf('
        select
            *
        from
            %s
        where
            status %s "unidentified bounce"
        order by
            date desc
        limit
            %s
        offset
            %s'
        , $tables['bounce']
        , $status_compare
        , $limit
        , $offset
    );
    $result = Sql_Query($query);
} else {
    $paging = '';
    $query = sprintf('
        select
            *
        from
            %s
        where
            status '.$status_compare.' "unidentified bounce"
        order by
            date desc'
        , $tables['bounce']
    );
    $result = Sql_Query($query);
}

$buttons = new ButtonGroup(new Button(PageURL2('bounces'), s('delete')));
$buttons->addButton(
    new ConfirmButton(
        $GLOBALS['I18N']->get('are you sure you want to delete all unidentified bounces older than 2 months').'?',
        PageURL2("$baseurl&action=deleteunidentified"),
        $GLOBALS['I18N']->get('delete all unidentified (&gt; 2 months old)')));
$buttons->addButton(
    new ConfirmButton(
        $GLOBALS['I18N']->get('are you sure you want to delete all bounces older than 2 months').'?',
        PageURL2("$baseurl&action=deleteprocessed"),
        $GLOBALS['I18N']->get('delete all processed (&gt; 2 months old)')));
$buttons->addButton(
    new ConfirmButton(
        $GLOBALS['I18N']->get('are you sure you want to delete all bounces').'?',
        PageURL2("$baseurl&action=deleteall"),
        $GLOBALS['I18N']->get('Delete all')));

echo "<div class='actions'>\n";
echo "<div class='minitabs'>\n";
echo $tabs->display();
echo "</div>\n";

echo '<span class="pull-right">'.PageLinkButton('listbounces', $GLOBALS['I18N']->get('view bounces by list')).'</span>';
if (ALLOW_DELETEBOUNCE) {

    echo '<div class="fright pull-left">'.$buttons->show().'</div>';
}
echo "</div><!-- .actions div-->\n";
echo '<div class="clearfix"></div>';

if (!Sql_Num_Rows($result)) {
    switch ($status) {
        case 'unidentified':
            print '<p class="information">'.s('no unidentified bounces available').'</p>';
            break;
        case 'processed':
            print '<p class="information">'.s('no processed bounces available').'</p>';
            break;

    }
}

$ls = new WebblerListing(s($status).' '.s('bounces'));
$ls->setElementHeading('Bounce ID');
$ls->usePanel($paging);
while ($bounce = Sql_fetch_array($result)) {
    //@@@ not sure about these ones - bounced list message
    $element = $bounce['id'];
    $ls->addElement($element, PageUrl2('bounce&type='.$status.'&id='.$bounce['id']));
    if (preg_match("#bounced list message ([\d]+)#", $bounce['status'], $regs)) {
        $messageid = PageLink2('message&id='.$regs[1], shortenTextDisplay(campaignTitle($regs[1]),
            30)); //sprintf('<a href="./?page=message&amp;id=%d">%d</a>',$regs[1],$regs[1]);
    } elseif ($bounce['status'] == 'bounced system message') {
        $messageid = $GLOBALS['I18N']->get('System Message');
    } else {
        $messageid = $GLOBALS['I18N']->get('Unknown');
    }

    /*  if (preg_match('/Action: delayed\s+Status: 4\.4\.7/im',$bounce["data"])) {
        $ls->addColumn($element,'delayed',$GLOBALS['img_tick']);
      } else {
        $ls->addColumn($element,'delayed',$GLOBALS['img_cross']);
      }
    */
    $ls->addColumn($element, s('Campaign'), $messageid);


    if (
        preg_match("#([\d]+) bouncecount increased#", $bounce['comment'], $regs)
        OR preg_match("#([\d]+) marked unconfirmed#", $bounce['comment'], $regs)
    ) {
        // Fetch additional data to be able to print subscriber address
        $userdata = Sql_Fetch_Array_Query(
            sprintf('
                select
                    *
                from
                    %s
                where
                    id = %d'
                , $GLOBALS['tables']['user']
                , $regs[1]
            )
        );
        $userIdLink = PageLink2('user&id='.$regs[1], $userdata['email']);
    } else {
        $userIdLink = $GLOBALS['I18N']->get('Unknown');
    }

    $ls->addColumn($element, $GLOBALS['I18N']->get('user'), $userIdLink);
    $ls->addColumn($element, $GLOBALS['I18N']->get('date'), formatDateTime($bounce['date']));

    /*
      printf( "<tr><td>[ <a href=\"javascript:deleteRec('%s');\">%s</a> |
       %s ] </td><td>%s</td><td>%s</td><td>%s</td></tr>\n",
       PageURL2("bounces",$GLOBALS['I18N']->get('delete'),"s=$start&amp;delete=".$bounce["id"]),
       $GLOBALS['I18N']->get('delete'),
       PageLinkButton("bounce",$GLOBALS['I18N']->get('Show'),"s=$start&amp;id=".$bounce["id"]),
       $messageid,
       $userIdLink,
       $bounce["date"]
       );
    */
}
//print "</table>";
echo $ls->display();
