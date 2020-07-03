<?php

require_once dirname(__FILE__).'/accesscheck.php';
// library used for plugging into the webbler, instead of "connect"
// depricated and should be removed
include_once dirname(__FILE__).'/class.phplistmailer.php';

$domain = getConfig('domain');
$website = getConfig('website');

if (!$GLOBALS['message_envelope']) {
    // why not try set it to "person in charge of this system". Will help get rid of a lot of bounces to nobody@server :-)
    $admin = getConfig('admin_address');
    if (!empty($admin)) {
        $GLOBALS['message_envelope'] = $admin;
    }
}

if (defined('IN_WEBBLER') && is_object($GLOBALS['config']['plugins']['phplist'])) {
    $GLOBALS['tables'] = $GLOBALS['config']['plugins']['phplist']->tables;
}

/* this should probably move to init.php */
$GLOBALS['bounceruleactions'] = array(
    'deleteuser'                    => $GLOBALS['I18N']->get('delete subscriber'),
    'unconfirmuser'                 => $GLOBALS['I18N']->get('unconfirm subscriber'),
    'blacklistuser'                 => $GLOBALS['I18N']->get('blacklist subscriber'),
    'blacklistemail'                => $GLOBALS['I18N']->get('blacklist email address'),
    'deleteuserandbounce'           => $GLOBALS['I18N']->get('delete subscriber and bounce'),
    'unconfirmuseranddeletebounce'  => $GLOBALS['I18N']->get('unconfirm subscriber and delete bounce'),
    'blacklistuseranddeletebounce'  => $GLOBALS['I18N']->get('blacklist subscriber and delete bounce'),
    'blacklistemailanddeletebounce' => $GLOBALS['I18N']->get('blacklist email address and delete bounce'),
    'deletebounce'                  => $GLOBALS['I18N']->get('delete bounce'),
);

