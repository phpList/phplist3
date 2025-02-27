<?php

// check for basic prerequisites
require_once dirname(__FILE__).'/checkprerequisites.php';

if (ob_get_level() == 0) {
    @ob_start();
}
$er = error_reporting(0);
// check for commandline and cli version
if (!isset($_SERVER['SERVER_NAME']) && PHP_SAPI != 'cli') {
    echo 'Warning: commandline only works well with the cli version of PHP';
}

if (isset($_REQUEST['_SERVER'])) {
    exit;
}
$cline = array();
$GLOBALS['commandline'] = 0;

require_once dirname(__FILE__).'/inc/unregister_globals.php';
require_once dirname(__FILE__).'/inc/magic_quotes.php';
// Remove when php5.X is unsupported, currently 31 Dec 2018, https://secure.php.net/supported-versions.php
require_once dirname(__FILE__).'/inc/random_compat/random.php';

/* no idea why it wouldn't be there (no dependencies are mentioned on php.net/mb_strtolower), but
 * found a system missing it. We need it from the start */
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($string)
    {
        return strtolower($string);
    }
}

// setup commandline
//if (php_sapi_name() == "cli") {
//# 17355 - change the way CL is detected, using the way Drupal does it.
if (!isset($_SERVER['SERVER_SOFTWARE']) && (php_sapi_name() == 'cli' || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0))) {
    for ($i = 0; $i < $_SERVER['argc']; ++$i) {
        $my_args = array();
        if (preg_match('/(.*)=(.*)/', $_SERVER['argv'][$i], $my_args)) {
            $_GET[$my_args[1]] = $my_args[2];
            $_REQUEST[$my_args[1]] = $my_args[2];
        }
    }
    $GLOBALS['commandline'] = 1;
    $cline = parseCline();
    $dir = dirname($_SERVER['SCRIPT_FILENAME']);
    chdir($dir);

    if (isset($cline['c']) && !is_file($cline['c'])) {
        echo "Cannot find config file\n";
        exit;
    }
} else {
    $GLOBALS['commandline'] = 0;
    header('Cache-Control: no-cache, must-revalidate');           // HTTP/1.1
    header('Pragma: no-cache');                                   // HTTP/1.0
}

$configfile = '';

if (isset($_SERVER['ConfigFile']) && is_file($_SERVER['ConfigFile'])) {
    $configfile = $_SERVER['ConfigFile'];
} elseif (isset($cline['c']) && is_file($cline['c'])) {
    $configfile = $cline['c'];
} elseif (is_file(__DIR__.'/../config/config.php')) {
    $configfile = __DIR__.'/../config/config.php';
} else {
    $configfile = '../config/config.php';
}

if (is_file($configfile) && filesize($configfile) > 20) {
    include $configfile;
} elseif ($GLOBALS['commandline']) {
    echo 'Cannot find config file'."\n";
} else {
    echo '<h3>Cannot find config file, please check permissions</h3>';
    exit;
}

$ajax = isset($_GET['ajaxed']);

if (!isset($database_host) || !isset($database_user) || !isset($database_password) || !isset($database_name)) {
    echo 'Database details incomplete, please check your config file';
    exit;
}
//exit;
// record the start time(usec) of script
$now = gettimeofday();
$GLOBALS['pagestats'] = array();
$GLOBALS['pagestats']['time_start'] = $now['sec'] * 1000000 + $now['usec'];
$GLOBALS['pagestats']['number_of_queries'] = 0;

// load all required files
require_once dirname(__FILE__).'/init.php';
require_once dirname(__FILE__).'/inc/UUID.php';
require_once dirname(__FILE__).'/'.$GLOBALS['database_module'];
include_once dirname(__FILE__).'/defaultFrontendTexts.php';
if (file_exists(dirname(__FILE__).'/../texts/'.$GLOBALS['language_module'])) {
    include_once dirname(__FILE__).'/../texts/'.$GLOBALS['language_module'];
}
@session_start();
include_once dirname(__FILE__).'/languages.php';
require_once dirname(__FILE__).'/defaultconfig.php';

require_once dirname(__FILE__).'/connect.php';
include_once dirname(__FILE__).'/lib.php';
require_once dirname(__FILE__).'/inc/interfacelib.php';

if (!empty($cline['-']) && $cline['-'] == 'version') {
    cl_output(ClineSignature());
    exit;
}

$systemTimer = new timer();

// do a loose check, if the token is there, it needs to be valid.
verifyCsrfGetToken(false);

if (!empty($_SESSION['hasconf']) || Sql_Table_exists($tables['config'], 1)) {
    $_SESSION['hasconf'] = true;
    //## Activate all plugins
    /* already done in pluginlib */
    //foreach ($GLOBALS['plugins'] as $plugin) {
    //$plugin->activate();
    //}
}

if (isset($_REQUEST['resettheme'])) {
    SetCookie ( 'preferredTheme','');
}

