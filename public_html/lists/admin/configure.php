
<div id="configurecontent"></div>

<?php
require_once dirname(__FILE__).'/accesscheck.php';
/*
if ($_GET["firstinstall"] || $_SESSION["firstinstall"]) {
  $_SESSION["firstinstall"] = 1;
  print "<p class="x">" . $GLOBALS['I18N']->get('checklist for installation') . "</p>";
  require "setup.php";
}
*/
if (empty($_REQUEST['id'])) {
  $id = '';
} else {
  $id = $_REQUEST['id'];
  if (!isset($default_config[$id])) {
    print $GLOBALS['I18N']->get('invalid request');
    return;
  }
}

print Info(s('You can edit all of the values in this page, and click the "save changes" button once to save all the changes you made.'),1);
print formStart(' class="configForm" ');
# configure options
reset($default_config);
if (!empty($_REQUEST['save'])) {
  if (!verifyToken()) {
    print Error($GLOBALS['I18N']->get('No Access'));
    return;
  }
  $info = $default_config[$id];
  $haserror = 0;
  if (is_array($_POST['values'])) {
    foreach ($_POST['values'] as $id => $value) {
      if (isset($default_config[$id])) {
        $info = $default_config[$id];
        if ($id == "website" || $id == "domain") {
          $value = str_replace("[DOMAIN]","",$value);
          $value = str_replace("[WEBSITE]","",$value);
        }
        if ($value == "" && empty($info[3])) {
          Error("$info[1] " . $GLOBALS['I18N']->get('cannot be empty'));
          $haserror = 1;
        } else {
          SaveConfig($id,$value);
        }
      }
    }
    if (!$haserror) {
      print Info($GLOBALS['I18N']->get('Changes Saved'));
      unset($id);
    }
    
    if (!empty($_SESSION['firstinstall'])) {
      print PageLink2('setup',$GLOBALS['I18N']->get('Continue phpList configuration'));
    }
    
#    Redirect("configure");
#    exit;
  }
}

if (empty($id)) {
  $alternate = 1;
  while (list($key,$val) = each($default_config)) {
    if (is_array($val)) {
      $dbval = getConfig($key);
      if (isset($dbval)) {
        $value = $dbval;
      } else {
        $value = $val[0];
      }
      if (!in_array($key,$GLOBALS['noteditableconfig'])) {
        printf('<div class="shade%d"><div class="configEdit"><a href="%s" class="ajaxable">%s</a> <b>%s</b></div>',$alternate,PageURL2("configure","","id=$key"),$GLOBALS['I18N']->get('edit'),$GLOBALS['I18N']->get($val[1]));
        printf('<div id="edit_%s" class="configcontent">%s</div></div>',$key,nl2br(htmlspecialchars(stripslashes($value))));
        if ($alternate == 1) {
          $alternate = 2;
        } else {
          $alternate = 1;
        }
      }
    }
  }
  print '</form>';
} else {
  include dirname(__FILE__).'/actions/configure.php';
}
