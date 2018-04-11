<script language="Javascript" type="text/javascript">
    function checkSubFolders(folder) {
        var element = document.getElementById(folder);
        var isset = element.checked;
        for (var i = 0; i < document.folderlist.length; i++) {
            var name = document.folderlist.elements[i].value;
            if (name.indexOf(element.name) >= 0)
                document.folderlist.elements[i].checked = isset;
        }
    }
</script>

<?php
require_once dirname(__FILE__).'/accesscheck.php';
if (!ALLOW_IMPORT) {
    echo '<p class="information">'.$GLOBALS['I18N']->get('import is not available').'</p>';

    return;
}

ob_end_flush();
echo '<p class="button">'.$GLOBALS['I18N']->get('Import emails from IMAP folders').'</p>';
$email_header_fields = array('to', 'from', 'cc', 'bcc', 'reply_to', 'sender', 'return_path');

if ($require_login && !isSuperUser()) {
    $access = accessLevel('import3');
    if ($access == 'owner') {
        $subselect = ' where owner = '.$_SESSION['logindetails']['id'];
    } elseif ($access == 'all') {
        $subselect = '';
    } elseif ($access == 'none') {
        $subselect = ' where id = 0';
    }
}

$result = Sql_query('SELECT id,name FROM '.$tables['list']." $subselect ORDER BY listorder");
while ($row = Sql_fetch_array($result)) {
    $available_lists[$row['id']] = $row['name'];
    $some = 1;
}
if (!$some) {
    echo $GLOBALS['I18N']->get('No lists available').', '.PageLink2('editlist',
            $GLOBALS['I18N']->get('Add a list'));
}

function mailBoxName($mailbox, $delimiter, $level)
{
    $folder_path = explode($delimiter, $mailbox);
    if ($level > count($folder_path)) {
        return 0;
    } else {
        return $folder_path[$level];
    }
}

function mailBoxParent($mailbox, $delimiter, $level)
{
    $folder_path = explode($delimiter, $mailbox);
    $parent = '';
    for ($i = 0; $i < $level; ++$i) {
        if ($folder_path[$i] == '') {
            $parent .= 'INBOX';
        } else {
            $parent .= '.'.$folder_path[$i];
        }
    }

    return $parent;
}

$nodesdone = array();

