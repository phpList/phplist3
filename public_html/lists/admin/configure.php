
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

$configCategories = array();
$configTypes = array();

foreach ($default_config as $item => $details) {
  if (empty($details['category'])) {
    $details['category'] = 'other';
  }
  if (empty($details['type'])) {
    $details['type'] = 'undefined';
  }
  if (!isset($configCategories[$details['category']])) {
    $configCategories[$details['category']] = array();
  }
  if (!isset($configTypes[$details['type']])) {
    $configTypes[$details['type']] = array();
  }
  $configTypes[$details['type']][] = $item;
  $configCategories[$details['category']][] = $item;
}
#var_dump($configCategories);
#var_dump($configTypes);

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
        if (empty($value) && !$info['allowempty']) {
          Error($info['description']. ' ' . $GLOBALS['I18N']->get('cannot be empty'));
          $haserror = 1;
        } else {
          SaveConfig($id,$value);
        }
      }
    }
    if (!$haserror) {
      print '<div class="actionresult">'.s('Changes Saved').'</div>';
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

  foreach ($configCategories as $configCategory => $configItems) {
    $some = 0;
    $categoryHTML = '<fieldset id="'.$configCategory.'">';
    $categoryHTML .= '<legend>'.s($configCategory).' '.s('settings').'</legend>';
  
    foreach ($configItems as $configItem) {
      
      $dbvalue = getConfig($configItem);
      if (isset($dbvalue)) {
        $value = $dbvalue;
      } else {
        $value = $default_config[$configItem]['value'];
      }
      if (!in_array($configItem,$GLOBALS['noteditableconfig'])) {
        $some = 1;
        $categoryHTML .= sprintf('<div class="shade%d"><div class="configEdit"><a href="%s" class="ajaxable">%s</a> <b>%s</b><a class="resourcereference" href="http://resources.phplist.com/%s/config:%s" target="_blank">?</a></div>',$alternate,PageURL2("configure","","id=$configItem"),s('edit'),$default_config[$configItem]['description'],$_SESSION['adminlanguage']['iso'],$configItem);
        $categoryHTML .= sprintf('<div id="edit_%s" class="configcontent">%s</div></div>',$configItem,nl2br(htmlspecialchars(stripslashes($value))));
        if ($alternate == 1) {
          $alternate = 2;
        } else {
          $alternate = 1;
        }
      }
    }
    $categoryHTML .= '</fieldset>';
    if ($some) {
      print $categoryHTML;
    }
  }
  print '</form>';
} else {
  include dirname(__FILE__).'/actions/configure.php';
}
