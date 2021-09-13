<?php
 
$disabled_plugins = unserialize(getConfig('plugins_disabled'));
 
function change_plugin_status($plugin_name, $enable_plugin) {
    $success = false;
    if (!$enable_plugin) {
        if (isset($plugins[$plugin_name])) {
            unset($plugins[$plugin_name]);
            $disabled_plugins[$plugin_name] = 1;
        }
 
        // test whether other enabled plugins depend on this one
        foreach ($plugins as $piName => $pi) {
            if (!pluginCanEnable($piName)) {
                unset($plugins[$piName]);
                $disabled_plugins[$piName] = 1;
            }
        }
        saveConfig('plugins_disabled', serialize($disabled_plugins), 0);
        saveConfig(md5('plugin-'.$plugin_name.'-initialised'), 0);
        $success = true;
    } elseif (!empty($GLOBALS['allplugins'][$plugin_name])) {
        if (pluginCanEnable($plugin_name)) {
            if (isset($disabled_plugins[$plugin_name])) {
                unset($disabled_plugins[$plugin_name]);
            }
            if (isset($GLOBALS['allplugins'][$plugin_name])) {
                $GLOBALS['allplugins'][$plugin_name]->initialise();
            }
            //  var_dump($disabled_plugins);
            saveConfig('plugins_disabled', serialize($disabled_plugins), 0);
            $success = true;
        } else {
            logEvent(s('Failed to enable plugin (%s), dependencies failed', clean($plugin_name)));
        }
    }
    return $success;
}
 
 
if ($GLOBALS['commandline']) {
    $full_result = true;
    $cline = parseCline();
    reset($cline);
    if (!$cline || !is_array($cline) || !$cline['n']) {
        clineUsage('-n plugin_name_1 plugin_name_2 [-e [true]|false]');
        exit;
    }
 
    $intendedEnableStatus = true;
    if (($cline['e']) && ($cline['e'] == 'false')) {
        $intendedEnableStatus = false;
    }
 
    $pluginnames = explode(' ', $cline['n']);
    foreach ($pluginnames as $pluginname) {
        cl_output('Setting plugin '.$pluginname.' status to '.var_export($intendedEnableStatus, true).'.');
        $result = change_plugin_status($pluginname, $intendedEnableStatus);
        $full_result = $full_result && $result;
        $result_display = $result ? "succeeded" : "FAILED";
        cl_output('Changing '.$pluginname.' status ' .$result_display.".");
    }
    if (!$full_result) {
        exit(1);
    } else {
        exit(0);
    }
}