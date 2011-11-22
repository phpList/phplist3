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

if ($_SESSION["installType"] == "BASIC"){
   header("Location:?page=3");
}

/*********************************************************
  This script is used to autogenerate the ADVANCE pages
*********************************************************/

include("lib/parameters.inc");

$group = (isset($_POST["group"]) && $_POST["group"] > 0 && $_POST["group"] < sizeof($parameters))?$_POST["group"]:0;
$msg   = "";

getSessionData($parameters[$group]["parameters"]);

if (isset($_POST['option']) && $_POST['option'] != ""){
   if ($_POST['option'] != 'Back'){
      getPostVariables($parameters[$group-1]["parameters"]);
      setSessionData($parameters[$group-1]["parameters"]);
   
      if ($_POST['option'] == "Finish") header("Location:?page=3");
   }
}


$withAdvanced = 0;

while (!$withAdvanced){
   $estruct = $parameters[$group]["parameters"];
   $title   = $GLOBALS["I18N"]->get($parameters[$group]['name']);
 
   $HTMLElements = getHTMLElements($estruct, "ADVANCED");
   $JSElements   = getJSValidations($estruct, "ADVANCED");
    
   if (is_bool($HTMLElements) && !$HTMLElements)
        $withAdvanced = 0;
   else $withAdvanced = 1;

   $group++;
}
?>

<br>
<br>
<div class="wrong"><?php echo $msg?></div>
<style type="text/css">
//table tr td input { float:right; }
</style>

<?php echo $JSElements;?>
<script type='text/javascript'>
function submission(opt){
   if (opt != "Back"){
      if (validar()){
         document.pageForm.option.value = opt;
         document.pageForm.submit();
      }
   }
   else document.backForm.submit();
}
</script>

<table width=500>
  <tr>
    <td>
    <div class="explain">
    <?php
    echo $GLOBALS["I18N"]->get($GLOBALS['strAdvanceMode']).
         "<BR>".
         "<font size=3>".$title."</font>";
    ?>
    </div>
    </td>
  </tr>
</table>
<form method="post" name="backForm">
  <input type="hidden" name="page" value="<?php echo $page?>"/>
  <input type="hidden" name="group" value="<?php echo $group-2  // because $group was incremented yet to say what is the next group?>"/>
  <input type="hidden" name="option" id="option" value="Back"/>
</form>
<form method="post" name="pageForm">
  <input type="hidden" name="page" value="<?php echo $page?>"/>
  <input type="hidden" name="group" value="<?php echo $group?>"/>
  <input type="hidden" name="option" id="option"/>
  <table border=0>
  <?php echo $HTMLElements?>
  </table>
  <br>
  <br>
  <input type=button value="&laquo;&nbsp;Back" onClick="javascript:submission('Back')" <?php echo ($group-1 <= 0)?"disabled":""?>>
  <input type=button value="Next&nbsp;&raquo;" onClick="javascript:submission('Next')" <?php echo ($group == sizeof($parameters))?"disabled":""?>>
  <input type=button value="Finish Advance installation" onClick="javascript:submission('Finish')">
</form>
