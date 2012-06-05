<?php
require_once dirname(__FILE__).'/accesscheck.php';

$GLOBALS["plugins"] = array();
if (!defined("PLUGIN_ROOTDIR")) {
  define("PLUGIN_ROOTDIR","notdefined");
}
if (is_dir(PLUGIN_ROOTDIR)) {
  include_once dirname(__FILE__). "/defaultplugin.php";
  $files = array();
  $dh=opendir(PLUGIN_ROOTDIR);
  while (false!==($file = readdir($dh))) {
    if ($file != "." && $file != ".." &&
      !preg_match("/~$/",$file) &&
      is_file(PLUGIN_ROOTDIR."/".$file) &&
      preg_match("/\.php$/",$file) ){
      array_push($files,$file);
    }
  }
  closedir($dh);
  asort($files);
  reset($files);
  foreach ($files as $file) {
    list($className,$ext) = explode(".",$file);
    if (preg_match("/[\w]+/",$className) && !in_array($className,$GLOBALS['plugins_disabled'])) {
      include_once PLUGIN_ROOTDIR."/" . $file;
      if (class_exists($className)) {
        eval("\$pluginInstance = new ". $className ."();");
        if ($pluginInstance->enabled) {
          $GLOBALS["plugins"][$className] = $pluginInstance;
        } else {
          dbg( $className .' disabled');
        }
      } else {
        logEvent('Error initiliasing plugin'. $className);
      }
      #print "$className = ".$pluginInstance->name."<br/>";
    }
  }
  $GLOBALS['pluginsendformats'] = array();
  foreach ($GLOBALS['plugins'] as $className => $pluginInstance) {
    $plugin_sendformats = $pluginInstance->sendFormats();
    if (is_array($plugin_sendformats) && sizeof($plugin_sendformats)) {
      foreach ($plugin_sendformats as $val => $desc) {
        $val = preg_replace("/\W/",'',strtolower(trim($val)));
        $GLOBALS['pluginsendformats'][$val] = $className;
      }
    }
  }
} 
