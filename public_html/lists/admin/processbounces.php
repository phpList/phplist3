<?php

require_once dirname(__FILE__).'/accesscheck.php';

if (!$GLOBALS['commandline'] && !$GLOBALS['inRemoteCall']) {
    // browser session
    ob_end_flush();
    if (!MANUALLY_PROCESS_BOUNCES) {
        echo $GLOBALS['I18N']->get('This page can only be called from the commandline');

        return;
    }
    if (isset($_GET['login']) || isset($_GET['password'])) {
        echo Error(s('Remote processing of the queue is now handled with a processing secret'));

        return;
    }

    //# we're in a normal session, so the csrf token should work
    verifyCsrfGetToken();
}

flush();
$outputdone = 0;
function prepareOutput()
{
    global $outputdone;
    if (!$outputdone) {
        $outputdone = 1;

        return formStart('name="outputform" class="processbounces" ').'<textarea name="output" rows=10 cols=50></textarea></form>';
    }
}

$report = '';
//# some general functions
function finishBounceProcessing($flag, $message)
{
    if ($flag == 'error') {
        $subject = $GLOBALS['I18N']->get('Bounce processing error');
    } elseif ($flag == 'info') {
        $subject = $GLOBALS['I18N']->get('Bounce Processing info');
    }
    if (!TEST && $message) {
        sendReport($subject, $message);
    }
}

function bounceProcessError($message)
{
    outputProcessBounce("$message");
    finishBounceProcessing('error', $message);
    exit;
}

function processbounces_shutdown()
{
    global $report, $process_id;
    releaseLock($process_id);
    // $report .= "Connection status:".connection_status();
    finishBounceProcessing('info', $report);
}

function outputProcessBounce($message, $reset = 0)
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
        cl_output($message);
    } elseif ($GLOBALS['inRemoteCall']) {
        ob_end_clean();
        echo $message, "\n";
        ob_start();
    } else {
        if ($reset) {
            echo '<script language="Javascript" type="text/javascript">
//        if (document.forms[0].name == "outputform") {
          document.outputform.output.value = "";
          document.outputform.output.value += "\n";
//        }
      </script>' .PHP_EOL;
        }

        echo '<script language="Javascript" type="text/javascript">
//      if (document.forms[0].name == "outputform") {
        document.outputform.output.value += "' .$message.'";
        document.outputform.output.value += "\n";
//      } else
//        document.writeln("' .$message.'");
    </script>' .PHP_EOL;
    }

    flush();
}

function findMessageId($text)
{
    $msgid = 0;

    if (preg_match('/(?:X-MessageId|X-Message): (.*)\r\n/iU', $text, $match)) {
        $msgid = trim($match[1]);
    }

    return $msgid;
}

function findUserID($text)
{
    global $tables;
    $userid = 0;
    $user = '';

    if (preg_match('/(?:X-ListMember|X-User): (.*)\r\n/iU', $text, $match)) {
        $user = trim($match[1]);
    }

    // some versions used the email to identify the users, some the userid and others the uniqid
    // use backward compatible way to find user
    if (strpos($user, '@') !== false) {
        $userid_req = Sql_Fetch_Row_Query(sprintf('select id from %s where email = "%s"', $tables['user'],
            sql_escape($user)));
        $userid = $userid_req[0];
    } elseif (preg_match("/^\d$/", $user)) {
        $userid = $user;
    } elseif (!empty($user)) {
        $userid_req = Sql_Fetch_Row_Query(sprintf('select id from %s where uniqid = "%s"', $tables['user'],
            sql_escape($user)));
        $userid = $userid_req[0];
    }

    //## if we didn't find any, parse anything looking like an email address and check if it's a subscriber.
    //# this is probably fairly time consuming, but as the process is only done once every so often
    //# that should not be too bad

    if (!$userid) {
        preg_match_all('/[._a-zA-Z0-9-]+@[.a-zA-Z0-9-]+/', $text, $regs);

        foreach ($regs[0] as $email) {
            $useridQ = Sql_Fetch_Row_Query(sprintf('select id from %s where email = "%s"', $tables['user'],
                sql_escape($email)));
            if (!empty($useridQ[0])) {
                $userid = $useridQ[0];
                break;
            }
        }
    }

    return $userid;
}

