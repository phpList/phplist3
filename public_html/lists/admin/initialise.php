<?php
require_once dirname(__FILE__).'/accesscheck.php';

include dirname(__FILE__).'/structure.php';
@ob_end_flush();

$success = 1;
$force = !empty($_GET['force']) && $_GET['force'] == 'yes';

if (!empty($_REQUEST['firstinstall']) && empty($_REQUEST['adminemail'])) {
  print '<form method="post" action="">';
  print '<input type="hidden" name="firstinstall" value="1" />';
  print '<input type="hidden" name="page" value="initialise" />';
  print '<p>'.$GLOBALS['I18N']->get('Please enter your email address.').'</p>';

  /* would be nice to do this, but needs more work
  if (ENCRYPT_ADMIN_PASSWORDS) {
    print '<p>'.$GLOBALS['I18N']->get('After Database initialisation, you will receive an email with a token to set your login password.').'</p>';
    print '<p>'.$GLOBALS['I18N']->get('The initial <i>login name</i> will be' ).' "admin"'.'</p>';
  }
  */
  print '<input type="text" name="adminemail" value="" size="25" /><br/>';
  /*
  if (!ENCRYPT_ADMIN_PASSWORDS) {
    */
    print '<p>'.$GLOBALS['I18N']->get('The initial <i>login name</i> will be' ).' "admin"'.'</p>';
    print '<p>'.$GLOBALS['I18N']->get('Please enter the password you want to use for this account.').' ('.$GLOBALS['I18N']->get('minimum of 8 characters.').')</p>';
    print '<input type="text" name="adminpassword" value="" size="25" id="initialadminpassword" /><br/><br/>';
/*
  } 
*/
  print '<input type="submit" value="'.$GLOBALS['I18N']->get('Continue').'" id="initialisecontinue" disabled="disabled" />';
  print '</form>';
  return;
} 

#var_dump($GLOBALS['plugins']);exit;

print "<h3>".$GLOBALS['I18N']->get("Creating tables")."</h3><br />\n";
while (list($table, $val) = each($DBstruct)) {
  if ($force) {
    if ($table == "attribute") {
      $req = Sql_Query("select tablename from {$tables["attribute"]}");
      while ($row = Sql_Fetch_Row($req))
        Sql_Drop_Table($table_prefix . 'listattr_' . $row[0]);
    }
    Sql_Drop_Table($tables[$table]);
  }
  $query = "CREATE TABLE $tables[$table] (\n";
  while (list($column, $struct) = each($DBstruct[$table])) {
    if (preg_match('/index_\d+/',$column)) {
      $query .= "index " . $struct[0] . ",";
    } elseif (preg_match('/unique_\d+/',$column)) {
      $query .= "unique " . $struct[0] . ",";
    } else {
      $query .= "$column " . $struct[0] . ",";
    }
  }
  # get rid of the last ,
  $query = substr($query,0,-1);
  $query .= "\n) default character set utf8";

  # submit it to the database
  echo $GLOBALS['I18N']->get("Initialising table")." <b>$table</b>";
  if (!$force && Sql_Table_Exists($tables[$table])) {
    Error( $GLOBALS['I18N']->get("Table already exists").'<br />');
    echo "... ".$GLOBALS['I18N']->get("failed")."<br />\n";
    $success = 0;
  } else {
    $res = Sql_Query($query,0);
    $error = Sql_Has_Error($database_connection);
    $success = $force || ($success && !$error);
    if (!$error || $force) {
      if ($table == "admin") {
        # create a default admin
        $_SESSION['firstinstall'] = 1;
        if (isset($_REQUEST['adminemail'])) {
          $adminemail = $_REQUEST['adminemail'];
        } else {
          $adminemail = '';
        }
        if (isset($_REQUEST['adminpassword'])) {
          $adminpass = $_REQUEST['adminpassword'];
        } else {
          $adminpass = 'phplist';
        }
        
        Sql_Query(sprintf('insert into %s (loginname,namelc,email,created,modified,password,passwordchanged,superuser,disabled)
          values("%s","%s","%s",current_timestamp,current_timestamp,"%s",current_timestamp,%d,0)',
          $tables["admin"],"admin","admin",$adminemail,encryptPass($adminpass),1));

        ## let's add them as a subscriber as well
        addNewUser($adminemail,$adminpass);
        /* to send the token at the end, doesn't work yet
        $adminid = Sql_Insert_Id();
        */
      } elseif ($table == "task") {
        while (list($type,$pages) = each ($system_pages)) {
          foreach ($pages as $page => $access_level)
            Sql_Query(sprintf('replace into %s (page,type) values("%s","%s")',
              $tables["task"],$page,$type));
        }
      }

      echo "... ".$GLOBALS['I18N']->get("ok")."<br />\n";
    }
    else
      echo "... ".$GLOBALS['I18N']->get("failed")."<br />\n";
  }
}

