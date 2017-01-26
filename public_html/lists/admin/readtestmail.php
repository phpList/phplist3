<?php

require_once dirname(__FILE__).'/accesscheck.php';

//#######################################################################
// Reads mail from a test account that will recieve all sent mail.
// Use developer_email to send all mail to one account.
// Use test_email settings to pop this box.
// This file shows the links from the first email foun d for a selected user.
// The links can be use to for instance confirm the user in an automated test.

// 2007 Bas Ovink - tincan ltd
//#######################################################################

//CREATE TABLE `dev-phplist`.`phplist_testemail` (
//`id` int( 11 ) NOT NULL AUTO_INCREMENT ,
//`date` datetime default NULL ,
//`header` text,
//`data` blob,
//`status` varchar( 255 ) default NULL ,
//`comment` text,
//PRIMARY KEY ( `id` ) ,
//KEY `dateindex` ( `date` )
//) ENGINE = MYISAM DEFAULT CHARSET = latin1 AUTO_INCREMENT =34;

if (!$GLOBALS['commandline']) {
    ob_end_flush();
    if (!MANUALLY_PROCESS_testS) {
        echo $GLOBALS['I18N']->get('This page can only be called from the commandline');

        return;
    }
} else {
    ob_end_clean();
    echo ClineSignature();
    ob_start();
}

function prepareOutput()
{
    global $outputdone;
    if (!$outputdone) {
        $outputdone = 1;

        return formStart('name="outputform" class="readtestmailOutput" ').'<textarea name="output" rows=10 cols=70></textarea></form>';
    }
}

$report = '';
//# some general functions
function finish($flag, $message)
{
    if ($flag == 'error') {
        $subject = $GLOBALS['I18N']->get('test processing error');
    } elseif ($flag == 'info') {
        $subject = $GLOBALS['I18N']->get('test Processing info');
    }
    if (!TEST && $message) {
        sendReport($subject, $message);
    }

    // try..catch
    global $link;
    imap_close($link);
}

function ProcessError($message)
{
    output("$message");
    finish('error', $message);
    exit;
}

function processTestEmails_shutdown()
{
    global $report, $process_id;
    releaseLock($process_id);
    // $report .= "Connection status:".connection_status();
    finish('info', $report);
    if (!$GLOBALS['commandline']) {
        include_once dirname(__FILE__).'/footer.inc';
    }
}

function output($message, $reset = 0)
{
    $infostring = '['.date('D j M Y H:i',
            time()).'] ['.getenv('REMOTE_HOST').'] ['.getenv('REMOTE_ADDR').']';
    //print "$infostring $message<br/>\n";
    $message = preg_replace("/\n/", '', $message);
    //# contribution from http://forums.phplist.com/viewtopic.php?p=14648
    //# in languages with accented characters replace the HTML back
    //Replace the "&rsquo;" which is not replaced by html_decode
    $message = preg_replace('/&rsquo;/', "'", $message);
    //Decode HTML chars
    //$message = html_entity_decode($message,ENT_QUOTES,$_SESSION['adminlanguage']['charset']);
    $message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
    if ($GLOBALS['commandline']) {
        ob_end_clean();
        echo strip_tags($message)."\n";
        ob_start();
    } else {
        if ($reset) {
            echo '<script language="Javascript" type="text/javascript">
                                                                  //        if (document.forms[0].name == "outputform") {
                                                                            document.outputform.output.value = "";
                                                                            document.outputform.output.value += "\n";
                                                                  //        }
                                                                        </script>' ."\n";
        }

        echo '<script language="Javascript" type="text/javascript">
                                            //      if (document.forms[0].name == "outputform") {
                                                    document.outputform.output.value += "' .$message.'";
                                                    document.outputform.output.value += "\n";
                                            //      } else
                                            //        document.writeln("' .$message.'");
                                                </script>' ."\n";
    }

    flush();
}

//function processTestEmail($link, $mailIndex, $header) {
//  global $tables;
//  $headerinfo= imap_headerinfo($link, $mailIndex);
//  $header= imap_fetchheader($link, $mailIndex);

