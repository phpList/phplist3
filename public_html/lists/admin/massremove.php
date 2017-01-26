<?php
require_once dirname(__FILE__).'/accesscheck.php';

@ob_end_flush();
flushBrowser();

if (!$_SESSION['logindetails']['superuser']) {
    echo $GLOBALS['I18N']->get('Sorry, this page can only be used by super admins');

    return;
}

if (isset($_POST['unsubscribe'])) {
    $emails = explode("\n", trim($_POST['unsubscribe']));
    $total = count($emails);
    $count = $notfound = $deleted = $blacklisted = $notDeleted = 0;
    foreach ($emails as $email) {
        $email = trim($email);
        ++$count;
        set_time_limit(600);
        $userid = Sql_Fetch_Row_Query(sprintf('select id from %s where email = "%s"', $GLOBALS['tables']['user'],
            $email));
        if (!empty($_POST['blacklist'])) {
            ++$blacklisted;
            addUserToBlackList($email,
                $GLOBALS['I18N']->get('Blacklisted by').' '.$_SESSION['logindetails']['adminname']);
        }
        $campaignCount = Sql_Fetch_Row_Query(sprintf('select count(*) from %s where userid = %d',
            $GLOBALS['tables']['usermessage'], $userid[0]));

        if ($userid[0] && empty($campaignCount[0])) {
            deleteUser($userid[0]);
            ++$deleted;
        } elseif (!empty($campaignCount[0])) {
            ++$notDeleted;
        } else {
            ++$notfound;
        }
        if ($total > 100) {
            if ($count % 100 == 0) {
                printf('%d/%d<br/>', $count, $total);
                flushBrowser();
            }
        }
    }
    echo s('All done, %d emails processed<br/>%d emails blacklisted<br/>%d emails deleted<br/>%d emails not found',
        $count, $blacklisted, $deleted, $notfound);
    echo '<br/>'.s('%d subscribers could not be deleted, because they have already received campaigns', $notDeleted);
    echo '<br/>'.PageLinkButton('massremove', s('Remove more'));

    return;
}
?>

<form method=post action="">
    <h3><?php echo $GLOBALS['I18N']->get('Mass remove email addresses') ?></h3>

    <?php echo $GLOBALS['I18N']->get('Check to also add the emails to the blacklist') ?> <input type="checkbox"
                                                                                                name="blacklist"
                                                                                                value="1"><br/>
    <p class="information"><?php echo $GLOBALS['I18N']->get('Paste the emails to remove in this box, and click continue') ?></p>
    <p class="submit"><input type="submit" name="go" value="<?php echo $GLOBALS['I18N']->get('Continue') ?>"></p><br/>
    <textarea name="unsubscribe" rows=30 cols=40></textarea>
</form>
