<?php

require_once dirname(__FILE__).'/inc/userlib.php';
include_once dirname(__FILE__).'/inc/maillib.php';
include_once dirname(__FILE__).'/inc/php_compat.php';

// set some variables
if (!isset($_GET['pi'])) {
    $_GET['pi'] = '';
}

$GLOBALS['mail_error'] = '';
$GLOBALS['mail_error_count'] = 0;
$organisation_name = getConfig('organisation_name');
$domain = getConfig('domain');
$website = getConfig('website');
if (empty($domain)) {
    $domain = $_SERVER['SERVER_NAME'];
}
if (empty($website)) {
    $website = $_SERVER['SERVER_NAME'];
}
if (empty($organisation_name)) {
    $organisation_name = $_SERVER['SERVER_NAME'];
}

$xormask = getConfig('xormask');
if (empty($xormask)) {
    $xormask = md5(uniqid(rand(), true));
    SaveConfig('xormask', $xormask, 0, 1);
}
define('XORmask', str_repeat($xormask, 20));
$hmackey = getConfig('hmackey');
if (empty($hmackey)) {
    $hmackey = bin2hex(random_bytes(256));
    SaveConfig('hmackey', $hmackey, 0, 1);
}
define('HMACKEY', $hmackey);

if (empty($_SESSION[$GLOBALS['installation_name'].'_csrf_token'])) {
    $_SESSION[$GLOBALS['installation_name'].'_csrf_token'] = bin2hex(random_bytes(16));
}
if (isset($_SESSION['lastactivity'])) {
    $_SESSION['session_age'] = time() - $_SESSION['lastactivity'];
}
$_SESSION['lastactivity'] = time();

$GLOBALS['img_tick'] = '<span class="yes">Yes</span>';
$GLOBALS['img_cross'] = '<span class="no">No</span>';
$GLOBALS['img_view'] = '<span class="view">View</span>';
$GLOBALS['img_busy'] = '<img src="images/busy.gif" with="34" height="34" border="0" alt="Please wait" id="busyimage" />';

// if keys need expanding with 0-s
$checkboxgroup_storesize = 1; // this will allow 10000 options for checkboxes

// identify pages that can be run on commandline
$commandline_pages = array(
    'dbcheck',
    'send',
    'processqueue',
    'processbounces',
    'import',
    'upgrade',
    'convertstats',
    'reindex',
    'blacklistemail',
    'systemstats',
    'converttoutf8',
    'initlanguages',
    'cron',
    'updatetlds',
    'runcommand',
);

if (isset($message_envelope)) {
    $envelope = "-f$message_envelope";
}

include_once dirname(__FILE__).'/pluginlib.php';

//# this needs more testing, and docs on how to set the Timezones in the DB
if (defined('SYSTEM_TIMEZONE')) {
    //  print('set time_zone = "'.SYSTEM_TIMEZONE.'"<br/>');
    Sql_Query('set time_zone = "'.SYSTEM_TIMEZONE.'"');
    //# verify that it applied correctly
    $tz = Sql_Fetch_Row_Query('select @@session.time_zone');
    if ($tz[0] != SYSTEM_TIMEZONE) {
        //# I18N doesn't exist yet, @@TODO need better error catching here
        echo 'Error setting timezone in Sql Database'.'<br/>';
    } else {
        //    print "Mysql timezone set to $tz[0]<br/>";
    }
    $phptz_set = date_default_timezone_set(SYSTEM_TIMEZONE);
    $phptz = date_default_timezone_get();
    if (!$phptz_set || $phptz != SYSTEM_TIMEZONE) {
        //# I18N doesn't exist yet, @@TODO need better error catching here
        echo 'Error setting timezone in PHP'.'<br/>';
    } else {
        //    print "PHP system timezone set to $phptz<br/>";
    }
//  print "Time now: ".date('Y-m-d H:i:s').'<br/>';
}

//# build a list of themes that are available
$themedir = dirname(__FILE__).'/ui';
$themeNames = array(); // avoid duplicate theme names
$d = opendir($themedir);

while (false !== ($th = readdir($d))) {
    if (is_dir($themedir.'/'.$th) && is_file($themedir.'/'.$th.'/theme_info')) {
        $themeData = parse_ini_file($themedir.'/'.$th.'/theme_info');

        if (false === $themeData || null === $themeData) {
            // unable to parse the theme info file so choose the first theme found
            $THEMES[$th] = array(
                'name' => 'unknown',
                'dir' => $th,
            );
            break;
        }

        if (!empty($themeData['name']) && !empty($themeData['dir']) && !isset($themeNames[$themeData['name']])) {
            $THEMES[$th] = $themeData;
            $themeNames[$themeData['name']] = $th;
        }
    }
}
if (count($THEMES) > 1 && THEME_SWITCH) {
    unset($THEMES['default']); // the default theme can be hidden if others are available
    unset($themeNames['phpList Default']);

    $default_config['UITheme'] = array(
        'value'       => isset($_SESSION['ui']) ? $_SESSION['ui'] : '',
        'values'      => array_flip($themeNames),
        'description' => s('Theme for phpList'),
        'type'        => 'select',
        'allowempty'  => false,
        'category'    => 'general',
        'hidden'      => false,
    );
}
unset($themeNames);


if (!empty($GLOBALS['SessionTableName'])) { // rather undocumented feature, but seems to be used by some
    include_once dirname(__FILE__).'/sessionlib.php';
}

if (!isset($table_prefix)) {
    $table_prefix = '';
}
if (!isset($usertable_prefix)) {
    $usertable_prefix = $table_prefix;
}

/* set session name, without revealing version
  * but with version included, so that upgrading works more smoothly
  */

/* hmm, won't work, going around in circles. Session is started in languages, where the DB
 * is not known yet, so we can't read xormask from the DB yet*/
//ini_set('session.name','phpList-'.$GLOBALS['installation_name'].VERSION | $xormask);

$redfont = '';
$efont = '';
$GLOBALS['coderoot'] = dirname(__FILE__).'/';
$GLOBALS['mail_error'] = '';
$GLOBALS['mail_error_count'] = 0;

function SaveConfig($item, $value, $editable = 1, $ignore_errors = 0)
{
    global $tables;
    //# in case DB hasn't been initialised
    if (empty($_SESSION['hasconf'])) {
        $_SESSION['hasconf'] = Sql_Table_Exists($tables['config']);
    }
    if (empty($_SESSION['hasconf'])) {
        return;
    }
    if (isset($GLOBALS['default_config'][$item])) {
        $configInfo = $GLOBALS['default_config'][$item];
    } else {
        $configInfo = array(
            'type'       => 'unknown',
            'allowempty' => true,
            'value'      => '',
        );
    }
    //# to validate we need the actual values
    $value = str_ireplace('[domain]', $GLOBALS['domain'], $value);
    $value = str_ireplace('[website]', $GLOBALS['website'], $value);

    switch ($configInfo['type']) {
        case 'boolean':
            if ($value == 'false' || $value == 'no') {
                $value = 0;
            } elseif ($value == 'true' || $value == 'yes') {
                $value = 1;
            }
            break;
        case 'integer':
            $value = sprintf('%d', $value);
            if ($value < $configInfo['min']) {
                $value = $configInfo['min'];
            }
            if ($value > $configInfo['max']) {
                $value = $configInfo['max'];
            }
            break;
        case 'email':
            if (!empty($value) && !is_email($value)) {
                //# hmm, this is displayed only later
                // $_SESSION['action_result'] = s('Invalid value for email address');
                return $configInfo['description'].': '.s('Invalid value for email address');
                $value = '';
            }
            break;
        case 'emaillist':
            $valid = array();
            $hasError = false;
            $emails = explode(',', $value);
            foreach ($emails as $email) {
                if (is_email($email)) {
                    $valid[] = $email;
                } else {
                    $hasError = true;
                }
            }
            $value = implode(',', $valid);
            /*
             * hmm, not sure this is good or bad for UX
             *
              */
            if ($hasError) {
                return $configInfo['description'].': '.s('Invalid value for email address');
            }

            break;
        case 'image':
            include 'class.image.inc';
            $image = new imageUpload();
            $imageId = $image->uploadImage($item, 0);
#            if ($imageId) {
                $value = $imageId;
#            }
            //# we only use the image type for the logo
            flushLogoCache();
            break;
        default:
            if (isset($configInfo['allowtags'])) { ## allowtags can be set but empty
                $value = strip_tags($value,$configInfo['allowtags']);
            }
            if (isset($configInfo['allowJS']) && !$configInfo['allowJS']) { ## it needs to be set and false
                $value = disableJavascript($value);
            }
    }
    //# reset to default if not set, and required
    if (empty($configInfo['allowempty']) && empty($value)) {
        $value = $configInfo['value'];
    }
    if (!empty($configInfo['hidden'])) {
        $editable = 0;
    }

    //# force reloading config values in session
    unset($_SESSION['config']);
    //# and refresh the config immediately https://mantis.phplist.com/view.php?id=16693
    unset($GLOBALS['config']);

    Sql_Query(sprintf('replace into %s set item = "%s", value = "%s", editable = %d', $tables['config'],
        sql_escape($item), sql_escape($value), $editable));

    return false; //# true indicates error, and which one
}

/*
  We request you retain the $PoweredBy variable including the links.
  This not only gives respect to the large amount of time given freely
  by the developers  but also helps build interest, traffic and use of
  PHPlist, which is beneficial to it's future development.

  You can configure your PoweredBy options in your config file

  Michiel Dethmers, phpList Ltd 2001-2015
*/
if (DEVVERSION) {
    $v = 'dev';
} else {
    $v = VERSION;
}
if (REGISTER) {
    $PoweredByImage = '<p class="poweredby" style="text-align:center"><a href="https://www.phplist.com/poweredby?utm_source=pl'.$v.'&amp;utm_medium=poweredhostedimg&amp;utm_campaign=phpList" title="visit the phpList website" ><img src="'.PHPLIST_POWEREDBY_URLROOT.'/'.$v.'/power-phplist.png" title="powered by phpList version '.$v.', &copy; phpList ltd" alt="powered by phpList '.$v.', &copy; phpList ltd" border="0" /></a></p>';
} else {
    $PoweredByImage = '<p class="poweredby" style="text-align:center"><a href="https://www.phplist.com/poweredby?utm_source=pl'.$v.'&amp;utm_medium=poweredlocalimg&amp;utm_campaign=phpList" title="visit the phpList website"><img src="images/power-phplist.png" title="powered by phpList version '.$v.', &copy; phpList ltd" alt="powered by phpList '.$v.', &copy; phpList ltd" border="0"/></a></p>';
}
$PoweredByText = '<div style="clear: both; font-family: arial, verdana, sans-serif; font-size: 8px; font-variant: small-caps; font-weight: normal; padding: 2px; padding-left:10px;padding-top:20px;">powered by <a href="https://www.phplist.com/poweredby?utm_source=download'.$v.'&amp;utm_medium=poweredtxt&amp;utm_campaign=phpList" target="_blank" title="powered by phpList version '.$v.', &copy; phpList ltd">phpList</a></div>';