function decodeBody($header, $body)
{
    $transfer_encoding = '';
    if (preg_match('/Content-Transfer-Encoding: ([\w-]+)/i', $header, $regs)) {
        $transfer_encoding = strtolower($regs[1]);
    }
    switch ($transfer_encoding) {
        case 'quoted-printable':
            $decoded_body = @imap_qprint($body);
            break;
        case 'base64':
            $decoded_body = @imap_base64($body);
            break;
        case '7bit':
        case '8bit':
        default:
            // $body = $body;
    }
    if (!empty($decoded_body)) {
        return $decoded_body;
    } else {
        return $body;
    }
}

function processImapBounce($link, $num, $header)
{
    global $tables;
    $headerinfo = imap_headerinfo($link, $num);
    $bounceDate = @strtotime($headerinfo->date);
    $body = imap_body($link, $num);
    $body = decodeBody($header, $body);

    $msgid = findMessageId($body);
    $userid = findUserID($body);
    if (VERBOSE) {
        outputProcessBounce('UID'.$userid.' MSGID'.$msgid);
    }

    //# @TODO add call to plugins to determine what to do.
    // for now, quick hack to zap MsExchange Delayed messages
    if (preg_match('/Action: delayed\s+Status: 4\.4\.7/im', $body)) {
        //# just say we did something, when actually we didn't
        return true;
    }
    $bounceDateFormatted = date('Y-m-d H:i:s', $bounceDate);

    Sql_Query(sprintf('insert into %s (date,header,data)
    values("%s","%s","%s")',
        $tables['bounce'],
        $bounceDateFormatted,
        addslashes($header),
        addslashes($body)));

    $bounceid = Sql_Insert_Id();

    return processBounceData($bounceid, $msgid, $userid, $bounceDateFormatted);
}

function processBounceData($bounceid, $msgid, $userid, $bounceDate = null)
{
    global $tables;
    $useremailQ = Sql_fetch_row_query(sprintf('select email from %s where id = %d', $tables['user'], $userid));
    $useremail = $useremailQ[0];

    if ($bounceDate === null) {
        $bounceDate = date('Y-m-d H:i', time());
    }

    if ($msgid === 'systemmessage' && !empty($userid)) {
        Sql_Query(sprintf('update %s
      set status = "bounced system message",
      comment = "%s marked unconfirmed"
      where id = %d',
            $tables['bounce'],
            $userid, $bounceid));

        #Use the date of the bounce, instead of "now" as processing may be different
        Sql_Query(sprintf('INSERT INTO %s
            (
                        user,
                        message,
                        bounce,
                        time
            )
            VALUES
            (
                        %d,
                        -1,
                        %d,
                        "%s"
            )',
                $tables['user_message_bounce'],
                $userid, $bounceid, $bounceDate)
        );
        logEvent("$userid ".$GLOBALS['I18N']->get('system message bounced, user marked unconfirmed'));
        addUserHistory($useremail, $GLOBALS['I18N']->get('Bounced system message'), '
    <br/>' .$GLOBALS['I18N']->get('User marked unconfirmed')."
    <br/><a href=\"./?page=bounce&amp;id=$bounceid\">".$GLOBALS['I18N']->get('View Bounce').'</a>

    ');
        Sql_Query(sprintf('update %s
      set confirmed = 0
      where id = %d',
            $tables['user'],
            $userid));
    } elseif (!empty($msgid) && !empty($userid)) {
        //# check if we already have this um as a bounce
        //# so that we don't double count "delayed" like bounces
        $exists = Sql_Fetch_Row_Query(sprintf('select count(*) from %s where user = %d and message = %d',
            $tables['user_message_bounce'], $userid, $msgid));
        if (empty($exists[0])) {
            Sql_Query(sprintf('insert into %s
        set user = %d, message = %d, bounce = %d',
                $tables['user_message_bounce'],
                $userid, $msgid, $bounceid));
            Sql_Query(sprintf('update %s
        set status = "bounced list message %d",
        comment = "%s bouncecount increased"
        where id = %d',
                $tables['bounce'],
                $msgid,
                $userid, $bounceid));
            Sql_Query(sprintf('update %s
        set bouncecount = bouncecount + 1
        where id = %d',
                $tables['message'],
                $msgid));
            Sql_Query(sprintf('update %s
        set bouncecount = bouncecount + 1
        where id = %d',
                $tables['user'],
                $userid));
        } else {
            //# we create the relationship, but don't increase counters
            Sql_Query(sprintf('insert into %s
        set user = %d, message = %d, bounce = %d',
                $tables['user_message_bounce'],
                $userid, $msgid, $bounceid));

            //# we cannot translate this text
            Sql_Query(sprintf('update %s
        set status = "duplicate bounce for %d",
        comment = "duplicate bounce for subscriber %d on message %d"
        where id = %d',
                $tables['bounce'],
                $userid,
                $userid, $msgid, $bounceid));
        }
    } elseif ($userid) {
        Sql_Query(sprintf('update %s
      set status = "bounced unidentified message",
      comment = "%s bouncecount increased"
      where id = %d',
            $tables['bounce'],
            $userid, $bounceid));
        Sql_Query(sprintf('update %s
      set bouncecount = bouncecount + 1
      where id = %d',
            $tables['user'],
            $userid));
    } elseif ($msgid === 'systemmessage') {
        Sql_Query(sprintf('update %s
      set status = "bounced system message",
      comment = "unknown user"
      where id = %d',
            $tables['bounce'],
            $bounceid));
        logEvent("$userid ".$GLOBALS['I18N']->get('system message bounced, but unknown user'));
    } elseif ($msgid) {
        Sql_Query(sprintf('update %s
      set status = "bounced list message %d",
      comment = "unknown user"
      where id = %d',
            $tables['bounce'],
            $msgid,
            $bounceid));
        Sql_Query(sprintf('update %s
      set bouncecount = bouncecount + 1
      where id = %d',
            $tables['message'],
            $msgid));
    } else {
        Sql_Query(sprintf('update %s
      set status = "unidentified bounce",
      comment = "not processed"
      where id = %d',
            $tables['bounce'],
            $bounceid));

        return false;
    }

    return true;
}

function processPop($server, $user, $password)
{
    $port = $GLOBALS['bounce_mailbox_port'];
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
        outputProcessBounce($GLOBALS['I18N']->get('Cannot create POP3 connection to')." $server: ".imap_last_error());

        return false;
    }

    return processMessages($link, 100000);
}

