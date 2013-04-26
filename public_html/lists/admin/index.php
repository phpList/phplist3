<?php

@ob_start();
$er = error_reporting(0);
# check for commandline and cli version
if (!isset($_SERVER["SERVER_NAME"]) && !PHP_SAPI == "cli") {
  print "Warning: commandline only works well with the cli version of PHP";
}

if (isset($_REQUEST['_SERVER'])) { exit; }
$cline = array();
$GLOBALS['commandline'] = 0;

require_once dirname(__FILE__) .'/commonlib/lib/unregister_globals.php';
require_once dirname(__FILE__) .'/commonlib/lib/magic_quotes.php';

# setup commandline
if (php_sapi_name() == "cli") {
  for ($i=0; $i<$_SERVER['argc']; $i++) {
    $my_args = array();
    if (preg_match("/(.*)=(.*)/",$_SERVER['argv'][$i], $my_args)) {
      $_GET[$my_args[1]] = $my_args[2];
      $_REQUEST[$my_args[1]] = $my_args[2];
    }
  }
  $GLOBALS["commandline"] = 1;
  $cline = parseCLine();
  $dir = dirname($_SERVER["SCRIPT_FILENAME"]);
  chdir($dir);
  
  if (!is_file($cline['c'])) {
    print "Cannot find config file\n";
    exit;
  }
  
} else {
  $GLOBALS["commandline"] = 0;
  header("Cache-Control: no-cache, must-revalidate");           // HTTP/1.1
  header("Pragma: no-cache");                                   // HTTP/1.0
}

$configfile = '';

if (isset($_SERVER["ConfigFile"]) && is_file($_SERVER["ConfigFile"])) {
  #print '<!-- using (server)'.$_SERVER["ConfigFile"].'-->'."\n";
   $configfile = $_SERVER["ConfigFile"];
} elseif (isset($cline["c"]) && is_file($cline["c"])) {
  #print '<!-- using (cline)'.$cline["c"].' -->'."\n";
  $configfile = $cline["c"];
# obsolete, set Config in Linux environment, use -c /path/to/config instead
/*} elseif (isset($_ENV["CONFIG"]) && is_file($_ENV["CONFIG"]) && filesize($_ENV["CONFIG"]) > 1) {
#  print '<!-- using '.$_ENV["CONFIG"].'-->'."\n";
  include $_ENV["CONFIG"];*/
} elseif (is_file(dirname(__FILE__).'/../config/config.php')) {
#  print '<!-- using (common)../config/config.php -->'."\n";
   $configfile = "../config/config.php";
} else {
  $configfile = "../config/config.php";
}

if (is_file($configfile) && filesize($configfile) > 20) {
#  print '<!-- using config '.$configfile.'-->';
  include $configfile;
} elseif ($GLOBALS["commandline"]) {
  print 'Cannot find config file'."\n";
} else {
  $GLOBALS['installer'] = 1;
  include(dirname(__FILE__).'/install.php');
  exit;
}
$ajax = isset($_GET['ajaxed']);

if (!isset($database_host) || !isset($database_user) || !isset($database_password) || !isset($database_name)) {
 # print $GLOBALS['I18N']->get('Database details incomplete, please check your config file');
  print 'Database details incomplete, please check your config file';
  exit;
}
#exit;
# record the start time(usec) of script
$now =  gettimeofday();
$GLOBALS["pagestats"] = array();
$GLOBALS["pagestats"]["time_start"] = $now["sec"] * 1000000 + $now["usec"];
$GLOBALS["pagestats"]["number_of_queries"] = 0;

if (!$GLOBALS["commandline"] && isset($GLOBALS["developer_email"]) && $_SERVER['HTTP_HOST'] != 'dev.phplist.com' && !empty($GLOBALS['show_dev_errors'])) {
#  error_reporting(E_ALL & ~E_NOTICE);
  ## in developer mode, show all errors and force "registered globals off"
  error_reporting(E_ALL);
  ini_set('display_errors',1);
  foreach ($_REQUEST as $key => $val) {
    unset($$key);
  }
} else {
#  error_reporting($er);
  error_reporting(0);
}

