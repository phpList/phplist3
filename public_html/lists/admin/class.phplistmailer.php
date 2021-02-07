<?php

require_once __DIR__.'/accesscheck.php';
require __DIR__.'/class.phplistmailerbase.php';

class phplistMailer extends phplistMailerBase
{
    // Inherited properties
    public $XMailer = ' ';  // disables `X-Mailer' header
    public $WordWrap = 75;

    // Additional properties
    public $messageid = 0;
    public $destinationemail = '';

    private $timeStamp = '';
    private $inBlast = false;
    private $image_types = array(
        'gif'  => 'image/gif',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpe'  => 'image/jpeg',
        'bmp'  => 'image/bmp',
        'png'  => 'image/png',
        'tif'  => 'image/tiff',
        'tiff' => 'image/tiff',
        'swf'  => 'application/x-shockwave-flash',
    );

    public function __construct($messageid, $email, $inBlast = true, $exceptions = false)
    {
        parent::__construct($exceptions);
        $this->addCustomHeader('X-phpList-version', VERSION);
        $this->addCustomHeader('X-MessageID', $messageid);
        $this->addCustomHeader('X-ListMember', $email);
        if (GOOGLE_SENDERID != '') {
            $this->addCustomHeader('Feedback-ID', "$messageid:".GOOGLE_SENDERID);
        }

        //# amazon SES doesn't like this
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
            $this->addCustomHeader('Precedence', 'bulk');
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
        $this->CharSet = 'UTF-8'; // getConfig("html_charset");
        $this->inBlast = $inBlast;
        //## hmm, would be good to sort this out differently, but it'll work for now
        //# don't send test message using the blast server
        if (isset($_GET['page']) && $_GET['page'] == 'send') {
            $this->inBlast = false;
        }
        $this->Helo = getConfig('domain');

        if ($GLOBALS['emailsenderplugin']) {
            $this->Mailer = 'plugin';
        } elseif ($this->inBlast && defined('PHPMAILERBLASTHOST') && defined('PHPMAILERBLASTPORT') && PHPMAILERBLASTHOST != '') {
            $this->Host = PHPMAILERBLASTHOST;
            $this->Port = PHPMAILERBLASTPORT;
            if (isset($GLOBALS['phpmailer_smtpuser']) && $GLOBALS['phpmailer_smtpuser'] != ''
                && isset($GLOBALS['phpmailer_smtppassword']) && $GLOBALS['phpmailer_smtppassword']
            ) {
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
            $this->Host = PHPMAILERTESTHOST;
            if (isset($GLOBALS['phpmailer_smtpuser']) && $GLOBALS['phpmailer_smtpuser'] != ''
                && isset($GLOBALS['phpmailer_smtppassword']) && $GLOBALS['phpmailer_smtppassword']
            ) {
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
            $this->Host = PHPMAILERHOST;
            if (POP_BEFORE_SMTP) {
                // authenticate using the smtp user and password
                $pop = new POP3();

                if (!$pop->authorise(PHPMAILERHOST, $this->Port, 30, $GLOBALS['phpmailer_smtpuser'], $GLOBALS['phpmailer_smtppassword'], 1)) {
                    // unable to authenticate, there might be an error message in $pop->getErrors()
                    $poperror = $pop->getErrors();

                    if (POPBEFORESMTP_DEBUG) {
                        $this->SMTPDebug = 2;
                        //Ask for HTML-friendly debug output
                        $this->Debugoutput = 'html';
                    }
                }

            } else {
                // the existing smtp code
                if (isset($GLOBALS['phpmailer_smtpuser']) && $GLOBALS['phpmailer_smtpuser'] != ''
                    && isset($GLOBALS['phpmailer_smtppassword']) && $GLOBALS['phpmailer_smtppassword']
                ) {
                    $this->Username = $GLOBALS['phpmailer_smtpuser'];
                    $this->Password = $GLOBALS['phpmailer_smtppassword'];
                    $this->SMTPAuth = true;
                }
            }
        $this->Mailer = 'smtp';
        } elseif (USE_AMAZONSES) {
            $this->Mailer = 'amazonSes';
        } elseif (USE_LOCAL_SPOOL && is_dir(USE_LOCAL_SPOOL) && is_writable(USE_LOCAL_SPOOL)) {
            $this->Mailer = 'localSpool';
        } else {
            $this->isMail();
        }
        if (empty($_SERVER['SERVER_NAME']) || empty($this->Hostname)) {
            $this->Hostname = getConfig('domain');
        }

        $this->SMTPAutoTLS = true;
        if (defined('PHPMAILER_SECURE') && PHPMAILER_SECURE) {
            if (PHPMAILER_SECURE != 'auto') { // auto is already on
                $this->SMTPSecure = PHPMAILER_SECURE;
            }
        } elseif (defined('PHPMAILER_SECURE') && !PHPMAILER_SECURE) {
            //#18115 allow switching AutoTLS off, when using insecure and untrusted (self signed) certificates
            $this->SMTPSecure = '';
            $this->SMTPAutoTLS = false;
        }

        if (isset($GLOBALS['phpmailer_smtpoptions'])) {
            $this->SMTPOptions = $GLOBALS['phpmailer_smtpoptions'];
        }

        if ($GLOBALS['message_envelope']) {
            $this->Sender = $GLOBALS['message_envelope'];

//# one to work on at a later stage
//        $this->addCustomHeader("Return-Receipt-To: ".$GLOBALS["message_envelope"]);
        }
        //# when the email is generated from a webpage (quite possible :-) add a "received line" to identify the origin
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
        //0013076:
        // Add a line like Received: from [10.1.2.3] by website.example.com with HTTP; 01 Jan 2003 12:34:56 -0000
        // more info: http://www.spamcop.net/fom-serve/cache/369.html
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
            $this->Body = html_entity_decode($text, ENT_QUOTES, 'UTF-8'); //$text;
        } else {
            $this->AltBody = html_entity_decode($text, ENT_QUOTES, 'UTF-8'); //$text;
        }
    }

    public function append_text($text)
    {
        if ($this->AltBody) {
            $this->AltBody .= html_entity_decode($text, ENT_QUOTES, 'UTF-8'); //$text;
        } else {
            $this->Body .= html_entity_decode($text."\n", ENT_QUOTES, 'UTF-8'); //$text;
        }
    }

    public function CreateHeader()
    {
        $parentheader = parent::CreateHeader();
        if (!empty($this->timeStamp)) {
            $header = 'Received: '.$this->timeStamp.$this->lineEnding.$parentheader;
        } else {
            $header = $parentheader;
        }

        return $header;
    }

    public function compatSend(
        $to_name,
        $to_addr,
        $from_name,
        $from_addr,
        $subject = ''
    ) {
        if (!empty($from_addr) && method_exists($this, 'SetFrom')) {
            $this->SetFrom($from_addr, $from_name);
        } else {
            $this->From = $from_addr;
            $this->FromName = $from_name;
        }
        if (!empty($GLOBALS['developer_email'])) {
            // make sure we are not sending out emails to real users
            // when developing
            $this->AddAddress($GLOBALS['developer_email']);
            if ($GLOBALS['developer_email'] != $to_addr) {
                $this->Body = 'X-Originally to: '.$to_addr."\n\n".$this->Body;
            }
        } else {
            $this->AddAddress($to_addr);
        }
        $this->Subject = $subject;
        if ($this->Body) {
            //# allow plugins to add header lines
            foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
                //    print "Checking Destination for ".$plugin->name."<br/>";
                $pluginHeaders = $plugin->messageHeaders($this);
                if ($pluginHeaders && count($pluginHeaders)) {
                    foreach ($pluginHeaders as $headerItem => $headerValue) {
                        //# @@TODO, do we need to sanitise them?
                        $this->addCustomHeader($headerItem, $headerValue);
                    }
                }
            }
            if (!parent::Send()) {
                logEvent(s('Error sending email to %s', $to_addr).' '.$this->ErrorInfo);

                return 0;
            }
        } else {
            logEvent(s('Error, empty message-body sending email to %s', $to_addr));

            return 0;
        }

        return 1;
    }

