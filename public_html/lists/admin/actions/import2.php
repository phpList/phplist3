<?php

verifyCsrfGetToken();

require dirname(__FILE__).'/../structure.php';
require dirname(__FILE__).'/../inc/importlib.php';
require dirname(__FILE__).'/../CsvReader.php';

@ob_end_flush();
$status = 'FAIL';
output('<p class="information">'.$GLOBALS['I18N']->get('Reading emails from file ... '));
flush();
if (filesize($_SESSION['import_file']) > 50000) {
    @ini_set('memory_limit', memory_get_usage() + 50 * filesize($_SESSION['import_file']));
}
flush();

$csvReader = new CsvReader($_SESSION['import_file'], $_SESSION['import_field_delimiter']);
$total = $csvReader->totalRows();
output(sprintf('..'.$GLOBALS['I18N']->get('ok, %d lines').'</p>', $total));
--$total; // now the number of subscribers to be imported
$headers = $csvReader->getRow();
$headers = array_unique($headers);
$_SESSION['columnnames'] = $headers;

//## show progress and adjust working space
if ($total > 0) {
    $import_field_delimiter = $_SESSION['import_field_delimiter'];
    if ($total > 300 && !$_SESSION['test_import']) {
        // this is a possibly a time consuming process, so show a progress bar
        echo '<script language="Javascript" type="text/javascript"> document.write(progressmeter); start();</script>';
        flush();
        // increase the memory to make sure we are not running out
        ini_set('memory_limit', '32M');
    }

    //## store the chosen mappings in the $system_attribute_mapping list
    // print "A: ".sizeof($import_attribute);
    foreach ($system_attributes as $key => $val) {
        if (isset($_SESSION['systemindex'][$key])) {
            $system_attribute_mapping[$key] = $_SESSION['systemindex'][$key];
        }
    }

    //## Parse the lines into records
    //  print "<br/>Loading emails .. ";
    flush();
    $count = array();
    $count['email_add'] = 0;
    $count['exist'] = 0;
    $count['list_add'] = 0;
    $count['group_add'] = 0;
    $count['foundblacklisted'] = 0;
    $c = 1;
    $count['invalid_email'] = 0;
    $num_lists = count($_SESSION['lists']);
    $cnt = 0;
    $count['emailmatch'] = 0;
    $count['fkeymatch'] = 0;
    $count['dataupdate'] = 0;
    $count['duplicate'] = 0;
    $additional_emails = 0;

    while ($values = $csvReader->getRow()) {
        set_time_limit(60);
        // will contain attributes to store / change
        $user = array();
        $system_values = array();
        foreach ($system_attribute_mapping as $column => $index) {
            //   print '<br/>'.$column . ' = '. $values[$index];
            if (!empty($values[$index])) {
                $system_values[$column] = $values[$index];
            } else {
                $system_values[$column] = '';
            }
        }
        //# Check completeness
        $index = clean($system_values['email']);
        $invalid = 0;
        if (!$index) {
            if ($_SESSION['show_warnings']) {
                Warn($GLOBALS['I18N']->get('Record has no email').
                    ": $c -> $line");
            }
            $index = $GLOBALS['I18N']->get('Invalid Email')." $c";
            $system_values['email'] = $_SESSION['assign_invalid'];
            $invalid = 1;
            ++$count['invalid_email'];
        }

        //print ("<pre>" . var_dump($_SESSION["import_attribute"]) . "</pre>"); // debug
        //    dbg('_SESSION["import_attribute"',$_SESSION["import_attribute"]); //debug
        if (count($values) != (count($_SESSION['import_attribute']) + count($system_attributes) - count($unused_systemattr)) && !empty($_SESSION['test_import']) && !empty($_SESSION['show_warnings'])) {
            Warn('Record has more values than header indicated ('.
                count($values).'!='.
                (count($_SESSION['import_attribute']) + count($system_attributes) - count($unused_systemattr)).
                "), this may cause trouble: $index");
        }
        if (!$invalid || ($invalid && $_SESSION['omit_invalid'] != 'yes')) {
            $user['systemvalues'] = $system_values;
            reset($_SESSION['import_attribute']);
            $replace = array();
            foreach ($_SESSION['import_attribute'] as $key => $val) {
                if (!empty($values[$val['index']])) {
                    $user[$val['index']] = htmlspecialchars($values[$val['index']]);
                    $replace[$key] = htmlspecialchars($values[$val['index']]);
                }
            }
        } else {
            // Warn("Omitting invalid one: $email");
        }
        $user['systemvalues']['email'] = parsePlaceHolders($system_values['email'],
            array_merge($replace, $system_values, array(
                'number' => $c,
            )));
        $user['systemvalues']['email'] = cleanEmail($user['systemvalues']['email']);
        ++$c;
        if (!isset($user['systemvalues']['htmlemail'])) {
            $user['systemvalues']['htmlemail'] = 1;
        }
        if ($_SESSION['test_import']) {

//      var_dump($user["systemvalues"]);exit;
            $html = '';
            foreach ($user['systemvalues'] as $column => $value) {
                if (strpos($column, 'grouptype_') === 0) {
                    if (isset($system_attributes[$column])) {
                        $column = $system_attributes[$column];
                    }
                }
                if (!empty($column)) {
                    if ($value) {
                        $html .= "$column -> $value<br/>\n";
                    } else {
                        $html .= "$column -> ".$GLOBALS['I18N']->get('clear value')."<br/>\n";
                    }
                }
            }
            //  var_dump($_SESSION["systemindex"]);

            reset($_SESSION['import_attribute']);
            foreach ($_SESSION['import_attribute'] as $column => $item) {
                if (!empty($user[$item['index']])) {
                    if ($item['record'] == 'new') {
                        $html .= ' '.$GLOBALS['I18N']->get('New Attribute').': '.$item['column'];
                    } elseif ($item['record'] == 'skip') {
                        // forget about it
                        $html .= ' '.$GLOBALS['I18N']->get('Skip value').' '.$column.': ';
                    } elseif ($item['record'] != 'system') {
                        $html .= $attributes[$item['record']];
//            var_dump($attributes[$item['record']]);
                    } else {
                        $html .= $item['column'];
                    }
                    $html .= ' -> '.$user[$item['index']].'<br/>';
                }
            }
            if ($html) {
                echo '<blockquote>'.$html.'</blockquote><hr />';
            }
        } else { // not test
            @ob_end_flush();
            if ($cnt % 5 == 0) {
                echo '<script type="text/javascript">
        var parentJQuery = window.parent.jQuery;
        parentJQuery("#progressbar").updateProgress("' .$cnt.','.$total.'");
        </script>';
                flush();
            }
            ++$cnt;

            if (!$invalid || ($invalid && $_SESSION['omit_invalid'] != 'yes')) {
                // do import
                //# create new attributes
                foreach ($_SESSION['import_attribute'] as $column => $item) {
                    if ($item['record'] == 'new') {
                        Sql_Query(sprintf('insert into %s (name,type) values("%s","textline")', $tables['attribute'],
                            addslashes($column)));
                        $attid = Sql_Insert_id();
                        Sql_Query(sprintf('update %s set tablename = "attr%d" where id = %d', $tables['attribute'],
                            $attid, $attid));
                        Sql_Query('create table '.$GLOBALS['table_prefix'].'listattr_attr'.$attid.'
                        (id integer not null primary key auto_increment, name varchar(255), unique (name(150)),
                        listorder integer default 0)');
                        $_SESSION['import_attribute'][$column]['record'] = $attid;
                    }
                }
                $new = 0;
                if (!empty($user['systemvalues']['foreignkey'])) {
                    //         dbg('Importing on FK '.$user["systemvalues"]["foreignkey"].' email :'.$user["systemvalues"]["email"]);
                    $result = Sql_query(sprintf('select id,uniqid from %s where foreignkey = "%s"', $tables['user'],
                        $user['systemvalues']['foreignkey']));
                    // print "<br/>Using foreign key for matching: ".$user["systemvalues"]["foreign key"];
                    ++$count['fkeymatch'];
                    $exists = Sql_Affected_Rows();
                    $existing_user = Sql_fetch_array($result);
                    // check whether the email will clash
                    $clashcheck = Sql_Fetch_Array_Query(sprintf('select id,foreignkey,uniqid from %s
                    where email = "%s"', $tables['user'], $user['systemvalues']['email']));
                    if (!empty($clashcheck['id']) && $clashcheck['id'] != $existing_user['id']) {
                        //https://mantis.phplist.org/view.php?id=17752
                        // if the existing record does not have an FK, we treat it as an update, matched on email
                        if (empty($clashcheck['foreignkey'])) {
                            ++$count['emailmatch'];
                            --$count['fkeymatch'];
                            $exists = 1;
                            $existing_user = $clashcheck;
                        } else {
                            ++$count['duplicate'];
                            $notduplicate = 0;
                            $c = 0;
                            while (!$notduplicate) {
                                ++$c;
                                $req = Sql_Query(sprintf('select id from %s where email = "%s"', $tables['user'],
                                    $GLOBALS['I18N']->get('duplicate').
                                    "$c ".$user['systemvalues']['email']));
                                $notduplicate = !Sql_Affected_Rows();
                            }
                            if (!$_SESSION['retainold']) {
                                Sql_Query(sprintf('update %s set email = "%s" where email = "%s"', $tables['user'],
                                    "duplicate$c ".
                                    $user['systemvalues']['email'], $user['systemvalues']['email']));
                                addUserHistory("duplicate$c ".$user['systemvalues']['email'], 'Duplication clash ',
                                    ' User marked duplicate email after clash with imported record');
                            } else {
                                if ($_SESSION['show_warnings']) {
                                    echo Warn($GLOBALS['I18N']->get('Duplicate Email').' '.$user['systemvalues']['email'].$GLOBALS['I18N']->get(' user imported as ').'&quot;'.$GLOBALS['I18N']->get('duplicate')."$c ".$user['systemvalues']['email'].'&quot;');
                                }
                                $user['systemvalues']['email'] = $GLOBALS['I18N']->get('duplicate')."$c ".$user['systemvalues']['email'];
                            }
                        }
                    }
                } else {
                    dbg('Importing on email '.$user['systemvalues']['email']);
                    $result = Sql_query(sprintf('select id,uniqid from %s where email = "%s"', $tables['user'],
                        $user['systemvalues']['email']));
                    // print "<br/>Using email for matching: ".$user["systemvalues"]["email"];
                    ++$count['emailmatch'];
                    $exists = Sql_Affected_Rows();
                    $existing_user = Sql_fetch_array($result);
                }
                if ($exists) {
                    // User exist, remember some values to add them to the lists
                    ++$count['exist'];
                    $userid = $existing_user['id'];
                    $uniqid = $existing_user['uniqid'];
                } else {
                    // user does not exist
                    $new = 1;
                    // this is very time consuming when importing loads of users as it does a lookup
                    // needs speeding up if possible
                    $uniqid = getUniqid();
                    $confirmed = $_SESSION['notify'] != 'yes' && !preg_match('/Invalid Email/i', $index);

                    $query = sprintf('INSERT INTO %s (email,entered,confirmed,uniqid,htmlemail,uuid)
                    values("%s",now(),%d,"%s",1,"%s")', $tables['user'], $user['systemvalues']['email'], $confirmed,
                        $uniqid, (string) uuid::generate(4));
                    $result = Sql_query($query, 1);
                    $userid = Sql_insert_id();
                    if (!$userid) {
                        // no id returned, so it must have been a duplicate entry
                        if ($_SESSION['show_warnings']) {
                            echo Warn($GLOBALS['I18N']->get('Duplicate Email').' '.$user['systemvalues']['email']);
                        }
                        $c = 0;
                        while (!$userid) {
                            ++$c;
                            $query = sprintf('INSERT INTO %s (email,entered,confirmed,uniqid,htmlemail,uuid)
                            values("%s",now(),%d,"%s",1,"%s")', $tables['user'], $user['systemvalues']['email'].
                                " ($c)", 0, $uniqid, (string) uuid::generate(4));
                            $result = Sql_query($query, 1);
                            $userid = Sql_insert_id();
                        }
                        $user['systemvalues']['email'] = $user['systemvalues']['email']." ($c)";
                    }

                    ++$count['email_add'];
                    $some = 1;
                }

                reset($_SESSION['import_attribute']);
                //   var_dump($_SESSION);exit;
                if ($new || (!$new && $_SESSION['overwrite'] == 'yes')) {
                    $query = '';
                    ++$count['dataupdate'];
                    $old_data = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d', $tables['user'],
                        $userid));
                    $old_data = array_merge($old_data, getUserAttributeValues('', $userid));
                    $history_entry = $GLOBALS['admin_scheme'].'://'.getConfig('website').$GLOBALS['adminpages'].'/?page=user&id='.$userid."\n\n";
                    foreach ($user['systemvalues'] as $column => $value) {
                        if (!empty($column)) { // && !empty($value)) {
                            if ($column == 'groupmapping' || strpos($column, 'grouptype_') === 0) {
                                //# specifically request this group, so that it doesn't interfere with the "groups" which are the ones
                                //# submitted in the form

                                if (strpos($column, 'grouptype_') === 0) {
                                    list($tmp, $type) = explode('_', $column);
                                } else {
                                    $type = $_SESSION['grouptype'];
                                }
                                $type = sprintf('%d', $type);
                                //# verify the type is set
                                if (!in_array($type, array_keys($GLOBALS['config']['usergroup_types']))) {
                                    Warn('Invalid group membership type'.$type);
                                    dbg($type, 'Type not found');
                                }

                                $columnGroups = explode(',', $value);
                                foreach ($columnGroups as $sGroup) {
                                    $sGroup = trim($sGroup);
                                    $groupIdReq = Sql_Fetch_Row_Query(sprintf('select id from groups where name = "%s"',
                                        $sGroup));
                                    if (empty($groupIdReq[0])) {
                                        Sql_Query(sprintf('insert into groups (name) values("%s")', $sGroup));
                                        Warn("Group $sGroup added");
                                        $groupIdReq[0] = Sql_Insert_id();
                                    }
                                    dbg('Adding to group '.$sGroup.' with type '.$GLOBALS['config']['usergroup_types'][$type]);
                                    //# @@ this may cause problems on not-upgraded DBs
                                    Sql_Query(sprintf('replace into user_group (userid,groupid,type) values(%d,%d,%d)',
                                        $userid, $groupIdReq[0], $type));
                                }
                            } else {
                                $query .= sprintf('%s = "%s",', $column, $value);
                            }
                        }
                    }
                    if ($query) {
                        $query = substr($query, 0, -1);
                        // this may cause a duplicate error on email, so add ignore
                        Sql_Query("update ignore {$tables['user']} set $query where id = $userid");
                    }
                    foreach ($_SESSION['import_attribute'] as $item) {
                        if (isset($user[$item['index']]) && is_numeric($item['record']) && strpos($item['record'],
                                'grouptype_') !== 0
                        ) {
                            $attribute_index = $item['record'];
                            $uservalue = $user[$item['index']];
                            // check whether this is a textline or a selectable item
                            $att = Sql_Fetch_Row_Query('select type,tablename,name from '.$tables['attribute']." where id = $attribute_index"); ////
                            switch ($att[0]) {
                                case 'select':
                                case 'radio':
                                    $val = Sql_Query("select id from $table_prefix"."listattr_$att[1] where name = \"$uservalue\"");
                                    // if we do not have this value add it
                                    if (!Sql_Affected_Rows()) {
                                        Sql_Query("insert into $table_prefix"."listattr_$att[1] (name) values(\"$uservalue\")");
                                        Warn("Value $uservalue added to attribute $att[2]");
                                        $user_att_value = Sql_Insert_Id();
                                    } else {
                                        $d = Sql_Fetch_Row($val);
                                        $user_att_value = $d[0];
                                    }
                                    break;
                                case 'checkboxgroup':
                                    $values = explode(',', $uservalue);
                                    $valueIds = array();
                                    foreach ($values as $importValue) {
                                        $val = Sql_Query("select id from $table_prefix"."listattr_$att[1] where name = \"$importValue\"");
                                        // if we do not have this value add it
                                        if (!Sql_Affected_Rows()) {
                                            Sql_Query("insert into $table_prefix"."listattr_$att[1] (name) values(\"$importValue\")");
                                            Warn("Value $importValue added to attribute $att[2]");
                                            $valueIds[] = Sql_Insert_Id();
                                        } else {
                                            $d = Sql_Fetch_Row($val);
                                            $valueIds[] = $d[0];
                                        }
                                    }
                                    $user_att_value = implode(',', $valueIds);
                                    break;
                                case 'checkbox':
                                    $uservalue = trim($uservalue);
                                    //print $uservalue;exit;
                                    if (!empty($uservalue) && $uservalue != 'off') {
                                        $user_att_value = 'on';
                                    } else {
                                        $user_att_value = '';
                                    }
                                    break;
                                case 'date':
                                    //                $user_att_value = parseDate($uservalue);
                                    $user_att_value = $uservalue;
                                    break;
                                default:
                                    $user_att_value = $uservalue;
                                    break;
                            }

                            Sql_query(sprintf('replace into %s (attributeid,userid,value) values(%d,%d,"%s")',
                                $tables['user_attribute'], $attribute_index, $userid, $user_att_value));
                        } else {
                            if ($item['record'] != 'skip') {
                                // add an empty entry if none existed
                                Sql_Query(sprintf('insert ignore into %s (attributeid,userid,value) values(%d,%d,"")',
                                    $tables['user_attribute'], $item['record'], $userid));
                            }
                        }
                    }
                    $current_data = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d', $tables['user'],
                        $userid));
                    $current_data = array_merge($current_data, getUserAttributeValues('', $userid));
                    $information_changed = 0;
                    foreach ($current_data as $key => $val) {
                        if (!is_numeric($key)) {
                            if (isset($old_data[$key]) && $old_data[$key] != $val && $old_data[$key] && $key != 'password' && $key != 'modified') {
                                $information_changed = 1;
                                $history_entry .= "$key = $val\n*changed* from $old_data[$key]\n";
                            }
                        }
                    }
                    if (!$information_changed) {
                        $history_entry .= "\nNo user details changed";
                    }
                    addUserHistory($user['systemvalues']['email'], 'Import by '.adminName(), $history_entry);
                }

                //add this user to the lists identified, except when they are blacklisted
                $isBlackListed = isBlackListed($user['systemvalues']['email']);
                if (!$isBlackListed && is_array($_SESSION['lists'])) {
                    reset($_SESSION['lists']);
                    $addition = 0;
                    $listoflists = '';
                    foreach ($_SESSION['lists'] as $key => $listid) {
                        $query = 'insert ignore INTO '.$tables['listuser']." (userid,listid,entered) values($userid,$listid,now())";
                        $result = Sql_query($query, 1);
                        // if the affected rows is 0, the user was already subscribed
                        $addition = $addition || Sql_Affected_Rows() == 1;
                        $listoflists .= '  * '.listName($key)."\n"; // $_SESSION["listname"][$key] . "\n";
                    }
                    if ($addition) {
                        ++$count['list_add'];
                    }
                    if (!TEST && $_SESSION['notify'] == 'yes' && $addition) {
                        $subscribemessage = str_replace('[LISTS]', $listoflists,
                            getUserConfig('subscribemessage', $userid));
                        if (function_exists('sendmail')) {
                            sendMail($user['systemvalues']['email'], getConfig('subscribesubject'), $subscribemessage,
                                system_messageheaders(), $envelope);
                            if (isset($_SESSION['throttle_import'])) {
                                sleep($_SESSION['throttle_import']);
                            }
                        }
                    }
                } elseif ($isBlackListed) {
                    //# mark blacklisted, just in case ##17288
                    Sql_Query(sprintf('update %s set blacklisted = 1 where id = %d', $tables['user'], $userid));
                    ++$count['foundblacklisted'];
                }
                if (!is_array($_SESSION['groups'])) {
                    $groups = array();
                } else {
                    $groups = $_SESSION['groups'];
                }
                if (isset($everyone_groupid) && !in_array($everyone_groupid, $groups)) {
                    array_push($groups, $everyone_groupid);
                }
                if (defined('IN_WEBBLER') && is_array($groups)) {
                    //add this user to the groups identified
                    reset($groups);
                    $groupaddition = 0;
                    foreach ($groups as $key => $groupid) {
                        if ($groupid) {
                            $query = sprintf('replace INTO user_group (userid,groupid,type) values(%d,%d,%d)', $userid,
                                $groupid, $_SESSION['grouptype']);
                            $result = Sql_query($query);
                            // if the affected rows is 2, the user was already subscribed
                            $groupaddition = $groupaddition || Sql_Affected_Rows() == 1;
                        }
                    }
                    if ($groupaddition) {
                        ++$count['group_add'];
                    }
                }
            }
        } // end else not test
        if ($_SESSION['test_import'] && $c > 50) {
            break;
        }
    }

    $report = '';
    if (empty($some) && !$count['list_add']) {
        $report .= '<br/>'.s('All the emails already exist in the database and are member of the lists');
    } else {
        $report .= '<br/>'.s('%d emails succesfully imported to the database and added to %d lists.',
                $count['email_add'], $num_lists);
        $report .= '<br/>'.s('%d emails subscribed to the lists', $count['list_add']);
        if ($count['exist']) {
            $report .= '<br/>'.s('%d emails already existed in the database', $count['exist']);
        }
    }
    if ($count['invalid_email']) {
        $report .= '<br/>'.s('%d Invalid Emails found.', $count['invalid_email']);
        if (!$_SESSION['omit_invalid']) {
            $report .= '<br/>'.s('These records were added, but the email has been made up from ').$_SESSION['assign_invalid'];
        } else {
            $report .= '<br/>'.s('These records were deleted. Check your source and reimport the data. Duplicates will be identified.');
        }
    }
    if ($count['duplicate']) {
        $report .= '<br/>'.s('%d duplicate emails found.', $count['duplicate']);
    }
    if ($_SESSION['overwrite'] == 'yes') {
        $report .= '<br/>'.s('Subscriber data was updated for %d subscribers', $count['dataupdate']);
    }
    if ($count['foundblacklisted']) {
        $report .= '<br/>'.s('%d emails were on the blacklist and have not been added to the lists',
                $count['foundblacklisted']);
    }
    $report .= '<br/>'.s('%d subscribers were matched by foreign key, %d by email', $count['fkeymatch'],
            $count['emailmatch']);
    if (!$GLOBALS['commandline']) {
        echo $report;
        if (function_exists('sendmail')) {
            sendMail(getConfig('admin_address'), $GLOBALS['I18N']->get('phplist Import Results'), $report);
        }
        if (function_exists('logevent')) {
            logEvent($report);
        }
        foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
            $plugin->importReport($report);
        }
    } else {
        output($report);
    }
    $htmlupdate = $report.'<br/>'.'<div class="button btn btn-default">'.PageLinkButton('import2', s('Import some more emails')).'</div>';
    $htmlupdate = str_replace("'", "\'", $htmlupdate);

    clearImport();
    $status = '<script type="text/javascript">
      var parentJQuery = window.parent.jQuery;
      parentJQuery("#progressbar").progressbar("destroy");
      parentJQuery("#busyimage").hide();
      parentJQuery("#progresscount").html(\'' .$htmlupdate.'\');
      </script>';
}
