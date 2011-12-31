<?php

$status = '';

if (empty($_REQUEST['id'])) {
  $id = '';
} else {
  $id = $_REQUEST['id'];
  if (!isset($default_config[$id])) {
    print $GLOBALS['I18N']->get('invalid request');
    return;
  }
}

/*
printf('<script type="text/javascript">
$(".configValue").each(function() {
  if (this.id != "edit_%s") {
    this.innerHTML = "Not editing";
  }
});
</script>',$id);
*/

$val = $default_config[$id];
printf('<div class="configEditing">' . $GLOBALS['I18N']->get('Editing') . ' <b>%s</b></div>',$GLOBALS['I18N']->get($val[1]));
printf('<div class="configValue" id="edit_%s"><input type="hidden" name="id" value="%s" />',$id,$id);
$dbval = getConfig($id);
#  print $dbval.'<br/>';
if (isset($dbval))
  $value = $dbval;
else
  $value = $val[0];
#  print $id.' '.$value . " ".$website . " ".$domain.'<br/>';

if ($id != "website" && $id != "domain") {
  $value = str_replace($domain,'[DOMAIN]', $value);
  $value = str_replace($website,'[WEBSITE]', $value);
}

#  print "VALUE:".$value . '<br/>';
if ($val[2] == "textarea") {
  printf('<textarea name="values[%s]" rows=25 cols=55>%s</textarea>',
    $id,htmlspecialchars(stripslashes($value)));
} else if ($val[2] == "text") {
  printf('<input type="text" name="values[%s]" size="70" value="%s" />',
  $id,htmlspecialchars(stripslashes($value)));
} else if ($val[2] == "boolean") {
  printf ('<select name="values[%s]">',$id);
  print '<option value="true" ';
  if ($value == 'true') {
    print 'selected="selected"';
  }
  print '>';
  print $GLOBALS['I18N']->get('Yes') ;
  print '  </option>';
  print '<option value="false" ';
  if ($value == 'false') {
    print 'selected="selected"';
  }
  print '>';
  print $GLOBALS['I18N']->get('No') ;
  print '  </option>';
  print '</select>';
}
print '<input type="hidden" name="save" value="1" /><br/><input class="submit" type="submit" name="savebutton" value="' . $GLOBALS['I18N']->get('save changes') . '" /></div>';