if (!TEST && REGISTER) {
    if (!PAGETEXTCREDITS) {
        $PoweredBy = $PoweredByImage;
    } else {
        $PoweredBy = $PoweredByText;
    }
} else {
    if (!PAGETEXTCREDITS) {
        $PoweredBy = $PoweredByImage;
    } else {
        $PoweredBy = $PoweredByText;
    }
}
// some other configuration variables, which need less tweaking
// number of users to show per page if there are more
if (!defined('MAX_USER_PP')) {
    define('MAX_USER_PP', 50);
}
if (!defined('MAX_MSG_PP')) {
    define('MAX_MSG_PP', 5);
}
// Used by e.g. mviews.php
if (!defined('MAX_OPENS_PP')) {
    define('MAX_OPENS_PP', 20);
}

function formStart($additional = '')
{
    global $form_action, $page, $p;
    // depending on server software we can post to the directory, or need to pass on the page
    if ($form_action) {
        $html = sprintf('<form method="post" action="%s" %s>', $form_action, $additional);
        // retain all get variables as hidden ones
        foreach (array(
                     'p',
                     'page',
                 ) as $key) {
            $val = $_REQUEST[$key];
            if ($val) {
                $html .= sprintf('<input type="hidden" name="%s" value="%s" />', $key, htmlspecialchars($val));
            }
        }
    } else {
        $html = sprintf('<form method="post" action="" %s>', $additional);
    }

    if (!empty($_SESSION['logindetails']['id'])) {
        //# create the token table, if necessary
        if (!Sql_Check_For_Table('admintoken')) {
            createTable('admintoken');
        }
        $key = bin2hex(random_bytes(16));
        Sql_Query(sprintf('insert into %s (adminid,value,entered,expires) values(%d,"%s",%d,date_add(now(),interval 1 hour))',
            $GLOBALS['tables']['admintoken'], $_SESSION['logindetails']['id'], $key, time()), 1);
        $html .= sprintf('<input type="hidden" name="formtoken" value="%s" />', $key);

        //# keep the token table empty
        Sql_Query(sprintf('delete from %s where expires < now()',
            $GLOBALS['tables']['admintoken']), 1);
    }

    return $html;
}

function checkAccess($page, $pluginName = '')
{
    if (empty($pluginName)) {
        if (!$GLOBALS['commandline'] && isset($GLOBALS['disallowpages']) && in_array($page,
                $GLOBALS['disallowpages'])
        ) {
            return 0;
        }
    } else {
        if (!$GLOBALS['commandline'] && isset($GLOBALS['disallowpages']) && in_array($page.'&pi='.$pluginName,
                $GLOBALS['disallowpages'])
        ) {
            return 0;
        }
    }

    /*
      if (isSuperUser())
        return 1;
    */
    //# we allow all that haven't been disallowed
    //# might be necessary to turn that around
    return 1;
}

//@@TODO centralise the reporting and who gets what
function sendReport($subject, $message)
{
    $report_addresses = explode(',', getConfig('report_address'));
    foreach ($report_addresses as $address) {
        sendMail($address, $GLOBALS['installation_name'].' '.$subject, $message);
    }
    foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
        $plugin->sendReport($GLOBALS['installation_name'].' '.$subject, $message);
    }
}

function sendError($message, $to, $subject)
{
    foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
        $plugin->sendError($GLOBALS['installation_name'].' Error: '.$subject, $message);
    }
//  Error($msg);
}

function sendMessageStats($msgid)
{
    global $stats_collection_address, $tables;
    $msg = '';
    if (defined('NOSTATSCOLLECTION') && NOSTATSCOLLECTION) {
        return;
    }
    if (!isset($stats_collection_address)) {
        $stats_collection_address = 'phplist-stats@phplist.com';
    }
    $data = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d', $tables['message'], $msgid));
    $msg .= 'phpList version '.VERSION."\n";
    $msg .= 'phpList url '.getConfig("website")."\n";

    $diff = timeDiff($data['sendstart'], $data['sent']);

    if ($data['id'] && $data['processed'] > 10 && $diff != 'very little time') {
        $msg .= "\n".'Time taken: '.$diff;
        foreach (array(
                     'entered',
                     'processed',
                     'sendstart',
                     'sent',
                     'htmlformatted',
                     'sendformat',
                     'template',
                     'astext',
                     'ashtml',
                     'astextandhtml',
                     'aspdf',
                     'astextandpdf',
                 ) as $item) {
            $msg .= "\n".$item.' => '.$data[$item];
        }
        sendMail($stats_collection_address, 'phpList stats', $msg, '', '', true);
    }
}

function normalize($var)
{
    $var = str_replace(' ', '_', $var);
    $var = str_replace(';', '', $var);

    return $var;
}

function ClineSignature()
{
    return 'phpList version '.VERSION.' (c) 2000-'.date('Y')." phpList Ltd, https://www.phplist.com";
}

function ClineError($msg)
{
    ob_end_clean();
    echo "\nError: $msg\n";
    exit;
}

function clineUsage($line = '')
{
    cl_output( 'Usage: '.$_SERVER['SCRIPT_FILENAME']." -p page $line".PHP_EOL);
}

function Error($msg, $documentationURL = '')
{
    if ($GLOBALS['commandline']) {
        clineError($msg);

        return;
    }
    echo '<div class="error">'.$GLOBALS['I18N']->get('error').": $msg ";
    if (!empty($documentationURL)) {
        echo resourceLink($documentationURL);
    }
    echo '</div>';

    $GLOBALS['mail_error'] .= 'Error: '.$msg."\n";
    ++$GLOBALS['mail_error_count'];
    if (is_array($_POST) && count($_POST)) {
        $GLOBALS['mail_error'] .= "\nPost vars:\n";
        foreach ($_POST as $key => $val) {
            if ($key != 'password') {
                if (is_array($val)) {
                    $GLOBALS['mail_error'] .= $key.'='.serialize($val)."\n";
                } else {
                    $GLOBALS['mail_error'] .= $key.'='.$val."\n";
                }
            } else {
                $GLOBALS['mail_error'] .= "password=********\n";
            }
        }
    }
}

function clean($value)
{
    $value = trim($value);
    $value = preg_replace("/\r/", '', $value);
    $value = preg_replace("/\n/", '', $value);
    $value = str_replace('"', '&quot;', $value);
    $value = str_replace("'", '&rsquo;', $value);
    $value = str_replace('`', '&lsquo;', $value);
    $value = stripslashes($value);

    return $value;
}

function join_clean($sep, $array)
{
    // join values without leaving a , at the end
    $arr2 = array();
    foreach ($array as $key => $val) {
        if ($val) {
            $arr2[$key] = $val;
        }
    }

    return implode($sep, $arr2);
}

function Fatal_Error($msg, $documentationURL = '')
{
    if (empty($_SESSION['fatalerror'])) {
        $_SESSION['fatalerror'] = 0;
    }
    ++$_SESSION['fatalerror'];
    header('HTTP/1.0 500 Fatal error');
    if ($_SESSION['fatalerror'] > 5) {
        $_SESSION['logout_error'] = s('Too many errors, please login again');
        $_SESSION['adminloggedin'] = '';
        $_SESSION['logindetails'] = '';
        session_destroy();
        Redirect('logout&err=2');
        exit;
    }

    if ($GLOBALS['commandline']) {
        @ob_end_clean();
        echo "\n".$GLOBALS['I18N']->get('fatalerror').': '.strip_tags($msg)."\n";
        @ob_start();
    } else {
        @ob_end_clean();
        if (isset($GLOBALS['I18N']) && is_object($GLOBALS['I18N'])) {
            echo '<div align="center" class="error">'.$GLOBALS['I18N']->get('fatalerror').": $msg ";
        } else {
            echo '<div align="center" class="error">'."Fatal Error: $msg ";
        }
        if (!empty($documentationURL)) {
            echo resourceLink($documentationURL);
        }
        echo '</div>';

        foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
            $plugin->processError($msg);
        }
    }
    // include "footer.inc";
    // exit;
    return 0;
}

function resourceLink($url, $title = '')
{
    if (empty($title)) {
        $title = s('Documentation about this error');
    }

    return ' <span class="resourcelink"><a href="'.$url.'" title="'.htmlspecialchars($title).'" target="_blank" class="resourcelink">'.snbr('More information').'</a></span>';
}

function Warn($msg)
{
    if ($GLOBALS['commandline']) {
        @ob_end_clean();
        echo "\n".strip_tags($GLOBALS['I18N']->get('warning').': '.$msg)."\n";
        @ob_start();
    } else {
        echo '<div align=center class="error">'."$msg </div>";
        $message = '

    An warning has occurred in the Mailinglist System

    ' .$msg;
    }
//  sendMail(getConfig("report_address"),"Mail list warning",$message,"");
}

function Info($msg, $noClose = false)
{
    if (!empty($GLOBALS['commandline'])) {
        @ob_end_clean();
        echo "\n".strip_tags($msg)."\n";
        @ob_start();
    } else {
        //# generate some ID for the info div
        $id = substr(md5($msg), 0, 15);
        $pageinfo = new pageInfo($id);
        $pageinfo->setContent('<p>'.$msg.'</p>');
        if ($noClose && method_exists($pageinfo, 'suppressHide')) {
            $pageinfo->suppressHide();
        }
        echo $pageinfo->show();
    }
}

function ActionResult($msg)
{
    if ($GLOBALS['commandline']) {
        @ob_end_clean();
        echo "\n".strip_tags($msg)."\n";
        @ob_start();
    } else {
        return '<div class="actionresult">'.$msg.'</div>';
    }
}

function pageTitle($page)
{
    return $GLOBALS['I18N']->pageTitle($page);
}