function processMbox($file)
{
    set_time_limit(6000);

    if (!TEST) {
        $link = imap_open($file, '', '', CL_EXPUNGE);
    } else {
        $link = imap_open($file, '', '');
    }
    if (!$link) {
        outputProcessBounce($GLOBALS['I18N']->get('Cannot open mailbox file').' '.imap_last_error());

        return false;
    }

    return processMessages($link, 100000);
}

function processMessages($link, $max = 3000)
{
    global $bounce_mailbox_purge_unprocessed, $bounce_mailbox_purge;
    $num = imap_num_msg($link);
    outputProcessBounce(s('%d bounces to fetch from the mailbox', $num).PHP_EOL);

    if ($num == 0) {
        imap_close($link);

        return '';
    }
    outputProcessBounce($GLOBALS['I18N']->get('Please do not interrupt this process').PHP_EOL);
    $report = ' '.s('%d bounces to process', $num).PHP_EOL;
    if ($num > $max) {
        outputProcessBounce(s('Processing first %d bounces', $max).PHP_EOL);
        $report .= s('Processing first %d bounces', $max).PHP_EOL;
        $num = $max;
    }
    if (TEST) {
        echo s('Running in test mode, not deleting messages from mailbox').'<br/>';
    } else {
        echo s('Processed messages will be deleted from mailbox').'<br/>';
    }
    $nberror = 0;
//  for ($x=1;$x<150;$x++) {
    for ($x = 1; $x <= $num; ++$x) {
        set_time_limit(60);
        $header = imap_fetchheader($link, $x);
        if ($x % 25 == 0) {
            //    outputProcessBounce( $x . " ". nl2br($header));
            outputProcessBounce($x.' done', 1);
        }
        echo PHP_EOL;
        flush();
        $processed = processImapBounce($link, $x, $header);
        if ($processed) {
            if (!TEST && $bounce_mailbox_purge) {
                if (VERBOSE) {
                    outputProcessBounce($GLOBALS['I18N']->get('Deleting message')." $x");
                }
                imap_delete($link, $x);
            } elseif (VERBOSE) {
                outputProcessBounce(s('Not deleting processed message')." $x $bounce_mailbox_purge");
            }
        } else {
            if (!TEST && $bounce_mailbox_purge_unprocessed) {
                if (VERBOSE) {
                    outputProcessBounce($GLOBALS['I18N']->get('Deleting message')." $x");
                }
                imap_delete($link, $x);
            } elseif (VERBOSE) {
                outputProcessBounce(s('Not deleting unprocessed message')." $x");
            }
        }
        flush();
    }
    flush();
    outputProcessBounce(s('Closing mailbox, and purging messages'));
    set_time_limit(60 * $num);
    imap_close($link);

    return $report;
}

