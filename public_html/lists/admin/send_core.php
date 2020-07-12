<?php
require_once dirname(__FILE__).'/accesscheck.php';

include_once dirname(__FILE__).'/date.php';
include_once dirname(__FILE__).'/analytics.php';

$errormsg = '';
$done = 0;
$messageid = 0;
$forwardsubject = $forwardmessage = $forwardfooter = '';
$placeinqueue = '';
$sendtestresult = '';
$duplicate_atribute = 0; // not actually used it seems @@@ check
$embargo = new date('embargo');
$embargo->useTime = true;
$repeatuntil = new date('repeatuntil');
$repeatuntil->useTime = true;
$requeueuntil = new date('requeueuntil');
$requeueuntil->useTime = true;

if (ALLOW_ATTACHMENTS) {
    $enctype = 'enctype="multipart/form-data"';
} else {
    $enctype = '';
}

//## variable initialisation and sanity checks
if (isset($_GET['id'])) {
    $id = sprintf('%d', $_GET['id']);
} else {
    $id = 0;
}
//# page actions
$send = isset($_POST['send']);
$prepare = isset($_POST['prepare']);
$save = !empty($_POST['save']) || !empty($_POST['followupto']);
$savedraft = !empty($_POST['savedraft']);
$sendtest = !empty($_POST['sendtest']);
$baseurl = PageURL2('send'.'&amp;id='.$id);

if (!isset($_GET['tab'])) {
    $_GET['tab'] = '';
}
$_GET['tab'] = strip_tags($_GET['tab']);
if (!empty($_GET['tab'])) {
    $baseurl .= '&tab='.$_GET['tab'];
}

//## if we're not working on an existing message, create one and redirect to edit it
if (!$id) {
    $defaulttemplate = getConfig('defaultmessagetemplate');
    $defaultfooter = getConfig('messagefooter');
    Sql_Query(sprintf('insert into %s (subject, status, entered, sendformat, embargo, repeatuntil, owner, template, tofield, replyto,footer, uuid)
    values("(no title)", "draft", now(), "HTML", now(), now(), %d, %d, "", "", "%s", "%s" )',
        $GLOBALS['tables']['message'],
        $_SESSION['logindetails']['id'],
        $defaulttemplate, sql_escape($defaultfooter), (string) Uuid::generate(4)));

    $id = Sql_Insert_Id();
    if (empty($id)) { // something went wrong creating the campaign
        Fatal_Error(s('Unable to create campaign, did you forget to upgrade the database?'));
        $done = 1;

        return;
    }

    if (isset($_GET['list'])) {
        if ($_GET['list'] == 'all') {
            $req = Sql_Query('select id from '.$tables['list']);
            while ($row = Sql_Fetch_Row($req)) {
                $addlists[] = $row[0];
            }
        } else {
            $addlists = explode(',', $_GET['list']);
        }
        $addlists = cleanArray($addlists);
        foreach ($addlists as $listid) {
            $query = sprintf('replace into %s (messageid,listid,entered) values(%d,%d,now())',
                $GLOBALS['tables']['listmessage'], $id, $listid);
            Sql_Query($query);
        }
    }

    // 0008720: Using -p send from the commandline doesn't seem to work
    if (!$GLOBALS['commandline']) {
        Redirect($_GET['page'].'&id='.$id.addCsrfGetToken());
        exit;
    }
}

// load all message data
$messagedata = loadMessageData($id);

//# auto generate the text version if empty
//# hmm, might want this as config
/*
if (empty($messagedata['textmessage'])) {
  include 'actions/generatetext.php';
}
*/

//var_dump($messagedata);
//exit;
//print '<h3>'.$messagedata['status'].'</h3>';

if (!empty($_GET['deletecriterion'])) {
    include dirname(__FILE__).'/actions/deletecriterion.php';
    Redirect($_GET['page'].'&id='.$id.'&tab='.$_GET['tab']);
}
ob_end_flush();

//load database data###########################

if ($id) {
    // Load message attributes / values
    $result = Sql_query("SELECT * FROM {$tables['message']} where id = $id $ownership");
    if (!Sql_Affected_Rows()) {
        echo $GLOBALS['I18N']->get('Access Denied');
        $done = 1;

        return;
    }

    echo formStart($enctype.' name="sendmessageform" class="sendSend" id="sendmessageform" ');
    if (empty($send)) {
        $placeinqueue = '<div id="addtoqueue"><button class="submit" type="submit" name="send" id="addtoqueuebutton">'.$GLOBALS['I18N']->get('Send Campaign').'</button></div>';
    } else {
        //# hide the div in the final "message added to queue" page
        //  print '<div id="addtoqueue"></div>';
    }
    require dirname(__FILE__).'/structure.php';  // This gets the database structures into DBStruct

    include dirname(__FILE__).'/actions/storemessage.php';
}

$htmlformatted = strip_tags($messagedata['message']) != $messagedata['message'];

// sanitise the header fields, what else do we need to check on?
if (preg_match("/\n|\r/", $messagedata['fromfield'])) {
    $messagedata['fromfield'] = '';
}
if (preg_match("/\n|\r/", $messagedata['forwardsubject'])) {
    $messagedata['forwardsubject'] = '';
}

//# check that the message does not contain URLs that look like click tracking links
//# it seems people are pasting the results of test messages back in the editor, which would duplicate
//# tracking

$hasClickTrackLinks = preg_match('/lt\.php\?id=[\w%]{22}/', $messagedata['message'],
        $regs) || preg_match('/lt\.php\?id=[\w%]{16}/', $messagedata['message'], $regs) ||
    (CLICKTRACK_LINKMAP && (preg_match('#'.CLICKTRACK_LINKMAP.'/[\w%]{22}#',
                $messagedata['message']) || preg_match('#'.CLICKTRACK_LINKMAP.'/[\w%]{16}#',
                $messagedata['message'])));

if ($hasClickTrackLinks) {
    echo Error(s('You should not paste the results of a test message back into the editor<br/>This will break the click-track statistics, and overload the server.'),
        'http://resources.phplist.com/documentation/errors/pasteclicktrack');
}
// If the variable isn't filled in, then the input fields don't default to the
// values selected.  Need to fill it in so a post will correctly display.

if (!isset($_SESSION['fckeditor_height'])) {
    $_SESSION['fckeditor_height'] = getConfig('fckeditor_height');
}

//actions and store in database#######################

if ($send || $sendtest || $prepare || $save || $savedraft) {
    verifyCsrfGetToken();
    if ($savedraft || $save || $sendtest) {
        // We're just saving, not sending.
        if (!isset($messagedata['status']) || $messagedata['status'] == '') {
            // No status - move to draft state
            $messagedata['status'] = 'draft';
        }
    } elseif ($send) {
        // We're sending - change state to "send-it" status!
        if (is_array($messagedata['targetlist']) && count($messagedata['targetlist'])
            && !empty($messagedata['subject']) && !empty($messagedata['fromfield']) &&
            !empty($messagedata['message']) && empty($duplicate_attribute)
        ) {
            $messagedata['status'] = 'submitted';
            setMessageData($id, 'status', 'submitted');
        } else {
            if (USE_PREPARE) {
                $messagedata['status'] = 'prepared';
            } else {
                $messagedata['status'] = 'draft';
            }
        }
    }

    //## allow plugins manipulate data or save it somewhere else
    $plugintabs = array();
    foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
        //  print "Saving ".$plugin->name;
        $resultMsg = $plugin->sendMessageTabSave($id, $messagedata);
    }

    if (!$htmlformatted && strip_tags($messagedata['message']) != $messagedata['message']) {
        $errormsg = '<span  class="error">'.$GLOBALS['I18N']->get('Warning: You indicated the content was not HTML, but there were  some HTML  tags in it. This  may  cause  errors').'</span>';
    }

    $result = Sql_Query(
        sprintf('update %s  set
        subject = "%s", fromfield = "%s", tofield = "%s",
        replyto ="%s", embargo = "%s", repeatinterval = "%s", repeatuntil = "%s",
        message = "%s", textmessage = "%s", footer = "%s", status = "%s",
        htmlformatted = "%s", sendformat  = "%s", template  =  "%s" where id = %d',
            $tables['message'],
            sql_escape(strip_tags($messagedata['campaigntitle'])),
            /* we store the title in the subject field. Better would be to rename the DB column, but this will do for now */
            sql_escape($messagedata['fromfield']),
            sql_escape($messagedata['tofield']),
            sql_escape($messagedata['replyto']),
            sprintf('%04d-%02d-%02d %02d:%02d',
                $messagedata['embargo']['year'], $messagedata['embargo']['month'], $messagedata['embargo']['day'],
                $messagedata['embargo']['hour'], $messagedata['embargo']['minute']), $messagedata['repeatinterval'],
            sprintf('%04d-%02d-%02d %02d:%02d',
                $messagedata['repeatuntil']['year'], $messagedata['repeatuntil']['month'],
                $messagedata['repeatuntil']['day'],
                $messagedata['repeatuntil']['hour'], $messagedata['repeatuntil']['minute']),
            sql_escape($messagedata['message']),
            sql_escape($messagedata['textmessage']),
            sql_escape($messagedata['footer']),
            sql_escape($messagedata['status']), $htmlformatted ? '1' : '0',
            sql_escape($messagedata['sendformat']),
            sql_escape($messagedata['template']), $id
        )
    );

    //# do this seperately, so that the above query doesn't fail when the DB hasn't been upgraded
    $result = Sql_Query(
        sprintf('update %s set requeueinterval = "%s", requeueuntil = "%s" where id = %d', $tables['message'],
            $messagedata['requeueinterval'], sprintf('%04d-%02d-%02d %02d:%02d',
                $messagedata['requeueuntil']['year'], $messagedata['requeueuntil']['month'],
                $messagedata['requeueuntil']['day'],
                $messagedata['requeueuntil']['hour'], $messagedata['requeueuntil']['minute']), $id
        )
    );

