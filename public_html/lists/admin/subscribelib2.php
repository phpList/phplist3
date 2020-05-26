<?php

require_once dirname(__FILE__).'/accesscheck.php';

if (empty($id) && isset($_GET['id'])) {
    $id = sprintf('%d', $_GET['id']);
} elseif (!isset($id)) {
    $id = 0;
}

if (!$id && $_GET['page'] != 'import1') {
    Fatal_Error('Invalid call');
    exit;
}
require_once dirname(__FILE__).'/date.php';
$date = new Date();

//# Check if input is complete
$allthere = 1;
$subscribepagedata = PageData($id);
if (isset($subscribepagedata['language_file']) && is_file(dirname(__FILE__).'/../texts/'.basename($subscribepagedata['language_file']))) {
    @include_once dirname(__FILE__).'/../texts/'.basename($subscribepagedata['language_file']);
}
// Allow customisation per installation
if (is_file($_SERVER['DOCUMENT_ROOT'].'/'.basename($GLOBALS['language_module']))) {
    include_once $_SERVER['DOCUMENT_ROOT'].'/'.basename($GLOBALS['language_module']);
}
if (!empty($data['language_file']) && is_file($_SERVER['DOCUMENT_ROOT'].'/'.basename($data['language_file']))) {
    include_once $_SERVER['DOCUMENT_ROOT'].'/'.basename($data['language_file']);
}

$required = array();   // id's of missing attribbutes '
if (count($subscribepagedata)) {
    $attributes = explode('+', $subscribepagedata['attributes']);
    foreach ($attributes as $attribute) {
        if (isset($subscribepagedata[sprintf('attribute%03d',
                    $attribute)]) && $subscribepagedata[sprintf('attribute%03d', $attribute)]
        ) {
            list($dummy, $dummy2, $dummy3, $req) = explode('###',
                $subscribepagedata[sprintf('attribute%03d', $attribute)]);
            if ($req) {
                array_push($required, $attribute);
            }
        }
    }
} else {
    $req = Sql_Query(sprintf('select * from %s', $GLOBALS['tables']['attribute']));
    while ($row = Sql_Fetch_Array($req)) {
        if ($row['required']) {
            array_push($required, $row['id']);
        }
    }
}

if (count($required)) {
    $required_ids = implode(',', $required);
    // check if all required attributes have been entered;
    if ($required_ids) {
        $res = Sql_Query("select * from {$GLOBALS['tables']['attribute']} where id in ($required_ids)");
        $allthere = 1;
        $missing = '';
        while ($row = Sql_Fetch_Array($res)) {
            $fieldname = 'attribute'.$row['id'];
            $thisonemissing = 0;
            if ($row['type'] != 'hidden') {
                $thisonemissing = empty($_POST[$fieldname]);
                if ($thisonemissing) {
                    $missing .= $row['name'].', ';
                }
                $allthere = $allthere && !$thisonemissing;
            }
        }
        $missing = substr($missing, 0, -2);
        if ($allthere) {
            $missing = '';
        }
    }
} else {
    $missing = '';
}

// If need to check for double entry of email address

if (isset($subscribepagedata['emaildoubleentry']) && $subscribepagedata['emaildoubleentry'] == 'yes') {
    if (!(isset($_POST['email']) && isset($_POST['emailconfirm']) && $_POST['email'] == $_POST['emailconfirm'])) {
        $allthere = 0;
        $missing = $GLOBALS['strEmailsNoMatch'];
    }
}

// check if the lists should be displayed by category
if (isset($subscribepagedata['showcategories']) && $subscribepagedata['showcategories'] == 'yes') {
    $GLOBALS['showCat'] = true;
}

// anti spambot check
if (!empty($_POST['VerificationCodeX'])) {
    if (NOTIFY_SPAM) {
        $msg = $GLOBALS['I18N']->get('
--------------------------------------------------------------------------------
    This is a notification of a possible spam attack to your phplist subscribe page.
    The data submitted has been copied below, so you can check whether this was actually the case.
    The submitted data has been converted into non-html characters, for security reasons.
    If you want to stop receiving this message, set

     define("NOTIFY_SPAM",0);

     in your phplist config file.

     This subscriber has NOT been added to the database.
     If there is an error, you will need to  add them manually.
--------------------------------------------------------------------------------  ');
        foreach ($_REQUEST as $key => $val) {
            $msg .= "\n".'Form field: '.htmlentities($key)."\n".'================='."\nSubmitted value: ".htmlentities($val)."\n".'=============='."\n\n";
        }
        foreach ($_SERVER as $key => $val) {
            $msg .= "\n".'HTTP Server Data: '.htmlentities($key)."\n".'================='."\nValue: ".htmlentities($val)."\n".'=============='."\n\n";
        }
        sendAdminCopy(s('phplist Spam blocked'), "\n".$msg);
    }
    unset($msg);

    return;
}
$pluginErrors = array();

foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
    $pluginResult = $plugin->validateSubscriptionPage($subscribepagedata);
    if (!empty($pluginResult)) {
        $pluginErrors[] = $pluginResult;
        $allthere = 0;
    }
}

if (!isset($_POST['passwordreq'])) {
    $_POST['passwordreq'] = '';
}
if (!isset($_POST['password'])) {
    $_POST['password'] = '';
}

if ($allthere && ASKFORPASSWORD && ($_POST['passwordreq'] || $_POST['password'])) {
    if (empty($_POST['password']) || $_POST['password'] !== $_POST['password_check']) {
        $allthere = 0;
        $missing = $GLOBALS['strPasswordsNoMatch'];
    }
    if ($_POST['email']) {
        $curpwd = Sql_Fetch_Row_Query(sprintf('select password from %s where email = "%s"',
            $GLOBALS['tables']['user'], sql_escape($_POST['email'])));

        if ($curpwd[0] && $_POST['password'] !== $curpwd[0]) {
            $missing = $GLOBALS['strInvalidPassword'];
        }
    }
}

if (isset($_POST['email']) && !empty($GLOBALS['check_for_host'])) {
    list($username, $domaincheck) = explode('@', $_POST['email']);
//  $mxhosts = array();
//  $validhost = getmxrr ($domaincheck,$mxhosts);
    $validhost = checkdnsrr($domaincheck, 'MX') || checkdnsrr($domaincheck, 'A');
} else {
    $validhost = 1;
}

$listsok = ((!ALLOW_NON_LIST_SUBSCRIBE && isset($_POST['list']) && is_array($_POST['list'])) || ALLOW_NON_LIST_SUBSCRIBE);