# load all required files
require_once dirname(__FILE__).'/init.php';
require_once dirname(__FILE__).'/'.$GLOBALS["database_module"];
include_once dirname(__FILE__)."/../texts/english.inc";
include_once dirname(__FILE__)."/../texts/".$GLOBALS["language_module"];
include_once dirname(__FILE__)."/languages.php";
require_once dirname(__FILE__)."/defaultconfig.php";

require_once dirname(__FILE__).'/connect.php';
include_once dirname(__FILE__)."/lib.php";
if (INTERFACELIB == 2 && is_file(dirname(__FILE__).'/interfacelib.php')) {
  require_once dirname(__FILE__)."/interfacelib.php";
} else {
  require_once dirname(__FILE__)."/commonlib/lib/interfacelib.php";
}

if (!empty($_SESSION['hasconf']) || Sql_Table_exists($tables["config"],1)) {
  $_SESSION['hasconf'] = true;
  ### Activate all plugins
  foreach ($GLOBALS['plugins'] as $plugin) {
    $plugin->activate();
  }
}
if (!empty($_GET['page']) && $_GET['page'] == 'logout') {
  foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
    $plugin->logout();
  }

  $_SESSION["adminloggedin"] = "";
  $_SESSION["logindetails"] = "";
  session_destroy();
  Redirect('home');
}

## send a header for IE
header('X-UA-Compatible: IE=Edge');
## tell SE's to leave us alone
header('X-Robots-Tag: noindex');

if (!$ajax && !$GLOBALS["commandline"]) {
  include_once dirname(__FILE__).'/ui/'.$GLOBALS['ui'].'/pagetop.php';
}

if (isset($GLOBALS['pageheader'])) {
  foreach ($GLOBALS['pageheader'] as $sHeaderItem => $sHtml ) {
    print '<!--'.$sHeaderItem.'-->'.$sHtml;
    print "\n";
  }
} 

if ($GLOBALS["commandline"]) {
  if (!isset($_SERVER["USER"]) && sizeof($GLOBALS["commandline_users"])) {
    clineError("USER environment variable is not defined, cannot do access check. Please make sure USER is defined.");
    exit;
  }
  if (is_array($GLOBALS["commandline_users"]) && sizeof($GLOBALS["commandline_users"]) && !in_array($_SERVER["USER"],$GLOBALS["commandline_users"])) {
    clineError("Sorry, You (".$_SERVER["USER"].") do not have sufficient permissions to run phplist on commandline");
    exit;
  }
  $GLOBALS["require_login"] = 0;

  # getopt is actually useless
  #$opt = getopt("p:");

  $IsCommandlinePlugin = isset($cline['m']) && in_array($cline['m'],$GLOBALS["commandlinePlugins"]);
  if ($cline['p'] && !$IsCommandlinePlugin) {
    if (empty($GLOBALS['developer_email']) && isset($cline['p']) && !in_array($cline['p'],$GLOBALS["commandline_pages"])) {
      clineError($cline['p']." does not process commandline");
    } elseif (isset($cline['p'])) {
      $_GET['page'] = $cline['p'];
    }
  } elseif ($cline['p'] && $IsCommandlinePlugin) {
    if (empty($GLOBALS['developer_email']) && isset($cline['p']) && !in_array($cline['p'],$commandlinePluginPages[$cline['m']])) {
      clineError($cline['p']." does not process commandline");
    } elseif (isset($cline['p'])) {
      $_GET['page'] = $cline['p'];
      $_GET['pi'] = $cline['m'];
    }
  } else {
    clineUsage(" [other parameters]");
    exit;
  }
} else {
  if (CHECK_REFERRER && isset($_SERVER['HTTP_REFERER'])) {
    ## do a crude check on referrer. Won't solve everything, as it can be faked, but shouldn't hurt
    $ref = parse_url($_SERVER['HTTP_REFERER']);
    if ($ref['host'] != $_SERVER['HTTP_HOST'] && !in_array($ref['host'],$allowed_referrers)) {
      print 'Access denied';exit;
    }
  }
}

