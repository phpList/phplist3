<?php

require_once dirname(__FILE__).'/accesscheck.php';
$access = accessLevel('msgbounces');

$messageid = empty($_GET['id']) ? 0 : sprintf('%d', $_GET['id']);
$download = isset($_GET['type']) && $_GET['type'] === 'dl';

$isowner_where = '';
switch ($access) {
    case 'owner':
        if ($messageid) {
            $req = Sql_Query(sprintf('select id from '.$tables['message'].' where owner = %d and id = %d',
                $_SESSION['logindetails']['id'], $messageid));
            if (!Sql_Affected_Rows()) {
                echo s('You do not have access to this page');

                return;
            }
        }

        break;
    case 'all':
    case 'view':
        break;
    case 'none':
    default:
        if ($messageid) {
            echo s('You do not have access to this page');


            return;
        }
        break;
}
if (!$messageid) {
    //# for testing the loader allow a delay flag
    if (isset($_GET['delay'])) {
        $_SESSION['LoadDelay'] = sprintf('%d', $_GET['delay']);
    } else {
        unset($_SESSION['LoadDelay']);
    }
    echo '<div id="contentdiv"></div>';
    echo asyncLoadContent('./?page=pageaction&action=msgbounces&ajaxed=true&id='.$messageid.addCsrfGetToken());

    return;
}
$query = <<<END
    select
        u.id as userid,
        u.email,
        mb.bounce,
        mb.time
    from {$tables['user_message_bounce']} mb
    join {$tables['user']} u on u.id = mb.user
    where mb.message = $messageid
END;
$req = Sql_Query($query);

$total = Sql_Affected_Rows();
$limit = '';
$numpp = 150;

$chooseAnotherCampaign = new buttonGroup (
    new Button(PageUrl2('msgbounces'), s('Select another campaign')
    )
);
$listOfCampaigns = Sql_Query(sprintf('select id, subject from %s campaign order by subject ', $tables['message']));
while ($campaign = Sql_Fetch_Assoc($listOfCampaigns)) {
    $chooseAnotherCampaign->addButton(new Button
        (PageUrl2('msgbounces') . '&amp;id=' . $campaign['id'], htmlentities($campaign['subject']))
    );

}
echo $chooseAnotherCampaign->show();

if ($total) {
    echo PageLinkButton('msgbounces&amp;type=dl&amp;id='.$messageid, s('Download'),'','btn-primary pull-right btn-lg pull-bottom');
}

echo '<p>'.number_format($total).' '.s('bounces to campaign').' \''.campaignTitle($messageid).'\'</p>';
$start = empty($_GET['start']) ? 0 : sprintf('%d', $_GET['start']);
if ($total > $numpp && !$download ) {
    $limit = " limit $start, $numpp";
    echo simplePaging('msgbounces&amp;id='.$messageid, $start, $total, $numpp);

    $query .= $limit;
    $req = Sql_Query($query);

}

$messagedata = loadMessageData($messageid);
if ($download) {
    ob_end_clean();
    header('Content-type: text/csv');
    $filename = 'Bounces on '.campaignTitle($messageid).'.csv';
    header('Content-disposition:  attachment; filename="'.$filename.'"');
    ob_start();
}
$bouncels = new WebblerListing(s('Bounces on').' '.shortenTextDisplay($messagedata['subject'], 30));
$bouncels->noShader();
$bouncels->setElementHeading(s('Bounce ID'));

while ($row = Sql_Fetch_Array($req)) {
    $bouncels->addElement($row['bounce'], PageUrl2('bounce&amp;id='.$row['bounce']));
    $bouncels->addColumn($row['bounce'], s('user'), PageLink2('user&id='.$row['userid'], $row['email']));
    $bouncels->addColumn($row['bounce'], s('Time'), formatDateTime($row['time']));
}
if ($download) {
    ob_end_clean();
    echo $bouncels->tabDelimited();
    exit;
} else {
     echo $bouncels->display();
}
