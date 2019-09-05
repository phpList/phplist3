<?php

require_once dirname(__FILE__).'/accesscheck.php';
require_once dirname(__FILE__).'/EmailSender.php';
include_once dirname(__FILE__).'/defaultplugin.php';
require_once dirname(__FILE__).'/AnalyticsQuery.php';

$GLOBALS['plugins'] = array();
$GLOBALS['editorplugin'] = false;
$GLOBALS['authenticationplugin'] = false;
$GLOBALS['emailsenderplugin'] = false;
$GLOBALS['analyticsqueryplugin'] = false;

$pluginRootDirs = array();
if (PLUGIN_ROOTDIRS != '') {
    $pluginRootDirs = explode(';', PLUGIN_ROOTDIRS);
}
$pluginRootDirs[] = PLUGIN_ROOTDIR;
$pluginRootDirs = array_filter(array_unique($pluginRootDirs));
$pluginFiles = array();

foreach ($pluginRootDirs as $pluginRootDir) {
    //# try to expand to subdir of the admin dir
    if (!is_dir($pluginRootDir)) {
        $pluginRootDir = dirname(__FILE__).'/'.$pluginRootDir;
    }

//  print '<h3>'.$pluginRootDir.'</h3>';
    if (is_dir($pluginRootDir) && ($dh = opendir($pluginRootDir))) {
        while (false !== ($file = readdir($dh))) {
            if ($file != '.' && $file != '..' && !preg_match('/~$/', $file)) {
                //        print $pluginRootDir.' '.$file.'<br/>';
                if (is_file($pluginRootDir.'/'.$file) && preg_match("/\.php$/", $file)) {
                    //          print "ADD $file<br/>";
                    array_push($pluginFiles, $pluginRootDir.'/'.$file);
                } elseif (is_dir($pluginRootDir.'/'.$file.'/plugins')) {
                    //         print 'SUBROOT'.$pluginRootDir.' '.$file.'<br/>';
                    $subRoot = $pluginRootDir.'/'.$file.'/plugins';
                    $subDir = opendir($subRoot);
                    while (false !== ($subFile = readdir($subDir))) {
                        if (is_file($subRoot.'/'.$subFile) && preg_match("/\.php$/", $subFile)) {
                            //              print "ADD $subFile<br/>";
                            array_push($pluginFiles, $subRoot.'/'.$subFile);
                        } else {
                            //              print "NOT A FILE: $subRoot.'/'.$subFile<br/>";
                        }
                    }
                } else {
                    //          print 'NOT A DIR: '.$pluginRootDir.'/'.$file.'/plugins<br/>';
                }
            }
        }
        closedir($dh);
    }
}

$auto_enable_plugins = array();
if (isset($GLOBALS['plugins_autoenable'])) {
    $auto_enable_plugins = $GLOBALS['plugins_autoenable'];
}

//var_dump($pluginFiles);exit;
$disabled_plugins = unserialize(getConfig('plugins_disabled'));
if (is_array($disabled_plugins)) {
    foreach ($disabled_plugins as $pl => $plstate) {
        if (!empty($plstate) && !in_array($pl, $auto_enable_plugins)) {
            $GLOBALS['plugins_disabled'][] = $pl;
        }
    }
}