    public function add_attachment($contents, $filename, $mimetype)
    {
        $this->AddStringAttachment($contents, $filename, 'base64', $mimetype);
    }

    public function find_html_images($templateid)
    {
        //if (!$templateid) return;
        //# no template can be templateid 0, find the powered by image
        $templateid = sprintf('%d', $templateid);

        // Build the list of image extensions
        $extensions = implode('|', array_keys($this->image_types));
        $html_images = array();
        $filesystem_images = array();

        //# addition for external images
        if (defined('EMBEDEXTERNALIMAGES') && EMBEDEXTERNALIMAGES) {
            $external_images = array();
            $matched_images = array();
            $pattern = sprintf(
                '~="(https?://(?!%s)([^"]+\.(%s))([\\?/][^"]+)?)"~Ui',
                preg_quote(getConfig('website')),
                $extensions
            );
            preg_match_all($pattern, $this->Body, $matched_images);

            for ($i = 0; $i < count($matched_images[1]); ++$i) {
                if ($this->external_image_exists($matched_images[1][$i])) {
                    $external_images[] = $matched_images[1][$i].'~^~'.basename($matched_images[2][$i]).'~^~'.strtolower($matched_images[3][$i]);
                }
            }

            if (!empty($external_images)) {
                $external_images = array_unique($external_images);

                for ($i = 0; $i < count($external_images); ++$i) {
                    $external_image = explode('~^~', $external_images[$i]);

                    if ($image = $this->get_external_image($external_image[0])) {
                        $content_type = $this->image_types[$external_image[2]];
                        $cid = $this->add_html_image($image, $external_image[1], $content_type);

                        if (!empty($cid)) {
                            $this->Body = str_replace($external_image[0], 'cid:'.$cid, $this->Body);
                        }
                    }
                }
            }
        }
        //# end addition

        preg_match_all('/"([^"]+\.('.$extensions.'))"/Ui', $this->Body, $images);

        for ($i = 0; $i < count($images[1]); ++$i) {
            if ($this->image_exists($templateid, $images[1][$i])) {
                $html_images[] = $images[1][$i];
                $this->Body = str_replace($images[1][$i], basename($images[1][$i]), $this->Body);
            }
            //# addition for filesystem images
            if (EMBEDUPLOADIMAGES) {
                if ($this->filesystem_image_exists($images[1][$i])) {
                    $filesystem_images[] = $images[1][$i];
                    $this->Body = str_replace($images[1][$i], basename($images[1][$i]), $this->Body);
                }
            }
            //# end addition
        }
        if (!empty($html_images)) {
            // If duplicate images are embedded, they may show up as attachments, so remove them.
            $html_images = array_unique($html_images);
            sort($html_images);
            for ($i = 0; $i < count($html_images); ++$i) {
                if ($image = $this->get_template_image($templateid, $html_images[$i])) {
                    $content_type = $this->image_types[strtolower(substr($html_images[$i],
                        strrpos($html_images[$i], '.') + 1))];
                    $cid = $this->add_html_image($image, basename($html_images[$i]), $content_type);
                    if (!empty($cid)) {
                        $this->Body = str_replace(basename($html_images[$i]), "cid:$cid", $this->Body);
                    }
                }
            }
        }
        //# addition for filesystem images
        if (!empty($filesystem_images)) {
            // If duplicate images are embedded, they may show up as attachments, so remove them.
            $filesystem_images = array_unique($filesystem_images);
            sort($filesystem_images);
            for ($i = 0; $i < count($filesystem_images); ++$i) {
                if ($image = $this->get_filesystem_image($filesystem_images[$i])) {
                    $content_type = $this->image_types[strtolower(substr($filesystem_images[$i],
                        strrpos($filesystem_images[$i], '.') + 1))];
                    $cid = $this->add_html_image($image, basename($filesystem_images[$i]), $content_type);
                    if (!empty($cid)) {
                        $this->Body = str_replace(basename($filesystem_images[$i]), "cid:$cid", $this->Body); //@@@
                    }
                }
            }
        }
        //# end addition
    }