//  $body= imap_body($link, $mailIndex);
//  $msgid= 0;
//  $user= 0;
//  preg_match("/X-MessageId: (.*)/i", $header, $match);
//  if (is_array($match) && isset ($match[1]))
//    $msgid= trim($match[1]);
//  if (!$msgid) {
//    # older versions use X-Message
//    preg_match("/X-Message: (.*)/i", $header, $match);
//    if (is_array($match) && isset ($match[1]))
//      $msgid= trim($match[1]);
//  }

//  preg_match("/X-ListMember: (.*)/i", $header, $match);
//  if (is_array($match) && isset ($match[1]))
//    $user= trim($match[1]);
//  if (!$user) {
//    # older version use X-User
//    preg_match("/X-User: (.*)/i", $header, $match);
//    if (is_array($match) && isset ($match[1]))
//      $user= trim($match[1]);
//  }
//  # some versions used the email to identify the users, some the userid and others the uniqid
//  # use backward compatible way to find user
//  if (preg_match("/.*@.*/i", $user, $match)) {
//    $userid_req= Sql_Fetch_Row_Query("select id from {$tables["user"]} where email = \"$user\"");
//    if (VERBOSE)
//      output("UID" .
//      $userid_req[0] . " MSGID" . $msgid);
//    $userid= $userid_req[0];
//  }
//  elseif (preg_match("/^\d$/", $user)) {
//    $userid= $user;
//    if (VERBOSE)
//      output("UID" . $userid . " MSGID" . $msgid);
//  }
//  elseif ($user) {
//    $userid_req= Sql_Fetch_Row_Query("select id from {$tables["user"]} where uniqid = \"$user\"");
//    if (VERBOSE)
//      output("UID" . $userid_req[0] . " MSGID" . $msgid);
//    $userid= $userid_req[0];
//  } else {
//    $userid= '';
//  }
//  //  Sql_Query(sprintf('insert into %s (date,header,data)
//  //      values("%s","%s","%s")', 'phplist_testemail', date("Y-m-d H:i", @ strtotime($headerinfo->date)), addslashes($header), addslashes($body)));
//  //  $testid= Sql_Insert_id();
//  //  if ($userid) {
//  //    Sql_Query(sprintf('update %s
//  //          set status = "test system message",
//  //          comment = "userid %s"
//  //          where id = %d', 'phplist_testemail', $userid, $testid));
//  //  } else {
//  //    Sql_Query(sprintf('update %s
//  //          set status = "unidentified test",
//  //          comment = "not processed"
//  //          where id = %d', 'phplist_testemail', $testid));
//  //    return false;
//  //  }
//  dbg($userid, '$userid');
//  return true;
//}

function openPop($server, $user, $password)
{
    $port = $GLOBALS['test_mailbox_port'];
    if (!$port) {
        $port = '110/pop3/notls';
    }
    set_time_limit(6000);

    if (!TEST) {
        $link = imap_open('{'.$server.':'.$port.'}INBOX', $user, $password, CL_EXPUNGE);
    } else {
        $link = imap_open('{'.$server.':'.$port.'}INBOX', $user, $password);
    }

    if (!$link) {
        output($GLOBALS['I18N']->get('Cannot create POP3 connection to')." $server: ".imap_last_error());

        return;
    }

    return $link;
}

function openMbox($file)
{
    set_time_limit(6000);

    if (!TEST) {
        $link = imap_open($file, '', '', CL_EXPUNGE);
    } else {
        $link = imap_open($file, '', '');
    }
    if (!$link) {
        output($GLOBALS['I18N']->get('Cannot open mailbox file').' '.imap_last_error());

        return;
    }

    return $link;
}