$GLOBALS['pagecategories'] = array(
    //# category title => array(
    // toplink => page to link top menu to
    // pages => pages in this category
    'dashboard'   => array(
        'toplink'=> 'home',
        'pages'  => array(),
        'menulinks' => array(),
    ),
    'subscribers' => array(
        'toplink' => 'list',
        'pages'   => array(
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
            'suppressionlist',
            'reconcileusers',
            'usercheck',
            'user',
            'adduser',
            'attributes',

        ),
        'menulinks' => array(
            'users',
            'usermgt',
            'attributes',
            'list',
            'import',
            'export',
            'listbounces',
            'suppressionlist',
            'reconcileusers',
        ),

    ),
    'campaigns' => array(
        'toplink' => 'messages',
        'pages'   => array(
            'send',
            'sendprepared',
            'message',
            'messages',
            'viewmessage',
            'templates',
            'template',
            'viewtemplate',
        ),
        'menulinks' => array(
            'send',
            'messages',
            'templates',
        ),
    ),
    'statistics' => array(
        'toplink' => 'statsmgt',
        'pages'   => array(
            'mviews',
            'mclicks',
            'uclicks',
            'userclicks',
            'statsmgt',
            'statsoverview',
            'domainstats',
            'msgbounces',
        ),
        'menulinks' => array(
            'statsoverview',
            'mviews',
            'mclicks',
            'uclicks',
            'domainstats',
            'msgbounces',
        ),
    ),
    'system' => array(
        'toplink' => 'system',
        'pages'   => array(
            'bounce',
            'bounces',
            'convertstats',
            'dbcheck',
            'eventlog',
            'bouncemgt',
            'generatebouncerules',
            'initialise',
            'upgrade',
            'processqueue',
            'processbounces',
            'reindex',
            'resetstats',
            'updatetranslation',
        ),
        'menulinks' => array(
            //     'bounces',
            'updatetranslation',
            'dbcheck',
            'eventlog',
            'initialise',
            'upgrade',
            'bouncemgt',
            'processqueue',
            //     'processbounces',
            'reindex',
        ),
    ),
    'config' => array(
        'toplink' => 'setup',
        'pages'   => array(
            'setup',
            'configure',
            'plugins',
            'catlists',
            'spage',
            'spageedit',
            'admins',
            'admin',
            'importadmin',
            'adminattributes',
            'editattributes',
            'defaults',
            'bouncerules',
            'bouncerule',
            'checkbouncerules',
        ),
        'menulinks' => array(
            'setup',
            'configure',
            'plugins',
            'spage',
            'admins',
            'importadmin',
            'adminattributes',
            'bouncerules',
            'checkbouncerules',
            'catlists',
        ),
    ),
    //'info' => array(
        //'toplink' => 'about',
        //'pages'   => array(
            //'about',
            //'community',
            //'home',
            //   'translate',
            //'vote',
        //),
        //'menulinks' => array(
           // 'about',
            //'community',
            //    'translate',
            //'home',
        //),
    //),

    //'plugins' => array(
    //'toplink' => 'plugins',
    //'pages' => array(),
    //'menulinks' => array(),
    //),
);
if(isSuperUser() && ALLOW_UPDATER){
    $GLOBALS['pagecategories']['update'] = array(
        'toplink'=> 'redirecttoupdater',
        'pages'  => array(),
        'menulinks' => array(),
    );
}
if (DEVVERSION) {
    $GLOBALS['pagecategories']['develop'] = array(
        'toplink' => 'develop',
        'pages'   => array(
            //   'checki18n',
            'stresstest',
            'subscriberstats',
            'tests',
        ),
        'menulinks' => array(
            //   'checki18n',
            'stresstest',
            'subscriberstats',
            'tests',
        ),
    );
}
function pageCategory($page)
{
    foreach ($GLOBALS['pagecategories'] as $category => $cat_details) {
        if (in_array($page, $cat_details['pages'])) {
            return $category;
        }
    }

    return '';
}

/*
$main_menu = array(
  "configure" => "Configure",
  "community" => "Help",
  "about" => "About",
  "div1" => "<hr />",
  "list" => "Lists",
  "send"=>"Send a message",
  "users" => "Users",
  "usermgt" => "Manage Users",
  "spage" => "Subscribe Pages",
  "messages" => "Messages",
  'statsmgt' => 'Statistics',
  "div2" => "<hr />",
  "templates" => "Templates",
  "preparesend"=>"Prepare a message",
  "sendprepared"=>"Send a prepared message",
  "processqueue"=>"Process Queue",
  "processbounces"=>"Process Bounces",
  "bouncemgt" => 'Manage Bounces',
  "bounces"=>"View Bounces",
  "eventlog"=>"Eventlog"
);
*/
$GLOBALS['context_menu'] = array(
    'home'      => 'home',
    'community' => 'help',
    'about'     => 'about',
    'logout'    => 'logout',
);

function contextMenu()
{
    if (isset($GLOBALS['firsttime']) || (isset($_GET['page']) && $_GET['page'] == 'initialise')) {
        return;
    }
    if (!CLICKTRACK) {
        unset($GLOBALS['context_menu']['statsmgt']);
    }
    $shade = 1;
    $spb = '<li class="shade0">';
//  $spb = '<li class="shade2">';
    $spe = '</li>';
    $nm = mb_strtolower(NAME);
    if ($nm != 'phplist') {
        $GLOBALS['context_menu']['community'] = '';
    }
    // if (USE_ADVANCED_BOUNCEHANDLING) {
    $GLOBALS['context_menu']['bounces'] = '';
    $GLOBALS['context_menu']['processbounces'] = '';
    // } else {
    //   $GLOBALS["context_menu"]["bouncemgt"] = '';
    // }

    if (!isset($_SESSION['adminloggedin']) || !$_SESSION['adminloggedin']) {
        return '<ul class="contextmenu">'.$spb.PageLink2('home',
            $GLOBALS['I18N']->get('Main Page')).'<br />'.$spe.$spb.PageLink2('about',
            $GLOBALS['I18N']->get('about').' phplist').'<br />'.$spe.'</ul>';
    }

    $access = accessLevel('spage');
    switch ($access) {
        case 'owner':
            $subselect = sprintf(' where owner = %d', $_SESSION['logindetails']['id']);
            break;
        case 'all':
        case 'view':
            $subselect = '';
            break;
        case 'none':
        default:
            $subselect = ' where id = 0';
            break;
    }
    if (TEST && REGISTER) {
        $pixel = '<img src="https://d3u7tsw7cvar0t.cloudfront.net/images/pixel.gif" width="1" height="1" alt="" />';
    } else {
        $pixel = '';
    }
    global $tables;
    $html = '';

    if (isset($_GET['page'])) {
        $thispage = $_GET['page'];
    } else {
        $thispage = 'home';
    }
    $thispage_category = pageCategory($thispage);

    if (empty($thispage_category) && empty($_GET['pi'])) {
        $thispage_category = '';
    } elseif (!empty($_GET['pi'])) {
        $thispage_category = 'plugins';
    }

    if (!empty($thispage_category) && !empty($GLOBALS['pagecategories'][$thispage_category]['menulinks'])) {
        if (count($GLOBALS['pagecategories'][$thispage_category]['menulinks'])) {
            foreach ($GLOBALS['pagecategories'][$thispage_category]['menulinks'] as $category_page) {
                $GLOBALS['context_menu'][$category_page] = $category_page;
            }
        } else {
            unset($GLOBALS['context_menu']['categoryheader']);
        }
    } elseif (!empty($_GET['pi'])) {
        if (isset($GLOBALS['plugins'][$_GET['pi']]) && method_exists($GLOBALS['plugins'][$_GET['pi']], 'adminmenu')) {
            $GLOBALS['context_menu']['categoryheader'] = $GLOBALS['plugins'][$_GET['pi']]->name;
            $GLOBALS['context_menu'] = $GLOBALS['plugins'][$_GET['pi']]->adminMenu();
        }
    }

    foreach ($GLOBALS['context_menu'] as $page => $desc) {
        if (!$desc) {
            continue;
        }
        $link = PageLink2($page, $GLOBALS['I18N']->pageTitle($desc));
        if ($link) {
            if ($page == 'preparesend' || $page == 'sendprepared') {
                if (USE_PREPARE) {
                    $html .= $spb.$link.$spe;
                }
            } // don't use the link for a rule
            elseif ($desc == '<hr />') {
                $html .= '<li>'.$desc.'</li>';
            } elseif ($page == 'categoryheader') {
                //  $html .= '<li><h3>'.$GLOBALS['I18N']->get($thispage_category).'</h3></li>';
                $html .= '<li><h3>'.$GLOBALS['I18N']->get('In this section').'</h3></li>';
            } else {
                $html .= $spb.$link.$spe;
            }
        }
    }
    /*
      if (sizeof($GLOBALS["plugins"])) {
        $html .= $spb."<hr/>".$spe;
        foreach ($GLOBALS["plugins"] as $pluginName => $plugin) {
          $html .= $spb.PageLink2("main&amp;pi=$pluginName",$pluginName).$spe;
        }
      }
    */

    if ($html) {
        return '<ul class="contextmenu">'.$html.'</ul>'.$pixel;
    } else {
        return '';
    }
}