/*
# fix for old PHP versions, although not failsafe :-(
if (!isset($_POST) && isset($HTTP_POST_VARS)) {
  include_once dirname(__FILE__) ."/commonlib/lib/oldphp_vars.php";
}
*/

if (!isset($_GET['page'])) {
  $page = $GLOBALS['homepage'];
} else {
  $page = $_GET['page'];
}

if (preg_match("/([\w_]+)/",$page,$regs)) {
  $page = $regs[1];
} else {
  $page = '';
}
if (!is_file($page.'.php') && !isset($_GET['pi'])) {
  $page = $GLOBALS['homepage'];
}

if (!$GLOBALS["admin_auth_module"]) {
  # stop login system when no admins exist
  if (!Sql_Table_Exists($tables["admin"])) {
    $GLOBALS["require_login"] = 0;
  } else {
    $num = Sql_Query("select * from {$tables["admin"]}");
    if (!Sql_Affected_Rows())
      $GLOBALS["require_login"] = 0;
  }
} elseif (!Sql_Table_exists($GLOBALS['tables']['config'])) {
  $GLOBALS['require_login'] = 0;
}

if (!empty($_GET['pi']) && isset($GLOBALS['plugins'][$_GET['pi']])) {
  $page_title = $GLOBALS['plugins'][$_GET['pi']]->pageTitle($page);
} else {
  $page_title = $GLOBALS['I18N']->pageTitle($page);
}

print "<title>".NAME." :: ";
if (isset($GLOBALS["installation_name"])) {
  print $GLOBALS["installation_name"] .' :: ';
}
print "$page_title</title>";