if (!isset($GLOBALS['developer_email'])) {
    ini_set('error_append_string', 'phpList version '.VERSION);
    ini_set('error_prepend_string', '<p class="error">Sorry a software error occurred:<br/>
    Please <a href="http://mantis.phplist.com">report a bug</a> when reporting the bug, please include URL and the entire content of this page.<br/>');
}

function cleanListName($name) { ## we allow certain tags in a listname
    $name = strip_tags($name,'<br><p><h1><h2><h3><b><i>');
    $allowedAttr = array('title','class','style');

    // we need to clean attributes as well.

    preg_match_all('~<(/?)([\w]+)([^>]*)/?>~',$name,$regs);

    for ($i=0; $i< sizeof($regs[0]); $i++ ) {
        $clean = '';
        #      print $i . ' '.htmlspecialchars($regs[0][$i]).'<h2>'.$regs[1][$i].'</h2><b>'.strlen($regs[3][$i]).'</b><br/> ';
        if ($regs[1][$i] == '/') {
            $clean .= '</'.$regs[2][$i].'>';
        } elseif (!strlen($regs[3][$i]) || $regs[3][$i] == '/') {
            $clean .= '<'.$regs[2][$i].$regs[3][$i].'>';
        } else {
            $attributes = $regs[3][$i];
            preg_match_all('/([\w]+)="([^\"]+)"/',$attributes,$attrregs); // only allow properly formatted, without spacing
            $newattributes = '';
            for ($j=0; $j< sizeof($attrregs[0]); $j++ ) {
                if (in_array($attrregs[1][$j],$allowedAttr)) {
                    $newattributes .= $attrregs[1][$j].'="'.$attrregs[2][$j].'" ';
                }
            }
            $clean .= '<'.$regs[2][$i].' '.trim($newattributes).'>';
        }
        $name = str_ireplace($regs[0][$i],$clean,$name);
    }
    return $name;
}

function listName($id)
{
    global $tables;
    $req = Sql_Fetch_Row_Query(sprintf('select name from %s where id = %d', $tables['list'], $id));

    return $req[0] ? stripslashes(cleanListName($req[0])) : $GLOBALS['I18N']->get('Unnamed List');
}

function setMessageData($msgid, $name, $value)
{
    if ($name == 'PHPSESSID') {
        return;
    }
    if ($name == session_name()) {
        return;
    }

    if ($name == 'subject' || $name == 'campaigntitle') {
        //# disallow html in the subject and title
        $value = strip_tags($value);
    }

    if ($name == 'targetlist' && is_array($value)) {
        Sql_query(sprintf('delete from %s where messageid = %d', $GLOBALS['tables']['listmessage'], $msgid));
        if (!empty($value['all']) || !empty($value['allactive'])) {
            $res = Sql_query('select * from '.$GLOBALS['tables']['list'].' '.$GLOBALS['subselect']);
            while ($row = Sql_Fetch_Array($res)) {
                $listid = $row['id'];
                if ($row['active'] || !empty($value['all'])) {
                    $result = Sql_query('insert ignore into '.$GLOBALS['tables']['listmessage']."  (messageid,listid,entered) values($msgid,$listid,now())");
                }
            }
            //# once we used "all" to set all, unset it, to avoid confusion trying to unselect lists
            unset($value['all']);
        } else {
            foreach ($value as $listid => $val) {
                if ($listid != 'unselect') { //# see #16940 - ignore a list called "unselect" which is there to allow unselecting all
                    $query = sprintf(' insert into '.$GLOBALS['tables']['listmessage'].' (messageid,listid,entered) values(%d, %d, now())',
                        $msgid, $listid);
                    $result = Sql_Query($query);
                }
            }
        }
    }
    if ($name == 'excludelist' && is_array($value)) {
        ## make sure all entries are numerical. @@TO(DO We could also check that they are actually valid list IDs
        $newLists = array();
        foreach ($value as $v) {
            if (is_numeric($v)) {
                $newLists[$v] = $v;
            }
        }
        $value = $newLists;
        unset($newLists);
    }
    if (is_array($value) || is_object($value)) {
        $value = 'SER:'.serialize($value);
    }
    if ($name == 'footer') {
        // strip HTML comments
        $value = preg_replace('/<!--.*-->/', '', $value); // this is ungreedy
    }

    Sql_Query(sprintf('replace into %s set id = %d,name = "%s", data = "%s"',
        $GLOBALS['tables']['messagedata'], $msgid, addslashes($name), addslashes($value)));
//  print "setting $name for $msgid to $value";
//  exit;
}

function loadMessageData($msgid)
{
    $default = array(
        'from' => getConfig('message_from_address'),
        //# can add some more from below
        'google_track' => getConfig('always_add_googletracking'),
    );
    if (empty($default['from'])) {
        $default['from'] = getConfig('admin_address');
    }

    if (!isset($GLOBALS['MD']) || !is_array($GLOBALS['MD'])) {
        $GLOBALS['MD'] = array();
    }
    if (isset($GLOBALS['MD'][$msgid])) {
        return $GLOBALS['MD'][$msgid];
    }

    //# when loading an old message that hasn't got data stored in message data, load it from the message table
    $prevMsgData = Sql_Fetch_Assoc_Query(sprintf('select * from %s where id = %d',
        $GLOBALS['tables']['message'], $msgid));

    $finishSending = time() + DEFAULT_MESSAGEAGE;

    $messagedata = array(
        'template'       => getConfig('defaultmessagetemplate'),
        'sendformat'     => 'HTML',
        'message'        => '',
        'forwardmessage' => '',
        'textmessage'    => '',
        'rsstemplate'    => '',
        'embargo'        => array(
            'year'   => date('Y'),
            'month'  => date('m'),
            'day'    => date('d'),
            'hour'   => date('H'),
            'minute' => date('i'),
        ),
        'repeatinterval' => 0,
        'repeatuntil'    => array(
            'year'   => date('Y'),
            'month'  => date('m'),
            'day'    => date('d'),
            'hour'   => date('H'),
            'minute' => date('i'),
        ),
        'requeueinterval' => 0,
        'requeueuntil'    => array(
            'year'   => date('Y'),
            'month'  => date('m'),
            'day'    => date('d'),
            'hour'   => date('H'),
            'minute' => date('i'),
        ),
        'finishsending' => array(
            'year'   => date('Y', $finishSending),
            'month'  => date('m', $finishSending),
            'day'    => date('d', $finishSending),
            'hour'   => date('H', $finishSending),
            'minute' => date('i', $finishSending),
        ),
        'fromfield'      => '',
        'subject'        => '',
        'forwardsubject' => '',
        'footer'         => getConfig('messagefooter'),
        'forwardfooter'  => getConfig('forwardfooter'),
        'status'         => '',
        'tofield'        => '',
        'replyto'        => '',
        'targetlist'     => array(),
        'criteria_match' => '',
        'sendurl'        => '',
        'sendmethod'     => 'inputhere', //# make a config
        'testtarget'     => '',
        'notify_start'   => getConfig('notifystart_default'),
        'notify_end'     => getConfig('notifyend_default'),
        'google_track'   => $default['google_track'] == 'true' || $default['google_track'] === true || $default['google_track'] == '1',
        'excludelist'    => array(),
        'sentastest'     => 0,
    );
    if (is_array($prevMsgData)) {
        foreach ($prevMsgData as $key => $val) {
            $messagedata[$key] = $val;
        }
    }

    if (!empty($GLOBALS['commandline']) && $_GET['page'] == 'send') {
        $messagedata['fromfield'] = $_POST['from'];
        $messagedata['subject'] = $_POST['subject'];
        $messagedata['message'] = $_POST['message'];
        $messagedata['targetlist'] = $_POST['targetlist'];
    }
    if ($messagedata['subject'] == '(no title)') {
        $messagedata['subject'] = '(no subject)';
    }

    $msgdata_req = Sql_Query(sprintf('select * from %s where id = %d',
        $GLOBALS['tables']['messagedata'], $msgid));
    while ($row = Sql_Fetch_Assoc($msgdata_req)) {
        if (strpos($row['data'], 'SER:') === 0) {
            $data = stripSlashesArray(unserialize(substr($row['data'], 4)));
        } else {
            $data = stripslashes($row['data']);
        }
        if (!in_array($row['name'],
            array('astext', 'ashtml', 'astextandhtml', 'aspdf', 'astextandpdf'))
        ) { //# don't overwrite counters in the message table from the data table
            $messagedata[stripslashes($row['name'])] = $data;
        }
    }

    foreach (array('embargo', 'repeatuntil', 'requeueuntil') as $datefield) {
        if (!is_array($messagedata[$datefield])) {
            $messagedata[$datefield] = array(
                'year'   => date('Y'),
                'month'  => date('m'),
                'day'    => date('d'),
                'hour'   => date('H'),
                'minute' => date('i'),
            );
        }
    }

    // Load lists that were targetted with message...
    $result = Sql_Query(sprintf('select list.name,list.id
    from ' .$GLOBALS['tables']['listmessage'].' listmessage,'.$GLOBALS['tables']['list'].' list
     where listmessage.messageid = %d and listmessage.listid = list.id', $msgid));
    while ($lst = Sql_fetch_array($result)) {
        $messagedata['targetlist'][$lst['id']] = 1;
    }

    //# backwards, check that the content has a url and use it to fill the sendurl
    if (empty($messagedata['sendurl'])) {

        //# can't do "ungreedy matching, in case the URL has placeholders, but this can potentially
        //# throw problems
        if (preg_match('/\[URL:(.*)\]/i', $messagedata['message'], $regs)) {
            $messagedata['sendurl'] = $regs[1];
        }
    }
    if (empty($messagedata['sendurl']) && !empty($messagedata['message'])) {
        // if there's a message and no url, make sure to show the editor, and not the URL input
        $messagedata['sendmethod'] = 'inputhere';
    }

    //## parse the from field into it's components - email and name
    if (preg_match('/([^ ]+@[^ ]+)/', $messagedata['fromfield'], $regs)) {
        // if there is an email in the from, rewrite it as "name <email>"
        $messagedata['fromname'] = str_replace($regs[0], '', $messagedata['fromfield']);
        $messagedata['fromemail'] = $regs[0];
        // if the email has < and > take them out here
        $messagedata['fromemail'] = str_replace('<', '', $messagedata['fromemail']);
        $messagedata['fromemail'] = str_replace('>', '', $messagedata['fromemail']);
        // make sure there are no quotes around the name
        $messagedata['fromname'] = str_replace('"', '', ltrim(rtrim($messagedata['fromname'])));
    } elseif (strpos($messagedata['fromfield'], ' ')) {
        // if there is a space, we need to add the email
        $messagedata['fromname'] = $messagedata['fromfield'];
        //  $cached[$messageid]["fromemail"] = "listmaster@$domain";
        $messagedata['fromemail'] = $default['from'];
    } else {
        $messagedata['fromemail'] = $default['from'];
        $messagedata['fromname'] = $messagedata['fromfield'];
    }
    // disallow an email address in the name
    if (preg_match('/([^ ]+@[^ ]+)/', $messagedata['fromname'], $regs)) {
        $messagedata['fromname'] = str_replace($regs[0], '', $messagedata['fromname']);
    }
    // clean up
    $messagedata['fromemail'] = str_replace(',', '', $messagedata['fromemail']);
    $messagedata['fromname'] = str_replace(',', '', $messagedata['fromname']);

    $messagedata['fromname'] = trim($messagedata['fromname']);

    // erase double spacing
    while (strpos($messagedata['fromname'], '  ')) {
        $messagedata['fromname'] = str_replace('  ', ' ', $messagedata['fromname']);
    }

    //# if the name ends up being empty, copy the email
    if (empty($messagedata['fromname'])) {
        $messagedata['fromname'] = $messagedata['fromemail'];
    }

    if (isset($messagedata['targetlist']['unselect'])) {
        unset($messagedata['targetlist']['unselect']);
    }
    if (isset($messagedata['excludelist']['unselect'])) {
        unset($messagedata['excludelist']['unselect']);
    }

    if (empty($messagedata['campaigntitle'])) {
        if ($messagedata['subject'] != '(no subject)') {
            $messagedata['campaigntitle'] = $messagedata['subject'];
        } else {
            $messagedata['campaigntitle'] = '(no title)';
        }
    }
    //# copy subject to title
    if ($messagedata['campaigntitle'] == '(no title)' && $messagedata['subject'] != '(no subject)') {
        $messagedata['campaigntitle'] = $messagedata['subject'];
    }
    $GLOBALS['MD'][$msgid] = $messagedata;
//  var_dump($messagedata);
    return $messagedata;
}

function campaignTitle($id)
{
    $campaignTitle = Sql_Fetch_Assoc_Query(sprintf('select data as title from %s where name = "subject" and id = %d',
        $GLOBALS['tables']['messagedata'], $id));
    if (empty($campaignTitle['title'])) {
        $campaignTitle = Sql_Fetch_Assoc_Query(sprintf('select subject as title from %s where id = %d',
            $GLOBALS['tables']['message'], $id));
    }
    if (empty($campaignTitle['title'])) {
        $campaignTitle['title'] = $id;
    }

    return stripslashes($campaignTitle['title']);
}

//Send an email with a password reset token to the specified adminId.
function sendAdminPasswordToken($adminId)
{
    //Invalidate extisting password reset tokens for this user
    Sql_Query(sprintf("delete from %s where admin = '%s'", $GLOBALS['tables']['admin_password_request'], $adminId));

    //Create random token
    // @TODO alter database schema to allow longer tokens, currently 16 characters / 128 bits
    $key = bin2hex(random_bytes(16));

    // sql to insert token into the database, token can be used to change the password once executed.
    $insertToken = sprintf("insert into %s(date, admin, key_value) values (now(), %d, '%s');",
        $GLOBALS['tables']['admin_password_request'], $adminId, $key);

    //Retrieve the admin login name and email address
    $SQLquery = sprintf('select loginname,email from %s where id=%d;', $GLOBALS['tables']['admin'], $adminId);
    $row = Sql_Fetch_Row_Query($SQLquery);
    $adminName = $row[0];
    $email = $row[1];

    $urlroot = getConfig('website').$GLOBALS['adminpages'];
    //Build the email body to be sent, and finally send it.
    $emailBody = $GLOBALS['I18N']->get('Hello').' '.$adminName."\n\n";
    $emailBody .= $GLOBALS['I18N']->get('You have requested a new password for phpList.')."\n\n";
    $emailBody .= $GLOBALS['I18N']->get('To enter a new one, please visit the following link:')."\n\n";
    $emailBody .= sprintf('%s://%s/?page=login&token=%s', $GLOBALS['admin_scheme'], $urlroot, $key)."\n\n";
    $emailBody .= $GLOBALS['I18N']->get('You have 24 hours left to change your password. After that, your token won\'t be valid.');

    if (sendMail($email, $GLOBALS['I18N']->get('New password'), "\n\n".$emailBody, '', '', true)) {
        //Insert the token into the database
        Sql_Query($insertToken);

        return $GLOBALS['I18N']->get('A password change token has been sent to the corresponding email address.');
    } else {
        return $GLOBALS['I18N']->get('Error sending password change token');
    }
}

function getTopSmtpServer($domain)
{
    $mx = getmxrr($domain, $mxhosts, $weight);
    $thgiew = array_flip($weight);
    ksort($thgiew);

    return $mxhosts[array_shift($thgiew)];
}

function sendMail($to, $subject, $message, $header = '', $parameters = '', $skipblacklistcheck = 0)
{
    if (defined('TEST') && TEST) {
        return 1;
    }

    // do a quick check on mail injection attempt, @@@ needs more work
    if (preg_match("/\n/", $to)) {
        logEvent('Error: invalid recipient, containing newlines, email blocked');

        return 0;
    }
    if (preg_match("/\n/", $subject)) {
        logEvent('Error: invalid subject, containing newlines, email blocked');

        return 0;
    }

    if (!$to) {
        logEvent("Error: empty To: in message with subject $subject to send");

        return 0;
    } elseif (!$subject) {
        logEvent("Error: empty Subject: in message to send to $to");

        return 0;
    }
    if (!$skipblacklistcheck && isBlackListed($to)) {
        logEvent("Error, $to is blacklisted, not sending");
        Sql_Query(sprintf('update %s set blacklisted = 1 where email = "%s"', $GLOBALS['tables']['user'], $to));
        addUserHistory($to, 'Marked Blacklisted',
            'Found user in blacklist while trying to send an email, marked black listed');

        return 0;
    }

    return sendMailPhpMailer($to, $subject, $message);
}

function constructSystemMail($message, $subject = '')
{
    $hasHTML = strip_tags($message) != $message;
    $htmlcontent = '';

    if ($hasHTML) {
        $message = stripslashes($message);
        $textmessage = HTML2Text($message);
        $htmlmessage = $message;
    } else {
        $textmessage = $message;
        $htmlmessage = $message;
        //  $htmlmessage = str_replace("\n\n","\n",$htmlmessage);
        $htmlmessage = nl2br($htmlmessage);
        //# make links clickable:
        $htmlmessage = preg_replace('~https?://[^\s<]+~i', '<a href="$0">$0</a>', $htmlmessage);
    }
    //# add li-s around the lists
    if (preg_match('/<ul>\s+(\*.*)<\/ul>/imsxU', $htmlmessage, $listsmatch)) {
        $lists = $listsmatch[1];
        $listsHTML = '';
        preg_match_all('/\*([^\*]+)/', $lists, $matches);
        for ($i = 0; $i < count($matches[0]); ++$i) {
            $listsHTML .= '<li>'.$matches[1][$i].'</li>';
        }
        $htmlmessage = str_replace($listsmatch[0], '<ul>'.$listsHTML.'</ul>', $htmlmessage);
    }

    $htmltemplate = '';
    $templateid = getConfig('systemmessagetemplate');
    if (!empty($templateid)) {
        $req = Sql_Fetch_Row_Query(sprintf('select template from %s where id = %d',
            $GLOBALS['tables']['template'], $templateid));
        $htmltemplate = stripslashes($req[0]);
    }
    if (strpos($htmltemplate, '[CONTENT]')) {
        $htmlcontent = str_replace('[CONTENT]', $htmlmessage, $htmltemplate);
        $htmlcontent = str_replace('[SUBJECT]', $subject, $htmlcontent);
        $htmlcontent = str_replace('[FOOTER]', '', $htmlcontent);
        if (!EMAILTEXTCREDITS) {
            $phpListPowered = preg_replace('/src=".*power-phplist.png"/', 'src="powerphplist.png"',
                $GLOBALS['PoweredByImage']);
        } else {
            $phpListPowered = $GLOBALS['PoweredByText'];
        }
        if (strpos($htmlcontent, '[SIGNATURE]')) {
            $htmlcontent = str_replace('[SIGNATURE]', $phpListPowered, $htmlcontent);
        } elseif (strpos($htmlcontent, '</body>')) {
            $htmlcontent = str_replace('</body>', $phpListPowered.'</body>', $htmlcontent);
        } else {
            $htmlcontent .= $phpListPowered;
        }
        $htmlcontent = parseLogoPlaceholders($htmlcontent);
    }

    return array($htmlcontent, $textmessage);
}

function sendMailPhpMailer($to, $subject, $message)
{
    // global function to capture sending emails, to avoid trouble with
    // older (and newer!) php versions
    $fromemail = getConfig('message_from_address');
    $fromname = getConfig('message_from_name');
    $message_replyto_address = getConfig('message_replyto_address');
    if ($message_replyto_address) {
        $reply_to = $message_replyto_address;
    } else {
        $reply_to = $from_address;
    }
    $destinationemail = '';

//  print "Sending $to from $fromemail<br/>";
    if (defined('DEVVERSION') && DEVVERSION) {
        $message = "To: $to\n".$message;
        if ($GLOBALS['developer_email']) {
            $destinationemail = $GLOBALS['developer_email'];
        } else {
            //  print 'Error: Running DEV version, but developer_email not set';
        }
    } else {
        $destinationemail = $to;
    }
    list($htmlmessage, $textmessage) = constructSystemMail($message, $subject);

    $mail = new PHPlistMailer('systemmessage', $destinationemail, false);
    if (!empty($htmlmessage)) {
        $mail->add_html($htmlmessage, $textmessage, getConfig('systemmessagetemplate'));
        //# In the above phpMailer strips all tags, which removes the links which are wrapped in < and > by HTML2text
        //# so add it again
        $mail->add_text($textmessage);
    }
    $mail->add_text($textmessage);
    // 0008549: message envelope not passed to php mailer,
    $mail->Sender = $GLOBALS['message_envelope'];

    //# always add the List-Unsubscribe header
    $removeurl = getConfig('unsubscribeurl');
    $sep = strpos($removeurl, '?') === false ? '?' : '&';
    $mail->addCustomHeader('List-Unsubscribe: <'.$removeurl.$sep.'email='.$to.'&jo=1>');

    return $mail->compatSend('', $destinationemail, $fromname, $fromemail, $subject);
}

function sendMailDirect($destinationemail, $subject, $message)
{
    $GLOBALS['smtpError'] = '';
    //# try to deliver directly, so that any error (eg user not found) can be sent back to the
    //# subscriber, so they can fix it
    unset($GLOBALS['developer_email']);

    list($htmlmessage, $textmessage) = constructSystemMail($message, $subject);
    $mail = new PHPlistMailer('systemmessage', $destinationemail, false, true);

    list($dummy, $domain) = explode('@', $destinationemail);

    //print_r ($mxhosts);exit;
    $smtpServer = getTopSmtpServer($domain);

    $fromemail = getConfig('message_from_address');
    $fromname = getConfig('message_from_name');
    $mail->Host = $smtpServer;
    $mail->Helo = getConfig('website');
    $mail->Port = 25;
    $mail->Mailer = 'smtp';
    if (!empty($htmlmessage)) {
        $mail->add_html($htmlmessage, $textmessage, getConfig('systemmessagetemplate'));
        $mail->add_text($textmessage);
    }
    $mail->add_text($textmessage);
    try {
        $mail->Send('', $destinationemail, $fromname, $fromemail, $subject);
    } catch (Exception $e) {
        $GLOBALS['smtpError'] = $e->getMessage();

        return false;
    }

    return true;
}

function sendAdminCopy($subject, $message, $lists = array())
{
    $sendcopy = getConfig('send_admin_copies');
    if ($sendcopy) {
        $lists = cleanArray($lists);
        $mails = array();
        if (count($lists) && SEND_LISTADMIN_COPY) {
            $mailsreq = Sql_Query(sprintf('select email from %s admin, %s list where admin.id = list.owner and list.id in (%s)',
                $GLOBALS['tables']['admin'], $GLOBALS['tables']['list'], implode(',', $lists)));
            while ($row = Sql_Fetch_Array($mailsreq)) {
                array_push($mails, $row['email']);
            }
        }
        //# hmm, do we want to be exclusive? Either listadmin or main ones
        //# could do all instead
        if (!count($mails)) {
            $admin_mail = getConfig('admin_address');

            if ($c = getConfig('admin_addresses')) {
                $mails = explode(',', $c);
            }
            array_push($mails, $admin_mail);
        }
        $sent = array();
        foreach ($mails as $admin_mail) {
            $admin_mail = trim($admin_mail);
            if (!isset($sent[$admin_mail]) && !empty($admin_mail)) {
                sendMail($admin_mail, $subject, $message, system_messageheaders($admin_mail));
                //   logEvent(s('Sending admin copy to').' '.$admin_mail);
                $sent[$admin_mail] = 1;
            }
        }
    }
}

function safeImageName($name)
{
    $name = 'image'.str_replace('.', 'DOT', $name);
    $name = str_replace('(', 'BRO', $name);
    $name = str_replace(')', 'BRC', $name);
    $name = str_replace(' ', 'SPC', $name);
    $name = str_replace('-', 'DASH', $name);
    $name = str_replace('_', 'US', $name);
    $name = str_replace('/', 'SLASH', $name);
    $name = str_replace(':', 'COLON', $name);

    return $name;
}

function clean2($value)
{
    $value = trim($value);
    $value = preg_replace("/\r/", '', $value);
    $value = preg_replace("/\n/", '', $value);
    $value = str_replace('"', '&quot;', $value);
    $value = str_replace("'", '&rsquo;', $value);
    $value = str_replace('`', '&lsquo;', $value);
    $value = stripslashes($value);

    return $value;
}

function cleanEmail($value)
{
    $value = trim($value);
    $value = preg_replace("/\r/", '', $value);
    $value = preg_replace("/\n/", '', $value);
    $value = preg_replace('/"/', '&quot;', $value);
    $value = preg_replace('/^mailto:/i', '', $value);
    $value = str_replace('(', '', $value);
    $value = str_replace(')', '', $value);
    $value = preg_replace('/\.$/', '', $value);

    //# these are allowed in emails
//  $value = preg_replace("/'/","&rsquo;",$value);
    $value = preg_replace('/`/', '&lsquo;', $value);
    $value = stripslashes($value);

    return $value;
}

if (TEST && REGISTER) {
    $pixel = '<img src="'.PHPLIST_POWEREDBY_URLROOT.'/pixel.gif" width="1" height="1" />';
}

function timeDiff($time1, $time2)
{
    if (!$time1 || !$time2) {
        return $GLOBALS['I18N']->get('Unknown');
    }
    $t1 = strtotime($time1);
    $t2 = strtotime($time2);

    if ($t1 < $t2) {
        $diff = $t2 - $t1;
    } else {
        $diff = $t1 - $t2;
    }
    if ($diff == 0) {
        return $GLOBALS['I18N']->get('very little time');
    }

    return secs2time($diff);
}

function previewTemplate($id, $adminid = 0, $text = '', $footer = '')
{
    global $tables;
    if (defined('IN_WEBBLER')) {
        $more = '&amp;pi='.$_GET['pi'];
    } else {
        $more = '';
    }
    $poweredImageId = 0;
    // make sure the 0 template has the powered by image
    $req = Sql_Query(sprintf('select id from %s where filename = "powerphplist.png" and template = 0',
        $GLOBALS['tables']['templateimage']));
    if (!Sql_Affected_Rows()) {
        Sql_Query(sprintf('insert into %s (template, mimetype, filename, data, width, height)
      values (0, "image/png", "powerphplist.png", "%s", 70, 30)', $GLOBALS['tables']['templateimage'],
            $GLOBALS['newpoweredimage']));
        $poweredImageId = Sql_Insert_Id();
    } else {
        $row = Sql_Fetch_Row($req);
        $poweredImageId = $row[0];
    }
    $tmpl = Sql_Fetch_Row_Query(sprintf('select template from %s where id = %d', $tables['template'], $id));
    $template = stripslashes($tmpl[0]);
    $img_req = Sql_Query(sprintf('select id,filename from %s where template = %d order by filename desc',
        $tables['templateimage'], $id));
    while ($img = Sql_Fetch_Array($img_req)) {
        $template = preg_replace('#'.preg_quote($img['filename']).'#', '?page=image&amp;id='.$img['id'].$more,
            $template);
    }
    if ($adminid) {
        $att_req = Sql_Query("select name,value from {$tables['adminattribute']},{$tables['admin_attribute']} where {$tables['adminattribute']}.id = {$tables['admin_attribute']}.adminattributeid and {$tables['admin_attribute']}.adminid = $adminid");
        while ($att = Sql_Fetch_Array($att_req)) {
            $template = preg_replace("#\[LISTOWNER.".strtoupper(preg_quote($att['name']))."\]#", $att['value'],
                $template);
        }
    }
    if (empty($footer)) {
        $footer = getConfig('messagefooter');
    }

    if ($footer) {
        $template = str_ireplace('[FOOTER]', $footer, $template);
    }
    $template = preg_replace("#\[CONTENT\]#", $text, $template);
    $fromemail = getConfig('campaignfrom_default');
    if (empty($fromemail)) {
        $fromemail = 'user@server.com';
    }
    $template = str_ireplace('[FROMEMAIL]', $fromemail, $template);
    $template = str_ireplace('[EMAIL]', 'recipient@destination.com', $template);

    $template = str_ireplace('[SUBJECT]', s('This is the Newsletter Subject'), $template);
    $template = str_ireplace('[UNSUBSCRIBE]',
        sprintf('<a href="%s">%s</a>', getConfig('unsubscribeurl'), $GLOBALS['strThisLink']), $template);
    //0013076: Blacklisting posibility for unknown users
    $template = str_ireplace('[BLACKLIST]',
        sprintf('<a href="%s">%s</a>', getConfig('blacklisturl'), $GLOBALS['strThisLink']), $template);
    $template = str_ireplace('[PREFERENCES]',
        sprintf('<a href="%s">%s</a>', getConfig('preferencesurl'), $GLOBALS['strThisLink']), $template);

    $logoImageId = getConfig('organisation_logo');
    preg_match_all('/\[LOGO\:?(\d+)?\]/', $template, $logoInstances);
    foreach ($logoInstances[0] as $index => $logoInstance) {
        $size = $logoInstances[1][$index];
        if (!empty($size)) {
            $logoSize = '&amp;m='.$size;
        } else {
            $logoSize = '';
        }
        $template = str_replace($logoInstance, '?page=image&amp;id='.$logoImageId.$logoSize, $template);
    }

    $orgName = getConfig('organisation_name');
    if ($orgName === '' || $orgName === null) {
        // If organisation name is not set, show [ORGANISATION_NAME] placeholder
        $template = str_ireplace('[ORGANISATION_NAME]', '[ORGANISATION_NAME]', $template);
    } else {
        $template = str_ireplace('[ORGANISATION_NAME]', $orgName, $template);
    }

    if (!EMAILTEXTCREDITS) {
        $template = str_ireplace('[SIGNATURE]',
            '<img src="?page=image&amp;id='.$poweredImageId.'" width="70" height="30" />', $template);
    } else {
        $template = str_ireplace('[SIGNATURE]', $GLOBALS['PoweredByText'], $template);
    }
    $template = preg_replace("/\[[A-Z\. ]+\]/", '', $template);
    $template = str_ireplace('<form', '< form', $template);
    $template = str_ireplace('</form', '< /form', $template);

    return $template;
}

function parseMessage($content, $template, $adminid = 0)
{
    global $tables;
    $tmpl = Sql_Fetch_Row_Query("select template from {$tables['template']} where id = $template");
    $template = $tmpl[0];
    $template = preg_replace("#\[CONTENT\]#", $content, $template);
    $att_req = Sql_Query("select name,value from {$tables['adminattribute']},{$tables['admin_attribute']} where {$tables['adminattribute']}.id = {$tables['admin_attribute']}.adminattributeid and {$tables['admin_attribute']}.adminid = $adminid");
    while ($att = Sql_Fetch_Array($att_req)) {
        $template = preg_replace("#\[LISTOWNER.".strtoupper(preg_quote($att['name']))."\]#", $att['value'],
            $template);
    }

    return $template;
}

function listOwner($listid = 0)
{
    global $tables;
    $req = Sql_Fetch_Row_Query("select owner from {$tables['list']} where id = $listid");

    return $req[0];
}

function listUsedInSubscribePage($listid = 0)
{
    if (empty($listid)) {
        return false;
    }
    $used = false;
    $req = Sql_Query(sprintf('select data from %s where name = "lists"', $GLOBALS['tables']['subscribepage_data']));
    while ($row = Sql_Fetch_Assoc($req)) {
        $lists = explode(',', $row['data']);
        $used = $used || in_array($listid, $lists);
        if ($used) {
            return true;
        }
    }

    return $used;
}

function system_messageHeaders($useremail = '')
{
    $from_address = getConfig('message_from_address');
    $from_name = getConfig('message_from_name');
    if ($from_name) {
        $additional_headers = "From: \"$from_name\" <$from_address>\n";
    } else {
        $additional_headers = "From: $from_address\n";
    }
    $message_replyto_address = getConfig('message_replyto_address');
    if ($message_replyto_address) {
        $additional_headers .= "Reply-To: $message_replyto_address\n";
    } else {
        $additional_headers .= "Reply-To: $from_address\n";
    }
    $v = VERSION;
    $additional_headers .= "X-Mailer: phplist version $v (www.phplist.com)\n";
    $additional_headers .= "X-MessageID: systemmessage\n";
    if ($useremail) {
        $additional_headers .= 'X-User: '.$useremail."\n";
    }

    return $additional_headers;
}

function logEvent($msg)
{
    $logged = false;
    foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
        $logged = $logged || $plugin->logEvent($msg);
    }
    if ($logged) {
        return;
    }

    global $tables;
    if (isset($GLOBALS['page'])) {
        $p = $GLOBALS['page'];
    } elseif (isset($_GET['page'])) {
        $p = $_GET['page'];
    } elseif (isset($_GET['p'])) {
        $p = $_GET['p'];
    } else {
        $p = 'unknown page';
    }
    if (!Sql_Table_Exists($tables['eventlog'])) {
        return;
    }
    Sql_Query(sprintf('insert into %s (entered,page,entry) values(now(),"%s","%s")', $tables['eventlog'],
        $p, sql_escape($msg)));
}

//## process locking stuff
function getPageLock($force = 0)
{
    global $tables;
    $thispage = $GLOBALS['page'];
    if ($thispage == 'pageaction') {
        $thispage = $_GET['action'];
    }
    $thispage = preg_replace('/\W/', '', $thispage);
//  cl_output('getting pagelock '.$thispage);
//  ob_end_flush();

    if ($GLOBALS['commandline'] && $thispage == 'processqueue') {
        if (is_object($GLOBALS['MC'])) {
            //# multi-send requires a valid memcached setup
            $max = MAX_SENDPROCESSES;
        } else {
            $max = 1;
        }
    } else {
        $max = 1;
    }

    //# allow killing other processes
    if ($force) {
        Sql_query('delete from '.$tables['sendprocess'].' where page = "'.sql_escape($thispage).'"');
    }

    $running_req = Sql_query(sprintf('select now() - modified as age,id from %s where page = "%s" and alive order by started desc',
        $tables['sendprocess'], sql_escape($thispage)));
    $count = Sql_Num_Rows($running_req);
    $running_res = Sql_Fetch_Assoc($running_req);
    $waited = 0;
    // while ($running_res['age'] && $count >= $max) { # a process is already running
    while ($count >= $max) { // don't check age, as it may be 0
        //   cl_output('running process: '.$running_res['age'].' '.$max);
        if ($running_res['age'] > 600) {
            // some sql queries can take quite a while
            //cl_output($running_res['id'].' is old '.$running_res['age']);
            // process has been inactive for too long, kill it
            Sql_query("update {$tables['sendprocess']} set alive = 0 where id = ".$running_res['id']);
        } elseif ((int) $count >= (int) $max) {
            //   cl_output (sprintf($GLOBALS['I18N']->get('A process for this page is already running and it was still alive %s seconds ago'),$running_res['age']));
            if (function_exists('output')) {
                output(s('A process for this page is already running and it was still alive %d seconds ago', $running_res['age']), 0);
            }
            sleep(1); // to log the messages in the correct order
            if ($GLOBALS['commandline']) {
                cl_output(s('A process for this page is already running and it was still alive %d seconds ago',
                    $running_res['age']), 0);
                cl_output($GLOBALS['I18N']->get('Running commandline, quitting. We\'ll find out what to do in the next run.'));
                exit;
            }
            output($GLOBALS['I18N']->get('Sleeping for 20 seconds, aborting will quit'), 0);
            flush();
            $abort = ignore_user_abort(0);
            sleep(20);
        }
        ++$waited;
        if ($waited > 10) {
            // we have waited 10 cycles, abort and quit script
            output($GLOBALS['I18N']->get('We have been waiting too long, I guess the other process is still going ok'),
                0);

            return false;
        }
        $running_req = Sql_query('select now() - modified,id from '.$tables['sendprocess']." where page = \"$thispage\" and alive order by started desc");
        $count = Sql_Num_Rows($running_req);
        $running_res = Sql_Fetch_row($running_req);
    }
    if (!empty($GLOBALS['commandline'])) {
        $processIdentifier = SENDPROCESS_SERVERNAME.':'.getmypid();
    } else {
        $processIdentifier = $_SERVER['REMOTE_ADDR'];
    }
    $res = Sql_query('insert into '.$tables['sendprocess'].' (started,page,alive,ipaddress) values(now(),"'.$thispage.'",1,"'.$processIdentifier.'")');
    $send_process_id = Sql_Insert_Id();
    $abort = ignore_user_abort(1);
//  cl_output('Got pagelock '.$send_process_id );
    return $send_process_id;
}

function keepLock($processid)
{
    global $tables;
    $thispage = $GLOBALS['page'];
    Sql_query('Update '.$tables['sendprocess']." set alive = alive + 1 where id = $processid");
}

function checkLock($processid)
{
    global $tables;
    $thispage = $GLOBALS['page'];
    $res = Sql_query("select alive from {$tables['sendprocess']} where id = $processid");
    $row = Sql_Fetch_Row($res);

    return $row[0];
}

function getPageCache($url, $lastmodified = 0)
{
    $req = Sql_Fetch_Row_Query(sprintf('select content from %s where url = "%s" and lastmodified >= %d',
        $GLOBALS['tables']['urlcache'], $url, $lastmodified));

    return $req[0];
}

function getPageCacheLastModified($url)
{
    $req = Sql_Fetch_Row_Query(sprintf('select lastmodified from %s where url = "%s"', $GLOBALS['tables']['urlcache'],
        $url));

    return $req[0];
}

function setPageCache($url, $lastmodified, $content)
{
    //  if (isset($GLOBALS['developer_email'])) return;
    Sql_Query(sprintf('delete from %s where url = "%s"', $GLOBALS['tables']['urlcache'], $url));
    Sql_Query(sprintf('insert into %s (url,lastmodified,added,content)
    values("%s",%d,now(),"%s")', $GLOBALS['tables']['urlcache'], $url, $lastmodified, sql_escape($content)));
}

function clearPageCache()
{
    unset($GLOBALS['urlcache']);
    Sql_Query('delete from '.$GLOBALS['tables']['urlcache']);
}

function removeJavascript($content)
{
    $content = preg_replace('/<script[^>]*>(.*?)<\/script\s*>/mis', '', $content);

    return $content;
}

function stripComments($content)
{
    $content = preg_replace('/<!--(.*?)-->/mis', '', $content);

    return $content;
}

function compressContent($content)
{

    //# this needs loads more testing across systems to be sure
    return $content;

    $content = preg_replace("/\n/", ' ', $content);
    $content = preg_replace("/\r/", '', $content);
    $content = removeJavascript($content);
    $content = stripComments($content);

    //# find some clean way to remove double spacing
    $content = preg_replace("/\t/", ' ', $content);
    while (preg_match('/  /', $content)) {
        $content = preg_replace('/  /', ' ', $content);
    }

    return $content;
}

function encryptPass($pass)
{
    if (empty($pass)) {
        return '';
    }

    if (function_exists('hash')) {
        if (!in_array(HASH_ALGO, hash_algos(), true)) {
            //# fallback, not that secure, but better than none at all
            $algo = 'md5';
        } else {
            $algo = HASH_ALGO;
        }

        return hash($algo, $pass);
    } else {
        return md5($pass);
    }
}

/* do not use @@@

# try to pull remote styles into the email, but that's a nightmare, because
# it needs to be made absolute again
# kind of works, but not really nicely.

function includeStyles($text) {
  $styles = fetchStyles($text);
  $text = stripStyles($text);
  $text = preg_replace('#</head>#','<style type="text/css">'.$styles.'</style></head>',$text);
  return $text;
}

function stripStyles($text) {
  $tags = array('src\s*=\s*','href\s*=\s*','action\s*=\s*',
    'background\s*=\s*','@import\s+','@import\s+url\(');
  foreach ($tags as $tag) {
    preg_match_all('/<link.*('.$tag.')"([^"|\#]*)".*>/Uim', $text, $foundtags);
    for ($i=0; $i< count($foundtags[0]); $i++) {
      $pat = $foundtags[0][$i];
      $match = $foundtags[2][$i];
      $tagmatch = $foundtags[1][$i];
      if (preg_match("#[http|https]:#i",$match) && preg_match("#\.css$#i",$match)) {
        $text = str_replace($foundtags[0][$i],'',$text);
      }
    }
  }
  return $text;
}

function fetchStyles($text) {
  $styles = '';
  $url = '';
  $tags = array('src\s*=\s*','href\s*=\s*','action\s*=\s*',
    'background\s*=\s*','@import\s+','@import\s+url\(');
  foreach ($tags as $tag) {
    preg_match_all('/<link.*('.$tag.')"([^"|\#]*)"/Uim', $text, $foundtags);
    for ($i=0; $i< count($foundtags[0]); $i++) {
      $match = $foundtags[2][$i];
      $url = $match;
      $tagmatch = $foundtags[1][$i];
      if (preg_match("#[http|https]:#i",$match) && preg_match("#\.css$#i",$match)) {
        $styles .= fetchUrl($match);
      }
    }
  }
  return addAbsoluteResources($styles,$url);
}

*/

/* verify that a redirection is to ourselves */
function isValidRedirect($url)
{
    //# we might want to add some more checks here
    return strpos($url, hostName());
}

/* check the url_append config and expand the url with it
 */

function expandURL($url)
{
    $url_append = getConfig('remoteurl_append');
    $url_append = strip_tags($url_append);
    $url_append = preg_replace('/\W/', '', $url_append);
    if ($url_append) {
        if (strpos($url, '?')) {
            $url = $url.$url_append;
        } else {
            $url = $url.'?'.$url_append;
        }
    }

    return $url;
}

function testUrl($url)
{
    if (VERBOSE) {
        logEvent('Checking '.$url);
    }

    if ($GLOBALS['has_curl']) {
        if (VERBOSE) {
            logEvent('Checking curl ');
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_DNS_USE_GLOBAL_CACHE, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'phplist v'.VERSION.'c (https://www.phplist.com)');
        $raw_result = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    } else {
        if (VERBOSE) {
            logEvent('Checking HTTP_Request2 ');
        }
        require_once 'HTTP/Request2.php';

        $headreq = new HTTP_Request2($url, HTTP_Request2::METHOD_HEAD, array('follow_redirects' => true));
        $headreq->setHeader('User-Agent', 'phplist v'.VERSION.'p (https://www.phplist.com)');

        try {
            $response = $headreq->send();
            $code = $response->getStatus();
        } catch (HTTP_Request2_Exception $e) {
            logEvent(sprintf('Error fetching %s %s', $url, $e->getMessage()));
            $code = 500;
        }
    }
    if (VERBOSE) {
        logEvent('Checking '.$url.' => '.$code);
    }

    return $code;
}

/**
 * Returns the content of a URL from
 *  the global variable $urlcache,
 *  or the urlcache table
 *  or fetching the URL directly.
 *
 * @param string $url      the URL to fetch
 * @param array  $userdata user fields to be replaced in the url
 * @param int    $ttl      time to live, the number of seconds after which the cached item will expire
 *
 * @return string|false the url content or false for an error
 */
function fetchUrl($url, $userdata = array(), $ttl = REMOTE_URL_REFETCH_TIMEOUT)
{
    $content = '';

    //# fix the Editor replacing & with &amp;
    $url = str_ireplace('&amp;', '&', $url);

    // logEvent("Fetching $url");
    if (count($userdata)) {
        foreach ($userdata as $key => $val) {
            if ($key != 'password') {
                $url = utf8_encode(str_ireplace("[$key]", urlencode($val), utf8_decode($url)));
            }
        }
    }

    if (!isset($GLOBALS['urlcache'])) {
        $GLOBALS['urlcache'] = array();
    }

    $url = expandUrl($url);
//  print "<h1>Fetching ".$url."</h1>";

    // keep in memory cache in case we send a page to many emails
    if (isset($GLOBALS['urlcache'][$url]) && is_array($GLOBALS['urlcache'][$url])
        && (time() - $GLOBALS['urlcache'][$url]['fetched'] < $ttl)
    ) {
        //     logEvent($url . " is cached in memory");
        if (VERBOSE && function_exists('output')) {
            output('From memory cache: '.$url);
        }

        return $GLOBALS['urlcache'][$url]['content'];
    }

    $dbcache_lastmodified = getPageCacheLastModified($url);
    $timeout = time() - $dbcache_lastmodified;
    if ($timeout < $ttl) {
        //    logEvent($url.' was cached in database');
        if (VERBOSE && function_exists('output')) {
            output('From database cache: '.$url);
        }

        return getPageCache($url);
    } else {
        //    logEvent($url.' is not cached in database '.$timeout.' '. $dbcache_lastmodified." ".time());
    }

    $request_parameters = array(
        'timeout'        => 600,
        'allowRedirects' => 1,
        'method'         => 'HEAD',
    );

    $remote_charset = 'UTF-8';
    //# relying on the last modified header doesn't work for many pages
    //# use current time instead
    //# see http://mantis.phplist.com/view.php?id=7684
//    $lastmodified = strtotime($header["last-modified"]);
    $lastmodified = time();
    $content = getPageCache($url, $lastmodified);

    if ($content) {
        if (VERBOSE) {
            logEvent($url.' was cached in database');
        }
    } else {
        $content = fetchUrlDirect($url, $request_parameters);
    }

    if (!empty($content)) {
        $content = addAbsoluteResources($content, $url);
        logEvent('Fetching '.$url.' success');
        setPageCache($url, $lastmodified, $content);

        $GLOBALS['urlcache'][$url] = array(
            'fetched' => time(),
            'content' => $content,
        );
    }

    return $content;
}

/**
 * Fetches a URL directly.
 * Use curl if available otherwise fallback to HTTP_Request2.
 *
 * @param string  $url               the URL to fetch
 * @param array   $requestParameters params for the http request
 *
 * @return string|false the url content or false for an error
 */
function fetchUrlDirect($url, $requestParameters = array())
{
    global $has_curl;

    $defaultParameters = array(
        'timeout' => 10,
    );
    $parameters = $requestParameters + $defaultParameters;

    if ($has_curl) {
        return fetchUrlCurl($url, $parameters);
    }

    return fetchUrlHttpRequest2($url, $parameters);
}

function fetchUrlCurl($url, $request_parameters)
{
    if (VERBOSE) {
        logEvent($url.' fetching with curl ');
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_TIMEOUT, $request_parameters['timeout']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_DNS_USE_GLOBAL_CACHE, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'phplist v'.VERSION.'c (https://www.phplist.com)');
    $raw_result = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if (VERBOSE) {
        logEvent('fetched '.$url.' status '.$status);
    }
//    var_dump($status); exit;
    if ($status == 200) {
        return $raw_result;
    } else {
        return '';
    }
}

/**
 * Fetches a URL using the PEAR package HTTP_Request2.
 *
 * @param string  $url               the URL to fetch
 * @param array   $requestParameters params for the http request
 *
 * @return string the url content or false for an error
 */
function fetchUrlHttpRequest2($url, $requestParameters)
{
    require_once 'HTTP/Request2.php';

    if (VERBOSE) {
        logEvent("Fetching $url with HTTP_Request2");
    }
    $request = new HTTP_Request2(
        $url,
        HTTP_Request2::METHOD_GET,
        array(
            'timeout' => $requestParameters['timeout'],
            'follow_redirects' => true,
        )
    );
    $request->setHeader('User-Agent', 'phplist v'.VERSION.'p (http://www.phplist.com)');

    try {
        $response = $request->send();

        if ($response->getStatus() == 200) {
            $content = $response->getBody();

            if (VERBOSE) {
                logEvent("Fetched $url");
            }
        } else {
            logEvent(sprintf('Unexpected HTTP status: %s %s', $response->getStatus(), $response->getReasonPhrase()));
            $content = false;
        }
    } catch (HTTP_Request2_Exception $e) {
        logEvent(sprintf('Error fetching %s %s', $url, $e->getMessage()));
        $content = false;
    }

    return $content;
}

function releaseLock($processid)
{
    global $tables;
    if (!$processid) {
        return;
    }
    Sql_query("delete from {$tables['sendprocess']} where id = $processid");
}

// some basic MM functions, to be expanded upon
function setMaintenanceMode($message = '') {
    SaveConfig('maintenancemode', serialize(array('message' => $message)),0);
}

function clearMaintenanceMode() {
    SaveConfig('maintenancemode','',0);
}

function inMaintenanceMode() {
    $mm = getConfig('maintenancemode');
    return $mm;
}

function parseQueryString($str)
{
    if (empty($str)) {
        return array();
    }
    $op = array();
    $pairs = explode('&', $str);
    foreach ($pairs as $pair) {
        if (strpos($pair, '=') !== false) {
            list($k, $v) = array_map('urldecode', explode('=', $pair));
            $op[$k] = $v;
        } else {
            $op[$pair] = '';
        }
    }

    return $op;
}

function cleanUrl($url, $disallowed_params = array('PHPSESSID'))
{
    // process url only if it contains a disallowed parameter
    $pattern = sprintf('/(%s)=/', implode('|', $disallowed_params));

    if (!preg_match($pattern, $url)) {
        return htmlspecialchars_decode($url);
    }
    $parsed = @parse_url($url);
    $params = array();
    if (empty($parsed['query'])) {
        $parsed['query'] = '';
    }
    // hmm parse_str should take the delimiters as a parameter
    if (strpos($parsed['query'], '&amp;')) {
        $pairs = explode('&amp;', $parsed['query']);
        foreach ($pairs as $pair) {
            if (strpos($pair, '=') !== false) {
                list($key, $val) = explode('=', $pair);
                $params[$key] = $val;
            } else {
                $params[$pair] = '';
            }
        }
    } else {
        //# parse_str turns . into _ which is wrong
//    parse_str($parsed['query'],$params);
        $params = parseQueryString($parsed['query']);
    }
    $uri = !empty($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '' : '//') : '';
    $uri .= !empty($parsed['user']) ? $parsed['user'].(!empty($parsed['pass']) ? ':'.$parsed['pass'] : '').'@' : '';
    $uri .= !empty($parsed['host']) ? $parsed['host'] : '';
    $uri .= !empty($parsed['port']) ? ':'.$parsed['port'] : '';
    $uri .= !empty($parsed['path']) ? $parsed['path'] : '';
//  $uri .= $parsed['query'] ? '?'.$parsed['query'] : '';
    $query = '';
    foreach ($params as $key => $val) {
        if (!in_array($key, $disallowed_params)) {
            //0008980: Link Conversion for Click Tracking. no = will be added if key is empty.
            $query .= $key.($val != '' ? '='.$val.'&' : '&');
        }
    }
    $query = substr($query, 0, -1);
    $uri .= $query ? '?'.$query : '';
//  if (!empty($params['p'])) {
//    $uri .= '?p='.$params['p'];
//  }
    $uri .= !empty($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

    return $uri;
}

function adminName($id = 0)
{
    if (!$id) {
        $id = $_SESSION['logindetails']['id'];
    }
    if (is_object($GLOBALS['admin_auth'])) {
        return $GLOBALS['admin_auth']->adminName($id);
    }
    $req = Sql_Fetch_Row_Query(sprintf('select loginname from %s where id = %d', $GLOBALS['tables']['admin'], $id));

    return $req[0] ? $req[0] : 'Nobody';
}

if (!function_exists('dbg')) {
    function xdbg($msg, $logfile = '')
    {
        if (!$logfile) {
            return;
        }
        $fp = @fopen($logfile, 'a');
        $line = '['.date('d M Y, H:i:s').'] '.getenv('REQUEST_URI').'('.$config['stats']['number_of_queries'].") $msg \n";
        @fwrite($fp, $line);
        @fclose($fp);
    }
}

function addSubscriberStatistics($item, $amount, $list = 0)
{
    switch (STATS_INTERVAL) {
        case 'monthly':
            // mark everything as the first day of the month
            $time = mktime(0, 0, 0, date('m'), 1, date('Y'));
            break;
        case 'weekly':
            // mark everything for the first sunday of the week
            $time = mktime(0, 0, 0, date('m'), date('d') - date('w'), date('Y'));
            break;
        case 'daily':
            $time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            break;
    }
    Sql_Query(sprintf('update %s set value = value + %d where unixdate = %d and item = "%s" and listid = %d',
        $GLOBALS['tables']['userstats'], $amount, $time, $item, $list));
    $done = Sql_Affected_Rows();
    if (!$done) {
        Sql_Query(sprintf('insert into %s set value = %d,unixdate = %d,item = "%s",listid = %d',
            $GLOBALS['tables']['userstats'], $amount, $time, $item, $list));
    }
}


/**
 * Insert a draft campaign for use with the Invite plugin
 * @param null|int $forcedOwnerId
 * @return bool|mysqli_result
 * @throws Exception
 * @todo Make the campaign content translatable
 * @todo Add Campaign Meta Title to clarify purpose of this draft
 */
function addInviteCampaign($forcedOwnerId = null) {

    $message =
'<p>Hi [FIRST NAME%%there], remember us? You first signed up for our email newsletter on&nbsp;[ENTERED] &ndash; please click here to confirm you&#39;re happy to continue receiving our messages:</p>

<p><strong><a href="[CONFIRMATIONURL]" title="Confirm subscription">Continue receiving messages</a></strong>&nbsp; <u>(If you do not confirm using this link, then you won&#39;t hear from us again</u>)</p>

<p>While you&#39;re at it, you can also update your preferences, including your email address or other details, by clicking here:</p>

<p><strong><a href="[PREFERENCESURL]">Update preferences</a></strong></p>

<p>By confirming your membership and keeping your details up to date, you&#39;re helping us to manage and protect your data in accordance with best practices.</p>

<p>Thank you!</p>';

    $inviteMessage = addslashes($message);
    $inviteMessageSubject = "Do you want to continue receiving our messages?";
    $uuid = uuid::generate(4);
    if ( $forcedOwnerId !== null) {
        $ownerid = $forcedOwnerId;
    } else {
        $ownerid = $_SESSION['logindetails']['id'];
    }
    $footer = sql_escape(getConfig('messagefooter'));
    $result = Sql_query("insert into {$GLOBALS['tables']['message']} (uuid,subject,message,entered, status, owner, footer, sendformat) values(\"$uuid\",\"$inviteMessageSubject\",\"$inviteMessage\",now(),\"draft\",\"$ownerid\",\"$footer\",\"invite\" )");

    return $result;

   }

function deleteMessage($id = 0)
{
    if (isSuperUser()) {
        $ownerselect_and = '';
        $ownerselect_where = '';
    } else {
        $ownerselect_where = ' WHERE owner = '.$_SESSION['logindetails']['id'];
        $ownerselect_and = ' and owner = '.$_SESSION['logindetails']['id'];
    }

    // delete the message in delete
    $result = Sql_query('select id from '.$GLOBALS['tables']['message']." where id = $id $ownerselect_and");
    while ($row = Sql_Fetch_Row($result)) {
        $result = Sql_query('delete from '.$GLOBALS['tables']['message']." where id = $row[0]");
        $suc6 = Sql_Affected_Rows();
        $result = Sql_query('delete from '.$GLOBALS['tables']['usermessage']." where messageid = $row[0]");
        $result = Sql_query('delete from '.$GLOBALS['tables']['listmessage']." where messageid = $row[0]");
        $result = Sql_query('delete from '.$GLOBALS['tables']['user_message_view']." where messageid = $row[0]");
        $result = Sql_query('delete from '.$GLOBALS['tables']['message_attachment']." where messageid = $row[0]");
        $result = Sql_query('delete from '.$GLOBALS['tables']['messagedata']." where id = $row[0]");
        $result = Sql_query('delete from '.$GLOBALS['tables']['linktrack_uml_click']." where messageid = $row[0]");
        $result = Sql_query('delete from '.$GLOBALS['tables']['linktrack_ml']." where messageid = $row[0]");
        $result = Sql_query('delete from '.$GLOBALS['tables']['user_message_bounce']." where message = $row[0]");
        $result = Sql_query('delete from '.$GLOBALS['tables']['user_message_forward']." where message = $row[0]");
        return $suc6;
    }
}

function deleteBounce($id = 0)
{
    if (!$id) {
        return;
    }
    $id = sprintf('%d', $id);
    Sql_query(sprintf('delete from %s where id = %d', $GLOBALS['tables']['bounce'], $id));
    Sql_query(sprintf('delete from %s where bounce = %d', $GLOBALS['tables']['user_message_bounce'], $id));
    Sql_query(sprintf('delete from %s where bounce = %d', $GLOBALS['tables']['bounceregex_bounce'], $id));
}

function reverse_htmlentities($mixed)
{
    $htmltable = get_html_translation_table(HTML_ENTITIES);
    foreach ($htmltable as $key => $value) {
        $mixed = str_replace(addslashes($value), $key, $mixed);
    }

    return $mixed;
}

function loadBounceRules($all = 0)
{
    if ($all) {
        $status = '';
    } else {
        $status = ' where status = "active"';
    }
    $result = array();
    $req = Sql_Query(sprintf('select * from %s %s order by listorder', $GLOBALS['tables']['bounceregex'], $status));
    while ($row = Sql_Fetch_Array($req)) {
        if ($row['regex'] && $row['action']) {
            $result[$row['regex']] = array(
                'action' => $row['action'],
                'id'     => $row['id'],
            );
        }
    }

    return $result;
}

function matchedBounceRule($text, $activeonly = 0)
{
    if ($activeonly) {
        $status = ' where status = "active"';
    } else {
        $status = '';
    }
    $req = Sql_Query(sprintf('select * from %s %s order by listorder', $GLOBALS['tables']['bounceregex'], $status));
    while ($row = Sql_Fetch_Array($req)) {
        $pattern = str_replace(' ', '\s+', $row['regex']);
        //   print "Trying to match ".$pattern;
        //print ' with '.$text;
        //   print '<br/>';
        if (@preg_match('/'.preg_quote($pattern).'/iUm', $text)) {
            return $row['id'];
        } elseif (@preg_match('/'.$pattern.'/iUm', $text)) {
            return $row['id'];
        }
    }

    return '';
}

function matchBounceRules($text, $rules = array())
{
    if (!count($rules)) {
        $rules = loadBounceRules();
    }

    foreach ($rules as $pattern => $rule) {
        $pattern = str_replace(' ', '\s+', $pattern);
        if (@preg_match('/'.preg_quote($pattern).'/iUm', $text)) {
            return $rule;
        } elseif (@preg_match('/'.$pattern.'/iUm', $text)) {
            return $rule;
        } else {
            //      print "Trying to match $pattern failed<br/>";
        }
    }

    return '';
}

function flushBrowser()
{
    //# push some more output to the browser, so it displays things sooner
    for ($i = 0; $i < 10000; ++$i) {
        echo ' '."\n";
    }
    flush();
}

function flushClickTrackCache()
{
    if (!isset($GLOBALS['cached']['linktracksent'])) {
        return;
    }
    foreach ($GLOBALS['cached']['linktracksent'] as $mid => $numsent) {
        foreach ($numsent as $fwdid => $fwdtotal) {
            if (VERBOSE) {
                output("Flushing clicktrack stats for $mid: $fwdid => $fwdtotal");
            }
            Sql_Query(sprintf('update %s set total = %d where messageid = %d and forwardid = %d',
                $GLOBALS['tables']['linktrack_ml'], $fwdtotal, $mid, $fwdid));
        }
    }
}

function resetMessageStatistics($messageid = 0)
{
    //# remove the record of the links in the message, actual clicks of links, and the users sent to

    //# do not do this, if more than X have gone out
    $numsent = Sql_Fetch_Row_Query(sprintf('select count(*) from %s where messageid = %d',
        $GLOBALS['tables']['usermessage'], $messageid));
    if ($numsent[0] < RESETSTATS_MAX) {
        Sql_Query(sprintf('delete from %s where messageid = %d', $GLOBALS['tables']['linktrack_ml'], $messageid));
        Sql_Query(sprintf('delete from %s where messageid = %d', $GLOBALS['tables']['linktrack_uml_click'],
            $messageid));
        Sql_Query(sprintf('delete from %s where messageid = %d', $GLOBALS['tables']['usermessage'], $messageid));
        Sql_Query(sprintf('delete from %s where messageid = %d', $GLOBALS['tables']['user_message_view'], $messageid));
        Sql_Query(sprintf('update %s set viewed = 0 where id = %d', $GLOBALS['tables']['message'], $messageid));
    }
}

if (!function_exists('formatbytes')) {
    function formatBytes($value)
    {
        $gb = 1024 * 1024 * 1024;
        $mb = 1024 * 1024;
        $kb = 1024;
        $gbs = $value / $gb;
        if ($gbs > 1) {
            return sprintf('%2.2fGb', $gbs);
        }
        $mbs = $value / $mb;
        if ($mbs > 1) {
            return sprintf('%2.2fMb', $mbs);
        }
        $kbs = $value / $kb;
        if ($kbs > 1) {
            return sprintf('%dKb', $kbs);
        } else {
            return sprintf('%dBytes', $value);
        }
    }
}

function strip_newlines($str, $placeholder = '')
{
    $str = str_replace(chr(13).chr(10), $placeholder, $str);
    $str = str_replace(chr(10), $placeholder, $str);
    $str = str_replace(chr(13), $placeholder, $str);

    return $str;
}

function parseDate($strdate, $format = 'Y-m-d')
{
    // parse a string date into a date
    $strdate = trim($strdate);
    if (strlen($strdate) < 6) {
        $newvalue = 0;
    } elseif (preg_match("#(\d{2,2}).(\d{2,2}).(\d{4,4})#", $strdate, $regs)) {
        $newvalue = mktime(0, 0, 0, $regs[2], $regs[1], $regs[3]);
    } elseif (preg_match("#(\d{4,4}).(\d{2,2}).(\d{2,2})#", $strdate, $regs)) {
        $newvalue = mktime(0, 0, 0, $regs[2], $regs[3], $regs[1]);
    } elseif (preg_match("#(\d{2,2}).(\w{3,3}).(\d{2,4})#", $strdate, $regs)) {
        $newvalue = strtotime($strdate);
    } elseif (preg_match("#(\d{2,4}).(\w{3,3}).(\d{2,2})#", $strdate, $regs)) {
        $newvalue = strtotime($strdate);
    } else {
        $newvalue = strtotime($strdate);
        if ($newvalue < 0) {
            $newvalue = 0;
        }
    }
    if ($newvalue) {
        return date($format, $newvalue);
    } else {
        return '';
    }
}

function verifyToken()
{
    if (empty($_POST['formtoken'])) {
        return false;
    }

    //# @@@TODO for now ignore the error. This will cause a block on editing admins if the table doesn't exist.
    $req = Sql_Fetch_Row_Query(sprintf('select id from %s where adminid = %d and value = "%s" and expires > now()',
        $GLOBALS['tables']['admintoken'], $_SESSION['logindetails']['id'], sql_escape($_POST['formtoken'])), 1);
    if (empty($req[0])) {
        return false;
    }
    Sql_Query(sprintf('delete from %s where id = %d',
        $GLOBALS['tables']['admintoken'], $req[0]), 1);
    Sql_Query(sprintf('delete from %s where expires < now()',
        $GLOBALS['tables']['admintoken']), 1);

    return true;
}

//# verify the session token on ajaxed GET requests
function verifyCsrfGetToken($enforce = 1)
{ // enforce=0 allows checking "if exist"
    if (!defined('PHPLISTINIT')) {
        die();
    }
    if ($GLOBALS['commandline']) {
        return true;
    }
    if (isset($_GET['tk']) && isset($_SESSION[$GLOBALS['installation_name'].'_csrf_token'])) {
        if (!hash_equals($_SESSION[$GLOBALS['installation_name'].'_csrf_token'], $_GET['tk'])) {
            $_SESSION['logout_error'] = s('Error, incorrect session token');
            Redirect('logout&err=1');
            exit;
        }
    } elseif ($enforce && isset($_SESSION[$GLOBALS['installation_name'].'_csrf_token'])) {
        $_SESSION['logout_error'] = s('Error, incorrect session token');
        Redirect('logout&err=1');
        exit;
    }

    return true;
}

function addCsrfGetToken()
{
    if (empty($_SESSION[$GLOBALS['installation_name'].'_csrf_token'])) {
        $_SESSION[$GLOBALS['installation_name'].'_csrf_token'] = bin2hex(random_bytes(16));
    }

    return '&tk='.$_SESSION[$GLOBALS['installation_name'].'_csrf_token'];
}

function refreshTlds($force = 0)
{
    //# fetch list of Tlds and store in DB
    $lastDone = getConfig('tld_last_sync');
    $tlds = '';
    //# let's not do this too often
    if ($lastDone + TLD_REFETCH_TIMEOUT < time() || $force) {
        //# even if it fails we mark it as done, so that we won't getting stuck in eternal updating.
        SaveConfig('tld_last_sync', time(), 0);
        if (defined('TLD_AUTH_LIST')) {
            $tlds = fetchUrlDirect(TLD_AUTH_LIST);
        }
        if ($tlds && defined('TLD_AUTH_MD5')) {
            $tld_md5 = fetchUrlDirect(TLD_AUTH_MD5);
            list($remote_md5, $fname) = explode(' ', $tld_md5);
            $mymd5 = md5($tlds);
//        print 'OK: '.$remote_md5.' '.$mymd5;
            $validated = $remote_md5 == $mymd5;
        } else {
            $tlds = file_get_contents(dirname(__FILE__).'/data/tlds-alpha-by-domain.txt');
            $validated = true;
        }

        if ($validated) {
            $lines = explode("\n", $tlds);
            $tld_list = '';
            foreach ($lines as $line) {
                //# for now, only handle ascii lines
                if (preg_match('/^\w+$/', $line)) {
                    $tld_list .= $line.'|';
                }
            }
            $tld_list = substr($tld_list, 0, -1);
            SaveConfig('internet_tlds', strtolower($tld_list), 0);
        }
//  } else {
//    print $lastDone;
    }

    return true;
}

function listCategories()
{
    $sListCategories = getConfig('list_categories');
    $aConfiguredListCategories = cleanArray(explode(',', $sListCategories));
    foreach ($aConfiguredListCategories as $key => $val) {
        $aConfiguredListCategories[$key] = trim($val);
    }

    return $aConfiguredListCategories;
}

/*
 * shortenTextDisplay
 *
 * mostly used for columns in listings to retrict the width, particularly on mobile devices
 * it will show the full text as the title tip but restrict the size of the output
 *
 * will also place a space after / and @ to facilitate wrapping in the browser
 */

function shortenTextDisplay($text, $max = 30)
{
    //# use mb_ version if possible, see https://github.com/phpList/phplist3/pull/10
    if (function_exists('mb_strlen')) {
        return mb_shortenTextDisplay($text, $max);
    }

    $text = str_replace('http://', '', $text);
    if (strlen($text) > $max) {
        if ($max < 30) {
            $display = substr($text, 0, $max - 4).' ... ';
        } else {
            $display = substr($text, 0, 20).' ... '.substr($text, -10);
        }
    } else {
        $display = $text;
    }
    $display = str_replace('/', '/&#x200b;', $display);
    $display = str_replace('@', '@&#x200b;', $display);

    return sprintf('<span title="%s">%s</span>', htmlspecialchars($text),
        htmlspecialchars($text), $display);
}

function mb_shortenTextDisplay($text, $max = 30)
{
    $text = str_replace('http://', '', $text);
    if (mb_strlen($text) > $max) {
        if ($max < 30) {
            $display = mb_substr($text, 0, $max - 4).' ... ';
        } else {
            $display = mb_substr($text, 0, 20).' ... '.mb_substr($text, -10);
        }
    } else {
        $display = $text;
    }
    $display = str_replace('/', '/&#x200b;', $display);
    $display = str_replace('@', '@&#x200b;', $display);

    return sprintf('<span title="%s" ondblclick="alert(\'%s\');">%s</span>', htmlspecialchars($text),
        htmlspecialchars($text), $display);
}

if (!function_exists('getnicebacktrace')) {
    function getNiceBackTrace($bTrace = false)
    {
        $sTrace = '';
        $aBackTrace = debug_backtrace();
        $iMin = 0;
        if ($bTrace) {
            $iMax = count($aBackTrace) - 1;
            $iMax = count($aBackTrace);
        } else {
            $iMax = 3;
        }
        for ($iIndex = $iMin; $iIndex < $iMax; ++$iIndex) {
            if ($bTrace) {
                $sTrace .= "\n";
            }

            $sTrace .= $iIndex.sprintf('%s#%4d:%s() ',
                    pad_right($aBackTrace[$iIndex]['file'], 30),
                    $aBackTrace[$iIndex]['line'],
                    pad_right($aBackTrace[$iIndex]['function'], 15)
                );
        }

        return $sTrace;
    }
}
if (!function_exists('pad_right')) {
    function pad_right($str, $len)
    {
        $str = str_pad($str, $len, ' ', STR_PAD_LEFT);

        return substr($str, strlen($str) - $len, $len);
    }
}

function delFsTree($dir)
{
    if (empty($dir)) {
        return false;
    }
    if (!is_dir($dir) && is_file($dir)) {
        return unlink($dir); //# delete file
    }
    if (!is_dir($dir)) {
        return;
    }
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? delFsTree("$dir/$file") : unlink("$dir/$file");
    }

    return rmdir($dir);
}

function copy_recursive($source, $dest)
{
    if (is_dir($source)) {
        mkdir($dest);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->getBasename() == '..' || $item->getBasename() == '.') {
                continue;
            }
            if ($item->isDir()) {
                mkdir($dest.DIRECTORY_SEPARATOR.$iterator->getSubPathName());
            } else {
                if (!copy($item, $dest.DIRECTORY_SEPARATOR.$iterator->getSubPathName())) {
                    return false;
                }
            }
        }

        return true;
    }

    return copy($source, $dest);
}

function parsePlaceHolders($content, $array = array())
{
    //# the editor turns all non-ascii chars into the html equivalent so do that as well
    foreach ($array as $key => $val) {
        $array[htmlentities($key, ENT_QUOTES, 'UTF-8')] = $val;
        $array[str_ireplace(' ', '&nbsp;', $key)] = $val;
    }

    foreach ($array as $key => $val) {
        if (stripos($content, '['.$key.']') !== false) {
            $content = str_ireplace('['.$key.']', $val, $content);
        }
        $quoted = preg_quote($key, '/');

        if (preg_match('/\['.$quoted.'%%([^\]]+)\]/i', $content, $regs)) {
            if (isset($val) && $val != '') {
                $content = str_ireplace($regs[0], $val, $content);
            } else {
                $content = str_ireplace($regs[0], $regs[1], $content);
            }
        }
    }

    return $content;
}

function quoteEnclosed($value, $col_delim = "\t", $row_delim = "\n")
{
    $enclose = 0;
    if (strpos($value, '"') !== false) {
        $value = str_replace('"', '""', $value);
        $enclose = 1;
    }
    if (strpos($value, $col_delim) !== false) {
        $enclose = 1;
    }
    if (strpos($value, $row_delim) !== false) {
        $enclose = 1;
    }
    if ($enclose) {
        $value = '"'.$value.'"';
    }

    return $value;
}

function activateRemoteQueue()
{
    $result = '';
    $activated = file_get_contents(PQAPI_URL.'&cmd=start&key='.getConfig('PQAPIkey').'&s='.urlencode(getConfig('remote_processing_secret')).'&u='.base64_encode($GLOBALS['admin_scheme'].'://'.hostName().dirname($_SERVER['REQUEST_URI'])));
    if ($activated == 'OK') {
        $result .= '<h3>'.s('Remote queue processing has been activated successfully').'</h3>';
        $result .= '<p>'.PageLinkButton('messages&tab=active', $GLOBALS['I18N']->get('view progress')).'</p>';
    } elseif ($activated == 'KEYFAIL' || $activated == 'NAC') {
        $result .= '<h3>'.s('Error activating remote queue processing').'</h3>';
        if ($activated == 'KEYFAIL') {
            $result .= s('The API key is incorrect');
        } elseif ($activated == 'NAC') {
            $result .= s('The phpList.com server is unable to reach your phpList installation');
        } else {
            $result .= s('Unknown error');
        }
        $result .= '<p><a href="./?page=hostedprocessqueuesetup" class="button">'.s('Change settings').'</a></p>';
        $result .= '<p><a href="./?page=processqueue&pqchoice=local" class="button">'.s('Run queue locally').'</a></p>';
    } else {
        $result .= '<h3>'.s('Error activating remote queue processing').'</h3>';
        $result .= '<p><a href="./?page=processqueue&pqchoice=local" class="button">'.s('Run queue locally').'</a></p>';
    }

    return $result;
}

function subscribeToAnnouncementsForm($emailAddress = '')
{
    if (!is_email($emailAddress) && isset($_SESSION['logindetails']['id'])) {
        $emailAddress = $GLOBALS['admin_auth']->adminEmail($_SESSION['logindetails']['id']);
    }

    return '<div class="information">'
        .'<h3>'.s('Sign up to receive news and updates about phpList ').'</h3>'
        .s('Make sure you are updated with new security and feature release announcements (fewer than one message per month)').
        '<script type="text/javascript">var pleaseEnter = "'.strip_tags($emailAddress).'";</script> '.
        '<script type="text/javascript" src="../js/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="../js/phplist-subscribe-0.3.min.js"></script>
<div id="phplistsubscriberesult"></div> <form action="https://announce.hosted.phplist.com/lists/?p=subscribe&id=3" method="post" id="phplistsubscribeform">
<input type="text" name="email" value="" id="emailaddress" />
<button type="submit" id="phplistsubscribe">' .s('Subscribe').'</button> <button id="phplistnotsubscribe" class="fright">'.s('Do not subscribe').'</button></form>'
        .' </div>';
}

function createCachedLogoImage($size)
{
    $logoImageId = getConfig('organisation_logo');
    if (empty($logoImageId)) {
        return false;
    }

    $imgData = Sql_Fetch_Assoc_Query(sprintf('select data from %s where template = 0 and filename = "ORGANISATIONLOGO%d.png"',
        $GLOBALS['tables']['templateimage'], $size));
    if (!empty($imgData['data'])) {
        return true;
    }
    $imgData = Sql_Fetch_Assoc_Query(sprintf('select data from %s where id = %d and template = 0',
        $GLOBALS['tables']['templateimage'], $logoImageId));
    $imageContent = base64_decode($imgData['data']);
    if (empty($imageContent)) {
        //# fall back to a single pixel, so that there are no broken images
        $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAABGdBTUEAALGPC/xhBQAAAAZQTFRF////AAAAVcLTfgAAAAF0Uk5TAEDm2GYAAAABYktHRACIBR1IAAAACXBIWXMAAAsSAAALEgHS3X78AAAAB3RJTUUH0gQCEx05cqKA8gAAAApJREFUeJxjYAAAAAIAAUivpHEAAAAASUVORK5CYII=');
    }

    if (function_exists('getimagesizefromstring')) {
        $imgSize = getimagesizefromstring($imageContent);
        $sizeW = $imgSize[0];
        $sizeH = $imgSize[1];
        if ($sizeH > $sizeW) {
            $sizefactor = (float) ($size / $sizeH);
        } else {
            $sizefactor = (float) ($size / $sizeW);
        }
        $newwidth = (int) ($sizeW * $sizefactor);
        $newheight = (int) ($sizeH * $sizefactor);
    } else {
        $sizefactor = 1;
    }
    if ($sizefactor < 1) {
        $original = imagecreatefromstring($imageContent);
        $resized = imagecreatetruecolor($newwidth, $newheight); //# creates a black image (why would you want that....)
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255,
            127); //# white. All the methods to make it transparent didn't work for me @@TODO really make transparent
        imagefill($resized, 0, 0, $transparent);

        if (imagecopyresized($resized, $original, 0, 0, 0, 0, $newwidth, $newheight, $sizeW, $sizeH)) {
            Sql_Query(sprintf('delete from %s where template = 0 and filename = "ORGANISATIONLOGO%d.png"',
                $GLOBALS['tables']['templateimage'], $size));

            //# rather convoluted way to get the image contents
            $buffer = ob_get_contents();
            ob_end_clean();
            ob_start();
            imagepng($resized);
            $imageContent = ob_get_contents();
            ob_end_clean();
            echo $buffer;
        }
    } // else copy original
    Sql_Query(sprintf('insert into %s (template,filename,mimetype,data,width,height) values(0,"ORGANISATIONLOGO%d.png","%s","%s",%d,%d)',
        $GLOBALS['tables']['templateimage'], $size, $imgSize['mime'], base64_encode($imageContent), $newwidth,
        $newheight));

    return true;
}

function flushLogoCache()
{
    Sql_Query(sprintf('delete from %s where template = 0 and filename like "ORGANISATIONLOGO%%.png"',
        $GLOBALS['tables']['templateimage']));
}

function parseLogoPlaceholders($content)
{
    //# replace Logo placeholders
    preg_match_all('/\[LOGO\:?(\d+)?\]/', $content, $logoInstances);
    foreach ($logoInstances[0] as $index => $logoInstance) {
        $size = sprintf('%d', $logoInstances[1][$index]);
        if (!empty($size)) {
            $logoSize = $size;
        } else {
            $logoSize = '500';
        }
        createCachedLogoImage($logoSize);
        $content = str_replace($logoInstance, 'ORGANISATIONLOGO'.$logoSize.'.png', $content);
    }

    return $content;
}

/**
 * Parse [CONTACT] as HTML placeholder for VCard
 * @param $content
 * @return mixed
 */
function parseVCardHTMLPlaceholder($content) {
    preg_match_all('/\[CONTACT\:?(\d+)?\]/i', $content, $contactInstances);
    foreach ($contactInstances[0] as $index => $contactInstance) {
        $content = str_ireplace($contactInstance, '<a href="'.htmlentities(getConfig('vcardurl')).'">'.$GLOBALS['strContactMessage'].'</a>', $content);
    }

    return $content;
}

/**
 * Parse [CONTACT] as Text placeholder for VCard
 * @param $content
 * @return mixed
 */
function parseVCardTextPlaceholder($content) {
    preg_match_all('/\[CONTACT\:?(\d+)?\]/i', $content, $contactInstances);
    foreach ($contactInstances[0] as $index => $contactInstance) {
        $content = str_ireplace($contactInstance, $GLOBALS['strContactMessage'].' '.htmlentities(getConfig('vcardurl')), $content);
    }

    return $content;
}

/**
 * Loop through a multi-dimensional array, check a particular child array
 * key equals desired value, and return a new multi-dimensional array of those
 * child arrays which qualify.
 *
 * @param array  $parentArray   Multi-dimensional array to check
 * @param string $requiredKey   Key to check
 * @param string $requiredValue Required value for qualification
 */
function multiArrayFilterBy(array $parentArray, $requiredKey, $requiredValue)
{
    // Initialise empty array for storing qualifying child arrays
    $parentArraySubset = array();
    // Loop through parent arrays
    foreach ($parentArray as $key => $childArray) {
        // Check if child array key value matches required value
        if (arrayKeyHasValue($childArray, $requiredKey, $requiredValue)) {
            // Values match, add to qualified array
            $parentArraySubset[$key] = $childArray;
        }
    }

    return $parentArraySubset;
}

/**
 * Loop through a multi-dimensional array, check a particular child array
 * key **does not equal** desired value, and return a new multi-dimensional array
 * of those child arrays which qualify.
 *
 * @param array  $parentArray    Multi-dimensional array to check
 * @param string $requiredKey    Key to check
 * @param string $forbiddenValue Required value for qualification
 */
function multiArrayFilterByNot(array $parentArray, $requiredKey, $forbiddenValue)
{
    // Initialise empty array for storing qualifying child arrays
    $parentArraySubset = array();
    // Loop through parent arrays
    foreach ($parentArray as $key => $childArray) {
        if (arrayKeyHasNotValue($childArray, $requiredKey, $forbiddenValue)) {
            // Values match, add to qualified array
            $parentArraySubset[$key] = $childArray;
        }
    }

    return $parentArraySubset;
}

/**
 * Check that the value of a given array key matches a particular value.
 *
 * @param array  $array         array to check
 * @param string $requiredKey   Key to check
 * @param string $requiredValue Required value
 */
function arrayKeyHasValue(array $array, $requiredKey, $requiredValue)
{
    // Check if array key value matches required value
    if ($array[$requiredKey] == $requiredValue) {
        return true;
    } else {
        return false;
    }
}

/**
 * Check that the value of a given array key does not match a particular value.
 *
 * @param array  $array          array to check
 * @param string $requiredKey    Key to check
 * @param string $forbiddenValue Forbidden value
 */
function arrayKeyHasNotValue(array $array, $requiredKey, $forbiddenValue)
{
    // Check if array key value matches required value
    if ($array[$requiredKey] != $forbiddenValue) {
        return true;
    } else {
        return false;
    }
}

function asyncLoadContent($url)
{
    return '<script type="text/javascript">
        var loadMessage = \'' .sjs('Please wait, your request is being processed. Do not refresh this page.').'\';
        var loadMessages = new Array();
        loadMessages[30] = \'' .sjs('Still loading').'\';
        loadMessages[90] = \'' .sjs('It may seem to take a while, but there is a lot of data to crunch<br/>if you have a lot of subscribers and campaigns').'\';
        loadMessages[150] = \'' .sjs('It should be soon now, your page content is almost here.').'\';
        loadMessages[210] = \'' .sjs('This seems to take longer than expected, looks like there is a lot of data to work on.').'\';
        loadMessages[240] = \'' .sjs('Still loading, please be patient, your page content will show shortly.').'\';
        loadMessages[300] = \'' .sjs('It will really be soon now until the page will display.').'\';
        loadMessages[360] = \'' .sjs('Still loading, please wait').'\';
        loadMessages[420] = \'' .sjs('The loading has been just over seven minutes. We can wait just a little longer.').'\';
        loadMessages[500] = \'' .sjs('If the page does not load soon, please report this in the user forums.').'\';
        var contentdivcontent = "'.$url.'";
     </script>';
}

/**
 * load content in a div after loading
 */
function asyncLoadContentDiv($url,$divname)
{

    // annoying how associate arrays don't seem to work

    return '<script type="text/javascript">
        if (typeof asyncLoadDiv == "undefined") {
            var asyncLoadDiv = new Array();
            var asyncLoadUrl = new Array();
        }
        asyncLoadDiv[asyncLoadDiv.length] = "'.$divname.'";
        asyncLoadUrl[asyncLoadUrl.length] = "'.$url.'";
     </script>';
}

/**
 * Transform a value to be valid for an html id by removing invalid characters.
 * This is for HTML 4. HTML 5 is more lenient.
 *
 * @see https://www.w3.org/TR/html4/types.html#type-id
 *
 * @param string $value
 *
 * @return string
 */
function sanitiseId($value)
{
    return preg_replace('/[^0-9A-Za-z\-_:.]/', '', $value);
}