//    print "Message ID: $id";
    //    exit;
    if ($GLOBALS['commandline']) {
        if (isset($_POST['targetlist']) && is_array($_POST['targetlist'])) {
            Sql_query("delete from {$tables['listmessage']} where messageid = $id");
            foreach ($_POST['targetlist'] as $listid => $val) {
                $result = Sql_query("insert ignore into {$tables['listmessage']} (messageid,listid,entered) values($id,$listid,now())");
            }
        }
    }

// we want to create a join on tables as follows, in order to find users who have their attributes to the values chosen
// (independent of their list membership).
// select
//  table1.userid from user_attribute as table1
//  left join user_attribute as table2 on table1.userid = table2.userid
//  left join user_attribute as table3 on table1.userid = table3.userid
//  ...
// where
//  table1.attributeid = 2 and table1.value in (1,2,3,4)
//  and table2.attributeid = 1 and table2.value in (3,15)
//  and table3.attributeid = 3 and table3.value in (4,5,6)
//  ...

    // criteria system, add one by one:

    if (ALLOW_ATTACHMENTS && isset($_FILES) && is_array($_FILES) && count($_FILES) > 0) {
        for ($att_cnt = 1; $att_cnt <= NUMATTACHMENTS; ++$att_cnt) {
            $fieldname = 'attachment'.$att_cnt;
            if (isset($_FILES[$fieldname])) {
                $tmpfile = $_FILES[$fieldname]['tmp_name'];
                $remotename = $_FILES[$fieldname]['name'];
                $type = $_FILES[$fieldname]['type'];
                $newtmpfile = $remotename.time();
                move_uploaded_file($tmpfile, $GLOBALS['tmpdir'].'/'.$newtmpfile);
                if (is_file($GLOBALS['tmpdir'].'/'.$newtmpfile) && filesize($GLOBALS['tmpdir'].'/'.$newtmpfile)) {
                    $tmpfile = $GLOBALS['tmpdir'].'/'.$newtmpfile;
                }
                if (strlen($type) > 255) {
                    echo Warn($GLOBALS['I18N']->get('Mime Type is longer than 255 characters, this is trouble'));
                }
                $description = $_POST[$fieldname.'_description'];
            } else {
                $tmpfile = '';
            }
            if ($tmpfile && filesize($tmpfile) && $tmpfile != 'none') {
                list($name, $ext) = explode('.', basename($remotename));
                // create a temporary file to make sure to use a unique file name to store with
                $newfile = tempnam($GLOBALS['attachment_repository'], $name);
                $newfile .= '.'.$ext;
                $newfile = basename($newfile);
                $file_size = filesize($tmpfile);
                $fd = fopen($tmpfile, 'r');
                $contents = fread($fd, filesize($tmpfile));
                fclose($fd);
                if ($file_size) {
                    // this may seem odd, but it allows for a remote (ftp) repository
                    // also, "copy" does not work across filesystems
                    $fd = fopen($GLOBALS['attachment_repository'].'/'.$newfile, 'w');
                    fwrite($fd, $contents);
                    fclose($fd);
                    Sql_query(sprintf('insert into %s (filename,remotefile,mimetype,description,size) values("%s","%s","%s","%s",%d)',
                            $tables['attachment'],
                            basename($newfile), $remotename, $type, $description, $file_size)
                    );
                    $attachmentid = Sql_Insert_id();
                    Sql_query(sprintf('insert into %s (messageid,attachmentid) values(%d,%d)',
                        $tables['message_attachment'], $id, $attachmentid));
                    if (is_file($tmpfile)) {
                        unlink($tmpfile);
                    }

                    // do a final check
                    if (filesize($GLOBALS['attachment_repository'].'/'.$newfile)) {
                        echo Info(s('Attachment %d succesfully added', $att_cnt));
                    } else {
                        echo Info(s('Adding attachment %d failed', $att_cnt));
                    }
                } else {
                    echo Warn($GLOBALS['I18N']->get('Uploaded file not properly received, empty file'));
                }
            } elseif (!empty($_POST['localattachment'.$att_cnt])) {
                $type = findMime(basename($_POST['localattachment'.$att_cnt]));
                Sql_query(sprintf('insert into %s (remotefile,mimetype,description,size) values("%s","%s","%s",%d)',
                        $tables['attachment'],
                        $_POST['localattachment'.$att_cnt], $type, $description,
                        filesize($_POST['localattachment'.$att_cnt]))
                );
                $attachmentid = Sql_Insert_id();
                Sql_query(sprintf('insert into %s (messageid,attachmentid) values(%d,%d)',
                    $tables['message_attachment'], $id, $attachmentid));
                echo Info(s('Adding attachment').' '.$att_cnt." mime: $type");
            }
        }
    }

    //# when followupto is set, go there
    if (!empty($_POST['followupto']) && isValidRedirect($_POST['followupto'])) {
        header('Location: '.$_POST['followupto']);
        exit;
    }

    if (!empty($id) && !$send) {
        if ($savedraft) {
            $_SESSION['action_result'] = s('Campaign saved as draft');
            header('Location: ./?page=messages&tab=draft');
            exit;
        }
    } else {
        //    $id = $messageid; // New ID - need to set it for later use (test email).
        echo '<h3>'.$GLOBALS['I18N']->get('Campaign added').'</h3><br/>';
    }
    // var_dump($messagedata);

    // If we're sending the message, just return now to the calling script
    // we only need to check that everything is there, once we actually want to send
    if ($send && !empty($messagedata['subject']) && !empty($messagedata['fromfield']) && !empty($messagedata['message']) && empty($duplicate_atribute) && count($messagedata['targetlist'])) {
        if ($messagedata['status'] == 'submitted') {

            //#16615, check that "send until" is in after the embargo and warn if it isn't
            $finishSending = mktime($messagedata['finishsending']['hour'], $messagedata['finishsending']['minute'], 0,
                $messagedata['finishsending']['month'], $messagedata['finishsending']['day'],
                $messagedata['finishsending']['year']);
            $embargoTime = mktime($messagedata['embargo']['hour'], $messagedata['embargo']['minute'], 0,
                $messagedata['embargo']['month'], $messagedata['embargo']['day'], $messagedata['embargo']['year']);

            if ($finishSending < $embargoTime) {
                echo Warn(s('This campaign is scheduled to stop sending before the embargo time. No mails will be sent.'));
                echo PageLinkButton('send&amp;id='.$messagedata['id'].'&amp;tab=Scheduling',
                    s('Review Scheduling'));
            }
            //# reset any queued messages, as the selection may have changed
            if (defined('MESSAGEQUEUE_PREPARE') && MESSAGEQUEUE_PREPARE) {
                $query = sprintf('delete from '.$tables['usermessage'].' where messageid = %d and status = "todo"',
                    $messagedata['id']);
            }

            echo '<h3>'.$GLOBALS['I18N']->get('Campaign queued').'</h3>';
            foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
                $plugin->messageQueued($id);
            }
            if (getConfig('pqchoice') == 'phplistdotcom') {
                echo activateRemoteQueue();
            } elseif (MANUALLY_PROCESS_QUEUE) {
                echo '<p>'.PageLinkButton('processqueue', $GLOBALS['I18N']->get('processqueue')).'</p>';
            } else {
                echo '<p>'.PageLinkButton('messages&tab=active', $GLOBALS['I18N']->get('view progress')).'</p>';
            }
        }
        $done = 1;

        return;
    } elseif ($send || $sendtest) {
        $errormessage = '';
        if ($messagedata['subject'] != stripslashes($messagedata['subject'])) {
            $errormessage = $GLOBALS['I18N']->get('Sorry, you used invalid characters in the Subject field.');
        } elseif (!empty($_POST['fromfield']) && $messagedata['fromfield'] != $_POST['fromfield']) {
            $errormessage = $GLOBALS['I18N']->get('Sorry, you used invalid characters in the From field.');
        } elseif (empty($messagedata['fromfield'])) {
            $errormessage = $GLOBALS['I18N']->get('Please enter a from line.');
        } elseif (empty($messagedata['message'])) {
            $errormessage = $GLOBALS['I18N']->get('Please enter a message');
        } elseif (empty($messagedata['subject'])) {
            $errormessage = $GLOBALS['I18N']->get('Please enter a subject');
        } elseif (!empty($duplicate_attribute)) {
            $errormessage = $GLOBALS['I18N']->get('Error: you can use an attribute in one rule only');
        } elseif ($send && !is_array($_POST['targetlist'])) {
            $errormessage = $GLOBALS['I18N']->get('Please select the list(s) to send the campaign to');
        }

        //# this is now handled on the last Tab, so don't display
//    echo "$errormessage<br/>";
    }

    // OK, the message has been saved, now check to see if we need to send a test message
    if ($sendtest) {
        $sendtestresult = '<br/>';
        if (empty($_SESSION['lasttestsent'])) {
            $_SESSION['lasttestsent'] = 0;
        }

        $sendtestAllowed = true;
        //# check with plugins that sending a test is allowed
        reset($GLOBALS['plugins']);
        while ($sendtestAllowed && $plugin = current($GLOBALS['plugins'])) {
            $sendtestAllowed = $plugin->sendTestAllowed($messagedata);
            if (!$sendtestAllowed) {
                if (VERBOSE) {
                    cl_output('Sending test blocked by plugin '.$plugin->name);
                }
            }
            next($GLOBALS['plugins']);
        }

        $delay = time() - $_SESSION['lasttestsent'];
        if ($delay < SENDTEST_THROTTLE) {
            foreach ($GLOBALS['plugins'] as $plname => $plugin) {
                $plugin->processError('Send test throttled on '.$delay);
            }
            $sendtestresult .= s('You can send a test mail once every %d seconds', SENDTEST_THROTTLE).'<br/>';
            $emailaddresses = array();
        } elseif (!$sendtestAllowed) {
            $sendtestresult .= s('Sending test mails is currently not available').'<br/>';
            $emailaddresses = array();
        } else {
            // Let's send test messages to everyone that was specified in the
            if ($messagedata['testtarget'] == '') {
                $sendtestresult .= $GLOBALS['I18N']->get('No target email addresses listed for testing.').'<br/>';
            }

            if (isset($cached[$id])) {
                unset($cached[$id]);
            }
            clearPageCache();
            include 'sendemaillib.php';

            // OK, let's get to sending!
            $emailaddresses = explode(',', $messagedata['testtarget']);
            if (count($emailaddresses) > SENDTEST_MAX) {
                foreach ($GLOBALS['plugins'] as $plname => $plugin) {
                    $plugin->processError('Send test capped from '.count($emailaddresses).' to '.SENDTEST_MAX);
                }
                $limited = array_chunk($emailaddresses, SENDTEST_MAX);
                $emailaddresses = $limited[0];
                $sendtestresult .= s('There is a maximum of %d test emails allowed', SENDTEST_MAX).'<br/>';
            }
        }
        //  var_dump($emailaddresses);#exit;
        $messagedata['testtarget'] = '';
        foreach ($emailaddresses as $address) {
            $address = trim($address);
            if (empty($address)) {
                continue;
            }
            $result = Sql_query(sprintf('select id,email,uniqid,htmlemail,rssfrequency,confirmed from %s where email = "%s"',
                $tables['user'], sql_escape($address)));
            //Leftover from the preplugin era
            if ($user = Sql_fetch_array($result)) {
                if (FORWARD_ALTERNATIVE_CONTENT && $_GET['tab'] == 'Forward') {
                    if (SEND_ONE_TESTMAIL) {
                        $success = sendEmail($id, $address, $user['uniqid'], $user['htmlemail'], array(),
                            array($address));
                    } else {
                        $success = sendEmail($id, $address, $user['uniqid'], 1, array(),
                                array($address)) && sendEmail($id, $address, $user['uniqid'], 0, array(),
                                array($address));
                    }
                } else {
                    if (SEND_ONE_TESTMAIL) {
                        $success = sendEmail($id, $address, $user['uniqid'], $user['htmlemail']);
                    } else {
                        $success = sendEmail($id, $address, $user['uniqid'], 1) && sendEmail($id, $address,
                                $user['uniqid'], 0);
                    }
                }
                $sendtestresult .= $GLOBALS['I18N']->get('Sent test mail to').": $address ";
                if (!$success) {
                    $sendtestresult .= $GLOBALS['I18N']->get('failed');
                } else {
                    $sendtestresult .= $GLOBALS['I18N']->get('success');
                    $_SESSION['lasttestsent'] = time();
                }
                $sendtestresult .= '<br/>';
            } else {
                $address = htmlspecialchars(substr(strip_tags($address), 0, 255));

                $sendtestresult .= $GLOBALS['I18N']->get('Email address not found to send test message.').": $address";
                $sendtestresult .= sprintf('  <div class="inline"><a href="%s&action=addemail&email=%s%s" class="button ajaxable">%s</a></div>',
                    $baseurl, urlencode($address), addCsrfGetToken(), $GLOBALS['I18N']->get('add'));
            }
            $messagedata['testtarget'] .= $address.', ';
        }
        $messagedata['testtarget'] = substr($messagedata['testtarget'], 0, -2);
        $sendtestresult .= '<hr/>';
        $sendtestresult .= '<script type="text/javascript">this.location.hash="sendTest";</script>';
    }
} elseif (isset($_POST['deleteattachments']) && is_array($_POST['deleteattachments']) && $id) {
    if (ALLOW_ATTACHMENTS) {
        // Delete Attachment button hit...
        $deleteattachments = $_POST['deleteattachments'];
        foreach ($deleteattachments as $attid) {
            $attDetails = Sql_fetch_assoc_query(sprintf('select filename from %s where id = %d', $tables['attachment'],
                $attid));
            $phys_file = $GLOBALS['attachment_repository'].'/'.$attDetails['filename'];
            $fileParts = pathinfo($phys_file);
            $phys_file2 = $GLOBALS['attachment_repository'].'/'.$fileParts['filename']; //# to remove the file created by tempnam

            @unlink($phys_file);
            @unlink($phys_file2);

            $result = Sql_Query(sprintf('delete from %s where id = %d and messageid = %d',
                $tables['message_attachment'],
                $attid,
                $id));
            echo Info($GLOBALS['I18N']->get('Removed Attachment '));
        }
    }
}