if (isset($_REQUEST['settheme']) && !empty($_REQUEST['settheme']) && is_array($THEMES[$_REQUEST['settheme']])) {
    $settheme = preg_replace('/[^\w_-]+/', '', strip_tags($_REQUEST['settheme']));
    $GLOBALS['ui'] = $_REQUEST['settheme'];
    $_SESSION['ui'] = $GLOBALS['ui'];
    SetCookie ( 'preferredTheme', $_SESSION['ui'],time()+31536000);
} elseif (!empty($_COOKIE['preferredTheme']) && isset($THEMES[$_COOKIE['preferredTheme']])) {
    $GLOBALS['ui'] = $_COOKIE['preferredTheme'];
    $_SESSION['ui'] = $GLOBALS['ui'];
}
if (isset($GLOBALS['ui']) && !is_array($THEMES[$GLOBALS['ui']])) {
    $themeKeys = array_keys($THEMES);
    $GLOBALS['ui'] = $themeKeys[0];
}
if (isset($_SESSION['ui']) && is_array($THEMES[$_SESSION['ui']])) {
    $GLOBALS['ui'] = $_SESSION['ui'];
} else {
    $_SESSION['ui'] = $GLOBALS['ui'];
}
$THEMEINFO = $THEMES[$_SESSION['ui']];

if (!empty($_GET['page']) && $_GET['page'] == 'logout' && empty($_GET['err'])) {
    foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
        $plugin->logout();
    }

    $_SESSION['adminloggedin'] = '';
    $_SESSION['logindetails'] = '';
    session_destroy();
    Redirect('home');
}

//# send a header for IE
header('X-UA-Compatible: IE=Edge');
//# tell SE's to leave us alone
header('X-Robots-Tag: noindex');

if (!$ajax && !$GLOBALS['commandline']) {
    if (USE_MINIFIED_ASSETS && file_exists(dirname(__FILE__).'/ui/'.$GLOBALS['ui'].'/pagetop_minified.php')) {
        include_once dirname(__FILE__).'/ui/'.$GLOBALS['ui'].'/pagetop_minified.php';
    } else {
        include_once dirname(__FILE__).'/ui/'.$GLOBALS['ui'].'/pagetop.php';
    }
}

if (isset($GLOBALS['pageheader'])) {
    foreach ($GLOBALS['pageheader'] as $sHeaderItem => $sHtml) {
        echo '<!--'.$sHeaderItem.'-->'.$sHtml;

        echo "\n";
    }
}

$GLOBALS['require_login'] = 1; ## this is no longer configurable and should never have been
if ($GLOBALS['commandline']) {
    cl_output(ClineSignature());
    if (!isset($_SERVER['USER']) && is_array($GLOBALS['commandline_users']) && count($GLOBALS['commandline_users'])) {
        clineError('USER environment variable is not defined, cannot do access check. Please make sure USER is defined.');
        exit;
    }
    if (is_array($GLOBALS['commandline_users']) && count($GLOBALS['commandline_users']) && !in_array($_SERVER['USER'],
            $GLOBALS['commandline_users'])
    ) {
        clineError('Sorry, You ('.$_SERVER['USER'].') do not have sufficient permissions to run phplist on commandline');
        exit;
    }
    $GLOBALS['require_login'] = 0;

    // getopt is actually useless
    //$opt = getopt("p:");

    $IsCommandlinePlugin = isset($cline['m']) && in_array($cline['m'], $GLOBALS['commandlinePlugins']);
    if ($cline['p'] && !$IsCommandlinePlugin) {
        if (empty($GLOBALS['developer_email']) && isset($cline['p']) && !in_array($cline['p'],
                $GLOBALS['commandline_pages'])
        ) {
            clineError($cline['p'].' does not process commandline');
        } elseif (isset($cline['p'])) {
            $_GET['page'] = $cline['p'];
        }
        cl_processtitle('core-'.$_GET['page']);
    } elseif ($cline['p'] && $IsCommandlinePlugin) {
        if (empty($GLOBALS['developer_email']) && isset($cline['p']) && !in_array($cline['p'],
                $commandlinePluginPages[$cline['m']])
        ) {
            clineError($cline['p'].' does not process commandline');
        } elseif (isset($cline['p'])) {
            $_GET['page'] = $cline['p'];
            $_GET['pi'] = $cline['m'];
            cl_processtitle($_GET['pi'].'-'.$_GET['page']);
        }
    } else {
        clineUsage(' [other parameters]');
        cl_output(s('Available options:'));
        @ob_end_clean();
        foreach ($GLOBALS['commandline_pages'] as $page){
            echo '     '.$page.PHP_EOL;
        }
        foreach ($GLOBALS['commandlinePluginPages'] as $plugin => $pluginPages){
            foreach ($pluginPages as $page) {
                echo '     ' . $page .' -m'.$plugin. PHP_EOL;
            }
        }
        exit;
    }
} else {
    if (CHECK_REFERRER && isset($_SERVER['HTTP_REFERER'])) {
        //# do a crude check on referrer. Won't solve everything, as it can be faked, but shouldn't hurt
        $ref = parse_url($_SERVER['HTTP_REFERER']);
        $parts = explode(':', $_SERVER['HTTP_HOST']);
        if ($ref['host'] != $parts[0] && !in_array($ref['host'], $allowed_referrers)) {
            echo 'Access denied <script type="text/javascript">document.location = document.location</script>';
            exit;
        }
    }
}

