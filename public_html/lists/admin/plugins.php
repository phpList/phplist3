<?php

require_once dirname(__FILE__).'/accesscheck.php';
//$_POST['pluginurl'] = '';

//# handle non-JS ajax
if (isset($_GET['disable']) || isset($_GET['enable'])) {
    include 'actions/plugins.php';
}

$pluginDestination = PLUGIN_ROOTDIR;
$pluginInfo = array();

if (!empty($_GET['delete'])) {
    $pluginname = preg_replace('/[^\w_-]/', '', $_GET['delete']);
    if (is_file($pluginDestination.'/'.$pluginname.'.info.txt')) {
        $pluginDetails = unserialize(file_get_contents($pluginDestination.'/'.$pluginname.'.info.txt'));
        unlink($pluginDestination.'/'.$pluginname.'.info.txt');
        delFsTree($pluginDestination.'/'.$pluginname);
        unlink($pluginDestination.'/'.$pluginname.'.php');
        $_SESSION['action_result'] = s('The plugin '.$pluginname.' was removed');
    }
    Redirect('plugins');
}

if (!empty($_POST['pluginurl']) && class_exists('ZipArchive')) {
    if (!verifyToken()) {
        echo Error(s('Invalid security token, please reload the page and try again'));

        return;
    }

    $packageurl = trim($_POST['pluginurl']);

    //# verify the url against known locations, and require it to be "zip".
    //# let's hope Github keeps this structure for a while
    if (!preg_match('~^https?://github\.com/([\w\-_]+)/([\w\-_]+)/archive/(.+)\.zip$~i', $packageurl, $regs)) {
        echo Error(s('Invalid download URL, please reload the page and try again'));

        return;
    }
    $developer = $regs[1];
    $project_name = $regs[2];
    $branch = $regs[3];

    echo '<h3>'.s('Fetching plugin').'</h3>';

    echo '<h2>'.s('Developer').': '.$developer.'</h2>';
    echo '<h2>'.s('Project').': '.$project_name.'</h2>';

    $filename = '';
    $packagefile = fetchUrlDirect($packageurl);
    if (!$packagefile) {
        echo Error(s('Unable to download plugin package, check your connection'));
    } else {
        $filename = basename($packageurl);

        file_put_contents($GLOBALS['tmpdir'].'/phpListPlugin-'.$filename, $packagefile);
        echo '<h3>'.s('Installing plugin').'</h3>';
    }
    $zip = new ZipArchive();
    if (!empty($filename) && $zip->open($GLOBALS['tmpdir'].'/phpListPlugin-'.$filename) === true) {

        /* the zip may have a variety of directory structures, as Github seems to add at least one for the "branch" of
         * the project and then the developer has some more.
         * We look for a directory called "plugins" and place it's contents in the plugins folder.
         */

        //  var_dump($zip);
        //echo "numFiles: " . $zip->numFiles . "\n";
        //echo "status: " . $zip->status  . "\n";
        //echo "statusSys: " . $zip->statusSys . "\n";
        //echo "filename: " . $zip->filename . "\n";
        //echo "comment: " . $zip->comment . "\n";

        $extractList = array();
        $dir_prefix = '';
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            //      echo "index: $i<br/>\n";
  //    var_dump($zip->statIndex($i));
            $zipItem = $zip->statIndex($i);
            if (preg_match('~^([^/]+)/plugins/~', $zipItem['name'], $regs)) {
                array_push($extractList, $zipItem['name']);
                $dir_prefix = $regs[1];
            }
        }
        //var_dump($extractList);
        //var_dump($dir_prefix);
        @mkdir($GLOBALS['tmpdir'].'/phpListPluginInstall', 0755);
        //  $destination = $GLOBALS['tmpdir'].'/phpListPluginDestination';
        @mkdir($pluginDestination, 0755);
        if (is_writable($pluginDestination)) {
            if ($zip->extractTo($GLOBALS['tmpdir'].'/phpListPluginInstall', $extractList)) {
                $extractedDir = opendir($GLOBALS['tmpdir'].'/phpListPluginInstall/'.$dir_prefix.'/plugins/');
                $installOk = false;
                $pluginsForUpgrade = array();
                while ($dirEntry = readdir($extractedDir)) {
                    if (!preg_match('/^\./', $dirEntry)) {
                        echo $dirEntry;
                        if (preg_match('/^([\w]+)\.php$/', $dirEntry, $regs)) {
                            $pluginInfo[$regs[1]] = array(
                                'installUrl'  => $packageurl,
                                'developer'   => $developer,
                                'projectName' => $project_name,
                                'installDate' => time(),
                            );
                        }

                        $bu_dir = time();
                        if (file_exists($pluginDestination.'/'.$dirEntry)) {
                            echo ' '.s('updating existing plugin');
                            if (preg_match('/(.*)\.php$/', $dirEntry, $regs)) {
                                $pluginsForUpgrade[] = $regs[1];
                            }
                            @rename($pluginDestination.'/'.$dirEntry,
                                $pluginDestination.'/'.$dirEntry.'.'.$bu_dir);
                        } else {
                            echo ' '.s('new plugin');
                        }
                        //       var_dump($pluginInfo);

                        echo '<br/>';
                        if (copy_recursive($GLOBALS['tmpdir'].'/phpListPluginInstall/'.$dir_prefix.'/plugins/'.$dirEntry,
                            $pluginDestination.'/'.$dirEntry)) {
                            delFsTree($pluginDestination.'/'.$dirEntry.'.'.$bu_dir);
                            $installOk = true;
                        } elseif (is_dir($pluginDestination.'/'.$dirEntry.'.'.$bu_dir)) {
                            //# try to place old one back
                            @rename($pluginDestination.'/'.$dirEntry.'.'.$bu_dir,
                                $pluginDestination.'/'.$dirEntry);
                        }
                    }
                }
                foreach ($pluginInfo as $plugin => $pluginDetails) {
                    //  print 'Writing '.$pluginDestination.'/'.$plugin.'.info.txt<br/>';
                    file_put_contents($pluginDestination.'/'.$plugin.'.info.txt', serialize($pluginDetails));
                }
                //# clean up
                delFsTree($GLOBALS['tmpdir'].'/phpListPluginInstall');

                if ($installOk) {
                    upgradePlugins($pluginsForUpgrade);

                    echo s('Plugin installed successfully');
                } else {
                    echo s('Error installing plugin');
                }
                $zip->close();
                echo '<hr/>'.PageLinkButton('plugins', s('Continue'));

                return;
            }
        } else {
            Error(s('Plugin directory is not writable'));
        }
    } else {
        Error(s('Invalid plugin package'));
    }

    echo s('Plugin installation failed');
    echo '<hr/>'.PageLinkButton('plugins', s('Continue'));

    return;
}

