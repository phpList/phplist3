<?php

require_once dirname(__FILE__).'/accesscheck.php';
ob_end_flush();
$upgrade_required = 0;
$canUpgrade = checkAccess('upgrade');

if (Sql_Table_exists($tables['config'], 1)) {
    $dbversion = getConfig('version');
    if ($dbversion != VERSION && $canUpgrade) {
        Error($GLOBALS['I18N']->get('Your database is out of date, please make sure to upgrade').'<br/>'.
            $GLOBALS['I18N']->get('Your version').' : '.$dbversion.'<br/>'.
            $GLOBALS['I18N']->get('phplist version').' : '.VERSION.
            '<br/>'.PageLink2('upgrade', $GLOBALS['I18N']->get('Upgrade'))
        );
        $upgrade_required = 1;
    }
} else {
    $GLOBALS['firsttime'] = 1;
    $_SESSION['firstinstall'] = 1;
    Info($GLOBALS['I18N']->get('Database has not been initialised').'. '.
        $GLOBALS['I18N']->get('go to').' '.
        PageLink2('initialise&firstinstall=1', $GLOBALS['I18N']->get('Initialise Database')).' '.
        $GLOBALS['I18N']->get('to continue'), 1);

    return;
}

//# trigger this somewhere else?
refreshTlds();

$lastCampaignID = null;

if ($_SESSION['logindetails']['superuser']) {
    $result = Sql_Query(sprintf(
        'select id from %s where sent is not null order by entered desc limit 1',
        $GLOBALS['tables']['message']
    ));
    if ($result) {
        $row = Sql_Fetch_Assoc($result);
        $lastCampaignID = $row['id'];
        $lastcampaign = Sql_Fetch_Assoc_Query(sprintf(
            'select msg.id as messageid,count(um.viewed) as views, count(um.status) as total,
            subject,date_format(sent,"%%e %%M %%Y") as sent,bouncecount as bounced
            from %s um
            join %s msg on msg.id = um.messageid
            where msg.id = %d and um.status = "sent"',
            $GLOBALS['tables']['usermessage'], $GLOBALS['tables']['message'], $lastCampaignID
        ));
    }
} else {
    $result = Sql_Query(sprintf(
        'select msg.id from %s msg
        join %s lm  on lm.messageid = msg.id
        join %s list on list.id = lm.listid
        where sent is not null
        and list.owner = %d
       order by msg.entered desc limit 1',
        $GLOBALS['tables']['message'],$GLOBALS['tables']['listmessage'],$GLOBALS['tables']['list'],$_SESSION['logindetails']['id']
    ));
    if ($result) {
        $row = Sql_Fetch_Assoc($result);
        $lastCampaignID = $row['id'];
        $lastcampaign = Sql_Fetch_Assoc_Query(sprintf(
            'select msg.id as messageid,count(um.viewed) as views, count(um.status) as total,
            subject,date_format(sent,"%%e %%b %%Y") as sent,bouncecount as bounced
            from  %s um
            join %s msg on um.messageid = msg.id
            join %s lm  on lm.messageid = msg.id
            join %s list on list.id = lm.listid
            where msg.id = %d and um.status = "sent"
            and list.owner = %d',
            $GLOBALS['tables']['usermessage'],
            $GLOBALS['tables']['message'],
            $GLOBALS['tables']['listmessage'],
            $GLOBALS['tables']['list'],
            $lastCampaignID,
            $_SESSION['logindetails']['id']
        ));
    }
}

?>
<div class="row">

<div id="gettingstarted" class="col-sm-6 col-lg-4">
    <h3><span class="glyphicon glyphicon-star"></span>Getting Started</h3>
    <div class="well" style="height:100%">
        <?php
        if (!in_array('list', $GLOBALS['disallowpages'])) {
            echo '
            <a class="btn btn-primary" href="./?page=import">' . s('Import Subscribers') . '</a>
            <br />';
        }
        if (!in_array('send', $GLOBALS['disallowpages'])) {
            echo '<a class="btn btn-primary" href="./?page=send">' . s('Start or continue a campaign') . '</a>
            <br />';
        }
        if  (!in_array('statsoverview', $GLOBALS['disallowpages'])) { // are we allowed to view stats
            if (!empty($lastCampaignID)) { // are there any
                echo '<a class="btn btn-primary " href="./?page=statsoverview" title="View Campaign result statistics">' . s('View Statistics') . '</a>
                <br />';
            } else {
                echo '<span style="display:inline-block" title="Send your first campaign to obtain useful statistics" data-toggle="tooltip">
                    <a class="btn btn-primary btn-sm disabled" href="./?page=statsoverview">' . s('View Statistics') . '</a>
                </span>
                <br />';
            }
        }
        ?>
    </div>
</div><!-- /col -->


<div id="lastcampaign" class="col-sm-6 col-lg-4">
    <h3><span class="glyphicon glyphicon-signal"></span>Last Campaign Results</h3>
        <div id="lastcampaigncontent" class="well">
        <?php if (empty($lastCampaignID)) {
    print '<p>' . s('There are currently no statistics available') . '</p>';
} else {
    ?>

    <p>
                <span class="total">
                    Subject:
                    <a href="./?page=statsoverview&amp;id=<?php echo $lastcampaign['messageid'] ?>" title="View campaign statistics">
                        <?php echo $lastcampaign['subject'] ?>
                    </a>
                </span>
    </p>

    <p><span class="total"><?php echo number_format($lastcampaign['total']) ?></span> Messages sent on <span class="total"><?php echo $lastcampaign['sent'] ?></span>.</p>
    <p><span class="total"><?php echo number_format($lastcampaign['views']) ?> </span> Viewed (<span class="total"><?php echo sprintf('%0.2f', ($lastcampaign['views'] / ($lastcampaign['total'] - $lastcampaign['bounced']) * 100)) ?>%</span>), and <span class="total"><?php echo $lastcampaign['bounced'] ?></span> bounced (<span class="total"><?php echo sprintf('%0.2f', ($lastcampaign['bounced'] / $lastcampaign['total'] * 100)) ?>%</span>).</p>

    <?php
} ?>

        </div>
</div><!-- /col -->

<div class="clearfix visible-md visible-sm"></div>

<div class="col-sm-6 col-lg-4">
    <h3><span class="glyphicon glyphicon-question-sign"></span><?php echo s('Help') ?></h3>
    <div class="well">
        <ul>
            <li>
                <a href="https://www.phplist.org/manual/books/phplist-manual/page/composing-your-first-campaign" target="_blank">How to create a campaign</a>
            </li>
            <li>
                <a href="https://www.phplist.org/manual/books/phplist-manual/page/basic-campaign-statistics" target="_blank">How to use statistics</a>
            </li>
        </ul>
        <div class="row">
            <div class="col-lg-12">
                <p><a class="btn btn-primary" href="https://phplist.org/manual" target="_blank">View Manual</a></p>
            </div>
        </div>
    </div><!-- /well -->
</div><!-- /col-lg-6-->

</div><!--/row-->
<div class="clearfix visible-lg"></div>