function recentlyVisited()
{
    $html = '';
    if (!isset($_SESSION['browsetrail']) || !is_array($_SESSION['browsetrail'])) {
        $_SESSION['browsetrail'] = array();
    }
    if (empty($_SESSION['adminloggedin'])) {
        return '';
    }
    if (isset($_SESSION['browsetrail']) && is_array($_SESSION['browsetrail'])) {
        if (!empty($_COOKIE['browsetrail'])) {
            //      if (!in_array($_COOKIE['browsetrail'],$_SESSION['browsetrail'])) {
            array_unshift($_SESSION['browsetrail'], $_COOKIE['browsetrail']);
//      }
        }

        $shade = 0;
        $html .= '<h3>'.$GLOBALS['I18N']->get('Recently visited').'</h3><ul class="recentlyvisited">';
        $browsetrail = array_unique($_SESSION['browsetrail']);

//    $browsetrail = array_reverse($browsetrail);
        $browsetaildone = array();
        $num = 0;
        foreach ($browsetrail as $pageid => $visitedpage) {
            if (strpos($visitedpage,
                'SEP')) { //# old method, store page title in cookie. However, that breaks on multibyte languages
                list($pageurl, $pagetitle) = explode('SEP', $visitedpage);
                if ($pagetitle != 'phplist') {  //# pages with no title
//          $pagetitle = str_replace('%',' ',$pagetitle);
                    if (strpos($pagetitle, ' ') > 20) {
                        $pagetitle = substr($pagetitle, 0, 10).' ...';
                    }
                    $html .= '<li class="shade'.$shade.'"><a href="./'.$pageurl.'" title="'.htmlspecialchars($pagetitle).'"><!--'.$pageid.'-->'.$pagetitle.'</a></li>';
                    $shade = !$shade;
                }
            } else {
                if (@preg_match('/\?page=([\w]+)/', $visitedpage, $regs)) {
                    $p = $regs[1];
                    $urlparams = array();
                    $pairs = explode('&', $visitedpage);
                    foreach ($pairs as $pair) {
                        if (strpos($pair, '=')) {
                            list($var, $val) = explode('=', $pair);
                            $urlparams[$var] = $val;
                        }
                    }
                    //# pass on ID
                    if (isset($urlparams['id'])) {
                        $urlparams['id'] = sprintf('%d', $urlparams['id']);
                    }
                    $url = 'page='.$p;
                    if (!empty($urlparams['id'])) {
                        $url .= '&id='.$urlparams['id'];
                    }
                    //# check for plugin
                    if (isset($urlparams['pi']) && isset($GLOBALS['plugins'][$urlparams['pi']])) {
                        $url .= '&pi='.$urlparams['pi'];
                        $title = $GLOBALS['plugins'][$urlparams['pi']]->pageTitle($p);
                        $titlehover = $GLOBALS['plugins'][$urlparams['pi']]->pageTitleHover($p);
                    } else {
                        unset($urlparams['pi']);
                        $title = $GLOBALS['I18N']->pageTitle($p);
                        $titlehover = $GLOBALS['I18N']->pageTitleHover($p);
                    }
                    if (!empty($p) && !empty($title) && !in_array($url, $browsetaildone)) {
                        $html .= '<li class="shade'.$shade.'"><a href="./?'.htmlspecialchars($url).addCsrfGetToken().'" title="'.htmlspecialchars($titlehover).'"><!--'.$pageid.'-->'.$title.'</a></li>';
                        $shade = !$shade;
                        $browsetaildone[] = $url;
                        ++$num;
                    }
                }
            }
            if ($num >= 3) {
                break;
            }
        }

        $html .= '</ul>';
        $_SESSION['browsetrail'] = array_slice($_SESSION['browsetrail'], 0, 20);
    }

    return $html;
}

function topMenu()
{
    if (empty($_SESSION['logindetails'])) {
        return '';
    }

    if ($_SESSION['logindetails']['superuser']) { // we don't have a system yet to distinguish access to plugins
        if (count($GLOBALS['plugins'])) {
            foreach ($GLOBALS['plugins'] as $pluginName => $plugin) {
                //if (isset($GLOBALS['pagecategories']['plugins'])) {
                //array_push($GLOBALS['pagecategories']['plugins']['menulinks'],'main&pi='.$pluginName);
                //}
                $menulinks = $plugin->topMenuLinks;
                foreach ($menulinks as $link => $linkDetails) {
                    if (isset($GLOBALS['pagecategories'][$linkDetails['category']])) {
                        array_push($GLOBALS['pagecategories'][$linkDetails['category']]['menulinks'],
                            $link.'&pi='.$pluginName);
                    }
                }
            }
        }
    }

    $topmenu = '';
    $topmenu .= '<div id="menuTop">';
    if (!DEVVERSION) {
        unset($GLOBALS['pagecategories']['develop']);
    }

    foreach ($GLOBALS['pagecategories'] as $category => $categoryDetails) {
        if ($category == 'hide'
            //# hmm, this also suppresses the "dashboard" item
            //     || count($categoryDetails['menulinks']) == 0
        ) {
            continue;
        }

        $thismenu = '';
        foreach ($categoryDetails['menulinks'] as $page) {
            $title = $GLOBALS['I18N']->pageTitle($page);

            $link = PageLink2($page, $title, '', true);
            if ($link) {
                $thismenu .= '<li>'.$link.'</li>';
            }
        }
        if (!empty($thismenu)) {
            $thismenu = '<ul>'.$thismenu.'</ul>';
        }

        if (!empty($categoryDetails['toplink'])) {
            $categoryurl = PageUrl2($categoryDetails['toplink'], '', '', true);
            if ($categoryurl) {
                $topmenu .= '<ul><li><a href="'.$categoryurl.'" title="'.$GLOBALS['I18N']->pageTitleHover($category).'">'.ucfirst($GLOBALS['I18N']->get($category)).'</a>'.$thismenu.'</li></ul>';
            } else {
                $topmenu .= '<ul><li><span>'.$GLOBALS['I18N']->get($category).$categoryurl.'</span>'.$thismenu.'</li></ul>';
            }
        }
    }

    $topmenu .= '</div>';

    return $topmenu;
}

//## hmm, these really should become objects
function PageLink2($name, $desc = '', $url = '', $no_plugin = false, $title = '')
{
    $plugin = '';
    if ($url) {
        $url = '&amp;'.$url;
    }

    if (in_array($name, $GLOBALS['disallowpages'])) {
        return '';
    }
    if (strpos($name, '&') !== false) {
        preg_match('/([^&]+)&/', $name, $regs);
        $page = $regs[1];
        if (preg_match('/&pi=([^&]+)/', $name, $regs)) {
            $plugin = $regs[1];
        }
        if (in_array($page, $GLOBALS['disallowpages'])) {
            return '';
        }
    } else {
        $page = $name;
    }

    $access = accessLevel($page);
    if (empty($plugin) || !is_object($GLOBALS['plugins'][$plugin])) {
        $name = str_replace('&amp;', '&', $name);
        $name = str_replace('&', '&amp;', $name);
    } else {
        if (isset($GLOBALS['plugins'][$plugin]->pageTitles[$page])) {
            $desc = $GLOBALS['plugins'][$plugin]->pageTitles[$page];
        } else {
            $desc = $plugin.' - '.$page;
        }
    }

    if (empty($desc)) {
        $desc = $name;
    }
    if (empty($title)) {
        $title = $GLOBALS['I18N']->pageTitleHover($page);
        if (empty($title)) {
            $title = $desc;
        }
    }

    $pqChoice = getConfig('pqchoice');
    $hideProcessQueue = !MANUALLY_PROCESS_QUEUE;

    if ($access == 'owner' || $access == 'all' || $access == 'view') {
        if ($name == 'processqueue' && $hideProcessQueue) {
            return '';
        }//'<!-- '.$desc.'-->';
        elseif ($name == 'processbounces' && !MANUALLY_PROCESS_BOUNCES) {
            return '';
        } //'<!-- '.$desc.'-->';
        else {
            if (!$no_plugin && !preg_match('/&amp;pi=/i',
                    $name) && isset($_GET['pi']) && isset($GLOBALS['plugins'][$_GET['pi']]) && is_object($GLOBALS['plugins'][$_GET['pi']])
            ) {
                $pi = '&amp;pi='.$_GET['pi'];
            } else {
                $pi = '';
            }

            if (!empty($_SESSION[$GLOBALS['installation_name'].'_csrf_token'])) {
                $token = '&amp;tk='.$_SESSION[$GLOBALS['installation_name'].'_csrf_token'];
            } else {
                $token = '';
            }
            $linktext = $desc;
            $linktext = str_ireplace('phplist', 'phpList', $linktext);

            return sprintf('<a href="./?page=%s%s%s%s" title="%s">%s</a>', $name, $url, $pi, $token,
                htmlspecialchars(strip_tags($title)), $linktext);
        }
    }

    return '';
//    return "\n<!--$name disabled $access -->\n";
//    return "\n$name disabled $access\n";
}

//# hmm actually should rename to PageLinkDialogButton
function PageLinkDialog($name, $desc = '', $url = '', $extraclass = '')
{
    //# as PageLink2, but add the option to ajax it in a popover window
    $link = PageLink2($name, $desc, $url);
    if ($link) {
        $link = str_replace('<a ', '<a class="button opendialog '.$extraclass.'" ', $link);
        $link .= '';
    }

    return $link;
}

function PageLinkDialogOnly($name, $desc = '', $url = '', $extraclass = '')
{
    //# as PageLink2, but add the option to ajax it in a popover window
    $link = PageLink2($name, $desc, $url);
    if ($link) {
        $link = str_replace('<a ', '<a class="opendialog '.$extraclass.'" ', $link);
        $link .= '';
    }

    return $link;
}

function PageLinkAjax($name, $desc = '', $url = '', $extraclass = '')
{
    //# as PageLink2, but add the option to ajax it in a popover window
    $link = PageLink2($name, $desc, $url);
    if ($link) {
        $link = str_replace('<a ', '<a class="ajaxable '.$extraclass.'" ', $link);
        $link .= '';
    }

    return $link;
}

function PageLinkClass($name, $desc = '', $url = '', $class = '', $title = '')
{
    $link = PageLink2($name, $desc, $url, false, $title);
    if (empty($class)) {
        $class = 'link';
    }
    if ($link) {
        $link = str_replace('<a ', '<a class="'.$class.'" ', $link);
        $link .= '';
    }

    return $link;
}

function PageLinkButton($name, $desc = '', $url = '', $extraclass = '', $title = '')
{
    return PageLinkClass($name, $desc, $url, 'button '.$extraclass, $title);
}

function PageLinkActionButton($name, $desc = '', $url = '', $extraclass = '', $title = '')
{
    //# as PageLink2, but add the option to ajax it in a popover window
    $link = PageLink2($name, $desc, $url);
    if ($link) {
        $link = str_replace('<a ', '<a class="action-button '.$extraclass.'" ', $link);
        $link .= '';
    }

    return $link;
}

function SidebarLink($name, $desc, $url = '')
{
    if ($url) {
        $url = '&'.$url;
    }
    $access = accessLevel($name);
    if ($access == 'owner' || $access == 'all') {
        if ($name == 'processqueue' && !MANUALLY_PROCESS_QUEUE) {
            return '<!-- '.$desc.'-->';
        } elseif ($name == 'processbounces' && !MANUALLY_PROCESS_BOUNCES) {
            return '<!-- '.$desc.'-->';
        } else {
            return sprintf('<a href="./?page=%s%s" target="phplistwindow">%s</a>', $name, $url, mb_strtolower($desc));
        }
    } else {
        return "\n<!--$name disabled $access -->\n";
    }
//    return "\n$name disabled $access\n";
}

