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

if ($_SESSION["session_ok"] != 1){
   header("Location:?");
}



/***************************************************
  This script use the completly PARAMETERS structure
***************************************************/

include("lib/parameters.inc");

$inTheSame = 1;
$errmsg    = "";
$errno     = "";

if ($submited){
   /* The code above take the mission to write in config.php file
      the configuration parameters that the user charge, and another data
      witch are written by default (at least in the BASIC installation)
      It's the final Step (2)
   */

   if (!is_writable(dirname($configfile))){
      $errno  = 1;
      $errmsg = $GLOBALS["I18N"]->get(sprintf($GLOBALS["strConfigDirNotWritable"],dirname($configfile)));
   }
   else{
      if (is_file($configfile)){
         if (!is_writable($configfile)){
            $errno  = 1;
            $errmsg = $GLOBALS["I18N"]->get($GLOBALS["strConfigNotWritable"]);
         }
         else{
            copy($configfile, $configfile.".ori");
            $stat  = writeConfigFile($configfile);
     
            if (!$stat){
               $errno  = 1;
               $errmsg = $GLOBALS["I18N"]->get($GLOBALS["strConfigRewriteError"]);
            }
            else{
               $errno = 0;
               $link_o = "<a href=?>";
               $link_c = "</a>";
               $okmsg  = $GLOBALS["I18N"]->get($GLOBALS["strConfigRewrited"]);
            }
         }
      }
      else{
         $stat = writeConfigFile($configfile);
   
         if (!$stat){
            $errno = 1;
            $errmsg   = $GLOBALS["I18N"]->get($GLOBALS["strConfigRewriteError"]);
         }
         else{
            $errno = 0;
            $link_o = "<a href=?>";
            $link_c = "</a>";
            $okmsg  = $GLOBALS["I18N"]->get($GLOBALS["strConfigWrited"]);
            //header("Location:?");
         }
      }
   }
}

include("installer/lib/js_nextPage.inc");
?>

<script type="text/javascript">
/* Is needed to declare this function (even in this "dummy" way)
   because is referenced in the js_nextPage.inc file
*/

function validation(){
   return true;
}
</script>
<br>
<br>
<div class="wrong"><?php echo $errmsg?></div>
<style type="text/css">
table tr td input { float:right; }
</style>

<?php
if ($errno || !$submited){
?>
<table width=500>
  <tr>
    <td>
    <div class="explain"><?php echo $GLOBALS["I18N"]->get($GLOBALS['strReadyToInstall'])?></div>
    </td>
  </tr>
</table>

<form method="post" name="pageForm">
  <input type="hidden" name="page" value="<?php echo $nextPage?>"/>
  <input type="hidden" name="submited" value="<?php echo $inTheSame?>"/>

</form>
<?php
include("installer/lib/nextStep.inc");
?>
<?php }else{ ?>
<table width=500>
  <tr>
    <td>
    <div class="explain" align="center"><br><br><font size=4><?php echo $okmsg?></font><br><?php echo $GLOBALS["I18N"]->get(sprintf($GLOBALS['strReadyToUse'],$link_o,$link_c))?><br><br><br></div>
    </td>
  </tr>
</table>
<?php } ?>
