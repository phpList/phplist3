<?php

require_once dirname(__FILE__).'/accesscheck.php';

if (defined('PHPMAILER_PATH') and PHPMAILER_PATH != '') {
    #require_once '/usr/share/php/libphp-phpmailer/class.phpmailer.php'
    require_once PHPMAILER_PATH;
} else {
    //https://github.com/PHPMailer/PHPMailer
    require_once dirname(__FILE__).'/PHPMailer/PHPMailerAutoload.php';
}

class PHPlistMailer extends PHPMailer
{
    public $WordWrap = 75;
    public $encoding = 'base64';
    public $messageid = 0;
    public $destionationemail = '';
    public $estimatedsize = 0;
    public $mailsize = 0;
    private $inBlast = false;
    public $image_types = array(
                  'gif'   => 'image/gif',
                  'jpg'   => 'image/jpeg',
                  'jpeg'  => 'image/jpeg',
                  'jpe'   => 'image/jpeg',
                  'bmp'   => 'image/bmp',
                  'png'   => 'image/png',
                  'tif'   => 'image/tiff',
                  'tiff'  => 'image/tiff',
                  'swf'   => 'application/x-shockwave-flash',
                  );

    public $LE              = "\n";
    public $Hello = '';
    public $timeStamp = '';

    public function PHPlistMailer($messageid, $email, $inBlast = true, $exceptions = false)
    {
        parent::__construct($exceptions);
        parent::SetLanguage('en', dirname(__FILE__).'/PHPMailer/language/');
        $this->addCustomHeader('X-phpList-version: '.VERSION);
        $this->addCustomHeader("X-MessageID: $messageid");
        $this->addCustomHeader("X-ListMember: $email");

      ## amazon SES doesn't like this
/*
 * http://mantis.phplist.com/view.php?id=15562
 * Interesting, https://mantis.phplist.com/view.php?id=16688
 * says Gmail wants it. Everyone's confused.
 * 
 * Also, are we "Precedence: bulk, or Precedence: list"
 * 
 * I guess this should become configurable, to leave the choice up to the installation, 
 * but what would be our default?
 * 
      if (!USE_AMAZONSES) {
#        $this->addCustomHeader("Precedence: bulk");
      }
      * 
      * ok, decided:
*/

      if (!USE_AMAZONSES && USE_PRECEDENCE_HEADER) {
          $this->addCustomHeader('Precedence: bulk');
      }

        $newwrap = getConfig('wordwrap');
        if ($newwrap) {
            $this->WordWrap = $newwrap;
        }
        if (defined('SMTP_TIMEOUT')) {
            $this->Timeout = sprintf('%d', SMTP_TIMEOUT);
        }

        $this->destinationemail = $email;
        $this->SingleTo = false;
        $this->CharSet =  'UTF-8';# getConfig("html_charset");
      $this->inBlast = $inBlast;
      ### hmm, would be good to sort this out differently, but it'll work for now
      ## don't send test message using the blast server
      if (isset($_GET['page']) && $_GET['page'] == 'send') {
          $this->inBlast = false;
      }

        if ($this->inBlast && defined('PHPMAILERBLASTHOST') && defined('PHPMAILERBLASTPORT') && PHPMAILERBLASTHOST != '') {
            $this->Helo = getConfig('website');
            $this->Host = PHPMAILERBLASTHOST;
            $this->Port = PHPMAILERBLASTPORT;
            if (isset($GLOBALS['phpmailer_smtpuser']) && $GLOBALS['phpmailer_smtpuser'] != ''
             && isset($GLOBALS['phpmailer_smtppassword']) && $GLOBALS['phpmailer_smtppassword']) {
                $this->Username = $GLOBALS['phpmailer_smtpuser'];
                $this->Password = $GLOBALS['phpmailer_smtppassword'];
                $this->SMTPAuth = true;
            }
            $this->Mailer = 'smtp';
        } elseif (!$this->inBlast && defined('PHPMAILERTESTHOST') && PHPMAILERTESTHOST != '') {
            if (defined('PHPMAILERPORT')) {
                $this->Port = PHPMAILERPORT;
            }
        //logEvent('Sending email via '.PHPMAILERHOST);
        $this->Helo = getConfig('website');
            $this->Host = PHPMAILERTESTHOST;
            if (isset($GLOBALS['phpmailer_smtpuser']) && $GLOBALS['phpmailer_smtpuser'] != ''
             && isset($GLOBALS['phpmailer_smtppassword']) && $GLOBALS['phpmailer_smtppassword']) {
                $this->Username = $GLOBALS['phpmailer_smtpuser'];
                $this->Password = $GLOBALS['phpmailer_smtppassword'];
                $this->SMTPAuth = true;
            }
            $this->Mailer = 'smtp';
        } elseif (defined('PHPMAILERHOST') && PHPMAILERHOST != '') {
            if (defined('PHPMAILERPORT')) {
                $this->Port = PHPMAILERPORT;
            }
        //logEvent('Sending email via '.PHPMAILERHOST);
        $this->Helo = getConfig('website');
            $this->Host = PHPMAILERHOST;
            if (isset($GLOBALS['phpmailer_smtpuser']) && $GLOBALS['phpmailer_smtpuser'] != ''
             && isset($GLOBALS['phpmailer_smtppassword']) && $GLOBALS['phpmailer_smtppassword']) {
                $this->Username = $GLOBALS['phpmailer_smtpuser'];
                $this->Password = $GLOBALS['phpmailer_smtppassword'];
                $this->SMTPAuth = true;
            }
            $this->Mailer = 'smtp';
        } else {
            $this->isMail();
        }
        if (empty($_SERVER['SERVER_NAME']) || empty($this->Hostname)) {
            $this->Hostname = getConfig('domain');
        }

        if (defined('PHPMAILER_SECURE') && PHPMAILER_SECURE) {
            $this->SMTPSecure = PHPMAILER_SECURE;
        }

        if ($GLOBALS['message_envelope']) {
            $this->Sender = $GLOBALS['message_envelope'];
            $this->addCustomHeader('Bounces-To: '.$GLOBALS['message_envelope']);

## one to work on at a later stage
#        $this->addCustomHeader("Return-Receipt-To: ".$GLOBALS["message_envelope"]);
        }
      ## when the email is generated from a webpage (quite possible :-) add a "received line" to identify the origin
      if (!empty($_SERVER['REMOTE_ADDR'])) {
          $this->add_timestamp();
      }
        $this->messageid = $messageid;
    }

