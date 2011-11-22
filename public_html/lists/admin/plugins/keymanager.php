<?php
class keymanager extends phplistPlugin {
  var $name = "Key Manager";
  var $coderoot = "keymgr/";
  var $keyring = "keyring"; # set this to the location of your keyring
  var $keyreq = '';
  var $uiditems = array('email','uid','comments');
  var $keyitems = array('timestamp','disabled','expired','expires','revoked','invalid','is_secret','can_sign','can_encrypt','fingerprint');
  var $LE = "\r\n";
  var $enabled = 1;

  var $DBstructkm = array(
    'keymanager_keys' => array(
      'id' => array('integer not null primary key auto_increment','ID'),
      'keyid' => array('varchar(255) not null','Key ID'),
      'email' => array('varchar(255)','Email'),
      'name' => array('varchar(255)','Name'),
      'fingerprint' =>  array('varchar(255)','Key fingerprint'),
      'can_encrypt' => array('tinyint',''),
      'can_sign' => array('tinyint',''),
      'deleted' => array('tinyint default 0',''),
    ),
    'keymanager_keydata' => array(
      'name' => array('varchar(255)',''),
      'id' => array('integer not null',''),
      'data' => array('text',''),
      'primary key' => array('(name,id)',''),
    ),
  );

  var $configvars = array(
    # config var    array( type, name )
    'keyattribute' => array('attributeselect','Public Key Attribute'),
    'keyringlocation' => array('text','Location of Keyring'),
  );

  function keymanager() {
    parent::phplistplugin();
    if (!class_exists('gnupg')) $this->enabled = 0;
  }

  function activate() {
    parent :: activate();
#    error_reporting(E_ALL);
    $keyring = $this->getConfig('keyringlocation');
    if (is_dir($keyring) && is_writable($keyring)) {
      putenv('GNUPGHOME='.$keyring);
    } elseif (is_dir(dirname(__FILE__).'/keymgr/'.$this->keyring) && is_writable(dirname(__FILE__).'/keymgr/'.$this->keyring)) {
      putenv('GNUPGHOME='.dirname(__FILE__).'/keymgr/'.$this->keyring);
    } else {
      $this->enabled = 0;
      #print $GLOBALS['I18N']->get('invalid keyring location');
      if (is_object( $GLOBALS['I18N'])) {
        print $GLOBALS['I18N']->get('invalid keyring location');
      } else {
        print "Invalid keyring location";
     #   logevent("Invalid keyring location: ".$keyring);
      }
    }
    return true;
  }

  function initialise() {
    foreach ($this->DBstructkm as $table => $structure) {
      print $table.'<br/>';
      Sql_Create_Table($table,$structure);
    }
  }

  function getAllKeys() {
#    $this->keyreq = Sql_Query(sprintf('select * from keymanager_keys where !deleted'));
    $this->keyreq = Sql_Query(sprintf('select * from keymanager_keys'));
    return Sql_Affected_rows();
  }

  function getNextKey() {
    if ($this->keyreq) {
      return Sql_Fetch_Array($this->keyreq);
    }
    return 0;
  }

  function getKeyDetails($id,$field = '') {
    $data = array();
    $req = Sql_Query(sprintf('select * from keymanager_keydata where id = %d',$id));
    while ($row = Sql_Fetch_Array($req)) {
      $data[$row['name']] = $row['data'];
    }
    if ($field) {
      if (isset($data[$field])) {
        return $data[$field];
      } else {
        return '';
      }
    } else {
      return $data;
    }
  }

  function menu() {
    return '<div class="menu">[ '.PageLink2('configure',$GLOBALS['I18N']->get('Configure')).' | '.
      PageLink2('add',$GLOBALS['I18N']->get('Add a key')).' | '.PageLink2('main',$GLOBALS['I18N']->get('List keys')) .' | '.PageLink2('sync',$GLOBALS['I18N']->get('Synchronise keys')) .' | '.PageLink2('sign',$GLOBALS['I18N']->get('Sign Text')) .' | '.PageLink2('encrypt',$GLOBALS['I18N']->get('Encrypt Text')) .' ]</div><br/>';
  }