function PageURL2($name, $desc = '', $url = '', $no_plugin = false)
{
    if (empty($name)) {
        return '';
    }
    if ($url) {
        $url = '&amp;'.$url;
    }
    $access = accessLevel($name);
    if ($access == 'owner' || $access == 'all' || $access == 'view') {
        if (!$no_plugin && !preg_match('/&amp;pi=/i',
                $name) && $_GET['pi'] && is_object($GLOBALS['plugins'][$_GET['pi']])
        ) {
            $pi = '&amp;pi='.$_GET['pi'];
        } else {
            $pi = '';
        }

        return sprintf('./?page=%s%s%s%s', $name, $url, $pi, addCsrfGetToken());
    } else {
        return '';
    }
}

function ListofLists($current, $fieldname, $subselect)
{
    //# @@TODO, this is slow on more than 150 lists. We should add caching or optimise
    $GLOBALS['systemTimer']->interval();
    $categoryhtml = array();
    //# add a hidden field, so that all checkboxes can be unchecked while keeping the field in POST to process it
    // $categoryhtml['unselect'] = '<input type="hidden" name="'.$fieldname.'[unselect]" value="1" />';

    $categoryhtml['selected'] = '';

    $categoryhtml['all'] = '<input type="hidden" name="' .$fieldname.'[unselect]" value="-1" />';
    if ($fieldname == 'targetlist') {
        $categoryhtml['all'] .= '
    <li><input type="checkbox" name="'.$fieldname.'[all]"';
        if (!empty($current['all'])) {
            $categoryhtml['all'] .= 'checked';
        }
        $categoryhtml['all'] .= ' />'.s('All Lists').'</li>';
    
        $categoryhtml['all'] .= '<li><input type="checkbox" name="'.$fieldname.'[allactive]"';
        if (!empty($current['allactive'])) {
            $categoryhtml['all'] .= 'checked="checked"';
        }
        $categoryhtml['all'] .= ' />'.s('All Public Lists').'</li>';
    }

    //# need a better way to suppress this
    if ($_GET['page'] != 'send') {
        $categoryhtml['all'] .= '<li>'.PageLinkDialog('addlist', s('Add a list')).'</li>';
    }

    $result = Sql_query('select * from '.$GLOBALS['tables']['list'].$subselect.' order by category, name');
    $numLists = Sql_Affected_Rows();

    while ($list = Sql_fetch_array($result)) {
        if (empty($list['category'])) {
            if ($numLists < 5) { //# for a small number of lists, add them to the @ tab
                $list['category'] = 'all';
            } else {
                $list['category'] = s('Uncategorised');
            }
        }
        if (!isset($categoryhtml[$list['category']])) {
            $categoryhtml[$list['category']] = '';
        }
        if (isset($current[$list['id']]) && $current[$list['id']]) {
            $list['category'] = 'selected';
        }
        $categoryhtml[$list['category']] .= sprintf('<li><input type="checkbox" name="'.$fieldname.'[%d]" value="%d" ',
            $list['id'], $list['id']);
        // check whether this message has been marked to send to a list (when editing)
        if (isset($current[$list['id']]) && $current[$list['id']]) {
            $categoryhtml[$list['category']] .= 'checked';
        }
        $categoryhtml[$list['category']] .= ' />'.htmlspecialchars(cleanListName(stripslashes($list['name'])));
        if ($list['active']) {
            $categoryhtml[$list['category']] .= ' <span class="activelist">'.s('Public list').'</span>';
        } else {
            $categoryhtml[$list['category']] .= ' <span class="inactivelist">'.s('Private list').'</span>';
        }

        if (!empty($list['description'])) {
            $desc = nl2br(stripslashes(disableJavascript($list['description'])));
            $categoryhtml[$list['category']] .= "<br />$desc";
        }
        $categoryhtml[$list['category']] .= '</li>';
        $some = 1;
    }
    if (empty($categoryhtml['selected'])) {
        unset($categoryhtml['selected']);
    }
//  file_put_contents('/tmp/timer.log','ListOfLists '.$GLOBALS['systemTimer']->interval(). "\n",FILE_APPEND);
    return $categoryhtml;
}

function listSelectHTML($current, $fieldname, $subselect, $alltab = '')
{
    $GLOBALS['systemTimer']->interval();
    $categoryhtml = ListofLists($current, $fieldname, $subselect);

    $tabno = 1;
    $listindex = $listhtml = '';
    $some = count($categoryhtml);

    if (!empty($alltab)) {
        //&& $some > 1) {
        //   unset($categoryhtml['all']);
        //## @@@TODO this has a weird effect when categories are numbers only eg years, because PHP renumbers them to 0,1,2
        //   array_unshift($categoryhtml,$alltab);
    }

    if ($some > 0) {
        foreach ($categoryhtml as $category => $content) {
            if ($category == 'all') {
                $category = '@';
            }
/* "if" commented on 2017-5-8 to show tabs always fixing UI bugs on all themes with jQuery in checkboxes. I suggest to remove it permanently. */
//            if ($some > 1) { //# don't show tabs, when there's just one
                $listindex .= sprintf('<li><a href="#%s%d">%s</a></li>', $fieldname, $tabno, $category);
 //           }
            if ($fieldname == 'targetlist') {
                // Add select all checkbox in every category to select all lists in that category.
                if ($category == 'selected') {
                    $content = sprintf('<li class="selectallcategory"><input type="checkbox" name="all-lists-'.$fieldname.'-cat-'.str_replace(' ',
                                '-',
                                strtolower($category)).'" checked="checked">'.s('Select all').'</li>').$content;
                } elseif ($category != '@') {
                    $content = sprintf('<li class="selectallcategory"><input type="checkbox" name="all-lists-'.$fieldname.'-cat-'.str_replace(' ',
                                '-', strtolower($category)).'">'.s('Select all').'</li>').$content;
                }
            }
            $listhtml .= sprintf('<div class="%s" id="%s%d"><ul>%s</ul></div>',
                str_replace(' ', '-', strtolower($category)), $fieldname, $tabno, $content);
            ++$tabno;
        }
    }

    $html = '<div class="tabbed"><ul>'.$listindex.'</ul>';
    $html .= $listhtml;
    $html .= '</div><!-- end of tabbed -->'; //# close tabbed

    if (!$some) {
        $html = s('There are no lists available');
    }
// file_put_contents('/tmp/timer.log','ListSelectHTML '.$GLOBALS['systemTimer']->interval(). "\n",FILE_APPEND);
    return $html;
}

function getSelectedLists($fieldname)
{
    $lists = array();
    if (!empty($_POST['addnewlist'])) {
        include 'editlist.php';
        $lists[$_SESSION['newlistid']] = $_SESSION['newlistid'];

        return $lists;
    }
    if (!isset($_POST[$fieldname])) {
        return array();
    }
    if (!empty($_POST[$fieldname]['all'])) {
        //# load all lists
        $req = Sql_Query(sprintf('select id from %s', $GLOBALS['tables']['list']));
        while ($row = Sql_Fetch_Row($req)) {
            $lists[$row[0]] = $row[0];
        }
    } elseif (!empty($_POST[$fieldname]['allactive'])) {
        //# load all active lists
        $req = Sql_Query(sprintf('select id from %s where active', $GLOBALS['tables']['list']));
        while ($row = Sql_Fetch_Row($req)) {
            $lists[$row[0]] = $row[0];
        }
    } else {
        //# verify the lists are actually allowed
        $req = Sql_Query(sprintf('select id from %s', $GLOBALS['tables']['list']));
        while ($row = Sql_Fetch_Row($req)) {
            if (in_array($row[0], $_POST[$fieldname])) {
                $lists[$row[0]] = $row[0];
            }
        }
    }

    return $lists;
}

function hostName()
{
    if (HTTP_HOST) {
        return HTTP_HOST;
    } elseif (!empty($_SERVER['HTTP_HOST'])) {
        return $_SERVER['HTTP_HOST'];
    } else {
        //# could check SERVER_NAME as well
        return getConfig('website');
    }
}

function Redirect($page)
{
    $website = hostName();
    header('Location: '.$GLOBALS['admin_scheme'].'://'.$website.$GLOBALS['adminpages']."/?page=$page");
    exit;
}

function formatBytes($value)
{
    $gb = 1024 * 1024 * 1024;
    $mb = 1024 * 1024;
    $kb = 1024;
    $gbs = $value / $gb;
    if ($gbs > 1) {
        return sprintf('%2.2fGb', $gbs);
    }
    $mbs = $value / $mb;
    if ($mbs > 1) {
        return sprintf('%2.2fMb', $mbs);
    }
    $kbs = $value / $kb;
    if ($kbs > 1) {
        return sprintf('%dKb', $kbs);
    } else {
        return sprintf('%dBytes', $value);
    }
}

function phpcfgsize2bytes($val)
{
    $val = trim($val);
    $last = mb_strtolower($val{strlen($val) - 1});
    $result = substr($val, 0, -1);
    switch ($last) {
        case 'g':
            $result *= 1024;
        case 'm':
            $result *= 1024;
        case 'k':
            $result *= 1024;
    }

    return $result;
}

function Help($topic, $text = '?')
{
    return sprintf('<a href="help/?topic=%s" class="helpdialog" target="_blank">%s</a>', $topic, $text);
}

/**
 * Checks if the list is private based on if the specified list id is active or not.
 *
 * @param int $listid
 * @return bool
 */
function isPrivateList($listid) {

    $activeVal = Sql_Query(sprintf('
          SELECT active
          FROM   %s
          WHERE  id = %d',
            $GLOBALS['tables']['list'], sql_escape($listid))
    );

    while ($row = Sql_Fetch_Row($activeVal)) {
        $activeVal = $row[0];
    }

    if ($activeVal == 0) {
        return true;
    } else
        return false;
}

// Debugging system, needs $debug = TRUE and $verbose = TRUE or $debug_log = {path} in config.php
// Hint: When using log make sure the file gets write permissions