if (!function_exists('imap_open')) {
    Error($GLOBALS['I18N']->get('IMAP is not included in your PHP installation, cannot continue').
        '<br/>'.$GLOBALS['I18N']->get('Check out').
        ' <a href="http://www.php.net/manual/en/ref.imap.php">http://www.php.net/manual/en/ref.imap.php</a>');

    return;
}

if (empty($bounce_mailbox) && (empty($bounce_mailbox_host) || empty($bounce_mailbox_user) || empty($bounce_mailbox_password))) {
    Error($GLOBALS['I18N']->get('Bounce mechanism not properly configured'));

    return;
}

// lets not do this unless we do some locking first
register_shutdown_function('processbounces_shutdown');
$abort = ignore_user_abort(1);
if (!empty($GLOBALS['commandline']) && isset($cline['f'])) {
    // force set, so kill other processes
    cl_output(s('Force set, killing other send processes'));
    $process_id = getPageLock(1);
} else {
    $process_id = getPageLock();
}
if (empty($process_id)) {
    return;
}
echo prepareOutput();
flushBrowser();

$download_report = '';
switch ($bounce_protocol) {
    case 'pop':
        $download_report = processPop($bounce_mailbox_host, $bounce_mailbox_user, $bounce_mailbox_password);
        break;
    case 'mbox':
        $download_report = processMbox($bounce_mailbox);
        break;
    default:
        Error($GLOBALS['I18N']->get('bounce_protocol not supported'));

        return;
}

if ($GLOBALS['commandline'] && $download_report === false) {
    cl_output(s('Download failed, exiting'));

    return;
}
// now we have filled database with all available bounces

//# reprocess the unidentified ones, as the bounce detection has improved, so it might catch more

