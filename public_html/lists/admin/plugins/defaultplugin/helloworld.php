
<?php

if ($_POST["info"]) {
  print "You typed: ".$_POST["info"]."<br/>";
  print "<b>Thanks for that.</b>";
  return;
}

?>

Default plugin Hello World page

<form method="post">
Enter some info: <input type="text" name="info" value="Hello World" size="30">
<br/>
<input type="submit" class="submit">
</form>