if (defined('PLUGIN_ROOTDIR') && !is_writable(PLUGIN_ROOTDIR)) {
    Info(s('The plugin root directory is not writable, please install plugins manually'));
} elseif (!class_exists('ZipArchive')) {
    Info(s('PHP has no <a href="http://php.net/zip">Zip capability</a>. This is required to allow installation from a remote URL'));
} else {
    echo '<h3>'.s('Install a new plugin').'</h3>';
        echo '<div class="jumbotron col-sm-12">';
    echo '<div class="col-sm-3 col-md-3 col-lg-3 row"><a class="resourceslink btn btn-info" href="http://resources.phplist.com/plugins/" title="'.s('Find plugins').'" target="_blank">'.s('Find plugins').'</a></div><div class="clearfix visible-xs visible-md"></div><br class="visible-xs visible-md" />';
    echo formStart('class="form-horizontal"');
    echo '<fieldset class="col-sm-9 col-md-9 col-lg-9 input-group">
      <label for="pluginurl" class="pull-left control-label">' .s('Plugin package URL').'</label>
            <div class="clearfix visible-xs visible-md"></div>
      <div type="field" class="pull-left col-lg-7 col-md-9 input-group"><input type="text" id="pluginurl" name="pluginurl" /></div>
      <button type="submit" name="download">' .s('Install plugin').'</button>
      </fieldset>';
        echo '</form>';
        echo '</div>';
}

$ls = new WebblerListing(s('Installed plugins'));
$ls->setElementHeading(s('Plugin'));

if (empty($GLOBALS['allplugins'])) {
    return;
}
$countUpdate = 0;
$countEnabled = 0;
$countDisabled = 0;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
ksort($GLOBALS['allplugins'], SORT_FLAG_CASE | SORT_STRING);