cl_output('reprocessing');
$reparsed = $count = 0;
$reidentified = 0;
$req = Sql_Query(sprintf('select * from %s where status = "unidentified bounce"', $tables['bounce']));
$total = Sql_Affected_Rows();
cl_output(s('%d bounces to reprocess', $total));
while ($bounce = Sql_Fetch_Assoc($req)) {
    ++$count;
    if ($count % 25 == 0) {
        cl_progress(s('%d out of %d processed', $count, $total));
    }
    $bounceBody = decodeBody($bounce['header'], $bounce['data']);
    $userid = findUserID($bounceBody);
    $messageid = findMessageId($bounceBody);
    if (!empty($userid) || !empty($messageid)) {
        ++$reparsed;
        if (processBounceData($bounce['id'], $messageid, $userid)) {
            ++$reidentified;
        }
    }
}
cl_output(s('%d out of %d processed', $count, $total));
if (VERBOSE) {
    outputProcessBounce(s('%d bounces were re-processed and %d bounces were re-identified', $reparsed, $reidentified));
}
$advanced_report = '';
$bouncerules = loadBounceRules();
if (count($bouncerules)) {
    outputProcessBounce($GLOBALS['I18N']->get('Processing bounces based on active bounce rules'));
    $matched = 0;
    $notmatched = 0;
    $limit = ' limit 10000';
    $limit = '';

    //# run this in batches. With many bounces this query runs OOM
    $bounceCount = Sql_Fetch_Row_Query(sprintf('select count(*) from %s', $GLOBALS['tables']['user_message_bounce']));
    $total = $bounceCount[0];
    $counter = 0;
    $batchSize = 500; //# @TODO make a config, to allow tweaking on bigger systems
    while ($counter < $total) {
        $limit = ' limit '.$counter.', '.$batchSize;
        $counter += $batchSize;
        cl_progress(s('processed %d out of %d bounces for advanced bounce rules', $counter, $total));

        $req = Sql_Query(sprintf('select * from %s as bounce, %s as umb where bounce.id = umb.bounce %s',
            $GLOBALS['tables']['bounce'], $GLOBALS['tables']['user_message_bounce'], $limit));
        while ($row = Sql_Fetch_Array($req)) {
            $alive = checkLock($process_id);
            if ($alive) {
                keepLock($process_id);
            } else {
                bounceProcessError($GLOBALS['I18N']->get('Process Killed by other process'));
            }
            //    cl_output(memory_get_usage());

            //    outputProcessBounce('User '.$row['user']);
            $rule = matchBounceRules($row['header']."\n\n".$row['data'], $bouncerules);
            //    outputProcessBounce('Action '.$rule['action']);
            //    outputProcessBounce('Rule'.$rule['id']);
            $userdata = array();
            if ($rule && is_array($rule)) {
                if ($row['user']) {
                    $userdata = Sql_Fetch_Array_Query("select * from {$tables['user']} where id = ".$row['user']);
                }
                $report_linkroot = $GLOBALS['admin_scheme'].'://'.$GLOBALS['website'].$GLOBALS['adminpages'];

                Sql_Query(sprintf('update %s set count = count + 1 where id = %d',
                    $GLOBALS['tables']['bounceregex'], $rule['id']));
                Sql_Query(sprintf('insert ignore into %s (regex,bounce) values(%d,%d)',
                    $GLOBALS['tables']['bounceregex_bounce'], $rule['id'], $row['bounce']));

                //17860 - check the current status to avoid doing it over and over
                $currentStatus = Sql_Fetch_Assoc_Query(sprintf('select confirmed,blacklisted from %s where id = %d', $GLOBALS['tables']['user'],$row['user']));
                $confirmed = !empty($currentStatus['confirmed']);
                $blacklisted = !empty($currentStatus['blacklisted']);

                switch ($rule['action']) {
                    case 'deleteuser':
                        logEvent('User '.$userdata['email'].' deleted by bounce rule '.PageLink2('bouncerule&amp;id='.$rule['id'],
                                $rule['id']));
                        $advanced_report .= 'User '.$userdata['email'].' deleted by bounce rule '.$rule['id'].PHP_EOL;
                        $advanced_report .= 'User: '.$report_linkroot.'/?page=user&amp;id='.$userdata['id'].PHP_EOL;
                        $advanced_report .= 'Rule: '.$report_linkroot.'/?page=bouncerule&amp;id='.$rule['id'].PHP_EOL;
                        deleteUser($row['user']);
                        break;
                    case 'unconfirmuser':
                        if ($confirmed) {
                            logEvent('User ' . $userdata['email'] . ' unconfirmed by bounce rule ' . PageLink2('bouncerule&amp;id=' . $rule['id'],
                                    $rule['id']));
                            Sql_Query(sprintf('update %s set confirmed = 0 where id = %d', $GLOBALS['tables']['user'],
                                $row['user']));
                            $advanced_report .= 'User ' . $userdata['email'] . ' made unconfirmed by bounce rule ' . $rule['id'] . PHP_EOL;
                            $advanced_report .= 'User: ' . $report_linkroot . '/?page=user&amp;id=' . $userdata['id'] . PHP_EOL;
                            $advanced_report .= 'Rule: ' . $report_linkroot . '/?page=bouncerule&amp;id=' . $rule['id'] . PHP_EOL;
                            addUserHistory($userdata['email'], s('Auto Unconfirmed'),
                                s('Subscriber auto unconfirmed for') . ' ' . s('bounce rule') . ' ' . $rule['id']);
                            addSubscriberStatistics('auto unsubscribe', 1);
                        }
                        break;
                    case 'deleteuserandbounce':
                        logEvent('User '.$userdata['email'].' deleted by bounce rule '.PageLink2('bouncerule&amp;id='.$rule['id'],
                                $rule['id']));
                        $advanced_report .= 'User '.$userdata['email'].' deleted by bounce rule '.$rule['id'].PHP_EOL;
                        $advanced_report .= 'User: '.$report_linkroot.'/?page=user&amp;id='.$userdata['id'].PHP_EOL;
                        $advanced_report .= 'Rule: '.$report_linkroot.'/?page=bouncerule&amp;id='.$rule['id'].PHP_EOL;
                        deleteUser($row['user']);
                        deleteBounce($row['bounce']);
                        break;
                    case 'unconfirmuseranddeletebounce':
                        if ($confirmed) {
                            logEvent('User ' . $userdata['email'] . ' unconfirmed by bounce rule ' . PageLink2('bouncerule&amp;id=' . $rule['id'],
                                    $rule['id']));
                            Sql_Query(sprintf('update %s set confirmed = 0 where id = %d', $GLOBALS['tables']['user'],
                                $row['user']));
                            $advanced_report .= 'User ' . $userdata['email'] . ' made unconfirmed by bounce rule ' . $rule['id'] . PHP_EOL;
                            $advanced_report .= 'User: ' . $report_linkroot . '/?page=user&amp;id=' . $userdata['id'] . PHP_EOL;
                            $advanced_report .= 'Rule: ' . $report_linkroot . '/?page=bouncerule&amp;id=' . $rule['id'] . PHP_EOL;
                            addUserHistory($userdata['email'], s('Auto unconfirmed'),
                                s('Subscriber auto unconfirmed for') . ' ' . $GLOBALS['I18N']->get('bounce rule') . ' ' . $rule['id']);
                            addSubscriberStatistics('auto unsubscribe', 1);
                        }
                        deleteBounce($row['bounce']);
                        break;
                    case 'blacklistuser':
                        if (!$blacklisted) {
                            logEvent('User ' . $userdata['email'] . ' blacklisted by bounce rule ' . PageLink2('bouncerule&amp;id=' . $rule['id'],
                                    $rule['id']));
                            addUserToBlacklist($userdata['email'],
                                s('Subscriber auto blacklisted  by bounce rule', $rule['id']));
                            $advanced_report .= 'User ' . $userdata['email'] . ' blacklisted by bounce rule ' . $rule['id'] . PHP_EOL;
                            $advanced_report .= 'User: ' . $report_linkroot . '/?page=user&amp;id=' . $userdata['id'] . PHP_EOL;
                            $advanced_report .= 'Rule: ' . $report_linkroot . '/?page=bouncerule&amp;id=' . $rule['id'] . PHP_EOL;
                            addUserHistory($userdata['email'], $GLOBALS['I18N']->get('Auto Unsubscribed'),
                                $GLOBALS['I18N']->get('User auto unsubscribed for') . ' ' . $GLOBALS['I18N']->get('bounce rule') . ' ' . $rule['id']);
                            addSubscriberStatistics('auto blacklist', 1);
                        }
                        break;
                    case 'blacklistuseranddeletebounce':
                        if (!$blacklisted) {
                            logEvent('User ' . $userdata['email'] . ' blacklisted by bounce rule ' . PageLink2('bouncerule&amp;id=' . $rule['id'],
                                    $rule['id']));
                            addUserToBlacklist($userdata['email'],
                                s('Subscriber auto blacklisted by bounce rule %d', $rule['id']));
                            $advanced_report .= 'User ' . $userdata['email'] . ' blacklisted by bounce rule ' . $rule['id'] . PHP_EOL;
                            $advanced_report .= 'User: ' . $report_linkroot . '/?page=user&amp;id=' . $userdata['id'] . PHP_EOL;
                            $advanced_report .= 'Rule: ' . $report_linkroot . '/?page=bouncerule&amp;id=' . $rule['id'] . PHP_EOL;
                            addUserHistory($userdata['email'], $GLOBALS['I18N']->get('Auto Unsubscribed'),
                                $GLOBALS['I18N']->get('User auto unsubscribed for') . ' ' . $GLOBALS['I18N']->get('bounce rule') . ' ' . $rule['id']);
                            addSubscriberStatistics('auto blacklist', 1);
                        }
                        deleteBounce($row['bounce']);
                        break;
                    case 'blacklistemail':
                        logEvent('email '.$userdata['email'].' blacklisted by bounce rule '.PageLink2('bouncerule&amp;id='.$rule['id'],
                                $rule['id']));
                        addEmailToBlackList($userdata['email'],
                            s('Email address auto blacklisted by bounce rule %d', $rule['id']));
                        $advanced_report .= 'email '.$userdata['email'].' blacklisted by bounce rule '.$rule['id'].PHP_EOL;
                        $advanced_report .= 'User: '.$report_linkroot.'/?page=user&amp;id='.$userdata['id'].PHP_EOL;
                        $advanced_report .= 'Rule: '.$report_linkroot.'/?page=bouncerule&amp;id='.$rule['id'].PHP_EOL;
                        addUserHistory($userdata['email'], $GLOBALS['I18N']->get('Auto Unsubscribed'),
                            $GLOBALS['I18N']->get('email auto unsubscribed for').' '.$GLOBALS['I18N']->get('bounce rule').' '.$rule['id']);
                        addSubscriberStatistics('auto blacklist', 1);
                        break;
                    case 'blacklistemailanddeletebounce':
                        logEvent('email '.$userdata['email'].' blacklisted by bounce rule '.PageLink2('bouncerule&amp;id='.$rule['id'],
                                $rule['id']));
                        addEmailToBlackList($userdata['email'],
                            s('Email address auto blacklisted by bounce rule %d', $rule['id']));
                        $advanced_report .= 'email '.$userdata['email'].' blacklisted by bounce rule '.$rule['id'].PHP_EOL;
                        $advanced_report .= 'User: '.$report_linkroot.'/?page=user&amp;id='.$userdata['id'].PHP_EOL;
                        $advanced_report .= 'Rule: '.$report_linkroot.'/?page=bouncerule&amp;id='.$rule['id'].PHP_EOL;
                        addUserHistory($userdata['email'], $GLOBALS['I18N']->get('Auto Unsubscribed'),
                            $GLOBALS['I18N']->get('User auto unsubscribed for').' '.$GLOBALS['I18N']->get('bounce rule').' '.$rule['id']);
                        addSubscriberStatistics('auto blacklist', 1);
                        deleteBounce($row['bounce']);
                        break;
                    case 'deletebounce':
                        deleteBounce($row['bounce']);
            if (REPORT_DELETED_BOUNCES == 1) {
                $advanced_report .= 'Deleted bounce ' . $userdata['email'] . ' --> Bounce deleted by bounce rule ' . $rule['id'] . PHP_EOL;
            }
                        break;
                }

                ++$matched;
            } else {
                ++$notmatched;
            }
        }
    }
    outputProcessBounce($matched.' '.$GLOBALS['I18N']->get('bounces processed by advanced processing'));
    outputProcessBounce($notmatched.' '.$GLOBALS['I18N']->get('bounces were not matched by advanced processing rules'));
}