function dbg($variable, $description = 'Value', $nestingLevel = 0)
{
    //  smartDebug($variable, $description, $nestingLevel); //TODO Fix before release!
//  return;

    global $config;

    if (isset($config['debug']) && !$config['debug']) {
        return;
    }

    if (is_array($variable)) {
        $tmp = $variable;
        $variable = '';
        foreach ($tmp as $key => $val) {
            $variable .= $key.'='.$val.';';
        }
    }

    $msg = $description.': '.$variable;

    if (isset($config['verbose']) && $config['verbose']) {
        echo "\n".'DBG: '.$msg.'<br/>'."\n";
    } elseif (isset($config['debug_log']) && $config['debug_log']) {
        $fp = @fopen($config['debug_log'], 'a');
        $line = '['.date('d M Y, H:i:s').'] '.$_SERVER['REQUEST_METHOD'].'-'.$_SERVER['REQUEST_URI'].'('.$GLOBALS['pagestats']['number_of_queries'].") $msg \n";
        @fwrite($fp, $line);
        @fclose($fp);
        //  $fp = fopen($config["sql_log"],"a");
        //  fwrite($fp,"$line");
        //  fclose($fp);
    }
}

function PageData($id)
{
    global $tables;
    $req = Sql_Query(sprintf('select * from %s where id = %d', $tables['subscribepage_data'], $id));
    if (!Sql_Affected_Rows()) {
        $data = array();
        $data['header'] = getConfig('pageheader');
        $data['footer'] = getConfig('pagefooter');
        $data['button'] = 'Subscribe';
        $data['attributes'] = '';
        $req = Sql_Query(sprintf('select * from %s order by listorder', $GLOBALS['tables']['attribute']));
        while ($row = Sql_Fetch_Array($req)) {
            $data['attributes'] .= $row['id'].'+';
            $data[sprintf('attribute%03d', $row['id'])] = '';
            foreach (array(
                         'id',
                         'default_value',
                         'listorder',
                         'required',
                     ) as $key) {
                $data[sprintf('attribute%03d', $row['id'])] .= $row[$key].'###';
            }
        }
        $data['attributes'] = substr($data['attributes'], 0, -1);
        $data['htmlchoice'] = 'checkforhtml';
        $lists = array();
        $req = Sql_Query(sprintf('select * from %s where active order by listorder', $GLOBALS['tables']['list']));
        while ($row = Sql_Fetch_Array($req)) {
            array_push($lists, $row['id']);
        }
        $data['lists'] = implode(',', $lists);
        $data['intro'] = $GLOBALS['strSubscribeInfo'];
        $data['emaildoubleentry'] = 'yes';
        $data['thankyoupage'] = '';
        foreach ($data as $key => $val) {
            $data[$key] = str_ireplace('[organisation_name]', $GLOBALS['organisation_name'], $val);
        }

        return $data;
    }
    while ($row = Sql_Fetch_Array($req)) {
        if (in_array($row['name'], array(
            'title',
            'language_file',
            'intro',
            'header',
            'footer',
            'thankyoupage',
            'button',
            'htmlchoice',
            'emaildoubleentry',
            'ajax_subscribeconfirmation',
        ))) {
            $data[$row['name']] = stripslashes($row['data']);
        } else {
            $data[$row['name']] = $row['data'];
        }
        $data[$row['name']] = preg_replace('/<\?=VERSION\?>/i', VERSION, $data[$row['name']]);
        $data[$row['name']] = str_ireplace('[organisation_name]', $GLOBALS['organisation_name'], $data[$row['name']]);
        $data[$row['name']] = str_ireplace('[website]', $GLOBALS['website'], $data[$row['name']]);
        $data[$row['name']] = str_ireplace('[website]', $GLOBALS['domain'], $data[$row['name']]);
        //@@ TODO, add call to plugins here?
    }
    if (!isset($data['lists'])) {
        $data['lists'] = '';
    }
    if (!isset($data['emaildoubleentry'])) {
        $data['emaildoubleentry'] = '';
    }
    if (!isset($data['rssdefault'])) {
        $data['rssdefault'] = '';
    }
    if (!isset($data['rssintro'])) {
        $data['rssintro'] = '';
    }
    if (!isset($data['rss'])) {
        $data['rss'] = '';
    }
    if (!isset($data['lists'])) {
        $data['lists'] = '';
    }

    return $data;
}

function PageAttributes($data)
{
    $attributes = explode('+', $data['attributes']);
    $attributedata = array();
    if (is_array($attributes)) {
        foreach ($attributes as $attribute) {
            if (isset($data[sprintf('attribute%03d', $attribute)])) {
                list($attributedata[$attribute]['id'], $attributedata[$attribute]['default_value'], $attributedata[$attribute]['listorder'], $attributedata[$attribute]['required']) = explode('###',
                    $data[sprintf('attribute%03d', $attribute)]);
                if (!isset($sorted) || !is_array($sorted)) {
                    $sorted = array();
                }
                $sorted[$attributedata[$attribute]['id']] = $attributedata[$attribute]['listorder'];
            }
        }
        if (isset($sorted) && is_array($sorted)) {
            $attributes = $sorted;
            asort($attributes);
        }
    }

    return array(
        $attributes,
        $attributedata,
    );
}

/**
 * Return either the short or long representation of a month in the language for the current admin.
 * Cache the set of month translations to avoid repeating the translations.
 *
 * @param string $month month number 01-12
 * @param bool   $short generate short or long representation of the month
 *
 * @return string
 */
function monthName($month, $short = 0)
{
    static $shortmonths;
    static $months;

    if ($short) {
        if ($shortmonths === null) {
            $shortmonths = array(
                '',
                s('Jan'),
                s('Feb'),
                s('Mar'),
                s('Apr'),
                s('May'),
                s('Jun'),
                s('Jul'),
                s('Aug'),
                s('Sep'),
                s('Oct'),
                s('Nov'),
                s('Dec'),
            );
        }

        return $shortmonths[(int) $month];
    }

    if ($months === null) {
        $months = array(
            '',
            s('January'),
            s('February'),
            s('March'),
            s('April'),
            s('May'),
            s('June'),
            s('July'),
            s('August'),
            s('September'),
            s('October'),
            s('November'),
            s('December'),
        );
    }

    return $months[(int) $month];
}

/**
 * Format a date using a configurable format string.
 *      d   2-digit day with leading zero
 *      j   day without leading zero
 *      F   long representation of month
 *      m   2-digit month with leading zero
 *      n   month without leading zero
 *      M   short representation of month
 *      y   2-digit year
 *      Y   4-digit year
 * Optionally force a short representation to be used for the month.
 *
 * @param string $date  date as YYYY-MM-DD
 * @param bool   $short force short representation of the month
 *
 * @return string
 */
function formatDate($date, $short = 0)
{
    $format = getConfig('date_format');
    $year = substr($date, 0, 4);
    $month = substr($date, 5, 2);
    $day = substr($date, 8, 2);
    $specifiers = array(
        'Y' => $year,
        'y' => substr($year, 2, 2),
        'F' => monthName($month, $short),
        'M' => monthName($month, true),
        'm' => $month,
        'n' => +$month,
        'd' => $day,
        'j' => +$day,
    );
    $result = strtr($format, $specifiers);

    return $result;
}

function formatTime($time, $short = 0)
{
    return $time;
}

function formatDateTime($datetime, $short = 0)
{
    if ($datetime == '') {
        return '';
    }
    $date = substr($datetime, 0, 10);
    $time = substr($datetime, 11, 8);

    return formatDate($date, $short).' '.formatTime($time, $short);
}

$oldestpoweredimage = 'iVBORw0KGgoAAAANSUhEUgAAAFgAAAAfCAMAAABUFvrSAAAABGdBTUEAALGPC/xhBQAAAMBQTFRFmQAAZgAAmgICmwUFnAgInQsLnxAQbw4OohYWcBERpBwcpiIiqCcnqiwsfCAgrDAwrjU1rzg4sTs7iTAws0FBtEVFtklJuU9Pu1VVn0pKkEREvltbtFxcwWRkw2trm1ZWrGNjx3V1y3x8zoWFqW5u0I6O15ycuoqK3aysxZqa3rm55s3N8t3d9+zs+fHx5t/f/Pf3/fr6////7+/vz8/PtbW1j4+Pb29vVVVVRkZGKioqExMTDg4OBwcHAwMDAAAAB4LGQwAAAAFiS0dEAIgFHUgAAAAJcEhZcwAACxIAAAsSAdLdfvwAAAAHdElNRQfSBAITGhB/UY5ZAAAD2ElEQVR4nI2VC3uiOhCGoVqq9YbcZHGxIoI0SLGhIJdt8///1c4kHnVPhTpPK4TPvEzmpkTvsiK/73vckmAuSdJ93/26G5wEhsQN7uuaVTSrWP1BGT1WtCpgUWUf7FhVX1WWVZ/Hz/Qu6ltoSf8ZLFnxwfKypPBXZ02dsrQss7oovnJ+PZa0au6gHqJFT5KuwDmjGctZzp09lux4pF911RRFTT/x+geU8ifqe2T3pX8MEsM+ioY2BThHyyavm5TWRQbhKMS1KVJQOo24ivR/o/RY101Oi4Yd4SUVBoTmNaCqnOYV0POqKLtyR7zBNyoHVz+402nxZqI83uIi+KdSWjtOfFPYh+boeaB8D4N0Xx3LsnzjaRK5hqZOkNwK7u4rIsv6Nyrxl0t7YRmc3ApmneCdLK//efAWhxvPW63cpc3JreCU1QyrNj/31+tul5K1s+brtSzv0p3j7IS0ffHW+lT3kO3aljYbP7eBcyhk6BAKnXGJ6gv8y0NMmg4eD3G1pe97iIvs4OIpCjbearkw1PGoDQzFm7OU5U124sbI3G6HIriIcXY6pnAf+VzCF+kHCIhrm/NJK7iqM+gKdmmvV+Er8hPMHcY44bURrbn0HqGU+OAyxKIV3JQweWh9dphu8dgiCARzNwXujrsfvfCIkGiKUrBBsMvnpAl4xTThBm10qeO8uTQgBDE+XQkF1I4eyBr9fiM6SntC+DsjDqY+d9CTzAQcmHGCdwFX58xdOmKIlClHRQ7yee4gRoQ84VMOnp/BJFaUfcRvpZudF5/AcB2eYns6+z4QKxKgREOevDPYo6E7kjrAkDtw57B38PTgowOIULi65RIhXDpAVUC5ncGSBwF0O8C4W08xqk+pSOQ+XInc/bqWYlEUZ7BtSkpEO8DgzlTm9koPOn7G/i90MQn1a8kX/UFDKAMe48S2430b+BDjqVNsvCmBcPIERp6OuYuDaykCLrYH34a0WQTBmt0EH8hm6f7mhRu8QsCSEGYNFJHvuitYktW15AJX6x6bwt7JSlWNxRJO/ULf/E0QBjDAwGy05dJdeSfJ55INXJhAg9ZfEGHEfVaexzPNssWpcSyCTwvLsngvWQt76QqJzzUcmXPO7QLHq4H00FcGo8ncsHjFRq4Y5NocTFXVuWYAWkh8EoO76onbbwHHHh+oCAaX54aubxPqA9U0tNlsMpmMwSYzVTNMIeErTXCXx/fxsd+7Cd6MTzcPvcfBYIRkKwxD2KnB1vFo9CxsNJ6A2yZItmWdNOT2+73b4LMBGFzG/RrYXBU7uSkKfKA0UyEwVyJwe72Hh1u4v1tVRVPPqSx/AAAAAElFTkSuQmCC';
$olderpoweredimage = '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';
$oldpoweredimage = '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';
$newpoweredimage = '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';

