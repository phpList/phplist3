<?php
require_once dirname(__FILE__).'/accesscheck.php';

if (!$_SESSION['logindetails']['superuser']) {
    echo $GLOBALS['I18N']->get('Sorry, this page can only be used by super admins');

    return;
}

if (!empty($_POST['unsubscribe'])) {
    $emails = explode("\n", $_POST['unsubscribe']);
    $count = 0;
    $unsubbed = $blacklisted = 0;
    foreach ($emails as $email) {
        $email = trim($email);
        ++$count;
        set_time_limit(30);
        Sql_Query(sprintf('update %s set confirmed = 0 where email = "%s"', $GLOBALS['tables']['user'], $email));
        $unsubbed += Sql_Affected_Rows();
        if (!empty($_POST['blacklist'])) {
            ++$blacklisted;
            addUserToBlackList($email,
                $GLOBALS['I18N']->get('Blacklisted by').' '.$_SESSION['logindetails']['adminname']);
        }
    }
    printf($GLOBALS['I18N']->get('All done, %d emails processed, %d emails marked unconfirmed, %d emails blacklisted<br/>'),
        $count, $unsubbed, $blacklisted);
    echo PageLinkButton('suppressionlist', s('Add more'));

    return;
}
?>

<form method="post" action="">
    <h3><?php echo $GLOBALS['I18N']->get('Manage suppression list') ?></h3>
    <?php echo $GLOBALS['I18N']->get('Make suppression permanent') ?> <input type="checkbox" name="blacklist" value="1"
                                                                             checked="checked"/></br />
    <p class="information"><?php echo $GLOBALS['I18N']->get('Paste the emails to mark unconfirmed in this box, and click continue') ?></p>
    <p class="submit"><input type="submit" name="go" value="<?php echo $GLOBALS['I18N']->get('Continue') ?>"></p><br/>
    <textarea name="unsubscribe" rows="30" cols="40"></textarea>
</form>