if (isset($_POST['subscribe']) && is_email($_POST['email']) && $listsok && $allthere && $validhost) {
    $history_entry = '';
    // make sure to save the correct data
    if ($subscribepagedata['htmlchoice'] == 'checkfortext' && empty($_POST['textemail'])) {
        $htmlemail = 1;
    } else {
        $htmlemail = !empty($_POST['htmlemail']);
    }

    // now check whether this user already exists.
    $email = $_POST['email'];
    if (preg_match("/(.*)\n/U", $email, $regs)) {
        $email = $regs[1];
    }
    $result = Sql_query(sprintf('select * from %s where email = "%s"', $GLOBALS['tables']['user'], sql_escape($email)));
    if (!Sql_affected_rows()) {
        // they do not exist, so add them
        $query = sprintf('insert into %s (email,entered,uniqid,confirmed,htmlemail,subscribepage,uuid) values("%s",now(),"%s",0,%d,%d,"%s")',
            $GLOBALS['tables']['user'], sql_escape($email), getUniqid(), $htmlemail, $id, (string) uuid::generate(4));
        $result = Sql_query($query);
        $userid = Sql_Insert_Id();
        addSubscriberStatistics('total users', 1);
    } else {
        // they do exist, so update the existing record
        // read the current values to compare changes
        $old_data = Sql_fetch_array($result);
        if (ASKFORPASSWORD && $old_data['password']) {
            $encP = encryptPass($_POST['password']);
            $canlogin = !empty($encP) && !empty($_POST['password']) && $encP == $old_data['password'];
            //     print $canlogin.' '.$_POST['password'].' '.$encP.' '. $old_data["password"];
            if (!$canlogin) {
                $msg = '<p class="error">'.$GLOBALS['strUserExists'].'</p>';
                $msg .= '<p class="information">'.$GLOBALS['strUserExistsExplanationStart'].
                    sprintf('<a href="%s&amp;email=%s">%s</a>', getConfig('preferencesurl'), $email,
                        $GLOBALS['strUserExistsExplanationLink']).
                    $GLOBALS['strUserExistsExplanationEnd'].'</p>';

                return;
            }
        }

        // https://mantis.phplist.com/view.php?id=15557, disallow re-subscribing existing subscribers
        if (!SILENT_RESUBSCRIBE) {
            $msg = '<div class="error missing"><h4>'.$GLOBALS['strUserExistsResubscribe'].'</h4>';
            $msg .= '<p>';
            $msg .= sprintf($GLOBALS['strUserExistsResubscribeExplanation'], getConfig('preferencesurl'));
            $msg .= '</p></div>';

            return;
        }

        $userid = $old_data['id'];
        $old_data = array_merge($old_data, getUserAttributeValues('', $userid));
        $history_entry = ''; //http://'.getConfig("website").$GLOBALS["adminpages"].'/?page=user&amp;id='.$userid."\n\n";

        $query = sprintf('update %s set email = "%s",htmlemail = %d,subscribepage = %d where id = %d',
            $GLOBALS['tables']['user'], addslashes($email), $htmlemail, $id, $userid);
        $result = Sql_query($query);
    }

    if (ASKFORPASSWORD && $_POST['password']) {
        $newpassword = encryptPass($_POST['password']);
        // see whether is has changed
        $curpwd = Sql_Fetch_Row_Query("select password from {$GLOBALS['tables']['user']} where id = $userid");
        if ($newpassword != $curpwd[0]) {
            $storepassword = 'password = "'.$newpassword.'"';
            Sql_query("update {$GLOBALS['tables']['user']} set passwordchanged = now(),$storepassword where id = $userid");
        } else {
            $storepassword = '';
        }
    } else {
        $storepassword = '';
    }

    // subscribe to the lists
    $lists = '';
    $subscriptions = array(); //# used to keep track of which admins to alert

    if (isset($_POST['list']) && is_array($_POST['list'])) {
        foreach ($_POST['list'] as $key => $val) {
            if ($val == 'signup' && !isPrivateList($key)) { // make sure that the list is not private
                $key = sprintf('%d', $key);
                if (!empty($key)) {
                    $result = Sql_query(sprintf('replace into %s (userid,listid,entered) values(%d,%d,now())',
                        $GLOBALS['tables']['listuser'], $userid, $key));
                    $lists .= "\n  * ".listname($key);
                    $subscriptions[] = $key;

                    addSubscriberStatistics('subscribe', 1, $key);
                } else {
                    //# hack attempt...
                    exit;
                }
            }
        }
    }

    // remember the users attributes
    // make sure to only remember the ones from this subscribe page
    $history_entry .= 'Subscribe page: '.$id;
    array_push($attributes, 0);
    $attids = join_clean(',', $attributes);
    if ($attids && $attids != '') {
        $res = Sql_Query('select * from '.$GLOBALS['tables']['attribute']." where id in ($attids)");
        while ($row = Sql_Fetch_Array($res)) {
            $fieldname = 'attribute'.$row['id'];
            if (!array_key_exists($fieldname, $_POST)) {
                continue;
            }
            $value = $_POST[$fieldname];
            //    if ($value != "") {
            if ($row['type'] == 'date') {
                $value = $date->getDate($fieldname);
            } elseif (is_array($value)) {
                $newval = array();
                foreach ($value as $val) {
                    array_push($newval, sprintf('%0'.$checkboxgroup_storesize.'d', $val));
                }
                $value = implode(',', $newval);
            } elseif ($row['type'] != 'textarea') {
                if (preg_match("/(.*)\n/U", $value, $regs)) {
                    $value = $regs[1];
                }
            }
            Sql_Query(sprintf('replace into %s (attributeid,userid,value) values("%s","%s","%s")',
                $GLOBALS['tables']['user_attribute'], $row['id'], $userid, $value));
            $history_entry .= "\n".$row['name'].' = '.UserAttributeValue($userid, $row['id']);
            //    }
        }
    }
    $information_changed = 0;
    if (isset($old_data) && is_array($old_data)) {
        $history_subject = 'Re-Subscription';
        // when they submit a new subscribe
        $current_data = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d', $GLOBALS['tables']['user'],
            $userid));
        $current_data = array_merge($current_data, getUserAttributeValues('', $userid));
        foreach ($current_data as $key => $val) {
            if (!is_numeric($key)) {
                if ($old_data[$key] != $val && $key != 'password' && $key != 'modified') {
                    $information_changed = 1;
                    $history_entry .= "\n$key = $val\n*changed* from $old_data[$key]";
                }
            }
        }
        if (!$information_changed) {
            $history_entry .= "\nNo user details changed";
        }
    } else {
        $history_subject = 'Subscription';
    }

    $history_entry .= "\n\nList Membership: \n$lists\n";

    $subscribemessage = str_ireplace('[LISTS]', $lists, getUserConfig("subscribemessage:$id", $userid));

    $blacklisted = isBlackListed($email);

    echo '<title>'.$GLOBALS['strSubscribeTitle'].'</title>';
    echo $subscribepagedata['header'];

    if (isset($_SESSION['adminloggedin']) && $_SESSION['adminloggedin'] && !(isset($_GET['p']) && $_GET['p'] == 'asubscribe')) {
        echo '<p class="information"><b>You are logged in as '.$_SESSION['logindetails']['adminname'].'</b></p>';
        echo '<p><a href="'.$adminpages.'" class="button">Back to the main admin page</a></p>';

        if ($_POST['makeconfirmed'] && !$blacklisted) {
            $sendrequest = 0;
            Sql_Query(sprintf('update %s set confirmed = 1 where email = "%s"', $GLOBALS['tables']['user'], $email));
            addUserHistory($email, $history_subject.' by '.$_SESSION['logindetails']['adminname'], $history_entry);
        } elseif ($_POST['makeconfirmed']) {
            echo '<p class="information">'.$GLOBALS['I18N']->get('Email is blacklisted, so request for confirmation has been sent.').'<br/>';
            echo $GLOBALS['I18N']->get('If user confirms subscription, they will be removed from the blacklist.').'</p>';

            $sendrequest = 1;
        } else {
            $sendrequest = 1;
        }
    } else {
        $sendrequest = 1;
    }

    // personalise the thank you page
    if ($subscribepagedata['thankyoupage']) {
        $thankyoupage = $subscribepagedata['thankyoupage'];
    } else {
        $thankyoupage = '<h3>'.$strThanks.'</h3>'.'<p class="information">'.$strEmailConfirmation.'</p>';
    }

    $thankyoupage = str_ireplace('[email]', $email, $thankyoupage);

    $user_att = getUserAttributeValues($email);

    if (count($user_att)) {
        foreach ($user_att as $att_name => $att_value) {
            $thankyoupage = str_ireplace('['.$att_name.']', $att_value, $thankyoupage);
        }
    }

    if (is_array($GLOBALS['plugins'])) {
        reset($GLOBALS['plugins']);
        foreach ($GLOBALS['plugins'] as $name => $plugin) {
            $thankyoupage = $plugin->parseThankyou($id, $userid, $thankyoupage);
        }
    }

    if ($sendrequest && $listsok) { //is_array($_POST["list"])) {
        if (RFC_DIRECT_DELIVERY) {
            $ok = sendMailDirect($email, getConfig("subscribesubject:$id"), $subscribemessage,
                system_messageheaders($email), $envelope, 1);
            if (!$ok) {
                echo '<h3>'.$strEmailFailed.'</h3>';
                echo '<p>'.$GLOBALS['smtpError'].'</p>';
            } else {
                sendAdminCopy('Lists subscription', "\n".$email." has subscribed\n\n$history_entry",
                    $subscriptions);
                addUserHistory($email, $history_subject, $history_entry);
                echo $thankyoupage;
            }
        } elseif (sendMail($email, getConfig("subscribesubject:$id"), $subscribemessage, system_messageheaders($email),
            $envelope, 1)) {
            sendAdminCopy('Lists subscription', "\n".$email." has subscribed\n\n$history_entry", $subscriptions);
            addUserHistory($email, $history_subject, $history_entry);
            echo $thankyoupage;
        } else {
            echo '<h3>'.$strEmailFailed.'</h3>';
            if ($blacklisted) {
                echo '<p class="information">'.$GLOBALS['strYouAreBlacklisted'].'</p>';
            }
        }
    } else {
        echo $thankyoupage;
        if ($_SESSION['adminloggedin']) {
            echo '<p class="information">User has been added and confirmed</p>';
        }
    }

    echo '<p class="information">'.$PoweredBy.'</p>';
    echo $subscribepagedata['footer'];
    //  exit;
    // Instead of exiting here, we return 2. So in lists/index.php
    // We can decide, whether to show subscribe page or not.
    //# issue 6508
    return 2;
} elseif (isset($_POST['update']) && $_POST['update'] && is_email($_POST['email']) && $allthere) {
    $email = trim($_POST['email']);
    if (preg_match("/(.*)\n/U", $email, $regs)) {
        $email = $regs[1];
    }
    if ($_GET['uid']) {
        $req = Sql_Fetch_Row_Query(sprintf('select id from %s where uniqid = "%s"',
            $GLOBALS['tables']['user'], $_GET['uid']));
        $userid = $req[0];
    } else {
        // This could be abused and is not required
        // $req = Sql_Fetch_Row_query("select id from {$GLOBALS['tables']['user']} where email = \"".sql_escape($_GET['email']).'"');
        // $userid = $req[0];
        $userid = false;
    }
    if (!$userid) {
        Fatal_Error('Error, no such user');
    }
    // update the existing record, check whether the email has changed
    $req = Sql_Query("select * from {$GLOBALS['tables']['user']} where id = $userid");
    $data = Sql_fetch_array($req);

    // check that the password was provided if required
    // we only require a password if there is one, otherwise people are blocked out
    // when switching to requiring passwords
    if (ASKFORPASSWORD && $data['password']) {
        // they need to be "logged in" for this
        if (empty($_SESSION['userloggedin'])) {
            Fatal_Error('Access Denied');
            exit;
        }
        $checkpassword = '';
        $allow = 0;
        // either they have to give the current password, or given two new ones
        if (ENCRYPTPASSWORD) {
            $checkpassword = encryptPass($_POST['password']);
        } else {
            $checkpassword = sprintf('%s', $_POST['password']);
        }
        if (!empty($_POST['password_check'])) {
            $allow = $_POST['password_check'] == $_POST['password'] && !empty($_POST['password']);
        } else {
            $allow = (!empty($_POST['password']) && $data['password'] == $checkpassword) || empty($_POST['password']);
        }

        if (!$allow) {
            // @@@ this check should be done above, so the error can be embedded in the template
            echo $GLOBALS['strPasswordsNoMatch'];
            exit;
        }
    }

    // check whether they are changing to an email that already exists, should not be possible
    $req = Sql_Query("select uniqid from {$GLOBALS['tables']['user']} where email = \"$email\"");
    if (Sql_Affected_Rows()) {
        $row = Sql_Fetch_Row($req);
        if ($row[0] != $_GET['uid']) {
            Fatal_Error('Cannot change to that email address.
      <br/>This email already exists.
      <br/>Please use the preferences URL for this email to make updates.
      <br/>Click <a href="' .getConfig('preferencesurl')."&amp;email=$email\">here</a> to request your personal location");
            exit;
        }
    }
    // read the current values to compare changes
    $old_data = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d', $GLOBALS['tables']['user'], $userid));
    $old_data = array_merge($old_data, getUserAttributeValues('', $userid));
    $history_entry = ''; //'http://'.getConfig("website").$GLOBALS["adminpages"].'/?page=user&amp;id='.$userid."\n\n";

    if (ASKFORPASSWORD && $_POST['password']) {
        if (ENCRYPTPASSWORD) {
            $newpassword = encryptPass($_POST['password']);
        } else {
            $newpassword = sprintf('%s', $_POST['password']);
        }
        // see whether is has changed
        $curpwd = Sql_Fetch_Row_Query("select password from {$GLOBALS['tables']['user']} where id = $userid");
        if ($_POST['password'] != $curpwd[0]) {
            $storepassword = 'password = "'.$newpassword.'",';
            Sql_query("update {$GLOBALS['tables']['user']} set passwordchanged = now() where id = $userid");
            $history_entry .= "\nUser has changed their password\n";
            addSubscriberStatistics('password change', 1);
        } else {
            $storepassword = '';
        }
    } else {
        $storepassword = '';
    }

    // We want all these to be set, albeit to empty string
    foreach (array('htmlemail') as $sUnsubscribeFormVar) {
        if (!isset($_POST[$sUnsubscribeFormVar])) {
            $_POST[$sUnsubscribeFormVar] = '';
        }
    }

    $query = sprintf('update %s set email = "%s", %s htmlemail = %d where id = %d',
        $GLOBALS['tables']['user'], sql_escape($_POST['email']), $storepassword, $_POST['htmlemail'], $userid);
    //print $query;
    $result = Sql_query($query);
    if (strtolower($data['email']) != strtolower($email)) {
        $emailchanged = 1;
        Sql_Query(sprintf('update %s set confirmed = 0 where id = %d', $GLOBALS['tables']['user'], $userid));
    }

    // subscribe to the lists
    // first take them off the ones, then re-subscribe
    if ($subscribepagedata['lists']) {
        $subscribepagedata['lists'] = preg_replace("/^\,/", '', $subscribepagedata['lists']);
        Sql_query(sprintf('delete from %s where userid = %d and listid in (%s)', $GLOBALS['tables']['listuser'],
            $userid, $subscribepagedata['lists']));
        $liststat = explode(',', $subscribepagedata['lists']);
    } else {
        Sql_query(sprintf('delete from %s where userid = %d', $GLOBALS['tables']['listuser'], $userid));
    }

    $lists = '';
    if (is_array($_POST['list'])) {
        foreach ($_POST['list'] as $key => $val) {
            if ($val == 'signup' && !isPrivateList($key)) {
                $result = Sql_query(sprintf('replace into %s (userid,listid,entered) values(%d,%d,now())',$GLOBALS['tables']['listuser'],$userid,$key));
//        $lists .= "  * ".$_POST["listname"][$key]."\n";
            }
        }
    }
    // check list membership
    $subscriptions = array();
    $req = Sql_Query(sprintf('select * from %s listuser,%s list where listuser.userid = %d and listuser.listid = list.id and list.active',
        $GLOBALS['tables']['listuser'], $GLOBALS['tables']['list'], $userid));
    while ($row = Sql_Fetch_Array($req)) {
        $lists .= '  * '.listName($row['listid'])."\n";
        array_push($subscriptions, $row['listid']);
    }

    if ($lists == '') {
        $lists = 'No Lists';
    }
    if ($lists == '') {
        $lists = 'No Lists';
    }

    // We want all these to be set, albeit to empty string
    foreach (array('datachange', 'htmlemail', 'information_changed', 'emailchanged') as $sUnsubscribeVar) {
        if (!isset(${$sUnsubscribeVar})) {
            ${$sUnsubscribeVar} = '';
        }
    }

    $datachange .= "$strEmail : ".$email."\n";
    if ($subscribepagedata['htmlchoice'] != 'textonly'
        && $subscribepagedata['htmlchoice'] != 'htmlonly'
    ) {
        $datachange .= "$strSendHTML : ";
        $datachange .= $_POST['htmlemail'] ? "$strYes\n" : "$strNo\n";
    }

    // remember the users attributes
    $attids = join_clean(',', $attributes);
    if ($attids && $attids != '') {
        $res = Sql_Query('select * from '.$GLOBALS['tables']['attribute']." where id in ($attids)");
        while ($attribute = Sql_Fetch_Array($res)) {
            $fieldname = 'attribute'.$attribute['id'];
            if (isset($_POST[$fieldname])) {
                $value = $_POST[$fieldname]; //# is being sanitised below, depending on attribute type
            } else {
                $value = '';
            }
            $replace = 1; //isset($_POST[$fieldname]);
            if ($attribute['type'] == 'date') {
                $value = $date->getDate($fieldname);
            } elseif (is_array($value)) {
                $values = array();
                foreach ($value as $val) {
                    array_push($values, sprintf('%0'.$checkboxgroup_storesize.'d', $val));
                }
                $value = implode(',', $values);
            } elseif ($attribute['type'] != 'textarea') {
                if (preg_match("/(.*)\n/U", $value, $regs)) {
                    $value = $regs[1];
                }
            }
            if ($replace) {
                Sql_query(sprintf('replace into %s (attributeid,userid,value) values("%s","%s","%s")',
                    $GLOBALS['tables']['user_attribute'], $attribute['id'], $userid, $value));
                if ($attribute['type'] != 'hidden') {
                    $datachange .= strip_tags($attribute['name']).' : ';
                    if ($attribute['type'] == 'checkbox') {
                        $datachange .= $value ? $strYes : $strNo;
                    } elseif ($attribute['type'] != 'date' && $attribute['type'] != 'textline' && $attribute['type'] != 'textarea') {
                        $datachange .= AttributeValue($attribute['tablename'], $value);
                    } else {
                        $datachange .= stripslashes($value);
                    }
                    $datachange .= "\n";
                }
            }
        }
    }
    $current_data = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d', $GLOBALS['tables']['user'],
        $userid));
    $current_data = array_merge($current_data, getUserAttributeValues('', $userid));
    foreach ($current_data as $key => $val) {
        if (!is_numeric($key)) {
            if ($old_data[$key] != $val && $key != 'password' && $key != 'modified') {
                $information_changed = 1;
                $history_entry .= "$key = $val\n*changed* from $old_data[$key]\n";
            }
        }
    }
    if (!$information_changed) {
        $history_entry .= "\nNo user system details changed";
    }
    $history_entry .= "\n\nList Membership: \n$lists\n";

    $message = str_replace('[LISTS]', $lists, getUserConfig('updatemessage', $userid));
    $message = str_replace('[USERDATA]', $datachange, $message);
    if ($emailchanged) {
        $newaddressmessage = str_replace('[CONFIRMATIONINFO]', getUserConfig('emailchanged_text', $userid), $message);
        $oldaddressmessage = str_replace('[CONFIRMATIONINFO]', getUserConfig('emailchanged_text_oldaddress', $userid),
            $message);
    } else {
        $message = str_replace('[CONFIRMATIONINFO]', '', $message);
    }

    echo '<title>'.$GLOBALS['strPreferencesTitle'].'</title>';
    echo $subscribepagedata['header'];
    if (!TEST) {
        if ($emailchanged) {
            if (sendMail($data['email'], getConfig('updatesubject'), $oldaddressmessage, system_messageheaders($email),
                    $envelope) &&
                sendMail($email, getConfig('updatesubject'), $newaddressmessage, system_messageheaders($email),
                    $envelope)
            ) {
                $ok = 1;
                sendAdminCopy('Lists information changed',
                    "\n".$data['email']." has changed their information.\n\nThe email has changed to $email.\n\n$history_entry",
                    $subscriptions);
                addUserHistory($email, 'Change', $history_entry);
            } else {
                $ok = 0;
            }
        } else {
            if (sendMail($email, getConfig('updatesubject'), $message, system_messageheaders($email), $envelope)) {
                $ok = 1;
                sendAdminCopy('Lists information changed',
                    "\n".$data['email']." has changed their information\n\n$history_entry", $subscriptions);
                addUserHistory($email, 'Change', $history_entry);
            } else {
                $ok = 0;
            }
        }
    } else {
        $ok = 1;
    }
    if ($ok) {
        echo '<h3>'.$GLOBALS['strPreferencesUpdated'].'</h3>';
        if ($emailchanged) {
            echo $strPreferencesEmailChanged;
        }
        echo '<br/>';
        if ($_GET['p'] == 'preferences') {
            //0013134: turn off the confirmation email when an existing subscriber changes preference.
            $ok = 1;
        } else {
            echo $strPreferencesNotificationSent;
        }
    } else {
        $isThisBlacklisted = isBlackListed($email);
        if ($isThisBlacklisted) {
            echo '<p class="information">'.$GLOBALS['strYouAreBlacklisted'].'</p>';
        } else {
            echo '<h3>'.$strEmailFailed.'</h3>';
        }

    }
    echo '<p class="information">'.$PoweredBy.'</p>';
    echo $subscribepagedata['footer'];
    // exit;
    // Instead of exiting here, we return 3. So in lists/index.php
    // We can decide, whether to show preferences page or not.
    //# mantis issue 6508
    return 3;
}

