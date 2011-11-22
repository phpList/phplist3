<?php
/* This file receive tha variable $textSubmit from the caller script.
   This variable have the step number description for page related.
*/
$maxPage = 4;


if (isset($inTheSame))
if ($inTheSame)
     $nextPage = ($page != "")?$page:0;
else $nextPage = ($page != "")?$page+1:0;

if ($page+1 < $maxPage)
     $textSubmit = $GLOBALS["I18N"]->get($GLOBALS["strNextStep"]);
else $textSubmit = $GLOBALS["I18N"]->get($GLOBALS["strWriteToConfig"]);

//echo "PP: $page<br>";
//echo "NP: $nextPage";

?>
<script language="Javascript" type="text/javascript">
var submitted = false;

function goToPage() {
   if (validation()){
      if (!submitted) {
         submitted = true;

         document.pageForm.submit();
      }
   }
}
</script>
<noscript><input type="submit" name="submit" value="<?php echo $textSubmit?>"/></noscript>