if (!isset($_GET['page'])) {
    $page = $GLOBALS['homepage'];
} else {
    $page = $_GET['page'];
}

if (preg_match("/([\w_]+)/", $page, $regs)) {
    $page = $regs[1];
} else {
    $page = '';
}
if (!is_file($page.'.php') && !isset($_GET['pi'])) {
    $page = $GLOBALS['homepage'];
}

if (!$GLOBALS['admin_auth_module']) {
    // stop login system when no admins exist
    if (!Sql_Table_Exists($tables['admin'])) {
        $GLOBALS['require_login'] = 0;
    } else {
        $num = Sql_Query("select * from {$tables['admin']}");
        if (!Sql_Affected_Rows()) {
            $msg = s('Login not available. Create an account first.');
        }
    }
} elseif (!Sql_Table_exists($GLOBALS['tables']['config'])) {
    $GLOBALS['require_login'] = 0;
}

$plugin = !empty($GLOBALS['plugins'][$_GET['pi']]) && isset($GLOBALS['plugins'][$_GET['pi']])
    ? $GLOBALS['plugins'][$_GET['pi']]
    : null;
if ($plugin) {
    $page_title = $plugin->pageTitle($page);
} else {
    $page_title = $GLOBALS['I18N']->pageTitle($page);
}

echo '<title>'.NAME.' :: ';
if (isset($GLOBALS['installation_name'])) {
    echo $GLOBALS['installation_name'].' :: ';
}
echo "$page_title</title>";
$inRemoteCall = false;
$doLoginCheck = Sql_Table_exists($tables['admin_login']);

