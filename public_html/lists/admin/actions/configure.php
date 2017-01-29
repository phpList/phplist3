<?php

$status = '';

if (empty($_REQUEST['id'])) {
    $id = '';
} else {
    $id = $_REQUEST['id'];
    if (!isset($default_config[$id])) {
        echo $GLOBALS['I18N']->get('invalid request');

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

$configItem = $default_config[$id];
printf('<div class="configEditing" id="descriptionitem_'.$id.'">'.s('Editing').' <b>%s</b></div>',
    $configItem['description']);
printf('<div class="configValue" id="edit_%s"><input type="hidden" name="id" value="%s" />', $id, $id);
$dbval = getConfig($id);
//  print $dbval.'<br/>';
if (isset($dbval)) {
    $value = $dbval;
} else {
    $value = $configItem['value'];
}
//  print $id.' '.$value . " ".$website . " ".$domain.'<br/>';

if ($id != 'website' && $id != 'domain') {
    $value = str_replace($GLOBALS['website'], '[WEBSITE]', $value);
    $value = str_replace($GLOBALS['domain'], '[DOMAIN]', $value);
}

//  print "VALUE:".$value . '<br/>';
if ($configItem['type'] == 'textarea') {
    printf('<textarea name="values[%s]" rows=25 cols=55>%s</textarea>',
        $id, htmlspecialchars(stripslashes($value)));
} elseif (
    $configItem['type'] == 'text' || $configItem['type'] == 'url' ||
    $configItem['type'] == 'email' || $configItem['type'] == 'emaillist'
) {
    printf('<input type="text" name="values[%s]" size="70" value="%s" />',
        $id, htmlspecialchars(stripslashes($value)));
} elseif ($configItem['type'] == 'integer') {
    printf('<input type="text" name="values[%s]" size="70" value="%d" />',
        $id, htmlspecialchars(stripslashes($value)));
} elseif ($configItem['type'] == 'boolean') {
    printf('<select name="values[%s]">', $id);
    echo '<option value="true" ';
    if ($value === true || $value == 'true' || $value == 1) {
        echo 'selected="selected"';
    }
    echo '>';
    echo $GLOBALS['I18N']->get('Yes');
    echo '  </option>';
    echo '<option value="false" ';
    if ($value === false || $value == 'false' || $value == 0) {
        echo 'selected="selected"';
    }
    echo '>';
    echo $GLOBALS['I18N']->get('No');
    echo '  </option>';
    echo '</select>';
} elseif ($configItem['type'] == 'image') {
    echo '<br/><p>'.s('Please upload an image file, PNG or JPG.').'</p>';
    include 'class.image.inc';
    $image = new imageUpload();
    printf('<input type="hidden" name="values[%s]" value="%s" />', $id, $value); //# to trigger the saving of the value
    echo $image->showInput($id, $value, 0);
} elseif ($configItem['type'] == 'select') {
    echo '<select name="values['.$id.']">';
    foreach ($configItem['values'] as $key => $label) {
        print '<option value="'.$key.'"';
        if ($key == $value) {
            print ' selected="selected"';
        }
        print '>'.$label.'</option>';
    }


} else {
    echo s('Don\'t know how to handle type '.$configItem['type']);
}
if (isset($_GET['ret']) && $_GET['ret'] == 'catlists') {
    echo '<input type="hidden" name="ret" value="catlists" />';
}
echo '<input type="hidden" name="save" value="item_'.$id.'" />
<button class="submit" type="submit" name="savebutton">' .s('save changes').'</button>';

//# for cancellation, we use a reset button, but that will reset all values in the entire page
//# https://mantis.phplist.org/view.php?id=16924

//# UX wise, it would be good to close the editing DIV again.
echo '<button class="dontsavebutton" id="dontsaveitem_'.$id.'" type="reset">'.s('undo').'</button>';

//# another option is to use a link back to configure, but that will go back to top, which isn't great UX either.
//print '<a href="./?page=configure" class="button">'.s('cancel changes').'</a>';

echo '</div>';

echo '<script type="text/javascript">

  $(".dontsavebutton").click(function() {
     item = $(this).attr(\'id\');
     item = item.replace(/dontsave/,\'\'); 
     desc = $("#description"+item).html();
     $("#"+item).html(desc+\' <i>' .str_replace("'", "\'", s('editing cancelled')).'</i>\');
  });

</script>';