    public function add_html($html, $text = '', $templateid = 0)
    {
        $this->Body = $html;
        $this->IsHTML(true);
        if ($text) {
            $this->add_text($text);
        }
        $this->Encoding = HTMLEMAIL_ENCODING;
        $this->find_html_images($templateid);
    }

    public function add_timestamp()
    {
        #0013076:
      # Add a line like Received: from [10.1.2.3] by website.example.com with HTTP; 01 Jan 2003 12:34:56 -0000
      # more info: http://www.spamcop.net/fom-serve/cache/369.html
      $ip_address = $_SERVER['REMOTE_ADDR'];
        if (!empty($_SERVER['REMOTE_HOST'])) {
            $ip_domain = $_SERVER['REMOTE_HOST'];
        } else {
            $ip_domain = gethostbyaddr($ip_address);
        }
        if ($ip_domain != $ip_address) {
            $from = "$ip_domain [$ip_address]";
        } else {
            $from = "[$ip_address]";
        }
        $hostname = hostName();
        $request_time = date('r', $_SERVER['REQUEST_TIME']);
        $sTimeStamp = "from $from by $hostname with HTTP; $request_time";
        $this->addTimeStamp($sTimeStamp);
    }

    public function AddTimeStamp($sTimeStamp)
    {
        $this->timeStamp = $sTimeStamp;
    }

    public function add_text($text)
    {
        if (!$this->Body) {
            $this->IsHTML(false);
            $this->Body = html_entity_decode($text, ENT_QUOTES, 'UTF-8'); #$text;
        } else {
            $this->AltBody = html_entity_decode($text, ENT_QUOTES, 'UTF-8');#$text;
        }
    }

    public function append_text($text)
    {
        if ($this->AltBody) {
            $this->AltBody .= html_entity_decode($text, ENT_QUOTES, 'UTF-8');#$text;
        } else {
            $this->Body .= html_entity_decode($text."\n", ENT_QUOTES, 'UTF-8');#$text;
        }
    }

    public function build_message()
    {
    }

    public function CreateHeader()
    {
        $parentheader = parent::CreateHeader();
        if (!empty($this->timeStamp)) {
            $header = 'Received: '.$this->timeStamp.$this->LE.$parentheader;
        } else {
            $header = $parentheader;
        }

        return $header;
    }

