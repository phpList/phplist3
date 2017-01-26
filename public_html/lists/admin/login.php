<?php
require_once dirname(__FILE__).'/accesscheck.php';

if (TEST && strpos($_SERVER['HTTP_HOST'], 'phplist.org') !== false) {
    echo Info($GLOBALS['I18N']->get('default login is').' admin, '.$GLOBALS['I18N']->get('with password').' phplist').'.';
}

$page = '';
if (isset($_GET['page']) && $_GET['page']) {
    $page = $_GET['page'];
    if (!is_file($page.'.php') || $page == 'logout') {
        $page = $GLOBALS['homepage'];
    }
} else {
    $page = $GLOBALS['homepage'];
}

if (!isset($GLOBALS['msg'])) {
    $GLOBALS['msg'] = '';
    $msg_class = '';
} else {
    $msg_class = " class='result' ";
}

echo '<div '.$msg_class.'>'.$GLOBALS['msg'].'</div>';

?>


<script language="Javascript" type="text/javascript">
    //<![CDATA[
    if (!navigator.cookieEnabled) {
        document.writeln('<div class="error"><?php echo $GLOBALS['I18N']->get('In order to login, you need to enable cookies in your browser')?><\/div>');
    }
    //]]>
</script>
<?php
function footer()
{
    echo '<form method="post" id="forgotpassword-form" action="">';
    echo '<div class="login"><p>';
    echo $GLOBALS['I18N']->get('Forgot password').' ';
    echo $GLOBALS['I18N']->get('Enter your email address').': </p><input type="text" name="forgotpassword" value="" size="30" />';
    echo '  <input class="submit" type="submit" name="process" value="'.$GLOBALS['I18N']->get('Send password').'" />';
    echo '  <div class="clear"></div>';
    echo '</div></form>';
}

//Delete from the DB every token older than certain elapsed time.
function deleteOldTokens()
{
    //  echo "<script>alert('".PASSWORD_CHANGE_TIMEFRAME."');</script>";
    //DELETE FROM phplist_admin_password_request WHERE date_add( date, INTERVAL 1 year ) < now( )
    $SQLquery = sprintf('delete from %s where date_add( date, INTERVAL %s) < now( )',
        $GLOBALS['tables']['admin_password_request'], PASSWORD_CHANGE_TIMEFRAME);
    $query = Sql_Query($SQLquery);
}

//if (ENCRYPT_PASSWORDS) {
if (isset($_POST['password1']) && isset($_POST['password2'])) {
    $SQLquery = sprintf('select date, admin from %s where key_value = "%s" and admin = %d',
        $GLOBALS['tables']['admin_password_request'], sql_escape($_GET['token']), $_POST['admin']);
    $tokenData = Sql_Fetch_Row_Query($SQLquery);
    $p1 = $_POST['password1'];
    $p2 = $_POST['password2'];
    $adminId = $tokenData[1];
    $SQLquery = sprintf('select loginname from %s where id = %d;', $GLOBALS['tables']['admin'], $adminId);
    $adminData = Sql_Fetch_Row_Query($SQLquery);
    $admin = $adminData[0];
    if ($p1 == $p2 && !empty($admin)) {
        //Database update.
        $SQLquery = sprintf("update %s set password='%s', passwordchanged=now() where loginname = '%s';",
            $GLOBALS['tables']['admin'], encryptPass($p1), $admin);
        //#     print $SQLquery;
        $query = Sql_Query($SQLquery);
        echo $GLOBALS['I18N']->get('Your password was changed succesfully').'<br/>';
        echo '<p><a href="./" class="action-button">'.$GLOBALS['I18N']->get('Continue').'</a></p>';
        //Token deletion.
        $SQLquery = sprintf('delete from %s where admin = %d;', $GLOBALS['tables']['admin_password_request'], $adminId);
        $query = Sql_Query($SQLquery);
    } else {
        echo $GLOBALS['I18N']->get('The passwords you entered are not the same.');
    }
} elseif (isset($_GET['token'])) {
    $SQLquery = sprintf("select date, admin from %s where key_value = '".sql_escape($_GET['token'])."';",
        $GLOBALS['tables']['admin_password_request']);
    $row = Sql_Fetch_Row_Query($SQLquery);
    $tokenDate = date('U', strtotime($row[0]));
    $actualDate = date('U');
    $time_exceeded = ($actualDate - $tokenDate) / (60 * 60) > 24;
    if ($row && !$time_exceeded) {
        $date = strtotime($row[0]);
        $adminId = $row[1];
        $final_date = date('U', strtotime($row[0]));
        echo '<p>'.$GLOBALS['I18N']->get('You have requested a password update').'</p>';
        echo "<form method=\"post\" id=\"login-form\" action=\"\">\n";
//      echo "  <input type=\"hidden\" name=\"page\" value=\"$page\" />\n";
        echo '  <input type="hidden" name="admin" value="'.sprintf('%d', $adminId)."\" />\n";
        echo "  <table class=\"loginPassUpdate\" width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n";
//      echo "    <tr><td><span class=\"general\">".$GLOBALS['I18N']->get('Name').":</span></td></tr>\n";
//      echo "    <tr><td>".$row[0]."</td></tr>";
        echo '    <tr><td><span class="general">'.$GLOBALS['I18N']->get('New password').":</span></td></tr>\n";
        echo '    <tr><td><input type="password" name="password1" value="" size="30" /></td></tr>';
        echo '    <tr><td><span class="general">'.$GLOBALS['I18N']->get('Confirm password').':</span></td></tr>';
        echo '    <tr><td><input type="password" name="password2" value="" size="30" /></td></tr>';
        echo '    <tr><td><input class="submit" type="submit" name="process" value="'.$GLOBALS['I18N']->get('Continue').'" /></td></tr>';
        echo '  </table>';
        echo '</form>';
    } else {
        echo '<div class="action-result">';
        echo $GLOBALS['I18N']->get('Unknown token or time expired (More than 24 hrs. passed since the notification email was sent)');
        echo '<br/><br/>';
        session_destroy();
        echo '<p><a href="./" class="action-button">'.$GLOBALS['I18N']->get('Continue').'</a></p>';
        deleteOldTokens();
        exit;
    }
} else {
    echo "<form method=\"post\" id=\"login-form\" action=\"\">\n";
    echo "  <input type=\"hidden\" name=\"page\" value=\"$page\" />\n";
    echo "  <table class=\"loginPassUpdate\" width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n";
    echo '    <tr><td><span class="general">'.$GLOBALS['I18N']->get('Name').":</span></td></tr>\n";
    echo '    <tr><td><input type="text" name="login" value="" size="30"  autofocus="autofocus" /></td></tr>';
    echo '    <tr><td><span class="general">'.$GLOBALS['I18N']->get('Password').':</span></td></tr>';
    echo '    <tr><td><input type="password" name="password" value="" size="30" /></td></tr>';
    echo '    <tr><td><input class="submit" type="submit" name="process" value="'.$GLOBALS['I18N']->get('Continue').'" /></td></tr>';
    echo '  </table>';
    echo '</form>';
    footer();
}
?>