if (isset($_POST['subscribe']) || isset($_POST['update'])) {
    $format = '<div class="error missing">%s</div>'."\n";
    $msg = '';

    if (!is_email($_POST['email'])) {
        $msg .= sprintf($format, $strEnterEmail);
    }
    if (!$validhost) {
        $msg .= sprintf($format, $strInvalidHostInEmail);
    }
    if ($missing) {
        $msg .= sprintf($format, "$strValuesMissing: $missing");
    }
    if (!isset($_POST['list']) && !ALLOW_NON_LIST_SUBSCRIBE) {
        $msg .= sprintf($format, $strEnterList);
    }
    foreach ($pluginErrors as $pluginError) {
        $msg .= sprintf($format, $pluginError);
    }
}

/**
 * @param int $userid
 * @param string $lists_to_show
 * @return string
 */
function ListAvailableLists($userid = 0, $lists_to_show = '')
{
    global $tables;
    if (isset($_POST['list'])) {
        $list = $_POST['list'];
    } elseif (!isset($_POST["subscribe"]) && isset($_GET['list']) && preg_match("/^(\d+,)*\d+$/", $_GET['list'])) {
        $list_value = "signup";
        $list_values = explode(",", $_GET["list"]);
        $list = array_fill_keys($list_values, $list_value);
    } else {
        $list = '';
    }
    $subselect = '';
    $listset = array();
    $subscribed = array();

    $showlists = explode(',', $lists_to_show);
    if (PREFERENCEPAGE_SHOW_PRIVATE_LISTS && !empty($userid)) {
        //# merge with the subscribed lists, regardless of public state
        $req = Sql_Query(sprintf('select listid from %s where userid = %d', $tables['listuser'], $userid));
        while ($row = Sql_Fetch_Row($req)) {
            $subscribed[] = $row[0];
        }
        $showlists = array_unique(array_merge($showlists, $subscribed));
    }


    foreach ($showlists as $listid) {
        if (preg_match("/^\d+$/", $listid) && !isPrivateList($listid)) {
            array_push($listset, $listid);
        }
    }
    if (count($listset) >= 1) {
        $subselect = 'where id in ('.implode(',', $listset).') ';
    }

    $some = 0;


    if (isset($GLOBALS['showCat'])&& $GLOBALS['showCat']===true){
        $listspercategory = array();
        $categories = array();
        $catresult = Sql_query(sprintf('select * from %s %s order by category, listorder, name',
            $GLOBALS['tables']['list'], $subselect));


        while ($row = Sql_fetch_array($catresult)) {

            $listspercategory[] = array('id' => $row ['id'], 'name' => $row ['name'], 'description' => $row ['description'], 'active' => $row ['active'], 'category' => $row ['category']);

        }

        foreach ($listspercategory as $key => $value) {

            if($value['active']) {
                $categories[] = $value['category'];
            }
        }
        $uniqueCat = array_unique($categories);

        $html = '<div class="accordion allexpanded" >';
        foreach ($uniqueCat as $key) {

            if ($key !== '') {
                $displayedCat = $key;
            } else  $displayedCat = s('General');

            $html .= '<h3 ><a name="general" >' . $displayedCat . '</a></h3>';
            $html .= '<div>';
            $html .= '<ul class="list" id="listcategory">';
            $count = 0;
            foreach ($listspercategory as $listelement)
                if ($listelement['category'] === $key) {
                    if ($listelement['active'] || in_array($listelement['id'], $subscribed) ) {

                        $html .= '<li ><input type="checkbox" name="list[' . $listelement['id'] . ']" value="signup" ';
                        if (isset($list[$listelement['id']]) && $list[$listelement['id']] === 'signup') {
                            $html .= 'checked="checked"';
                        }
                        if ($userid) {
                            $req = Sql_Fetch_Row_Query(sprintf('select userid from %s where userid = %d and listid = %d',
                                $GLOBALS['tables']['listuser'], $userid, $listelement['id']));
                            if (Sql_Affected_Rows()) {
                                $html .= 'checked="checked"';
                            }
                        }


                        $html .= ' /><b>' . stripslashes($listelement['name']) . '</b><div class="listdescription">';
                        $desc = nl2br(disableJavascript(stripslashes($listelement['description'])));
                        //     $html .= '<input type="hidden" name="listname['.$row["id"] . ']" value="'.htmlspecialchars(stripslashes($row["name"])).'"/>';
                        $html .= $desc . '</div></li>';
                        ++$some;
                        if ($some == 1) {
                            $singlelisthtml = sprintf('<input type="hidden" name="list[%d]" value="signup" />', $listelement['id']);
                            $singlelisthtml .= '<input type="hidden" name="listname[' . $listelement['id'] . ']" value="' . htmlspecialchars(stripslashes($listelement['name'])) . '"/>';
                        }

                    }

                } $html .= '</ul>';

            $html .= '</div>';
        }

        // end of row active

        $html .= '</div>';

    } else {

        $html = '<ul class="list">';
        $result = Sql_query("SELECT * FROM {$GLOBALS['tables']['list']} $subselect order by listorder, name");
        while ($row = Sql_fetch_array($result)) {
            if ($row['active'] || in_array($row['id'], $subscribed)) {
                //  id required for label
                $html .= '<li class="list"><input type="checkbox" name="list[' . $row['id'] . ']" id="list'.$row['id'].'" value="signup" ';
                if (isset($list[$row['id']]) && $list[$row['id']] == 'signup') {
                    $html .= 'checked="checked"';
                }
                if ($userid) {
                    $req = Sql_Fetch_Row_Query(sprintf('select userid from %s where userid = %d and listid = %d',
                        $GLOBALS['tables']['listuser'], $userid, $row['id']));
                    if (Sql_Affected_Rows()) {
                        $html .= 'checked="checked"';
                    }
                }
                $html .= " /> <label for=\"list$row[id]\"><b>".stripslashes($row['name']).'</b></label><div class="listdescription">';
                $desc = nl2br(disableJavascript(stripslashes($row['description'])));
                //     $html .= '<input type="hidden" name="listname['.$row["id"] . ']" value="'.htmlspecialchars(stripslashes($row["name"])).'"/>';
                $html .= $desc.'</div></li>';
                ++$some;
                if ($some == 1) {
                    $singlelisthtml = sprintf('<input type="hidden" name="list[%d]" value="signup" />', $row['id']);
                    $singlelisthtml .= '<input type="hidden" name="listname['.$row['id'].']" value="'.htmlspecialchars(stripslashes($row['name'])).'"/>';
                }

            }
        }
        $html .= '</ul>';

    }

    $hidesinglelist = getConfig('hide_single_list');
    if (!$some) {
        global $strNotAvailable;

        return '<p class="information">'.$strNotAvailable.'</p>';
    } elseif ($some == 1 && ($hidesinglelist == 'true' || $hidesinglelist === true || $hidesinglelist === '1')) {
        return $singlelisthtml;
    } else {
        global $strPleaseSelect;

        return '<p class="information">'.$strPleaseSelect.':</p>'.$html;
    }
}