function findEmailForUser($link, $mailToFind, $max = 3000)
{
    global $test_mailbox_purge_unprocessed, $test_mailbox_purge;
    output(sprintf('Looking for emails to %s', $mailToFind));

    $num = imap_num_msg($link);
    output($num.' '.$GLOBALS['I18N']->get('mails in mailbox')."\n");
    output($GLOBALS['I18N']->get('Please do not interrupt this process')."\n");
    if ($num > $max) {
        echo $GLOBALS['I18N']->get('Processing first')." $max ".$GLOBALS['I18N']->get('tests').'<br/>';
        $num = $max;
    }

    $nberror = 0;
    $found = false;
    $mailIndex = 0;
    while (!$found && $mailIndex++ <= $num) { //for ($x= 1; $x <= $num; $x++)
        set_time_limit(60);
        $header = imap_fetchheader($link, $mailIndex);
        preg_match('/X-ListMember: (.*)/i', $header, $match);

        if (is_array($match) && isset($match[1])) {
            $match[1] = trim($match[1]);
            $found = $mailToFind == $match[1];
            if (!$found) {
                printf('<a href="?page=readtestmail&amp;email=%s">Get (& delete) %s</a><br />', $match[1], $match[1]);
            }
        }

        if ($found) {
            output('Message found');
            if (!TEST && $test_mailbox_purge) {
                output($GLOBALS['I18N']->get('Deleting message')." $mailIndex");
                imap_delete($link, $mailIndex);
            }
        } else {
            if (!TEST && $test_mailbox_purge_unprocessed) {
                output($GLOBALS['I18N']->get('Deleting message')." $mailIndex");
                imap_delete($link, $mailIndex);
            }
        }
        flush();
    }
    flush();
    output($GLOBALS['I18N']->get('Closing mailbox, and purging messages'));
    if ($found) {
        return $num;
    } else {
        return;
    }
}

//############################################
// main
function main()
{
}

if (!function_exists('imap_open')) {
    Error($GLOBALS['I18N']->get('IMAP is not included in your PHP installation, cannot continue').
        '<br/>'.$GLOBALS['I18N']->get('Check out').
        ' <a href="http://www.php.net/manual/en/ref.imap.php">http://www.php.net/manual/en/ref.imap.php</a>');

    return;
}

flush();
$outputdone = 0;

// lets not do this unless we do some locking first
register_shutdown_function('processTestEmails_shutdown');
$abort = ignore_user_abort(1);
$process_id = getPageLock();

if (!empty($_REQUEST['email'])) {
    $mailToFind = $_REQUEST['email'];
    echo prepareOutput();

    switch ($test_protocol) {
        case 'pop':
            $link = openPop($test_mailbox_host, $test_mailbox_user, $test_mailbox_password);
            break;
        case 'mbox':
            $link = openMbox($test_mailbox);
            break;
        default:
            Error($GLOBALS['I18N']->get('test_protocol not supported'));

            return;
    }

    if (isset($link)) {
        $mailIndex = findEmailForUser($link, $mailToFind);
        if (!is_null($mailIndex)) {
            $body = imap_body($link, $mailIndex);
            $overview = imap_fetch_overview($link, $mailIndex);
            printf('Subject: %s<br />', $overview[0]->subject);

            preg_match_all("/<a(.*)href=[\"\'](.*)[\"\']([^>]*)>/Umis", $body, $links);
            foreach ($links[0] as $matchindex => $fullmatch) {
                preg_match('/p=(\w+)/', $fullmatch, $linkPages);
                printf('<a href="%s" id=%s>Link %s: %s</a><br />', $fullmatch, $matchindex++, $matchindex,
                    $linkPages[1]);
            }

            preg_match_all('/http:\/\/\S+/', $body, $links);
            foreach ($links[0] as $matchindex => $fullmatch) {
                preg_match('/p=(\w+)/', $fullmatch, $linkPages);
                printf('<a href="%s" id=%s>Link %s: %s</a><br />', $fullmatch, $matchindex++, $matchindex,
                    $linkPages[1]);
            }
        }
    }
}

{
    echo '<form method="get">';
    echo '  <input name="page" value="readtestmail" type="hidden" />';
    echo '  <input class="submit" type="submit" name="action" value="Get email for user: " />';
    printf('  <input type="text" name="email" value="%s" />', $_REQUEST['email']);
    echo '</form>';
}