if (!empty($GLOBALS['require_login'])) {
    //bth 7.1.2015 to support x-forwarded-for
    $remoteAddr = getClientIP();

    if ($GLOBALS['authenticationplugin']) {
        $GLOBALS['admin_auth'] = $GLOBALS['plugins'][$GLOBALS['authenticationplugin']];
    } else {
        require __DIR__.'/phpListAdminAuthentication.php';
        $GLOBALS['admin_auth'] = new phpListAdminAuthentication();
    }
    if ((!isset($_SESSION['adminloggedin']) || !$_SESSION['adminloggedin']) && isset($_REQUEST['login']) && isset($_REQUEST['password'])) {
        $loginresult = $GLOBALS['admin_auth']->validateLogin($_REQUEST['login'], $_REQUEST['password']);
        if (!$loginresult[0]) {
            $_SESSION['adminloggedin'] = '';
            $_SESSION['logindetails'] = '';
            $page = 'login';
            logEvent(sprintf($GLOBALS['I18N']->get('invalid login from %s, tried logging in as %s'), $remoteAddr,
                $_REQUEST['login']));
            $msg = $loginresult[1];
        } else {
            session_regenerate_id();
            if ($doLoginCheck) {
              # invalidate other active sessions
              Sql_Query(sprintf('update %s set active = 0 where adminid = %d and active != 0',$GLOBALS['tables']['admin_login'],$loginresult[0]));
            }

            $_SESSION['adminloggedin'] = $remoteAddr;
            $_SESSION['logindetails'] = array(
                'adminname' => $_REQUEST['login'],
                'id'        => $loginresult[0],
                'superuser' => $admin_auth->isSuperUser($loginresult[0]),
                'passhash'  => sha1($_REQUEST['password']),
            );
            //#16692 - make sure admin permissions apply at first login
            $GLOBALS['admin_auth']->validateAccount($_SESSION['logindetails']['id']);
            unset($_SESSION['session_age']);
            if (!empty($_POST['page'])) {
                $page = preg_replace('/\W+/', '', $_POST['page']);
            }

            if ($doLoginCheck) {
              # check if this is a new IP address
              $knownIP = Sql_Fetch_Row_Query(sprintf('select * from %s where remote_ip4 = "%s" and adminid = %d ',$GLOBALS['tables']['admin_login'],$remoteAddr,$loginresult[0]));
              if (empty($knownIP[0])) {
                notifyNewIPLogin($loginresult[0]);
              }
              Sql_Query(sprintf('insert into %s (moment,adminid,remote_ip4,remote_ip6,sessionid,active) 
                values(%d,%d,"%s","%s","%s",1)',
                $GLOBALS['tables']['admin_login'],time(),$loginresult[0],$remoteAddr,"",session_id()));
            }
        }
        //If passwords are encrypted and a password recovery request was made, send mail to the admin of the given email address.
    } elseif (isset($_REQUEST['forgotpassword'])) {
        $adminId = $GLOBALS['admin_auth']->adminIdForEmail($_REQUEST['forgotpassword']);
        if ($adminId) {
            $msg = sendAdminPasswordToken($adminId);
        } else {
            $msg = $GLOBALS['I18N']->get('Failed sending a change password token');
        }
        $page = 'login';
    } elseif (!empty($_GET['secret'])
        && in_array($_GET['page'], $plugin === null ? array('processbounces', 'processqueue', 'processcron') : $plugin->remotePages)) {
        //# remote processing call
        $ourSecret = getConfig('remote_processing_secret');
        if ($ourSecret != $_GET['secret']) {
            @ob_end_clean();
            echo 'Error'.': '.s('Incorrect processing secret');
            exit;
        }

        $_SESSION['adminloggedin'] = $remoteAddr;
        $_SESSION['logindetails'] = array(
            'adminname' => 'remotecall',
            'id'        => 0,
            'superuser' => 0,
            'passhash'  => 'xxxx',
        );
        $inRemoteCall = true;
    } elseif (!isset($_SESSION['adminloggedin']) || !$_SESSION['adminloggedin']) {
        //$msg = 'Not logged in';
        $logged = false;
        foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
            if ($pluginname == 'simplesaml' && !isset($_GET['sso'])) {
                continue;
            }
            if ($plugin->login()) {
                $logged = true;
                break;
            }
        }
        if (!$logged) {
            $page = 'login';
        }
    } elseif (CHECK_SESSIONIP && $_SESSION['adminloggedin'] && $_SESSION['adminloggedin'] != $remoteAddr) {
        logEvent(sprintf($GLOBALS['I18N']->get('login ip invalid from %s for %s (was %s)'), $remoteAddr,
            $_SESSION['logindetails']['adminname'], $_SESSION['adminloggedin']));
        $msg = $GLOBALS['I18N']->get('Your IP address has changed. For security reasons, please login again');
        $_SESSION['adminloggedin'] = '';
        $_SESSION['logindetails'] = '';
        $page = 'login';
    } elseif ($_SESSION['adminloggedin'] && $_SESSION['logindetails']) {
        if ($doLoginCheck) {
          $active = Sql_Fetch_Row_Query(sprintf('select active from %s where adminid = %d and (remote_ip4 = "%s" or remote_ip6 = "%s") and sessionid = "%s"',
            $GLOBALS['tables']['admin_login'],$_SESSION['logindetails']['id'],$remoteAddr,"",session_id()));
        } else {
          $active = array(1); ## pretend to be active
        }
        $validate = $GLOBALS['admin_auth']->validateAccount($_SESSION['logindetails']['id']);
        if (empty($active[0]) || !$validate[0]) {
            logEvent(sprintf($GLOBALS['I18N']->get('invalidated login from %s for %s (error %s)'), $remoteAddr,
                $_SESSION['logindetails']['adminname'], $validate[1]));
            $_SESSION['adminloggedin'] = '';
            $_SESSION['logindetails'] = '';
            $page = 'login';
            if (empty($active[0])) {
              $msg = s('Your session was invalidated by a new session in a different browser');
            } else {
              $msg = $validate[1];
            }
        }
    } else {
        $page = 'login';
    }
}
if ($page == 'login') {
    unset($_GET['pi']);
}

if (!empty($_SESSION['adminloggedin']) && !empty($_SESSION['session_age']) && $_SESSION['session_age'] > SESSION_TIMEOUT) {
    $_SESSION['adminloggedin'] = '';
    $_SESSION['logindetails'] = '';
    $page = 'login';
    $msg = s('Your session timed out, please log in again');
}

//#  force to login page, if an Ajax call is made without being logged in
if ($ajax && empty($_SESSION['adminloggedin'])) {
    $_SESSION['action_result'] = s('Your session timed out, please login again');
    echo '<script type="text/javascript">top.location = "./?page=home";</script>';
    exit;
}

## add a few menu options when the admin is superuser
if (isSuperUser() && ALLOW_UPDATER) {
    $GLOBALS['pagecategories']['system']['pages'][] = 'update';
    $GLOBALS['pagecategories']['system']['menulinks'][] = 'update';
}
if (isSuperUser()) {
  foreach (array('admins','admin','importadmin','adminattributes') as $adminPage) {
    $GLOBALS['pagecategories']['config']['menulinks'][] = $adminPage;
    $GLOBALS['pagecategories']['config']['pages'][] = $adminPage;
  }
}

