<?php

require_once dirname(__FILE__).'/../accesscheck.php';

class admin_auth {

  function validateLogin($login,$password) {
    $query
    = ' select password, disabled, id'
    . ' from %s'
    . ' where loginname = ?';
    $query = sprintf($query, $GLOBALS['tables']['admin']);
    $req = Sql_Query_Params($query, array($login));
    $admindata = Sql_Fetch_Assoc($req);
    $encryptedPass = hash(ENCRYPTION_ALGO,$password);
    $passwordDB = $admindata['password'];    
    #Password encryption verification.
    if(strlen($passwordDB)<$GLOBALS['hash_length']) { // Passwords are encrypted but the actual is not.
      #Encrypt the actual DB password before performing the validation below.
      $encryptedPassDB =  hash(ENCRYPTION_ALGO,$passwordDB);
      $query = "update %s set password = '%s' where loginname = ?";
      $query = sprintf($query, $GLOBALS['tables']['admin'], $encryptedPassDB);
      $passwordDB = $encryptedPassDB;
      $req = Sql_Query_Params($query, array($login));
    } 
    if ($admindata["disabled"]) {
      return array(0,s("your account has been disabled"));
    } elseif (#Password validation.
      !empty($passwordDB) && $encryptedPass == $passwordDB) {
      return array($admindata['id'],"OK");
    } else {
      return array(0,s("incorrect password"));
    }
    return array(0,s("Login failed"));
  }

  function getPassword($email) {
    $email = preg_replace("/[;,\"\']/","",$email);
    $query = sprintf('select email, password, loginname from %s where email = ?', $GLOBALS['tables']['admin']);
    $req = Sql_Query_Params($query, array($email));
    if (Sql_Num_Rows($req)) {
      $row = Sql_Fetch_Row($req);
      return $row[1];
    }
  }

  function validateAccount($id) {
    /* can only do this after upgrade, which means
     * that the first login will always fail
    $query
    = ' select id, disabled,password,privileges'
    . ' from %s'
    . ' where id = ?';
    */
    
    $query
    = ' select id, disabled,password'
    . ' from %s'
    . ' where id = ?';

    $query = sprintf($query, $GLOBALS['tables']['admin']);
    $req = Sql_Query_Params($query, array($id));
    $data = Sql_Fetch_Row($req);
    if (!$data[0]) {
      return array(0,s("No such account"));
    } elseif (!ENCRYPT_ADMIN_PASSWORDS && sha1($noaccess_req[2]) != $_SESSION["logindetails"]["passhash"]) {
      return array(0,s("Your session does not match your password. If you just changed your password, simply log back in."));
    } elseif ($data[1]) {
      return array(0,s("your account has been disabled"));
    }
    
    ## do this seperately from above, to avoid lock out when the DB hasn't been upgraded.
    ## so, ignore the error
    $query
    = ' select privileges'
    . ' from %s'
    . ' where id = ?';

    $query = sprintf($query, $GLOBALS['tables']['admin']);
    $req = Sql_Query_Params($query, array($id),1);
    if ($req) {
      $data = Sql_Fetch_Row($req);
    } else {
      $data = array();
    }
    
    if (!empty($data[0])) {
      $_SESSION['privileges'] = unserialize($data[0]);
    }
    return array(1,"OK");
  }

  function adminName($id) {
    $req = Sql_Fetch_Row_Query(sprintf('select loginname from %s where id = %d',$GLOBALS["tables"]["admin"],$id));
    return $req[0] ? $req[0] : s("Nobody");
  }
  
  function adminEmail($id) {
    $req = Sql_Fetch_Row_Query(sprintf('select email from %s where id = %d',$GLOBALS["tables"]["admin"],$id));
    return $req[0] ? $req[0] : "";
  }    

  function adminIdForEmail($email) { #Obtain admin Id from a given email address.
    $req = Sql_Fetch_Row_Query(sprintf('select id from %s where email = "%s"',$GLOBALS["tables"]["admin"],sql_escape($email)));
    return $req[0] ? $req[0] : "";
  } 
  
  function isSuperUser($id) {
    $req = Sql_Fetch_Row_Query(sprintf('select superuser from %s where id = %d',$GLOBALS["tables"]["admin"],$id));
    return $req[0];
  }

  function listAdmins() {
    $result = array();
    $req = Sql_Query("select id,loginname from {$GLOBALS["tables"]["admin"]} order by loginname");
    while ($row = Sql_Fetch_Array($req)) {
      $result[$row["id"]] = $row["loginname"];
    }
    return $result;
  }

}

?>
