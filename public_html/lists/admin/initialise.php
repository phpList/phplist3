<?php
require_once dirname(__FILE__).'/accesscheck.php';

include dirname(__FILE__).'/structure.php';
@ob_end_flush();

$success = 1;
if (!isset($_REQUEST['adminname'])) $_REQUEST['adminname'] = '';
if (!isset($_REQUEST['orgname'])) $_REQUEST['orgname'] = '';
if (!isset($_REQUEST['adminpassword'])) $_REQUEST['adminpassword'] = '';
if (!isset($_REQUEST['adminemail'])) $_REQUEST['adminemail'] = '';
if (isset($_REQUEST['adminemail']) && !is_email($_REQUEST['adminemail'])) {
  $_REQUEST['adminemail'] = '';
}

$force = !empty($_GET['force']) && $_GET['force'] == 'yes';

if (!empty($_REQUEST['firstinstall']) && (empty($_REQUEST['adminemail']) || strlen($_REQUEST['adminpassword']) < 8)) {
  print '<noscript>';
  print '<div class="error">'.s('To install phpList, you need to enable Javascript').'</div>';
  print '</noscript>';
  
  if ($_SESSION['adminlanguage']['iso'] != $GLOBALS['default_system_language'] && 
    in_array($_SESSION['adminlanguage']['iso'],array_keys($GLOBALS['LANGUAGES']))) {
      print '<div class="info error">'.s('The default system language is different from your browser language.').'<br/>';
      print s('You can set <pre>$default_system_language = "%s";</pre> in your config file, to use your language as the fallback language.',$_SESSION['adminlanguage']['iso']).'<br/>';
      print s('It is best to do this before initialising the database.');
      print '</div>';
  }

  print '<form method="post" action="" class="configForm">';
  print '<fieldset><legend>'.s('phpList initialisation').' </legend>
    <input type="hidden" name="firstinstall" value="1" />';
  print '<input type="hidden" name="page" value="initialise" />';
  print '<label for="adminname">'.s('Please enter your name.').'</label>';
  print '<div class="field"><input type="text" name="adminname" class="error missing" value="'.htmlspecialchars($_REQUEST['adminname']).'" /></div>';
  print '<label for="orgname">'.s('The name of your organisation').'</label>';
  print '<input type="text" name="orgname" value="'.htmlspecialchars($_REQUEST['orgname']).'" />';
  print '<label for="adminemail">'.s('Please enter your email address.').'</label>';

  /* would be nice to do this, but needs more work
  if (ENCRYPT_ADMIN_PASSWORDS) {
    print '<p>'.$GLOBALS['I18N']->get('After Database initialisation, you will receive an email with a token to set your login password.').'</p>';
    print '<p>'.$GLOBALS['I18N']->get('The initial <i>login name</i> will be' ).' "admin"'.'</p>';
  }
  */
  print '<input type="text" name="adminemail" value="'.htmlspecialchars($_REQUEST['adminemail']).'" />';
  print s('The initial <i>login name</i> will be' ).' "admin"'.'<br/>';
  print '<label for="adminpassword">'.s('Please enter the password you want to use for this account.').' ('.$GLOBALS['I18N']->get('minimum of 8 characters.').')</label>';
  print '<input type="text" name="adminpassword" value="" id="initialadminpassword" /><br/><br/>';
  print '<input type="submit" value="'.s('Continue').'" id="initialisecontinue" disabled="disabled" />';
  print '</fieldset></form>';
  return;
} 

#var_dump($GLOBALS['plugins']);exit;

print "<h3>".s("Creating tables")."</h3><br />\n";
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
  echo s("Initialising table")." <b>$table</b>";
  if (!$force && Sql_Table_Exists($tables[$table])) {
    Error( s("Table already exists").'<br />');
    echo "... ".s("failed")."<br />\n";
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
        $userid = addNewUser($adminemail,$adminpass);
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

      echo "... ".s("ok")."<br />\n";
    }
    else
      echo "... ".s("failed")."<br />\n";
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
  SaveConfig('version',VERSION,0);
  # mark now to be the last time we checked for an update
  Sql_Replace($tables['config'], array('item' => "updatelastcheck", 'value' => 'current_timestamp', 'editable' => '0'), 'item', false);
  SaveConfig('admin_address',$_REQUEST['adminemail'],1);
  SaveConfig('message_from_name',strip_tags($_REQUEST['adminname']),1);
  if (!empty($_REQUEST['orgname'])) {
    SaveConfig('organisation_name',strip_tags($_REQUEST['orgname']),1);
  } elseif (!empty($_REQUEST['adminname'])) {
    SaveConfig('organisation_name',strip_tags($_REQUEST['adminname']),1);
  } else {
    SaveConfig('organisation_name',strip_tags($_REQUEST['adminemail']),1);
  }
 
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
  
  ## add the admin to the lists
  Sql_Query(sprintf('insert into %s (listid, userid, entered) values(%d,%d,now())',$tables['listuser'],1,$userid));
  Sql_Query(sprintf('insert into %s (listid, userid, entered) values(%d,%d,now())',$tables['listuser'],2,$userid));
 
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
  # make sure the 0 template has the powered by image
  $query
  = ' insert into %s'
  . '   (template, mimetype, filename, data, width, height)'
  . ' values (0, ?, ?, ?, ?, ?)';
  $query = sprintf($query, $GLOBALS["tables"]["templateimage"]);
  Sql_Query_Params($query, array('image/png', 'powerphplist.png', $newpoweredimage, 70, 30));
  print '<p>'.$GLOBALS['I18N']->get("Continue with")." ".PageLinkButton("setup",$GLOBALS['I18N']->get("phpList Setup"))."</p>";

  unset($_SESSION['hasI18Ntable']);
  ## load language files
  # this is too slow
  #  $GLOBALS['I18N']->initFSTranslations();


} else {
 print ('<div class="initialiseOptions"><ul><li>'.s("Maybe you want to")." ".PageLinkButton("upgrade",s("Upgrade")).' '.s("instead?").'</li>
    <li>'.PageLinkButton("initialise",s("Force Initialisation"),"force=yes").' '.s("(will erase all data!)").' '."</li></ul></div>\n");
}
/*
if ($_GET["firstinstall"] || $_SESSION["firstinstall"]) {
  $_SESSION["firstinstall"] = 1;
  print "<p class=".">".$GLOBALS['I18N']->get("Checklist for Installation")."</p>";
  require "setup.php";
}
*/

?>