//#############################
// Stacked attributes, processing and calculation
//#############################

//# moved to plugin

//#############################
// Stacked attributes, end
//#############################

echo $errormsg;
if (!$done) {

    //$baseurl = sprintf('./?page=%s&amp;id=%d',$_GET["page"],$id);
    if ($id) {
        $tabs = new WebblerTabs();
        $tabbaseurl = preg_replace('/&tab=[^&]+/', '', $baseurl);
        $tabs->addTab(s('Content'), $tabbaseurl.'&amp;tab=Content');
        $counttabs = 1;
        if (USE_MANUAL_TEXT_PART) {
            $tabs->addTab(s('Text'), $tabbaseurl.'&amp;tab=Text');
            ++$counttabs;
        }
        if (FORWARD_ALTERNATIVE_CONTENT) {
            $tabs->addTab(s('Forward'), $tabbaseurl.'&amp;tab=Forward');
            ++$counttabs;
        }
        $tabs->addTab(s('Format'), $tabbaseurl.'&amp;tab=Format');
        ++$counttabs;
        if (ALLOW_ATTACHMENTS) {
            $tabs->addTab(s('Attach'), $tabbaseurl.'&amp;tab=Attach');
            ++$counttabs;
        }
        $tabs->addTab(s('Scheduling'), $tabbaseurl.'&amp;tab=Scheduling');
        ++$counttabs;
        $tabs->addTab(s('Lists'), $tabbaseurl.'&amp;tab=Lists');
        ++$counttabs;

        if ($_GET['tab']) {
            $tabs->setCurrent(s($_GET['tab']));
        } else {
            $tabs->setCurrent(s('Content'));
        }
        $tabs->addLinkCode(' class="savechanges" ');

        //## allow plugins to add tabs
        $plugintabs = array();
        foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
            //   print $plugin->name;
            $plugintab = $plugin->sendMessageTab($id, $messagedata);
            if ($plugintab) {
                $plugintabname = substr(strip_tags($plugin->sendMessageTabTitle()), 0, 10);
                $plugintabs[$plugintabname] = $plugintab;
                $tabs->addTab($GLOBALS['I18N']->get($plugintabname),
                    "$tabbaseurl&amp;tab=".urlencode($plugintabname));
                if ($insertBefore = $plugin->sendMessageTabInsertBefore()) {
                    $tabs->insertTabBefore(s($insertBefore), s($plugintabname));
                }
                ++$counttabs;
            }
        }

        //# this one always last
        $tabs->addTab(s('Finish'), $tabbaseurl.'&amp;tab=Finish');
        ++$counttabs;

        // print $tabs->display();
    } ?>

    <script language="Javascript" type="text/javascript">
        // some debugging stuff to see what happens
        function checkForm() {
            //  return true;
            for (var i = 0; i < document.sendmessageform.elements.length; i++) {
                alert(document.sendmessageform.elements[i].name + " " + document.sendmessageform.elements[i].value);
            }
            return true;
        }

        // detection of unsaved changes,
        var browser = navigator.appName.substring(0, 9);
        var changed = 1;
        function haschanged() {
            changed = 1;
        }
        function savechanges() {
        }
        var event_number = 0;
        if (browser == "Microsoft") {
            document.onkeydown = haschanged;
            document.onchange = haschanged;
        } else if (browser == "Netscape") {
            document.captureEvents(Event.KEYDOWN);
            document.captureEvents(Event.CHANGE);
            document.onkeydown = haschanged;
            document.onchange = haschanged;
        }
        function submitform() {
            document.sendmessageform.submit()
        }
    </script>
    <?php
    //print '<form method="post" enctype="multipart/form-data" name="sendmessageform" onSubmit="return checkForm()">';
    echo '<input type="hidden" name="workaround_fck_bug" value="1" />';
    echo '<input type="hidden" name="followupto" value="" />';

    if ($_GET['page'] == 'preparemessage') {
        echo Help('preparemessage', $GLOBALS['I18N']->get('What is prepare a message'));
    }

    if (!defined('IN_WEBBLER')) {
        if (empty($messagedata['fromfield'])) {
            $defaultFrom = getConfig('campaignfrom_default');
            if (empty($defaultFrom)) {
                $defaultFrom = getConfig('message_from_name').' '.getConfig('message_from_address');
            }
            $messagedata['fromfield'] = $defaultFrom;

            if (!isSuperUser() && USE_ADMIN_DETAILS_FOR_MESSAGES && is_object($GLOBALS['admin_auth'])) {
                $adminemail = $GLOBALS['admin_auth']->adminEmail($_SESSION['logindetails']['id']);
                if (!empty($adminemail)) {
                    $messagedata['fromfield'] = $GLOBALS['admin_auth']->adminName($_SESSION['logindetails']['id']).' '.$adminemail;
                }
            }
        }
    }

    $formatting_content = '<div id="formatcontent">';

    //0013076: different content when forwarding 'to a friend'
    //  value="'.htmlentities($subject,ENT_QUOTES,'UTF-8').'" size="40"></td></tr> --> previous code in line 1032
    //  value="'.htmlentities($from,ENT_QUOTES,'UTF-8').'" size="40"></td></tr> --> previous code in line 1038

    $tmp = '<div id="maincontent">';
    $maincontent = $tmp;
    $forwardcontent = $tmp;

