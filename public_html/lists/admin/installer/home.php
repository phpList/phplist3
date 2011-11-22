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

$_SESSION["session_ok"] = 1;


$page = (!is_int($page))?0:$page;
include("installer/lib/js_nextPage.inc");
include("installer/lib/parameters.inc");

delSessionData();

/*
<div id="language_change">
  <script language="JavaScript" type="text/javascript">
  function langChange(){
     var lang_change=this.window.document.lang_change;

     if(lang_change.language_module.selectedIndex==0)return false;

     lang_change.submit();
     return true;
  }
  </script>
  <p>
  <form name="lang_change" action="" method=POST>
  <?phpecho languagePack("","langChange();")?>
  </form>
  </p>
</div>
*/

$errno = 0;
$msg   = "";

if (!is_writable(dirname($configfile))){
   $errno = 1;
   $msg   = $GLOBALS["I18N"]->get(sprintf($GLOBALS["strConfigDirNotWritable"],dirname($configfile)));
} else {
   if (is_file($configfile)){
      if (!is_writable($configfile)){
         $errno = 1;
         $msg   = $GLOBALS["I18N"]->get($GLOBALS["strConfigNotWritable"]);
      }
   }
}

?>
<script type="text/javascript">
function validation(){
   var frm = document.pageForm;
   
   return true;
}
</script>

<div id="phplist_logo_header">
  <span class="phplist_logo_span"><img src="images/phplist-logo.png" title="phplist"></span>
  <span class="title_installer"><?php print $GLOBALS["I18N"]->get(sprintf('%s',$GLOBALS["strInstallerTitle"]));?></span>
</div>
<div id="maincontent_install">
  <div class="intro_install"><?php print $GLOBALS["I18N"]->get(sprintf('%s',$GLOBALS["strIntroInstaller"]));?></div>
  <?php  if ($errno) { ?>
  <div class="allwrong"><?php print $msg?></div>
  <?php } ?>
  <div class="intro_install">
  <?php print $GLOBALS["I18N"]->get(sprintf('%s',$GLOBALS["strChooseInstallation"]));?>
  <form method="post" name="pageForm">
    <input type="hidden" name="page" value="<?php echo $nextPage?>"/>
    <input type="hidden" name="submited" value="<?php echo $inTheSame?>"/>
    <input type=radio name=insttype value=BASIC checked>Basic installation<br>
    <input type=radio name=insttype value=ADVANCED>Advanced installation
  </form>
  </div>
</div>

<?php
if (!$errno)
include("installer/lib/nextStep.inc");
?>
