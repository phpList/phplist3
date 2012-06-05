
<?php

if (!empty($_POST["info"])) {
  ## now make sure it only has the stuff we expect it to have
  $posted = strip_tags($_POST['info']);
  $posted = preg_replace('/\W/','',$posted);
  
  print "You typed: ".$posted."<br/>";
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



