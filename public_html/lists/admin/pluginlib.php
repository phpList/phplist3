<?php
require_once dirname(__FILE__).'/accesscheck.php';

$GLOBALS["plugins"] = array();
$GLOBALS['editorplugin'] = false;
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
          ## remember the first plugin that says it can provide the editor
          ## the "editor" method is not defined in the default plugin, so it'll have to be made explicitly.
          if (!$GLOBALS['editorplugin'] && $pluginInstance->editorProvider && method_exists($pluginInstance,'editor')) {
            $GLOBALS['editorplugin'] = $className;
          }
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

/*
  * central function to call a method on all plugins
  * not sure to go down this route yet, MD 201212
  */

function pluginsCall($method) {
  $args = func_get_args();
  $m = array_shift($args); # the first is the method itself
  foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
    if (method_exists($plugin,$method)) {
      $plugin->$method($args);
    }
  }
}



