<?php
require_once dirname(__FILE__).'/accesscheck.php';

if (TEST)
  print $GLOBALS['I18N']->get('default login is')." admin, ".$GLOBALS['I18N']->get('with password')." phplist";

$page = '';
if (isset($_GET['page']) && $_GET["page"]) {
  $page = $_GET["page"];
  if (!is_file($page.".php") || $page == "logout") {
    $page = $GLOBALS['homepage'];
  }
} else {
  $page = $GLOBALS['homepage'];
}
if (!isset($GLOBALS['msg'])) $GLOBALS['msg'] = '';
?>
<?php echo $GLOBALS['msg']?>


<script language="Javascript" type="text/javascript">
//<![CDATA[
if (!navigator.cookieEnabled) {
  document.writeln('<div class="error"><?php echo $GLOBALS['I18N']->get('In order to login, you need to enable cookies in your browser')?><\/div>');
}
//]]>
</script>
<?php
function footer(){
  echo '<form method="post" id="forgotpassword-form" action="">';
  echo '  <hr width="50%" size="3"/><p class="login">';
  echo $GLOBALS['I18N']->get('forgot password').':';
  echo $GLOBALS['I18N']->get('enter your email').': <input type="text" name="forgotpassword" value="" size="30" />';
  echo '  <input class="submit" type="submit" name="process" value="'.$GLOBALS['I18N']->get('send password').'" />';
  echo '</p></form>';
}

#Delete from the DB every token older than certain elapsed time.
function deleteOldTokens(){
#  echo "<script>alert('".PASSWORD_CHANGE_TIMEFRAME."');</script>";
  //DELETE FROM phplist_admin_password_request WHERE date_add( date, INTERVAL 1 year ) < now( )  
  $SQLquery = sprintf('delete from %s where date_add( date, INTERVAL %s) < now( )', $GLOBALS['tables']['admin_password_request'], PASSWORD_CHANGE_TIMEFRAME);
  $query = Sql_Query($SQLquery);
}

//if (ENCRYPT_PASSWORDS) {
  if(isset($_POST['password1']) && isset($_POST['password2'])) {
    $p1 = $_POST['password1'];
    $p2 = $_POST['password2'];
    $admin = $_POST['name'];
    if($p1==$p2) {
      #Database update.
      if (ENCRYPT_ADMIN_PASSWORDS) {
        $SQLquery=sprintf("update %s set password='%s', passwordchanged=now() where loginname = '%s';", $GLOBALS['tables']['admin'], md5($p1), $admin);
      }
      else {
        $SQLquery=sprintf("update %s set password='%s', passwordchanged=now() where loginname = '%s';", $GLOBALS['tables']['admin'], $p1, $admin);
      }
      $query = Sql_Query($SQLquery);
      #Retrieve the id.
      $SQLquery=sprintf("select id from %s where loginname = '%s';", $GLOBALS['tables']['admin'], $admin);
      $row = Sql_Fetch_Row_Query($SQLquery);
      if ($row[0]) {
        echo("Your password was changed succesfully.\n");
        echo("To return, click: <a href='./'>Home</a>.\n");
        #Token deletion.
        $SQLquery=sprintf("delete from %s where admin = %d;", $GLOBALS['tables']['admin_password_request'], $row[0]);
        $query = Sql_Query($SQLquery);
      }
    } else {
        echo("Your passwords are different.");
    }
  } elseif (isset($_GET['token'])) {
    $SQLquery = sprintf("select date, admin from %s where key_value = '".$_GET['token']."';", $GLOBALS['tables']['admin_password_request']);
    print "\n";
    $row = Sql_Fetch_Row_Query($SQLquery);
    $tokenDate = date("U", strtotime($row[0]));
    $actualDate = date("U");
    $time_exceeded = ($actualDate-$tokenDate)/(60*60)>24;
    if ($row && !$time_exceeded) {
      $date=strtotime($row[0]);
      $adminId=$row[1];
      $final_date=date("U", strtotime($row[0]));
      $SQLquery=sprintf("select loginname from %s where id = %d;", $GLOBALS['tables']['admin'], $adminId);
      $row = Sql_Fetch_Row_Query($SQLquery);
      echo "You have requested a password update.\n";
      echo "<form method=\"post\" id=\"login-form\" action=\"\">\n";
      echo "  <input type=\"hidden\" name=\"page\" value=\"$page\" />\n";
      echo "  <input type=\"hidden\" name=\"name\" value=\"".$row[0]."\" />\n";
      echo "  <table class=\"loginPassUpdate\" width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n";
      echo "    <tr><td><span class=\"general\">".$GLOBALS['I18N']->get('name').":</span></td></tr>\n";
      echo "    <tr><td>".$row[0]."</td></tr>";
      echo "    <tr><td><span class=\"general\">".$GLOBALS['I18N']->get('new password').":</span></td></tr>\n";
      echo "    <tr><td><input type=\"password\" name=\"password1\" value=\"\" size=\"30\" /></td></tr>";
      echo "    <tr><td><span class=\"general\">".$GLOBALS['I18N']->get('confirm password').":</span></td></tr>";
      echo "    <tr><td><input type=\"password\" name=\"password2\" value=\"\" size=\"30\" /></td></tr>";
      echo "    <tr><td><input class=\"submit\" type=\"submit\" name=\"process\" value=\"".$GLOBALS['I18N']->get('enter')."\" /></td></tr>";
      echo "  </table>";
      echo "</form>";
    } else {
      echo "<p class=\"information\"> Unknown token or time expired (More than 24 hrs. passed since the notification email was sent).<br/><br/>";
      echo "To return and log in again, click: <a href='./'>login</a>.</p><br/><br/>";
      deleteOldTokens();
    }
    } else {
    echo "<form method=\"post\" id=\"login-form\" action=\"\">\n";
    echo "  <input type=\"hidden\" name=\"page\" value=\"$page\" />\n";
    echo "  <table class=\"loginPassUpdate\" width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n";
    echo "    <tr><td><span class=\"general\">".$GLOBALS['I18N']->get('name').":</span></td></tr>\n";
    echo "    <tr><td><input type=\"text\" name=\"login\" value=\"\" size=\"30\" /></td></tr>";
    echo "    <tr><td><span class=\"general\">".$GLOBALS['I18N']->get('password').":</span></td></tr>";
    echo "    <tr><td><input type=\"password\" name=\"password\" value=\"\" size=\"30\" /></td></tr>";
    echo "    <tr><td><input class=\"submit\" type=\"submit\" name=\"process\" value=\"".$GLOBALS['I18N']->get('enter')."\" /></td></tr>";
    echo "  </table>";
    echo "</form>";
    footer();
  }
?>