// custom code - start
    $utf8_subject = $messagedata['subject'];
    $utf8_from = $messagedata['fromfield'];
    if (empty($utf8_subject)) {
        $utf8_subject = '(no subject)';
    }
    if (empty($messagedata['campaigntitle'])) {
        $messagedata['campaigntitle'] = $utf8_subject;
    }
    /*
      if (0 && strcasecmp($GLOBALS['strCharSet'], 'utf-8') <> 0) {
         $utf8_subject = iconv($GLOBALS['strCharSet'],'UTF-8',$utf8_subject);
         $utf8_from = iconv($GLOBALS['strCharSet'],'UTF-8',$utf8_from);
      }
    */

    $maincontent .= '
  <div class="field">
    <label for="subject">' .s('Campaign subject').Help('subject').'</label>'.
    '<input type="text" name="subject"  id="subjectinput" value="' .htmlentities($utf8_subject, ENT_QUOTES, 'UTF-8').'" size="60" />
  </div>

  <div class="field"><label for="fromfield">' .$GLOBALS['I18N']->get('From Line').Help('from').'</label>'.'
    <input type="text" name="fromfield"
   value="' .htmlentities($utf8_from, ENT_QUOTES, 'UTF-8').'" size="60" /></div>';

    if (USE_REPLY_TO) {
        $maincontent .= '
  <div class="field"><label for="replyto">' .$GLOBALS['I18N']->get('Reply to').Help('from').'</label>'.'
    <input type="text" name="replyto"
   value="' .htmlspecialchars($messagedata['replyto']).'" size="60" /></div>';
    }
    $maincontent .= '
    <div class="field" id="message-text-preview">
      <label for="messagepreview">' .s('Message preview').Help('generatetextpreview').'</label>
      <input type="text" id="messagepreview" name="messagepreview" size="60" readonly />
      <div id="message-text-preview-button">' .
        PageLinkAjax('send&tab=Content&id='.$id.'&action=generatetextpreview', $GLOBALS['I18N']->get('Generate')).'</a>
      </div>
    </div>';

    $maincontent .= sprintf('

      <div id="contentchoice" class="field">
      <label for="sendmethod">' .$GLOBALS['I18N']->get('Content').Help('sendmethod').'</label>'.'
      <input type="radio" name="sendmethod" value="remoteurl" %s />' .$GLOBALS['I18N']->get('Send a Webpage').'
      <input type="radio" name="sendmethod" value="inputhere" %s />' .$GLOBALS['I18N']->get('Compose Message').'
      </div>',
        $messagedata['sendmethod'] == 'remoteurl' ? 'checked="checked"' : '',
        $messagedata['sendmethod'] == 'inputhere' ? 'checked="checked"' : ''
        );

    if (empty($messagedata['sendurl'])) {
        $messagedata['sendurl'] = 'e.g. https://www.phplist.com/testcampaign.html';
    }

    $maincontent .= '
      <div id="remoteurl" class="field"><label for="sendurl">' .$GLOBALS['I18N']->get('Send a Webpage - URL').Help('sendurl').'</label>'.'
        <input type="text" name="sendurl" id="remoteurlinput"
       value="' .htmlspecialchars($messagedata['sendurl']).'" size="60" /> <span id="remoteurlstatus"></span></div>';
    if (isset($messagedata['sendmethod']) && $messagedata['sendmethod'] != 'remoteurl') {
        $GLOBALS['pagefooter']['hideremoteurl'] = '<script type="text/javascript">$("#remoteurl").hide();</script>';
    }

