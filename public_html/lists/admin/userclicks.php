<?php

// click stats listing users
require_once dirname(__FILE__).'/accesscheck.php';

if (isset($_GET['msgid'])) {
    $msgid = sprintf('%d', $_GET['msgid']);
} else {
    $msgid = 0;
}
if (isset($_GET['fwdid'])) {
    $fwdid = sprintf('%d', $_GET['fwdid']);
} else {
    $fwdid = 0;
}
if (isset($_GET['userid'])) {
    $userid = sprintf('%d', $_GET['userid']);
} else {
    $userid = 0;
}
if (isset($_GET['start'])) {
    $start = sprintf('%d', $_GET['start']);
} else {
    $start = 0;
}

if (!$msgid && !$fwdid && !$userid) {
    echo $GLOBALS['I18N']->get('Invalid Request');

    return;
}

$access = accessLevel('userclicks');
switch ($access) {
    case 'owner':
    case 'all':
        $subselect = '';
        break;
    case 'none':
    default:
        print $GLOBALS['I18N']->get('You do not have access to this page');

        return;
        break;
}

$download = !empty($_GET['dl']);
$downloadContent = '';

if ($download) {
    ob_end_clean();
    header('Content-type: text/csv');
    header('Content-disposition:  attachment; filename="phpList click statistics.csv"');
    ob_start();
}

//$limit = ' limit 100';

$ls = new WebblerListing($GLOBALS['I18N']->get('Click statistics'));

if ($fwdid) {
    $urldata = Sql_Fetch_Array_Query(sprintf('select url from %s where id = %d',
        $GLOBALS['tables']['linktrack_forward'], $fwdid));
}
if ($msgid) {
    //  $messagedata = Sql_Fetch_Array_query("SELECT * FROM {$tables['message']} where id = $msgid $subselect");
    $messagedata = loadMessageData($msgid);
}
if ($userid) {
    $userdata = Sql_Fetch_Array_query("SELECT * FROM {$tables['user']} where id = $userid $subselect");
}