$languageswitcher = '';
if (LANGUAGE_SWITCH && empty($logoutontop) && !$ajax && empty($_SESSION['firstinstall']) && empty($_GET['firstinstall'])) {
    $languageswitcher = '
 <div id="languageswitcher">
       <form name="languageswitchform" method="post" action="">';
    $languageswitcher .= '
           <select name="setlanguage" onchange="document.languageswitchform.submit()">';
    $lancount = 0;
    foreach ($GLOBALS['LANGUAGES'] as $iso => $rec) {
        //  if (is_dir(dirname(__FILE__).'/locale/'.$iso)) {
        $languageswitcher .= sprintf('
                 <option value="%s" %s>%s</option>', $iso,
            $_SESSION['adminlanguage']['iso'] == $iso ? 'selected="selected"' : '', $rec[0]);
        ++$lancount;
        //  }
    }
    $languageswitcher .= '
            </select>
       </form>
 </div>';
    if ($lancount <= 1) {
        $languageswitcher = '';
    }
}

$themeswitcher = '';
if (THEME_SWITCH && empty($logoutontop) && !$ajax && empty($_SESSION['firstinstall']) && empty($_GET['firstinstall'])) {
    $themeswitcher = '
 <div id="themeswitcher">
       <form name="themeswitchform" method="post" action="">';
    $themeswitcher .= '
           <select name="settheme" onchange="document.themeswitchform.submit()">';
    $themecount = 0;
    foreach ($GLOBALS['THEMES'] as $theme => $themeData) {
        $themeswitcher .= sprintf('
                 <option value="%s" %s>%s</option>', $theme,
            $_SESSION['ui'] == $theme ? 'selected="selected"' : '', htmlspecialchars(strip_tags($themeData['name'])));
        ++$themecount;
    }
    $themeswitcher .= '
            </select>
       </form>
 </div>';
    if ($themecount <= 1) {
        $themeswitcher = '';
    }
}

require_once dirname(__FILE__).'/setpermissions.php';
$include = '';

if ($page != '' && $page != 'install') {
    preg_match("/([\w_]+)/", $page, $regs);
    $include = $regs[1];
    $include .= '.php';
    $include = $page.'.php';
} else {
    $include = $GLOBALS['homepage'].'.php';
}
$pageinfo = new pageInfo();
$pageinfo->fetchInfoContent($include);

if (is_file('ui/'.$GLOBALS['ui'].'/mainmenu.php')) {
    include 'ui/'.$GLOBALS['ui'].'/mainmenu.php';
}
if (!$ajax) {
    if (USE_MINIFIED_ASSETS && file_exists(dirname(__FILE__).'/ui/'.$GLOBALS['ui'].'/header_minified.inc')) {
        include 'ui/'.$GLOBALS['ui'].'/header_minified.inc';
    } else {
        include 'ui/'.$GLOBALS['ui'].'/header.inc';
    }
}

if (!$ajax) {
    echo '<h4 class="pagetitle">'.$page_title.'</h4>';
}
echo '<div class="hidden">'.PageLink2('home', s('Main page')).'</div>';

if ($page != 'login') {
    if ($page == 'logout') {
        $greeting = $GLOBALS['I18N']->get('goodbye');
    } else {
        $hr = date('G');
        if ($hr > 0 && $hr < 12) {
            $greeting = $GLOBALS['I18N']->get('good morning');
        } elseif ($hr <= 18) {
            $greeting = $GLOBALS['I18N']->get('good afternoon');
        } else {
            $greeting = $GLOBALS['I18N']->get('good evening');
        }
    }

    if ($page != 'logout' && empty($logoutontop) && !$ajax) {
        //  print '<div class="right">'.PageLink2("logout",$GLOBALS['I18N']->get('logout')).'</div>';
        if (!empty($_SESSION['firstinstall']) && $page != 'setup') {
            $firstInstallButton = '<div id="firstinstallbutton">'.PageLinkClass('setup', s('Continue Configuration'),
                    '', 'firstinstallbutton').'</div>';
        }
    }
}

if (!$GLOBALS['commandline']) {
    echo '<noscript>';
    Info(s('phpList will work without Javascript, but it will be easier to use if you switch it on.'));
    echo '</noscript>';
}

