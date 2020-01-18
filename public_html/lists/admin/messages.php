<?php

require_once dirname(__FILE__).'/accesscheck.php';

$subselect = $whereClause = '';
$action_result = '';
$access = accessLevel('messages');

$messageSortOptions = array(
    'subjectasc'  => array(
        // caption for drop-down list
        'label' => s('Subject').' - '.s('Ascending'),
        // sql order by
        'orderby' => 'subject asc'
    ),
    'subjectdesc'  => array(
        'label' => s('Subject').' - '.s('Descending'),
        'orderby' => 'subject desc'
    ),
    'enteredasc'  => array(
        'label' => s('Entered').' - '.s('Ascending'),
        'orderby' => 'entered asc'
    ),
    'entereddesc'  => array(
        'label' => s('Entered').' - '.s('Descending'),
        'orderby' => 'entered desc'
    ),
    'modifiedasc'  => array(
        'label' => s('Modified').' - '.s('Ascending'),
        'orderby' => 'modified asc'
    ),
    'modifieddesc'  => array(
        'label' => s('Modified').' - '.s('Descending'),
        'orderby' => 'modified desc'
    ),
    'embargoasc'  => array(
        'label' => s('Embargo').' - '.s('Ascending'),
        'orderby' => 'embargo asc'
    ),
    'embargodesc'  => array(
        'label' => s('Embargo').' - '.s('Descending'),
        'orderby' => 'embargo desc'
    ),
    'sentasc'  => array(
        'label' => s('Sent').' - '.s('Ascending'),
        'orderby' => 'sent asc'
    ),
    'sentdesc'  => array(
        'label' => s('Sent').' - '.s('Descending'),
        'orderby' => 'sent desc'
    ),
);
$tabParameters = array(
    'active' => array(
        // status values to select messages
        'status' => "'inprocess', 'submitted', 'suspended'",
        // initial ordering of tab
        'defaultSort' => 'embargoasc'
    ),
    'draft' => array(
        'status' => "'draft'",
        'defaultSort' => 'modifieddesc'
    ),
    'sent' => array(
        'status' => "'sent'",
        'defaultSort' => 'sentdesc'
    ),
    'static' => array(
        'status' => "'prepared'",
        'defaultSort' => 'embargoasc'
    ),
);

if ($access == 'all') {
    $ownerselect_and = '';
    $ownerselect_where = '';
} else {
    $ownerselect_where = ' where owner = '.$_SESSION['logindetails']['id'];
    $ownerselect_and = ' and owner = '.$_SESSION['logindetails']['id'];
}
if (isset($_GET['start'])) {
    $start = sprintf('%d', $_GET['start']);
} else {
    unset($start);
}

if (!isset($_SESSION['messagefilter'])) {
    $_SESSION['messagefilter'] = '';
}
if (!empty($_POST['clear'])) {
    $_SESSION['messagefilter'] = '';
    $_SESSION['messagesortby'] = array();
    $_SESSION['messagenumpp'] = MAX_MSG_PP;
    unset($_POST['filter']);
    unset($_POST['numPP']);
    unset($_POST['sortBy']);
}
if (isset($_POST['filter'])) {
    $_SESSION['messagefilter'] = removeXSS($_POST['filter']);
    if ($_SESSION['messagefilter'] == $filterSelectDefault) {
        $_SESSION['messagefilter'] = '';
    }
}
if (!isset($_SESSION['messagenumpp'])) {
    $_SESSION['messagenumpp'] = MAX_MSG_PP;
}
if (isset($_POST['numPP'])) {
    $_SESSION['messagenumpp'] = sprintf('%d', $_POST['numPP']);
    if ($_SESSION['messagenumpp'] <= 0) {
        $_SESSION['messagenumpp'] = MAX_MSG_PP;
    }
}

if (isset($_GET['tab']) && isset($tabParameters[$_GET['tab']])) {
    $currentTab = $_GET['tab'];
} else {
    if (isset($_SESSION['lastmessagetype'])) {
        $currentTab = $_SESSION['lastmessagetype'];
    } else {
        $currentTab = 'sent';
    }
}
$_SESSION['lastmessagetype'] = $currentTab;

