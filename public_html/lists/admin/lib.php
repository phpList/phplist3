<?php
require_once dirname(__FILE__)."/accesscheck.php";
# library used for plugging into the webbler, instead of "connect"
# depricated and should be removed
#error_reporting(63);
include_once dirname(__FILE__).'/class.phplistmailer.php';

$domain = getConfig("domain");
$website = getConfig("website");
if (!$GLOBALS["message_envelope"]) {
  # why not try set it to "person in charge of this system". Will help get rid of a lot of bounces to nobody@server :-)
  $admin = getConfig('admin_address');
  if (!empty($admin)) {
    $GLOBALS["message_envelope"] = $admin;
  }
}
$GLOBALS['homepage'] = 'home';

if (defined("IN_WEBBLER") && is_object($GLOBALS["config"]["plugins"]["phplist"])) {
  $GLOBALS["tables"] = $GLOBALS["config"]["plugins"]["phplist"]->tables;
}

$usephpmailer = 0;
if (PHPMAILER) {
  if (is_file(PHPMAILER_PATH)) {
    include_once PHPMAILER_PATH;
  } else {
    ## fall back to old version, should be phased out
    include_once dirname(__FILE__).'/class.phplistmailer.php';
  }
  $usephpmailer = 1;  
} else {
  require_once dirname(__FILE__)."/class.html.mime.mail.inc";
}

$GLOBALS['bounceruleactions'] = array(
  'deleteuser' => $GLOBALS['I18N']->get('delete user'),
  'unconfirmuser' => $GLOBALS['I18N']->get('unconfirm user'),
  'blacklistuser' => $GLOBALS['I18N']->get('blacklist user'),
  'deleteuserandbounce' => $GLOBALS['I18N']->get('delete user and bounce'),
  'unconfirmuseranddeletebounce' => $GLOBALS['I18N']->get('unconfirm user and delete bounce'),
  'blacklistuseranddeletebounce' => $GLOBALS['I18N']->get('blacklist user and delete bounce'),
  'deletebounce' => $GLOBALS['I18N']->get('delete bounce'),
);