function ListAttributes($attributes, $attributedata, $htmlchoice = 0, $userid = 0, $emaildoubleentry = 'no')
{
    global $strPreferHTMLEmail, $strPreferTextEmail,
           $strEmail, $tables, $table_prefix, $strPreferredFormat, $strText, $strHTML;
    /*  if (!sizeof($attributes)) {
        return "No attributes have been defined for this page";
       }
    */
    if ($userid) {
        $data = array();
        $current = Sql_Fetch_array_Query("select * from {$GLOBALS['tables']['user']} where id = $userid");
        $datareq = Sql_Query("select * from {$GLOBALS['tables']['user_attribute']} where userid = $userid");
        while ($row = Sql_Fetch_Array($datareq)) {
            $data[$row['attributeid']] = $row['value'];
        }

        $email = obfuscateEmailAddress($current['email']);
        $htmlemail = $current['htmlemail'];
        // override with posted info
        foreach ($current as $key => $val) {
            if (isset($_POST[$key]) && $key != 'password') {
                $current[$key] = $val;
            }
        }
    } else {
        if (isset($_REQUEST['email'])) {
            $email = stripslashes($_REQUEST['email']);
        } else {
            $email = '';
        }
        if (isset($_POST['htmlemail'])) {
            $htmlemail = $_POST['htmlemail'];
    	} elseif (!isset($_POST["subscribe"]) && isset($_GET['htmlemail']) && in_array($_GET['htmlemail'], [1,0])) {
      		$htmlemail = $_GET["htmlemail"];
        }
        $data = array();
        $current = array();
    }

    $textlinewidth = sprintf('%d', getConfig('textline_width'));
    if (!$textlinewidth) {
        $textlinewidth = 40;
    }
    list($textarearows, $textareacols) = explode(',', getConfig('textarea_dimensions'));
    if (!$textarearows) {
        $textarearows = 10;
    }
    if (!$textareacols) {
        $textareacols = 40;
    }

    $html = '';
    if (!isset($_GET['page']) || (isset($_GET['page']) && $_GET['page'] != 'import1')) {
        $html = sprintf('
  <tr><td><div class="required"><label for="email">%s *</label></div></td>
  <td class="attributeinput"><input type=text name=email required="required" placeholder="%s" size="%d" id="email" />
  <script language="Javascript" type="text/javascript">addFieldToCheck("email","%s");</script></td></tr>',
            $GLOBALS['strEmail'], htmlspecialchars($email), $textlinewidth, $GLOBALS['strEmail']);
    }

// BPM 12 May 2004 - Begin
    if ($emaildoubleentry == 'yes') {
        if (!isset($_REQUEST['emailconfirm'])) {
            $_REQUEST['emailconfirm'] = '';
        }
        $html .= sprintf('
  <tr><td><div class="required"><label for="confirm">%s *</label></div></td>
  <td class="attributeinput"><input type=text name=emailconfirm required="required" value="%s" size="%d" id="confirm" />
  <script language="Javascript" type="text/javascript">addFieldToCheck("emailconfirm","%s");</script></td></tr>',
            $GLOBALS['strConfirmEmail'], htmlspecialchars(stripslashes($_REQUEST['emailconfirm'])), $textlinewidth,
            $GLOBALS['strConfirmEmail']);
    }
// BPM 12 May 2004 - Finish

    if ((isset($_GET['page']) && $_GET['page'] != 'import1') || !isset($_GET['page'])) {
        if (ASKFORPASSWORD) {
            // we only require a password if there isnt one, so they can set it
            // otherwise they can keep the existing, if they do not enter anything
            if (!isset($current['password']) || !$current['password']) {
                $pwdclass = 'required';
                $js = sprintf('<script language="Javascript" type="text/javascript">addFieldToCheck("password","%s");</script>',
                    $GLOBALS['strPassword']);
                $js2 = sprintf('<script language="Javascript" type="text/javascript">addFieldToCheck("password_check","%s");</script>',
                    $GLOBALS['strPassword2']);
                $html .= '<input type="hidden" name="passwordreq" value="1" />';
            } else {
                $pwdclass = 'attributename';
                $html .= '<input type="hidden" name="passwordreq" value="0" />';
            }

            $html .= sprintf('
  <tr><td><div class="%s"><label for="pwd">%s</label></div></td>
  <td class="attributeinput"><input type=password name=password value="" size="%d" id="pwd" />%s</td></tr>',
                $pwdclass, $GLOBALS['strPassword'], $textlinewidth, $js);
            $html .= sprintf('
  <tr><td><div class="%s"><label for="pwd2">%s</label></div></td>
  <td class="attributeinput"><input type="password" name="password_check" value="" size="%d" id="pwd2" />%s</td></tr>',
                $pwdclass, $GLOBALS['strPassword2'], $textlinewidth, $js2);
        }
    }

//# Write attribute fields
    switch ($htmlchoice) {
        case 'textonly':
            if (!isset($htmlemail)) {
                $htmlemail = 0;
            }
            $html .= sprintf('<input type="hidden" name="htmlemail" value="0" />');
            break;
        case 'htmlonly':
            if (!isset($htmlemail)) {
                $htmlemail = 1;
            }
            $html .= sprintf('<input type="hidden" name="htmlemail" value="1" />');
            break;
        case 'checkfortext':
            if (!isset($htmlemail)) {
                $htmlemail = 1;
            }
            $html .= sprintf('<tr><td colspan="2">
      <span class="attributeinput">
      <input type="checkbox" name="textemail" value="1" %s id="textemail" /></span>
      <span class="attributename"><label for="textemail">%s</label></span>
      </td></tr>', !$htmlemail ? 'checked="checked"' : '', $strPreferTextEmail);
            break;
        case 'radiotext':
            if (!isset($htmlemail)) {
                $htmlemail = 0;
            }
            $html .= sprintf('<tr><td colspan="2">
        <span class="attributename">%s<br/>
        <span class="attributeinput"><input type=radio name="htmlemail" value="0" %s id="htmlemail0" /></span>
        <span class="attributename"><label for="htmlemail0">%s</label></span>
        <span class="attributeinput"><input type=radio name="htmlemail" value="1" %s id="htmlemail1" /></span>
        <span class="attributename"><label for="htmlemail1">%s</label></span></td></tr>',
                $strPreferredFormat,
                !$htmlemail ? 'checked="checked"' : '', $strText,
                $htmlemail ? 'checked="checked"' : '', $strHTML);
            break;
        case 'radiohtml':
            if (!isset($htmlemail)) {
                $htmlemail = 1;
            }
            $html .= sprintf('<tr><td colspan="2">
        <span class="attributename">%s</span><br/>
        <span class="attributeinput"><input type="radio" name="htmlemail" value="0" %s id="htmlemail0" /></span>
        <span class="attributename"><label for="htmlemail0">%s</label></span>
        <span class="attributeinput"><input type="radio" name="htmlemail" value="1" %s id="htmlemail1" /></span>
        <span class="attributename"><label for="htmlemail1">%s</label></span></td></tr>',
                $strPreferredFormat,
                !$htmlemail ? 'checked="checked"' : '', $strText,
                $htmlemail ? 'checked="checked"' : '', $strHTML);
            break;
        case 'checkforhtml':
        default:
            if (!isset($htmlemail)) {
                $htmlemail = 1;
            }
            $html .= sprintf('<tr><td colspan="2">
        <span class="attributeinput"><input type="checkbox" name="htmlemail" value="1" %s id="htmlemail" /></span>
        <span class="attributename"><label for="htmlemail">%s</label></span></td></tr>', $htmlemail ? 'checked="checked"' : '', $strPreferHTMLEmail);
            break;
    }
    $html .= "\n";

    $attids = implode(',', array_keys($attributes));
    $output = array();
    if ($attids) {
        $res = Sql_Query("select * from {$GLOBALS['tables']['attribute']} where id in ($attids)");
        while ($attr = Sql_Fetch_Array($res)) {
            $output[$attr['id']] = '';
            if (!isset($data[$attr['id']])) {
                $data[$attr['id']] = '';
            }
            $attr['required'] = $attributedata[$attr['id']]['required'];
            $attr['default_value'] = $attributedata[$attr['id']]['default_value'];
            $fieldname = 'attribute'.$attr['id'];
            //  print "<tr><td>".$attr["id"]."</td></tr>";
            if ($userid && !isset($_POST[$fieldname])) {
                // post values take precedence
                $val = Sql_Fetch_Row_Query(sprintf('select value from %s where
          attributeid = %d and userid = %d', $GLOBALS['tables']['user_attribute'], $attr['id'], $userid));
                $_POST[$fieldname] = $val[0];
            } elseif (!isset($_POST[$fieldname])) {
                $_POST[$fieldname] = 0;
            }
            switch ($attr['type']) {
                case 'checkbox':
                    $output[$attr['id']] = '<tr><td colspan="2">';
                    // what they post takes precedence over the database information
                    if ($_POST[$fieldname]) {
                        $checked = $_POST[$fieldname] ? 'checked="checked"' : '';
                    } else {
                        $checked = $data[$attr['id']] ? 'checked="checked"' : '';
                    }
                    $output[$attr['id']] .= sprintf("\n".'<input type="checkbox" name="%s" value="on" %s class="attributeinput" id="'.$fieldname.'" />',
                        $fieldname, $checked);
                    $output[$attr['id']] .= sprintf("\n".'<span class="%s"><label for="'.$fieldname.'">%s</label></span>',
                        $attr['required'] ? 'required' : 'attributename', $attr['required'] ? stripslashes($attr['name']).' *' : stripslashes($attr['name']));
                    if ($attr['required']) {
                        $output[$attr['id']] .= sprintf('<script language="Javascript" type="text/javascript">addFieldToCheck("%s","%s");</script>',
                            $fieldname, $attr['name']);
                    }
                    break;
                case 'radio':
                    $output[$attr['id']] .= sprintf("\n".'<tr><td colspan="2"><div class="%s">%s</div>',
                        $attr['required'] ? 'required' : 'attributename', $attr['required'] ? stripslashes($attr['name']).' *' : stripslashes($attr['name']));
                    $values_request = Sql_Query("select * from $table_prefix".'listattr_'.$attr['tablename'].' order by listorder,name');
                    while ($value = Sql_Fetch_array($values_request)) {
                        if (!empty($_POST[$fieldname])) {
                            $checked = $_POST[$fieldname] == $value['id'] ? 'checked="checked"' : '';
                        } elseif ($data[$attr['id']]) {
                            $checked = $data[$attr['id']] == $value['id'] ? 'checked="checked"' : '';
                        } else {
                            $checked = $attr['default_value'] == $value['name'] ? 'checked="checked"' : '';
                        }
                        $output[$attr['id']] .= sprintf('<input type="radio"  class="attributeinput" name="%s" id="'.$fieldname.$value['id'].'" value="%s" %s />&nbsp;%s&nbsp;',
                            $fieldname, $value['id'], $checked, '<label for="'.$fieldname.$value['id'].'">'.$value['name'].'</label>');
                    }
                    if ($attr['required']) {
                        $output[$attr['id']] .= sprintf('<script language="Javascript" type="text/javascript">addGroupToCheck("%s","%s");</script>',
                            $fieldname, $attr['name']);
                    }
                    break;
                case 'select':
                    $output[$attr['id']] .= sprintf("\n".'<tr><td><div class="%s"><label for="'.$fieldname.'">%s</label></div>',
                        $attr['required'] ? 'required' : 'attributename', $attr['required'] ? stripslashes($attr['name']).' *' : stripslashes($attr['name']));
                    $values_request = Sql_Query("select * from $table_prefix".'listattr_'.$attr['tablename'].' order by listorder,name');
                    $output[$attr['id']] .= sprintf('</td><td class="attributeinput"><!--%d--><select name="%s" class="attributeinput" id="'.$fieldname.'">',
                        $data[$attr['id']], $fieldname);
                    while ($value = Sql_Fetch_array($values_request)) {
                        if (!empty($_POST[$fieldname])) {
                            $selected = $_POST[$fieldname] == $value['id'] ? 'selected="selected"' : '';
                        } elseif ($data[$attr['id']]) {
                            $selected = $data[$attr['id']] == $value['id'] ? 'selected="selected"' : '';
                        } elseif (!empty($attr['default_value'])) {
                            $selected = strtolower($attr['default_value']) == strtolower($value['name']) ? 'selected="selected"' : '';
                        } elseif (strtolower($attr['name']) == 'country' && !empty($_SERVER['GEOIP_COUNTRY_NAME'])) {
                            $selected = strtolower($_SERVER['GEOIP_COUNTRY_NAME']) == strtolower($value['name']) ? 'selected="selected"' : '';
                        } else {
                            $selected = '';
                        }
                        if (preg_match('/^'.preg_quote(EMPTY_VALUE_PREFIX).'/i', $value['name'])) {
                            $value['id'] = '';
                        }
                        $output[$attr['id']] .= sprintf('<option value="%s" %s>%s', $value['id'], $selected,
                            stripslashes($value['name']));
                    }
                    $output[$attr['id']] .= '</select>';
                    break;
                case 'checkboxgroup':
                    $output[$attr['id']] .= sprintf("\n".'<tr><td><div class="%s">%s</div>',
                        $attr['required'] ? 'required' : 'attributename', $attr['required'] ? stripslashes($attr['name']).' *' : stripslashes($attr['name']));
                    $values_request = Sql_Query("select * from $table_prefix".'listattr_'.$attr['tablename'].' order by listorder,name');
                    $output[$attr['id']] .= sprintf('</td>');
                    $first_td = 0;
                    while ($value = Sql_Fetch_array($values_request)) {
                        $selected = '';
                        if (is_array($_POST[$fieldname])) {
                            $selected = in_array($value['id'], $_POST[$fieldname]) ? 'checked' : '';
                        } elseif ($data[$attr['id']]) {
                            $selection = explode(',', $data[$attr['id']]);
                            $selected = in_array($value['id'], $selection) ? 'checked="checked"' : '';
                        }
                        if ($first_td == 0) {
                            $output[$attr['id']] .= sprintf('<td class="attributeinput"><input type="checkbox" name="%s[]"  class="attributeinput" value="%s" %s id="'.$fieldname.$value['id'].'" /> <label for="'.$fieldname.$value['id'].'">%s</label></td>',
                                $fieldname, $value['id'], $selected, stripslashes($value['name']));
                            $output[$attr['id']] .= sprintf('</tr>');
                        } else {
                            $output[$attr['id']] .= sprintf('<tr><td><div></div></td><td class="attributeinput"><input type="checkbox" name="%s[]"  class="attributeinput" value="%s" %s id="'.$fieldname.$value['id'].'" />  <label for="'.$fieldname.$value['id'].'">%s</label></td></tr>',
                                $fieldname, $value['id'], $selected, stripslashes($value['name']));
                        }
                        ++$first_td;
                    }
                    $first_td = 0;
                    break;
                case 'textline':
                    $output[$attr['id']] .= sprintf("\n".'<tr><td><div class="%s"><label for="'.$fieldname.'">%s</label></div>',
                        $attr['required'] ? 'required' : 'attributename', $attr['required'] ? $attr['name'].' *' : $attr['name']);
                    $output[$attr['id']] .= sprintf('</td><td class="attributeinput">
            <input type="text" name="%s"  class="attributeinput" size="%d" value="%s" id="'.$fieldname.'" />', $fieldname,
                        $textlinewidth,
                        $_POST[$fieldname] ? str_replace('"', '&#x22;', stripslashes($_POST[$fieldname])) : ($data[$attr['id']] ? $data[$attr['id']] : $attr['default_value']));
                    if ($attr['required']) {
                        $output[$attr['id']] .= sprintf('<script language="Javascript" type="text/javascript">addFieldToCheck("%s","%s");</script>',
                            $fieldname, $attr['name']);
                    }
                    break;
                case 'textarea':
                    $output[$attr['id']] .= sprintf("\n".'<tr><td colspan="2">
            <div class="%s"><label for="'.$fieldname.'">%s</label></div></td></tr>', $attr['required'] ? 'required' : 'attributename',
                        $attr['required'] ? $attr['name'].' *' : $attr['name']);
                    $output[$attr['id']] .= sprintf('<tr><td class="attributeinput" colspan="2">
            <textarea name="%s" rows="%d"  class="attributeinput" cols="%d" wrap="virtual" id="'.$fieldname.'">%s</textarea>',
                        $fieldname, $textarearows, $textareacols,
                        $_POST[$fieldname] ? str_replace(array('>', '<'), array('&gt;', '&lt;'),stripslashes($_POST[$fieldname])) : ($data[$attr['id']] ? str_replace(array('>', '<'), array('&gt;', '&lt;'),stripslashes($data[$attr['id']])) : $attr['default_value']));
                    if ($attr['required']) {
                        $output[$attr['id']] .= sprintf('<script language="Javascript" type="text/javascript">addFieldToCheck("%s","%s");</script>',
                            $fieldname, $attr['name']);
                    }
                    break;
                case 'hidden':
                    $output[$attr['id']] .= sprintf('<input type="hidden" name="%s" size="40" value="%s" />',
                        $fieldname, $data[$attr['id']] ? $data[$attr['id']] : $attr['default_value']);
                    break;
                case 'date':
                    require_once dirname(__FILE__).'/date.php';
                    $date = new Date();
                    $postval = $date->getDate($fieldname);
                    if ($data[$attr['id']]) {
                        $val = $data[$attr['id']];
                    } else {
                        $val = $postval;
                    }

                    $output[$attr['id']] = sprintf("\n".'<tr><td><div class="%s">%s</div>',
                        $attr['required'] ? 'required' : 'attributename', $attr['required'] ? $attr['name'].' *' : $attr['name']);
                    $output[$attr['id']] .= sprintf('</td><td class="attributeinput">
            %s</td></tr>', $date->showInput($fieldname, '', $val));
                    break;
                default:
                    print '<!-- error: huh, invalid attribute type -->';
            }
            $output[$attr['id']] .= "</td></tr>\n";
        }
    }

    // make sure the order is correct
    foreach ($attributes as $attribute => $listorder) {
        if (isset($output[$attribute])) {
            $html .= $output[$attribute];
        }
    }

    return $html;
}

/* same as the above, with minimal markup and no JS */
function ListAttributes2011($attributes, $attributedata, $htmlchoice = 0, $userid = 0, $emaildoubleentry = 'no')
{
    global $strPreferHTMLEmail, $strPreferTextEmail,
           $strEmail, $tables, $table_prefix, $strPreferredFormat, $strText, $strHTML;

    if ($userid) {
        $data = array();
        $current = Sql_Fetch_array_Query("select * from {$GLOBALS['tables']['user']} where id = $userid");
        $datareq = Sql_Query("select * from {$GLOBALS['tables']['user_attribute']} where userid = $userid");
        while ($row = Sql_Fetch_Array($datareq)) {
            $data[$row['attributeid']] = $row['value'];
        }

        $email = $current['email'];
        $htmlemail = $current['htmlemail'];
        // override with posted info
        foreach ($current as $key => $val) {
            if (isset($_POST[$key]) && $key != 'password') {
                $current[$key] = $val;
            }
        }
    } else {
        if (isset($_REQUEST['email'])) {
            $email = stripslashes($_REQUEST['email']);
        } else {
            $email = '';
        }
        if (isset($_POST['htmlemail'])) {
            $htmlemail = $_POST['htmlemail'];
        }
        $data = array();
        $current = array();
    }

    $textlinewidth = sprintf('%d', getConfig('textline_width'));
    if (!$textlinewidth) {
        $textlinewidth = 40;
    }
    list($textarearows, $textareacols) = explode(',', getConfig('textarea_dimensions'));
    if (!$textarearows) {
        $textarearows = 10;
    }
    if (!$textareacols) {
        $textareacols = 40;
    }

    $html = '';
    $html .= '<fieldset class="subscriberdetails">';
    $html .= sprintf('<div class="required"><label for="email">%s *</label>
    <input type="text" name="email" value="%s" class="input email required" />', $GLOBALS['strEmail'],
        htmlspecialchars($email));

    if ($emaildoubleentry == 'yes') {
        if (!isset($_REQUEST['emailconfirm'])) {
            $_REQUEST['emailconfirm'] = '';
        }
        $html .= sprintf('<label for="emailconfirm">%s</label>
      <input type="text" name="emailconfirm" value="%s" class="input emailconfirm required" />',
            $GLOBALS['strConfirmEmail'], htmlspecialchars(stripslashes($_REQUEST['emailconfirm'])),
            $GLOBALS['strConfirmEmail']);
    }

    if (ASKFORPASSWORD) {
        // we only require a password if there isnt one, so they can set it
        // otherwise they can keep the existing, if they do not enter anything
        if (!isset($current['password']) || !$current['password']) {
            $pwdclass = 'required';
            //  $html .= '<input type="hidden" name="passwordreq" value="1" />';
        } else {
            $pwdclass = 'attributename';
            //  $html .= '<input type="hidden" name="passwordreq" value="0" />';
        }

        $html .= sprintf('
      <label for="password">%s</label><input type="password" id="password" name="password" value="" class="input password required" />',
            $GLOBALS['strPassword']);
        $html .= sprintf('
      <label for="password_check">%s</label><input type="password" name="password_check" id="password_check" value="" class="input password required" />',
            $GLOBALS['strPassword2']);
    }
    $html .= '</div>'; //# class=required

    $htmlchoice = 'checkforhtml';
//# Write attribute fields
    switch ($htmlchoice) {
        case 'textonly':
            if (!isset($htmlemail)) {
                $htmlemail = 0;
            }
            $html .= sprintf('<input type="hidden" name="htmlemail" value="0" />');
            break;
        case 'htmlonly':
            if (!isset($htmlemail)) {
                $htmlemail = 1;
            }
            $html .= sprintf('<input type="hidden" name="htmlemail" value="1" />');
            break;
        case 'checkfortext':
            if (!isset($htmlemail)) {
                $htmlemail = 0;
            }
            $html .= sprintf('<fieldset class="htmlchoice"><div><input type="checkbox" name="textemail" id="textemail" value="1" %s /><label for="textemail">%s</label></div></fieldset>',
                empty($htmlemail) ? 'checked="checked"' : '', $GLOBALS['strPreferTextEmail']);
            break;
        case 'radiotext':
            if (!isset($htmlemail)) {
                $htmlemail = 0;
            }
        case 'radiohtml':
            if (!isset($htmlemail)) {
                $htmlemail = 1;
            }
            $html .= sprintf('<fieldset class="htmlchoice">
        <legend>%s</legend>
        <div><input type="radio" id="choicetext" name="htmlemail" value="0" %s /><label for="choicetext" class="htmlchoice">%s</label></div>
        <div><input type="radio" id="choicehtml" name="htmlemail" value="1" %s /><label for="choicehtml" class="htmlchoice">%s</label></div>
        </fieldset>',
                $GLOBALS['strPreferredFormat'],
                empty($htmlemail) ? 'checked="checked"' : '', $GLOBALS['strText'],
                !empty($htmlemail) ? 'checked="checked"' : '', $GLOBALS['strHTML']);
            break;
        case 'checkforhtml':
        default:
            if (!isset($htmlemail)) {
                $htmlemail = 0;
            }
            $html .= sprintf('<fieldset class="htmlchoice"><div><input type="checkbox" name="htmlemail" id="htmlemail" value="1" %s />
        <label for="htmlemail">%s</label></div></fieldset>', !empty($htmlemail) ? 'checked="checked"' : '',
                $GLOBALS['strPreferHTMLEmail']);
            break;
    }
    $html .= "</fieldset>\n";

    $html .= '<fieldset class="attributes">'."\n";

    $attids = implode(',', array_keys($attributes));
    $output = array();
    if ($attids) {
        $res = Sql_Query("select * from {$GLOBALS['tables']['attribute']} where id in ($attids)");
        while ($attr = Sql_Fetch_Array($res)) {
            $output[$attr['id']] = '';
            if (!isset($data[$attr['id']])) {
                $data[$attr['id']] = '';
            }
            $attr['required'] = $attributedata[$attr['id']]['required'];
            $attr['default_value'] = $attributedata[$attr['id']]['default_value'];
            $fieldname = 'attribute'.$attr['id'];
            //  print "<tr><td>".$attr["id"]."</td></tr>";
            if ($userid && !isset($_POST[$fieldname])) {
                // post values take precedence
                $val = Sql_Fetch_Row_Query(sprintf('select value from %s where
          attributeid = %d and userid = %d', $GLOBALS['tables']['user_attribute'], $attr['id'], $userid));
                $_POST[$fieldname] = $val[0];
            } elseif (!isset($_POST[$fieldname])) {
                $_POST[$fieldname] = 0;
            }
            switch ($attr['type']) {
                case 'checkbox':
                    $output[$attr['id']] = '';
                    // what they post takes precedence over the database information
                    if (isset($_POST[$fieldname])) {
                        $checked = !empty($_POST[$fieldname]) ? 'checked="checked"' : '';
                    } else {
                        $checked = !empty($data[$attr['id']]) ? 'checked="checked"' : '';
                    }
                    $output[$attr['id']] .= sprintf("\n".'<input type="checkbox" name="%s" value="on" %s class="input%s" />',
                        $fieldname, $checked, $attr['required'] ? ' required' : '');
                    $output[$attr['id']] .= sprintf("\n".'<label for="%s" class="%s">%s</label>', $fieldname,
                        $attr['required'] ? 'required' : '', stripslashes($attr['name']));
                    break;
                case 'radio':
                    $output[$attr['id']] .= sprintf("\n".'<fieldset class="radiogroup %s"><legend>%s</legend>',
                        $attr['required'] ? 'required' : '', stripslashes($attr['name']));
                    $values_request = Sql_Query("select * from $table_prefix".'listattr_'.$attr['tablename'].' order by listorder,name');
                    while ($value = Sql_Fetch_array($values_request)) {
                        if (!empty($_POST[$fieldname])) {
                            $checked = $_POST[$fieldname] == $value['id'] ? 'checked="checked"' : '';
                        } elseif ($data[$attr['id']]) {
                            $checked = $data[$attr['id']] == $value['id'] ? 'checked="checked"' : '';
                        } else {
                            $checked = $attr['default_value'] == $value['name'] ? 'checked="checked"' : '';
                        }
                        $output[$attr['id']] .= sprintf('<input type="radio" class="input%s" name="%s" value="%s" %s /><label for="%s">%s</label>',
                            $attr['required'] ? ' required' : '', $fieldname, $value['id'], $checked, $fieldname,
                            $value['name']);
                    }
                    $output[$attr['id']].'</fieldset>';
                    break;
                case 'select':
                    $output[$attr['id']] .= sprintf("\n".'<fieldset class="selectgroup %s"><label for="%s">%s</label>',
                        $attr['required'] ? 'required' : '', $fieldname, stripslashes($attr['name']));
                    $values_request = Sql_Query("select * from $table_prefix".'listattr_'.$attr['tablename'].' order by listorder,name');
                    $output[$attr['id']] .= sprintf('<select name="%s" class="input%s">', $fieldname,
                        $attr['required'] ? 'required' : '');
                    while ($value = Sql_Fetch_array($values_request)) {
                        if (!empty($_POST[$fieldname])) {
                            $selected = $_POST[$fieldname] == $value['id'] ? 'selected="selected"' : '';
                        } elseif ($data[$attr['id']]) {
                            $selected = $data[$attr['id']] == $value['id'] ? 'selected="selected"' : '';
                        } else {
                            $selected = $attr['default_value'] == $value['name'] ? 'selected="selected"' : '';
                        }
                        if (preg_match('/^'.preg_quote(EMPTY_VALUE_PREFIX).'/i', $value['name'])) {
                            $value['id'] = '';
                        }
                        $output[$attr['id']] .= sprintf('<option value="%s" %s>%s</option>', $value['id'], $selected,
                            stripslashes($value['name']));
                    }
                    $output[$attr['id']] .= '</select>';
                    $output[$attr['id']] .= '</fieldset>';
                    break;
                case 'checkboxgroup':
                    $output[$attr['id']] .= sprintf("\n".'<fieldset class="checkboxgroup %s"><label for="%s">%s</label>',
                        $attr['required'] ? 'required' : '', $fieldname, stripslashes($attr['name']));
                    $values_request = Sql_Query("select * from $table_prefix".'listattr_'.$attr['tablename'].' order by listorder,name');
                    $cbCounter = 0;
                    while ($value = Sql_Fetch_array($values_request)) {
                        ++$cbCounter;
                        $selected = '';
                        if (is_array($_POST[$fieldname])) {
                            $selected = in_array($value['id'], $_POST[$fieldname]) ? 'checked="checked"' : '';
                        } elseif ($data[$attr['id']]) {
                            $selection = explode(',', $data[$attr['id']]);
                            $selected = in_array($value['id'], $selection) ? 'checked="checked"' : '';
                        }
                        $output[$attr['id']] .= sprintf('<input type="checkbox" name="%s[]" id="%s%d" class="input%s" value="%s" %s /><label for="%s%d">%s</label>',
                            $fieldname, $fieldname, $cbCounter, $attr['required'] ? ' required' : '', $value['id'],
                            $selected, $fieldname, $cbCounter, stripslashes($value['name']));
                    }
                    $output[$attr['id']] .= '</fieldset>';
                    break;
                case 'textline':
                    $output[$attr['id']] .= sprintf("\n".'<label for="%s" class="input %s">%s</label>', $fieldname,
                        $attr['required'] ? ' required' : '', $attr['name']);
                    $output[$attr['id']] .= sprintf('<input type="text" name="%s" class="input%s" value="%s" />',
                        $fieldname, $attr['required'] ? ' required' : '',
                        isset($_POST[$fieldname]) ? htmlspecialchars(stripslashes($_POST[$fieldname])) : (isset($data[$attr['id']]) ? $data[$attr['id']] : $attr['default_value']));
                    break;
                case 'textarea':
                    $output[$attr['id']] .= sprintf("\n".'<label for="%s" class="input %s">%s</label>', $fieldname,
                        $attr['required'] ? ' required' : '', $attr['name']);
                    $output[$attr['id']] .= sprintf('<textarea name="%s" rows="%d" cols="%d" class="input%s" wrap="virtual">%s</textarea>',
                        $fieldname, $textarearows, $textareacols, $attr['required'] ? ' required' : '',
                        isset($_POST[$fieldname]) ? htmlspecialchars(stripslashes($_POST[$fieldname])) : (isset($data[$attr['id']]) ? htmlspecialchars(stripslashes($data[$attr['id']])) : $attr['default_value']));
                    break;
                case 'hidden':
                    $output[$attr['id']] .= sprintf('<input type="hidden" name="%s" value="%s" />', $fieldname,
                        $data[$attr['id']] ? $data[$attr['id']] : $attr['default_value']);
                    break;
                case 'date':
                    require_once dirname(__FILE__).'/date.php';
                    $date = new Date();
                    $postval = $date->getDate($fieldname);
                    if ($data[$attr['id']]) {
                        $val = $data[$attr['id']];
                    } else {
                        $val = $postval;
                    }

                    $output[$attr['id']] = sprintf("\n".'<fieldset class="date%s"><label for="%s">%s</label>',
                        $attr['required'] ? ' required' : '', $fieldname, $attr['name']);
                    $output[$attr['id']] .= sprintf('%s', $date->showInput($fieldname, '', $val));
                    $output[$attr['id']] .= '</fieldset>';
                    break;
                default:
                    print '<!-- error: huh, invalid attribute type -->';
            }
            $output[$attr['id']] .= "\n";
        }
    }

    // make sure the order is correct
    foreach ($attributes as $attribute => $listorder) {
        if (isset($output[$attribute])) {
            $html .= $output[$attribute];
        }
    }

    $html .= '</fieldset>'."\n"; //# class=attributes

//  print htmlspecialchars( '<fieldset class="phplist">'.$html.'</fieldset>');exit;
    return $html;
}

function ListAllAttributes()
{
    global $tables;
    $attributes = array();
    $attributedata = array();
    $res = Sql_Query("select * from {$GLOBALS['tables']['attribute']} order by listorder");
    while ($row = Sql_Fetch_Array($res)) {
        //   print $row["id"]. " ".$row["name"];
        $attributes[$row['id']] = $row['listorder'];
        $attributedata[$row['id']]['id'] = $row['id'];
        $attributedata[$row['id']]['default_value'] = $row['default_value'];
        $attributedata[$row['id']]['listorder'] = $row['listorder'];
        $attributedata[$row['id']]['required'] = $row['required'];
        $attributedata[$row['id']]['default_value'] = $row['default_value'];
    }

    return ListAttributes($attributes, $attributedata, 'checkforhtml');
}