    public function CreateBody()
    {
        $body = parent::CreateBody();
/*
      if ($this->ContentType != 'text/plain') {
        foreach ($GLOBALS['plugins'] as $plugin) {
          $plreturn =  $plugin->mimeWrap($this->messageid,$body,$this->header,$this->ContentTypeHeader,$this->destinationemail);
          if (is_array($plreturn) && sizeof($plreturn) == 3) {
            $this->header = $plreturn[0];
            $body = $plreturn[1];
            $this->ContentTypeHeader = $plreturn[2];
          }
        }
      }
*/
      return $body;
    }

    public function compatSend($to_name = '', $to_addr, $from_name, $from_addr, $subject = '', $headers = '', $envelope = '')
    {
        if (!empty($from_addr) && method_exists($this, 'SetFrom')) {
            $this->SetFrom($from_addr, $from_name);
        } else {
            $this->From = $from_addr;
            $this->FromName = $from_name;
        }
        if (!empty($GLOBALS['developer_email'])) {
            # make sure we are not sending out emails to real users
        # when developing
        $this->AddAddress($GLOBALS['developer_email']);
            if ($GLOBALS['developer_email'] != $to_addr) {
                $this->Body = 'X-Originally to: '.$to_addr."\n\n".$this->Body;
            }
        } else {
            $this->AddAddress($to_addr);
        }
        $this->Subject = $subject;
        if ($this->Body) {
            ## allow plugins to add header lines
        foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
            #    print "Checking Destination for ".$plugin->name."<br/>";
          $pluginHeaders = $plugin->messageHeaders($this);
            if ($pluginHeaders && sizeof($pluginHeaders)) {
                foreach ($pluginHeaders as $headerItem => $headerValue) {
                    ## @@TODO, do we need to sanitise them? 
              $this->addCustomHeader($headerItem.': '.$headerValue);
                }
            }
        }
            if (!parent::Send()) {
                logEvent(s('Error sending email to %s', $to_addr).' '.$this->ErrorInfo);

                return 0;
            }#
        } else {
            logEvent(s('Error, empty message-body sending email to %s', $to_addr));

            return 0;
        }