function printTree($tree, $root, $delim)
{
    reset($tree);
//  print "<hr/>ROOT: $root<br/>";
    foreach ($tree as $node => $rec) {
        if (!in_array($node, $GLOBALS['nodesdone'])) {
            if (preg_match('#'.preg_quote($root).'#i', $node)) {
                echo '<li>';
                printf('<input type="checkbox" name="checkfolder[]" value="%s">&nbsp;', $node);
                echo "<b>$node</b>\n";
                printf('<input type="checkbox" name="%s" id="%s" value="1"
          onchange="checkSubFolders(\'%s\');"> (add subfolders)', $node, $node, $node);
                echo '</li>';
                echo "<ul>\n";
                foreach ($tree[$node]['children'] as $leaf) {
                    if ($tree[$node.$delim.$leaf]) {
                        //          print "<ul>";
                        printTree($tree, $node.$delim.$leaf, $delim);
                        //          print "</ul>";
                    } else {
                        //  print "NO $node$delim$leaf <br/>";
                        echo '<li>';
                        printf('<input type="checkbox" name="checkfolder[]" value="%s">&nbsp;', $node.$delim.$leaf);
//            print "$node.$delim";
                        echo "$leaf</li>\n";
                    }
                    array_push($GLOBALS['nodesdone'], $node);
                }
                echo '</ul>';
            } else {
                //     print "<li>$node</li>";
                //      print $root ."===". $node . "<br/>";
            }
        } else {
            //   print "<br/>Done: $node";
        }
    }
}

function fetchEmailsFromHeader($header, $folder, $fieldlist = array())
{
    $res = array();
//  print "<br/>Processing $header";
    if (!count($fieldlist)) {
        $fieldlist = $GLOBALS['email_header_fields'];
    }
    ++$GLOBALS['messagecount'];

//  foreach (array("to","from","cc","bcc","reply_to","sender","return_path") as $item) {
    foreach ($fieldlist as $item) {
        if (is_array($header->$item)) {
            //      print "<br/><b>Values in $item<br/>";
            foreach ($header->$item as $object) {
                //        print "Personal: ".$object->personal."<br/>";
//        print "Adl: ".$object->adl."<br/>";
//        print "Mailbox: ".$object->mailbox."<br/>";
//        print "Host: ".$object->host."<br/>";
                //"$object->personal <".$object->mailbox.'@'.$object->host.">"
                if (!is_array($res[strtolower($object->mailbox.'@'.$object->host)])) {
                    $res[strtolower($object->mailbox.'@'.$object->host)] = array();
                }
                array_push($res[strtolower($object->mailbox.'@'.$object->host)],
                    array(
                        'personal' => $object->personal,
                        'email'    => $object->mailbox.'@'.$object->host,
                        'folder'   => $folder,
                        'date'     => $header->udate,
                    ));
            }
        }
    }

    return $res;
}

function processImapFolder($server, $user, $password, $folder, $fieldlist = array())
{
    $result = array();
    //$port =  "993/imap/ssl/novalidate-cert";
    $port = '143/imap/notls';
    $mbox = imap_open('{'.$server.':'.$port."}$folder", $user, $password, OP_READONLY);
    if (!$mbox) {
        Fatal_Error($GLOBALS['I18N']->get("can't connect").': '.imap_last_error());

        return 0;
    }
    echo $GLOBALS['I18N']->get('Processing').' '.$folder;
    ++$GLOBALS['foldercount'];
    $num = imap_num_msg($mbox);
    echo '('.$num.' '.$GLOBALS['I18N']->get('messages').')';
    for ($x = 1; $x <= $num; ++$x) {
        set_time_limit(60);
        $header = imap_headerinfo($mbox, $x);
        $emails = fetchEmailsFromHeader($header, $folder, $fieldlist);
//    $result = array_merge($result,$emails);
        foreach ($emails as $email => $list) {
            if (!is_array($result[$email])) {
                $result[$email] = array();
            }
            foreach ($list as $key => $rec) {
                array_push($result[$email], $rec);
            }
        }
        if ($x % 25 == 0) {
            echo $x."/$num ".$GLOBALS['I18N']->get('done').'<br/>';
        }
        echo "\n";
        flush();
    }

    return $result;
}

function getImapFolders($server, $user, $password)
{
    //$port =  "993/imap/ssl/novalidate-cert";
    $port = '143/imap/notls';
    $mbox = @imap_open('{'.$server.':'.$port.'}', $user, $password, OP_HALFOPEN);
    if (!$mbox) {
        Fatal_Error($GLOBALS['I18N']->get("can't connect").': '.imap_last_error());

        return 0;
    }

    $list = imap_getmailboxes($mbox, '{'.$server.'}', '*');
    if (is_array($list)) {
        return $list;
    } else {
        Fatal_Error($GLOBALS['I18N']->get('imap_getmailboxes failed').': '.imap_last_error()."\n");

        return 0;
    }
    imap_close($mbox);
}

function sortbydate($a, $b)
{
    return $a['date'] < $b['date'];
}

function getBestVersion($emails)
{
    // to start with order in reverse time order
    usort($emails, 'sortbydate');
    foreach ($emails as $email) {
        // now check how good the "personal" is
        // if it is only the email repeated we do not want it
        if (strpos($email['personal'], '@') === false) {
            return $email;
        }
        // we possibly want to search for better ones, but leave it here
//    print $email["date"] . '=>'.$email["email"]."<br/>";
    }
    // if we did not return anything return the latest version by date
    return $emails[0];
}

if (!$_POST['server'] || !$_POST['user'] || !$_POST['password'] || !is_array($_POST['lists'])) {
    echo '
  <p class="information">' .$GLOBALS['I18N']->get('Please enter details of the IMAP account').'</p>
  <form method="post">
  <table class="importForm">
  <tr><td>' .$GLOBALS['I18N']->get('Server').':</td><td><input type="text" name="server" value="" size="30"></td></tr>
  <tr><td>' .$GLOBALS['I18N']->get('User').':</td><td><input type="text" name="user" value="" size="30"></td></tr>
  <tr><td>' .$GLOBALS['I18N']->get('Password').':</td><td><input type="password" name="password" value="" size="30"></td></tr>
  <tr><td colspan="2">' .$GLOBALS['I18N']->get('Select the headers fields to search').':</td></tr>
  ';
    foreach ($email_header_fields as $header_field) {
        printf('
    <tr><td>%s</td><td><input type="checkbox" name="selected_header_fields[]" value="%s"', $header_field,
            $header_field);
    }
    $c = 0;
    echo '<tr><td>';
    if (count($available_lists) > 1) {
        echo $GLOBALS['I18N']->get('Select the lists to add the emails to').'<br/>';
    }
    echo '<ul>';
    foreach ($available_lists as $index => $name) {
        if (count($available_lists) == 1) {
            printf('<input type="hidden" name="lists[0]" value="%d">
        <li>' .$GLOBALS['I18N']->get('Adding users to list').'. <b>%s</b>', $index, $name);
        } else {
            printf('<li><input type="checkbox" name="lists[%d]" value="%d">%s',
                $c, $index, $name);
            ++$c;
        }
    }

    echo '
  </ul></td></tr>
<tr><td>' .$GLOBALS['I18N']->get('Mark new users as HTML').':</td><td><input type="checkbox" name="markhtml" value="yes"></td></tr>
<tr><td colspan="2">' .$GLOBALS['I18N']->get('If you check')." '".$GLOBALS['I18N']->get('Overwrite Existing')."', ".$GLOBALS['I18N']->get('information about a user in the database will be replaced by the imported information. Users are matched by email.').'</td></tr>
<tr><td>' .$GLOBALS['I18N']->get('Overwrite Existing').':</td><td><input type="checkbox" name="overwrite" value="yes"></td></tr>
<tr><td colspan="2">' .$GLOBALS['I18N']->get('If you check')." '".$GLOBALS['I18N']->get('Only use complete addresses')."' ".$GLOBALS['I18N']->get('addresses that do not have a real name will be ignored. Otherwise all emails will be imported.').'</td></tr>
<tr><td>' .$GLOBALS['I18N']->get('Only use complete addresses').':</td><td><input type="checkbox" name="onlyfull" value="yes"></td></tr>
<tr><td colspan="2">' .$GLOBALS['I18N']->get('If you choose')." '".$GLOBALS['I18N']->get('send notification email')."' ".$GLOBALS['I18N']->get('the users you are adding will be sent the request for confirmation of subscription to which they will have to reply. This is recommended, because it will identify invalid emails.').'</td></tr>
<tr><td>' .$GLOBALS['I18N']->get('Send&nbsp;Notification&nbsp;email&nbsp;').'<input type="radio" name="notify" value="yes"></td><td>'.$GLOBALS['I18N']->get('Make confirmed immediately').'&nbsp;<input type="radio" name="notify" value="no"></td></tr>
<tr><td colspan="2">' .$GLOBALS['I18N']->get('There are two ways to add the names of the users,  either one attribute for the entire name or two attributes, one for first name and one for last name. If you use &quot;two attributes&quot;, the name will be split after the first space.').'
</td></tr>
<tr><td>' .$GLOBALS['I18N']->get('Use one attribute for name').'<input type="radio" name="nameattributes" value="one"></td><td>'.$GLOBALS['I18N']->get('Use two attributes for the name').'&nbsp;<input type="radio" name="nameattributes" value="two"></td></tr>
<tr><td>' .$GLOBALS['I18N']->get('Attribute one').': </td><td><select name="attributeone">
<option value="create">' .$GLOBALS['I18N']->get('Create Attribute').'</option>
';
    $req = Sql_Query("select * from {$tables['attribute']} where type=\"textline\"");
    while ($att = Sql_Fetch_array($req)) {
        printf('<option value="%d">%s</option>', $att['id'], $att['name']);
    }
    echo '</select></td></tr>
  <tr><td>' .$GLOBALS['I18N']->get('Attribute two').': </td><td><select name="attributetwo">
  <option value="create">' .$GLOBALS['I18N']->get('Create Attribute').'</option>';
    $req = Sql_Query("select * from {$tables['attribute']} where type=\"textline\"");
    while ($att = Sql_Fetch_array($req)) {
        printf('<option value="%d">%s</option>', $att['id'], $att['name']);
    }
    echo '</select></td></tr>
  <tr><td colspan="2"><p class="submit"><input type="submit" value="' .$GLOBALS['I18N']->get('Continue').'"></p></td></tr>
  </table></form>
  ';
} elseif (!is_array($_POST['checkfolder'])) {
    $folders = getImapFolders($server, $user, $password);
    if (!$folders) {
        Error($GLOBALS['I18N']->get('Cannot continue'));

        return;
    }

    printf('
  <form method="post" name="folderlist">
  <input type="hidden" name="parsefolders" value="1">
  ', $_POST['server'], $_POST['user'], $_POST['password']);
    if (is_array($_POST['selected_header_fields'])) {
        foreach ($_POST['selected_header_fields'] as $field) {
            printf('<input type="hidden" name="selected_header_fields[]" value="%s">', $field);
        }
    }
    if (is_array($_POST['lists'])) {
        foreach ($_POST['lists'] as $key => $val) {
            printf('<input type="hidden" name="lists[%d]" value="%s">', $key, $val);
        }
    }
    foreach (array(
                 'server',
                 'user',
                 'password',
                 'markhtml',
                 'overwrite',
                 'onlyfull',
                 'notify',
                 'nameattributes',
                 'attributeone',
                 'attributetwo',
             ) as $item) {
        printf('<input type="hidden" name="%s" value="%s">', $item, $_POST[$item]);
    }

    $done = 0;
    $level = 0;
    $foldersdone = array();
    $tree = array();
    while (count($folderdone) < count($folders) && $level < 10) {
        reset($folders);
        asort($folders);
        foreach ($folders as $key => $val) {
            $delim = $val->delimiter;
            $name = str_replace('{'.$server.'}INBOX', '', imap_utf7_decode($val->name));
            $parent = mailBoxParent($name, $delim, $level);
            $folder = mailBoxName($name, $delim, $level);
            if ($folder) {
                if (!is_array($tree[$parent])) {
                    $tree[$parent] = array(
                        'node'     => $parent,
                        'children' => array(),
                    );
                }
                if (!in_array($folder, $tree[$parent]['children'])) {
                    array_push($tree[$parent]['children'], $folder);
                }
                //   print $parent . " ".$folder."<br/>";
                flush();
            } else {
                array_push($foldersdone, $name);
            }
        }
        ++$level;
    }
    ksort($tree);
    echo '<ul>'.printTree($tree, 'INBOX', '.').'</ul>';
    echo '<p class="submit"><input type="submit" value="'.$GLOBALS['I18N']->get('Process Selected Folders').'"></p></form>';
} else {
    $all_emails = array();
    foreach ($_POST['checkfolder'] as $key => $folder) {
        echo '<br/>';
        flush();

        $emails = processImapFolder($_POST['server'], $_POST['user'], $_POST['password'], $folder,
            $_POST['selected_header_fields']);
        if (is_array($emails)) {
            foreach ($emails as $email => $list) {
                if (!is_array($all_emails[$email])) {
                    $all_emails[$email] = array();
                }
                //      $emaillist = array_merge($emaillist,$emails);
                foreach ($list as $key => $rec) {
                    array_push($all_emails[$email], $rec);
                }
            }
            echo '... '.$GLOBALS['I18N']->get('ok');
        } else {
            echo '... '.$GLOBALS['I18N']->get('failed');
        }
        flush();
    }
    if (is_array($all_emails)) {
        $num = count($all_emails);
        echo '<p class="information">'.$GLOBALS['I18N']->get('Processed').':'.$GLOBALS['foldercount'].' '.$GLOBALS['I18N']->get('folders and').' '.$GLOBALS['messagecount'].' '.$GLOBALS['I18N']->get('messages').'</p>';
        echo '<h3>'.count($all_emails).' '.$GLOBALS['I18N']->get('unique emails found').'</h3>';
        flush();

        $usetwo = 0;
        // prepare the attributes
        if ($_POST['nameattributes'] == 'two') {
            $usetwo = 1;
            if ($_POST['attributeone'] == 'create') {
                $req = Sql_Query(sprintf('insert into %s (name,type)
          values("First Name","textline")', $tables['attribute']));
                $firstname_att_id = Sql_Insert_id();
            } else {
                $firstname_att_id = $_POST['attributeone'];
            }
            if ($_POST['attributetwo'] == 'create') {
                $req = Sql_Query(sprintf('insert into %s (name,type)
          values("Last Name","textline")', $tables['attribute']));
                $lastname_att_id = Sql_Insert_id();
            } else {
                $lastname_att_id = $_POST['attributetwo'];
            }
        } else {
            if ($_POST['attributeone'] == 'create') {
                $req = Sql_Query(sprintf('insert into %s (name,type)
          values("Name","textline")', $tables['attribute']));
                $name_att_id = Sql_Insert_id();
            } else {
                $name_att_id = $_POST['attributeone'];
            }
        }

        $x = 0;
        $count_email_add = 0;
        $count_exist = 0;
        $count_list_add = 0;

        foreach ($all_emails as $key => $versions) {
            set_time_limit(60);
            $importuser = getBestVersion($versions);
            //     print $importuser["personal"]." &lt;".$importuser["email"]."&gt;<br/>";
            printf('<input type="hidden" name="importemail[%s] value="%s">',
                $importuser['email'], $importuser['personal']);

            // split personal in first and last name
            list($importuser['firstname'], $importuser['lastname']) = explode(' ', $importuser['personal'], 2);

            ++$x;
            if ($x % 25 == 0) {
                echo $x."/$num ".$GLOBALS['I18N']->get('done').'<br/>';
                flush();
            }

            // check for full email
            if ($_POST['onlyfull'] != 'yes' ||
                ($_POST['onlyfull'] == 'yes' && strpos($importuser['personal'], '@') === false) &&
                strlen($importuser['email']) > 4
            ) {
                $new = 0;
                $result = Sql_query(sprintf('SELECT id,uniqid FROM %s
          WHERE email = "%s"', $tables['user'], $importuser['email']));
                if (Sql_affected_rows()) {
                    // Email exist, remember some values to add them to the lists
                    ++$count_exist;
                    $user = Sql_fetch_array($result);
                    $userid = $user['id'];
                    $uniqid = $user['uniqid'];
                    Sql_Query(sprintf('update %s set htmlemail = %d where id = %d', $tables['user'],
                        $_POST['markhtml'] ? '1' : '0', $userid));
                } else {
                    // Email does not exist
                    $new = 1;

                    $uniqid = getUniqid();
                    $query = sprintf('INSERT INTO %s (email,entered,confirmed,uniqid,htmlemail)
             values("%s",now(),%d,"%s",%d)',
                        $tables['user'], $importuser['email'], $_POST['notify'] != 'yes', $uniqid,
                        $_POST['markhtml'] ? '1' : '0');
                    $result = Sql_query($query);
                    $userid = Sql_insert_id();

                    ++$count_email_add;
                    $some = 1;
                }

                if ($_POST['overwrite'] == 'yes') {
                    if ($usetwo) {
                        Sql_query(sprintf('replace into %s (attributeid,userid,value) values(%d,%d,"%s")',
                            $tables['user_attribute'], $firstname_att_id, $userid, $importuser['firstname']));
                        Sql_query(sprintf('replace into %s (attributeid,userid,value) values(%d,%d,"%s")',
                            $tables['user_attribute'], $lastname_att_id, $userid, $importuser['lastname']));
                    } else {
                        Sql_query(sprintf('replace into %s (attributeid,userid,value) values(%d,%d,"%s")',
                            $tables['user_attribute'], $name_att_id, $userid, $importuser['personal']));
                    }
                }
                //add this user to the lists identified
                reset($lists);
                $addition = 0;
                $listoflists = '';
                foreach ($lists as $key => $listid) {
                    $query = 'replace INTO '.$tables['listuser']." (userid,listid,entered) values($userid,$listid,now())";
                    $result = Sql_query($query);
                    // if the affected rows is 2, the user was already subscribed
                    $addition = $addition || Sql_Affected_Rows() == 1;
                    $listoflists .= '  * '.$available_lists[$listid]."\n";
                }
                if ($addition) {
                    ++$additional_emails;
                }
                if (!TEST && $_POST['notify'] == 'yes' && $addition) {
                    $subscribemessage = str_replace('[LISTS]', $listoflists,
                        getUserConfig('subscribemessage', $userid));
                    sendMail($email, getConfig('subscribesubject'), $subscribemessage, system_messageheaders(),
                        $envelope);
                }
            } // end if
        } // end foreach

        $num_lists = count($lists);

        // be grammatically correct :-)
        $displists = ($num_lists == 1) ? $GLOBALS['I18N']->get('list') : $GLOBALS['I18N']->get('lists');
        $dispemail = ($count_email_add == 1) ? $GLOBALS['I18N']->get('new email was').' ' : $GLOBALS['I18N']->get('new emails were').' ';
        $dispemail2 = ($additional_emails == 1) ? $GLOBALS['I18N']->get('email was').' ' : $GLOBALS['I18N']->get('emails were').' ';

        if (!$some && !$additional_emails) {
            echo '<br/>'.$GLOBALS['I18N']->get('All the emails already exist in the database and are members of the')." $displists.";
        } else {
            echo "$count_email_add $dispemail ".$GLOBALS['I18N']->get('succesfully imported to the database and added to')." $num_lists $displists.<br/>$additional_emails $dispemail2 ".$GLOBALS['I18N']->get('subscribed to the')." $displists";
            if ($count_exist) {
                echo "<br/>$count_exist ".$GLOBALS['I18N']->get('emails already existed in the database');
            }
            if ($invalid_email_count) {
                echo "<br/>$invalid_email_count ".$GLOBALS['I18N']->get('Invalid Emails found.');
                if (!$omit_invalid) {
                    echo ' '.$GLOBALS['I18N']->get('These records were added, but the email has been made up. You can find them by doing a search on').' "Invalid Email"';
                } else {
                    echo ' '.$GLOBALS['I18N']->get('These records were deleted. Check your source and reimport the data. Duplicates will be identified.');
                }
            }
        }
    } else {
        echo $GLOBALS['I18N']->get('No emails found');
    }
    echo '<p class="button">'.PageLink2('import', $GLOBALS['I18N']->get('Import some more emails'));
}

?>

