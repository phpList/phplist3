<?php


verifyCsrfGetToken();
@ob_end_flush();

$file = $_GET['file'];
if (!file_exists($GLOBALS['tmpdir'] . '/' . $file) || !file_exists($GLOBALS['tmpdir'] . '/' . $file . '.data')) {
    echo s('File not found');

    return;
}
$omit_invalid = false;
if ($_GET['omitinvalid']) {
    $omit_invalid = true;
}

$importdata = unserialize(file_get_contents($GLOBALS['tmpdir'] . '/' . $file . '.data'));

$email_list = file_get_contents($GLOBALS['tmpdir'] . '/' . $file);
include_once dirname(__FILE__) . '/../inc/userlib.php';

// Clean up email file
$email_list = trim($email_list);
$email_list = str_replace("\r", "\n", $email_list);
$email_list = str_replace("\n\r", "\n", $email_list);
$email_list = str_replace("\n\n", "\n", $email_list);

if (isset($importdata['import_record_delimiter'])) {
    $import_record_delimiter = $importdata['import_record_delimiter'];
} else {
    $import_record_delimiter = "\n";
}

// Change delimiter for new line.
if (isset($import_record_delimiter) && $import_record_delimiter != '' && $import_record_delimiter != "\n") {
    $email_list = str_replace($import_record_delimiter, "\n", $email_list);
}

// Split file/emails into array
$email_list = explode("\n", $email_list);

// Parse the lines into records
$hasinfo = 0;
$count_invalid_emails = 0;
foreach ($email_list as $line) {
    $info = '';
    $email = trim($line); //# just take the entire line up to the first space to be the email
    if (strpos($email, ' ')) {
        list($email, $info) = explode(' ', $email);
    }

    if (!is_email($email) && $omit_invalid) {
        unset($email, $info);
        $count_invalid_emails++;
    }
    //# actually looks like the "info" bit will get lost, but
    //# in a way, that doesn't matter
    $user_list[$email] = array(
        'info' => $info,
    );
}