// custom code - end
    //0013076: different content when forwarding 'to a friend'
    $forwardcontent .=
        '<div class="field"><label for="forwardsubject">'.$GLOBALS['I18N']->get('Subject').Help('forwardsubject').'</label>'.'
    <input type="text" name="forwardsubject" value="' .htmlentities($messagedata['forwardsubject'], ENT_QUOTES,
            'UTF-8').'" size="40" /></div>';

    $currentTime = Sql_Fetch_Row_Query('select now()');

    $scheduling_content = '<div id="schedulecontent">';
    if (defined('SYSTEM_TIMEZONE')) {
        $scheduling_content .= '
    <div class="field">' .s('phpList operates in the time zone "%s"', SYSTEM_TIMEZONE).'</div>';
    } else {
        $scheduling_content .= '
    <div class="field">' . s('Dates and times are relative to the Server Time') . '<br/>' . s('Current Server Time is') . ' <span id="servertime">' .
date('H:i, l j F Y', strtotime($currentTime[0])) . '</span>' . '</div>';
    }

    $scheduling_content .= '  <div class="field"><label for="embargo">'.$GLOBALS['I18N']->get('Embargoed Until').Help('embargo').'</label>'.'
    ' .$embargo->showInput('embargo', '', $messagedata['embargo']).'</div>
  <div class="field"><label for="finishsending">' .$GLOBALS['I18N']->get('Stop sending after').Help('finishsending').'</label>'.'
    ' .$embargo->showInput('finishsending', '', $messagedata['finishsending']).'</div>';

    if (USE_REPETITION) {
        $repeatinterval = $messagedata['repeatinterval'];

        $scheduling_content .= '
    <div class="field"><label for="repeatinterval">' .$GLOBALS['I18N']->get('Repeat campaign every').Help('repetition').'</label>'.'
        <select name="repeatinterval">
      <option value="0"';
        if ($repeatinterval == 0) {
            $scheduling_content .= ' selected="selected"';
        }
        $scheduling_content .= '>-- '.$GLOBALS['I18N']->get('no repetition').'</option>
      <option value="60"';
        if ($repeatinterval == 60) {
            $scheduling_content .= ' selected="selected"';
        }
        $scheduling_content .= '>'.$GLOBALS['I18N']->get('hour').'</option>
      <option value="1440"';
        if ($repeatinterval == 1440) {
            $scheduling_content .= ' selected="selected"';
        }
        $scheduling_content .= '>'.$GLOBALS['I18N']->get('day').'</option>
      <option value="10080"';
        if ($repeatinterval == 10080) {
            $scheduling_content .= ' selected="selected"';
        }
        $scheduling_content .= '>'.$GLOBALS['I18N']->get('week').'</option>
      <option value="20160"';
        if ($repeatinterval == 20160) {
            $scheduling_content .= ' selected="selected"';
        }
        $scheduling_content .= '>'.$GLOBALS['I18N']->get('fortnight').'</option>
      <option value="40320"';
        //# @@@TODO adding "month" is a bit trickier, as we use minutes for value, and months have varying numbers of seconds
        if ($repeatinterval == 40320) {
            $scheduling_content .= ' selected="selected"';
        }
        $scheduling_content .= '>'.$GLOBALS['I18N']->get('four weeks').'</option>
      </select>
        <label for="repeatuntil">' .$GLOBALS['I18N']->get('Repeat Until').'</label>
        ' .$repeatuntil->showInput('repeatuntil', '', $messagedata['repeatuntil']);
        $scheduling_content .= '</div>';
    }

    $requeueinterval = $messagedata['requeueinterval'];
    $scheduling_content .= '
  <div class="field"><label for="requeueinterval"> ' .$GLOBALS['I18N']->get('Requeue every').Help('requeueing').'</label>'.'
    <select name="requeueinterval">
    <option value="0"';
    if ($requeueinterval == 0) {
        $scheduling_content .= ' selected="selected"';
    }
    $scheduling_content .= '>-- '.$GLOBALS['I18N']->get('do not requeue').'</option>
    <option value="60"';
    if ($requeueinterval == 60) {
        $scheduling_content .= ' selected="selected"';
    }
    $scheduling_content .= '>'.$GLOBALS['I18N']->get('hour').'</option>
    <option value="1440"';
    if ($requeueinterval == 1440) {
        $scheduling_content .= ' selected="selected"';
    }
    $scheduling_content .= '>'.$GLOBALS['I18N']->get('day').'</option>
    <option value="10080"';
    if ($requeueinterval == 10080) {
        $scheduling_content .= ' selected="selected"';
    }
    $scheduling_content .= '>'.$GLOBALS['I18N']->get('week').'</option>
    </select>

      <label for="requeueuntil">' .$GLOBALS['I18N']->get('Requeue Until').'</label>
      ' .$requeueuntil->showInput('requeueuntil', '', $messagedata['requeueuntil']);
    $scheduling_content .= '</div>';

    $scheduling_content .= '</div>';

    $formatting_content .= '<input type="hidden" name="htmlformatted" value="auto" />';

    $formatting_content .= '
    <div class="field">
    <label for="sendformat"> ' .$GLOBALS['I18N']->get('Send as').Help('sendformat').'</label>'.'
  ' .$GLOBALS['I18N']->get('html').' <input type="radio" name="sendformat" value="HTML" ';
    $formatting_content .= $messagedata['sendformat'] == 'HTML' ? 'checked="checked"' : '';
    $formatting_content .= '/>
  ' .$GLOBALS['I18N']->get('text').' <input type="radio" name="sendformat" value="text" ';
    $formatting_content .= $messagedata['sendformat'] == 'text' ? 'checked="checked"' : '';
    $formatting_content .= '/>
  ';

//  0009687: Confusing use of the word "Both", indicating one email with both text and html and not two emails
//  $formatting_content .= $GLOBALS['I18N']->get("text and html").' <input type="radio" name="sendformat" value="text and HTML" ';
//  $formatting_content .= $_POST["sendformat"]=="text and HTML" || !isset($_POST["sendformat"]) ?"checked":"";
//  $formatting_content .= '/>';

    foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
        $plugins_sendformats = $plugin->sendFormats();
        if (is_array($plugins_sendformats) && count($plugins_sendformats)) {
            foreach ($plugins_sendformats as $val => $desc) {
                $val = preg_replace("/\W/", '', strtolower(trim($val)));
                if ($val[0] != '_') { //# allow a plugin to add a format that is not actually displayed
                    $formatting_content .= sprintf('%s <input type="radio" name="sendformat" value="%s" %s />',
                        $desc, $val, $messagedata['sendformat'] == $val ? 'checked="checked"' : '');
                }
            }
        }
    }
    $formatting_content .= '</div>';

    $req = Sql_Query("select id,title from {$tables['template']} order by listorder");
    if (Sql_affected_Rows()) {
        $formatting_content .= '<div class="field"><label for="template">'.$GLOBALS['I18N']->get('Use Template').Help('usetemplate').'</label>'.'
      <select name="template"><option value="0" hidden>' .s('--Select one--').'</option>
      <option value="0">-- ' .s('No template').'</option>';
        $req = Sql_Query("select id,title from {$tables['template']} order by listorder");
        while ($row = Sql_Fetch_Array($req)) {
            if ($row['title']) {
                $formatting_content .= sprintf('<option value="%d" %s>%s</option>', $row['id'],
                    $row['id'] == $messagedata['template'] ? 'selected="selected"' : '', $row['title']);
            }
        }
        $formatting_content .= '</select></div>';
    }
    $formatting_content .= '</div>';

    //0013076: different content when forwarding 'to a friend'
    $maincontent .= '<div id="messagecontent" class="field"><label for="message">'.s('Compose Message').Help('message').'</label> ';
    $forwardcontent .= '<div id="messagecontent" class="field"><label for="forwardmessage">'.s('Compose Message').Help('forwardmessage').'</label> ';

    if (!empty($GLOBALS['editorplugin'])) {
        $maincontent .= '<div>'.$GLOBALS['plugins'][$GLOBALS['editorplugin']]->editor('message',
                $messagedata['message']).'</div>';
    } else {
        $maincontent .= '
      <div><textarea name="message" cols="65" rows="20">' .htmlspecialchars($messagedata['message']).'</textarea></div>';
    }

    //0013076: different content when forwarding 'to a friend'
    $forwardcontent .= '<div><textarea name="forwardmessage" cols="65" rows="20">'.htmlspecialchars($messagedata['forwardmessage']).'</textarea></div>';

    //0013076: different content when forwarding 'to a friend'
    $tmp = '

  </div></div> <!-- end of message content -->
  ';

    if (isset($messagedata['sendmethod']) && $messagedata['sendmethod'] != 'inputhere') {
        $GLOBALS['pagefooter']['hidemessagecontent'] = '<script type="text/javascript">$("#messagecontent").hide()</script>';
    }
    $maincontent .= $tmp;
    $forwardcontent .= $tmp;

    if (USE_MANUAL_TEXT_PART) {
        $textcontent = '<div class="field">
    <label for="textmessage">' .$GLOBALS['I18N']->get('Plain text version of message').Help('plaintextversion').'</label>'.'
    <div id="generatetextversion">' .PageLinkAjax('send&tab=Text&id='.$id.'&action=generatetext',
                $GLOBALS['I18N']->get('generate from HTML')).'</a> '.Help('generatetext').'</div>
    <textarea id="textmessage" name="textmessage" cols="65" rows="20">' .htmlentities($messagedata['textmessage']).'</textarea>
  </div>';
    }