if (isset($GLOBALS["require_login"]) && $GLOBALS["require_login"]) {
  if ($GLOBALS["admin_auth_module"] && is_file("auth/".$GLOBALS["admin_auth_module"])) {
    require_once "auth/".$GLOBALS["admin_auth_module"];
  } elseif ($GLOBALS["admin_auth_module"] && is_file($GLOBALS["admin_auth_module"])) {
    require_once $GLOBALS["admin_auth_module"];
  } else {
    if ($GLOBALS["admin_auth_module"]) {
      logEvent("Warning: unable to use ".$GLOBALS["admin_auth_module"]. " for admin authentication, reverting back to phplist authentication");
      $GLOBALS["admin_auth_module"] = 'phplist_auth.inc';
    }
    require_once 'auth/phplist_auth.inc';
  }
  if (class_exists('admin_auth')) {
    $GLOBALS["admin_auth"] = new admin_auth();
  } else {
    print Fatal_Error($GLOBALS['I18N']->get('Admin Authentication initialisation failure'));
    return;
  }
  if ((!isset($_SESSION["adminloggedin"]) || !$_SESSION["adminloggedin"]) && isset($_REQUEST["login"]) && isset($_REQUEST["password"])) {
    $loginresult = $GLOBALS["admin_auth"]->validateLogin($_REQUEST["login"],$_REQUEST["password"]);
    if (!$loginresult[0]) {
      $_SESSION["adminloggedin"] = "";
      $_SESSION["logindetails"] = "";
      $page = "login";
      logEvent(sprintf($GLOBALS['I18N']->get('invalid login from %s, tried logging in as %s'),$_SERVER['REMOTE_ADDR'],$_REQUEST["login"]));
      $msg = $loginresult[1];
    } else {
      $_SESSION["adminloggedin"] = $_SERVER["REMOTE_ADDR"];
      $_SESSION["logindetails"] = array(
        "adminname" => $_REQUEST["login"],
        "id" => $loginresult[0],
        "superuser" => $admin_auth->isSuperUser($loginresult[0]),
        "passhash" => sha1($_REQUEST["password"]),
      );
      ##16692 - make sure admin permissions apply at first login
      $GLOBALS["admin_auth"]->validateAccount($_SESSION["logindetails"]["id"]);      
      if (!empty($_POST["page"])) {
        $page = preg_replace('/\W+/','',$_POST["page"]);
      }
    }
  #If passwords are encrypted and a password recovery request was made, send mail to the admin of the given email address.
  } elseif (ENCRYPT_ADMIN_PASSWORDS && isset($_REQUEST["forgotpassword"])){
  	  $adminId = $GLOBALS["admin_auth"]->adminIdForEmail($_REQUEST['forgotpassword']);
      if($adminId){
      	$msg = sendAdminPasswordToken($adminId);
      } else {
      	$msg = $GLOBALS['I18N']->get('cannotsendpassword');
      }
      $page = "login";
  } elseif (isset($_REQUEST["forgotpassword"])) {
    $pass = '';
    if (is_email($_REQUEST["forgotpassword"])) {
      $pass = $GLOBALS["admin_auth"]->getPassword($_REQUEST["forgotpassword"]);
    } 
    if ($pass) {
      sendMail ($_REQUEST["forgotpassword"],$GLOBALS['I18N']->get('Your password for phplist'),"\n\n".$GLOBALS['I18N']->get('Your password is')." $pass");
      $msg = $GLOBALS['I18N']->get('Your password has been sent by email');
      logEvent(sprintf($GLOBALS['I18N']->get('successful password request from %s for %s'),$_SERVER['REMOTE_ADDR'],$_REQUEST["forgotpassword"]));
    } else {
      $msg = $GLOBALS['I18N']->get('Unable to send the password');
      logEvent(sprintf($GLOBALS['I18N']->get('failed password request from %s for %s'),$_SERVER['REMOTE_ADDR'],$_REQUEST["forgotpassword"]));
    }
    $page = "login";
  } elseif (!isset($_SESSION["adminloggedin"]) || !$_SESSION["adminloggedin"]) {
    #$msg = 'Not logged in';
    $page = "login";
  } elseif (CHECK_SESSIONIP && $_SESSION["adminloggedin"] && $_SESSION["adminloggedin"] != $_SERVER["REMOTE_ADDR"]) {
    logEvent(sprintf($GLOBALS['I18N']->get('login ip invalid from %s for %s (was %s)'),$_SERVER['REMOTE_ADDR'],$_SESSION["logindetails"]['adminname'],$_SESSION["adminloggedin"]));
    $msg = $GLOBALS['I18N']->get('Your IP address has changed. For security reasons, please login again');
    $_SESSION["adminloggedin"] = "";
    $_SESSION["logindetails"] = "";
    $page = "login";
  } elseif ($_SESSION["adminloggedin"] && $_SESSION["logindetails"]) {
    $validate = $GLOBALS["admin_auth"]->validateAccount($_SESSION["logindetails"]["id"]);
    if (!$validate[0]) {
      logEvent(sprintf($GLOBALS['I18N']->get('invalidated login from %s for %s (error %s)'),$_SERVER['REMOTE_ADDR'],$_SESSION["logindetails"]['adminname'],$validate[1]));
      $_SESSION["adminloggedin"] = "";
      $_SESSION["logindetails"] = "";
      $page = "login";
      $msg = $validate[1];
    }
  } else {
    $page = "login";
  }
}
if ($page == 'login') {
  $_GET['pi'] = '';
}