if (isset($_POST['sortBy'])) {
    if (in_array($_POST['sortBy'], array_keys($messageSortOptions))) {
        $_SESSION['messagesortby'][$currentTab] = $_POST['sortBy'];
    }
}

if (!isset($_SESSION['messagesortby'][$currentTab])) {
    $_SESSION['messagesortby'][$currentTab] = $tabParameters[$currentTab]['defaultSort'];
}
$currentSortBy = $_SESSION['messagesortby'][$currentTab];

echo '<div class="actions"><div class="fright">';
echo PageLinkActionButton('send&amp;new=1', $GLOBALS['I18N']->get('Start a new campaign'));
echo '</div><div class="clear"></div></div>';

//## Print tabs
$tabs = new WebblerTabs();
$tabs->addTab($GLOBALS['I18N']->get('sent'), PageUrl2('messages&amp;tab=sent'), 'sent');
$tabs->addTab($GLOBALS['I18N']->get('active'), PageUrl2('messages&amp;tab=active'), 'active');
$tabs->addTab($GLOBALS['I18N']->get('draft'), PageUrl2('messages&amp;tab=draft'), 'draft');
//$tabs->addTab($GLOBALS['I18N']->get("queued"),PageUrl2("messages&amp;tab=queued"));#
if (USE_PREPARE) {
    $tabs->addTab($GLOBALS['I18N']->get('static'), PageUrl2('messages&amp;tab=static'), 'static');
}
//obsolete, moved to rssmanager plugin
//if (ENABLE_RSS) {
//  $tabs->addTab("rss",PageUrl2("messages&amp;tab=rss"));
//}
$tabs->setCurrent($currentTab);

echo '<div class="minitabs">';
echo $tabs->display();
echo '</div>';

$filterDisplay = $_SESSION['messagefilter'];

echo '<div id="messagefilter" class="filterdiv fright">';
echo formStart(' id="messagefilterform" ');
echo '<div><input type="text" name="filter" placeholder="&#128269;'.s('Search campaigns').'" value="'.htmlspecialchars($filterDisplay).'" />';

echo '<select name="numPP" class="numppOptions">';
foreach (array(5, 10, 15, 20, 50, 100) as $numppOption) {
    if ($numppOption == $_SESSION['messagenumpp']) {
        echo '<option selected="selected">'.$numppOption.'</option>';
    } else {
        echo '<option>'.$numppOption.'</option>';
    }
}
echo '</select>';
echo '<select name="sortBy" class="sortby">';
foreach ($messageSortOptions as $sortOption => $optionData) {
    if ($sortOption == $currentSortBy) {
        echo '<option selected="selected" value="'.$sortOption.'">'.$optionData['label'].'</option>';
    } else {
        echo '<option value="'.$sortOption.'">'.$optionData['label'].'</option>';
    }
}
echo '</select>';
echo '<button type="submit" name="go" id="filterbutton" >'.s('Go').'</button> <button type="submit" name="clear" id="filterclearbutton" value="1">'.s('Clear').'</button></div>';
echo '</form></div>';

//## Process 'Action' requests
if (!empty($_GET['delete'])) {
    verifyCsrfGetToken();
    $todelete = array();
    if ($_GET['delete'] == 'draft') {
        $req = Sql_Query(sprintf('select id from %s where status = "draft" and (subject = "" or subject = "(no subject)") %s',
            $GLOBALS['tables']['message'], $ownerselect_and));
        while ($row = Sql_Fetch_Row($req)) {
            array_push($todelete, $row[0]);
        }
    } else {
        array_push($todelete, sprintf('%d', $_GET['delete']));
    }
    foreach ($todelete as $delete) {
        $action_result .= $GLOBALS['I18N']->get('Deleting')." $delete ...";
        $del = deleteMessage($delete);
        if ($del) {
            $action_result .= '... '.$GLOBALS['I18N']->get('Done');
        } else {
            $action_result .= '... '.$GLOBALS['I18N']->get('failed');
        }
        $action_result .= '<br/>';
    }
    $action_result .= "<hr /><br />\n";
}