//var_dump($messagedata);
    //0013076: different content when forwarding 'to a friend'
    $maincontent .= '<div class="field"><label for="footer">'.$GLOBALS['I18N']->get('Footer').Help('footer').'.</label>'.'
   <textarea name="footer" cols="65" rows="5">' .htmlspecialchars($messagedata['footer']).'</textarea></div>';
    $forwardcontent .= '<div class="field"><label for="forwardfooter">'.$GLOBALS['I18N']->get('forwardfooter').Help('forwardfooter').'</label>'.'
    <textarea name="forwardfooter" cols="65" rows="5">' .htmlspecialchars($messagedata['forwardfooter']).'</textarea></div>';

    if (ALLOW_ATTACHMENTS) {
        // If we have a message id saved, we want to query the attachments that are associated with this
        // message and display that (and allow deletion of!)

        $att_content = '<div class="field"><label for="attach">'.$GLOBALS['I18N']->get('Add attachments to your campaign').Help('attachments').'</label>';
        $att_content .= '<div class="info">
      ' .$GLOBALS['I18N']->get('The upload has the following limits set by the server').':<br/>
      ' .$GLOBALS['I18N']->get('Maximum size of total data being sent to the server').': '.ini_get('post_max_size').'<br/>
      ' .$GLOBALS['I18N']->get('Maximum size of each individual file').': '.ini_get('upload_max_filesize').'</div>';

        if ($id) {
            $result = Sql_Query(sprintf('Select Att.id, Att.filename, Att.remotefile, Att.mimetype, Att.description, Att.size, MsgAtt.id linkid'.
                ' from %s Att, %s MsgAtt where Att.id = MsgAtt.attachmentid and MsgAtt.messageid = %d',
                $tables['attachment'],
                $tables['message_attachment'],
                $id));

            $ls = new WebblerListing($GLOBALS['I18N']->get('Current Attachments'));
            $totalSize = 0;
            while ($row = Sql_fetch_array($result)) {
                $ls->addElement($row['id']);
                $ls->addColumn($row['id'], $GLOBALS['I18N']->get('filename'), $row['remotefile']);
                $ls->addColumn($row['id'], $GLOBALS['I18N']->get('desc'), $row['description']);
                $ls->addColumn($row['id'], $GLOBALS['I18N']->get('size'), formatBytes($row['size']));
                $totalSize += $row['size'];
                $phys_file = $GLOBALS['attachment_repository'].'/'.$row['filename'];
                if (is_file($phys_file) && filesize($phys_file)) {
                    $ls->addColumn($row['id'], $GLOBALS['I18N']->get('file'), $GLOBALS['img_tick']);
                } else {
                    $ls->addColumn($row['id'], $GLOBALS['I18N']->get('file'), $GLOBALS['img_cross']);
                }
                $ls->addColumn($row['id'], $GLOBALS['I18N']->get('del'),
                    sprintf('<input type="checkbox" name="deleteattachments[]" value="%s"/>', $row['linkid']));
            }
            $ls->addButton(s('Delete checked'), 'javascript:document.sendmessageform.submit()');
            $att_content .= '<div>'.$ls->display().'</div>';
        }
        if (defined('MAX_MAILSIZE') && 3 * $totalSize > MAX_MAILSIZE) {  //# the 3 is roughly the size increase to encode the string
            $att_content .= Warn(s('The total size of attachments is very large. Sending this campaign may fail due to resource limits.'));
        }

        for ($att_cnt = 1; $att_cnt <= NUMATTACHMENTS; ++$att_cnt) {
            $att_content .= sprintf('<div>%s</div><div><input type="file" name="attachment%d"/>&nbsp;&nbsp;<input class="submit" type="submit" name="save" value="%s"/></div>',
                $GLOBALS['I18N']->get('New Attachment'), $att_cnt, $GLOBALS['I18N']->get('Add (and save)'));
            if (FILESYSTEM_ATTACHMENTS) {
                $att_content .= sprintf('<div><b>%s</b> %s:</div><div><input type="text" name="localattachment%d" size="50"/></div>',
                    $GLOBALS['I18N']->get('or'), $GLOBALS['I18N']->get('Path to file on server'), $att_cnt, $att_cnt);
            }
            $att_content .= sprintf('<div>%s:</div>
        <div><textarea name="attachment%d_description" cols="65" rows="3" wrap="virtual"></textarea></div>',
                $GLOBALS['I18N']->get('Description of attachment'), $att_cnt);
        }
        $att_content .= '</div>';
        // $shader = new WebblerShader("Attachments");
        // $shader->addContent($att_content);
        // $shader->initialstate = 'closed';
        // print $shader->display();
    }

    // Load the email address for the admin user so we can use that as the default value in the testtarget field
    // @@@ this only works with phplist authentication, needs to be abstracted
    if (!isset($messagedata['testtarget'])) {
        $res = Sql_Query(sprintf('Select email from %s where id = %d', $tables['admin'],
            $_SESSION['logindetails']['id']));
        $admin_details = Sql_Fetch_Array($res);

        $messagedata['testtarget'] = $admin_details['email'];
    }
    // if there isn't one, load the developer one, just being lazy here :-)
    if (empty($messagedata['testtarget']) && isset($GLOBALS['developer_email'])) {
        $messagedata['testtarget'] = $GLOBALS['developer_email'];
    }

    // Display the HTML for the "Send Test" button, and the input field for the email addresses
    $sendtest_content = '<div class="sendTest" id="sendTest">
    ' .$sendtestresult.Help('sendtest').' <b>'.s('to email address(es)').':</b><br />'.
        '<p><i>&nbsp; '.s('(comma separate addresses - all must be existing subscribers)').'</i></p>'.
        '<div class="input-group">
            <input type="text" name="testtarget" size="40" value="'.htmlspecialchars($messagedata['testtarget']).'" class="form-control blockenterkey" />
            <span class="input-group-btn">
                <input class="submit btn btn-primary" type="submit" name="sendtest" value="' .s('Send Test').'" />
             </span>
        </div>
    </div>';

    // notification of progress of message sending
    // defaulting to admin_details['email'] gives the wrong impression that this is the
    // value in the database, so it is better to leave that empty instead
    $notify_start = isset($messagedata['notify_start']) && is_email($messagedata['notify_start']) ? $messagedata['notify_start'] : ''; //$admin_details['email'];
    $notify_end = isset($messagedata['notify_end']) && is_email($messagedata['notify_end']) ? $messagedata['notify_end'] : ''; //$admin_details['email'];

    $send_content = sprintf('
    <div class="sendNotify">
    <label for="notify_start">%s<br/>%s</label><div><input type="text" name="notify_start" id="notify_start" value="%s" size="35"/></div>
    <label for="notify_end">%s<br/>%s</label><div><input type="text" name="notify_end" id="notify_end" value="%s" size="35"/></div>
    </div>',
        $GLOBALS['I18N']->get('email to alert when sending of this message starts'),
        $GLOBALS['I18N']->get('separate multiple with a comma'), $notify_start,
        $GLOBALS['I18N']->get('email to alert when sending of this message has finished'),
        $GLOBALS['I18N']->get('separate multiple with a comma'), $notify_end);

    $send_content .= sprintf('
    <div class="campaignTracking">
    <label for="cb[google_track]">%s</label><input type="hidden" name="cb[google_track]" value="1" /><input type="checkbox" name="google_track" id="google_track" value="1" %s />
    </div>',
        Help('googletrack').' '.s('Add analytics tracking code'),
        !empty($messagedata['google_track']) ? 'checked="checked"' : '');

    /* add analytics query parameters then hide if not currently enabled */
    $analytics = getAnalyticsQuery();
    $editableParameters = $analytics->editableParameters($messagedata);

    if (count($editableParameters) > 0) {
        $send_content .= '<div id="analytics">';

        foreach ($editableParameters as $field => $default) {
            $value =  isset($messagedata[$field]) ? $messagedata[$field] : $default;
            $send_content .= sprintf(
                '<label>%s <input type="text" name="%s" id="%s" value="%s" size="35"/></label>',
                $field,
                $field,
                $field,
                $value
            ) . "\n";
        }
        $send_content .= '</div>';

        if (empty($messagedata['google_track'])) {
            $GLOBALS['pagefooter']['hideanalytics'] = '<script type="text/javascript">$("#analytics").hide()</script>';
        }
    }
    $numsent = Sql_Fetch_Row_Query(sprintf('select count(*) from %s where messageid = %d',
        $GLOBALS['tables']['usermessage'], $messagedata['id']));
    if ($numsent[0] < RESETSTATS_MAX) {
        $send_content .= sprintf('
        <div class="resetStatistics">
        <label for="cb[resetstats]">%s</label><input type="hidden" name="cb[resetstats]" value="1" /><input type="checkbox" name="resetstats" id="resetstats" value="1" %s />
        </div>',
            Help('resetstats').' '.s('Reset click statistics'),
            !empty($messagedata['resetstats']) ? 'checked="checked"' : '');
    } else {
        $send_content .= '<input type="hidden" name="resetstats" value="0" />';
    }

    $send_content .= sprintf('
    <div class="isTestCampaign">
    <label for="cb[istestcampaign]">%s</label><input type="hidden" name="cb[istestcampaign]" value="1" /><input type="checkbox" name="istestcampaign" id="istestcampaign" value="1" %s />
    </div>',
        Help('istestcampaign').' '.s('This is a test campaign'),
        !empty($messagedata['istestcampaign']) ? 'checked="checked"' : '');

    $show_lists = 0;

    $send_content .= '<div class="sizeEstimate">';
    if (!empty($messagedata['htmlsize'])) {
        $send_content .= $GLOBALS['I18N']->get('Estimated size of HTML email').': '.formatBytes($messagedata['htmlsize']).'<br/>';
    }
    if (!empty($messagedata['textsize'])) {
        $send_content .= $GLOBALS['I18N']->get('Estimated size of text email').': '.formatBytes($messagedata['textsize']).'<br/>';
    }
    /*
      var_dump($messagedata['targetlist']);
    */

    if (!empty($messagedata['textsize']) || !empty($messagedata['htmlsize'])) {
        if (is_array($messagedata['targetlist']) && count($messagedata['targetlist'])) {
            $lists = $messagedata['targetlist'];
            if (isset($messagedata['excludelist'])) {
                $excludelists = $messagedata['excludelist'];
            } else {
                $excludelists = array();
            }

            if (!empty($lists['all']) || !empty($lists['allactive'])) {
                $allactive = isset($lists['allactive']);
                $all = isset($lists['all']);
                $req = Sql_Query(sprintf('select id,active from %s %s', $GLOBALS['tables']['list'], $subselect));
                $lists = array();
                while ($row = Sql_Fetch_Row($req)) {
                    if (($allactive && $row[1]) || $all) {
                        $lists[$row[0]] = $row[0];
                    }
                }
            }
            unset($lists['all']);
            unset($lists['allactive']);
            if (isset($messagedata['excludelist']) && is_array($messagedata['excludelist']) && count($messagedata['excludelist'])) {
                $exclude = sprintf(' and listuser.listid not in (%s)', implode(',', $messagedata['excludelist']));
            } else {
                $exclude = '';
            }

            $htmlcnt = Sql_Fetch_Row_Query(sprintf('select count(distinct userid) from %s listuser,%s user where user.htmlemail and user.id = listuser.userid and listuser.listid in (%s) %s',
                $GLOBALS['tables']['listuser'], $GLOBALS['tables']['user'], implode(',', array_keys($lists)), $exclude),
                1);
            $textcnt = Sql_Fetch_Row_Query(sprintf('select count(distinct userid) from %s listuser,%s user where !user.htmlemail and user.id = listuser.userid and listuser.listid in (%s) %s',
                $GLOBALS['tables']['listuser'], $GLOBALS['tables']['user'], implode(',', array_keys($lists)), $exclude),
                1);
            if ($htmlcnt[0] || $textcnt[0]) {
                if (!isset($messagedata['textsize'])) {
                    $messagedata['textsize'] = 0;
                }
                if (!isset($messagedata['htmlsize'])) {
                    $messagedata['htmlsize'] = 0;
                }

                $send_content .= $GLOBALS['I18N']->get('Estimated size of mailout').': '.formatBytes($htmlcnt[0] * $messagedata['htmlsize'] + $textcnt[0] * $messagedata['textsize']).'<br/>';
                //# remember this to see how well the estimate was
                Sql_Query(sprintf('replace into %s set name = "estimatedsize",id=%d,data = "%s"',
                    $GLOBALS['tables']['messagedata'], $id,
                    $htmlcnt[0] * $messagedata['htmlsize'] + $textcnt[0] * $messagedata['textsize']));
                $send_content .= sprintf($GLOBALS['I18N']->get('About %d users to receive HTML and %s users to receive text version of email'),
                        $htmlcnt[0], $textcnt[0]).'<br/>';
                Sql_Query(sprintf('replace into %s set name = "estimatedhtmlusers",id=%d,data = "%s"',
                    $GLOBALS['tables']['messagedata'], $id, $htmlcnt[0]));
                Sql_Query(sprintf('replace into %s set name = "estimatedtextusers",id=%d,data = "%s"',
                    $GLOBALS['tables']['messagedata'], $id, $textcnt[0]));
            }
        }
    }
    $send_content .= '</div>';

    //# the button to actually send the campagin
    $send_content .= $placeinqueue;

    echo '<div class="sendtabs_container">';

    $tabs->setListClass('sendcampaign');
    $tabs->setId('sendtabs');