if( !isset($GLOBALS["developer_email"]) ) {
  ini_set('error_append_string','phplist version '.VERSION);
  ini_set('error_prepend_string','<p class="error">Sorry a software error occurred:<br/>
    Please <a href="http://mantis.phplist.com">report a bug</a> when reporting the bug, please include URL and the entire content of this page.<br/>');
}

function listName($id) {
  global $tables;
  $req = Sql_Fetch_Row_Query(sprintf('select name from %s where id = %d',$tables["list"],$id));
  return $req[0] ? stripslashes($req[0]) : $GLOBALS['I18N']->get('Unnamed List');
}

function setMessageData($msgid,$name,$value) {
  if ($name == 'PHPSESSID') return;
  
  if ($name == 'targetlist' && is_array($value))  {
    Sql_query(sprintf('delete from %s where messageid = %d',$GLOBALS['tables']["listmessage"],$msgid));
    if ( !empty($value["all"]) || !empty($value["allactive"])) {
      $res = Sql_query('select * from '. $GLOBALS['tables']['list']. ' '.$GLOBALS['subselect']);
      while ($row = Sql_Fetch_Array($res))  {
        $listid  =  $row["id"];
        if ($row["active"] || !empty($value["all"]))  {
          $result  =  Sql_query("insert ignore into ".$GLOBALS['tables']["listmessage"]."  (messageid,listid,entered) values($msgid,$listid,current_timestamp)");
        }
      }
    } else {
      foreach($value as $listid => $val) {
        $query
        = ' insert into ' . $GLOBALS['tables']["listmessage"]
        . '    (messageid,listid,entered)'
        . ' values'
        . '    (?, ?, current_timestamp)';
        $result = Sql_Query_Params($query, array($msgid, $listid));
      }
    }
  }
  if (is_array($value) || is_object($value)) {
    $value = 'SER:'.serialize($value);
  }
  
  Sql_Replace($GLOBALS['tables']['messagedata'], array('id' => $msgid, 'name' => $name, 'data' => $value), array('name', 'id'));
#  print "<br/>setting $name for $msgid to $value";
#  exit;
}

function loadMessageData($msgid) {
  $default_from = getConfig('message_from_address');
  if (empty($default_from)) {
    $default_from = getConfig('admin_address');
  }
   
  if (!isset($GLOBALS['MD']) || !is_array($GLOBALS['MD'])) {
    $GLOBALS['MD'] = array();
  }
  if (isset($GLOBALS['MD'][$msgid])) return $GLOBALS['MD'][$msgid];
  
  ## when loading an old message that hasn't got data stored in message data, load it from the message table
  $prevMsgData = Sql_Fetch_Assoc_Query(sprintf('select * from %s where id = %d',
    $GLOBALS['tables']['message'],$msgid));

  $finishSending = time() + DEFAULT_MESSAGEAGE;

  $messagedata = array(
    'template' => getConfig("defaultmessagetemplate"),
    'sendformat' => 'HTML',
    'message' => '',
    'forwardmessage' => '',
    'textmessage' => '',
    'rsstemplate' => '',
    'embargo' => array('year' => date('Y'),'month' => date('m'),'day' => date('d'),'hour' => date('H'),'minute' => date('i')),
    'repeatinterval' => 0,
    'repeatuntil' =>  array('year' => date('Y'),'month' => date('m'),'day' => date('d'),'hour' => date('H'),'minute' => date('i')),
    'requeueinterval' => 0,
    'requeueuntil' =>  array('year' => date('Y'),'month' => date('m'),'day' => date('d'),'hour' => date('H'),'minute' => date('i')),
    'finishsending' => array('year' => date('Y',$finishSending),'month' => date('m',$finishSending),'day' => date('d',$finishSending),'hour' => date('H',$finishSending),'minute' => date('i',$finishSending)),
    'fromfield' => '',
    'subject' => '',
    'forwardsubject' => '',
    'footer' => getConfig("messagefooter"),
    'forwardfooter' => getConfig("forwardfooter"),
    'status' => '',
    'tofield' => '',
    'replyto' => '',
    'targetlist' => '',
    'criteria_match' => '',
    'sendurl' => '',
    'sendmethod' => 'inputhere', ## make a config
    'testtarget' => '',
    'notify_start' =>  getConfig("notifystart_default"),
    'notify_end' =>  getConfig("notifyend_default"),
    'google_track' => getConfig('always_add_googletracking') == 'true',
    'excludelist' => array(),
  );
  if (is_array($prevMsgData)) {
    foreach ($prevMsgData as $key => $val) {
      $messagedata[$key] = $val;
    }
  }
  
  $msgdata_req = Sql_Query(sprintf('select * from %s where id = %d',
    $GLOBALS['tables']['messagedata'],$msgid));
  while ($row = Sql_Fetch_Assoc($msgdata_req)) {
    if (strpos($row['data'],'SER:') === 0) {
      $data = substr($row['data'],4);
      $data = @unserialize(stripslashes($data));
    } else {
      $data = stripslashes($row['data']);
    }
    if (!in_array($row['name'],array('astext','ashtml','astextandhtml','aspdf','astextandpdf'))) { ## don't overwrite counters in the message table from the data table
      $messagedata[stripslashes($row['name'])] = $data;
    }
  }
  
  foreach (array('embargo','repeatuntil','requeueuntil') as $datefield) {
    if (!is_array($messagedata[$datefield])) {
      $messagedata[$datefield] = array('year' => date('Y'),'month' => date('m'),'day' => date('d'),'hour' => date('H'),'minute' => date('i'));
    }
  }
  
  // Load lists that were targetted with message...
  $result = Sql_Query(sprintf('select list.name,list.id 
    from '.$GLOBALS['tables']['listmessage'].' listmessage,'.$GLOBALS['tables']['list'].' list
     where listmessage.messageid = %d and listmessage.listid = list.id',$msgid));
  while ($lst = Sql_fetch_array($result)) {
    $messagedata["targetlist"][$lst["id"]] = 1;
  }
  
  ## backwards, check that the content has a url and use it to fill the sendurl
  if (empty($messagedata['sendurl'])) {

    ## can't do "ungreedy matching, in case the URL has placeholders, but this can potentially
    ## throw problems
    if (preg_match('/\[URL:(.*)\]/i',$messagedata['message'],$regs)) {
      $messagedata['sendurl'] = $regs[1];
    }
  }
  if (empty($messagedata['sendurl']) && !empty($messagedata['message'])) {
    # if there's a message and no url, make sure to show the editor, and not the URL input
    $messagedata['sendmethod'] = 'inputhere';
  }

  ### parse the from field into it's components - email and name
  if (preg_match("/([^ ]+@[^ ]+)/",$messagedata["fromfield"],$regs)) {
    # if there is an email in the from, rewrite it as "name <email>"
    $messagedata["fromname"] = str_replace($regs[0],"",$messagedata["fromfield"]);
    $messagedata["fromemail"] = $regs[0];
    # if the email has < and > take them out here
    $messagedata["fromemail"] = str_replace("<","",$messagedata["fromemail"]);
    $messagedata["fromemail"] = str_replace(">","",$messagedata["fromemail"]);
    # make sure there are no quotes around the name
    $messagedata["fromname"] = str_replace('"',"",ltrim(rtrim($messagedata["fromname"])));
  } elseif (strpos($messagedata["fromfield"]," ")) {
    # if there is a space, we need to add the email
    $messagedata["fromname"] = $messagedata["fromfield"];
  #  $cached[$messageid]["fromemail"] = "listmaster@$domain";
    $messagedata["fromemail"] = $default_from;
  } else {
    $messagedata["fromemail"] = $default_from;
    $messagedata["fromname"] = $messagedata["fromfield"] ;
  }
  $messagedata["fromname"] = trim($messagedata["fromname"]);

  # erase double spacing 
  while (strpos($messagedata["fromname"],"  ")) {
    $messagedata["fromname"] = str_replace("  "," ",$messagedata["fromname"]);
  }
  
  ## if the name ends up being empty, copy the email
  if (empty($messagedata["fromname"])) {
    $messagedata["fromname"] = $messagedata["fromemail"];
  }

  ## this has weird effects when used with only one word, so take it out for now
#    $cached[$messageid]["fromname"] = eregi_replace("@","",$cached[$messageid]["fromname"]);

  $GLOBALS['MD'][$msgid] = $messagedata;
#  var_dump($messagedata);
  return $messagedata;
}

function HTMLselect ($name, $table, $column, $value) {
  $res = "<!--$value--><select name=$name>\n";
  $result = Sql_Query("SELECT id,$column FROM $table");
  while($row = Sql_Fetch_Array($result)) {
    $res .= "<option value=".$row["id"] ;
    if ($row["$column"] == $value)
      $res .= 'selected="selected"';
    if ($row["id"] == $value)
      $res .= 'selected="selected"';
    $res .= ">" . $row[$column] . "\n";
  }
  $res .= "</select>\n";
  return $res;
}

#Send email with a random encrypted token.
function sendAdminPasswordToken ($adminId){
  #Retrieve the admin login name.
  $SQLquery = sprintf('select loginname,email from %s where id=%d;', $GLOBALS['tables']['admin'], $adminId);
  $row = Sql_Fetch_Row_Query($SQLquery);
  $adminName = $row[0];
  $email = $row[1];
  #Check if the token is not present in the database yet.  
  while(1){
    #Randomize the token to be encrypted and insert it into the db.
    $date = date("U"); $random = rand(1, $date);
    $key = md5($date ^ $random);
  	$SQLquery = sprintf("select * from %s where key_value = '%s'", $GLOBALS['tables']['admin_password_request'], $key);
  	$row = Sql_Fetch_Row_Query($SQLquery);
  	//echo "<script text='javascript'>alert('".($row[0]=='')."');</script>";
  	if($row[0]=='') break;
  }
  $query = sprintf("insert into %s(date, admin, key_value) values (now(), %d, '%s');", $GLOBALS['tables']['admin_password_request'], $adminId, $key);
  Sql_Query($query);
  $urlroot = getConfig('website').$GLOBALS['adminpages'];
  #Build the email body to be sent, and finally send it.
  $emailBody = $GLOBALS['I18N']->get('Hello').' '.$adminName."\n\n";
  $emailBody.= $GLOBALS['I18N']->get('You have requested a new password for phpList.')."\n\n";
  $emailBody.= $GLOBALS['I18N']->get('To enter a new one, please visit the following link:')."\n\n";
  $emailBody.= sprintf('http://%s/?page=login&token=%s',$urlroot, $key)."\n\n";
  $emailBody.= $GLOBALS['I18N']->get('You have 24 hours left to change your password. After that, your token won\'t be valid.');
  if (sendMail ($email, $GLOBALS['I18N']->get('New password'), "\n\n".$emailBody)) {
    return $GLOBALS['I18N']->get('A password change token has been sent to the corresponding email address.');
  } else {
    return $GLOBALS['I18N']->get('Error sending password change token');
  }
    
}

function sendMail ($to,$subject,$message,$header = "",$parameters = "",$skipblacklistcheck = 0) {
  if (TEST)
    return 1;

  # do a quick check on mail injection attempt, @@@ needs more work
  if (preg_match("/\n/",$to)) {
    logEvent("Error: invalid recipient, containing newlines, email blocked");
    return 0;
  }
  if (preg_match("/\n/",$subject)) {
    logEvent("Error: invalid subject, containing newlines, email blocked");
    return 0;
  }

  if (!$to)  {
    logEvent("Error: empty To: in message with subject $subject to send");
    return 0;
  } elseif (!$subject) {
    logEvent("Error: empty Subject: in message to send to $to");
    return 0;
  }
  if (!$skipblacklistcheck && isBlackListed($to)) {
    logEvent("Error, $to is blacklisted, not sending");
    Sql_Query(sprintf('update %s set blacklisted = 1 where email = "%s"',$GLOBALS["tables"]["user"],$to));
    addUserHistory($to,"Marked Blacklisted","Found user in blacklist while trying to send an email, marked black listed");
    return 0;
  }
  if ($GLOBALS['usephpmailer']) {
    return sendMailPhpMailer($to,$subject,$message);
  } else {
    return sendMailOriginal($to,$subject,$message,$header,$parameters);
  }
  return 0;
}


function sendMailOriginal ($to,$subject,$message,$header = "",$parameters = "") {
  # global function to capture sending emails, to avoid trouble with
  # older (and newer!) php versions
  $v = phpversion();
  $v = preg_replace("/\-.*$/","",$v);
  if ($GLOBALS["message_envelope"]) {
    $header = rtrim($header);
    if ($header)
      $header .= "\n";
    $header .= "Errors-To: ".$GLOBALS["message_envelope"];
    if (!$parameters || !ereg("-f".$GLOBALS["message_envelope"],$parameters)) {
      $parameters = '-f'.$GLOBALS["message_envelope"];
    }
  }

  // Use the system email encoding method
  if (TEXTEMAIL_ENCODING) {
    // only add if the required header is not already present
    if (!strpos(strtolower($header), 'content-transfer-encoding')) {
      $header = rtrim($header);
      if ($header)
        $header .= "\n";
      $header .= "Content-Transfer-Encoding: " . TEXTEMAIL_ENCODING;
    }
  }

  if (WORKAROUND_OUTLOOK_BUG) {
    $header = rtrim($header);
    if ($header)
      $header .= "\n";
     $header .= "X-Outlookbug-fixed: Yes";
    $message = preg_replace("/\r?\n/", "\r\n", $message);
  }

  # version 4.2.3 (and presumably up) does not allow the fifth parameter in safe mode
  # make sure not to send out loads of test emails to ppl when developing
  if (!DEVVERSION) {
    if ($v > "4.0.5" && !ini_get("safe_mode")) {
      if (mail($to,$subject,$message,$header,$parameters))
        return 1;
      else
        return mail($to,$subject,$message,$header);
    }
    else
      return mail($to,$subject,$message,$header);
  } else {
    # send mails to one place when running a test version
    $message = "To: $to\n".$message;
    if ($GLOBALS["developer_email"]) {
      # fake occasional failure
      if (mt_rand(0,50) == 1) {
        return 0;
      } else {
        if(@mail($GLOBALS["developer_email"],$subject,$message,$header,$parameters)) {
          return 1;
        } else {
          # Changed by Bas: Always ok, since the mac/xampp return false while sending and no error in /var/log/mail.log
          # We are in developermode anyway, and errors are faked by code just above this.
          mail($GLOBALS["developer_email"],$subject,$message,$header);
          return 1;
        }
      }
    } else {
      print "Error: Running DEV version, but developer_email not set";
    }
  }
}

function sendMailPhpMailer ($to,$subject,$message) {
  # global function to capture sending emails, to avoid trouble with
  # older (and newer!) php versions
  $fromemail = getConfig("message_from_address");
  $fromname = getConfig("message_from_name");
  $message_replyto_address = getConfig("message_replyto_address");
  if ($message_replyto_address)
    $reply_to = $message_replyto_address;
  else
    $reply_to = $from_address;
  $destinationemail = '';

#  print "Sending $to from $fromemail<br/>";
  if (!DEVVERSION) {
    $mail = new PHPlistMailer('systemmessage',$to,false);
    $destinationemail = $to;

    $hasHTML = strip_tags($message) != $message;

    if ($hasHTML) {
      $message = stripslashes($message);
      $textmessage = HTML2Text($message);
      $htmlmessage = $message;
    } else {
      $textmessage = $message;
      $htmlmessage = $message;
    #  $htmlmessage = str_replace("\n\n","\n",$htmlmessage);
      $htmlmessage = nl2br($htmlmessage);
      ## make links clickable:
      preg_match_all('~https?://[^\s<]+~i',$htmlmessage,$matches);
      for ($i=0; $i<sizeof($matches[0]);$i++) {
        $match = $matches[0][$i];
        $htmlmessage = str_replace($match,'<a href="'.$match.'">'.$match.'</a>',$htmlmessage);
      }
    }
    ## add li-s around the lists
    if (preg_match('/<ul>\s+(\*.*)<\/ul>/imsxU',$htmlmessage,$listsmatch)) {
      $lists = $listsmatch[1];
      $listsHTML = '';
      preg_match_all('/\*([^\*]+)/',$lists,$matches);
      for ($i=0;$i<sizeof($matches[0]);$i++) {
        $listsHTML .= '<li>'.$matches[1][$i].'</li>';
      }
      $htmlmessage = str_replace($listsmatch[0],'<ul>'.$listsHTML.'</ul>',$htmlmessage);
/*
      print "<h1>MATCH</h1>";
*/
    }
/*
    } else {
      print "<h1>NO MATCH</h1>";
      print htmlspecialchars($htmlmessage); exit;
    }
*/

    $templateid = getConfig('systemmessagetemplate');
    if (!empty($templateid)) {
      $req = Sql_Fetch_Row_Query(sprintf('select template from %s where id = %d',
        $GLOBALS["tables"]["template"],$templateid));
      $htmltemplate = stripslashes($req[0]);
    }
    if (strpos($htmltemplate,'[CONTENT]')) {
      $htmlcontent = str_replace('[CONTENT]',$htmlmessage,$htmltemplate);
      $htmlcontent = str_replace('[SUBJECT]',$subject,$htmlcontent);
      $htmlcontent = str_replace('[FOOTER]','',$htmlcontent);
      if (!EMAILTEXTCREDITS) {
        $phpListPowered = preg_replace('/src=".*power-phplist.png"/','src="powerphplist.png"',$GLOBALS['PoweredByImage']);
      } else {
        $phpListPowered = $GLOBALS['PoweredByText'];
      }
      if (strpos($htmlcontent,'[SIGNATURE]')) {
        $htmlcontent = str_replace('[SIGNATURE]',$phpListPowered,$htmlcontent);
      } elseif (strpos($htmlcontent,'</body>')) {
        $htmlcontent = str_replace('</body>',$phpListPowered.'</body>',$htmlcontent);
      } else {
        $htmlcontent .= $phpListPowered;
      }
      $mail->add_html($htmlcontent,$textmessage,$templateid);
      ## In the above phpMailer strips all tags, which removes the links which are wrapped in < and > by HTML2text
      ## so add it again
      $mail->add_text($textmessage);
    } 
    $mail->add_text($textmessage);
  } else {
    # send mails to one place when running a test version
    $message = "To: $to\n".$message;
    if ($GLOBALS["developer_email"]) {
      # fake occasional failure
      if (mt_rand(0,50) == 1) {
        return 0;
      } else {
        $mail = new PHPlistMailer('systemmessage',$to,false);
        $mail->add_text($message);
        $destinationemail = $GLOBALS["developer_email"];
      }
    } else {
      print "Error: Running DEV version, but developer_email not set";
    }
  }
  # 0008549: message envelope not passed to php mailer,
  $mail->Sender = $GLOBALS["message_envelope"];

  $mail->build_message(
      array(
        "html_charset" => getConfig("html_charset"),
        "html_encoding" => HTMLEMAIL_ENCODING,
        "text_charset" => getConfig("text_charset"),
        "text_encoding" => TEXTEMAIL_ENCODING)
      );
  return $mail->send("", $destinationemail, $fromname, $fromemail, $subject);
}

function sendAdminCopy($subject,$message,$lists = array()) {
  $sendcopy = getConfig("send_admin_copies");
  if ($sendcopy == "true") {
    $lists = cleanArray($lists);
    $mails = array();
    if (sizeof($lists) && SEND_LISTADMIN_COPY) {
      $mailsreq = Sql_Query(sprintf('select email from %s admin, %s list where admin.id = list.owner and list.id in (%s)',
        $GLOBALS['tables']['admin'],$GLOBALS['tables']['list'],join(',',$lists)));
      while ($row = Sql_Fetch_Array($mailsreq)) {
        array_push($mails,$row['email']);
      }
    }
    if (!sizeof($mails)) {
      $admin_mail = getConfig("admin_address");
      $mails = explode(",",getConfig("admin_addresses"));
      array_push($mails,$admin_mail);
    }
    $sent = array();
    foreach ($mails as $admin_mail) {
      $admin_mail = trim($admin_mail);
      if ( !isset($sent[$admin_mail]) && isset($admin_mail) ) {
        sendMail($admin_mail,$subject,$message,system_messageheaders($admin_mail));
        logEvent('Sending admin copy to '.$admin_mail);
        $sent[$admin_mail] = 1;
       }
     }
  }
}

function safeImageName($name) {
  $name = "image".str_replace(".","DOT",$name);
  $name = str_replace("-","DASH",$name);
  $name = str_replace("_","US",$name);
  $name = str_replace("/","SLASH",$name);
  $name = str_replace(':','COLON',$name);
  return $name;
}

function clean2 ($value) {
  $value = trim($value);
  $value = preg_replace("/\r/","",$value);
  $value = preg_replace("/\n/","",$value);
  $value = str_replace('"',"&quot;",$value);
  $value = str_replace("'","&rsquo;",$value);
  $value = str_replace("`","&lsquo;",$value);
  $value = stripslashes($value);
  return $value;
}

function cleanEmail ($value) {
  $value = trim($value);
  $value = preg_replace("/\r/","",$value);
  $value = preg_replace("/\n/","",$value);
  $value = preg_replace('/"/',"&quot;",$value);
  ## these are allowed in emails
//  $value = preg_replace("/'/","&rsquo;",$value);
  $value = preg_replace("/`/","&lsquo;",$value);
  $value = stripslashes($value);
  return $value;
}

if (TEST && REGISTER)
  $pixel = '<img src="http://powered.phplist.com/images/pixel.gif" width="1" height="1" />';


function timeDiff($time1,$time2) {
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
  if ($diff == 0)
    return $GLOBALS['I18N']->get('very little time');
  return secs2time($diff);
}


function previewTemplate($id,$adminid = 0,$text = "", $footer = "") {
  global $tables;
  if (defined("IN_WEBBLER")) {
    $more = '&amp;pi='.$_GET["pi"];
  } else {
    $more = '';
  }
  $tmpl = Sql_Fetch_Row_Query(sprintf('select template from %s where id = %d',$tables["template"],$id));
  $template = stripslashes($tmpl[0]);
  $img_req = Sql_Query(sprintf('select id,filename from %s where template = %d order by filename desc',$tables["templateimage"],$id));
  while ($img = Sql_Fetch_Array($img_req)) {
    $template = preg_replace("#".preg_quote($img["filename"])."#","?page=image&amp;id=".$img["id"].$more,$template);
  }
  if ($adminid) {
    $att_req = Sql_Query("select name,value from {$tables["adminattribute"]},{$tables["admin_attribute"]} where {$tables["adminattribute"]}.id = {$tables["admin_attribute"]}.adminattributeid and {$tables["admin_attribute"]}.adminid = $adminid");
    while ($att = Sql_Fetch_Array($att_req)) {
      $template = preg_replace("#\[LISTOWNER.".strtoupper(preg_quote($att["name"]))."\]#",$att["value"],$template);
    }
  }
  if ($footer) {
    $template = str_ireplace("[FOOTER]",$footer,$template);
  }
  $template = preg_replace("#\[CONTENT\]#",$text,$template);
  $template = str_ireplace("[UNSUBSCRIBE]",sprintf('<a href="%s">%s</a>',getConfig("unsubscribeurl"),$GLOBALS["strThisLink"]),$template);
  #0013076: Blacklisting posibility for unknown users
  $template = str_ireplace("[BLACKLIST]",sprintf('<a href="%s">%s</a>',getConfig("blacklisturl"),$GLOBALS["strThisLink"]),$template);
  $template = str_ireplace("[PREFERENCES]",sprintf('<a href="%s">%s</a>',getConfig("preferencesurl"),$GLOBALS["strThisLink"]),$template);
  if (!EMAILTEXTCREDITS) {
    $template = str_ireplace("[SIGNATURE]",$GLOBALS["PoweredByImage"],$template);
  } else {
    $template = str_ireplace("[SIGNATURE]",$GLOBALS["PoweredByText"],$template);
  }
  $template = preg_replace("/\[[A-Z\. ]+\]/","",$template);
  $template = str_ireplace('<form','< form',$template);
  $template = str_ireplace('</form','< /form',$template);

  return $template;
}


function parseMessage($content,$template,$adminid = 0) {
  global $tables;
  $tmpl = Sql_Fetch_Row_Query("select template from {$tables["template"]} where id = $template");
  $template = $tmpl[0];
  $template = preg_replace("#\[CONTENT\]#",$content,$template);
  $att_req = Sql_Query("select name,value from {$tables["adminattribute"]},{$tables["admin_attribute"]} where {$tables["adminattribute"]}.id = {$tables["admin_attribute"]}.adminattributeid and {$tables["admin_attribute"]}.adminid = $adminid");
  while ($att = Sql_Fetch_Array($att_req)) {
    $template = preg_replace("#\[LISTOWNER.".strtoupper(preg_quote($att["name"]))."\]#",$att["value"],$template);
  }
  return $template;
}

function listOwner($listid = 0) {
  global $tables;
  $req = Sql_Fetch_Row_Query("select owner from {$tables["list"]} where id = $listid");
  return $req[0];
}

function system_messageHeaders($useremail = "") {
  $from_address = getConfig("message_from_address");
  $from_name = getConfig("message_from_name");
  if ($from_name)
    $additional_headers = "From: \"$from_name\" <$from_address>\n";
  else
    $additional_headers = "From: $from_address\n";
  $message_replyto_address = getConfig("message_replyto_address");
  if ($message_replyto_address)
    $additional_headers .= "Reply-To: $message_replyto_address\n";
  else
    $additional_headers .= "Reply-To: $from_address\n";
  $v = VERSION;
  $additional_headers .= "X-Mailer: phplist version $v (www.phplist.com)\n";
  $additional_headers .= "X-MessageID: systemmessage\n";
  if ($useremail)
    $additional_headers .= "X-User: ".$useremail."\n";
  return $additional_headers;
}

function logEvent($msg) {

  $logged = false;
  foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
    $logged = $logged || $plugin->logEvent($msg);
  }
  if ($logged) return;
  
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
  if (!Sql_Table_Exists($tables["eventlog"])) {
    return;
  }

  $query
  = ' insert into %s'
  . '    (entered,page,entry)'
  . ' values'
  . '    (current_timestamp, ?, ?)';
  $query = sprintf($query, $tables["eventlog"]);
  Sql_Query_Params($query, array($p, $msg));
}

### process locking stuff
function getPageLock($force = 0) {
  global $tables;
  $thispage = $GLOBALS["page"];
  if ($thispage == 'pageaction') {
    $thispage = $_GET['action'];
  }
  
  if ($GLOBALS["commandline"] && $thispage == 'processqueue') {
    $max = MAX_SENDPROCESSES;
  } else {
    $max = 1;
  }
  
  ## allow killing other processes
  if ($force) {
    Sql_Query_Params("delete from ".$tables['sendprocess']." where page = ?",array($thispage));
  }

  $query
  = ' select current_timestamp - modified as age, id'
  . ' from ' . $tables['sendprocess']
  . ' where page = ?'
  . ' and alive > 0'
  . ' order by age desc';
  $running_req = Sql_Query_Params($query, array($thispage));
  $running_res = Sql_Fetch_Assoc($running_req);
  $count = Sql_Num_Rows($running_req);
  $waited = 0;
  while ($running_res['age'] && $count >= $max) { # a process is already running
    if ($running_res['age'] > 600) {# some sql queries can take quite a while
      # process has been inactive for too long, kill it
      Sql_query("update {$tables["sendprocess"]} set alive = 0 where id = ".$running_res['id']);
    } elseif ($count >= $max) {
      output (sprintf($GLOBALS['I18N']->get('A process for this page is already running and it was still alive %s seconds ago'),$running_res['age']));
      sleep(1); # to log the messages in the correct order
      if ($GLOBALS["commandline"]) {
        cl_output(s("Running commandline, quitting. We'll find out what to do in the next run."));
        exit;
      }
      output ($GLOBALS['I18N']->get('Sleeping for 20 seconds, aborting will quit'));
      flush();
      $abort = ignore_user_abort(0);
      sleep(20);
    }
    $waited++;
    if ($waited > 10) {
      # we have waited 10 cycles, abort and quit script
      output($GLOBALS['I18N']->get('We have been waiting too long, I guess the other process is still going ok'));
      exit;
    }
    $query
    = ' select current_timestamp - modified as age, id'
    . ' from ' . $tables['sendprocess']
    . ' where page = ?'
    . ' and alive > 0'
    . ' order by age desc';
    $running_req = Sql_Query_Params($query, array($thispage));
    $running_res = Sql_Fetch_Assoc($running_req);
    $count = Sql_Num_Rows($running_req);
  }
  $query
  = ' insert into ' . $tables['sendprocess']
  . '    (started, page, alive, ipaddress)'
  . ' values'
  . '    (current_timestamp, ?, 1, ?)';
  
  if (!empty($GLOBALS['commandline'])) {
    $processIdentifier = SENDPROCESS_ID;
  } else {
    $processIdentifier = $_SERVER['REMOTE_ADDR'];
  }
  
  $res = Sql_Query_Params($query, array($thispage, $processIdentifier));
  $send_process_id = Sql_Insert_Id($tables['sendprocess'], 'id');
  $abort = ignore_user_abort(1);
  return $send_process_id;
}

function keepLock($processid) {
  global $tables;
  $thispage = $GLOBALS["page"];
  Sql_query("Update ".$tables["sendprocess"]." set alive = alive + 1 where id = $processid");
}

function checkLock($processid) {
  global $tables;
  $thispage = $GLOBALS["page"];
  $res = Sql_query("select alive from {$tables['sendprocess']} where id = $processid");
  $row = Sql_Fetch_Row($res);
  return $row[0];
}

// function addAbsoluteResources() moved to commonlib/maillib.php

function getPageCache($url,$lastmodified = 0) {
  $req = Sql_Fetch_Row_Query(sprintf('select content from %s where url = "%s" and lastmodified >= %d',$GLOBALS["tables"]["urlcache"],$url,$lastmodified));
  return $req[0];
}

function getPageCacheLastModified($url) {
  $req = Sql_Fetch_Row_Query(sprintf('select lastmodified from %s where url = "%s"',$GLOBALS["tables"]["urlcache"],$url));
  return $req[0];
}

function setPageCache($url,$lastmodified = 0,$content) {
#  if (isset($GLOBALS['developer_email'])) return;
  Sql_Query(sprintf('delete from %s where url = "%s"',$GLOBALS["tables"]["urlcache"],$url));
  Sql_Query(sprintf('insert into %s (url,lastmodified,added,content)
    values("%s",%d,current_timestamp,"%s")',$GLOBALS["tables"]["urlcache"],$url,$lastmodified,addslashes($content)));
}

function clearPageCache () {
  Sql_Query('delete from ' . $GLOBALS['tables']['urlcache']);
  unset($GLOBALS['urlcache']);
}

function removeJavascript($content) {
  $content = preg_replace('/<script[^>]*>(.*?)<\/script\s*>/mis','',$content);
  return $content;
}

function stripComments($content) {
 $content = preg_replace('/<!--(.*?)-->/mis','',$content);
  return $content;
}

function compressContent($content) {

  ## this needs loads more testing across systems to be sure
  return $content;
  
  $content = preg_replace("/\n/",' ',$content);
  $content = preg_replace("/\r/",'',$content);
  $content = removeJavascript($content);
  $content = stripComments($content);

  ## find some clean way to remove double spacing
  $content = preg_replace("/\t/"," ",$content);
  while (preg_match("/  /",$content)) {
    $content = preg_replace("/  /"," ",$content);
  }
  return $content;
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
function isValidRedirect($url) {
  ## we might want to add some more checks here
  return strpos($url,$_SERVER['HTTP_HOST']);
}

/* check the url_append config and expand the url with it
 */

function expandURL($url) {
  $url_append = getConfig('remoteurl_append');
  $url_append = strip_tags($url_append);
  $url_append = preg_replace('/\W/','',$url_append);
  if ($url_append) {
    if (strpos($url,'?')) {
      $url = $url.$url_append;
    } else {
      $url = $url.'?'.$url_append;
    }
  }
  return $url;
}

function fetchUrl($url,$userdata = array()) {

  ## fix the Editor replacing & with &amp;
  $url = str_ireplace('&amp;','&',$url);
  
  require_once "HTTP/Request.php";
 # logEvent("Fetching $url");
  if (sizeof($userdata)) {
    foreach ($userdata as $key => $val) {
      if ($key != 'password') {
        $url = str_ireplace("[$key]",urlencode($val),$url);
      }
    }
  }

  if (!isset($GLOBALS['urlcache'])) {
    $GLOBALS['urlcache'] = array();
  }
  
  $url = expandUrl($url);
#  print "<h1>Fetching ".$url."</h1>";

  # keep in memory cache in case we send a page to many emails
  if (isset($GLOBALS['urlcache'][$url]) && is_array($GLOBALS['urlcache'][$url])
    && (time() - $GLOBALS['urlcache'][$url]['fetched'] < REMOTE_URL_REFETCH_TIMEOUT)) {
#     logEvent($url . " is cached in memory");
      if (VERBOSE && function_exists('output')) {
        output('From memory cache: '.$url);
      }
      return $GLOBALS['urlcache'][$url]['content'];
  }

  $dbcache_lastmodified = getPageCacheLastModified($url);
  $timeout = time() - $dbcache_lastmodified;
  if ($timeout < REMOTE_URL_REFETCH_TIMEOUT) {
#    logEvent($url.' was cached in database');
    if (VERBOSE && function_exists('output')) {
      output('From database cache: '.$url);
    }
    return getPageCache($url);
  } else {
#    logEvent($url.' is not cached in database '.$timeout.' '. $dbcache_lastmodified." ".time());
  }

  # add a small timeout, although the biggest timeout will exist in doing the DNS lookup,
  # so it won't make too much of a difference
  $request_parameters = array(
    'timeout' => 10,
    'allowRedirects' => 1,
    'method' => 'HEAD',
  );

  $remote_charset = 'UTF-8';
  
  $headreq = new HTTP_Request($url,$request_parameters);
  $headreq->addHeader('User-Agent', 'phplist v'.VERSION.' (http://www.phplist.com)');
  if (!PEAR::isError($headreq->sendRequest(false))) {
    $code = $headreq->getResponseCode();
    if ($code != 200) {
      logEvent('Fetching '.$url.' failed, error code '.$code);
      return 0;
    }
    $header = $headreq->getResponseHeader();

    if (preg_match('/charset=(.*)/i',$header['content-type'],$regs)) {
      $remote_charset = strtoupper($regs[1]);
    }

    ## relying on the last modified header doesn't work for many pages
    ## use current time instead
    ## see http://mantis.phplist.com/view.php?id=7684
#    $lastmodified = strtotime($header["last-modified"]);
    $lastmodified = time();
    $cache = getPageCache($url,$lastmodified);
    if (!$cache) {
      $request_parameters['method'] = 'GET';
      $req = new HTTP_Request($url,$request_parameters);
      $req->addHeader('User-Agent', 'phplist v'.VERSION.' (http://www.phplist.com)');
      logEvent('Fetching '.$url);
      if (VERBOSE && function_exists('output')) {
        output('Fetching remote: '.$url);
      }
      if (!PEAR::isError($req->sendRequest(true))) {
        $content = $req->getResponseBody();

        if ($remote_charset != 'UTF-8') {
          $content = iconv($remote_charset,'UTF-8//TRANSLIT',$content);
        }
        
        $content = addAbsoluteResources($content,$url);
        logEvent('Fetching '.$url.' success');
        setPageCache($url,$lastmodified,$content);
      } else {
        logEvent('Fetching '.$url.' failed on GET '.$req->getMessage());
        return 0;
      }
    } else {
      logEvent($url.' was cached in database');
      $content = $cache;
    }
  } else {
    logEvent('Fetching '.$url.' failed on HEAD');
    return 0;
  }
  $GLOBALS['urlcache'][$url] = array(
    'fetched' => time(),
    'content' => $content,
  );
  return $content;
}

function releaseLock($processid) {
  global $tables;
  if (!$processid) return;
  Sql_query("delete from {$tables["sendprocess"]} where id = $processid");
}

function cleanUrl($url,$disallowed_params = array('PHPSESSID')) {
  $parsed = @parse_url($url);
  $params = array();

  if (empty($parsed['query'])) {
    $parsed['query'] = '';
  }
  # hmm parse_str should take the delimiters as a parameter
  if (strpos($parsed['query'],'&amp;')) {
    $pairs = explode('&amp;',$parsed['query']);
    foreach ($pairs as $pair) {
      if (strpos($pair,'=') !== FALSE) {
        list($key,$val) = explode('=',$pair);
        $params[$key] = $val;
      } else {
        $params[$pair] = '';
      }
    }
  } else {
    parse_str($parsed['query'],$params);
  }
  $uri = !empty($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '':'//'): '';
  $uri .= !empty($parsed['user']) ? $parsed['user'].(!empty($parsed['pass'])? ':'.$parsed['pass']:'').'@':'';
  $uri .= !empty($parsed['host']) ? $parsed['host'] : '';
  $uri .= !empty($parsed['port']) ? ':'.$parsed['port'] : '';
  $uri .= !empty($parsed['path']) ? $parsed['path'] : '';
#  $uri .= $parsed['query'] ? '?'.$parsed['query'] : '';
  $query = '';

  foreach ($params as $key => $val) {
    if (!in_array($key,$disallowed_params)) {
      //0008980: Link Conversion for Click Tracking. no = will be added if val is empty.
      $query .= $key . ( $val != "" ? '=' . $val . '&' : '&' );
    }
  }
  $query = substr($query,0,-1);
  $uri .= $query ? '?'.$query : '';
#  if (!empty($params['p'])) {
#    $uri .= '?p='.$params['p'];
#  }
  $uri .= !empty($parsed['fragment']) ? '#'.$parsed['fragment'] : '';
  return $uri;
}

function adminName($id = 0) {
  if (!$id) {
    $id = $_SESSION["logindetails"]["id"];
  }
  if (is_object($GLOBALS["admin_auth"])) {
    return $GLOBALS["admin_auth"]->adminName($id);
  }
  $query
  = ' select loginname'
  . ' from ' . $GLOBALS['tables']['admin']
  . ' where id = ?';
  $rs = Sql_Query_Params($query, array($id));
  $req = Sql_Fetch_Row($rs);
  return $req[0] ? $req[0] : "Nobody";
}

if (!function_exists("dbg")) {
  function xdbg($msg,$logfile = "") {
    if (!$logfile) return;
    $fp = @fopen($logfile,"a");
    $line = "[".date("d M Y, H:i:s")."] ".getenv("REQUEST_URI").'('.$config["stats"]["number_of_queries"].") $msg \n";
    @fwrite($fp,$line);
    @fclose($fp);
  }
}

function addSubscriberStatistics($item = '',$amount,$list = 0) {
  switch (STATS_INTERVAL) {
    case 'monthly':
      # mark everything as the first day of the month
      $time = mktime(0,0,0,date('m'),1,date('Y'));
      break;
    case 'weekly':
      # mark everything for the first sunday of the week
      $time = mktime(0,0,0,date('m'),date('d') - date('w'),date('Y'));
      break;
    case 'daily':
      $time = mktime(0,0,0,date('m'),date('d'),date('Y'));
      break;
  }
  $query
  = ' update ' . $GLOBALS['tables']['userstats']
  . ' set value = value + ?'
  . ' where unixdate = ?'
  . '   and item = ?'
  . '   and listid = ?';
  Sql_Query_Params($query, array($amount, $time, $item, $list));
  $done = Sql_Affected_Rows();
  if (!$done) {
    $query
    = ' insert into ' . $GLOBALS['tables']['userstats']
    . '   (value, unixdate, item, listid)'
    . ' values'
    . '   (?, ?, ?, ?)';
    Sql_Query_Params($query, array($amount, $time, $item, $list));
  }
}

function deleteMessage($id = 0) {
  if( !$GLOBALS["require_login"] || $_SESSION["logindetails"]['superuser'] ){
    $ownerselect_and = '';
    $ownerselect_where = '';
  } else {
    $ownerselect_where = ' WHERE owner = ' . $_SESSION["logindetails"]['id'];
    $ownerselect_and = ' and owner = ' . $_SESSION["logindetails"]['id'];
  }

  # delete the message in delete
  $result = Sql_query("select id from ".$GLOBALS['tables']["message"]." where id = $id $ownerselect_and");
  while ($row = Sql_Fetch_Row($result)) {
    $result = Sql_query("delete from ".$GLOBALS['tables']["message"]." where id = $row[0]");
    $suc6 = Sql_Affected_Rows();
    $result = Sql_query("delete from ".$GLOBALS['tables']["usermessage"]." where messageid = $row[0]");
    $result = Sql_query("delete from ".$GLOBALS['tables']["listmessage"]." where messageid = $row[0]");
    return $suc6;
  }
}

function deleteBounce($id = 0) {
  if (!$id) return;
  $id = sprintf('%d',$id);
  Sql_query(sprintf('delete from %s where id = %d',$GLOBALS['tables']['bounce'],$id));
  Sql_query(sprintf('delete from %s where bounce = %d',$GLOBALS['tables']['user_message_bounce'],$id));
  Sql_query(sprintf('delete from %s where bounce = %d',$GLOBALS['tables']['bounceregex_bounce'],$id));
}

function reverse_htmlentities($mixed)
{
   $htmltable = get_html_translation_table(HTML_ENTITIES);
   foreach($htmltable as $key => $value)
   {
       $mixed = str_replace(addslashes($value),$key,$mixed);
   }
   return $mixed;
}

function loadBounceRules($all = 0) {
  if ($all) {
    $status = '';
  } else {
    $status = ' where status = "active"';
  }
  $result = array();
  $req = Sql_Query(sprintf('select * from %s %s order by listorder',$GLOBALS['tables']['bounceregex'],$status));
  while ($row = Sql_Fetch_Array($req)) {
    if ($row['regex'] && $row['action']) {
      $result[$row['regex']] = array(
        'action' => $row['action'],
        'id' => $row['id']
      );
    }
  }
  return $result;
}

function matchedBounceRule($text,$activeonly = 0) {
  if ($activeonly) {
    $status = ' where status = "active"';
  } else {
    $status = '';
  }
  $req = Sql_Query(sprintf('select * from %s %s order by listorder',$GLOBALS['tables']['bounceregex'],$status));
  while ($row = Sql_Fetch_Array($req)) {
    $pattern = str_replace(' ','\s+',$row['regex']);
 #   print "Trying to match ".$pattern;
    #print ' with '.$text;
 #   print '<br/>';
    if (@preg_match('/'.preg_quote($pattern).'/iUm',$text)) {
      return $row['id'];
    } elseif (@preg_match('/'.$pattern.'/iUm',$text)) {
      return $row['id'];
    }
  }
  return '';
}

function matchBounceRules($text,$rules = array()) {
  if (!sizeof($rules)) {
    $rules = loadBounceRules();
  }

  foreach ($rules as $pattern => $rule) {
    $pattern = str_replace(' ','\s+',$pattern);
    if (@preg_match('/'.preg_quote($pattern).'/iUm',$text)) {
      return $rule;
    } elseif (@preg_match('/'.$pattern.'/iUm',$text)) {
      return $rule;
    } else {
#      print "Trying to match $pattern failed<br/>";
    }
  }
  return '';
}

function flushBrowser() {
  ## push some more output to the browser, so it displays things sooner
  for ($i=0;$i<10000; $i++) {
    print ' '."\n";
  }
  flush();
}

function flushClickTrackCache() {
  if (!isset($GLOBALS['cached']['linktracksent'])) return;
  foreach ($GLOBALS['cached']['linktracksent'] as $mid => $numsent) {
    foreach ($numsent as $fwdid => $fwdtotal) {
      if (VERBOSE)
        output("Flushing clicktrack stats for $mid: $fwdid => $fwdtotal");
      Sql_Query(sprintf('update %s set total = %d where messageid = %d and forwardid = %d',
        $GLOBALS['tables']['linktrack_ml'],$fwdtotal,$mid,$fwdid));
    }
  }
}

if (!function_exists('formatbytes')) {
  function formatBytes ($value) {
    $gb = 1024 * 1024 * 1024;
    $mb = 1024 * 1024;
    $kb = 1024;
    $gbs = $value / $gb;
    if ($gbs > 1)
      return sprintf('%2.2fGb',$gbs);
    $mbs = $value / $mb;
    if ($mbs > 1)
      return sprintf('%2.2fMb',$mbs);
    $kbs = $value / $kb;
    if ($kbs > 1)
      return sprintf('%dKb',$kbs);
    else
    return sprintf('%dBytes',$value);
  }
}

function strip_newlines( $str, $placeholder = '' ) {
  $str = str_replace(chr(13) . chr(10), $placeholder , $str);
  $str = str_replace(chr(10), $placeholder , $str);
  $str = str_replace(chr(13), $placeholder , $str);
  return $str;
}

// Moved to subscribelib2, since itś only used there
//function validaterssFrequency($freq = '') {
//  if (!$freq) return '';
//  if (in_array($freq,array_keys($GLOBALS['rssfrequencies']))) {
//    return $freq;
//  }
//  return '';
//}
function parseDate($strdate,$format = 'Y-m-d') {
  # parse a string date into a date
  $strdate = trim($strdate);
  if (strlen($strdate) < 6) {
    $newvalue = 0;
	}
	elseif (preg_match("#(\d{2,2}).(\d{2,2}).(\d{4,4})#", $strdate, $regs)) {
    $newvalue = mktime(0,0,0,$regs[2],$regs[1],$regs[3]);
	}
	elseif (preg_match("#(\d{4,4}).(\d{2,2}).(\d{2,2})#", $strdate, $regs)) {
    $newvalue = mktime(0,0,0,$regs[3],$regs[1],$regs[1]);
	}
	elseif (preg_match("#(\d{2,2}).(\w{3,3}).(\d{2,4})#", $strdate, $regs)) {
    $newvalue = strtotime($value);
	}
	elseif (preg_match("#(\d{2,4}).(\w{3,3}).(\d{2,2})#", $strdate, $regs)) {
    $newvalue = strtotime($strdate);
  } else {
    $newvalue = strtotime($strdate);
    if ($newvalue < 0) {
      $newvalue = 0;
    }
  }
  if ($newvalue) {
    return date($format,$newvalue);
  } else {
    return "";
  }
}

function verifyToken() {
  if (empty($_POST['formtoken'])) {
    return false;
  }

  ## @@@TODO for now ignore the error. This will cause a block on editing admins if the table doesn't exist.
  $req = Sql_Fetch_Row_Query(sprintf('select id from %s where adminid = %d and value = "%s" and expires > now()',
    $GLOBALS['tables']['admintoken'],$_SESSION['logindetails']['id'],sql_escape($_POST['formtoken'])),1);
  if (empty($req[0])) {
    return false;
  }
  Sql_Query(sprintf('delete from %s where id = %d',
    $GLOBALS['tables']['admintoken'],$req[0]),1);
  Sql_Query(sprintf('delete from %s where expires < now()',
    $GLOBALS['tables']['admintoken']),1);
  return true;
}

function listCategories() {

  $sListCategories = getConfig('list_categories');
  $aConfiguredListCategories = cleanArray(explode(',',$sListCategories));
  foreach ($aConfiguredListCategories as $key => $val) {
    $aConfiguredListCategories[$key] = trim($val);
  }
  return $aConfiguredListCategories;
}

if (!function_exists('getnicebacktrace')) {
function getNiceBackTrace( $bTrace = false ) {
  $sTrace = '';
  $aBackTrace = debug_backtrace();
  $iMin = 0;
  if ( $bTrace ) {
    $iMax = count($aBackTrace) - 1;
    $iMax = count($aBackTrace) ;
  } else {
    $iMax = 3;
  }
  for($iIndex = $iMin; $iIndex < $iMax; $iIndex++){
    
    if ( $bTrace ) {
      $sTrace .= "\n"; 
    }
    
    $sTrace .= $iIndex . sprintf("%s#%4d:%s() ",
      pad_right($aBackTrace[$iIndex]['file'], 30),
      $aBackTrace[$iIndex]['line'],
      pad_right($aBackTrace[$iIndex]['function'], 15)
    );
  }
  
  return $sTrace;
  
}
}
if (!function_exists('pad_right')) {

function pad_right($str,$len) {
  $str = str_pad( $str, $len, ' ', STR_PAD_LEFT);
  return substr( $str, strlen( $str ) - $len, $len );
}
}

?>