if (isset($_GET['duplicate'])) {
    verifyCsrfGetToken();

    Sql_Query(sprintf('insert into %s (uuid, subject, fromfield, tofield, replyto, message, textmessage, footer, entered,
        modified, embargo, repeatuntil, repeatinterval, requeueinterval, status, htmlformatted, sendformat, template, rsstemplate, owner)
        select "%s", subject, fromfield, tofield, replyto, message, textmessage, footer, now(),
        now(), now(), now(), repeatinterval, requeueinterval, "draft",  htmlformatted,
        sendformat, template, rsstemplate, "%d" from %s
        where id = %d',
        $GLOBALS['tables']['message'], (string) Uuid::generate(4), $_SESSION['logindetails']['id'],$GLOBALS['tables']['message'],
        intval($_GET['duplicate'])));
    if ($newId = Sql_Insert_Id()) {  // if we don't have a newId then the copy failed
        Sql_Query(sprintf('insert into %s (id,name,data) '.
            'select %d,name,data from %s where name in ("sendmethod","sendurl","campaigntitle","excludelist","subject") and id = %d',
            $GLOBALS['tables']['messagedata'],$newId,$GLOBALS['tables']['messagedata'],intval($_GET['duplicate'])));
        Sql_Query(sprintf('insert into %s (messageid, listid, entered)  select %d, listid, now() from %s where messageid = %d',
            $GLOBALS['tables']['listmessage'],$newId,$GLOBALS['tables']['listmessage'],intval($_GET['duplicate'])));
    }

}

if (isset($_GET['resend'])) {
    verifyCsrfGetToken();
    $resend = sprintf('%d', $_GET['resend']);
    // requeue the message in $resend
    $action_result .= $GLOBALS['I18N']->get('Requeuing')." $resend ..";
    $result = Sql_Query(sprintf('update %s set status = "submitted", sendstart = null where id = %d',
        $tables['message'], $resend));
    $suc6 = Sql_Affected_Rows();
    // only send it again to users, if we are testing, otherwise only to new users
    if (TEST) {
        $result = Sql_query(sprintf('delete from %s where messageid = %d', $tables['usermessage'], $resend));
    }
    if ($suc6) {
        $action_result .= '... '.$GLOBALS['I18N']->get('Done');
        foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
            $plugin->messageReQueued($resend);
        }
        Sql_Query(sprintf('delete from %s where id = %d and (name = "start_notified" or name = "end_notified")',
            $tables['messagedata'], $resend));
        $messagedata = loadMessageData($resend);
        $finishSending = mktime($messagedata['finishsending']['hour'], $messagedata['finishsending']['minute'], 0,
            $messagedata['finishsending']['month'], $messagedata['finishsending']['day'],
            $messagedata['finishsending']['year']);
        if ($finishSending < time()) {
            $action_result .= '<br />'.s('This campaign is scheduled to stop sending in the past. No mails will be sent.');
            $action_result .= '<br />'.PageLinkButton('send&amp;id='.$messagedata['id'].'&amp;tab=Scheduling',
                    s('Review Scheduling'));
        }
        if (getConfig('pqchoice') == 'phplistdotcom') {
            $action_result .= activateRemoteQueue();
        }
    } else {
        $action_result .= '... '.$GLOBALS['I18N']->get('failed');
    }
    $action_result .= '<br />';
}

if (isset($_GET['suspend'])) {
    verifyCsrfGetToken();
    $suspend = sprintf('%d', $_GET['suspend']);
    $action_result .= $GLOBALS['I18N']->get('Suspending')." $suspend ..";
    $result = Sql_query(sprintf('update %s set status = "suspended" where id = %d and (status = "inprocess" or status = "submitted") %s',
        $tables['message'], $suspend, $ownerselect_and));
    $suc6 = Sql_Affected_Rows();
    if ($suc6) {
        $action_result .= '... '.$GLOBALS['I18N']->get('Done');
    } else {
        $action_result .= '... '.$GLOBALS['I18N']->get('failed');
    }
    $action_result .= '<br /><hr /><br />';
}
//0012081: Add new 'Mark as sent' button
if (isset($_GET['markSent'])) {
    verifyCsrfGetToken();
    $markSent = sprintf('%d', $_GET['markSent']);
    $action_result .= $GLOBALS['I18N']->get('Marking as sent ')." $markSent ..";
    $result = Sql_query(sprintf('update %s set status = "sent", repeatinterval = 0,requeueinterval = 0 where id = %d and (status = "suspended") %s',
        $tables['message'], $markSent, $ownerselect_and));
    $suc6 = Sql_Affected_Rows();
    if ($suc6) {
        $action_result .= '... '.$GLOBALS['I18N']->get('Done');
    } else {
        $action_result .= '... '.$GLOBALS['I18N']->get('Failed');
    }
    $action_result .= '<br /><hr /><br />';
}

