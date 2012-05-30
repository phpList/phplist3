<?php
require_once dirname(__FILE__).'/accesscheck.php';

$subselect = '';
$report = '';
if (!ALLOW_IMPORT) {
  print '<p class="information">'.$GLOBALS['I18N']->get('import is not available').'</p>';
  return;
}

print '<script language="Javascript" src="js/progressbar.js" type="text/javascript"></script>';

ignore_user_abort();
set_time_limit(500);
ob_end_flush();
?>
<p class="information">

<?php

if (!isset($GLOBALS["tmpdir"])) {
  $GLOBALS["tmpdir"] = ini_get("upload_tmp_dir");
}
if (!is_dir($GLOBALS["tmpdir"]) || !is_writable($GLOBALS["tmpdir"])) {
  $GLOBALS["tmpdir"] = ini_get("upload_tmp_dir");
}
#if (ini_get("open_basedir")) {
if (!is_dir($GLOBALS["tmpdir"]) || !is_writable($GLOBALS["tmpdir"])) {
  Warn($GLOBALS['I18N']->get('The temporary directory for uploading is not writable, so import will fail')." (".$GLOBALS["tmpdir"].")");
}

$import_lists = getSelectedLists('importlists');

if(isset($_REQUEST['import'])) {

  $test_import = (isset($_POST["import_test"]) && $_POST["import_test"] == "yes");

  if(empty($_FILES["import_file"])) {
    Fatal_Error($GLOBALS['I18N']->get('No file was specified. Maybe the file is too big?'));
    return;
  }
  if(!$_FILES["import_file"]) {
    Fatal_Error($GLOBALS['I18N']->get('File is either too large or does not exist.'));
    return;
  }
  if (filesize($_FILES["import_file"]['tmp_name']) > 1000000) {
    Fatal_Error($GLOBALS['I18N']->get('File too big, please split it up into smaller ones'));
    return;
  }
/*
  if( !preg_match("/^[0-9A-Za-z_\.\-\/\s \(\)]+$/", $_FILES["import_file"]["name"]) ) {
    Fatal_Error($GLOBALS['I18N']->get('Use of wrong characters: ').$_FILES["import_file"]["name"]);
    return;
  }
*/
  # don't send notification, but use processqueue instead
  $_POST['notify'] = 'no'; 
  if (!$_POST["notify"] && !$test_import) {
    Fatal_Error($GLOBALS['I18N']->get('Please choose whether to sign up immediately or to send a notification'));
    return;
  }
  $notify = $_POST["notify"];
  $throttle_import = $_POST["throttle_import"];

  if ($_FILES["import_file"] && filesize($_FILES["import_file"]['tmp_name']) > 10) {
    $newfile = $GLOBALS['tmpdir'].'/import'. $GLOBALS['installation_name'].time();
    move_uploaded_file($_FILES['import_file']['tmp_name'], $newfile);
    if( !($fp = fopen ($newfile, "r"))) {
      Fatal_Error($GLOBALS['I18N']->get('Cannot read file. It is not readable !')." (".$newfile.")");
      return;
     }
    $email_list = fread($fp, filesize ($newfile));
    fclose($fp);
  } elseif ($_FILES["import_file"]) {
    Fatal_Error($GLOBALS['I18N']->get('Something went wrong while uploading the file. Empty file received. Maybe the file is too big, or you have no permissions to read it.'));
    return;
  }

  // Clean up email file
  $email_list = trim($email_list);
  $email_list = str_replace("\r","\n",$email_list);
  $email_list = str_replace("\n\r","\n",$email_list);
  $email_list = str_replace("\n\n","\n",$email_list);

  if (isset($_REQUEST['import_record_delimiter'])) {
    $import_record_delimiter = $_REQUEST['import_record_delimiter'];
  } else {
    $import_record_delimiter = "\n";
  }

  // Change delimiter for new line.
  if(isset($import_record_delimiter) && $import_record_delimiter != "" && $import_record_delimiter != "\n") {
    $email_list = str_replace($import_record_delimiter,"\n",$email_list);
  };

  // Split file/emails into array
  $email_list = explode("\n",$email_list);

  // Parse the lines into records
  $hasinfo = 0;
  foreach ($email_list as $line) {
    $info = '';
    $email = trim($line); ## just take the entire line up to the first space to be the email
    if (strpos($email,' ')) {
      list($email,$info) = explode(' ',$email);
    }

    ## actually looks like the "info" bit will get lost, but
    ## in a way, that doesn't matter
    $user_list[$email] = array (
      'info' => $info,
    );
  }

  if (sizeof($email_list) > 300 && !$test_import) {
    # this is a possibly a time consuming process, so lets show a progress bar
    print '<script language="Javascript" type="text/javascript"> document.write(progressmeter); start();</script>';
    flush();
    # increase the memory to make sure we are not running out
    ini_set("memory_limit","16M");
  }

  // View test output of emails
  if($test_import) {
    print $GLOBALS['I18N']->get('Test output:').':<br/>'.$GLOBALS['I18N']->get('There should only be ONE email per line.').'<br/>'.$GLOBALS['I18N']->get('If the output looks ok, go').' <a href="javascript:history.go(-1)">'.$GLOBALS['I18N']->get('back').'</a>'.$GLOBALS['I18N']->get(' to resubmit for real').'<br/><br/>';
    $i = 1;
    while (list($email,$data) = each ($user_list)) {
      $email = trim($email);
      if(strlen($email) > 4) {
        print "<b>$email</b><br/>";
        $html = "";
        foreach (array("info") as $item)
          if ($user_list[$email][$item])
            $html .= "$item -> ".$user_list[$email][$item]."<br/>";
        if ($html) print "<blockquote>$html</blockquote>";
      };
      if($i == 50) {break;};
      $i++;
    };

  // Do import
  } else {
    $count_email_add = 0;
    $count_email_exist = 0;
    $count_list_add = 0;
    $additional_emails = 0;
    $some = 0;
    $num_lists = sizeof($import_lists);
    $todo = sizeof($user_list);
    $done = 0;
    if ($hasinfo) {
      # we need to add an info attribute if it does not exist
      $req = Sql_Query("select id from ".$tables["attribute"]." where name = \"info\"");
      if (!Sql_Affected_Rows()) {
        # it did not exist
        Sql_Query(sprintf('insert into %s (name,type,listorder,default_value,required,tablename)
         values("info","textline",0,"",0,"info")', $tables["attribute"]));
      }
    }

    # which attributes were chosen, apply to all users
    $res = Sql_Query("select * from ".$tables["attribute"]);
    $attributes = array();
    while ($row = Sql_Fetch_Array($res)) {
      $fieldname = "attribute" .$row["id"];
      if (isset($_POST[$fieldname])) {
        if (is_array($_POST[$fieldname])) {
          $attributes[$row["id"]] = join(',',$_POST[$fieldname]);
        } else {
          $attributes[$row["id"]] = $_POST[$fieldname];
        }
      } else {
        $attributes[$row["id"]] = '';
      }
    }

    while (list($email,$data) = each ($user_list)) {
      ## a lot of spreadsheet include those annoying quotes
      $email = str_replace('"', '', $email);      
      $done++;
      if ($done % 50 ==0) {
        print "$done/$todo<br/>";
        flush();
      }
      if(strlen($email) > 4) {
        $email = addslashes($email);
        // Annoying hack => Much too time consuming. Solution => Set email in users to UNIQUE()
        $result = Sql_query("SELECT id,uniqid FROM ".$tables["user"]." WHERE email = '$email'");
        if (Sql_affected_rows()) {
          // Email exist, remember some values to add them to the lists
          $user = Sql_fetch_array($result);
          $userid = $user["id"];
          $uniqid = $user["uniqid"];
          $history_entry = $GLOBALS['I18N']->get('Import of existing subscriber');
          $old_data = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d',$tables["user"],$userid));
          $old_data = array_merge($old_data,getUserAttributeValues('',$userid));
          # and membership of lists
          $req = Sql_Query("select * from {$tables["listuser"]} where userid = $userid");
          while ($row = Sql_Fetch_Array($req)) {
            $old_listmembership[$row["listid"]] = listName($row["listid"]);
          }
          $count_email_exist++;
        } else {

          // Email does not exist

          // Create unique number
          mt_srand((double)microtime()*1000000);
          $randval = mt_rand();
          include_once dirname(__FILE__)."/commonlib/lib/userlib.php";
          $uniqid = getUniqid();

          $query = sprintf('INSERT INTO %s (email,entered,confirmed,uniqid,htmlemail) values("%s",current_timestamp,%d,"%s","%s")',
          $tables["user"],$email,$notify != "yes",$uniqid,isset($_POST['htmlemail']) ? '1':'0');
          $result = Sql_query($query);
          $userid = Sql_Insert_Id($tables['user'], 'id');

          $count_email_add++;
          $some = 1;
          $history_entry = $GLOBALS['I18N']->get('Import of new subscriber');

          # add the attributes for this user
          foreach($attributes as $attr => $value) {
            if (is_array($value)) {
              $value = join(',',$value);
            }
            Sql_query(sprintf('replace into %s (attributeid,userid,value) values("%s","%s","%s")',
              $tables["user_attribute"],$attr,$userid,addslashes($value)));
          }
        }

        #add this user to the lists identified
        $addition = 0;
        $listoflists = "";
        foreach($import_lists as $key => $listid) {
          $query = "replace INTO ".$tables["listuser"]." (userid,listid,entered) values($userid,$listid,current_timestamp)";
          $result = Sql_query($query);
          # if the affected rows is 2, the user was already subscribed
          $addition = $addition || Sql_Affected_Rows() == 1;
          if (!empty($_POST['listname'][$key])) {
            $listoflists .= "  * ".$_POST['listname'][$key]."\n";
          }
        }
        if ($addition) {
          $additional_emails++;
        }

        $subscribemessage = str_replace('[LISTS]', $listoflists, getUserConfig("subscribemessage",$userid));
        if (!TEST && $notify == "yes" && $addition) {
          sendMail($email, getConfig("subscribesubject"), $subscribemessage,system_messageheaders(),$envelope);
          if ($throttle_import) {
            sleep($throttle_import);
          }
        }
        # history stuff
        $current_data = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d',$tables["user"],$userid));
        $current_data = array_merge($current_data,getUserAttributeValues('',$userid));
        foreach ($current_data as $key => $val) {
          if (!is_numeric($key))
            if ($old_data[$key] != $val && $key != "modified") {
            $history_entry .= "$key = $val\nchanged from $old_data[$key]\n";
          }
        }
        if (!$history_entry) {
          $history_entry = "\n".$GLOBALS['I18N']->get('No data changed');
        }
        # check lists
        $req = Sql_Query("select * from {$tables["listuser"]} where userid = $userid");
        while ($row = Sql_Fetch_Array($req)) {
          $listmembership[$row["listid"]] = listName($row["listid"]);
        }
        $history_entry .= "\n".$GLOBALS['I18N']->get('List subscriptions:')."\n";
        foreach ($old_listmembership as $key => $val) {
          $history_entry .= $GLOBALS['I18N']->get('Was subscribed to:')." $val\n";
        }
        foreach ($listmembership as $key => $val) {
          $history_entry .= $GLOBALS['I18N']->get('Is now subscribed to:')." $val\n";
        }
        if (!sizeof($listmembership)) {
          $history_entry .= $GLOBALS['I18N']->get('Not subscribed to any lists')."\n";
        }

        addUserHistory($email,$GLOBALS['I18N']->get('Import by ').adminName(),$history_entry);

      }; // end if
    }; // end while

    print '<script language="Javascript" type="text/javascript"> finish(); </script>';
    # lets be gramatically correct :-)
    $displists = ($num_lists == 1) ? $GLOBALS['I18N']->get('list'): $GLOBALS['I18N']->get('lists');
    $dispemail = ($count_email_add == 1) ? $GLOBALS['I18N']->get('new email was'): $GLOBALS['I18N']->get('new emails were');
    $dispemail2 = ($additional_emails == 1) ? $GLOBALS['I18N']->get('email was'): $GLOBALS['I18N']->get('emails were');

    if ($count_email_exist) {
      $report .= "<br/>$count_email_exist ".$GLOBALS['I18N']->get('emails existed in the database');
    }
    if(!$some && !$additional_emails) {
      $report .= "<br/>".$GLOBALS['I18N']->get('All the emails already exist in the database.');
    } else {
      $report .= "<br/>$count_email_add $dispemail ".$GLOBALS['I18N']->get('succesfully imported to the database and added to')." $num_lists $displists.<br/>$additional_emails $dispemail2 ".$GLOBALS['I18N']->get('subscribed to the')." $displists";
    }
    print ActionResult($report);
    foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
      $plugin->importReport($report);
    }
  }; // end else
  print '<p class="button">'.PageLink2("import1",$GLOBALS['I18N']->get('Import some more emails')).'</p>';


} else {
?>


<?php echo FormStart(' enctype="multipart/form-data" name="import"')?>
<?php
if ($GLOBALS["require_login"] && !isSuperUser()) {
  $access = accessLevel("import1");
  switch ($access) {
    case "owner":
      $subselect = " where owner = ".$_SESSION["logindetails"]["id"];break;
    case "all":
      $subselect = "";break;
    case "none":
    default:
      $subselect = " where id = 0";break;
  }
}

$result = Sql_query("SELECT id,name FROM ".$tables["list"]."$subselect ORDER BY listorder");
$c=0;
if (Sql_Affected_Rows() == 1) {
  $row = Sql_fetch_array($result);
  printf('<input type="hidden" name="listname[%d]" value="%s"><input type="hidden" name="importlists[%d]" value="%d">'.$GLOBALS['I18N']->get('adding_users').' <b>%s</b>',$c,stripslashes($row["name"]),$c,$row["id"],stripslashes($row["name"]));
} else {
  print '<p class="button">'.$GLOBALS['I18N']->get('select_lists').'</p>';
  print ListSelectHTML($import_lists,'importlists',$subselect);
}

?>


<script language="Javascript" type="text/javascript">

var fieldstocheck = new Array();
var fieldnames = new Array();
function addFieldToCheck(value,name) {
  fieldstocheck[fieldstocheck.length] = value;
  fieldnames[fieldnames.length] = name;
}

</script>
<table class="import1" border="1">
<tr><td colspan="2"><?php echo $GLOBALS['I18N']->get('The file you upload will need to contain the emails you want to add to these lists. Anything after the email will be added as attribute "Info" of the Subscriber. You can specify the rest of the attributes of these subscribers below. Warning: the file needs to be plain text. Do not upload binary files like a Word Document.'); ?></td></tr>
<tr><td><?php echo $GLOBALS['I18N']->get('File containing emails:'); ?></td><td><input type="file" name="import_file"></td></tr>
<tr><td colspan="2"><?php echo $GLOBALS['I18N']->get('If you check "Test Output", you will get the list of parsed emails on screen, and the database will not be filled with the information. This is useful to find out whether the format of your file is correct. It will only show the first 50 records.'); ?></td></tr>
<tr><td><?php echo $GLOBALS['I18N']->get('Test output:'); ?></td><td><input type="checkbox" name="import_test" value="yes"></td></tr>
<!--tr><td colspan="2"><?php echo $GLOBALS['I18N']->get('If you choose "send notification email" the subscribers you are adding will be sent the request for confirmation of subscription to which they will have to reply. This is recommended, because it will identify invalid emails.'); ?></td></tr>
<tr><td><?php echo $GLOBALS['I18N']->get('Send Notification email'); ?><input type="radio" name="notify" value="yes"></td><td><?php echo $GLOBALS['I18N']->get('Make confirmed immediately'); ?><input type="radio" name="notify" value="no"></td></tr>
<tr><td colspan="2"><?php echo $GLOBALS['I18N']->get('If you are going to send notification to users, you may want to add a little delay between messages')?></td></tr>
<tr><td><?php echo $GLOBALS['I18N']->get('Notification throttle')?>:</td><td><input type="text" name="throttle_import" size="5"> <?php echo $GLOBALS['I18N']->get('(default is nothing, will send as fast as it can)')?></td></tr-->
<?php
include_once dirname(__FILE__)."/subscribelib2.php";
print ListAllAttributes();
?>

<tr><td><p class="input"><input type="submit" name="import" value="<?php echo $GLOBALS['I18N']->get('import'); ?>"></p></td><td>&nbsp;</td></tr>
</table>
</form>
<?php } ?>