//  $tabs->addPrevNext();
    $tabs->addTabNo();
    echo $tabs->display();
    //print '<div id="tabcontent"></div>';

    $panelcontent = '';
    switch ($_GET['tab']) {
        case 'Attach':
            $panelcontent = $att_content;
            break;
        //   case "Criteria": print $criteria_content; break; // moved to plugin
        case 'Text':
            $panelcontent = $textcontent;
            break;
        case 'Format':
            $panelcontent = $formatting_content;
            break;
        case 'Scheduling':
            $panelcontent = $scheduling_content;
            break;
//    case "RSS": print $rss_content;break;            //Obsolete by rssmanager plugin
        case 'Lists':
            $show_lists = 1;
            break;
        case 'Review':
            $panelcontent = $review_content;
            break;
        case 'Finish':
            $panelcontent = $send_content;
            break;
        case 'Forward':
            $panelcontent = $forwardcontent;
            break;
        default:
            $isplugin = 0;
            foreach ($plugintabs as $tabname => $tabcontent) {
                if ($_GET['tab'] == $tabname) {
                    $panelcontent = $tabcontent;
                    $isplugin = 1;
                }
            }
            if (!$isplugin) {
                $panelcontent = $maincontent;
            }
            break;
    }
}

$GLOBALS['pagefooter']['sendtabs'] = "<script language='Javascript' type='text/javascript' src='js/jquery.cycle2.min.js'></script>
<script>
    $(document).ready(function(){
        var counttab = ".$counttabs.";
        var currenttab = $('.current').attr('id');
        var tabvis = (($('.sendtabs_container').width()-50)/102)+'';
        var arrvis = tabvis.split('.');
        var tabdif = currenttab-arrvis[0];
        if (counttab <= arrvis[0]) {arrvis[0] = counttab};
        $('.sendcampaign').cycle({ slides:'> li', timeout:0, fx:'carousel', allowWrap:false, carouselVisible:arrvis[0], next:'.nexttab', prev:'.prevtab' });
        $('.sendcampaign').cycle('goto',tabdif);

        $(window).on('resize',function() {
            $('.sendcampaign').cycle('destroy');
            tabvis = (($('.sendtabs_container').width()-50)/102)+'';
            arrvis = tabvis.split('.');
            tabdif = currenttab-arrvis[0];
            if (counttab <= arrvis[0]) {arrvis[0] = counttab};
            $('.sendcampaign').cycle({ slides:'> li', timeout:0, fx:'carousel', allowWrap:false, carouselVisible:arrvis[0], next:'.nexttab', prev:'.prevtab' });
            $('.sendcampaign').cycle('goto',tabdif);
        });
    });
</script><style>#sendtabs ul{margin:0 1px}</style>";