function FileNotFound($msg = '')
{
    ob_end_clean();
    header('HTTP/1.0 404 File Not Found', true, 404);
    if (defined('ERROR404PAGE') && is_file($_SERVER['DOCUMENT_ROOT'].'/'.ERROR404PAGE)) {
        echo file_get_contents($_SERVER['DOCUMENT_ROOT'].'/'.ERROR404PAGE);
        exit;
    }

    printf('<html><head><title>404 Not Found</title></head><body><h1>Not Found</h1>The requested document was not found on this server<br/>%s<br/>Please contact the <a href="mailto:%s?subject=File not Found: %s">Administrator</a><p><hr><address><a href="http://phplist.com" target="_phplist">phpList</a> version %s</address></body></html>',
        $msg, getConfig('admin_address'),
        strip_tags($_SERVER['REQUEST_URI']), VERSION);
    exit;
}

function findMime($filename)
{
    list($name, $ext) = explode('.', $filename);
    if (!$ext || !is_file(MIMETYPES_FILE)) {
        return DEFAULT_MIMETYPE;
    }
    $fp = @fopen(MIMETYPES_FILE, 'r');
    $contents = fread($fp, filesize(MIMETYPES_FILE));
    fclose($fp);
    $lines = explode("\n", $contents);
    foreach ($lines as $line) {
        if (!preg_match("/^\s*#/", $line) && !preg_match("/^\s*$/", $line)) {
            $line = preg_replace("/\t/", ' ', $line);
            $items = explode(' ', $line);
            $mime = array_shift($items);
            foreach ($items as $extension) {
                $extension = trim($extension);
                if ($ext == $extension) {
                    return $mime;
                }
            }
        }
    }

    return DEFAULT_MIMETYPE;
}

function excludedDateForRepetition($date)
{
    if (!is_array($GLOBALS['repeat_exclude'])) {
        return 0;
    }
    foreach ($GLOBALS['repeat_exclude'] as $exclusion) {
        $formatted_value = Sql_Fetch_Row_Query(sprintf('select date_format("%s","%s")', $date, $exclusion['format']));
        foreach ($exclusion['values'] as $disallowed) {
            if ($formatted_value[0] == $disallowed) {
                return 1;
            }
        }
    }

    return 0;
}

function delimited($data)
{
    $delimitedData = '';
    reset($data);
    foreach ($data as $key => $val) {
        $delimitedData .= $key.'KEYVALSEP'.$val.'ITEMSEP';
    }
    $length = strlen($delimitedData);

    return substr($delimitedData, 0, -7);
}

function parseDelimitedData($value)
{
    $data = array();
    $rawdata = explode('ITEMSEP', $value);
    foreach ($rawdata as $item) {
        list($key, $val) = explode('KEYVALSEP', $item);
        $data[$key] = ltrim($val);
    }

    return $data;
}

function repeatMessage($msgid)
{
    //  if (!USE_REPETITION && !USE_rss) return;

    $data = loadMessageData($msgid);
    //# do not repeat when it has already been done
    if ($data['repeatinterval'] == 0 || !empty($data['repeatedid'])) {
        return;
    }

    // calculate the future embargo, a multiple of repeatinterval minutes after the current embargo

    $msgdata = Sql_Fetch_Array_Query(
        sprintf(
            'SELECT *,
        embargo +
            INTERVAL (FLOOR(TIMESTAMPDIFF(MINUTE, embargo, GREATEST(embargo, NOW())) / repeatinterval) + 1) * repeatinterval MINUTE AS newembargo
        FROM %s
        WHERE id = %d AND now() < repeatuntil',
            $GLOBALS['tables']['message'],
            $msgid
        )
    );

    if (!$msgdata) {
        logEvent("Message $msgid not repeated due to reaching the repeatuntil date");

        return;
    }

    // check whether the new embargo is not on an exclusion
    if (isset($GLOBALS['repeat_exclude']) && is_array($GLOBALS['repeat_exclude'])) {
        $loopcnt = 0;

        while (excludedDateForRepetition($msgdata['newembargo'])) {
            if (++$loopcnt > 15) {
                logEvent("Unable to find new embargo date too many exclusions? for message $msgid");

                return;
            }
            $result = Sql_Fetch_Array_Query(
                sprintf(
                    "SELECT '%s' + INTERVAL repeatinterval MINUTE AS newembargo
            FROM %s
            WHERE id = %d",
                    $msgdata['newembargo'],
                    $GLOBALS['tables']['message'],
                    $msgid
                )
            );
            $msgdata['newembargo'] = $result['newembargo'];
        }
    }

    // copy the new message
    Sql_Query(sprintf('
    insert into %s (entered) values(now())', $GLOBALS['tables']['message']));
    $newid = Sql_Insert_id();
    require dirname(__FILE__).'/structure.php';
    if (!is_array($DBstruct['message'])) {
        logEvent("Error including structure when trying to duplicate message $msgid");

        return;
    }

    //  Do not copy columns that use default values or are explicitly set, or indices
    $columnsToCopy = array_diff(
        array_keys($DBstruct['message']),
        array(
            'id', 'entered', 'modified', 'embargo', 'status', 'sent', 'processed', 'astext', 'ashtml',
            'astextandhtml', 'aspdf', 'astextandpdf', 'viewed', 'bouncecount', 'sendstart', 'uuid',
        )
    );
    $columnsToCopy = preg_grep('/^index_/', $columnsToCopy, PREG_GREP_INVERT);

    foreach ($columnsToCopy as $column) {
        Sql_Query(sprintf('update %s set %s = "%s" where id = %d',
            $GLOBALS['tables']['message'], $column, addslashes($msgdata[$column]), $newid));
    }
    Sql_Query(sprintf(
        'update %s set embargo = "%s",status = "submitted", uuid="%s" where id = %d',
        $GLOBALS['tables']['message'],
        $msgdata['newembargo'],
        (string) UUID::generate(4),
        $newid
    ));

    // copy rows in messagedata except those that are explicitly set
    $req = Sql_Query(sprintf(
        "SELECT *
    FROM %s
    WHERE id = %d AND name NOT IN ('id', 'embargo', 'finishsending')",
        $GLOBALS['tables']['messagedata'], $msgid
    ));
    while ($row = Sql_Fetch_Array($req)) {
        setMessageData($newid, $row['name'], $row['data']);
    }

    list($e['year'], $e['month'], $e['day'], $e['hour'], $e['minute'], $e['second']) =
        sscanf($msgdata['newembargo'], '%04d-%02d-%02d %02d:%02d:%02d');
    unset($e['second']);
    setMessageData($newid, 'embargo', $e);

    $finishSending = time() + DEFAULT_MESSAGEAGE;
    $finish = array(
        'year'   => date('Y', $finishSending),
        'month'  => date('m', $finishSending),
        'day'    => date('d', $finishSending),
        'hour'   => date('H', $finishSending),
        'minute' => date('i', $finishSending),
    );
    setMessageData($newid, 'finishsending', $finish);

    // lists
    $req = Sql_Query(sprintf('select listid from %s where messageid = %d', $GLOBALS['tables']['listmessage'], $msgid));
    while ($row = Sql_Fetch_Row($req)) {
        Sql_Query(sprintf('insert into %s (messageid,listid,entered) values(%d,%d,now())',
            $GLOBALS['tables']['listmessage'], $newid, $row[0]));
    }

    // attachments
    $req = Sql_Query(sprintf('select * from %s,%s where %s.messageid = %d and %s.attachmentid = %s.id',
        $GLOBALS['tables']['message_attachment'], $GLOBALS['tables']['attachment'],
        $GLOBALS['tables']['message_attachment'], $msgid, $GLOBALS['tables']['message_attachment'],
        $GLOBALS['tables']['attachment']));
    while ($row = Sql_Fetch_Array($req)) {
        if (is_file($row['remotefile'])) {
            // if the "remote file" is actually local, we want to refresh the attachment, so we set
            // filename to nothing
            $row['filename'] = '';
        }

        Sql_Query(sprintf('insert into %s (filename,remotefile,mimetype,description,size)
      values("%s","%s","%s","%s",%d)',
            $GLOBALS['tables']['attachment'], addslashes($row['filename']), addslashes($row['remotefile']),
            addslashes($row['mimetype']), addslashes($row['description']), $row['size']));
        $attid = Sql_Insert_id();
        Sql_Query(sprintf('insert into %s (messageid,attachmentid) values(%d,%d)',
            $GLOBALS['tables']['message_attachment'], $newid, $attid));
    }
    logEvent("Message $msgid was successfully rescheduled as message $newid");
    //# remember we duplicated, in order to avoid doing it again (eg when requeuing)
    setMessageData($msgid, 'repeatedid', $newid);
    if (getConfig('pqchoice') == 'phplistdotcom') {
        activateRemoteQueue();
    }
}

function versionCompare($thisversion, $latestversion)
{
    // return 1 if $thisversion is larger or equal to $latestversion

    list($major1, $minor1, $sub1) = sscanf($thisversion, '%d.%d.%d');
    list($major2, $minor2, $sub2) = sscanf($latestversion, '%d.%d.%d');
    if ($major1 > $major2) {
        return 1;
    }
    if ($major1 == $major2 && $minor1 > $minor2) {
        return 1;
    }
    if ($major1 == $major2 && $minor1 == $minor2 && $sub1 >= $sub2) {
        return 1;
    }

    return 0;
}

function cleanArray($array)
{
    $result = array();
    if (!is_array($array)) {
        return $array;
    }
    foreach ($array as $key => $val) {
        //# 0 is a valid key
        if (isset($key) && !empty($val)) {
            $result[$key] = $val;
        }
    }

    return $result;
}

function cl_processtitle($title)
{
    $title = preg_replace('/[^\w-]/', '', $title);
    if (function_exists('cli_set_process_title')) { // PHP5.5 and up
        cli_set_process_title('phpList:'.$GLOBALS['installation_name'].':'.$title);
    } elseif (function_exists('setproctitle')) { // pecl extension
        setproctitle('phpList:'.$GLOBALS['installation_name'].':'.$title);
    }
}

function cl_output($message)
{
    if (!empty($GLOBALS['commandline'])) {
        @ob_end_clean();
        echo $GLOBALS['installation_name'].' - '.strip_tags($message)."\n";
        @ob_start();
    }
}

function cl_progress($message)
{
    if ($GLOBALS['commandline']) {
        @ob_end_clean();
        echo $GLOBALS['installation_name'].' - '.strip_tags($message)."\r";
        @ob_start();
    }
}

function phplist_shutdown()
{
    //  output( "Script status: ".connection_status(),0); # with PHP 4.2.1 buggy. http://bugs.php.net/bug.php?id=17774
    $status = connection_status();
    if ($GLOBALS['mail_error_count']) {
        $message = "Some errors occurred in the phpList Mailinglist System\n"
            .'URL: '.$GLOBALS['admin_scheme'].'://'.hostName()."{$_SERVER['REQUEST_URI']}\n"
            ."Error message(s):\n\n"

            .$GLOBALS['mail_error'];
        $message .= "\n==== debugging information\n\nSERVER Vars\n";
        if (is_array($_SERVER)) {
            foreach ($_SERVER as $key => $val) {
                if (stripos($key, 'password') === false) {
                    $message .= $key.'='.serialize($val)."\n";
                }
            }
        }
        foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
            $plugin->processError($message);
        }
//   sendMail(getConfig("report_address"),$GLOBALS["installation_name"]." Mail list error",$message);
    }

//  print "Phplist shutdown $status";
//  exit;
}

function trimArray($array)
{
    $result = array();
    if (!is_array($array)) {
        return $array;
    }
    foreach ($array as $key => $val) {
        $testval = trim($val);
        if (isset($key) && !empty($testval)) {
            $result[$key] = $val;
        }
    }

    return $result;
}

register_shutdown_function('phplist_shutdown');

function secs2time($secs)
{
    $years = $days = $hours = $mins = 0;
    $hours = (int) ($secs / 3600);
    $secs = $secs - ($hours * 3600);
    if ($hours > 24) {
        $days = (int) ($hours / 24);
        $hours = $hours - (24 * $days);
    }
    if ($days > 365) { //# a well, an estimate
        $years = (int) ($days / 365);
        $days = $days - ($years * 365);
    }
    $mins = (int) ($secs / 60);
    $secs = (int) ($secs % 60);

    $format = compact('years', 'days', 'hours', 'mins');

    $output = '';

    foreach($format as $unit => $value) {
        if ($value > 0) {
              $output .= ' '.$value.' '.s($unit);
        }
    }

    if ($secs) {
        $output .= ' '.sprintf('%02d', $secs).' '.s('secs');
    }

    return $output;
}

function listPlaceHolders()
{
    $html = '<table border="1"><tr><td><strong>'.s('Attribute').'</strong></td><td><strong>'.s('Placeholder').'</strong></td></tr>';
    $req = Sql_query('
    select
        name
    from
        '.$GLOBALS['tables']['attribute'].'
    order by
        listorder
    ');
    while ($row = Sql_Fetch_Row($req)) {
        if (strlen($row[0]) <= 30) {
            $html .= sprintf('<tr><td>%s</td><td>[%s]</td></tr>', $row[0], strtoupper(cleanAttributeName($row[0])));
        }
    }
    $html .= '</table>';

    return $html;
}

//# clean out chars that make preg choke
//# primarily used for parsing the placeholders in emails.
function cleanAttributeName($name)
{
    $name = str_replace('(', '', $name);
    $name = str_replace(')', '', $name);
    $name = str_replace('/', '', $name);
    $name = str_replace('\\', '', $name);
    $name = str_replace('*', '', $name);
    $name = str_replace('.', '', $name);

    return $name;
}

function cleanCommaList($sList)
{
    if (strpos($sList, ',') === false) {
        return $sList;
    }
    $aList = explode(',', $sList);

    return implode(',', trimArray($aList));
}

function printobject($object)
{
    if (!is_object($object)) {
        echo 'Not an object';

        return;
    }
    $class = get_class($object);
    echo "Class: $class<br/>";
    $vars = get_object_vars($object);
    echo 'Vars:';
    printArray($vars);
}

function printarray($array)
{
    if (is_object($array)) {
        return printObject($array);
    }
    if (!is_array($array)) {
        return;
    }
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            echo $key.'(array):<blockquote>';
            printarray($value); //recursief!!
            echo '</blockquote>';
        } elseif (is_object($value)) {
            echo $key.'(object):<blockquote>';
            printobject($value);
            echo '</blockquote>';
        } else {
            echo $key.'==>'.$value.'<br />';
        }
    }
}