// have a look who should be flagged as unconfirmed
outputProcessBounce($GLOBALS['I18N']->get('Identifying consecutive bounces'));

// we only need users who are confirmed at the moment
$userid_req = Sql_query(sprintf('select distinct umb.user from %s umb, %s u
  where u.id = umb.user and u.confirmed and !u.blacklisted',
    $tables['user_message_bounce'],
    $tables['user']
));
$total = Sql_Affected_Rows();
if (!$total) {
    outputProcessBounce($GLOBALS['I18N']->get('Nothing to do'));
}

$usercnt = 0;
$unsubscribed_users = '';
while ($user = Sql_Fetch_Row($userid_req)) {
    keepLock($process_id);
    set_time_limit(600);
    //$msg_req = Sql_Query(sprintf('select * from
    //%s um left join %s umb on (um.messageid = umb.message and userid = user)
    //where userid = %d and um.status = "sent"
    //order by entered desc',
    //$tables["usermessage"],$tables["user_message_bounce"],
    //$user[0]));

    //# 17361 - update of the above query, to include the bounce table and to exclude duplicate bounces
    $msg_req = Sql_Query(sprintf('select umb.*,um.*,b.status,b.comment from %s um left join %s umb on (um.messageid = umb.message and userid = user)
    left join %s b on umb.bounce = b.id
    where userid = %d and um.status = "sent"
    order by entered desc',
        $tables['usermessage'], $tables['user_message_bounce'], $tables['bounce'],
        $user[0]));

    /*  $cnt = 0;
      $alive = 1;$removed = 0;
      while ($alive && !$removed && $bounce = Sql_Fetch_Array($msg_req)) {
        $alive = checkLock($process_id);
        if ($alive)
          keepLock($process_id);
        else
          bounceProcessError($GLOBALS['I18N']->get("Process Killed by other process"));
        if (sprintf('%d',$bounce["bounce"]) == $bounce["bounce"]) {
          $cnt++;
          if ($cnt >= $bounce_unsubscribe_threshold) {
            $removed = 1;
            outputProcessBounce(sprintf('unsubscribing %d -> %d bounces',$user[0],$cnt));
            $userurl = PageLink2("user&amp;id=$user[0]",$user[0]);
            logEvent($GLOBALS['I18N']->get("User")." $userurl ".$GLOBALS['I18N']->get("has consecutive bounces")." ($cnt) ".$GLOBALS['I18N']->get("over threshold, user marked unconfirmed"));
            $emailreq = Sql_Fetch_Row_Query("select email from {$tables["user"]} where id = $user[0]");
            addUserHistory($emailreq[0],$GLOBALS['I18N']->get("Auto Unsubscribed"),$GLOBALS['I18N']->get("User auto unsubscribed for")." $cnt ".$GLOBALS['I18N']->get("consecutive bounces"));
            Sql_Query(sprintf('update %s set confirmed = 0 where id = %d',$tables["user"],$user[0]));
            addSubscriberStatistics('auto unsubscribe',1);
            $email_req = Sql_Fetch_Row_Query(sprintf('select email from %s where id = %d',$tables["user"],$user[0]));
            $unsubscribed_users .= $email_req[0] . " [$user[0]] ($cnt)\n";
          }
        } elseif ($bounce["bounce"] == "") {
          $cnt = 0;
        }
      }*/
    //$alive = 1;$removed = 0; DT 051105
    $cnt = 0;
    $alive = 1;
    $removed = $msgokay = $unconfirmed = $unsubscribed = 0;
    //while ($alive && !$removed && $bounce = Sql_Fetch_Array($msg_req)) { DT 051105
    while ($alive && !$removed && !$msgokay && $bounce = Sql_Fetch_Array($msg_req)) {
        $alive = checkLock($process_id);
        if ($alive) {
            keepLock($process_id);
        } else {
            bounceProcessError('Process Killed by other process');
        }

        if (stripos($bounce['status'], 'duplicate') === false && stripos($bounce['comment'], 'duplicate') === false) {
            if (sprintf('%d', $bounce['bounce']) == $bounce['bounce']) {
                ++$cnt;
                if ($cnt >= $bounce_unsubscribe_threshold) {
                    if (!$unsubscribed) {
                        outputProcessBounce(sprintf('unsubscribing %d -> %d bounces', $user[0], $cnt));
                        $userurl = PageLink2("user&amp;id=$user[0]", $user[0]);
                        logEvent(s('User (url:%s) has consecutive bounces (%d) over threshold (%d), user marked unconfirmed',
                            $userurl, $cnt, $bounce_unsubscribe_threshold));
                        $emailreq = Sql_Fetch_Row_Query("select email from {$tables['user']} where id = $user[0]");
                        addUserHistory($emailreq[0], s('Auto Unconfirmed'),
                            s('Subscriber auto unconfirmed for %d consecutive bounces', $cnt));
                        Sql_Query(sprintf('update %s set confirmed = 0 where id = %d', $tables['user'], $user[0]));
                        $email_req = Sql_Fetch_Row_Query(sprintf('select email from %s where id = %d', $tables['user'],
                            $user[0]));
                        $unsubscribed_users .= $email_req[0]."\t\t($cnt)\t\t".$GLOBALS['scheme'].'://'.getConfig('website').$GLOBALS['adminpages'].'/?page=user&amp;id='.$user[0].PHP_EOL;
                        $unsubscribed = 1;
                    }
                    if (BLACKLIST_EMAIL_ON_BOUNCE && $cnt >= BLACKLIST_EMAIL_ON_BOUNCE) {
                        $removed = 1;
                        //0012262: blacklist email when email bounces
                        cl_output(s('%d consecutive bounces, threshold reached, blacklisting subscriber', $cnt));
                        addEmailToBlackList($emailreq[0], s('%d consecutive bounces, threshold reached', $cnt));
                    }
                }
            } elseif ($bounce['bounce'] == '') {
                //$cnt = 0; DT 051105
                $cnt = 0;
                $msgokay = 1; //DT 051105 - escaping loop if message received okay
            }
        }
    }
    if ($usercnt % 5 == 0) {
        //    outputProcessBounce($GLOBALS['I18N']->get("Identifying consecutive bounces"));
        cl_progress(s('processed %d out of %d subscribers', $usercnt, $total), 1);
    }
    ++$usercnt;
    flush();
}

//outputProcessBounce($GLOBALS['I18N']->get("Identifying consecutive bounces"));
outputProcessBounce(PHP_EOL.s('total of %d subscribers processed', $total).'                            ');

$report = '';

if ($advanced_report) {
    $report .= $GLOBALS['I18N']->get('Report of advanced bounce processing:')."\n$advanced_report\n";
}
if ($unsubscribed_users) {
    $report .= PHP_EOL.$GLOBALS['I18N']->get('Below are users who have been marked unconfirmed. The number in () is the number of consecutive bounces.').PHP_EOL;
    $report .= "\n$unsubscribed_users";
}
if ($report) {
    $report = $GLOBALS['I18N']->get('Report:')."\n$download_report\n".$report;
}
// shutdown will take care of reporting
//finish("info",$report);

// IMAP errors following when Notices are on are a PHP bug
# http://bugs.php.net/bug.php?id=7207