    public function add_html_image($contents, $name = '', $content_type = 'application/octet-stream')
    {
        $cid = bin2hex(random_bytes(16)); // @TODO seems this does not need to be random, just unique? perhaps hash($contents)
        $this->AddStringEmbeddedImage(base64_decode($contents), $cid, $name, 'base64', $content_type);

        return $cid;
    }

    //# addition for filesystem images

    public function filesystem_image_exists($filename)
    {
        //#  find the image referenced and see if it's on the server
        $imageroot = getConfig('uploadimageroot');
//      cl_output('filesystem_image_exists '.$docroot.' '.$filename);

        $elements = parse_url($filename);
        $localfile = basename($elements['path']);

        $localfile = urldecode($localfile);
        //     cl_output('CHECK'.$localfile);

        if (defined('UPLOADIMAGES_DIR')) {
            //  print $_SERVER['DOCUMENT_ROOT'].$localfile;
            return
                is_file($_SERVER['DOCUMENT_ROOT'].'/'.UPLOADIMAGES_DIR.'/image/'.$localfile)
                || is_file($_SERVER['DOCUMENT_ROOT'].'/'.UPLOADIMAGES_DIR.'/'.$localfile)
                //# commandline
                || is_file($imageroot.'/'.$localfile);
        } else {
            return
                is_file($_SERVER['DOCUMENT_ROOT'].$GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/image/'.$localfile)
                || is_file($_SERVER['DOCUMENT_ROOT'].$GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/'.$localfile)
                //# commandline
                || is_file('../'.FCKIMAGES_DIR.'/image/'.$localfile)
                || is_file('../'.FCKIMAGES_DIR.'/'.$localfile);
        }
    }

    public function get_filesystem_image($filename)
    {
        //# get the image contents
        $localfile = basename(urldecode($filename));
//      cl_output('get file system image'.$filename.' '.$localfile);
        if (defined('UPLOADIMAGES_DIR')) {
            //       print 'UPLOAD';
            $imageroot = getConfig('uploadimageroot');
            if (is_file($imageroot.$localfile)) {
                return base64_encode(file_get_contents($imageroot.$localfile));
            } else {
                if (is_file($_SERVER['DOCUMENT_ROOT'].$localfile)) {
                    //# save the document root to be able to retrieve the file later from commandline
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
        } elseif (is_file('../'.FCKIMAGES_DIR.'/'.$localfile)) {   //# commandline
            return base64_encode(file_get_contents('../'.FCKIMAGES_DIR.'/'.$localfile));
        } elseif (is_file('../'.FCKIMAGES_DIR.'/image/'.$localfile)) {
            return base64_encode(file_get_contents('../'.FCKIMAGES_DIR.'/image/'.$localfile));
        }

        return '';
    }

    //# end addition

    //# addition for external images

    public function external_image_exists($filename)
    {
        // Check for a http(s) address excluding this host
        if ((strpos($filename, 'http') === 0) && (strpos($filename, '://'.getConfig('website').'/') === false)) {
            $extCacheDir = $GLOBALS['tmpdir'].'/external_cache';

            // Create cache directory
            if (!file_exists($extCacheDir)) {
                @mkdir($extCacheDir);
            }

            if (file_exists($extCacheDir) && is_writable($extCacheDir)) {
                // Remove old files in cache directory
                if (defined('EXTERNALIMAGE_MAXAGE') && EXTERNALIMAGE_MAXAGE && ($extCacheDirHandle = @opendir($extCacheDir))) {
                    while (false !== ($cacheFile = @readdir($extCacheDirHandle))) {
                        if ((strlen($cacheFile) > 0) && (substr($cacheFile, 0, 1) != '.')) {
                            $cacheFileMTime = @filemtime($extCacheDir.'/'.$cacheFile);

                            if (is_numeric($cacheFileMTime) && ($cacheFileMTime > 0) && ((time() - $cacheFileMTime) > EXTERNALIMAGE_MAXAGE)) {
                                @unlink($extCacheDir.'/'.$cacheFile);
                            }
                        }
                    }

                    @closedir($extCacheDirHandle);
                }

                // Generate local filename
                //$cacheFile = $extCacheDir.'/'.$this->messageid.'_'.hash('sha256', $filename);
                $cacheFile = $extCacheDir.'/'.$this->messageid.'_'.preg_replace(array(
                        '~[\.][\.]+~Ui',
                        '~[^\w\.]~Ui',
                    ), array('', '_'), $filename);

                // Download and cache file
                if (!file_exists($cacheFile)) {
                    $cacheFileContent = '';
                    $downloadTimeout = (defined('EXTERNALIMAGE_TIMEOUT') ? EXTERNALIMAGE_TIMEOUT : 30);

                    // Try downloading using cURL
                    if (function_exists('curl_init')) {
                        $cURLHandle = curl_init($filename);

                        if ($cURLHandle !== false) {
                            //curl_setopt($cURLHandle, CURLOPT_URL, $filename);
                            curl_setopt($cURLHandle, CURLOPT_HTTPGET, true);
                            curl_setopt($cURLHandle, CURLOPT_HEADER, 0);
                            curl_setopt($cURLHandle, CURLOPT_BINARYTRANSFER, true);
                            curl_setopt($cURLHandle, CURLOPT_RETURNTRANSFER, true);
                            //curl_setopt($cURLHandle, CURLOPT_FILE, $cacheFileHandle);
                            curl_setopt($cURLHandle, CURLOPT_TIMEOUT, $downloadTimeout);
                            curl_setopt($cURLHandle, CURLOPT_FOLLOWLOCATION, true);
                            curl_setopt($cURLHandle, CURLOPT_MAXREDIRS, 10);
                            curl_setopt($cURLHandle, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($cURLHandle, CURLOPT_FAILONERROR, true);

                            $cacheFileContent = curl_exec($cURLHandle);

                            $cURLErrNo = curl_errno($cURLHandle);
                            $cURLInfo = curl_getinfo($cURLHandle);

                            curl_close($cURLHandle);

                            if ($cURLErrNo != 0) {
                                $cacheFileContent = 'CURL_ERROR_'.$cURLErrNo;
                            }
                            if ($cURLInfo['http_code'] >= 400) {
                                $cacheFileContent = 'HTTP_CODE_'.$cURLInfo['http_code'];
                            }
                        }
                    }

                    // Try downloading using file_get_contents
                    if ($cacheFileContent == '') {
                        $remoteURLContext = stream_context_create(array(
                            'http' => array(
                                'method'        => 'GET',
                                'timeout'       => $downloadTimeout,
                                'max_redirects' => '10',
                            ),
                        ));

                        $cacheFileContent = file_get_contents($filename, false, $remoteURLContext);
                        if ($cacheFileContent === false) {
                            $cacheFileContent = 'FGC_ERROR';
                        }
                    }

                    // Limit size
                    if (defined('EXTERNALIMAGE_MAXSIZE') && EXTERNALIMAGE_MAXSIZE && (strlen($cacheFileContent) > EXTERNALIMAGE_MAXSIZE)) {
                        $cacheFileContent = 'MAX_SIZE';
                    }

                    // Write cache file
                    //file_put_contents($cacheFile, $cacheFileContent, LOCK_EX);
                    $cacheFileHandle = @fopen($cacheFile, 'wb');
                    if ($cacheFileHandle !== false) {
                        if (flock($cacheFileHandle, LOCK_EX)) {
                            fwrite($cacheFileHandle, $cacheFileContent);
                            fflush($cacheFileHandle);
                            flock($cacheFileHandle, LOCK_UN);
                        }
                        fclose($cacheFileHandle);
                    }
                }

                if (file_exists($cacheFile) && (@filesize($cacheFile) > 64)) {
                    return true;
                }
            }
        }
    }

    public function get_external_image($filename)
    {
        $extCacheDir = $GLOBALS['tmpdir'].'/external_cache';
        //$cacheFile = $extCacheDir.'/'.$this->messageid.'_'.hash('sha256', $filename);
        $cacheFile = $extCacheDir.'/'.$this->messageid.'_'.preg_replace(array('~[\.][\.]+~Ui', '~[^\w\.]~Ui'),
                array('', '_'), $filename);

        if (file_exists($cacheFile) && (@filesize($cacheFile) > 64)) {
            return base64_encode(file_get_contents($cacheFile));
        }
    }

    //# end addition

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
        // as we already encoded the contents in $path, return $path
        return chunk_split($path, 76, $this->lineEnding);
    }

    /**
     * Called by phpmailer when $Mailer is set to amazonSes.
     * Builds and sends an email through Amazon SES.
     * The curl handle is persisted and closed only when an error occurs.
     *
     * @param string $header the message http headers
     * @param string $body   the message body
     *
     * @return bool whether the email was sent successfully
     */
    public function amazonSesSend($header, $body)
    {
        static $curl = null;

        if ($curl === null) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, AWS_POSTURL);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_HEADER, 1);
            curl_setopt($curl, CURLOPT_DNS_USE_GLOBAL_CACHE, true);
            curl_setopt($curl, CURLOPT_USERAGENT, NAME.' (phpList version '.VERSION.', https://www.phplist.com/)');
            curl_setopt($curl, CURLOPT_POST, 1);
        }
        $header = rtrim($header, "\r\n ");

        $date = date('r');
        $aws_signature = base64_encode(hash_hmac('sha256', $date, AWS_SECRETKEY, true));

        $requestheader = array(
            'Host: '.parse_url(AWS_POSTURL, PHP_URL_HOST),
            'Content-Type: application/x-www-form-urlencoded',
            'Date: '.$date,
            'X-Amzn-Authorization: AWS3-HTTPS AWSAccessKeyId='.AWS_ACCESSKEYID.',Algorithm=HMACSHA256,Signature='.$aws_signature,
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $requestheader);

        $rawmessage = base64_encode($header.$this->lineEnding.$this->lineEnding.$body);
        $requestdata = array(
            'Action'                => 'SendRawEmail',
            'Source'                => $GLOBALS['message_envelope'],
            'Destinations.member.1' => $this->destinationemail,
            'RawMessage.Data'       => $rawmessage,
        );
        $data = http_build_query($requestdata, null, '&');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $res = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($res === false || $status != 200) {
            $error = curl_error($curl);
            curl_close($curl);
            $curl = null;
            logEvent(sprintf('Amazon SES status: %s, result: %s, curl error: %s', $status, strip_tags($res), $error));

            return false;
        }

        return true;
    }

    /**
     * Called by phpmailer when $Mailer is set to plugin.
     *
     * @param string $header the message http headers
     * @param string $body   the message body
     *
     * @return bool success/failure
     */
    public function pluginSend($header, $body)
    {
        global $emailsenderplugin;

        return $emailsenderplugin->send($this, $header, $body);
    }

    /**
     * Called by phpmailer when $Mailer is set to localSpool.
     *
     * @param string $header the message http headers
     * @param string $body   the message body
     *
     * @return bool success/failure
     */
    public function localSpoolSend($header, $body)
    {
        $fname = tempnam(USE_LOCAL_SPOOL, 'msg');
        file_put_contents($fname, $header."\n".$body);
        file_put_contents($fname.'.S', $this->Sender);

        return true;
    }
}