if ($fwdid && $msgid) {
    echo '<h3>'.$GLOBALS['I18N']->get('Subscriber clicks for a URL in a campaign');
    echo ' '.strtolower(PageLink2('uclicks&amp;id='.$fwdid, $urldata['url']));
    echo '</h3>';
    $downloadContent = s('Subscribers who clicked on URL "%s" in the campaign with subject "%s", sent %s',
            $urldata['url'], $messagedata['subject'], $messagedata['sent']).PHP_EOL;
    echo '<table class="userclicksDetails">';
    if ($messagedata['subject'] != $messagedata['campaigntitle']) {
        echo '<tr><td>'.s('Title').'<td><td>'.$messagedata['campaigntitle'].'</td></tr>';
    }
    echo '<tr><td>'.s('Subject').'<td><td>'.PageLink2('mclicks&amp;id='.$msgid, $messagedata['subject']).'</td></tr>
  <tr><td>' .$GLOBALS['I18N']->get('Entered').'<td><td>'.$messagedata['entered'].'</td></tr>
  <tr><td>' .$GLOBALS['I18N']->get('Sent').'<td><td>'.$messagedata['sent'].'</td></tr>
  </table>';
    echo '<div class="fright">'.PageLinkButton('userclicks&fwdid='.$fwdid.'&msgid='.$msgid.'&dl=1',
            s('Download subscribers')).'</div>';
    $query = sprintf('select htmlclicked, textclicked, user.email,user.id as userid,firstclick,latestclick,clicked
    from %s as uml_click, %s as user where uml_click.userid = user.id 
    and uml_click.forwardid = %d and uml_click.messageid = %d
    and uml_click.clicked', $GLOBALS['tables']['linktrack_uml_click'], $GLOBALS['tables']['user'], $fwdid, $msgid);
} elseif ($userid && $msgid) {
    echo '<h3>'.$GLOBALS['I18N']->get('Subscriber clicks on a campaign').'</h3>';
    echo s('Subscriber').' '.PageLink2('user&amp;id='.$userid, $userdata['email']);
    echo '</h3>';
    echo '<table class="userclickDetails">';
    if ($messagedata['subject'] != $messagedata['campaigntitle']) {
        echo '<tr><td>'.s('Title').'<td><td>'.$messagedata['campaigntitle'].'</td></tr>';
    }
    echo '
  <tr><td>' .$GLOBALS['I18N']->get('Subject').'<td><td>'.PageLink2('mclicks&amp;id='.$msgid,
            $messagedata['subject']).'</td></tr>
  <tr><td>' .$GLOBALS['I18N']->get('Entered').'<td><td>'.$messagedata['entered'].'</td></tr>
  <tr><td>' .$GLOBALS['I18N']->get('Sent').'<td><td>'.$messagedata['sent'].'</td></tr>
  </table>';
    $query = sprintf('select htmlclicked, textclicked,user.email,user.id as userid,firstclick,latestclick,
    clicked,messageid,forwardid,url from %s as uml_click, %s as user, %s as forward where uml_click.userid = user.id 
    and uml_click.userid = %d and uml_click.messageid = %d and forward.id = uml_click.forwardid',
        $GLOBALS['tables']['linktrack_uml_click'], $GLOBALS['tables']['user'], $GLOBALS['tables']['linktrack_forward'],
        $userid, $msgid);
} elseif ($fwdid) {
    echo '<h3>'.$GLOBALS['I18N']->get('Subscribers who clicked a URL').' <b>'.$urldata['url'].'</b></h3>';
    $downloadContent = s('Subscribers who clicked on the URL "%s" across all campaigns', $urldata['url']).PHP_EOL;
    echo '<div class="fright">'.PageLinkButton('userclicks&fwdid='.$fwdid.'&dl=1',
            s('Download subscribers')).'</div>';
    $query = sprintf('
        SELECT user.email,
        user.id AS userid,
        MIN(firstclick) AS firstclick,
        MAX(latestclick) AS latestclick,
        SUM(clicked) AS clicked
        FROM %s AS uml_click
        JOIN %s AS user ON uml_click.userid = user.id
        WHERE uml_click.forwardid = %d
        GROUP BY uml_click.userid
        ',
        $GLOBALS['tables']['linktrack_uml_click'],
        $GLOBALS['tables']['user'],
        $fwdid
    );
} elseif ($msgid) {
    echo '<h3>'.$GLOBALS['I18N']->get('Subscribers who clicked a campaign').'</h3>';
    echo '<table class="userclickDetails">';
    if ($messagedata['subject'] != $messagedata['campaigntitle']) {
        echo '<tr><td>'.s('Title').'<td><td>'.$messagedata['campaigntitle'].'</td></tr>';
    }
    echo '
  <tr><td>' .$GLOBALS['I18N']->get('Subject').'<td><td>'.$messagedata['subject'].'</td></tr>
  <tr><td>' .$GLOBALS['I18N']->get('Entered').'<td><td>'.$messagedata['entered'].'</td></tr>
  <tr><td>' .$GLOBALS['I18N']->get('Sent').'<td><td>'.$messagedata['sent'].'</td></tr>
  </table>';
    $downloadContent = s('Subscribers who clicked on campaign with subject "%s", sent %s', $messagedata['subject'],
            $messagedata['sent']).PHP_EOL;
    echo '<div class="fright">'.PageLinkButton('userclicks&msgid='.$msgid.'&dl=1',
            s('Download subscribers')).'</div>';
    $query = sprintf('
        SELECT DISTINCT user.email,
        user.id AS userid,
        MIN(firstclick) AS firstclick,
        MAX(latestclick) AS latestclick,
        SUM(clicked) AS clicked
        FROM %s AS uml_click
        JOIN %s AS user ON uml_click.userid = user.id 
        WHERE uml_click.messageid = %d
        GROUP BY uml_click.userid
        ',
        $GLOBALS['tables']['linktrack_uml_click'],
        $GLOBALS['tables']['user'],
        $msgid
    );
} elseif ($userid) {
    echo '<div class="jumbotron">'.$GLOBALS['I18N']->get('All clicks by').' <b>'.PageLink2('user&amp;id='.$userid, $userdata['email']).'</b></div>';

    $query = '
        SELECT
            SUM(htmlclicked) AS htmlclicked,
            SUM(textclicked) AS textclicked,
            user.email,
            user.id AS userid,
            MIN(firstclick) AS firstclick,
            MAX(latestclick) AS latestclick,
            SUM(clicked) AS clicked,
            GROUP_CONCAT(
                messageid
            ORDER BY
                messageid SEPARATOR \' \') AS messageid,
                forwardid,
                url
        FROM 
            '.$GLOBALS['tables']['linktrack_uml_click'].' AS uml_click
        JOIN
            '.$GLOBALS['tables']['user'].' AS user ON uml_click.userid = user.id
        JOIN 
            '.$GLOBALS['tables']['linktrack_forward'].' AS forward ON forward.id = uml_click.forwardid
        WHERE 
            uml_click.userid = '.sprintf('%d', $userid).'
        GROUP BY 
            forwardid
        ORDER BY 
            clicked DESC, 
            url
        ';
}

//ob_end_flush();
//flush();

$req = Sql_Query($query);
$total = Sql_Num_Rows($req);
if ($total > 100 && !$download) {
    echo simplePaging('userclicks&msgid='.$msgid.'&fwdid='.$fwdid.'&userid='.$userid, $start, $total, 100,
        s('Subscribers'));

    $limit = ' limit '.$start.', 100';
    $req = Sql_Query($query.' '.$limit);
}

$summary = array();
$summary['totalclicks'] = 0;
while ($row = Sql_Fetch_Array($req)) {
    //  print $row['email'] . "<br/>";
    if ($download) {
        $downloadContent .= $row['email'].PHP_EOL;
    } else {
        if (!$userid) {
            $element = shortenTextDisplay($row['email']);
            $ls->addElement($element, PageUrl2('user&amp;id='.$row['userid']));
            $ls->setClass($element, 'row1');
        } else {
            //    $link = substr($row['url'],0,50);
            //    $element = PageLink2($link,$link,PageUrl2('uclicks&amp;id='.$row['forwardid']),"",true,$row['url']);
            $element = shortenTextDisplay($row['url']);
            $ls->addElement($element, PageUrl2('uclicks&amp;id='.$row['forwardid']));
            $ls->setClass($element, 'row1');
            $messageLinks = preg_replace_callback(
                '/\d+/',
                function ($matches) {
                    return PageLink2("mclicks&id={$matches[0]}", $matches[0]);
                },
                $row['messageid']
            );
            $ls->addColumn($element, $GLOBALS['I18N']->get('message'), $messageLinks);
        }
        //  $element = sprintf('<a href="%s" target="_blank" class="url" title="%s">%s</a>',$row['url'],$row['url'],substr(str_replace('http://','',$row['url']),0,50));
        //  $total = Sql_Verbose_Query(sprintf('select count(*) as total from %s where messageid = %d and url = "%s"',
        //    $GLOBALS['tables']['linktrack'],$id,$row['url']));
        //  $totalsent = Sql_Fetch_Array_Query(sprintf('select count(*) as total from %s where url = "%s"',
        //    $GLOBALS['tables']['linktrack'],$urldata['url']));
        $ls_userid = '';

        if (!empty($row['userid'])) {
            $userStatus = Sql_Fetch_Assoc_Query(sprintf('select blacklisted,confirmed from %s where id = %d',
                $GLOBALS['tables']['user'], $row['userid']));
            $ls->addColumn($element, s('Status'),
                $userStatus['confirmed'] && empty($userStatus['blacklisted']) ? $GLOBALS['img_tick'] : $GLOBALS['img_cross']);
        }
        $ls->addColumn($element, $GLOBALS['I18N']->get('firstclick'), formatDateTime($row['firstclick'], 1));
        $ls->addColumn($element, $GLOBALS['I18N']->get('latestclick'), formatDateTime($row['latestclick'], 1));
        $ls->addColumn($element, $GLOBALS['I18N']->get('clicks'), $row['clicked']);
        if (!$userid) { //Display
            $ls_userid = '<span class="viewusers"><a class="button" href="' . PageUrl2('userclicks&amp;userid=' . $row['userid']) . '" title="' . s('view user') . '"></a></span>';
            $ls->addColumn($element, s('View clicks'), $ls_userid);
        }
        if (!empty($row['htmlclicked']) && !empty($row['textclicked'])) {
            $ls->addRow($element,
                '<div class="content listingsmall fright gray">'.$GLOBALS['I18N']->get('HTML').': '.$row['htmlclicked'].'</div>'.
                '<div class="content listingsmall fright gray">'.$GLOBALS['I18N']->get('text').': '.$row['textclicked'].'</div>',
                '');
        }
        //  $ls->addColumn($element,$GLOBALS['I18N']->get('sent'),$total['total']);
        //  $perc = sprintf('%0.2f',($row['numclicks'] / $totalsent['total'] * 100));
        //  $ls->addColumn($element,$GLOBALS['I18N']->get('clickrate'),$perc.'%');
        $summary['totalclicks'] += $row['clicked'];
    }
}

//# adding a total doesn't make sense if we're not listing everything, it'll only do the total of the page
//$ls->addElement($GLOBALS['I18N']->get('total'));
//$ls->setClass($GLOBALS['I18N']->get('total'),'rowtotal');
//$ls->addColumn($GLOBALS['I18N']->get('total'),$GLOBALS['I18N']->get('clicks'),$summary['totalclicks']);
if (!$download) {
    echo $ls->display();
} else {
    ob_end_clean();
    echo $downloadContent;
    exit;
}
