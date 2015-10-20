
<!-- all info is in the info file -->
<?php
if (!ALLOW_IMPORT) {
    print $GLOBALS['I18N']->get('import is not available');

    return;
}

/*
 *
 * would be nice to make it tabbed, but that needs more work
 * 
print '<div id="importoptions" class="tabbed">';

$c = 1;
$tabs = '';
$html = '';

$tabs .= sprintf('<li><a href="./?page=%s&ajaxed=true">%s</a></li>','importsimple',$GLOBALS['I18N']->get('import'));
$tabs .= sprintf('<li><a href="./?page=%s&ajaxed=true">%s</a></li>','import1',$GLOBALS['I18N']->get('import from file'));
$tabs .= sprintf('<li><a href="./?page=%s&ajaxed=true">%s</a></li>','import2',$GLOBALS['I18N']->get('import from CSV'));
foreach ($GLOBALS['plugins'] as $pluginName => $plugin) {
  if (!empty($plugin->importPage)) {
    $tabs .= sprintf('<li><a href="./?pi=%s&amp;page=%s&ajaxed=true">%s</a></li>',$pluginName,$plugin->importPage,$plugin->importTabTitle);
  }
}

print '<ul>'.$tabs.'</ul>';
print $html;
print '</div>';
*/

print '<p><h3>'.$GLOBALS['I18N']->get('Please choose one of the import methods below').'</h3></p>';

print '<ul>';

print '<li class="dashboard_button" id="copy_paste">'.PageLink2('importsimple', $GLOBALS['I18N']->get('copy and paste list of emails')).'</li>';
print '<li class="dashboard_button" id="import_list">'.PageLink2('import1', $GLOBALS['I18N']->get('import by uploading a file with emails')).'</li>';
print '<li class="dashboard_button" id="import_csv">'.PageLink2('import2', $GLOBALS['I18N']->get('import by uploading a CSV file with emails and additional data')).'</li>';

foreach ($GLOBALS['plugins'] as $pluginName => $plugin) {
    if (!empty($plugin->importPage)) {
        printf('<li><a href="./?pi=%s&amp;page=%s">%s</a></li>', $pluginName, $plugin->importPage, $plugin->importTabTitle);
    }
}

print '</ul>';

if ($GLOBALS['commandline']) {
    $file = $cline['f'];
    if (!is_file($file)) {
        print ClineError('Cannot find file to import (hint: use -f)');
    }
    if (!$cline['l']) {
        print ClineError('Specify lists to import users to');
    }
    print clineSignature();

    ob_start();
    $_FILES['import_file'] = array(
    'tmp_name' => $file,
    'name'     => $file,
    'size'     => filesize($file),
  );
    $_POST['lists'] = explode(',', $cline['l']);
    $_POST['groups'] = explode(',', $cline['g']);

    $_POST['import'] = 1;
    $_POST['overwrite'] = 'yes';
    $_POST['notify'] = 'no';
    $_POST['omit_invalid'] = 'yes';
    $_POST['import_field_delimiter'] = "\t";
    $_POST['import_field_delimiter'] = ',';
    $_POST['import_record_delimiter'] = "\n";
    require dirname(__FILE__).'/import2.php';
    ob_end_clean();
    print "\nAll done\n";
    exit;
}