/* OLD SCRIPT:

    "<script type='text/javascript'>\n".
    '$(document).ready(function() {
    var counttab = ' .$counttabs.";
    var currenttab = $('.current').attr('id');
    if(matchMedia('only screen and (max-width: 480px)').matches){ tabs=2; starttab=currenttab-2;  }
    else if(matchMedia('only screen and (max-width: 767px)').matches){ tabs=3; starttab=currenttab-3;}
    else if(matchMedia('only screen and (max-width: 967px)').matches){ tabs=4; starttab=currenttab-4;}
    else{ tabs=6; starttab=currenttab-6; if ( counttab < 7 ){ $('.nexttab').addClass('disabled'); } }
    if ( starttab < 1 ){ starttab=0; $('.prevtab').addClass('disabled'); }
    if ( currenttab>=counttab ){ $('.nexttab').addClass('disabled'); }

    $('#sendtabs').jCarouselLite({
        btnNext: '.nexttab',
        btnPrev: '.prevtab',
        circular: false,
        visible: tabs,
        auto: null,
        speed:100,
        scroll:1,
        start: starttab
    });
});
$(window).resize(function(){
    var counttab = " .$counttabs.";
    var currenttab = $('.current').attr('id');
    if(matchMedia('only screen and (max-width: 480px)').matches){ tabs=2; $('.nexttab').removeClass('disabled'); starttab=currenttab-2;}
    else if(matchMedia('only screen and (max-width: 767px)').matches){ tabs=3; $('.nexttab').removeClass('disabled'); starttab=currenttab-3;}
    else if(matchMedia('only screen and (max-width: 967px)').matches){ tabs=4; $('.nexttab').removeClass('disabled'); starttab=currenttab-4;}
    else{ tabs=6; starttab=currenttab-6; if ( counttab < 7){ $('.nexttab').addClass('disabled'); } }
    if ( starttab < 1 ){ starttab=0; $('.prevtab').addClass('disabled'); }
    if ( currenttab>=counttab ){ $('.nexttab').addClass('disabled'); }
    $('#sendtabs').jCarouselLite({
        btnNext: '.nexttab',
        btnPrev: '.prevtab',
        circular: false,
        visible: tabs,
        auto: null,
        speed:100,
        scroll:1,
        start: starttab
    });
});

</script>";
*/

echo '<img src="ui/'.$GLOBALS['ui'].'/images/prevtab.png" id="prev" class="prevtab" />';
echo '<img src="ui/'.$GLOBALS['ui'].'/images/nexttab.png" id="next" class="nexttab" />';
echo '</div>';

//# if all is there, we can enable the send button
$allReady = true;
$GLOBALS['pagefooter']['addtoqueue'] = '<script type="text/javascript">
$("#addtoqueue").html("");
</script>';

$testValue = trim($messagedata['subject']);
if (empty($testValue) || $testValue == '(no subject)') {
    $allReady = false;
    $GLOBALS['pagefooter']['addtoqueue'] .= '<script type="text/javascript">
  $("#addtoqueue").append(\'<div class="missing">' .$GLOBALS['I18N']->get('subject missing').'</div>\');
  </script>';
}
$testValue = trim($messagedata['message']);
$testValue2 = trim($messagedata['sendurl']);

if (empty($testValue) && (empty($testValue2) || $testValue2 == 'e.g. https://www.phplist.com/testcampaign.html')) {
    $allReady = false;
    $GLOBALS['pagefooter']['addtoqueue'] .= '<script type="text/javascript">
  $("#addtoqueue").append(\'<div class="missing">' .$GLOBALS['I18N']->get('message content missing').'</div>\');
  </script>';
}

if ($messagedata['sendmethod'] == 'remoteurl') {
    $code = testUrl($messagedata['sendurl']);
    if ($code != 200) {
        $allReady = false;
        $GLOBALS['pagefooter']['addtoqueue'] .= '<script type="text/javascript">
        $("#addtoqueue").append(\'<div class="missing">' .s('Incorrect URL for sending').' ('.resourceLink('http://resources.phplist.com/documentation/errors/sendurlinvalid').')</div>\');
        </script>';
    }
}

$testValue = trim($messagedata['fromfield']);
if (empty($testValue)) {
    $allReady = false;
    $GLOBALS['pagefooter']['addtoqueue'] .= '<script type="text/javascript">
  $("#addtoqueue").append(\'<div class="missing">' .$GLOBALS['I18N']->get('From missing').'</div>\');
  </script>';
}

//#16615, check that "send until" is in after the embargo and warn if it isn't
$finishSending = mktime($messagedata['finishsending']['hour'], $messagedata['finishsending']['minute'], 0,
    $messagedata['finishsending']['month'], $messagedata['finishsending']['day'],
    $messagedata['finishsending']['year']);
$embargoTime = mktime($messagedata['embargo']['hour'], $messagedata['embargo']['minute'], 0,
    $messagedata['embargo']['month'], $messagedata['embargo']['day'], $messagedata['embargo']['year']);
$currentTime = time();

if ($finishSending < $embargoTime) {
    $allReady = false;
    $GLOBALS['pagefooter']['addtoqueue'] .= '<script type="text/javascript">
  $("#addtoqueue").append(\'<div class="missing">' .s('This campaign is scheduled to stop sending before the embargo time. No mails will be sent.').'<br/>'.PageLinkButton('send&amp;id='.$messagedata['id'].'&amp;tab=Scheduling',
            s('Review Scheduling')).'</div>\');
  </script>';
} elseif ($finishSending < $currentTime) {
    $allReady = false;
    $GLOBALS['pagefooter']['addtoqueue'] .= '<script type="text/javascript">
  $("#addtoqueue").append(\'<div class="missing">' .s('This campaign is scheduled to stop sending in the past. No mails will be sent.').'<br/>'.PageLinkButton('send&amp;id='.$messagedata['id'].'&amp;tab=Scheduling',
            s('Review Scheduling')).'</div>\');
  </script>';
}

if (empty($messagedata['targetlist'])) {
    $allReady = false;
    $GLOBALS['pagefooter']['addtoqueue'] .= '<script type="text/javascript">
  $("#addtoqueue").append(\'<div class="missing">' .$GLOBALS['I18N']->get('destination lists missing').'</div>\');
  </script>';
}
if ($hasClickTrackLinks && BLOCK_PASTED_CLICKTRACKLINKS) {
    $allReady = false;
    $GLOBALS['pagefooter']['addtoqueue'] .= '<script type="text/javascript">
  $("#addtoqueue").append(\'<div class="missing">' .s('Content contains click track links.').resourceLink('http://resources.phplist.com/documentation/errors/pasteclicktrack').'</div>\');
  </script>';
}

foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
    $pluginerror = '';
    $pluginerror = $plugin->allowMessageToBeQueued($messagedata);
    if ($pluginerror) {
        $allReady = false;
        $pluginerror = preg_replace("/\n/", '', $pluginerror);
        $GLOBALS['pagefooter']['addtoqueue'] .= '<script type="text/javascript">
    $("#addtoqueue").append(\'<div class="missing">' .$pluginerror.'</div>\');
    </script>';
    }
}

if ($allReady) {
    $GLOBALS['pagefooter']['addtoqueue'] .= '<script type="text/javascript">
  $("#addtoqueue").html(\'<input class="action-button" type="submit" name="send" id="addtoqueuebutton" value="' .htmlspecialchars(str_replace("'",
            "\'", s('Place Campaign in Queue for Sending'))).'">\');
  </script>';
} else {
    $GLOBALS['pagefooter']['addtoqueue'] .= '<script type="text/javascript">
  $("#addtoqueue").append(\'<div class="error">' .$GLOBALS['I18N']->get('Some required information is missing. The send button will be enabled when this is resolved.').'</div>\');
//  $("#addtoqueue").append(\'<button class="submit" type="submit" name="save" id="addtoqueuebutton" disabled="disabled">' .$GLOBALS['I18N']->get('Send Campaign').'</button>\');
  </script>';
}

$saveDraftButton = '<div class="sendSubmit">
    <input class="submit" type="submit" name="savedraft" value="' .s('Save as draft').'"/>
    <input type="hidden" name="id" value="' .$id.'"/>
    <input type="hidden" name="status" value="draft"/>
    <input class="submit" type="submit" name="save" value="' .s('Save and continue editing').'"/>
  </div>
';

$titleInput = '<label for="campaigntitle">'.s('Campaign Title').Help('campaigntitle').'</label>'.
    '<input type="text" name="campaigntitle"  id="campaigntitleinput"
    value="' .htmlentities($messagedata['campaigntitle'], ENT_QUOTES, 'UTF-8').'" size="60" />';
$metaPanel = new UIPanel(s('Meta data'), $titleInput);
$metaPanel->setID('metadata');

$testpanel = new UIPanel(s('Send Test'), $sendtest_content);
$testpanel->setID('testpanel');

# print $testpanel->display();