foreach ($GLOBALS['allplugins'] as $pluginname => $plugin) {
    $pluginDetails = array();
    $refl = new ReflectionObject($plugin);
    if (is_file(dirname($refl->getFileName()).'/'.$pluginname.'.info.txt')) {
        $pluginDetails = unserialize(file_get_contents($pluginDestination.'/'.$pluginname.'.info.txt'));
    }
    $canEnable = pluginCanEnable($pluginname);
    $canUpdate = !empty($pluginDetails['installUrl']) && class_exists('ZipArchive');

    if ($canEnable && $plugin->enabled) {
        $isEnabled = true;
        ++$countEnabled;
    } else {
        $isEnabled = false;
        ++$countDisabled;
    }

    if ($canUpdate) {
        $latestVersion = $plugin->checkForUpdate($pluginDetails);

        if ($latestVersion === null) {
            $latestVersion = getLatestTag($pluginDetails['developer'], $pluginDetails['projectName']);
            $updateAvailable = $latestVersion === null ? false : version_compare($latestVersion, $plugin->version) > 0;
        } else {
            $updateAvailable = (bool)$latestVersion;
        }

        if ($latestVersion === null) {
            $updateStatus = s('Unable to find update');
        } elseif ($updateAvailable) {
            $updateStatus = s('Version %s is available', $latestVersion);
            ++$countUpdate;
        } else {
            $updateStatus = s('Plugin is up-to-date');
        }
    } else {
        $updateAvailable = false;
        $updateStatus = s('Plugin must be updated manually');
    }

    if (($filter == 'enabled' && !$isEnabled)
        || ($filter == 'disabled' && $isEnabled)
        || ($filter == 'updates' && !$updateAvailable)) {
        continue;
    }
    $ls->addElement($pluginname);
    $ls->setClass($pluginname, 'row1');
    // $ls->addColumn($pluginname,s('name'),$plugin->name);

    $details = '<div class="plugindetails">';
    $details .= '<div class="detail"><span class="label">'.s('name').'</span>';
    $details .= '<span class="value">'.$plugin->name.'</span>';

    if (!empty($pluginDetails['developer'])) {
        $details .= '<span class="label">'.s('developer').'</span>';
        $details .= '<span class="value">'.$pluginDetails['developer'].'</span>';
    }
    $details .= '</div>';

    $details .= '<div class="detail"><span class="label">'.s('description').'</span>';
    $details .= '<span class="value">'.$plugin->description.'</span></div>';

    $details .= '<div class="detail"><span class="label">'.s('version').'</span>';
    $details .= '<span class="value">'.$plugin->version.'</span>';
    $details .= sprintf(
        '<span class="label">%s</span><span class="value">%s</span></div>',
        s('Update status'),
        $updateStatus
    );

    if (!empty($GLOBALS['developer_email'])) {
        //# show the origin of the plugin, as many may exist
        $details .= '<div class="detail"><span class="label">'.s('origin').'</span>';
        $details .= '<span class="value">'.$plugin->origin.'</span></div>';
    }

//  $ls->addRow($pluginname,s('description'),$plugin->description);
    // $ls->addColumn($pluginname,s('version'),$plugin->version);
    if (!empty($pluginDetails['installDate'])) {
        //  $ls->addColumn($pluginname,s('installed'),date('Y-m-d',$pluginDetails['installDate']));
        $details .= '<div class="detail"><span class="label">'.s('installed').'</span>';
        $details .= '<span class="value">'.formatDateTime(date('Y-m-d', $pluginDetails['installDate'])).'</span></div>';
    }
    if (!empty($pluginDetails['installUrl'])) {
        //   $ls->addRow($pluginname,s('installation Url'),$pluginDetails['installUrl']);
        $details .= '<div class="detail"><span class="label">'.s('installation Url').'</span>';
        $details .= '<span class="value">'.$pluginDetails['installUrl'].'</span></div>';
    }
    $detailEntry = '';

    if (!empty($plugin->documentationUrl)) {
        $detailEntry .= '<span class="label">'.s('More information').'</span>';
        $detailEntry .= '<span class="value"><a href="'.$plugin->documentationUrl.'" target="moreinfoplugin">'.s('Documentation Page').'</a></span>';
    }

    if ($plugin->enabled && !empty($plugin->settings)) {
        $firstSetting = reset($plugin->settings);
        $category = $firstSetting['category'];
        $settingsUrl = PageURL2('configure').'#'.sanitiseId(strtolower($category));
        $detailEntry .= '<span class="label">'.s('Configure').'</span>';
        $detailEntry .= '<span class="value"><a href="'.$settingsUrl.'">'.s($category).' '.s('settings').'</a></span>';
    }

    if ($detailEntry) {
        $details .= '<div class="detail">'.$detailEntry.'</div>';
    }

    if ($canEnable) {
        $ls->addColumn($pluginname, s('enabled'), $plugin->enabled ? $GLOBALS['img_tick'] : $GLOBALS['img_cross']);
        $ls->addColumn($pluginname, s('action'), $plugin->enabled ?
            PageLinkAjax('plugins&disable='.$pluginname, '<button>Disable</button>') :
            PageLinkAjax('plugins&enable='.$pluginname, '<button>Enable</button>'));
    } else {
        $ls->addColumn($pluginname, s('enabled'), $GLOBALS['img_cross']);
    }
    if (DEVVERSION) {
        //$ls->addColumn($pluginname,s('initialise'),$plugin->enabled ?
        //PageLinkAjax('plugins&initialise='.$pluginname,s('Initialise')) : '');
        if ($plugin->enabled) {
            $details .= '<div class="detail"><span class="label">'.s('initialise').'</span>';
            $details .= '<span class="value">';
            $details .= PageLinkAjax('plugins&initialise='.$pluginname, s('Initialise'));
            $details .= '</span></div>';
        }
    }
    if (!empty($pluginDetails['installUrl']) && is_writable($pluginDestination.'/'.$pluginname)) {
        //# we can only delete the ones that were installed from the interface
        $ls->addColumn($pluginname, s('delete'),
            '<span class="delete"><a href="javascript:deleteRec(\'./?page=plugins&delete='.$pluginname.'\');" class="button" title="'.s('delete this plugin').'">'.s('delete').'</a></span>');
    }
    if (!$canEnable) {
        $details .= '<div class="detail"><span class="label">'.s('Dependency check').'</span>';

        if ($plugin->dependencyFailure == 'No other editor enabled') {
            $details .= '<span class="value">'.s('Plugin can not be enabled, because "%s" is enabled.',
                    $GLOBALS['editorplugin']).'</span></div>';
        } else {
            $details .= '<span class="value">'.s('Plugin can not be enabled.').'<br/>'.s('Failure on system requirement <strong>%s</strong>',
                    $plugin->dependencyFailure).'</span></div>';
        }
    }

    if ($canUpdate) {
        $updateForm = formStart();
        $updateForm .= '<input type="hidden" name="pluginurl" value="'.$pluginDetails['installUrl'].'"/>
        <button type="submit" name="update" title="' .s('update this plugin').'" class="updatepluginbutton">'.s('update').'</button></form>';
    } else {
        $updateForm = '';
    }
    $ls->addColumn($pluginname, s('update'), $updateForm);
    $details .= '</div>';
    $ls->addRow($pluginname, s('details'), $details);
}
//  Add panel showing the number of plugins, enabled, disabled, and with update available
$ls->usePanel(
    sprintf(
        '%s (%d) | %s (%d) | %s (%d) | %s (%d)',
        filterLink('all', count($allplugins), s('All')),
        count($allplugins),
        filterLink('enabled', $countEnabled, s('Enabled')),
        $countEnabled,
        filterLink('disabled', $countDisabled, s('Disabled')),
        $countDisabled,
        filterLink('updates', $countUpdate, s('Update available')),
        $countUpdate
    )
);
echo $ls->display();

