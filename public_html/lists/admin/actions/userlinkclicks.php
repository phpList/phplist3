<?php

/**
 * Ajax-called script to fetch list of URLs clicked by a given subscriber
 */

require_once dirname(__FILE__).'/../accesscheck.php';
if (!defined('PHPLISTINIT')) {
    exit;
}

// If click tracking is disabled, then return nothing
if ( !defined('CLICKTRACK') || !CLICKTRACK ) {
    echo s('Clicktracking is disabled');
    return;
}

if (!$_GET['id']) {
    Fatal_Error(s('no such User'));

    return;
} else {
    $id = sprintf('%d', $_GET['id']);
}
$status = "";

$result = Sql_query("SELECT * FROM {$GLOBALS['tables']['user']} where id = $id");
if (!Sql_Affected_Rows()) {
    Fatal_Error(s('no such User'));

    return;
}
$user = sql_fetch_array($result);

$ls = new WebblerListing(s('Clicks'));
if (Sql_Table_Exists($GLOBALS['tables']['usermessage'])) {
    $urls = Sql_Query('
        SELECT 
            luc.messageid
            , luc.userid
            , firstclick
            , latestclick
            , fwd.url AS url
            , luc.forwardid
        FROM 
            '.$GLOBALS['tables']['linktrack_uml_click'].' AS luc
        LEFT JOIN
            '.$GLOBALS['tables']['linktrack_forward'].' AS fwd 
                ON fwd.id = luc.forwardid
        WHERE 
            luc.userid = '.$user['id']
    );
    $num = Sql_Affected_Rows();
} else {
    $num = 0;
}
printf('%d '.s('links clicked by this subscriber').'<br/>', $num);
if ($num) {
    $resptime = 0;
    $totalresp = 0;
    $ls->setElementHeading(s('URL'));

    while ($url = Sql_Fetch_Array($urls)) {
        $ls->addElement(
            shortenTextDisplay($url['url'], 150)
            , PageURL2('uclicks&id='.$url['forwardid'])
        );

        $clicksreq = Sql_Fetch_Row_Query('
            SELECT
                SUM(clicked) AS numclicks
            FROM
                '.$GLOBALS['tables']['linktrack_uml_click'].'
            WHERE
                userid = '.$user['id'].'
                AND forwardid = '.$url['forwardid']
        );

        $clicks = sprintf('%d', $clicksreq[0]);

        if ($clicks) {
            $ls->addColumn(
                $url['url']
                , s('clicks')
                , PageLink2(
                    'userclicks&amp;userid='.$user['id'].'&amp;msgid='.$url['messageid']
                    , number_format($clicks)
                )
            );
        }
    }
}

echo $ls->display();