if (!$ajax && $page != 'login') {
    if (strpos(VERSION, 'dev') && !TEST) {
        if (!empty($GLOBALS['developer_email'])) {
            Info( s('Running DEV version. All emails will be sent to '.$GLOBALS['developer_email']) );
        } else {
            Info( s('Running DEV version, but developer email is not set') );
        }
    }
    if (TEST) {
        echo Info($GLOBALS['I18N']->get('Running in testmode, no emails will be sent. Check your config file.'));
    }

    if (ALLOW_UPDATER || $updaterplugin) {
        $updaterdir = __DIR__ . '/../updater';

        include 'updateLib.php';
        $updateNotif = checkForUpdate();

        if ($updateNotif !== '' && $_GET['page'] !== 'update') {
            $moreInfo = ' <ul><li><a href="https://www.phplist.com/download?utm_source=pl' . VERSION . '&amp;utm_medium=updatedownload&amp;utm_campaign=phpList" title="' . s('Download the new version') . '" target="_blank">' . s('Download the new version') . '</a></li>';

            if ($updaterplugin) {
                $class = get_class($updaterplugin);
                $moreInfo .= '<li>'.s('or use the %sphpList Updater%s', sprintf('<a href="?page=update&amp;pi=%s" title="', $class) . s('automatic updater') . '">','</a>');
            } elseif (file_exists($updaterdir)) {
                $moreInfo .= '<li>'.s('or use the %sphpList Updater%s','<a href="?page=update" title="' . s('automatic updater') . '">','</a>');
            }
            $moreInfo .= '</ul>';
            Info($updateNotif . '' . $moreInfo);
        }
    }

    if (version_compare(PHP_VERSION, '5.3.3', '<') && WARN_ABOUT_PHP_SETTINGS) {
        Error(s('Your PHP version is out of date. phpList requires PHP version 5.3.3 or higher.'));
    }
    if (defined('RELEASEDATE') && ((time() - RELEASEDATE) / 31536000) > 2) {
        Fatal_Error(s('Your phpList version is older than two years. Please %supgrade phpList</a> before continuing.</br>
            Visit <a href="https://www.phplist.org/users/" title="'.s('Get some help').'">the support site</a> if you need some help.'
            ,'<a href="https://www.phplist.com/download?utm_source=pl'.VERSION.'&amp;utm_medium=outdated-download-forced&amp;utm_campaign=phpList" title="'.s('Download the latest version').'" target="_blank">'));
        return;
    }

    if (defined('ENABLE_RSS') && ENABLE_RSS && !function_exists('xml_parse') && WARN_ABOUT_PHP_SETTINGS) {
        Warn($GLOBALS['I18N']->get('You are trying to use RSS, but XML is not included in your PHP'));
    }

    if (ALLOW_ATTACHMENTS && WARN_ABOUT_PHP_SETTINGS && (!is_dir($GLOBALS['attachment_repository']) || !is_writable($GLOBALS['attachment_repository']))) {
        if (ini_get('open_basedir')) {
            Warn($GLOBALS['I18N']->get('open_basedir restrictions are in effect, which may be the cause of the next warning'));
        }
        Warn($GLOBALS['I18N']->get('The attachment repository does not exist or is not writable'));
    }

    if (MANUALLY_PROCESS_QUEUE && isSuperUser() && empty($_GET['pi']) &&
        //# hmm, how many more pages to not show this?
        (!isset($_GET['page']) ||
            ($_GET['page'] != 'processqueue' && $_GET['page'] != 'messages' && $_GET['page'] != 'upgrade'))
    ) {
        //# avoid error on uninitialised DB
        if (Sql_Table_exists($tables['message'])) {
            $queued_count = Sql_Fetch_Row_Query(sprintf('select count(id) from %s where status in ("submitted","inprocess") and embargo < now()',
                $tables['message']));
            if ($queued_count[0]) {
                $link = PageLinkButton('processqueue', s('Process the queue'));
                $link2 = PageLinkButton('messages&amp;tab=active', s('View the queue'));
                if ($link || $link2) {
                    echo Info(sprintf(s('You have %s campaign(s) in the queue, ready for processing'),
                            $queued_count[0]).'<br/>'.$link.' '.$link2);
                }
            }
        }
    }
}

// always allow access to the about page
if (isset($_GET['page']) && $_GET['page'] == 'about') {
    $page = 'about';
    $include = 'about.php';
}
echo $pageinfo->show();

if (!empty($_GET['action']) && $_GET['page'] != 'pageaction' && !empty($_SESSION['adminloggedin'])) {
    $action = basename($_GET['action']);
    if (is_file(dirname(__FILE__).'/actions/'.$action.'.php')) {
        $status = '';
        //# the page action return the result in $status
        include dirname(__FILE__).'/actions/'.$action.'.php';
        echo '<div id="actionresult">'.$status.'</div>';
    }
}

/*
 *
 * show global news, based on the version in use
 *
 * **/

