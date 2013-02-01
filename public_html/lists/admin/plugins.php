<?php
require_once dirname(__FILE__).'/accesscheck.php';
#$_POST['pluginurl'] = '';

if (!empty($_POST['pluginurl'])) {
  //if (!verifyToken()) {
    //print Error(s('Invalid security token, please reload the page and try again'));
    //return;
  //}
  
  $packageurl = $_POST['pluginurl'];
  ## verify the url against known locations, and require it to be "zip".
  //if (!preg_match('/^https?:\/\/github\.com\/.*\.zip$/i',$packageurl)) {
    //print Error(s('Invalid download URL, please reload the page and try again'));
    //return;
  //}
  print '<h3>'.s('Fetching plugin').'</h3>';
  
  $packagefile = file_get_contents($packageurl);
  $filename = basename($packageurl);

  file_put_contents($GLOBALS['tmpdir'].'/phpListPlugin-'.$filename,$packagefile);
  print '<h3>'.s('Installing plugin').'</h3>';
  $zip = new ZipArchive;
  if ($zip->open($GLOBALS['tmpdir'].'/phpListPlugin-'.$filename)) {
  
  /* the zip may have a variety of directory structures, as Github seems to add at least one for the "branch" of 
   * the project and then the developer has some more. 
   * We look for a directory called "plugins" and place it's contents in the plugins folder.
   */
  
 
#  var_dump($zip);
  //echo "numFiles: " . $zip->numFiles . "\n";
  //echo "status: " . $zip->status  . "\n";
  //echo "statusSys: " . $zip->statusSys . "\n";
  //echo "filename: " . $zip->filename . "\n";
  //echo "comment: " . $zip->comment . "\n";  
  
  $extractList = array();
  $dir_prefix = '';
  for ($i=0; $i<$zip->numFiles;$i++) {
#      echo "index: $i<br/>\n";
#    var_dump($zip->statIndex($i));
    $zipItem = $zip->statIndex($i);
    if (preg_match('~^([^/]+)/plugins/~',$zipItem['name'],$regs)) {
      array_push($extractList,$zipItem['name']);
      $dir_prefix = $regs[1];
    }
  }
  var_dump($extractList);
  var_dump($dir_prefix);
  @mkdir($GLOBALS['tmpdir'].'/phpListPluginInstall',0755);
  $destination = PLUGIN_ROOTDIR;
#  $destination = $GLOBALS['tmpdir'].'/phpListPluginDestination';
  @mkdir($destination,0755);
  if (is_writable($destination)) {
    if ($zip->extractTo($GLOBALS['tmpdir'].'/phpListPluginInstall',$extractList)) {
      $extractedDir = opendir($GLOBALS['tmpdir'].'/phpListPluginInstall/'.$dir_prefix.'/plugins/');
      while ($dirEntry = readdir($extractedDir)) {
        if (!preg_match('/^\./',$dirEntry)) {
          print $dirEntry .'<br/>';
          @rename($GLOBALS['tmpdir'].'/phpListPluginInstall/'.$dir_prefix.'/plugins/'.$dirEntry,
            $destination.'/'.$dirEntry);
        }  
      }
      ## clean up
      delFsTree($GLOBALS['tmpdir'].'/phpListPluginInstall');
      
      print s('Plugin installed successfully');
      $zip->close();   
      print '<hr/>'.PageLinkButton('plugins',s('Continue'));
      return;
    }
  } else {
    Error(s('Plugin directory is not writable'));
  }
  } else {
    Error(s('Invalid plugin package'));
  }

  print s('Plugin installation failed');
  $zip->close();   
  print '<hr/>'.PageLinkButton('plugins',s('Continue'));
  return;
}

if (!class_exists('ZipArchive')) {
  Warn(s('PHP has no <a href="http://php.net/zip">Zip capability</a>, cannot continue'));
  return;
}

if (!is_writable(PLUGIN_ROOTDIR)) {
  Warn(s('The plugin root directory is not writable, please install plugins manually'));
} else {
  print '<h3>'.s('Install a new plugin').'</h3>';
  print formStart();
  print '<fieldset>
      <label for="pluginurl">'.s('Plugin package URL').'</label>
      <div type="field"><input type="text" id="pluginurl" name="pluginurl" /></div>
      <button type="submit" name="download">'.s('Install plugin').'</button>
      </fieldset>';
}

$ls = new WebblerListing(s('Installed plugins'));

foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
  $ls->addElement($pluginname);
  $ls->addColumn($pluginname,s('name'),$plugin->name);
  $ls->addColumn($pluginname,s('enabled'),$plugin->enabled ? $GLOBALS['img_tick'] : $GLOBALS['img_cross']);
  
}

print $ls->display();

