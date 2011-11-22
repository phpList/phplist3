<?php
class email2fax extends phplistPlugin {
  var $name = "Email 2 Fax";
  var $coderoot = "email2fax/";
  var $config = array();
  var $faxattname;

  var $configvars = array(
    # config var    array( type, name )
    'provider' => array('text','Provider Domain'),
    'faxattribute' => array('attributeselect','Fax Attribute'),
    'prefix' => array('text','Prefix in address'),
    'toformat' => array('text','format for To Line'),
    'htmldocbin' => array('text','htmldoc binary location'),
    'htmldocoptions' => array('textarea','htmldoc commandline options'),
    'sendas' => array('radio','Send Fax as',array('html' => 'HTML attachment','pdf' => 'PDF attachment')),
  );

  function email2fax() {
    parent::phplistplugin();
  }
  
  function activate() {
    parent :: activate();
    foreach ($this->configvars as $var => $desc) {
      $this->config[$var] = $this->getConfig($var);
    }
    $this->getFaxAttributeName();
    return true;
  }

  function getFaxAttributeName() {
    $req = Sql_Fetch_Row_Query(sprintf('select name from %s where id = %d',$GLOBALS['tables']['attribute'],$this->config['faxattribute']));
    $this->faxattname = $req[0];
  }

  function adminmenu() {
    return array(
      "main" => "Email 2 Fax configuration"
    );
  }

  function sendFormats() {
    return array('fax' => 'Fax');
  }

  function setFinalDestinationEmail($messageid,$uservalues,$email) {
    $data = loadMessageData($messageid);
    if ($data['sendformat'] != 'fax') return $email;
    if (!isset($this->faxattname)) $this->getFaxAttributeName();
    if (!isset($uservalues[$this->faxattname])) {
      return $email;
    }
    $fax = $uservalues[$this->faxattname];
    $fax = preg_replace("/\D/",'',$fax);
    $prefix = $this->config['prefix'];
    $provider = $this->config['provider'];
    $format = $this->config['toformat'];

    $format = str_replace('[prefix]',$prefix,$format);
    $format = str_replace('[provider]',$provider,$format);
    $format = str_replace('[providerdomain]',$provider,$format);
    $format = str_replace('[fax]',$fax,$format);
    $format = str_replace('[faxnumber]',$fax,$format);
    foreach ($uservalues as $att => $value) {
      $format = str_replace('['.$att.']',$value,$format);
    }
    return $format;
  }

  function validateHtmlDocBinary($htmldoc) {
    ## make sure the binary called is actually htmldoc
    $bn = basename($htmldoc);
    if ($bn !== 'htmldoc') return 0;
    ## add some more checks?
    return 1;
  }

  function parseFinalMessage($sendformat,$htmlmessage,$textmessage,&$mail) {
    if ($sendformat != 'fax') {
      return 0;
    }

    $tmphtml = tempnam($GLOBALS['tmpdir'],'phplistfaxsource');
    $fp = @fopen($tmphtml,'w');
    @fwrite($fp,$htmlmessage);
    @fclose($fp);
    if (!filesize($tmphtml)) { 
      unlink($tmphtml);
      return 0;
    }
    clearstatcache();
  
    $fax = tempnam($GLOBALS['tmpdir'],'phplistfax');
    if (is_file($fax)) {
      unlink($fax);
    }

    $htmldoc = $this->config['htmldocbin'];
    if (!is_executable($htmldoc)) {
      unlink($tmphtml);
      unlink($fax);
      return 0;
    }
    if (!$this->validateHtmlDocBinary($htmldoc)) {
      return 0;
    }
    $htmldoc .= ' --quiet ';
    switch ($this->config['sendas']) {
      case 'html':
        $htmldoc .= ' --format html';
        $faxname = 'fax.htm';
        $faxmime = 'text/html';
        break;
      default:
        $faxname = 'fax.pdf';
        $faxmime = 'application/pdf';
        $htmldoc .= ' --format pdf14';
        break;
    }
    
    $options = $this->config['htmldocoptions'];
    $options = str_replace("\n"," ",$options);
    $options = str_replace("\r"," ",$options);
    $options = preg_replace('/^[-\w\s]/','',$options);
    $htmldoc .= ' '.$options .' -f '.$fax;
    #print $htmldoc;exit;
    $commandresult = system($htmldoc.' '.$tmphtml);
    $contents = @file_get_contents($fax);
    @unlink($tmphtml);
    @unlink($fax);
    if (!strlen($contents)) return 0;
    $mail->add_attachment($contents,
      $faxname,
      $faxmime);
    return 1;
  }
}
?>
