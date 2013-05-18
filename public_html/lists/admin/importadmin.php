<?php
require_once dirname(__FILE__).'/accesscheck.php';

print '<script language="Javascript" src="js/progressbar.js" type="text/javascript"></script>';

ignore_user_abort();
set_time_limit(500);

ob_end_flush();
if(!empty($_POST['import'])) {

  $test_import = (isset($_POST['import_test']) && $_POST['import_test'] == "yes");
  if(empty($_FILES['import_file']['tmp_name']))
    Fatal_Error($GLOBALS['I18N']->get("File is either to large or does not exist."));
  if(empty($_FILES['import_file']))
    Fatal_Error($GLOBALS['I18N']->get("No file was specified."));

  if ($_FILES['import_file']['tmp_name'] && $_FILES['import_file']['tmp_name'] != "none") {
    $fp = fopen ($_FILES['import_file']['tmp_name'], "r");
    $email_list = fread($fp, filesize ($_FILES['import_file']['tmp_name']));
    fclose($fp);
    unlink($_FILES['import_file']['tmp_name']);
  }

  $import_attribute = array();
  
  // Clean up email file
  $email_list = trim($email_list);
  $email_list = str_replace("\r","\n",$email_list);
  $email_list = str_replace("\n\r","\n",$email_list);
  $email_list = str_replace("\n\n","\n",$email_list);

  // Change delimiter for new line.
  if(isset($_POST['import_record_delimiter']) && $_POST['import_record_delimiter'] != "") {
    $email_list = str_replace($_POST['import_record_delimiter'],"\n",$email_list);
  };

  if (isset($_POST['import_field_delimiter'])) {
    $import_field_delimiter = $_POST['import_field_delimiter'];
  }

  if (!isset($_POST['import_field_delimiter']) || $_POST['import_field_delimiter'] == "" || $_POST['import_field_delimiter'] == "TAB") 
    $import_field_delimiter = "\t";

  // Check file for illegal characters
  $illegal_cha = array("\t");#",", ";", ":", "#",
  for($i=0; $i<count($illegal_cha); $i++) {
    if( ($illegal_cha[$i] != $import_field_delimiter) && ($illegal_cha[$i] != $import_record_delimiter) && (strpos($email_list, $illegal_cha[$i]) != false) )
      Fatal_Error($GLOBALS['I18N']->get("Some characters that are not valid have been found. These might be delimiters. Please check the file and select the right delimiter. Character found:"). $illegal_cha[$i]);
  };

  // Split file/emails into array
  $email_list = explode("\n",$email_list);
  if (sizeof($email_list) > 300 && !$test_import) {
    # this is a possibly a time consuming process, so let's show a progress bar
    print '<script language="Javascript" type="text/javascript"> document.write(progressmeter); start();</script>';
    flush();
    # increase the memory to make sure we're not running out
    ini_set("memory_limit","16M");
  }

  # take the header and parse it to attributes
  $header = array_shift($email_list);
  $attributes = explode($import_field_delimiter,$header);
  for ($i=0;$i<sizeof($attributes);$i++) {
    $attribute = clean($attributes[$i]);
    # check whether they exist
    if (strtolower($attribute) == "email") {
      $emailindex = $i;
    } elseif (strtolower($attribute) == "password") {
      $passwordindex = $i;
    } elseif (strtolower($attribute) == "loginname") {
      $loginnameindex = $i;
    } else {
      $req = Sql_Query("select id from ".$tables["adminattribute"]." where name = \"$attribute\"");
      if (!Sql_Affected_Rows()) {
        # it's a new one # oops, bad coding cut-n-paste '
        $lc_name = substr(str_replace(" ","", strtolower($attribute)),0,10);
        if ($lc_name == "") Fatal_Error($GLOBALS['I18N']->get("Name cannot be empty").": ".$lc_name);
        Sql_Query("select * from ".$tables["adminattribute"]." where tablename = \"$lc_name\"");
        if (Sql_Affected_Rows()) Fatal_Error($GLOBALS['I18N']->get("Name is not unique enough").": ".$attribute);

        if (!$test_import) {
          Sql_Query(sprintf('insert into %s (name,type,listorder,default_value,required,tablename) values("%s","%s",0,"",0,"%s")', $tables["adminattribute"],addslashes($attribute),"textline",$lc_name));
          $attid = Sql_Insert_Id($tables['adminattribute'], 'id');
        } else $attid = 0;
      } else {
        $d = Sql_Fetch_Row($req);
        $attid = $d[0];
      }
      $import_attribute[$attribute] = array("index"=>$i,"record"=>$attid);
    }
  }
  if (!isset($emailindex))
    Fatal_error($GLOBALS['I18N']->get("Cannot find the email in the header"));
  if (!isset($passwordindex))
    Fatal_error($GLOBALS['I18N']->get("Cannot find the password in the header"));
  if (!isset($loginnameindex))
    Fatal_error($GLOBALS['I18N']->get("Cannot find the loginname in the header"));

  $privs = array(
    'subscribers' => !empty($_POST['subscribers']),
    'campaigns' => !empty($_POST['campaigns']),
    'statistics' => !empty($_POST['statistics']),
    'settings' => !empty($_POST['settings'])
  );
  // Parse the lines into records
  $c = 1;$invalid_email_count = 0;

  print '<h3>'.s('Import administrators, please wait').'</h3>';
  flush();

  foreach ($email_list as $line) {
    $values = explode($import_field_delimiter,$line);
    $email = clean($values[$emailindex]);
    $password = $values[$passwordindex];
    $loginname = $values[$loginnameindex];
    $invalid = 0;
    if (!$email) {
      if ($test_input && $show_warnings)
        Warn($GLOBALS['I18N']->get('Record has no email').": ".$c -> $line);
        $email = $GLOBALS['I18N']->get('Invalid Email')." ".$c;
      $invalid = 1;
      $invalid_email_count++;
    }
    if (sizeof($values) != sizeof($attributes) && $test_input && $show_warnings)
      Warn($GLOBALS['I18N']->get("Record has more values than header indicated, this may cause trouble").": ".$email);
    if (!$invalid || ($invalid && $omit_invalid != "yes")) {
      $user_list[$email] = array (
       'password' => $password,
       'loginname' => $loginname
      );
      reset($import_attribute);
      for ($i=0;$i<=sizeof($import_attribute);$i++)
        if ($i != $emailindex)
          $user_list[$email][$i] = clean($values[$i]);
    } else {
     # Warn("Omitting invalid one: $email");
    }
    $c++;
    if ($test_import && $c > 50) break;
  }

  // View test output of emails
  if($test_import) {
  	
    print $GLOBALS['I18N']->get('Test output: There should only be ONE email per line.If the output looks ok, go Back to resubmit for real').'<br/><br/>';
    $i = 1;
    while (list($email,$data) = each ($user_list)) {
      $email = trim($email);
      if(strlen($email) > 4) {
        print "<b>$email</b><br/>";
        $html = "";
        $html .= $GLOBALS['I18N']->get('password').': '.$data["password"]."</br>";
        $html .= $GLOBALS['I18N']->get('login').': '.$data["loginname"]."</br>";
        reset($import_attribute);
        foreach ($import_attribute as $item)
          if ($data[$item["index"]])
            $html .= $attributes[$item["index"]]." -> ".$data[$item["index"]]."<br/>";
        if ($html) print "<blockquote>$html</blockquote>";
      };
      if($i == 50) {break;};
      $i++;
    };

  // Do import
  } else {
    $count_email_add = 0;
    $count_list_add = 0;
    $some = 0;

    if (is_array($user_list))
    while (list($email,$data) = each ($user_list)) {
      if(strlen($email) > 4) {
        // Annoying hack => Much too time consuming. Solution => Set email in users to UNIQUE()
        $result = Sql_query("SELECT id FROM ".$tables["admin"]." WHERE email = '$email'");
        if (Sql_affected_rows()) {
          // Email exists, remember some values to add them to the lists
          $admin = Sql_fetch_array($result);
          $adminid = $admin["id"];
        } else {

          // Email does not exist
          $loginname = $data["loginname"];
          if (!$loginname && $email) {
            $loginname = $email;
            Warn($GLOBALS['I18N']->get("Empty loginname, using email:")." ".$email);
          }
          $query = sprintf('INSERT INTO %s
            (email,loginname,namelc,created,modifiedby,password,superuser,disabled,privileges)
            values("%s","%s","%s",current_timestamp,"%s","%s",0,0,"%s")',
            $tables["admin"],sql_escape($email),sql_escape($loginname),
            normalize($loginname),adminName($_SESSION["logindetails"]["id"]),
            encryptPass($data["password"]),sql_escape(serialize($privs)));
          $result = Sql_query($query);
          $adminid = Sql_Insert_Id($tables['admin'], 'id');
          $count_email_add++;
          $some = 1;
        }

        reset($import_attribute);
        foreach ($import_attribute as $item) {
          if (!empty($data[$item["index"]])) {
            $attribute_index = $item["record"];
            $value = $data[$item["index"]];
            # check whether this is a textline or a selectable item
            $att = Sql_Fetch_Row_Query("select type,tablename,name from ".$tables["adminattribute"]." where id = $attribute_index");
            switch ($att[0]) {
              case "select":
              case "radio":
                $query = "select id from ${table_prefix}adminattr_$att[1] where name = ?";
                $val = Sql_Query_Params($query, array($value));
                # if we don't have this value add it '
                if (!Sql_Num_Rows($val)) {
                  $tn = $table_prefix . 'adminattr_' . $att[1];
                  Sql_Query_Params("insert into $tn (name) values (?)", array($value));
                  Warn($GLOBALS['I18N']->get("Value")." $value ".$GLOBALS['I18N']->get("added to attribute")." $att[2]");
                  $att_value = Sql_Insert_Id($tn, 'id');
                } else {
                  $d = Sql_Fetch_Row($val);
                  $att_value = $d[0];
                }
                break;
              case "checkbox":
                if ($value)
                  $val = Sql_Fetch_Row_Query("select id from $table_prefix"."adminattr_$att[1] where name = \"Checked\"");
                else
                  $val = Sql_Fetch_Row_Query("select id from $table_prefix"."adminattr_$att[1] where name = \"Unchecked\"");
                $att_value = $val[0];
                break;
              default:
                $att_value = $value;
                break;
            }

            Sql_query(sprintf('replace into %s (adminattributeid,adminid,value) values("%s","%s","%s")',
              $tables["admin_attribute"],$attribute_index,$adminid,$att_value));
          }
        }

        if (!empty($_REQUEST['createlist'])) {
          Sql_Query(sprintf('insert into %s (name,description,active,owner)
            values("%s","%s",1,%d)',
            $tables["list"],
            $loginname,
            $GLOBALS['I18N']->get('List for')." $loginname",
            $adminid));
          }
      }; // end if
    }; // end while

    print '<script language="Javascript" type="text/javascript"> finish(); </script>';
    # let's be grammatically correct :-) '
    $dispemail = ($count_email_add == 1) ? $GLOBALS['I18N']->get('new administrator was')." ": $GLOBALS['I18N']->get('new administrators were')." ";

    if(!$some) {
      print "<br/>".$GLOBALS['I18N']->get("All the administrators already exist in the database");
    } else {
      print "$count_email_add $dispemail ".$GLOBALS['I18N']->get("succesfully imported to the database and added to the system.")."<br/>";
    }
  }; // end else
  print PageLinkButton("importadmin",$GLOBALS['I18N']->get("Import some more administrators"));


} else {
 echo formStart('enctype="multipart/form-data" name="import" class="importadminDo" ')?>
<?php
if ($GLOBALS["require_login"] && !isSuperUser()) {
  $access = accessLevel("importadmin");
  if ($access == "owner")
    $subselect = " where owner = ".$_SESSION["logindetails"]["id"];
  elseif ($access == "all")
    $subselect = "";
  elseif ($access == "none")
    $subselect = " where id = 0";
}

?>
</ul>
<div class="panel"><div class="content">
<table class="importadmin" border="1">
<tr><td colspan="2"><?php echo $GLOBALS['I18N']->get('   The file you upload will need to contain the administrators you want to add to the system. The columns need to have the following headers: email, loginname, password. Any other columns will be added as admin attributes.  Warning: the file needs to be plain text. Do not upload binary files like a Word Document.   ')?></td></tr>
<tr><td><?php echo $GLOBALS['I18N']->get('File containing emails')?>:</td><td><input type="file" name="import_file"></td></tr>
<tr><td><?php echo $GLOBALS['I18N']->get('Field Delimiter')?>:</td><td><input type="text" name="import_field_delimiter" size=5> (<?php echo $GLOBALS['I18N']->get('default is TAB')?>)</td></tr>
<tr><td><?php echo $GLOBALS['I18N']->get('Record Delimiter')?>:</td><td><input type="text" name="import_record_delimiter" size=5> (<?php echo $GLOBALS['I18N']->get('default is line break')?>)</td></tr>
<tr><td colspan="2"><?php echo $GLOBALS['I18N']->get('If you check "Test Output", you will get the list of parsed emails on screen, and the database will not be filled with the information. This is useful to find out whether the format of your file is correct. It will only show the first 50 records.')?></td></tr>
<tr><td><?php echo $GLOBALS['I18N']->get('Test output')?>:</td><td><input type="checkbox" name="import_test" value="yes"></td></tr>
<tr><td colspan="2"><?php echo $GLOBALS['I18N']->get('Check this box to create a list for each administrator, named after their loginname')?> <input type=checkbox name="createlist" value="yes" checked></td></tr>
<?php
print '<tr><td colspan="2">';
print '<div id="privileges">
'.s('Privileges').':
<label for="subscribers"><input type="checkbox" name="subscribers" checked="checked" />'.s('Manage subscribers').'</label>
<label for="campaigns"><input type="checkbox" name="campaigns" checked="checked"/>'.s('Send Campaigns').'</label>
<label for="statistics"><input type="checkbox" name="statistics" checked="checked"/>'.s('View Statistics').'</label>
<label for="settings"><input type="checkbox" name="settings" checked="checked"/>'.s('Change Settings').'</label>
</div>';
print '</td></tr>';
print '</td></tr>';
?>
<tr><td><input type="submit" name="import" value="<?php echo $GLOBALS['I18N']->get('Do Import')?>"></td><td>&nbsp;</td></tr>
</table>
</div></div>
<?php } ?>