if (isset($_GET['action'])) {
    verifyCsrfGetToken();
    switch ($_GET['action']) {
        case 'suspall':
            $action_result .= $GLOBALS['I18N']->get('Suspending all').' ..';
            $result = Sql_query(sprintf('update %s set status = "suspended" where (status = "inprocess" or status = "submitted") %s',
                $tables['message'], $ownerselect_and));
            $suc6 = Sql_Affected_Rows();
            if ($suc6) {
                $action_result .= "... $suc6 ".$GLOBALS['I18N']->get('Done');
            } else {
                $action_result .= '... '.$GLOBALS['I18N']->get('Failed');
            }
            $action_result .= '<br /><hr /><br />';
            break;
        case 'markallsent':
            $action_result .= $GLOBALS['I18N']->get('Marking all as sent ').'  ..';
            $result = Sql_query(sprintf('update %s set status = "sent", repeatinterval = 0,requeueinterval = 0 where (status = "suspended") %s',
                $tables['message'], $markSent, $ownerselect_and));
            $suc6 = Sql_Affected_Rows();
            if ($suc6) {
                $action_result .= "... $suc6 ".$GLOBALS['I18N']->get('Done');
            } else {
                $action_result .= '... '.$GLOBALS['I18N']->get('Failed');
            }
            $action_result .= '<br /><hr /><br />';
            break;
    }
}

if (!empty($action_result)) {
    //print ActionResult($action_result);
    $_SESSION['action_result'] = $action_result;
    Redirect('messages');
    exit;
}

$where = array();
$where[] = sprintf('status in (%s)', $tabParameters[$currentTab]['status']);
$url_keep = '&amp;tab='.$currentTab;

if (!empty($_SESSION['messagefilter'])) {
    $where[] = ' subject like "%'.sql_escape($_SESSION['messagefilter']).'%" ';
}

//## Query messages from db
if ($access != 'all') {
    $where[] = ' owner = '.$_SESSION['logindetails']['id'];
}
$whereClause = ' where '.implode(' and ', $where);
$sortBySql = 'order by '.$messageSortOptions[$currentSortBy]['orderby'];
$req = Sql_query('select count(*) from '.$tables['message'].$whereClause.' '.$sortBySql);
$total_req = Sql_Fetch_Row($req);
$total = $total_req[0];

//# Browse buttons table
$limit = $_SESSION['messagenumpp'];
$offset = 0;
if (isset($start) && $start > 0) {
    $offset = $start;
} else {
    $start = 0;
}

$paging = '';
if ($total > $_SESSION['messagenumpp']) {
    $paging = simplePaging("messages$url_keep", $start, $total, $_SESSION['messagenumpp'],
        $GLOBALS['I18N']->get('Campaigns'));
}

$ls = new WebblerListing(s('Campaigns'));
$ls->setElementHeading('Campaign');
$ls->usePanel($paging);

