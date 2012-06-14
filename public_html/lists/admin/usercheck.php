<?php
require_once dirname(__FILE__).'/accesscheck.php';

$content = '';
if (isset($_POST["usercheck"])) {
  $lsexist = new WebblerListing($GLOBALS["I18N"]->get("Existing subscribers"));
  $lsnonexist = new WebblerListing($GLOBALS["I18N"]->get("Non existing subscribers "));
  $users = explode("\n",$_POST["usercheck"]);
  foreach ($users as $user) {
    $user = trim($user);
    if (isset($_POST['check']) && $_POST["check"] == "foreignkey") {
      $exists = Sql_Query(sprintf('select id,foreignkey,email from %s where foreignkey = "%s"',$tables["user"],sql_escape($user)));
    } else {
      $exists = Sql_Query(sprintf('select id,foreignkey,email from %s where email = "%s"',$tables["user"],sql_escape($user)));
    }
    if (Sql_Num_Rows($exists)) {
      $id = Sql_Fetch_Array($exists);
      $lsexist->addElement($user,PageUrl2("user&amp;id=".$id["id"]));
      $lsexist->addColumn($user,$GLOBALS["I18N"]->get('email'),$id['email']);
      $lsexist->addColumn($user,$GLOBALS["I18N"]->get('key'),$id['foreignkey']);
    } else {
      $lsnonexist->addElement($user);
    }
  }
  print $lsexist->display();
  print $lsnonexist->display();
} else {
  $_POST['usercheck'] = '';
}

/*
print $GLOBALS["I18N"]->get("Page to check the existence of users in the database");
*/

$content .=  '<form method="post" action="">';
$content .=  '<table class="usercheckForm">';
$content .=  '<tr><td>'.$GLOBALS["I18N"]->get("What is the type of information you want to check").'</td></tr>';
$content .=  '<tr><td><label for="foreignkey">'.$GLOBALS["I18N"]->get("Foreign Key").'</label> <input type="radio" id="foreignkey" name="check" value="foreignkey"></td></tr>';
$content .=  '<tr><td><label for="email">'.$GLOBALS["I18N"]->get("Email").'</label> <input type="radio" id="email" name="check" value="email"></td></tr>';
$content .=  '<tr><td>'.$GLOBALS["I18N"]->get("Paste the values to check in this box, one per line").'</td></tr>';
$content .=  '<tr><td><input type="submit" name="continue" value="'.$GLOBALS["I18N"]->get("Continue").'" class="button"></td></tr>';
$content .=  '<tr><td><textarea name="usercheck" rows=30 cols=65>'.htmlspecialchars(stripslashes($_POST['usercheck'])).'</textarea></td></tr>';
$content .=  '<tr><td><input type="submit" name="continue" value="'.$GLOBALS["I18N"]->get("Continue").'" class="button"></td></tr>';
$content .=  '</table></form>';

$p = new UIPanel('',$content);
print $p->display();