if (LANGUAGE_SWITCH && empty($logoutontop) && !$ajax) {
    $languageswitcher = '
 <div id="languageswitcher">
       <form name="languageswitchform" method="post" action="">';
    $languageswitcher .= '
           <select name="setlanguage" onchange="document.languageswitchform.submit()">';
    $lancount = 0;
    foreach ($GLOBALS['LANGUAGES'] as $iso => $rec) {
    #  if (is_dir(dirname(__FILE__).'/locale/'.$iso)) {
        $languageswitcher .= sprintf('
                 <option value="%s" %s>%s</option>',$iso,$_SESSION['adminlanguage']['iso'] == $iso ? 'selected="selected"':'',$rec[0]);
        $lancount++;
    #  }
    }
    $languageswitcher .= '
            </select>
       </form>
 </div>';
    if ($lancount <= 1) {
      $languageswitcher = '';
    }
}

require_once dirname(__FILE__).'/setpermissions.php';
$include = '';

if ($page != '' && $page != 'install') {
  preg_match("/([\w_]+)/",$page,$regs);
  $include = $regs[1];
  $include .= ".php";
  $include = $page . ".php";
} else {
  $include = $GLOBALS['homepage'].".php";
}
$pageinfo = new pageInfo();
$pageinfo->fetchInfoContent($include);

if (is_file('ui/'.$GLOBALS['ui']."/mainmenu.php")) {
  include 'ui/'.$GLOBALS['ui']."/mainmenu.php";
}  
if (!$ajax) {
  include 'ui/'.$GLOBALS['ui']."/header.inc";
} 

if (!$ajax) {
  print '<h4 class="pagetitle">'.strtolower($page_title).'</h4>';
}

if ($GLOBALS["require_login"] && $page != "login") {
  if ($page == 'logout') {
    $greeting = $GLOBALS['I18N']->get('goodbye');
  } else {
    $hr = date("G");
    if ($hr > 0 && $hr < 12) {
      $greeting = $GLOBALS['I18N']->get('good morning');
    } elseif ($hr <= 18) {
      $greeting = $GLOBALS['I18N']->get('good afternoon');
    } else {
      $greeting = $GLOBALS['I18N']->get('good evening');
    }
  }

  if ($page != "logout" && empty($logoutontop) && !$ajax) {
  #  print '<div class="right">'.PageLink2("logout",$GLOBALS['I18N']->get('logout')).'</div>';
    if (!empty($_SESSION['firstinstall']) && $page != 'setup') {
      print '<div class="info right">'.PageLinkClass("setup",$GLOBALS['I18N']->get('Continue Configuration'),'','firstinstallbutton').'</div>';
    }
  }
}

if (!$GLOBALS["commandline"]) {
  print '<noscript>';
   Info(s('phpList will work without Javascript, but it will be easier to use if you switch it on.'));
  print '</noscript>';
}

if (!$ajax && $page != "login") {
  if (strpos(VERSION,"dev") && !TEST) {#
    if ($GLOBALS["developer_email"]) {
      Info("Running DEV version. All emails will be sent to ".$GLOBALS["developer_email"]);
    } else {
      Info("Running DEV version, but developer email is not set");
    }
  }
  if (TEST) {
    print Info($GLOBALS['I18N']->get('Running in testmode, no emails will be sent. Check your config file.'));
  }
  if (version_compare(PHP_VERSION, '5.1.2', '<') && WARN_ABOUT_PHP_SETTINGS) {
    Error($GLOBALS['I18N']->get('phpList requires PHP version 5.1.2 or higher'));
  }
  if (defined("ENABLE_RSS") && ENABLE_RSS && !function_exists("xml_parse") && WARN_ABOUT_PHP_SETTINGS)
    Warn($GLOBALS['I18N']->get('You are trying to use RSS, but XML is not included in your PHP'));

  if (ALLOW_ATTACHMENTS && WARN_ABOUT_PHP_SETTINGS && (!is_dir($GLOBALS["attachment_repository"]) || !is_writable ($GLOBALS["attachment_repository"]))) {
    if (ini_get("open_basedir")) {
      Warn($GLOBALS['I18N']->get('open_basedir restrictions are in effect, which may be the cause of the next warning'));
    }
    Warn($GLOBALS['I18N']->get('The attachment repository does not exist or is not writable'));
  }

  if (MANUALLY_PROCESS_QUEUE && empty($_GET['pi']) &&
    ## hmm, how many more pages to not show this?
    (!isset($_GET['page']) || 
    ($_GET['page'] != 'processqueue' && $_GET['page'] != 'messages' && $_GET['page'] != 'upgrade'))) {
      ## avoid error on uninitialised DB
      if (Sql_Table_exists($tables['message'])) {
        $queued_count = Sql_Fetch_Row_Query(sprintf('select count(id) from %s where status in ("submitted","inprocess") and embargo < now()',$tables['message']));
        if ($queued_count[0]) {
          $link = PageLinkButton('processqueue',s('Process the queue'));
          $link2 = PageLinkButton('messages&amp;tab=active',s('View the queue'));
          if ($link || $link2) {
            print Info(sprintf(s('You have %s message(s) waiting to be sent'),$queued_count[0]).'<br/>'.$link.' '.$link2);
          }
        }
    }
  }
  
}

# always allow access to the about page
if (isset($_GET['page']) && $_GET['page'] == 'about') {
  $page = 'about';
  $include = 'about.php';
}
print $pageinfo->show();

if (!empty($_GET['action']) && $_GET['page'] != 'pageaction') {
  $action = basename($_GET['action']);
  if (is_file(dirname(__FILE__).'/actions/'.$action.'.php')) {
    $status = '';
    ## the page action return the result in $status
    include dirname(__FILE__).'/actions/'.$action.'.php';
    print '<div id="actionresult">'.$status.'</div>';
  }
}

/*
if (USEFCK) {
  $imgdir = getenv("DOCUMENT_ROOT").$GLOBALS["pageroot"].'/'.FCKIMAGES_DIR.'/';
  if (!is_dir($imgdir) || !is_writeable ($imgdir)) {
    Warn("The FCK image directory does not exist, or is not writable");
  }
}
*/

if (!empty($_COOKIE['browsetrail'])) {
  if (!isset($_SESSION['browsetrail']) || !is_array($_SESSION['browsetrail'])) {
    $_SESSION['browsetrail'] = array();
  }
  if (!in_array($_COOKIE['browsetrail'],$_SESSION['browsetrail'])) {
    $_SESSION['browsetrail'][] = $_COOKIE['browsetrail'];
  }
}

if (defined("USE_PDF") && USE_PDF && !defined('FPDF_VERSION')) {
  Warn($GLOBALS['I18N']->get('You are trying to use PDF support without having FPDF loaded'));
}

$this_doc = getenv("REQUEST_URI");
if (preg_match("#(.*?)/admin?$#i",$this_doc,$regs)) {
  $check_pageroot = $pageroot;
  $check_pageroot = preg_replace('#/$#','',$check_pageroot);
  if ($check_pageroot != $regs[1] && WARN_ABOUT_PHP_SETTINGS)
    Warn($GLOBALS['I18N']->get('The pageroot in your config does not match the current locationCheck your config file.'));
}

clearstatcache();
if (checkAccess($page,"") || $page == 'about') {
  if (!$_GET['pi'] && (is_file($include) || is_link($include))) {
    # check whether there is a language file to include
    if (is_file("lan/".$_SESSION['adminlanguage']['iso']."/".$include)) {
      include "lan/".$_SESSION['adminlanguage']['iso']."/".$include;
    }
    if (is_file('ui/'.$GLOBALS['ui'].'/pages/'.$include)) {
      $include = 'ui/'.$GLOBALS['ui'].'/pages/'.$include;
    }
  #  print "Including $include<br/>";

    # hmm, pre-parsing and capturing the error would be nice
    #$parses_ok = eval(@file_get_contents($include));
    $parses_ok = 1;

    if (!$parses_ok) {
      print Error("cannot parse $include");
      print '<p class="error">Sorry, an error occurred. This is a bug. Please <a href="http://mantis.phplist.com">report the bug to the Bug Tracker</a><br/>Sorry for the inconvenience</a></p>';
    } else {
      if (!empty($_SESSION['action_result'])) {
        print '<div class="actionresult">'.$_SESSION['action_result'].'</div>';
#        print '<script>alert("'.$_SESSION['action_result'].'")</script>';
        unset($_SESSION['action_result']);
      }

      if ($GLOBALS['commandline']) {
        @ob_end_clean();
        @ob_start();
      }
      if (isset($GLOBALS['developer_email'])) {
        include $include;
      } else {
        @include $include;
      }
    }
  #  print "End of inclusion<br/>";
  } elseif (!empty($_GET['pi']) && isset($GLOBALS['plugins']) && is_array($GLOBALS['plugins']) && isset($GLOBALS['plugins'][$_GET['pi']]) && is_object($GLOBALS['plugins'][$_GET['pi']])) {
    $plugin = $GLOBALS["plugins"][$_GET["pi"]];
    $menu = $plugin->adminmenu(); 
    if (is_file($plugin->coderoot . $include)) {
      include ($plugin->coderoot . $include);
    } elseif ($include == 'main.php' || $_GET['page'] == 'home') {
      print '<h3>'.$plugin->name.'</h3><ul>';
      foreach ($menu as $page => $desc) {
        print '<li>'.PageLink2($page,$desc).'</li>';
      }
      print '</ul>';
    } elseif ($page != 'login') {
      print '<br/>'."$page -&gt; ".$I18N->get('Sorry this page was not found in the plugin').'<br/>';#.' '.$plugin->coderoot.$include.'<br/>';
      #print $plugin->coderoot . "$include";
    }
  } else {
    if ($GLOBALS["commandline"]) {
      clineError("Sorry, that module does not exist");
      exit;
    }
    if (is_file('ui/'.$GLOBALS['ui'].'/pages/'.$include)) {
      include ('ui/'.$GLOBALS['ui'].'/pages/'.$include);
    } else {
      print "$page -&gt; ".$GLOBALS['I18N']->get('Sorry, not implemented yet');
    }
  }
} else {
  Error($GLOBALS['I18N']->get('Access Denied'));
}

# some debugging stuff
$now =  gettimeofday();
$finished = $now["sec"] * 1000000 + $now["usec"];
$elapsed = $finished - $GLOBALS["pagestats"]["time_start"];
$elapsed = ($elapsed / 1000000);

  print "\n\n".'<!--';
if (!empty($GLOBALS['developer_email'])) {
  print '<br clear="all" />';
  print $GLOBALS["pagestats"]["number_of_queries"]." db queries in $elapsed seconds";
  if (function_exists('memory_get_peak_usage')) {
    $memory_usage = 'Peak: ' .memory_get_peak_usage();
  } elseif (function_exists("memory_get_usage")) {
    $memory_usage = memory_get_usage();
  } else {
    $memory_usage = 'Cannot determine with this PHP version';
  }
  print '<br/>Memory usage: '.$memory_usage;
}  

if (isset($GLOBALS["statslog"])) {
  if ($fp = @fopen($GLOBALS["statslog"],"a")) {
    @fwrite($fp,$GLOBALS["pagestats"]["number_of_queries"]."\t$elapsed\t".$_SERVER['REQUEST_URI']."\t".$GLOBALS['installation_name']."\n");
  }
}
  print '-->';

if ($ajax || (isset($GLOBALS["commandline"]) && $GLOBALS["commandline"])) {
  @ob_clean();
  exit;
} elseif (!isset($_GET["omitall"])) {
  if (!$GLOBALS['compression_used']) {
    @ob_end_flush();
  }
  include_once 'ui/'.$GLOBALS['ui']."/footer.inc";
}

function parseCline() {
  $res = array();
  $cur = "";
  foreach ($GLOBALS["argv"] as $clinearg) {
    if (substr($clinearg,0,1) == "-") {
      $par = substr($clinearg,1,1);
      $clinearg = substr($clinearg,2,strlen($clinearg));
     # $res[$par] = "";
      $cur = strtolower($par);
      $res[$cur] .= $clinearg;
     } elseif ($cur) {
      if ($res[$cur])
        $res[$cur] .= ' '.$clinearg;
      else
        $res[$cur] .= $clinearg;
    }
  }
/*  ob_end_clean();
  foreach ($res as $key => $val) {
    print "$key = $val\n";
  }
  ob_start();*/
  return $res;
}