//if (empty($_SESSION['newsshown'])) { ## keep flag to only show one message per session
if (!empty($_SESSION['logindetails']['id']) && defined('PHPLISTNEWSROOT') && PHPLISTNEWSROOT) {
    //# for testing
    if (!empty($_GET['reset']) && $_GET['reset'] == 'news') {
        SaveConfig('readnews'.$_SESSION['logindetails']['id'], '', 0, 1);
        SaveConfig('viewednews'.$_SESSION['logindetails']['id'], '', 0, 1);
        SaveConfig('phpListNewsLastChecked-'.$_SESSION['adminlanguage']['iso'], '', 0, 1);
        SaveConfig('phpListNewsIndex-'.$_SESSION['adminlanguage']['iso'], '', 0, 1);
        clearPageCache();
    }

    $readmessagesconf = getConfig('readnews'.$_SESSION['logindetails']['id']);
    $readmessages = unserialize($readmessagesconf);
    if (!is_array($readmessages)) {
        $readmessages = array();
    }

    /* also keep track of when a message is viewed and suppress it
      if it hasn't been closed after several views */
    $viewedmessagesconf = getConfig('viewednews'.$_SESSION['logindetails']['id']);
    $viewedmessages = unserialize($viewedmessagesconf);
    if (!is_array($viewedmessages)) {
        $viewedmessages = array();
    }

    $news = array();

    // we only need it once per language per system, regardless of admins
    $phpListNewsLastChecked = getConfig('phpListNewsLastChecked-'.$_SESSION['adminlanguage']['iso']);
    if (empty($phpListNewsLastChecked) || ($phpListNewsLastChecked + 86400 < time())) {
        SaveConfig('phpListNewsLastChecked-'.$_SESSION['adminlanguage']['iso'], time(), 0, 1);
        $newsIndex = fetchUrlDirect(PHPLISTNEWSROOT.'/'.VERSION.'-'.$_SESSION['adminlanguage']['iso'].'-index.txt');
        SaveConfig('phpListNewsIndex-'.$_SESSION['adminlanguage']['iso'], $newsIndex, 0, 1);
    }
    $newsIndex = getConfig('phpListNewsIndex-'.$_SESSION['adminlanguage']['iso']);

    if (!empty($newsIndex)) {
        $newsitems = explode("\n", $newsIndex);
        foreach ($newsitems as $newsitem) {
            $newsitem = trim($newsitem);
            if (!empty($newsitem) && !in_array(md5($newsitem), $readmessages) &&
                (
                    empty($viewedmessages[md5($newsitem)]['count']) ||
                    $viewedmessages[md5($newsitem)]['count'] < 20)
            ) {
                $newscontent = fetchUrl(PHPLISTNEWSROOT.'/'.$newsitem);
                if (!empty($newscontent)) {
                    $news[$newsitem] = $newscontent;
                }
            }
        }

        ksort($news);
        $newscontent = '';
        foreach ($news as $newsitem => $newscontent) {
            $newsid = md5($newsitem);
            if (!isset($viewedmessages[$newsid])) {
                $viewedmessages[$newsid] = array(
                    'time'  => time(),
                    'count' => 1,
                );
            } else {
                ++$viewedmessages[$newsid]['count'];
            }
            SaveConfig('viewednews'.$_SESSION['logindetails']['id'], serialize($viewedmessages), 0, 1);
            $newscontent = '<div class="news"><a href="./?page=markread&id='.$newsid.'" class="ajaxable hide" title="'.s('Hide forever').'">'.s('Hide forever').'</a>'.$newscontent.'</div>';
            break;
        }
    }
    if (!empty($newscontent)) {
        $_SESSION['newsshown'] = time();
        echo '<div class="panel announcements closethisone">';
        echo '<div class="content">';
        echo $newscontent;
        echo '</div>';
        echo '</div>';
    }
}
//} // end of show one per session (not used)

/*
 *
 * end of news
 *
 * **/

if (WARN_ABOUT_PHP_SETTINGS && !$GLOBALS['commandline']) {
    if (strpos(getenv('REQUEST_URI'), $pageroot.'/admin') !== 0) {
        Warn(s(
            'The pageroot in your config "%s" does not match the current location "%s". Check your config file.',
            $pageroot,
            strstr(getenv('REQUEST_URI'), '/admin', true)
        ));
    }
}
clearstatcache();
if (empty($_GET['pi']) && (is_file($include) || is_link($include))) {

    if (checkAccess($page) || $page == 'about') {
        // check whether there is a language file to include
        if (is_file('lan/'.$_SESSION['adminlanguage']['iso'].'/'.$include)) {
            include 'lan/'.$_SESSION['adminlanguage']['iso'].'/'.$include;
        }
        if (is_file('ui/'.$GLOBALS['ui'].'/pages/'.$include)) {
            $include = 'ui/'.$GLOBALS['ui'].'/pages/'.$include;
        }
        //  print "Including $include<br/>";

        // hmm, pre-parsing and capturing the error would be nice
        //$parses_ok = eval(@file_get_contents($include));
        $parses_ok = 1;

        if (!$parses_ok) {
            echo Error("cannot parse $include");
            echo '<p class="error">Sorry, an error occurred. This is a bug. Please <a href="https://github.com/phpList/phplist3/issues">report the bug to the Bug Tracker</a><br/>Sorry for the inconvenience</a></p>';
        } else {
            if (!empty($_SESSION['action_result'])) {
                echo '<div class="actionresult">'.$_SESSION['action_result'].'</div>';
//        print '<script>alert("'.$_SESSION['action_result'].'")</script>';
                unset($_SESSION['action_result']);
            }

            if ($GLOBALS['commandline'] || !empty($_GET['secret'])) {
                @ob_end_clean();
                @ob_start();
            }
            $mm = inMaintenanceMode();
            if (empty($mm) || $GLOBALS['commandline'] || $page == 'login' || $page == 'about' || $page == 'community') {
                if (isset($GLOBALS['developer_email'])) {
                    include $include;
                } else {
                    @include $include;
                }
            } else {
                print '<h1>'.s('phpList is in maintenance mode.<br/>Please try again in half an hour.'). '<h1>';
            }
        }
    } else {
        Error(s('Access Denied'));
    }
//  print "End of inclusion<br/>";
} elseif ($plugin !== null && isset($GLOBALS['plugins']) && is_array($GLOBALS['plugins']) && is_object($GLOBALS['plugins'][$_GET['pi']])) {

    $menu = $plugin->adminmenu();

    if (checkAccess($page, $_GET['pi'])) {
        if (is_file($plugin->coderoot.$include)) {
            include $plugin->coderoot.$include;
        } elseif ($include == 'main.php' || $page == 'home') {
            echo '<h3>'.$plugin->name.'</h3><ul>';
            foreach ($menu as $page => $desc) {
                echo '<li>'.PageLink2($page, $desc).'</li>';
            }
            echo '</ul>';
        } elseif ($page != 'login') {
            echo '<br/>'."$page -&gt; ".s('Sorry this page was not found in the plugin').'<br/>'; //.' '.$plugin->coderoot.$include.'<br/>';
            cl_output("$page -> ".s('Sorry this page was not found in the plugin')); //. ' '.$plugin->coderoot . "$include");
        }
    } else {
        Error(s('Access Denied'));
    }
} else {
    if ($GLOBALS['commandline']) {
        clineError(s('Sorry, that module does not exist'));
        exit;
    }
    if (is_file('ui/'.$GLOBALS['ui'].'/pages/'.$include)) {
        include 'ui/'.$GLOBALS['ui'].'/pages/'.$include;
    } else {
        echo "$page -&gt; ".$GLOBALS['I18N']->get('Sorry, not implemented yet');
    }
}

