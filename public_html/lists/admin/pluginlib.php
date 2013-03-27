<?php
require_once dirname(__FILE__).'/accesscheck.php';

$GLOBALS["plugins"] = array();
$GLOBALS['editorplugin'] = false;
if (!defined("PLUGIN_ROOTDIR")) {
  define("PLUGIN_ROOTDIR","notdefined");
}

$pluginRootDirs = array();
if (defined('PLUGIN_ROOTDIRS')) {
  $pluginRootDirs = explode(';',PLUGIN_ROOTDIRS);
}
$pluginRootDirs[] = PLUGIN_ROOTDIR;
$pluginRootDirs = array_unique($pluginRootDirs);

include_once dirname(__FILE__). "/defaultplugin.php";
$pluginFiles = array();

foreach ($pluginRootDirs as $pluginRootDir) {
#  print '<h3>'.$pluginRootDir.'</h3>';
  if (is_dir($pluginRootDir)) {
    $dh = opendir($pluginRootDir);
    while (false!==($file = readdir($dh))) {
      if ($file != "." && $file != ".." && !preg_match("/~$/",$file)) {
#        print $pluginRootDir.' '.$file.'<br/>';
        if (is_file($pluginRootDir."/".$file) && preg_match("/\.php$/",$file)) {
#          print "ADD $file<br/>";
          array_push($pluginFiles,$pluginRootDir."/".$file);
        } elseif (is_dir($pluginRootDir.'/'.$file.'/plugins')) {
#         print 'SUBROOT'.$pluginRootDir.' '.$file.'<br/>';
          $subRoot = $pluginRootDir.'/'.$file.'/plugins';
          $subDir = opendir($subRoot);
          while (false!==($subFile = readdir($subDir))) {
            if (is_file($subRoot.'/'.$subFile) && preg_match("/\.php$/",$subFile) ) {
#              print "ADD $subFile<br/>";
              array_push($pluginFiles,$subRoot.'/'.$subFile);
            } else {
#              print "NOT A FILE: $subRoot.'/'.$subFile<br/>";
            }
          }
        } else {
#          print 'NOT A DIR: '.$pluginRootDir.'/'.$file.'/plugins<br/>';
        }
      }
    }
    closedir($dh);
  }
}
#var_dump($pluginFiles);exit;


foreach ($pluginFiles as $file) {
  list($className,$ext) = explode(".",basename($file));
  if (preg_match("/[\w]+/",$className) && !in_array($className,$GLOBALS['plugins_disabled'])) {
    if (!class_exists($className)) {
      include_once $file;
      if (class_exists($className)) {
        $pluginInstance = new $className();
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
        Error('initialisation of plugin '. $className.' failed');
      }
      #print "$className = ".$pluginInstance->name."<br/>";
    }
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