  function parse_info($info) {
  }

  function get_signing_emails() {
    $result = array();
    $req = Sql_Query(sprintf('select email from keymanager_keys where can_sign=1'));
    while ($row = Sql_Fetch_Row($req)) {
      array_push($result,$row[0]);
    }
    if (!sizeof($result)) {
      $result[0] = $GLOBALS['I18N']->get('No emails capable of signing');
    }
    return $result;
  }

  function get_encrypting_emails() {
    $result = array();
    $req = Sql_Query(sprintf('select email from keymanager_keys where can_encrypt=1'));
    while ($row = Sql_Fetch_Row($req)) {
      array_push($result,$row[0]);
    }
    if (!sizeof($result)) {
      $result[0] = $GLOBALS['I18N']->get('No emails capable of encrypting');
    }
    return $result;
  }

  function get_sign_key($email) {
    $key = Sql_Fetch_Row_Query(sprintf('select keyid from keymanager_keys where email = "%s" and can_sign=1',$email));
    return $key[0];
  }

  function get_encrypt_key($email) {
    $key = Sql_Fetch_Row_Query(sprintf('select keyid from keymanager_keys where email = "%s" and can_encrypt=1',$email));
    return $key[0];
  }

  function sign_text($text,$email = '',$passphrase) {
    $gnupg = new gnupg();
    if (!$email) return '';
    $gnupg->setsignmode(GNUPG_SIG_MODE_CLEAR);
    $key = $this->get_sign_key($email);
    if (!$key) return '';
    $gnupg->addsignkey($key,$passphrase);
#    $gnupg->clearsignkeys();
#    $gnupg->seterrormode(GNUPG_ERROR_WARNING);
    $signed = $gnupg->sign($text);
    $gnupg->clearsignkeys();
    return $signed;
  }

  function encrypt_sign_text($text,$email,$passphrase,$destination) {
    $gnupg = new gnupg();
    if (!$email) return '';
    $signkey = $this->get_sign_key($email);
    if (!$signkey) return '';
    $enckey = $this->get_encrypt_key($destination);
    if (!$enckey) return '';
    $gnupg->setsignmode(GNUPG_SIG_MODE_CLEAR);
    $gnupg->addsignkey($signkey,$passphrase);
    $gnupg->addencryptkey($enckey);
    $encsigned = $gnupg->encryptsign($text);
    $gnupg->clearsignkeys();
    $gnupg->clearencryptkeys();
    return $encsigned;
  }

  function encrypt_text($text,$email) {
    $gnupg = new gnupg();
    if (!$email) return '';
    $key = $this->get_encrypt_key($email);
    if (!$key) return '';
    $gnupg->addencryptkey($key);
    $encrypted = $gnupg->encrypt($text);
    $gnupg->clearencryptkeys();
    return $encrypted;
  }

  function get_text_signature($text,$email = '',$passphrase) {
    $gnupg = new gnupg();
    if (!$email) return '';
    $key = $this->get_sign_key($email);
    if (!$key) return '';
    $gnupg->setsignmode(GNUPG_SIG_MODE_DETACH);
    $gnupg->addsignkey($key,$passphrase);
    $signed = $gnupg->sign($text);
    $gnupg->clearsignkeys();
    return $signed;
  }

  function printArray($ar,$nest = 0) {
    if (!is_array($ar)) return '';
    $res = '';
    foreach ($ar as $k1 => $v1) {
      $res .= "$k1 => ";
      if (is_array($v1)) {
        $res .= 'Array: '.$this->printArray($v1,$nest++);
      } else {
        $res .= htmlspecialchars($v1);
      }
      $res .= '<br/>';
    }
    return $res;
  }

  function parseKeyInfo($info) {
#    $uids = array();

    ## for now we can only handle one uid per key
#    foreach ($info['uids'] as $uid) {
      $uid = $info['uids'][0];
#    }
    $keys = array();
    foreach ($info['subkeys'] as $subkey) {
      if (isset($subkey['keyid'])) {
        $keyid = $subkey['keyid'];
        $keys[$keyid] = array();
        $keys[$keyid]['keyid'] = $keyid;
        $keys[$keyid]['uid'] = $uid;
        foreach ($this->keyitems as $item) {
          if (isset($subkey[$item]) && $subkey[$item]) {
            $keys[$keyid][$item] = $subkey[$item];
          } elseif (!isset($keys[$keyid][$item])) {
            $keys[$keyid][$item] = '';
          }
        }
      }
    }
    return $keys;
  }

