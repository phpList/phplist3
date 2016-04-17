<?php

## some system tasks, that don't need to be in the menu

/*
 * bit of code to list all files and compare them to the menu categorisation
 *
$inmenu = array();
foreach ($GLOBALS['pagecategories'] as $category => $cat_details) {
  $inmenu = array_merge($inmenu,$cat_details['menulinks']);
}
var_dump($inmenu);

$dir = opendir(dirname(__FILE__));
while ($file = readdir($dir)) {
  if ($file != '.' && $file != '..' && preg_match('/\.php$/',$file) && !in_array(basename($file,'.php'),$inmenu)) {
    print $file.'<br/>';
  }
}
*/

$pages = array(
    'initialise',
    'upgrade',
    'dbcheck',
    'reindex',
    'converttoutf8',
);

print '<ul class="dashboard_button">';
foreach ($pages as $page) {
    print '<li class="configuration">' . PageLink2($page, s($page)) . '</li>';
}
print '</ul>';