//var_dump($GLOBALS['plugins_disabled']);exit;
foreach ($pluginFiles as $file) {
    list($className, $ext) = explode('.', basename($file));
    if (preg_match("/[\w]+/", $className)) {
        // && !in_array($className,$GLOBALS['plugins_disabled'])) {
        if (!class_exists($className)) {
            include_once $file;
            if (class_exists($className)) {
                $pluginInstance = new $className();
                $pluginInstance->origin = $file;
                //  print "Instance $className<br/>";
                //# bit of a duplication of plugins, but $GLOBALS['plugins'] should only contain active ones
                //# using "allplugins" allow listing them, and switch on/off in the plugins page
                $GLOBALS['allplugins'][$className] = $pluginInstance;

                if (!in_array($className, $GLOBALS['plugins_disabled'])) {
                    $plugin_initialised = getConfig(md5('plugin-'.$className.'-initialised'));

                    if (!empty($plugin_initialised)) {
                        $GLOBALS['plugins'][$className] = $pluginInstance;
                        $pluginInstance->enabled = true;
                    } elseif (in_array($className, $auto_enable_plugins)) {
                        $GLOBALS['plugins'][$className] = $pluginInstance;
                        $pluginInstance->initialise();
                        $pluginInstance->enabled = true;
                    } else {
                        // plugin is not enabled and not disabled, so disable it and don't process this plugin any further
                        $pluginInstance->enabled = false;
                        $disabled_plugins[$className] = 1;
                        saveConfig('plugins_disabled', serialize($disabled_plugins), 0);
                        continue;
                    }
                    // remember the first plugins that provide editor, authentication or email sending
                    if (!$GLOBALS['editorplugin'] && $pluginInstance->editorProvider && method_exists($pluginInstance,
                            'editor')
                    ) {
                        $GLOBALS['editorplugin'] = $className;
                    }
                    if (!$GLOBALS['authenticationplugin'] && $pluginInstance->authProvider && method_exists($pluginInstance,
                            'validateLogin')
                    ) {
                        $GLOBALS['authenticationplugin'] = $className;
                    }

                    if (!$GLOBALS['emailsenderplugin'] && $pluginInstance instanceof EmailSender) {
                        $GLOBALS['emailsenderplugin'] = $pluginInstance;
                    }

                    if (!$GLOBALS['analyticsqueryplugin'] && $pluginInstance instanceof AnalyticsQuery) {
                        $GLOBALS['analyticsqueryplugin'] = $pluginInstance;
                        // Add 'plugin' as an option on the Settings page
                        $default_config['analytic_tracker']['values'] += array('plugin' => $analyticsqueryplugin->name);
                    }

                    if (!empty($pluginInstance->DBstruct)) {
                        foreach ($pluginInstance->DBstruct as $tablename => $tablecolumns) {
                            $GLOBALS['tables'][$className.'_'.$tablename] = $GLOBALS['table_prefix'].$className.'_'.$tablename;
                        }
                    }
                } else {
                    $pluginInstance->enabled = false;
                    dbg($className.' disabled');
                }
            } else {
                Error('initialisation of plugin '.$className.' failed');
            }
            //print "$className = ".$pluginInstance->name."<br/>";
        }
    }
}
//  Activate plugins in descending priority order
uasort(
    $plugins,
    function($a, $b) {
        return $b->priority - $a->priority;
    }
);

foreach ($plugins as $className => $pluginInstance) {
    if (!pluginCanEnable($className)) {
        // an already enabled plugin now does not meet its dependencies, do not enable it
        $pluginInstance->enabled = false;
        unset($plugins[$className]);
        continue;
    }
    $pluginInstance->activate();
}

$GLOBALS['pluginsendformats'] = array();
foreach ($GLOBALS['plugins'] as $className => $pluginInstance) {
    $plugin_sendformats = $pluginInstance->sendFormats();
    if (is_array($plugin_sendformats) && count($plugin_sendformats)) {
        foreach ($plugin_sendformats as $val => $desc) {
            $val = preg_replace("/\W/", '', strtolower(trim($val)));
            $GLOBALS['pluginsendformats'][$val] = $className;
        }
    }
}

function upgradePlugins($toUpgrade)
{
    foreach ($toUpgrade as $pluginname) {
        //    print '<h2>Upgrading '.$pluginname. '</h2><br/> ';
//    print md5('plugin-'.$pluginname.'-versiondate');
        $currentDate = getConfig(md5('plugin-'.$pluginname.'-versiondate'));
//    print 'CUrrent '.$currentDate;
        if ($GLOBALS['allplugins'][$pluginname]->upgrade($currentDate)) {
            //      print "Saving ".'plugin-'.$pluginname.'-versiondate';
            SaveConfig(md5('plugin-'.$pluginname.'-versiondate'), date('Y-m-d'), 0);
        }
    }
}

$commandlinePluginPages = array();
$commandlinePlugins = array();
if (count($GLOBALS['plugins'])) {
    foreach ($GLOBALS['plugins'] as $pluginName => $plugin) {
        $cl_pages = $plugin->commandlinePluginPages;
        if (count($cl_pages)) {
            $commandlinePlugins[] = $pluginName;
            $commandlinePluginPages[$pluginName] = $cl_pages;
        }
    }
}

/*
  * central function to call a method on all plugins
  * not sure to go down this route yet, MD 201212
  */

function pluginsCall($method)
{
    $args = func_get_args();
    $m = array_shift($args); // the first is the method itself
    foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
        if (method_exists($plugin, $method)) {
            $plugin->$method($args);
        }
    }
}

function pluginCanEnable($plugin)
{
    global $allplugins;

    $canEnable = false;

    if (isset($allplugins[$plugin])) {
        $dependencies = $allplugins[$plugin]->dependencyCheck();
        $dependencyDesc = array_search(false, $dependencies);

        if ($dependencyDesc === false) {
            $canEnable = true;
        } else {
            $allplugins[$plugin]->dependencyFailure = $dependencyDesc;
        }
    }

    return $canEnable;
}