//# messages table
if ($total) {
    $result = Sql_query('SELECT * FROM '.$tables['message']." $whereClause $sortBySql limit $limit offset $offset");
    while ($msg = Sql_fetch_array($result)) {
        $editlink = '';
        $messagedata = loadMessageData($msg['id']);
        if ($messagedata['subject'] != $messagedata['campaigntitle']) {
            $listingelement = '<!--'.$msg['id'].'-->'.stripslashes($messagedata['campaigntitle']).'<br/><strong>'.stripslashes($messagedata['subject']).'</strong>';
        } else {
            $listingelement = '<!--'.$msg['id'].'-->'.stripslashes($messagedata['subject']);
        }

        //   $listingelement = '<!--'.$msg['id'].'-->'.stripslashes($messagedata["campaigntitle"]);
        if ($msg['status'] == 'draft') {
            $editlink = PageUrl2('send&id='.$msg['id']);
        }

        $ls->addElement($listingelement, $editlink);
        $ls->setClass($listingelement, 'row1');
        $uniqueviews = Sql_Fetch_Row_Query("select count(userid) from {$tables['usermessage']} where viewed is not null and status = 'sent' and messageid = ".$msg['id']);

        $clicks = Sql_Fetch_Row_Query("select sum(clicked) from {$tables['linktrack_ml']} where messageid = ".$msg['id']);
//    $clicks = array(0);

        /*
            foreach ($messagedata as $key => $val) {
              $ls->addColumn($listingelement,$key,$val);
            }

        */
        $ls->addColumn($listingelement, $GLOBALS['I18N']->get('Entered'), formatDateTime($msg['entered']));

        $_GET['id'] = $msg['id'];
        $statusdiv = '<div id="messagestatus'.$msg['id'].'">';
        include 'actions/msgstatus.php';
        $statusdiv .= $status;
        $statusdiv .= '</div>';
        $GLOBALS['pagefooter']['statusupdate'.$msg['id']] = '<script type="text/javascript">
      updateMessages.push(' .$msg['id'].');</script>';
        $GLOBALS['pagefooter']['statusupdate'] = '<script type="text/javascript">window.setInterval("messagesStatusUpdate()",5000);</script>';
        if ($msg['status'] == 'sent') {
            $statusdiv = $GLOBALS['I18N']->get('Sent').': '.formatDateTime($msg['sent']);
        }
        $ls->addColumn($listingelement, $GLOBALS['I18N']->get('Status'), $statusdiv);

        /*
         * Display the lists that have been selected for the campaign
         */
        $maxListsDisplayed = 3;
        $namesQuery = <<<END
    SELECT SQL_CALC_FOUND_ROWS l.name
    FROM {$tables['list']} l
    JOIN {$tables['listmessage']} lm  ON l.id = lm.listid
    WHERE lm.messageid = {$msg['id']}
    ORDER BY l.name
    LIMIT $maxListsDisplayed
END;
        $namesResult = Sql_Query($namesQuery);
        $row = Sql_Fetch_Row_Query('SELECT FOUND_ROWS()');
        $numberOfLists = $row[0];

        if ($numberOfLists > 0) {
            $listNames = array();

            while ($row = Sql_Fetch_Assoc($namesResult)) {
                $listNames[] = htmlspecialchars($row['name']);
            }

            if ($numberOfLists > $maxListsDisplayed) {
                array_pop($listNames);
                $listNames[] = sprintf(
                    '<a href="%s">%s</a>',
                    PageURL2('message', '', "id={$msg['id']}").'#targetlists',
                    htmlspecialchars(s('and %d more', $numberOfLists - ($maxListsDisplayed - 1)))
                );
            }
            $ls->addRow($listingelement, s('Lists'), implode('<br/>', $listNames), '', 'left');
        }

        if ($msg['status'] != 'draft') {
            //    $ls->addColumn($listingelement,$GLOBALS['I18N']->get("total"), $msg['astext'] + $msg['ashtml'] + $msg['astextandhtml'] + $msg['aspdf'] + $msg['astextandpdf']);
//    $ls->addColumn($listingelement,$GLOBALS['I18N']->get("text"), $msg['astext']);
//    $ls->addColumn($listingelement,$GLOBALS['I18N']->get("html"), $msg["ashtml"] + $msg["astextandhtml"]);
//    if (!empty($msg['aspdf'])) {
//      $ls->addColumn($listingelement,$GLOBALS['I18N']->get("PDF"), $msg['aspdf']);
//    }
//    if (!empty($msg["astextandpdf"])) {
//      $ls->addColumn($listingelement,$GLOBALS['I18N']->get("both"), $msg["astextandpdf"]);
//    }

            // Prepare view & bounce statistics for printing
            $viewStats = array(
                'views' => $msg['viewed']
                , 'uniqueViews' => $uniqueviews[0]
                , 'clicks' => $clicks[0]
                , 'bounces' => $msg['bouncecount']
            );

            $viewStatsFormatted = array();

            // Make statistical integers human readable
            foreach ($viewStats as $key => $value) {
                $viewStatsFormatted[$key] = number_format($value);
            }
            $resultStats = '
    <table class="messagesendstats">
        <thead>
            <tr>
                <th colspan="2">Statistics</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>' .s('Total views').'</td>
                <td>'.(!empty($viewStats['views']) ? PageLink2('mviews&id='.$msg['id'], $viewStatsFormatted['views']) : '0').'</td>
            </tr>
            <tr>
                <td>' .s('Unique Views').'</td>
                <td>'.(!empty($viewStats['uniqueViews']) ? PageLink2('mviews&id='.$msg['id'], $viewStatsFormatted['uniqueViews']) : '0').'</td>
            </tr>';
            if ($clicks[0]) {
                $resultStats .= '
            <tr>
                <td>'.s('Total clicks').'</td>
                <td>'. (!empty($viewStats['clicks']) ? PageLink2('mclicks&id='.$msg['id'], $viewStatsFormatted['clicks']): '0').'</td>
            </tr>';
            }
            $resultStats .= '
            <tr>
                <td>' .s('Bounced').'</td>
                <td>'.(!empty($viewStats['bounces']) ? PageLink2('msgbounces&id='.$msg['id'],$viewStatsFormatted['bounces']): '0').'</td>
            </tr>
        </tbody>
    </table>';

//      $ls->addColumn($listingelement,s('Results'),$resultStats);

            //$ls->addColumn($listingelement,$GLOBALS['I18N']->get("Viewed"), $msg["viewed"]);
            //$ls->addColumn($listingelement,$GLOBALS['I18N']->get("Unique Views"), $uniqueviews[0]);
            //if ($clicks[0]) {
            //$ls->addColumn($listingelement,$GLOBALS['I18N']->get("Clicks"), $clicks[0]);
            //}
            //$ls->addColumn($listingelement,$GLOBALS['I18N']->get("Bounced"), $msg["bouncecount"]);
        }

        if ($msg['status'] == 'sent') {
            $started = s('Started ').': '.formatDateTime($msg['sendstart']);
            $timetosend = s('Time to send').': '.timeDiff($msg['sendstart'], $msg['sent']);
        } else {
            $timetosend = '';
            $started = '';
        }

        $colspan = 3;
        if (!empty($msg['aspdf'])) {
            ++$colspan;
        }
        if (!empty($msg['astextandpdf'])) {
            ++$colspan;
        }
        $clicksrow = $bouncedrow = '';

        //if ($clicks[0]) {
        //$clicksrow = sprintf('<tr><td colspan="%d">%s</td><td>%d</td></tr>',
        //$colspan-1,$GLOBALS['I18N']->get("Clicks"),$clicks[0]);
        //}
        //if ($msg["bouncecount"]) {
        //$bouncedrow = sprintf('<tr><td colspan="%d">%s</td><td>%d</td></tr>',
        //$colspan-1,$GLOBALS['I18N']->get("Bounced"),$msg["bouncecount"]);
        //}

        // Calculcate sent statistics for printing
        $sentStats = array(
            'grandTotal' => $msg['astext'] + $msg['ashtml'] + $msg['astextandhtml'] + $msg['aspdf'] + $msg['astextandpdf']
            , 'text' => $msg['astext']
            , 'html' => $msg['ashtml'] + $msg['astextandhtml'] //bug 0009687
            , 'pdf' => $msg['aspdf']
            , 'textPlusPDF' => $msg['astextandpdf']
        );

        $sentStatsFormatted = array();

        // Make statistical integers human readable
        foreach ($sentStats as $key => $value) {
            $sentStatsFormatted[$key] = number_format($value);
        }

        $sendstats =
            sprintf('<table class="messagesendstats">
          <thead>
            <tr>
              <th colspan="3">Processed</th>
            </tr>
          </thead>
          <tbody>
              %s %s
              <tr><td>' .s('total').'</td><td>'.s('text').'</td><td>'.s('html').'</td>
                %s%
              </tr>
              <tr><td><b>%s</b></td><td><b>%s</b></td><td><b>%s</b></td>
                %s %s %s %s
              </tr>
          </tbody>
      </table>',
                !empty($started) ? '<tr> <td colspan="'.$colspan.'">'.$started.'</td></tr>' : '',

                !empty($timetosend) ? '<tr> <td colspan="'.$colspan.'">'.$timetosend.'</td></tr>' : '',
                !empty($msg['aspdf']) ? '<td>'.$GLOBALS['I18N']->get('PDF').'</td>' : '',
                !empty($msg['astextandpdf']) ? '<td>'.$GLOBALS['I18N']->get('both').'</td>' : '',
                $sentStatsFormatted['grandTotal'],
                $sentStatsFormatted['text'],
                $sentStatsFormatted['html'],
                !empty($msg['aspdf']) ? '<td><b>'.$sentStatsFormatted['pdf'].'</b></td>' : '',
                !empty($msg['astextandpdf']) ? '<td><b>'.$sentStatsFormatted['textPlusPDF'].'</b></td>' : '',
                $clicksrow, $bouncedrow
            );
        if ($msg['status'] != 'draft') {
            $ls->addRow($listingelement, '', $resultStats.$sendstats);
        }

        $actionbuttons = '';
        if ($msg['status'] == 'inprocess' || $msg['status'] == 'submitted') {
            $actionbuttons .= '<span class="suspend">'.PageLinkButton('messages&suspend='.$msg['id'],
                    $GLOBALS['I18N']->get('Suspend'), '', '', s('Suspend')).'</span>';
        } elseif ($msg['status'] != 'draft') {
            $actionbuttons .= '<span class="resend">'.PageLinkButton('messages', $GLOBALS['I18N']->get('Requeue'),
                    'resend='.$msg['id'], '', s('Requeue')).'</span>';
        }
        $actionbuttons .= '<span class="view">'.PageLinkButton('message', $GLOBALS['I18N']->get('View'),
                'id='.$msg['id'], '', s('View')).'</span>';

        if ($clicks[0] && CLICKTRACK) {
            $actionbuttons .= '<span class="stats">'.PageLinkButton('statsoverview',
                    $GLOBALS['I18N']->get('statistics'), 'id='.$msg['id'], '', s('Statistics')).'</span>';
        }
        //0012081: Add new 'Mark as sent' button
        if ($msg['status'] == 'suspended') {
            $actionbuttons .= '<span class="marksent">'.PageLinkButton('messages&amp;markSent='.$msg['id'],
                    $GLOBALS['I18N']->get('Mark&nbsp;sent'), '', '', s('Mark sent')).'</span>';
            $actionbuttons .= '<span class="edit">'.PageLinkButton('send', $GLOBALS['I18N']->get('Edit'),
                    'id='.$msg['id'], '', s('Edit')).'</span>';
        } elseif ($msg['status'] == 'draft' || !empty($messagedata['istestcampaign'])) {
            //# only draft messages should be deletable, the rest isn't

            $deletebutton = new ConfirmButton(
                s('Are you sure you want to delete this campaign?'),
                PageURL2("messages$url_keep&delete=".$msg['id']),
                s('delete this campaign'), '', 'button');

//      $actionbuttons .= sprintf('<span class="delete"><a href="javascript:deleteRec(\'%s\');" class="button" title="'.$GLOBALS['I18N']->get("delete").'">'.$GLOBALS['I18N']->get("delete").'</a></span>',PageURL2("messages$url_keep","","delete=".$msg["id"]));
            $actionbuttons .= '<span class="edit">'.PageLinkButton('send', $GLOBALS['I18N']->get('Edit'),
                    'id='.$msg['id'], '', s('Edit')).'</span>';
            if (empty($clicks[0])  ||  !empty($messagedata['istestcampaign'])) { //# disallow deletion when there are stats except when is test campaign
                $actionbuttons .= '<span class="delete">'.$deletebutton->show().'</span>';
            }
        }

        if ($msg['status'] == 'sent') {
            $actionbuttons .= '<span class="copy">'.PageLinkButton('messages', s('Copy to Draft'),
                    'tab=draft&duplicate='.$msg['id'], '', s('Copy to Draft')).'</span>';
        }

        $ls->addColumn($listingelement, $GLOBALS['I18N']->get('Action'),
            '<div class="messageactions">'.$actionbuttons.'</div>');
    }
}

echo $ls->display();

if ($total > 5 && $currentTab == 'active') {
    echo PageLinkButton('messages', $GLOBALS['I18N']->get('Suspend All'), 'action=suspall');
    echo PageLinkButton('messages', $GLOBALS['I18N']->get('Mark All Sent'), 'action=markallsent');
}