  function store_key($key) {
    if (!isset($key['keyid'])) return '';
    $existing = Sql_Fetch_Row_Query(sprintf('select id from keymanager_keys where keyid = "%s"',$key['keyid']));
    if ($existing[0]) {
      Sql_Query(sprintf('
        update keymanager_keys set email = "%s",name="%s",fingerprint ="%s", can_encrypt = %d,can_sign=%d where id = %d',
          $key['uid']['email'],$key['uid']['name'],$key['fingerprint'],$key['can_encrypt'],$key['can_sign'],$existing[0]));
      $id = $existing[0];
    } else {
      Sql_Query(sprintf('
        insert into keymanager_keys set id = 0,keyid = "%s",email = "%s",name="%s",fingerprint ="%s", can_encrypt = %d,can_sign=%d',
          $key['keyid'],$key['uid']['email'],$key['uid']['name'],$key['fingerprint'],$key['can_encrypt'],$key['can_sign'],$existing[0]));
      $id = Sql_Insert_Id('keymanager_keys', 'id');
    }
    foreach ($key as $k=>$v) {
      if (is_array($v)) {
        $data = serialize($v);
      } else {
        $data = $v;
      }
      Sql_Query(sprintf('replace into keymanager_keydata (name,id,data) values("%s",%d,"%s")', $k,$id,addslashes($data)));
    }
  }

  function key_stats() {
    $num = array();
    $req = Sql_Fetch_Row_Query(sprintf('select count(*) from keymanager_keys'));
    $num['numkeys'] = $req[0];
    $req = Sql_Fetch_Row_Query(sprintf('select count(distinct email) from keymanager_keys'));
    $num['emails'] = $req[0];
    return $num;
  }

  function sync_user_keys() {
    $keyattribute = $this->getConfig('keyattribute');
    if (!$keyattribute) return 0;

    $ok = 1;
    $req = sql_Query(sprintf('select value from %s useratt where useratt.attributeid = %d',$GLOBALS['tables']['user_attribute'],$keyattribute));
    while ($row = Sql_Fetch_Row($req)) {
      if (trim($row[0]) != '') {
        $ok = $ok && $this->add_key($row[0]);
      }
    }
    return $ok;
  }

  function sync_keys() {
    $usersync = $this->sync_user_keys();
    if (!$usersync) return 0;
    $gpg = new gnupg();
    $list = array();
    $info = $gpg -> keyinfo('');
#    print_r($info);
    foreach ($info as $keyinfo) {
/*      print '<hr/>';
      print $this->printArray($keyinfo);*/
      $keys = $this->parseKeyInfo($keyinfo);
//       print '<hr/>';
      foreach ($keys as $key) {
        $this->store_key($key);
      }
    }
    return 1;
  }

  function add_key($key) {
    $gnupg = new gnupg();
    echo $gnupg -> geterror();
    $gnupg->seterrormode(gnupg::ERROR_EXCEPTION); // throw an exception in case of an error
    $gnupg->setsignmode(gnupg::SIG_MODE_NORMAL);
#    print "importing <pre>$key</pre>";
    
    $info = $gnupg->import($key);
    echo $gnupg -> geterror();
    return $info;
  }

  function keyid($id) {
    $req = Sql_Fetch_Row_Query(sprintf('select keyid from keymanager_keys where id = %d',$id));
    return $req[0];
  }

  function del_key($id) {
    $keyid = $this->keyid($id);
    ## there doesn't seem to be an option to remove it from the keyring.
    $gnupg = new gnupg();
    $gnupg->deletekey($keyid,1);
#    Sql_Query(sprintf('update keymanager_keys set deleted = 1 where id = %d',$id));
    Sql_Query(sprintf('delete from keymanager_keys where id = %d',$id));
    Sql_Query(sprintf('delete from keymanager_keydata where id = %d',$id));
  }

