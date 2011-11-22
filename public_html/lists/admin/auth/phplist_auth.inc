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
    $admindata = Sql_Fetch_Array($req);
    $encryptedPass = md5($password);
    $passwordDB = $admindata[0];    
    #Password encryption verification.
    if(ENCRYPT_ADMIN_PASSWORDS==1 && (strlen($passwordDB)<32)) { //Passwords are encrypted but the actual is not.
      #Encrypt the actual DB password before performing the validation below.
      $encryptedPassDB = md5($passwordDB);
      $query = "update %s set password = '%s' where loginname = ?";
      $query = sprintf($query, $GLOBALS['tables']['admin'], $encryptedPassDB);
      $passwordDB = $encryptedPassDB;
      $req = Sql_Query_Params($query, array($login));
    } else if (strlen($passwordDB)==32) {
    	$encryptedPassDB = $passwordDB;
	  }
    if ($admindata["disabled"]) {
      return array(0,"your account has been disabled");
    } elseif (#Password validation.
      (ENCRYPT_ADMIN_PASSWORDS && $passwordDB && $encryptedPass == $encryptedPassDB) ||
   	  (!ENCRYPT_ADMIN_PASSWORDS && $passwordDB && $password == $passwordDB)) {
      return array($admindata["id"],"OK");
    } else {
      return array(0,"invalid password");
    }
    return array(0,"Login failed");
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
    $query
    = ' select id, disabled,password'
    . ' from %s'
    . ' where id = ?';
    $query = sprintf($query, $GLOBALS['tables']['admin']);
    $req = Sql_Query_Params($query, array($id));
    $noaccess_req = Sql_Fetch_Row($req);
    if (!$noaccess_req[0]) {
      return array(0,"No such account");
    } elseif (!ENCRYPT_ADMIN_PASSWORDS && sha1($noaccess_req[2]) != $_SESSION["logindetails"]["passhash"]) {
      return array(0,"Your session does not match your password.  If you just changed your password, simply log back in.");
    } elseif ($noaccess_req[1]) {
      return array(0,"your account has been disabled");
    }
    return array(1,"OK");
  }

  function adminName($id) {
    $req = Sql_Fetch_Row_Query(sprintf('select loginname from %s where id = %d',$GLOBALS["tables"]["admin"],$id));
    return $req[0] ? $req[0] : "Nobody";
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