// some debugging stuff
$now = gettimeofday();
$finished = $now['sec'] * 1000000 + $now['usec'];
$elapsed = $finished - $GLOBALS['pagestats']['time_start'];
$elapsed = ($elapsed / 1000000);

echo "\n\n".'<!--';
if (!empty($GLOBALS['developer_email'])) {
    echo '<br clear="all" />';
    echo $GLOBALS['pagestats']['number_of_queries']." db queries in $elapsed seconds";
    if (function_exists('memory_get_peak_usage')) {
        $memory_usage = 'Peak: '.memory_get_peak_usage();
    } elseif (function_exists('memory_get_usage')) {
        $memory_usage = memory_get_usage();
    } else {
        $memory_usage = 'Cannot determine with this PHP version';
    }
    echo '<br/>Memory usage: '.$memory_usage;
}

if (isset($GLOBALS['statslog']) && !empty($_SERVER['REQUEST_URI'])) {
    if ($fp = @fopen($GLOBALS['statslog'], 'a')) {
        @fwrite($fp,
            $GLOBALS['pagestats']['number_of_queries']."\t$elapsed\t".$_SERVER['REQUEST_URI']."\t NAME:".$GLOBALS['installation_name']."\n");
    }
}
echo '-->';

if (!empty($GLOBALS['inRemoteCall']) || $ajax || !empty($GLOBALS['commandline'])) {
    @ob_end_clean();
    exit;
} elseif (!isset($_GET['omitall'])) {
    if (!$GLOBALS['compression_used']) {
        @ob_end_flush();
    }

    if (USE_MINIFIED_ASSETS && file_exists(dirname(__FILE__).'/ui/'.$GLOBALS['ui'].'/footer_minified.inc')) {
        include_once 'ui/'.$GLOBALS['ui'].'/footer_minified.inc';
    } else {
        include_once 'ui/'.$GLOBALS['ui'].'/footer.inc';
    }
}
if (isset($GLOBALS['pagefooter'])) {
    foreach ($GLOBALS['pagefooter'] as $sFooterItem => $sHtml) {
        echo '<!--'.$sFooterItem.'-->'.$sHtml;

        echo "\n";
    }
}
echo '</body></html>';

function parseCline()
{
    $res = array();
    $cur = '';
    foreach ($GLOBALS['argv'] as $clinearg) {
        if (substr($clinearg, 0, 1) == '-') {
            $par = substr($clinearg, 1, 1);
            $clinearg = substr($clinearg, 2, strlen($clinearg));
            // $res[$par] = "";
            $cur = mb_strtolower($par);
            if (!isset($res[$cur])) {
                $res[$cur] = '';
            }
            $res[$cur] .= $clinearg;
        } elseif ($cur) {
            if ($res[$cur]) {
                $res[$cur] .= ' '.$clinearg;
            } else {
                $res[$cur] .= $clinearg;
            }
        }
    }
    /*  ob_end_clean();
      foreach ($res as $key => $val) {
        print "$key = $val\n";
      }
      ob_start();*/
    return $res;
}
