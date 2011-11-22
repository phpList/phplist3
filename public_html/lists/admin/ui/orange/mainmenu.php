<?php

## structure that controls the top menu

$GLOBALS['pagecategories'] = array(
  ## category title => array( 
    # toplink => page to link top menu to, required for the menu to shop up
    # pages => pages in this category
    
  'subscribers' => array(
     'toplink' => 'usermgt',
     'pages' => array(
        'users',
        'usermgt',
        'members',
        'import',
        'import1',
        'import2',
        'import3',
        'import4',
        'importsimple',
        'dlusers',
        'export',
        'listbounces',
        'massremove',
        'massunconfirm',
        'reconcileusers',
        'usercheck',
        'userhistory',
        'user',
      ),
     'menulinks' => array(
        'users',
        'usermgt',
        'list',
        'import',
        'export',
        'listbounces',
        'massremove',
        'massunconfirm',
        'reconcileusers',
        'usercheck',
      ),
      
   ),
  'campaigns' => array(
      'toplink' => 'send',
      'pages' => array(
        'send',
        'sendprepared',
        'message',
        'messages',
        'viewmessage',
        'templates',
        'template',
        'viewtemplate',
        'bouncemgt',
      ),
      'menulinks' => array(
        'send',
        'messages',
        'templates',
        'bouncemgt',
      ),
  ),
  'statistics' => array(
      'toplink' => 'statsmgt',
      'pages' => array(
        'mviews',
        'mclicks',
        'uclicks',
        'userclicks',
        'statsmgt',
        'statsoverview',
        'domainstats'
      ),
      'menulinks' => array(
        'statsoverview',
        'mviews',
        'mclicks',
        'uclicks',
        'domainstats'
      ),
  ),
  'system' => array(
      'toplink' => 'system',
      'pages' => array(
        'bounce',
        'bounces',
        'convertstats',
        'dbcheck',
        'eventlog',
        'generatebouncerules',
        'initialise',
        'upgrade',
        'processqueue',
        'processbounces',
        'reindex',
        'resetstats',
      ),
      'menulinks' => array(
        'bounces',
        'dbcheck',
        'eventlog',
        'generatebouncerules',
        'initialise',
        'upgrade',
        'processqueue',
        'processbounces',
        'reindex',
      ),
  ),
  'develop' => array(
      'toplink' => 'develop',
      'pages' => array(
        'checki18n',
        'stresstest',
        'subscriberstats',
        'tests',
        'resetstats',
      ),
      'menulinks' => array(
        'checki18n',
        'stresstest',
        'subscriberstats',
        'tests',
        'resetstats',
      ),
  ),
  'config' => array(
      'toplink' => 'setup',
      'pages' => array(
        'setup',
        'configure',
        'list',
        'editlist',
        'catlists',
        'spage',
        'spageedit',
        'admins',
        'admin',
        'importadmin',
        'adminattributes',
        'attributes',
        'editattributes',
        'defaults',
        'bouncerules',
        'bouncerule',
        'checkbouncerules',
      ),
      'menulinks' => array(
        'setup',
        'configure',
        'list',
        'attributes',
        'spage',
        'admins',
        'importadmin',
        'adminattributes',
        'bouncerules',
        'checkbouncerules',
        'catlists',
      ),
  ),
  'info' => array(
      'toplink' => 'about',
      'pages' => array(
        'about',
        'community',
        'home',
        'vote'
      ),
      'menulinks' => array(
        'about',
        'community',
        'home',
      ),
  ),
  'plugins' => array(
    'toplink' => 'plugins',
    'pages' => array(),
    'menulinks' => array(),
  ),
);