## initialise plugins that are already here
foreach ($GLOBALS['plugins'] as $pluginName => $plugin) {
  print s('Initialise plugin').' '.$pluginName.'<br/>';
  if (method_exists($plugin,'initialise')) {
    $plugin->initialise();
  }
  SaveConfig(md5('plugin-'.$pluginName.'-initialised'),time(),0);
}

if ($success) {
  # mark the database to be our current version
  Sql_Replace($tables['config'], array('item' => 'version', 'value' => VERSION, 'editable' => 0), 'item');
  # mark now to be the last time we checked for an update
  Sql_Replace($tables['config'], array('item' => "'updatelastcheck'", 'value' => 'current_timestamp', 'editable' => '0'), 'item', false);
  # add a testlist
  $info = $GLOBALS['I18N']->get("List for testing.");
  $stmt
  = ' insert into ' . $tables['list']
  . '   (name, description, entered, active, owner)'
  . ' values'
  . '   (?, ?, current_timestamp, ?, ?)';
  $result = Sql_Query_Params($stmt, array('test', $info, '0', '1'));
  # add public newsletter list
  $info = s("Sign up to our newsletter");
  $stmt
  = ' insert into ' . $tables['list']
  . '   (name, description, entered, active, owner)'
  . ' values'
  . '   (?, ?, current_timestamp, ?, ?)';
  $result = Sql_Query_Params($stmt, array('newsletter', $info, '1', '1'));
  $body = '
    Version: '.VERSION."\r\n".
   ' Url: '.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']."\r\n";
  printf('<p class="information">'.$GLOBALS['I18N']->get('Success').': <a class="button" href="mailto:info@phplist.com?subject=Successful installation of phplist&body=%s">'.$GLOBALS['I18N']->get('Tell us about it').'</a>. </p>', $body);
  printf('<p class="information">
    '.$GLOBALS['I18N']->get("Please make sure to read the file README.security that can be found in the zip file.").'</p>');
  printf('<p class="information">
    '.$GLOBALS['I18N']->get("Please make sure to").'
    <a href="http://announce.hosted.phplist.com"> '.$GLOBALS['I18N']->get("subscribe to the announcements list")."</a> ".
    $GLOBALS['I18N']->get("to make sure you are updated when new versions come out. Sometimes security bugs are found which make it important to upgrade. Traffic on the list is very low.").' </p>');
  if (ENCRYPT_ADMIN_PASSWORDS && !empty($adminid)) {
    print sendAdminPasswordToken($adminid);
  }
  print '<p>'.$GLOBALS['I18N']->get("Continue with")." ".PageLinkButton("setup",$GLOBALS['I18N']->get("phpList Setup"))."</p>";
} else {
 print ('<div class="initialiseOptions"><ul><li>'.$GLOBALS['I18N']->get("Maybe you want to")." ".PageLinkButton("upgrade",$GLOBALS['I18N']->get("Upgrade")).' '.$GLOBALS['I18N']->get("instead?").'</li>
    <li>'.PageLinkButton("initialise",$GLOBALS['I18N']->get("Force Initialisation"),"force=yes").' '.$GLOBALS['I18N']->get("(will erase all data!)").' '."</li></ul></div>\n");
}
/*
if ($_GET["firstinstall"] || $_SESSION["firstinstall"]) {
  $_SESSION["firstinstall"] = 1;
  print "<p class=".">".$GLOBALS['I18N']->get("Checklist for Installation")."</p>";
  require "setup.php";
}
*/

?>