/**
 * Creates a link to the plugins page to show only plugins that meet the filter value.
 *
 * @param string $filterParam the URL query parameter
 * @param int    $count       the number of plugins with the status
 * @param string $caption     text for the link
 *
 * @return string html or text to be displayed
 */
function filterLink($filterParam, $count, $caption)
{
    global $filter;

    return $count > 0
        ? ($filter == $filterParam ? "<strong>$caption</strong>" : PageLink2('plugins', $caption, "filter=$filterParam"))
        : $caption;
}

/**
 * Query GitHub for the latest tag of a plugin.
 * Cache the result of each query for 24 hours to limit the number of API calls.
 *
 * @link https://developer.github.com/v3/repos/#list-tags
 * @link https://developer.github.com/v3/#rate-limiting
 *
 * @param string $developer
 * @param string $repository
 *
 * @return string|null the name of the latest tag or null when that cannot be retrieved
 */
function getLatestTag($developer, $repository)
{
    $tagUrl = "https://api.github.com/repos/$developer/$repository/tags";
    $ttl = 24 * 60 * 60;
    $content = fetchUrl($tagUrl, array(), $ttl);

    if (!$content) {
        return null;
    }
    $tags = json_decode($content);

    if ($tags === null) {
        return null;
    }

    return $tags[0]->name;
}