function simplePaging($baseurl, $start, $total, $numpp, $itemname = '')
{
    $start = max(0, $start);
    $end = min($total, $start + $numpp);

    if (!empty($itemname)) {
        $text = $GLOBALS['I18N']->get('Listing %d to %d of %d');
    } else {
        $text = $GLOBALS['I18N']->get('Listing %d to %d');
    }
    $listing = sprintf($text, $start + 1, $end, $total).' '.$itemname;

    if ($total < $numpp) {
        return $listing;
    }
    // The last page displays the remaining items
    $remainingItems = $total % $numpp;

    if ($remainingItems == 0) {
        $remainingItems = $numpp;
    }
    $startLast = $total - $remainingItems;

    return '<div class="paging">
    <p class="range">' .$listing.'</p><div class="controls">
    <a title="' .$GLOBALS['I18N']->get('First Page').'" class="first" href="'.PageUrl2($baseurl.'&amp;start=0').'"></a>
    <a title="' .$GLOBALS['I18N']->get('Previous').'" class="previous" href="'.PageUrl2($baseurl.sprintf('&amp;start=%d',
            max(0, $start - $numpp))).'"></a>
    <a title="' .$GLOBALS['I18N']->get('Next').'" class="next" href="'.PageUrl2($baseurl.sprintf('&amp;start=%d',
            min($startLast, $start + $numpp))).'"></a>
    <a title="' .$GLOBALS['I18N']->get('Last Page').'" class="last" href="'.PageUrl2($baseurl.sprintf('&amp;start=%d',
            $startLast)).'"></a>
    </div></div>
  ';
}

function Paging($base_url, $start, $total, $numpp = 10, $label = '')
{
    $page = 1;
    $window = 8; //# size left and right of current
    $data = ''; //PagingPrevious($base_url,$start,$total,$numpp,$label);#.'&nbsp;|&nbsp;';
    if (!isset($GLOBALS['config']['paginglabeltitle'])) {
        $labeltitle = $label;
    } else {
        $labeltitle = $GLOBALS['config']['paginglabeltitle'];
    }
    if ($total < $numpp) {
        return '';
    }

    for ($i = 0; $i <= $total; $i += $numpp) {
        if ($i == $start) {
            $data .= sprintf('<a class="current paging-item" title="%s %s" class="paging-item">%s%s</a>', $labeltitle,
                $page, $label, $page);
        } //# only show 5 left and right of current
        elseif ($i > $start - $window * $numpp && $i < $start + $window * $numpp) {
            //    else
            $data .= sprintf('<a href="%s&amp;s=%d" title="%s %s" rel="nofollow" class="paging-item">%s%s</a>',
                $base_url, $i, $labeltitle, $page, $label, $page);
        }
        ++$page;
    }
    if ($page == 1) {
        return '';
    }
    // $data .= PagingNext($base_url,$start,$total,$numpp,$label,$page);
    return '<div class="paging">'.PagingPrevious($base_url, $start, $total, $numpp,
        $label).'<div class="items">'.$data.'</div>'.PagingNext($base_url, $start, $total, $numpp,
        $label).'</div>';

    return '<div class="paging"><a class="prev browse left">&lt;&lt;</a><div class="items">'.$data.'</div><a class="next browse right">&gt;&gt;</a></div>';
}

function PagingNext($base_url, $start, $total, $numpp, $label = '')
{
    if (!isset($GLOBALS['config']['pagingnext'])) {
        $GLOBALS['config']['pagingnext'] = '&gt;&gt;';
    }
    if (($start + $numpp - 1) < $total) {
        $data = sprintf('<a href="%s&amp;s=%d" title="Next" class="pagingnext paging-item" rel="nofollow">%s</a>',
            $base_url, $start + $numpp, $GLOBALS['config']['pagingnext']);
    } else {
        $data = sprintf('<a class="pagingnext paging-item">%s</a>', $GLOBALS['config']['pagingnext']);
    }

    return $data;
}

function PagingPrevious($base_url, $start, $total, $numpp, $label = '')
{
    if (!isset($GLOBALS['config']['pagingback'])) {
        $GLOBALS['config']['pagingback'] = '&lt;&lt;';
    }
    $page = 1;
    if ($start > 1) {
        $data = sprintf('<a href="%s&amp;s=%d" title="Previous" class="pagingprevious paging-item" rel="nofollow">%s</a>',
            $base_url, $start - $numpp, $GLOBALS['config']['pagingback']);
    } else {
        $data = sprintf('<a class="pagingprevious paging-item">%s</a>', $GLOBALS['config']['pagingback']);
    }

    return $data;
}

class timer
{
    public $start;
    public $previous = 0;

    public function __construct()
    {
        $now = gettimeofday();
        $this->start = $now['sec'] * 1000000 + $now['usec'];
    }

    public function elapsed($seconds = 0)
    {
        $now = gettimeofday();
        $end = $now['sec'] * 1000000 + $now['usec'];
        $elapsed = $end - $this->start;
        if ($seconds) {
            return sprintf('%0.10f', $elapsed / 1000000);
        } else {
            return sprintf('%0.10f', $elapsed);
        }
    }

    public function interval($seconds = 0)
    {
        $now = gettimeofday();
        $end = $now['sec'] * 1000000 + $now['usec'];
        if (!$this->previous) {
            $elapsed = $end - $this->start;
        } else {
            $elapsed = $end - $this->previous;
        }
        $this->previous = $end;

        if ($seconds) {
            return sprintf('%0.10f', $elapsed / 1000000);
        } else {
            return sprintf('%0.10f', $elapsed);
        }
    }
}
