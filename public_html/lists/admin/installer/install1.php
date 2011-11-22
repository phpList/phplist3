<?php
/* this script is called by install.php
   that parent-script have serveral included files
   witch contain some functions used in this one.
   i.e.:
   steps-lib.php
   languages.php
   mysql.inc
   install-texts.inc
*/
echo "<!-- ".__FILE__." -->\n";

if ($_SESSION["session_ok"] != 1){
   header("Location:?");
}


echo "<!-- ".__FILE__." -->\n";

/***************************************************
  This script use only the structure BOUNCE_DEF
***************************************************/

include("lib/parameters.inc");

genPHPVariables($bounce_def); 

echo "<!-- ".__FILE__." -->\n";

if ($submited){
   /* The code above take the mission to check some mail data
      enter by the user in the Step 1.
   */

echo "<!-- ".__FILE__." -->\n";
   getPostVariables($bounce_def);

   $mail_test = processPopTest($message_envelope, $bounce_mailbox_host, $bounce_mailbox_user, $bounce_mailbox_password);
echo "<!-- mail test: ".__FILE__." -->\n";

   if (!$mail_test){
echo "<!-- getting HTML elements: ".__FILE__." -->\n";
      $HTMLElements = getHTMLElements($bounce_def); 
      $inTheSame = 1;

echo "<!-- AFTER getting HTML elements: ".__FILE__." -->\n";
      $msg = $GLOBALS["I18N"]->get("Connection refused, check your host, user or password");
   }
   else{
      setSessionData($bounce_def);
      //$_SESSION["bounce_envelope"] = $bm_mail;
      //$_SESSION["bounce_host"]     = $bm_host;
      //$_SESSION["bounce_user"]     = $bm_user;
      //$_SESSION["bounce_pass"]     = $bm_pass;

      $inTheSame = 0;

      header("Location:?page=".($page+1));
   }
}
else{
   $msg = "";
   $inTheSame = 1;

   getSessionData($bounce_def);
   $HTMLElements = getHTMLElements($bounce_def); 
   $JSElements   = getJSValidations($bounce_def, $_SESSION["installType"]); 
}

include("installer/lib/js_nextPage.inc");

$mailacc  = $GLOBALS["I18N"]->get($GLOBALS['strJsMailAccount']);
$mailvalidacc = $GLOBALS["I18N"]->get($GLOBALS['strJsMailValidAccount']);
$mailhost = $GLOBALS["I18N"]->get($GLOBALS['strJsMailHost']);
$mailuser = $GLOBALS["I18N"]->get($GLOBALS['strJsMailUser']);
$mailpass = $GLOBALS["I18N"]->get($GLOBALS['strJsMailPass']);

?>
<br>
<br>
<div class="wrong"><?php echo $msg?></div>
<style type="text/css">
//table tr td input { float:right; }
</style>

<table width=500>
  <tr>
    <td>
    <div class="explain"><?php echo $GLOBALS["I18N"]->get($GLOBALS['strExplainInstall'])?></div>
    </td>
  </tr>
</table>

<script type="text/javascript">
function validarEmail(valor) {
   if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})*$/.test(valor)){
      return (true)
   }
   else{
      return (false);
   }
}

function validation(){
   var frm = document.pageForm;
   
   if (frm.message_envelope.value == ""){
      alert("<?php echo $mailacc?>");
      frm.message_envelope.focus();

      return false;
   }
   else
   if (!validarEmail(frm.message_envelope.value)){
      alert("<?php echo $mailvalidacc?>");
      frm.message_envelope.focus();
      frm.message_envelope.select();

      return false;
   }

   if (frm.bounce_mailbox_host.value == ""){
      alert("You must enter the mail server (host) for this account");
      alert("<?php echo $mailhost?>");
      frm.bounce_mailbox_host.focus();

      return false;
   }
   
   if (frm.bounce_mailbox_user.value == ""){
      alert("You must enter the user for connect to the mail server (host)");
      alert("<?php echo $mailuser?>");
      frm.bounce_mailbox_user.focus();

      return false;
   }
   
   if (frm.bounce_mailbox_password.value == ""){
      alert("You must enter the password for this user");
      alert("<?php echo $mailpass?>");
      frm.bounce_mailbox_password.focus();

      return false;
   }

   return true;
}
</script>

<form method="post" name="pageForm">
  <input type="hidden" name="page" value="<?php echo $nextPage?>"/>
  <input type="hidden" name="submited" value="<?php echo $inTheSame?>"/>

  <table border=0 width=350>
    <?php echo $HTMLElements?>
  </table>
</form>
<?php
include("installer/lib/nextStep.inc");
?>
