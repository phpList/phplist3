<?php

## dump the structure file for language parsing

define('PHPLISTINIT', 1);
function s($item) {}

include 'public_html/lists/admin/structure.php';
print '<?php ' . PHP_EOL;

foreach ($DBstruct as $table => $tStruct) {
    foreach ($tStruct as $column => $cStruct) {
        #  var_dump($cStruct);
        $val = $cStruct[1];
        $val = str_replace('sysexp:', '', $val);
        $val = str_replace('sys:', '', $val);
        $val = preg_replace('/^index$/', '', $val);
        $val = preg_replace('/^unique$/', '', $val);
        $val = preg_replace('/^id$/i', '', $val);

        if (!empty($val)) {
            print 'get("' . $val . '")' . PHP_EOL;
        }
    }
}

$pagetitles = array(
    'home',
    'setup',
    'about',
    'attributes',
    'stresstest',
    'list',
    'config',
    'catlists',
    'editattributes',
    'editlist',
    'checki18n',
    'importsimple',
    'import4',
    'import3',
    'import2',
    'import1',
    'import',
    'export',
    'initialise',
    'send',
    'preparesend',
    'sendprepared',
    'members',
    'users',
    'reconcileusers',
    'user',
    'adduser',
    'userhistory',
    'messages',
    'message',
    'processqueue',
    'defaults',
    'upgrade',
    'templates',
    'template',
    'viewtemplate',
    'configure',
    'admin',
    'admins',
    'adminattributes',
    'processbounces',
    'bounces',
    'bounce',
    'spageedit',
    'spage',
    'eventlog',
    'getrss',
    'viewrss',
    'community',
    'vote',
    'login',
    'logout',
    'mclicks',
    'uclicks',
    'massunconfirm',
    'massremove',
    'usermgt',
    'bouncemgt',
    'domainstats',
    'mviews',
    'statsmgt',
    'statsoverview',
    'subscriberstats',
    'dbcheck',
    'importadmin',
    'dbadmin',
    'usercheck',
    'listbounces',
    'bouncerules',
    'bouncerule',
    'checkbouncerules',
    'translate',
    'ajaxform',
    'updatetranslation',
    'reindex',
    'plugins',
    'hostedprocessqueuesetup',
    'suppressionlist',
);
## add the pagetitles and hover
foreach ($pagetitles as $pagetitle) {
    print 'get("pagetitle:' . $pagetitle . '")' . PHP_EOL;
    print 'get("pagetitlehover:' . $pagetitle . '")' . PHP_EOL;
}

include 'public_html/lists/admin/defaultconfig.php';
foreach ($default_config as $item => $details) {
    if (empty($details['category'])) {
        $details['category'] = 'other';
    }
    if (empty($details['type'])) {
        $details['type'] = 'undefined';
    }
    if (!isset($configCategories[strtolower($details['category'])])) {
        $configCategories[strtolower($details['category'])] = array();
    }
    if (!isset($configTypes[$details['type']])) {
        $configTypes[$details['type']] = array();
    }
    $configTypes[$details['type']][] = $item;
    $configCategories[strtolower($details['category'])][] = $item;
}
foreach (array_keys($configCategories) as $configCategory) {
    print 'get("'.$configCategory.' settings")' . PHP_EOL;
}