$count_email_add = 0;
$count_email_exist = 0;
$count_list_add = 0;
$additional_emails = 0;
$foundBlacklisted = 0;
$some = 0;
$num_lists = count($importdata['importlists']);
$todo = count($user_list);
$done = 0;
$report = '';
if ($hasinfo) {
    // we need to add an info attribute if it does not exist
    $req = Sql_Query('select id from ' . $tables['attribute'] . ' where name = "info"');
    if (!Sql_Affected_Rows()) {
        // it did not exist
        Sql_Query(sprintf('insert into %s (name,type,listorder,default_value,required,tablename)
       values("info","textline",0,"",0,"info")', $tables['attribute']));
    }
}

// which attributes were chosen, apply to all users
$res = Sql_Query('select * from ' . $tables['attribute']);
$attributes = array();
while ($row = Sql_Fetch_Array($res)) {
    $fieldname = 'attribute' . $row['id'];
    if (isset($importdata[$fieldname])) {
        if (is_array($importdata[$fieldname])) {
            $attributes[$row['id']] = implode(',', $importdata[$fieldname]);
        } else {
            $attributes[$row['id']] = $importdata[$fieldname];
        }
    } else {
        $attributes[$row['id']] = '';
    }
}

foreach ($user_list as $email => $data) {
    //# a lot of spreadsheet include those annoying quotes
    $email = str_replace('"', '', $email);
    set_time_limit(60);
    if ($done % 50 == 0) {
        //  print "$done / $todo<br/>";
        echo '<script type="text/javascript">
      var parentJQuery = window.parent.jQuery;
      parentJQuery("#progressbar").updateProgress("' . $done . ',' . $todo . '");
      </script>';
        flush();
    }
    ++$done;
    if (strlen($email) > 4) {
        $email = addslashes($email);
        // Annoying hack => Much too time consuming. Solution => Set email in users to UNIQUE()
        $result = Sql_query('SELECT id,uniqid FROM ' . $tables['user'] . " WHERE email = '$email'");
        if (Sql_affected_rows()) {
            // Email exist, remember some values to add them to the lists
            $user = Sql_fetch_array($result);
            $userid = $user['id'];
            $uniqid = $user['uniqid'];
            $old_listmembership = array();
            $history_entry = $GLOBALS['I18N']->get('Import of existing subscriber');
            $old_data = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d', $tables['user'], $userid));
            $old_data = array_merge($old_data, getUserAttributeValues('', $userid));
            // and membership of lists
            $req = Sql_Query("select * from {$tables['listuser']} where userid = $userid");
            while ($row = Sql_Fetch_Array($req)) {
                $old_listmembership[$row['listid']] = listName($row['listid']);
            }
            ++$count_email_exist;
        } else {

            // Email does not exist

            // Create unique number
            $randval = mt_rand();
            $uniqid = getUniqid();
            $old_listmembership = array();
            $old_data = array();

            $query = sprintf('INSERT INTO %s (email,entered,confirmed,uniqid,htmlemail,uuid) values("%s",now(),%d,"%s","%s", "%s")',
                $tables['user'], $email, $importdata['notify'] != 'yes', $uniqid,
                isset($importdata['htmlemail']) ? '1' : '0', (string)uuid::generate(4));
            $result = Sql_query($query);
            $userid = Sql_Insert_Id($tables['user'], 'id');

            ++$count_email_add;
            $some = 1;
            $history_entry = $GLOBALS['I18N']->get('Import of new subscriber');

            // add the attributes for this user
            foreach ($attributes as $attr => $value) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                Sql_query(sprintf('replace into %s (attributeid,userid,value) values("%s","%s","%s")',
                    $tables['user_attribute'], $attr, $userid, addslashes($value)));
            }
        }

        //add this user to the lists identified, execpt when found in the blacklist
        $addition = 0;
        $listoflists = '';
        $isBlackListed = isBlackListed($email);
        if (!$isBlackListed) {
            foreach ($importdata['importlists'] as $key => $listid) {
                $query = 'insert ignore INTO ' . $tables['listuser'] . " (userid,listid,entered) values($userid,$listid,now())";
                $result = Sql_query($query);
                // if the affected rows is 0, the user was already subscribed
                $addition = $addition || Sql_Affected_Rows() == 1;
                if (!empty($importdata['listname'][$key])) {
                    $listoflists .= '  * ' . $importdata['listname'][$key] . "\n";
                }
            }
            if ($addition) {
                ++$additional_emails;
            }
        } else {
            //# mark blacklisted, just in case ##17288
            Sql_Query(sprintf('update %s set blacklisted = 1 where id = %d', $tables['user'], $userid));
            ++$foundBlacklisted;
        }

        $subscribemessage = str_replace('[LISTS]', $listoflists, getUserConfig('subscribemessage', $userid));
        if (!TEST && $importdata['notify'] == 'yes' && $addition) {
            sendMail($email, getConfig('subscribesubject'), $subscribemessage, system_messageheaders(), $envelope);
            if ($throttle_import) {
                sleep($throttle_import);
            }
        }
        // history stuff
        $current_data = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d', $tables['user'], $userid));
        $current_data = array_merge($current_data, getUserAttributeValues('', $userid));
        foreach ($current_data as $key => $val) {
            if (!is_numeric($key)) {
                if (isset($old_data[$key]) && $old_data[$key] != $val && $key != 'modified') {
                    $history_entry .= "$key = $val\nchanged from $old_data[$key]\n";
                }
            }
        }
        if (!$history_entry) {
            $history_entry = "\n" . $GLOBALS['I18N']->get('No data changed');
        }
        // check lists
        $listmembership = array();
        $req = Sql_Query("select * from {$tables['listuser']} where userid = $userid");
        while ($row = Sql_Fetch_Array($req)) {
            $listmembership[$row['listid']] = listName($row['listid']);
        }
        $history_entry .= "\n" . $GLOBALS['I18N']->get('List subscriptions:') . "\n";
        foreach ($old_listmembership as $key => $val) {
            $history_entry .= $GLOBALS['I18N']->get('Was subscribed to:') . " $val\n";
        }
        foreach ($listmembership as $key => $val) {
            $history_entry .= $GLOBALS['I18N']->get('Is now subscribed to:') . " $val\n";
        }
        if (!count($listmembership)) {
            $history_entry .= $GLOBALS['I18N']->get('Not subscribed to any lists') . "\n";
        }

        addUserHistory($email, $GLOBALS['I18N']->get('Import by') . ' ' . adminName(), $history_entry);
    } // end if
} // end while

// lets be gramatically correct :-)
$displists = ($num_lists == 1) ? $GLOBALS['I18N']->get('list') : $GLOBALS['I18N']->get('lists');
$dispemail = ($count_email_add == 1) ? $GLOBALS['I18N']->get('new email was') : $GLOBALS['I18N']->get('new emails were');
$dispemail2 = ($additional_emails == 1) ? $GLOBALS['I18N']->get('email was') : $GLOBALS['I18N']->get('emails were');

if ($count_email_exist) {
    $report .= '<br/> ' . s('%d emails already existed in the database', $count_email_exist);
}
if ($count_invalid_emails !== 0) {
    $report .= '<br/> ' . s('%d invalid emails', $count_invalid_emails);
}
if (!$some && !$additional_emails) {
    $report .= '<br/>' . s('All the emails already exist in the database.');
} else {
    $report .= "<br/>$count_email_add $dispemail " . s('succesfully imported to the database and added to') . " $num_lists $displists.<br/>$additional_emails $dispemail2 " . $GLOBALS['I18N']->get('subscribed to the') . " $displists";
}
if ($foundBlacklisted) {
    $report .= '<br/>' . s('%d emails were found on the do-not-send-list and have not been added to the lists',
            $foundBlacklisted);
}

$htmlupdate = $report . '<br/>' .'<div class="input button btn btn-default">'.PageLinkButton('import1', s('Import some more emails')).'</div>';
$htmlupdate = str_replace("'", "\'", $htmlupdate);

$status = '<script type="text/javascript">
      var parentJQuery = window.parent.jQuery;
      parentJQuery("#progressbar").progressbar("destroy");
      parentJQuery("#busyimage").hide();
      parentJQuery("#progresscount").html(\'' . $htmlupdate . '\');
      </script>';

@unlink($GLOBALS['tmpdir'] . '/' . $file);
@unlink($GLOBALS['tmpdir'] . '/' . $file . '.data');

//  print ActionResult($report);
foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
    $plugin->importReport($report);
}