        return 1;
    }

    public function Send()
    {
        if (!parent::Send()) {
            return 0;
        }

        return 1;
    }

    public function add_attachment($contents, $filename, $mimetype)
    {
        ## phpmailer 2.x
      if (method_exists($this, 'AddStringAttachment')) {
          $this->AddStringAttachment($contents, $filename, 'base64', $mimetype);
      } else {
          ## old phpmailer
        // Append to $attachment array
        $cur = count($this->attachment);
          $this->attachment[$cur][0] = base64_encode($contents);
          $this->attachment[$cur][1] = $filename;
          $this->attachment[$cur][2] = $filename;
          $this->attachment[$cur][3] = $this->encoding;
          $this->attachment[$cur][4] = $mimetype;
          $this->attachment[$cur][5] = false; // isStringAttachment
        $this->attachment[$cur][6] = 'attachment';
          $this->attachment[$cur][7] = 0;
      }
    }

    public function find_html_images($templateid)
    {
        #if (!$templateid) return;
      ## no template can be templateid 0, find the powered by image
      $templateid = sprintf('%d', $templateid);

      // Build the list of image extensions
      $extensions = array();
        while (list($key) = each($this->image_types)) {
            $extensions[] = $key;
        }
        $html_images = array();
        $filesystem_images = array();

        preg_match_all('/"([^"]+\.('.implode('|', $extensions).'))"/Ui', $this->Body, $images);

        for ($i = 0; $i < count($images[1]); ++$i) {
            if ($this->image_exists($templateid, $images[1][$i])) {
                $html_images[] = $images[1][$i];
                $this->Body = str_replace($images[1][$i], basename($images[1][$i]), $this->Body);
            }
          ## addition for filesystem images
        if (EMBEDUPLOADIMAGES) {
            if ($this->filesystem_image_exists($images[1][$i])) {
                $filesystem_images[] = $images[1][$i];
                $this->Body = str_replace($images[1][$i], basename($images[1][$i]), $this->Body);
            }
        }
        ## end addition
        }
        if (!empty($html_images)) {
            // If duplicate images are embedded, they may show up as attachments, so remove them.
        $html_images = array_unique($html_images);
            sort($html_images);
            for ($i = 0; $i < count($html_images); ++$i) {
                if ($image = $this->get_template_image($templateid, $html_images[$i])) {
                    $content_type = $this->image_types[strtolower(substr($html_images[$i], strrpos($html_images[$i], '.') + 1))];
                    $cid = $this->add_html_image($image, basename($html_images[$i]), $content_type);
                    if (!empty($cid)) {
                        $this->Body = str_replace(basename($html_images[$i]), "cid:$cid", $this->Body);
                    }
                }
            }
        }
        ## addition for filesystem images
      if (!empty($filesystem_images)) {
          // If duplicate images are embedded, they may show up as attachments, so remove them.
        $filesystem_images = array_unique($filesystem_images);
          sort($filesystem_images);
          for ($i = 0; $i < count($filesystem_images); ++$i) {
              if ($image = $this->get_filesystem_image($filesystem_images[$i])) {
                  $content_type = $this->image_types[strtolower(substr($filesystem_images[$i], strrpos($filesystem_images[$i], '.') + 1))];
                  $cid = $this->add_html_image($image, basename($filesystem_images[$i]), $content_type);
                  if (!empty($cid)) {
                      $this->Body = str_replace(basename($filesystem_images[$i]), "cid:$cid", $this->Body);#@@@
                  }
              }
          }
      }
        ## end addition
    }

    public function add_html_image($contents, $name = '', $content_type = 'application/octet-stream')
    {
        ## in phpMailer 2 and up we cannot use AddStringAttachment, because that doesn't use a cid
      ## we can't write to "attachment" either, because it's private

      /* one way to do it, is using a temporary file, but that'll have
       * quite an effect on performance and also isn't backward compatible,
       * because EncodeFile would need to be reverted to the default

      file_put_contents('/tmp/'.$name,base64_decode($contents));
      $cid = md5(uniqid(time()));
      $this->AddEmbeddedImage('/tmp/'.$name, $cid, $name,'base64', $content_type);
      */

      /* So, for now the only way to get this working in phpMailer 2 or up is to make
       * the attachment array public or add the AddEmbeddedImageString method
       * we need to add instructions how to patch phpMailer for that.
       * find out here whether it's been done and give an error if not
       * 
       * it's been added to phpMailer 5.2.2
       * http://code.google.com/a/apache-extras.org/p/phpmailer/issues/detail?id=119
       * 
       * 
       */

      /* @@TODO additional optimisation:
       *
       * - we store the image base64 encoded
       * - then we decode it to pass it back to phpMailer
       * - when then encodes it again
       * - best would be to take out a step in there, but that would require more modifications
       * to phpMailer
       */

      #$cid = md5(uniqid(time()));
      $cid = md5(mt_rand().$name.uniqid(time(), true)); ##17603 better random CID value on Windows
      if (method_exists($this, 'AddEmbeddedImageString')) {
          $this->AddEmbeddedImageString(base64_decode($contents), $cid, $name, $this->encoding, $content_type);
      } elseif (method_exists($this, 'AddStringEmbeddedImage')) {
          ## PHPMailer 5.2.5 and up renamed the method
        ## https://github.com/PHPMailer/PHPMailer/issues/42#issuecomment-16217354
        $this->AddStringEmbeddedImage(base64_decode($contents), $cid, $name, $this->encoding, $content_type);
      } elseif (isset($this->attachment) && is_array($this->attachment)) {
          // Append to $attachment array
        $cur = count($this->attachment);
          $this->attachment[$cur][0] = base64_decode($contents);
          $this->attachment[$cur][1] = '';#$filename;
        $this->attachment[$cur][2] = $name;
          $this->attachment[$cur][3] = 'base64';
          $this->attachment[$cur][4] = $content_type;
          $this->attachment[$cur][5] = true; // isStringAttachment
        $this->attachment[$cur][6] = 'inline';
          $this->attachment[$cur][7] = $cid;
      } else {
          logEvent('phpMailer needs patching to be able to use inline images from templates');
          print Error('phpMailer needs patching to be able to use inline images from templates');

          return;
      }

        return $cid;
    }

    ## addition for filesystem images
    public function filesystem_image_exists($filename)
    {
        ##  find the image referenced and see if it's on the server
      $imageroot = getConfig('uploadimageroot');
#      cl_output('filesystem_image_exists '.$docroot.' '.$filename);

      $elements = parse_url($filename);
        $localfile = basename($elements['path']);

        $localfile = urldecode($localfile);
 #     cl_output('CHECK'.$localfile);

      if (defined('UPLOADIMAGES_DIR')) {
          #  print $_SERVER['DOCUMENT_ROOT'].$localfile;
        return
          is_file($_SERVER['DOCUMENT_ROOT'].'/'.UPLOADIMAGES_DIR.'/image/'.$localfile)
          || is_file($_SERVER['DOCUMENT_ROOT'].'/'.UPLOADIMAGES_DIR.'/'.$localfile)
          ## commandline
          || is_file($imageroot.'/'.$localfile);
      } else {
          return
        is_file($_SERVER['DOCUMENT_ROOT'].$GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/image/'.$localfile)
        || is_file($_SERVER['DOCUMENT_ROOT'].$GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/'.$localfile)
        ## commandline
        || is_file('../'.FCKIMAGES_DIR.'/image/'.$localfile)
        || is_file('../'.FCKIMAGES_DIR.'/'.$localfile);
      }
    }

    public function get_filesystem_image($filename)
    {
        ## get the image contents
      $localfile = basename(urldecode($filename));
#      cl_output('get file system image'.$filename.' '.$localfile);
      if (defined('UPLOADIMAGES_DIR')) {
          #       print 'UPLOAD';
        $imageroot = getConfig('uploadimageroot');
          if (is_file($imageroot.$localfile)) {
              return base64_encode(file_get_contents($imageroot.$localfile));
          } else {
              if (is_file($_SERVER['DOCUMENT_ROOT'].$localfile)) {
                  ## save the document root to be able to retrieve the file later from commandline
            SaveConfig('uploadimageroot', $_SERVER['DOCUMENT_ROOT'], 0, 1);

                  return base64_encode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$localfile));
              } elseif (is_file($_SERVER['DOCUMENT_ROOT'].'/'.UPLOADIMAGES_DIR.'/image/'.$localfile)) {
                  SaveConfig('uploadimageroot', $_SERVER['DOCUMENT_ROOT'].'/'.UPLOADIMAGES_DIR.'/image/', 0, 1);

                  return base64_encode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/'.UPLOADIMAGES_DIR.'/image/'.$localfile));
              } elseif (is_file($_SERVER['DOCUMENT_ROOT'].'/'.UPLOADIMAGES_DIR.'/'.$localfile)) {
                  SaveConfig('uploadimageroot', $_SERVER['DOCUMENT_ROOT'].'/'.UPLOADIMAGES_DIR.'/', 0, 1);

                  return base64_encode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/'.UPLOADIMAGES_DIR.'/'.$localfile));
              }
          }
      } elseif (is_file($_SERVER['DOCUMENT_ROOT'].$GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/'.$localfile)) {
          $elements = parse_url($filename);
          $localfile = basename($elements['path']);

          return base64_encode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/'.$localfile));
      } elseif (is_file($_SERVER['DOCUMENT_ROOT'].$GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/image/'.$localfile)) {
          return base64_encode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/image/'.$localfile));
      } elseif (is_file('../'.FCKIMAGES_DIR.'/'.$localfile)) {   ## commandline
        return base64_encode(file_get_contents('../'.FCKIMAGES_DIR.'/'.$localfile));
      } elseif (is_file('../'.FCKIMAGES_DIR.'/image/'.$localfile)) {
          return base64_encode(file_get_contents('../'.FCKIMAGES_DIR.'/image/'.$localfile));
      }

        return '';
    }
    ## end addition

    public function image_exists($templateid, $filename)
    {
        if (basename($filename) == 'powerphplist.png' || strpos($filename, 'ORGANISATIONLOGO') === 0) {
            $templateid = 0;
        }
        $req = Sql_Query(sprintf('select * from %s where template = %d and (filename = "%s" or filename = "%s")',
        $GLOBALS['tables']['templateimage'], $templateid, $filename, basename($filename)));

        return Sql_Affected_Rows();
    }

    public function get_template_image($templateid, $filename)
    {
        if (basename($filename) == 'powerphplist.png' || strpos($filename, 'ORGANISATIONLOGO') === 0) {
            $templateid = 0;
        }
        $req = Sql_Fetch_Row_Query(sprintf('select data from %s where template = %d and (filename = "%s" or filename = "%s")',
        $GLOBALS['tables']['templateimage'], $templateid, $filename, basename($filename)));

        return $req[0];
    }

    public function EncodeFile($path, $encoding = 'base64')
    {
        # as we already encoded the contents in $path, return $path
      return chunk_split($path, 76, $this->LE);
    }

    public function AmazonSESSend($messageheader, $messagebody)
    {
        $messageheader = preg_replace('/'.$this->LE.'$/', '', $messageheader);
        $messageheader .= $this->LE.'Subject: '.$this->EncodeHeader($this->Subject).$this->LE;

      #print nl2br(htmlspecialchars($messageheader));      exit;

      $date = date('r');
        $aws_signature = base64_encode(hash_hmac('sha256', $date, AWS_SECRETKEY, true));

        $requestheader = array(
        'Host: '.parse_url(AWS_POSTURL, PHP_URL_HOST),
        'Content-Type: application/x-www-form-urlencoded',
        'Date: '.$date,
        'X-Amzn-Authorization: AWS3-HTTPS AWSAccessKeyId='.AWS_ACCESSKEYID.',Algorithm=HMACSHA256,Signature='.$aws_signature,
      );

/*
 *    using the SendEmail call
      $requestdata = array(
        'Action' => 'SendEmail',
        'Source' => $this->Sender,
        'Destination.ToAddresses.member.1' => $this->destinationemail,
        'Message.Subject.Data' => $this->Subject,
        'Message.Body.Text.Data' => $messagebody,
      );
*/
 #     print '<hr/>Rawmessage '.nl2br(htmlspecialchars($messageheader. $this->LE. $this->LE.$messagebody));

      $rawmessage = base64_encode($messageheader.$this->LE.$this->LE.$messagebody);
  #   $rawmessage = str_replace('=','',$rawmessage);

      $requestdata = array(
        'Action'                => 'SendRawEmail',
        'Source'                => $GLOBALS['message_envelope'],
        'Destinations.member.1' => $this->destinationemail,
        'RawMessage.Data'       => $rawmessage,
      );

        $header = '';
        foreach ($requestheader as $param) {
            $header .= $param.$this->LE;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, AWS_POSTURL);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $requestheader);
  #    print('<br/>Sending header '.htmlspecialchars($header).'<hr/>');

      curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_DNS_USE_GLOBAL_CACHE, true);
        curl_setopt($curl, CURLOPT_USERAGENT, NAME.' (phpList version '.VERSION.', http://www.phplist.com/)');
        curl_setopt($curl, CURLOPT_POST, 1);

          ## this generates multipart/form-data, and that crashes the API, so don't use
    #      curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);

      $data = '';
        foreach ($requestdata as $param => $value) {
            $data .= $param.'='.urlencode($value).'&';
        }
        $data = substr($data, 0, -1);
  #    print('Sending data '.htmlspecialchars($data).'<hr/>');
      curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $res = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  #    print('Curl status '.$status);
      if ($status != 200) {
          $error = curl_error($curl);
          logEvent('Amazon SES status '.$status.' '.strip_tags($res).' '.$error);
      }
        curl_close($curl);
 #     print('Got remote admin response '.htmlspecialchars($res).'<br/>');
      return $status == 200;
    }

    public function MailSend($header, $body)
    {
        $this->mailsize = strlen($header.$body);

      ## use Amazon, if set up, @@TODO redo with latest PHPMailer
      ## https://github.com/PHPMailer/PHPMailer/commit/57b183bf6a203cb69231bc3a235a00905feff75b

      if (USE_AMAZONSES) {
          $header .= 'To: '.$this->destinationemail.$this->LE;

          return $this->AmazonSESSend($header, $body);
      }

      ## we don't really use multiple to's so pass that on to phpmailer, if there are any
      if (!$this->SingleTo || !USE_LOCAL_SPOOL) {
          return parent::MailSend($header, $body);
      }
        if (!is_dir(USE_LOCAL_SPOOL) || !is_writable(USE_LOCAL_SPOOL)) {
            ## if local spool is not set, send the normal way
        return parent::MailSend($header, $body);
        }
        $fname = tempnam(USE_LOCAL_SPOOL, 'msg');
        file_put_contents($fname, $header."\n".$body);
        file_put_contents($fname.'.S', $this->Sender);

        return true;
    }
}