  function sendMessageTab($messageid = 0,$data = array()) {
    if (!$this->enabled) return;
    $html = sprintf('<br/>
      <input type="hidden" name="keymanager_signmessage_actualstate" value="%s" />
    <script language="Javascript" type="text/javascript">
    function update_actualstate(checkboxname) {
      document.sendmessageform[checkboxname+"_actualstate"].value = document.sendmessageform[checkboxname].checked ;
    }
    </script>
    '.$GLOBALS['I18N']->get('Sign message').': <input type="checkbox" name="keymanager_signmessage" value="" onchange="update_actualstate(\'keymanager_signmessage\');" %s/>
    <br/>'.$GLOBALS['I18N']->get('Select email to sign with').':
    <select name="keymanager_emailtosign">
   ',isset($data['keymanager_signmessage_actualstate']) ? $data['keymanager_signmessage_actualstate'] : '',
    (isset($data['keymanager_signmessage_actualstate']) && $data['keymanager_signmessage_actualstate'] == 'true')? ' checked="checked" ':'');
    $signing_emails = $this->get_signing_emails();
    foreach ($signing_emails as $email) {
      $html .= '<option ';
      $html .= (!empty($data['keymanager_emailtosign']) && $data['keymanager_emailtosign'] == $email) ? 'selected="selected" />':' />';
      $html .=  $email.'</option>';
    }
    $html .= sprintf('
      </select><br/>
      '.$GLOBALS['I18N']->get('Enter pass phrase').': <input type="password" name="keymanager_passphrase" value="%s"><br/>
    ',isset($data['keymanager_passphrase'])?$data['keymanager_passphrase']:'');
    if (!empty($data['keymanager_passphrase'])) {
      $testsign = 'Hello World';
      $signed = $this->sign_text($testsign,$data['keymanager_emailtosign'],$data['keymanager_passphrase']);
      if (!$signed) {
        Error($GLOBALS['I18N']->get('incorrect passphrase, signing will fail'));
      } else {
        $html .= '<b>'.$GLOBALS['I18N']->get('Passphrase correct').'</b>';
      }
    }

    $html .= sprintf('<br/>
      <input type="hidden" name="keymanager_encryptmessage_actualstate" value="%s" />
    '.$GLOBALS['I18N']->get('Encrypt message').': <input type="checkbox" name="keymanager_encryptmessage" value="" onchange="update_actualstate(\'keymanager_encryptmessage\');" %s/>
   ',isset($data['keymanager_encryptmessage_actualstate']) ? $data['keymanager_encryptmessage_actualstate'] : '',
    (isset($data['keymanager_encryptmessage_actualstate']) && $data['keymanager_encryptmessage_actualstate'] == 'true')? ' checked="checked" ':'');

    $html .= sprintf('<br/>
    '.$GLOBALS['I18N']->get('When a message cannot be encrypted because the public key cannot be found').':<br/>'.$GLOBALS['I18N']->get('Send it anyway, but unencrypted').' <input type="radio" name="keymanager_encryptmessage_sendanyway" value="yes" %s />
    '.$GLOBALS['I18N']->get('Do not send it').' <input type="radio" name="keymanager_encryptmessage_sendanyway" value="no" %s />   ',(isset($data['keymanager_encryptmessage_sendanyway']) && $data['keymanager_encryptmessage_sendanyway'] == 'yes') || !isset($data['keymanager_encryptmessage_sendanyway']) ? 'checked="checked"' : '',
    (isset($data['keymanager_encryptmessage_sendanyway']) && $data['keymanager_encryptmessage_sendanyway'] == 'no')? ' checked="checked" ':'');
    return $html;
  }

  ## If adding a TAB to the Send a Message page, what is the TAB's name
  # parameters: none
  # returns: short title (less than about 10 characters)
  function sendMessageTabTitle($messageid = 0) {
    if (!$this->enabled) return '';
    return 'PGP';
  }

  function initVars($data) {
    if (!isset($data['keymanager_encryptmessage_actualstate'])) { $data['keymanager_encryptmessage_actualstate'] = ''; }
    if (!isset($data['keymanager_signmessage_actualstate'])) { $data['keymanager_signmessage_actualstate'] = ''; }
    if (!isset($data['keymanager_encryptmessage_sendanyway'])) { $data['keymanager_encryptmessage_sendanyway'] = ''; }
    if (!isset($data['keymanager_emailtosign'])) { $data['keymanager_emailtosign'] = ''; }
    if (!isset($data['keymanager_passphrase'])) { $data['keymanager_passphrase'] = ''; }
    return $data;
  }

  function parseOutgoingTextMessage($messageid,$content,$destination = '',$userhtmlpref = 0) {
#    return $content;
    if (!$this->enabled) return parent::parseOutgoingTextMessage($messageid,$content,$destination,$userhtmlpref);
    # we sign a text message inline, so it remains a text message
    $data = loadMessageData($messageid);
    $data = $this->initVars($data);

    # if the user wants to receive html and the message is sent that way, don't sign or encrypt here
    if ($data['sendformat'] != 'text' && $userhtmlpref) return $content;
    $result = $content;
    if ($data['keymanager_signmessage_actualstate'] == 'true' && $data['keymanager_encryptmessage_actualstate'] == 'true') {
      $encsigned = $this->encrypt_sign_text($result,$data['keymanager_emailtosign'],$data['keymanager_passphrase'],$destination);
      if ($encsigned) {
        $result = $encsigned;
      } elseif ($data['keymanager_encryptmessage_sendanyway'] == 'yes') {
        logEvent('unable to encrypt message to '.$destination.' sending unencrypted');
        $signed = $this->sign_text($result,$data['keymanager_emailtosign'],$data['keymanager_passphrase']);
        if ($signed) {
          $result = $signed;
        }
      } else {
        logEvent('unable to encrypt message to '.$destination.' not sending at all');
      }
    } elseif ($data['keymanager_signmessage_actualstate'] == 'true') {
      $signed = $this->sign_text($result,$data['keymanager_emailtosign'],$data['keymanager_passphrase']);
      if ($signed) {
        $result = $signed;
      }
    } elseif ($data['keymanager_encryptmessage_actualstate'] == 'true') {
      $encrypted = $this->encrypt_text($result,$destination);
      if ($encrypted) {
        $result = $encrypted;
      } elseif ($data['keymanager_encryptmessage_sendanyway'] == 'yes') {
        logEvent('unable to encrypt message to '.$destination.' sending unencrypted');
      } else {
        logEvent('unable to encrypt message to '.$destination.' not sending');
        return '';
      }
    }
    return $result;
  }

  function parseOutgoingHTMLMessage($messageid,$content,$destination = '',$userhtmlpref = 0) {
    if (!$this->enabled) return parent::parseOutgoingHTMLMessage($messageid,$content,$destination,$userhtmlpref);
    # an HTML message is signed in pgp/Mime so here we don't do anything
    return $content;
  }

  function extractSignature($content) {
    if (preg_match('/-----BEGIN PGP SIGNATURE-----(.*)-----END PGP SIGNATURE-----/Uims',$content,$regs)) {
      return $regs[0];
    }
    return '';
  }

  function createMessageSignature($messageid,$content) {
    $data = loadMessageData($messageid);
    $data = $this->initVars($data);
    if ($data['keymanager_signmessage_actualstate'] == 'true') {
      $signature = $this->get_text_signature($content,$data['keymanager_emailtosign'],$data['keymanager_passphrase']);
      if ($signature) {
        return array(
          'content' => $signature,
          'filename' => $data['keymanager_emailtosign'].'.asc',
          'mimetype' => 'application/pgp-signature',
        );
      }
    }
    return '';
  }

  function fixLineEnds($text) {
    $text = str_replace("\r\n", "\n", $text);
    $text = str_replace("\r", "\n", $text);
    $text = str_replace("\n", $this->LE, $text);
    return $text;
  }

  function pgpMimeSign($messageid,$header,$body,$contenttype) {
    $uniq_id = md5(uniqid(time()));
    $boundary = "phplist" . $uniq_id;
    $signature = $this->createMessageSignature($messageid,$body);

    $ctype = sprintf("Content-Type: %s;%s\tmicalg=pgp-sha1;%s\tprotocol=\"application/pgp-signature\";%s\tboundary=\"%s\"%s",
                                    "multipart/signed", $this->LE, $this->LE,$this->LE,
                                    $boundary,$this->LE);

    $content = $contenttype."\n".$body;
    $content = $this->fixlineEnds($content);

    $signature = $this->createMessageSignature($messageid,$content);

    $body = 'This is an OpenPGP/MIME signed message (RFC 2440 and 3156)

--'.$boundary."\n".$content.'
--'.$boundary.'
Content-Type: application/pgp-signature; name="signature.asc"
Content-Description: this is the signature of the email
Content-Generator: pgp-plugin for phplist, www.phplist.com'."\n\n".$signature['content'].'--'.$boundary.'--';
    $body = $this->fixLineEnds($body);
    return array($header,$body,$ctype);
  }

  function pgpMimeEncrypt($messageid,$header,$body,$contenttype,$destination) {
    $uniq_id = md5(uniqid(time()));
    $boundary = "phplist" . $uniq_id;

    $ctype = sprintf("Content-Type: %s;%s\tprotocol=\"application/pgp-encrypted\";%s\tboundary=\"%s\"%s",
                                    "multipart/encrypted", $this->LE,$this->LE,
                                    $boundary,$this->LE);

    $content = $contenttype."\n".$body;
    $content = $this->fixlineEnds($content);

    $encrypted = $this->encrypt_text($content,$destination);
    if (!$encrypted) return '';
    $body = 'This is an OpenPGP/MIME encrypted message (RFC 2440 and 3156)

--'.$boundary."\n".'Content-Type: application/pgp-encrypted
  Version: 1

--'.$boundary.'
Content-Type: application/octet-stream
Content-Description: this is a pgp encoded message that has to be decrypted using a private key and a passphrase
Content-Generator: pgp-plugin for phplist, www.phplist.com'."\n\n".$encrypted.'

--'.$boundary.'--';

    $body = $this->fixLineEnds($body);
    return array($header,$body,$ctype);
  }

  function mimeWrap($messageid,$body,$header,$contenttype,$destination) {
    if (!$this->enabled) return parent::mimeWrap($messageid,$body,$header,$contenttype,$destination);
    $data = loadMessageData($messageid);
    $data = $this->initVars($data);
    $result = array();
    if ($data['keymanager_signmessage_actualstate'] == 'true' && $data['keymanager_encryptmessage_actualstate'] == 'true') {
      $signed = $this->pgpMimeSign($messageid,$header,$body,$contenttype);
      if ($signed) {
        $result = $this->pgpMimeEncrypt($messageid,$header,$signed[1],$signed[2],$destination);
        if (!$result && $data['keymanager_encryptmessage_sendanyway'] == 'yes') {
          logEvent('unable to encrypt message to '.$destination.' sending unencrypted');
          $result = $signed;
        }
      } else {
        logEvent('unable to sign message to '.$destination.' not sending at all');
      }
    } elseif ($data['keymanager_signmessage_actualstate'] == 'true') {
      $signed = $this->pgpMimeSign($messageid,$header,$body,$contenttype);
      if ($signed) {
        $result = $signed;
      }
    } elseif ($data['keymanager_encryptmessage_actualstate'] == 'true') {
      $encrypted = $this->pgpMimeEncrypt($messageid,$header,$body,$contenttype,$destination);
      if ($encrypted) {
        $result = $encrypted;
      } elseif ($data['keymanager_encryptmessage_sendanyway'] == 'yes') {
        return array($header,$body,$contenttype);
      } else {
        logEvent('unable to encrypt message to '.$destination.' not sending at all');
        return '';
      }
    }
    return $result;
  }

  function adminmenu() {
    if (Sql_Table_exists('keymanager_keys')) {
      return array(
        "main" => $GLOBALS['I18N']->get("Manage PGP keys")
      );
    } else {
      return array(
        "initialise" => $GLOBALS['I18N']->get("Initialise Keymanager")
      );
    }
  }

}
?>
