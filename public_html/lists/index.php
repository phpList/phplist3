<?php

ob_start();
//$er = error_reporting(0);
require_once dirname(__FILE__).'/admin/inc/unregister_globals.php';
require_once dirname(__FILE__).'/admin/inc/magic_quotes.php';

//# none of our parameters can contain html for now
$_GET = removeXss($_GET);
$_POST = removeXss($_POST);
$_REQUEST = removeXss($_REQUEST);
$_SERVER = removeXss($_SERVER);
$_COOKIE = removeXss($_COOKIE);

//# remove a trailing punctuation mark on the uid
if (isset($_GET['uid'])) {
    if (preg_match('/[\.,:;]$/', $_GET['uid'])) {
        $_GET['uid'] = preg_replace('/[\.,:;]$/', '', $_GET['uid']);
    }
}

if (isset($_SERVER['ConfigFile']) && is_file($_SERVER['ConfigFile'])) {
    include $_SERVER['ConfigFile'];
} elseif (is_file('config/config.php')) {
    include 'config/config.php';
} else {
    echo "Error, cannot find config file\n";
    exit;
}

require_once dirname(__FILE__).'/admin/init.php';

$GLOBALS['database_module'] = basename($GLOBALS['database_module']);
$GLOBALS['language_module'] = basename($GLOBALS['language_module']);

require_once dirname(__FILE__).'/admin/'.$GLOBALS['database_module'];

// load default english and language
include_once dirname(__FILE__).'/admin/defaultFrontendTexts.php';
if (is_file(dirname(__FILE__).'/texts/'.$GLOBALS['language_module'])) {
    include_once dirname(__FILE__).'/texts/'.$GLOBALS['language_module'];
}
// Allow customisation per installation
if (is_file($_SERVER['DOCUMENT_ROOT'].'/'.$GLOBALS['language_module'])) {
    include_once $_SERVER['DOCUMENT_ROOT'].'/'.$GLOBALS['language_module'];
}

require_once dirname(__FILE__).'/admin/inc/random_compat/random.php';
require_once dirname(__FILE__).'/admin/inc/UUID.php';
include_once dirname(__FILE__).'/admin/languages.php';
require_once dirname(__FILE__).'/admin/defaultconfig.php';
require_once dirname(__FILE__).'/admin/connect.php';
include_once dirname(__FILE__).'/admin/lib.php';

$I18N = new phplist_I18N();
header('Access-Control-Allow-Origin: '.ACCESS_CONTROL_ALLOW_ORIGIN);

if (!empty($GLOBALS['SessionTableName'])) {
    require_once dirname(__FILE__).'/admin/sessionlib.php';
}
@session_start(); // it may have been started already in languages

if (!isset($_POST) && isset($HTTP_POST_VARS)) {
    require 'admin/commonlib/lib/oldphp_vars.php';
}

if (isset($_GET['id'])) {
    $id = sprintf('%d', $_GET['id']);
} else {
    $id = 0;
}

// What is id - id of subscribe page
// What is uid - uid of subscriber
// What is userid - userid of subscriber

$userid = '';
$userpassword = '';
$emailcheck = '';

if (isset($_GET['uid']) && $_GET['uid']) {
    $req = Sql_Fetch_Row_Query(sprintf('select subscribepage,id,password,email from %s where uniqid = "%s"',
        $tables['user'], $_GET['uid']));
    $id = $req[0];
    $userid = $req[1];
    $userpassword = $req[2];
    $emailcheck = $req[3];
} else {
    $userid = '';
    $userpassword = '';
    $emailcheck = '';
}

if (isset($_REQUEST['id']) && $_REQUEST['id']) {
    $id = sprintf('%d', $_REQUEST['id']);
}
// make sure the subscribe page still exists
$req = Sql_fetch_row_query(sprintf('select id from %s where id = %d', $tables['subscribepage'], $id));
$id = $req[0];
$msg = '';

if (!empty($_POST['sendpersonallocation'])) {
    if (isset($_POST['email']) && $_POST['email']) {
        $uid = Sql_Fetch_Assoc_Query(sprintf('select uniqid,email,id,blacklisted from %s where email = "%s"',
            $tables['user'], sql_escape($_POST['email'])));
        if ($uid['blacklisted']) {
            $msg .= $GLOBALS['strYouAreBlacklisted'];
        } elseif ($uid['uniqid']) {
            sendMail($uid['email'], getConfig('personallocation_subject'),
                getUserConfig('personallocation_message', $uid['id']), system_messageheaders(), $GLOBALS['envelope']);
            $msg = $GLOBALS['strPersonalLocationSent'];
            addSubscriberStatistics('personal location sent', 1);
        } else {
            $msg = $GLOBALS['strUserNotFound'];
        }
    }
}

if (isset($_GET['p']) && $_GET['p'] == 'subscribe') {
    $_SESSION['userloggedin'] = 0;
    $_SESSION['userdata'] = array();
}

$login_required =
    (ASKFORPASSWORD && $userpassword && $_GET['p'] == 'preferences') ||
    (ASKFORPASSWORD && UNSUBSCRIBE_REQUIRES_PASSWORD && $userpassword && $_GET['p'] == 'unsubscribe');

if ($login_required && empty($_SESSION['userloggedin'])) {
    $canlogin = 0;
    if (!empty($_POST['login'])) {
        // login button pushed, let's check formdata

        if (empty($_POST['email'])) {
            $msg = $strEnterEmail;
        } elseif (empty($_POST['password'])) {
            $msg = $strEnterPassword;
        } else {
            if (ENCRYPTPASSWORD) {
                $encP = encryptPass($_POST['password']);
                $canlogin = false;
                $canlogin =
                    !empty($encP) &&
                    !empty($_POST['password']) &&
                    !empty($emailcheck) &&
                    $encP == $userpassword && $_POST['email'] == $emailcheck;
                //      print $_POST['password'].' '.$encP.' '.$userpassword.' '.$canlogin; exit;
            } else {
                $canlogin = $_POST['password'] === $userpassword && $_POST['email'] === $emailcheck;
            }
        }

        if (!$canlogin) {
            $msg = '<p class="error">'.$strInvalidPassword.'</p>';
        } else {
            loadUser($emailcheck);
            $_SESSION['userloggedin'] = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (!empty($_POST['forgotpassword'])) {
        // forgot password button pushed
        if (!empty($_POST['email']) && $_POST['email'] == $emailcheck) {
            sendMail($emailcheck, $GLOBALS['strPasswordRemindSubject'],
                $GLOBALS['strPasswordRemindMessage'].' '.$userpassword, system_messageheaders());
            $msg = $GLOBALS['strPasswordSent'];
        } else {
            $msg = $strPasswordRemindInfo;
        }
    } elseif (isset($_SESSION['userdata']['email']['value']) && $_SESSION['userdata']['email']['value'] == $emailcheck) {
        // Entry without any button pushed (first time) test and, if needed, ask for password
        $canlogin = $_SESSION['userloggedin'];
        $msg = $strEnterPassword;
    }
} else {
    // Logged into session or login not required
    $canlogin = 1;
}

if (!$id) {
    // find the default one:
    $id = getConfig('defaultsubscribepage');
    // fix the true/false issue
    if ($id == 'true') {
        $id = 1;
    }
    if ($id == 'false') {
        $id = 0;
    }
    if (!$id) {
        // pick a first
        $req = Sql_Fetch_row_Query(sprintf('select ID from %s where active', $tables['subscribepage']));
        $id = $req[0];
    }
}

$pagedata = array();
if ($id) {
    $GLOBALS['pagedata'] = PageData($id);
    if (isset($pagedata['language_file']) && is_file(dirname(__FILE__).'/texts/'.basename($pagedata['language_file']))) {
        @include dirname(__FILE__).'/texts/'.basename($pagedata['language_file']);
        // Allow customisation per installation
        if (is_file($_SERVER['DOCUMENT_ROOT'].'/'.basename($pagedata['language_file']))) {
            include_once $_SERVER['DOCUMENT_ROOT'].'/'.basename($pagedata['language_file']);
        }
    }
}

/*
  We request you retain the inclusion of pagetop below. This will add invisible
  additional information to your public pages.
  This not only gives respect to the large amount of time given freely
  by the developers  but also helps build interest, traffic and use of
  phpList, which is beneficial to it's future development.

  Michiel Dethmers, phpList Ltd 2000-2017
*/
include 'admin/ui/'.$GLOBALS['ui'].'/publicpagetop.php';

if ($login_required && empty($_SESSION['userloggedin']) && !$canlogin) {
    echo LoginPage($id, $userid, $emailcheck, $msg);
} elseif (!empty($_GET['pi']) && isset($plugins[$_GET['pi']])) {
    $plugin = $plugins[$_GET['pi']];

    if (!empty($_GET['p']) && in_array($_GET['p'], $plugin->publicPages)) {
        $page = $_GET['p'];

        if (is_file($include = $plugin->coderoot.$page.'.php')) {
            require $include;
        } else {
            FileNotFound();
        }
    } else {
        FileNotFound();
    }
} elseif (isset($_GET['p']) && preg_match("/(\w+)/", $_GET['p'], $regs)) {
    if ($id) {
        switch ($_GET['p']) {
            case 'subscribe':
                $success = require 'admin/subscribelib2.php';
                if ($success != 2) {
                    echo SubscribePage($id);
                }
                break;
            case 'asubscribe': //# subscribe with Ajax
                $_POST['subscribe'] = 1;
                if (isset($_GET['email']) && !isset($_POST['email'])) {
                    $_POST['email'] = $_GET['email'];
                }
                foreach (explode(',', $GLOBALS['pagedata']['lists']) as $listid) {
                    $_POST['list'][$listid] = 'signup';
                }
                $_POST['htmlemail'] = 1; //# @@ should actually be taken from the subscribe page data

                $success = require 'admin/subscribelib2.php';
                $result = ob_get_contents();
                ob_end_clean();
                if (stripos($result, $GLOBALS['strEmailConfirmation']) !== false ||
                    stripos($result, $pagedata['thankyoupage']) !== false
                ) {
                    if (!empty($pagedata['ajax_subscribeconfirmation'])) {
                        $confirmation = $pagedata['ajax_subscribeconfirmation'];
                    } else {
                        $confirmation = getConfig('ajax_subscribeconfirmation');
                    }
                    if (empty($confirmation)) {
                        echo 'OK';
                    } else {
                        echo $confirmation;
                    }
                    exit;
                } else {
                    // we failed to subscribe the user; send an error back to
                    // the ajax client

                    echo 'FAIL';

                    // thow an exception so the http status code is a 500
                    // Internal Server Error, easily caught by jquery.ajax()
                    throw new Exception( "Error: Subscribe attempt failed!" );

                }
                break;
            case 'preferences':
                if (!isset($_GET['id']) || !$_GET['id']) {
                    $_GET['id'] = $id;
                }

                if (!$userid) {
                    //          print "Userid not set".$_SESSION["userid"];
                    echo sendPersonalLocationPage($id);
                    break;
                }

                if (ASKFORPASSWORD && $userpassword && !$canlogin) {
                    echo LoginPage($id, $userid, $emailcheck);
                    break;
                }
                $success = require 'admin/subscribelib2.php';

                if ($success != 3) {
                    echo PreferencesPage($id, $userid);
                }
                break;
            case 'forward':
                print ForwardPage($id);
                break;
            case 'confirm':
                print ConfirmPage($id);
                break;
            case 'vcard':
                print downloadvCard();
                break;
            //0013076: Blacklisting posibility for unknown users
            case 'donotsend':
            case 'blacklist':
            case 'unsubscribe':
                print UnsubscribePage($id);
                break;
            default:
                FileNotFound();
        }
    } else {
        FileNotFound();
    }
} else {
    // If no particular page was requested then show the default
    echo '<title>'.$GLOBALS['strSubscribeTitle'].'</title>';
    echo $pagedata['header'];
    $req = Sql_Query(sprintf('select * from %s where active', $tables['subscribepage']));

    // If active subscribe pages exist then list them
    if (Sql_Affected_Rows()) {
        while ($row = Sql_Fetch_Array($req)) {
            $intro = Sql_Fetch_Row_Query(sprintf('select data from %s where id = %d and name = "intro"',
                $tables['subscribepage_data'], $row['id']));
            echo stripslashes($intro[0]);
            if (SHOW_SUBSCRIBELINK) {
                printf('<p><a href="'.getConfig('subscribeurl').'&id=%d">%s</a></p>', $row['id'],
                    strip_tags(stripslashes($row['title'])));
            }
        }
    // If no active subscribe page exist then print link to default
    } else {
        if (SHOW_SUBSCRIBELINK) {
            printf('<p><a href="'.getConfig('subscribeurl').'">%s</a></p>', $strSubscribeTitle);
        }
    }

    // Print preferences page link
    if (SHOW_PREFERENCESLINK) {
        printf('<p><a href="'.getConfig('preferencesurl').'">%s</a></p>', $strPreferencesTitle);
    }

    // Print unsubscribe page link
    if (SHOW_UNSUBSCRIBELINK) {
        printf('<p><a href="'.getConfig('unsubscribeurl').'">%s</a></p>', $strUnsubscribeTitle);
    }
    // Print link to contact admin using HTML entities for email obfuscation
    echo
        '<p class=""><a href="'.
            preg_replace_callback('/./', function($m) {
                return '&#'.ord($m[0]).';';
            }
            , 'mailto:'.getConfig('admin_address')).
        '">'.$GLOBALS['strContactAdmin'].'</a></p>';
    echo $PoweredBy;
    echo $pagedata['footer'];
}

function LoginPage($id, $userid, $email = '', $msg = '')
{
    list($attributes, $attributedata) = PageAttributes($GLOBALS['pagedata']);
    $html = '<title>'.$GLOBALS['strLoginTitle'].'</title>';
    $html .= $GLOBALS['pagedata']['header'];
    $html .= '<h3>'.$GLOBALS['strLoginInfo'].'</h3>';
    $html .= $msg;
    if (isset($_REQUEST['email'])) {
        $email = $_REQUEST['email'];
    }
    if (!isset($_POST['password'])) {
        $_POST['password'] = '';
    }

    $html .= formStart('name="loginform"');
    $html .= '<table border=0>';
    $html .= '<tr><td>'.$GLOBALS['strEmail'].'</td><td><input type=text name="email" value="'.$email.'" size="30" autofocus></td></tr>';
    $html .= '<tr><td>'.$GLOBALS['strPassword'].'</td><td><input type="password" name="password" value="'.$_POST['password'].'" size="30"></td></tr>';
    $html .= '</table>';
    $html .= '<p><input type=submit name="login" value="'.$GLOBALS['strLogin'].'"></p>';
    if (ENCRYPTPASSWORD) {
        $forgotPassBody = $GLOBALS['strForgotPasswordEmailBody'];
        $forgotPassBody = str_replace("\n", '%0D%0A', $forgotPassBody);

        $html .= sprintf('<a href="mailto:%s?subject=%s&body=%s
    ">%s</a>', getConfig('admin_address'), $GLOBALS['strForgotPassword'], $forgotPassBody,
            $GLOBALS['strForgotPassword']);
    } else {
        $html .= '<input type=submit name="forgotpassword" value="'.$GLOBALS['strForgotPassword'].'">';
    }
    $html .= '<br/><br/>';
    if (SHOW_UNSUBSCRIBELINK) {
        $html .= '<p><a href="'.getConfig('unsubscribeurl').'&id='.$id.'">'.$GLOBALS['strUnsubscribe'].'</a></p>';
    }
    $html .= '</form>'.$GLOBALS['PoweredBy'];
    $html .= $GLOBALS['pagedata']['footer'];

    return $html;
}

function sendPersonalLocationPage($id)
{
    list($attributes, $attributedata) = PageAttributes($GLOBALS['pagedata']);
    $html = '<title>'.$GLOBALS['strPreferencesTitle'].'</title>';
    $html .= $GLOBALS['pagedata']['header'];
    $html .= '<h3>'.$GLOBALS['strPreferencesTitle'].'</h3>';
    $html .= $GLOBALS['msg'];

    if (isset($_REQUEST['email'])) {
        $email = $_REQUEST['email'];
    } elseif (isset($_SESSION['userdata']['email']['value'])) {
        $email = $_SESSION['userdata']['email']['value'];
    } else {
        $email = '';
    }
    $html .= $GLOBALS['strPersonalLocationInfo'];

    $html .= formStart('name="form"');
    $html .= '<table border=0>';
    $html .= '<tr><td>'.$GLOBALS['strEmail'].'</td><td><input type=text name="email" value="'.$email.'" size="30"></td></tr>';
    $html .= '</table>';
    $html .= '<p><input type=submit name="sendpersonallocation" value="'.$GLOBALS['strContinue'].'"></p>';
    $html .= '<br/><br/>';
    if (SHOW_UNSUBSCRIBELINK) {
        $html .= '<p><a href="'.getConfig('unsubscribeurl').'&id='.$id.'">'.$GLOBALS['strUnsubscribe'].'</a></p>';
    }
    $html .= '</form>'.$GLOBALS['PoweredBy'];
    $html .= $GLOBALS['pagedata']['footer'];

    return $html;
}

function preferencesPage($id, $userid)
{
    list($attributes, $attributedata) = PageAttributes($GLOBALS['pagedata']);
    $selected_lists = explode(',', $GLOBALS['pagedata']['lists']);
    $html = '<title>'.$GLOBALS['strPreferencesTitle'].'</title>';
    $html .= $GLOBALS['pagedata']['header'];
    $html .= '<h3>'.$GLOBALS['strPreferencesInfo'].'</h3>';
    $html .= '

<br/><div class="error"><span class="required">* ' .$GLOBALS['strRequired'].'</span></div><br/>
' .$GLOBALS['msg'].'

<script language="Javascript" type="text/javascript">

var fieldstocheck = new Array();
    fieldnames = new Array();

function checkform()
{
  for (i=0;i<fieldstocheck.length;i++) {
    if (eval("document.subscribeform.elements[\'"+fieldstocheck[i]+"\'].value") == "") {
      alert("' .$GLOBALS['strPleaseEnter'].' "+fieldnames[i]);
      eval("document.subscribeform.elements[\'"+fieldstocheck[i]+"\'].focus()");

      return false;
    }
  }
';
    if ($GLOBALS['pagedata']['emaildoubleentry'] == 'yes') {
        $html .= '
  if (! compareEmail()) {
    alert("' .str_replace('"', '\"', $GLOBALS['strEmailsNoMatch']).'");

    return false;
  }';
    }

    $html .= '
  if (! checkEmail()) {
    alert("' .str_replace('"', '\"', $GLOBALS['strEmailNotValid']).'");

    return false;
  }';

    $html .= '

  return true;
}

function addFieldToCheck(value,name)
{
  fieldstocheck[fieldstocheck.length] = value;
  fieldnames[fieldnames.length] = name;
}

function compareEmail()
{
  return (document.subscribeform.elements["email"].value == document.subscribeform.elements["emailconfirm"].value);
}

function checkEmail()
{
  var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(document.subscribeform.elements["email"].value);
}

</script>';
    $html .= formStart('name="subscribeform"');
    $html .= '<table border=0>';
    $html .= ListAttributes($attributes, $attributedata, $GLOBALS['pagedata']['htmlchoice'], $userid,
        $GLOBALS['pagedata']['emaildoubleentry']);
    $html .= '</table>';

//obsolete, moved to rssmanager plugin
//  if (ENABLE_RSS) {
//    $html .= rssOptions($data,$userid);
//   }
    foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
        if ($plugin->enabled) {
            $html .= $plugin->displaySubscriptionChoice($GLOBALS['pagedata'], $userid);
        }
    }

    $html .= ListAvailableLists($userid, $GLOBALS['pagedata']['lists']);
    if (isBlackListedID($userid)) {
        $html .= $GLOBALS['strYouAreBlacklisted'];
    }

    $html .= '<input type=submit name="update" value="'.$GLOBALS['strUpdatePreferences'].'" onClick="return checkform();">';
    if (SHOW_UNSUBSCRIBELINK) {
        $html .= ' &nbsp;&nbsp; <a href="'.getConfig('unsubscribeurl').'&id='.$id.'">'.$GLOBALS['strUnsubscribe'].'</a>';
    }
    $html.='</form>';
    $html .= $GLOBALS['PoweredBy'];
    $html .= $GLOBALS['pagedata']['footer'];

    return $html;
}

function downloadvCard(){

    require 'admin/vCard.php';
    $vCard = new vCard();
    $vCard-> setOrg(getConfig('organisation_name'));
    $vCard-> setEmail(getConfig('message_from_address'));
    $vCard-> setUrl('http://'.getConfig('website'));
    $vCard->createVCard();
}

function subscribePage($id)
{
    //  return subscribePage2($id);
    list($attributes, $attributedata) = PageAttributes($GLOBALS['pagedata']);
    $selected_lists = explode(',', $GLOBALS['pagedata']['lists']);
    $html = '<title>'.$GLOBALS['strSubscribeTitle'].'</title>';
    $html .= $GLOBALS['pagedata']['header'];
    $html .= $GLOBALS['pagedata']['intro'];
    $html .= '

<div class="error"><span class="required">* ' .$GLOBALS['strRequired'].'</span></div>
' .$GLOBALS['msg'].'

<script language="Javascript" type="text/javascript">

function checkform()
{
  for (i=0;i<fieldstocheck.length;i++) {
    if (eval("document.subscribeform.elements[\'"+fieldstocheck[i]+"\'].type") == "checkbox") {
      if (document.subscribeform.elements[fieldstocheck[i]].checked) {
      } else {
        alert("' .$GLOBALS['strCheckbox'].' "+fieldnames[i]);
        eval("document.subscribeform.elements[\'"+fieldstocheck[i]+"\'].focus()");

        return false;
      }
    } else {
      if (eval("document.subscribeform.elements[\'"+fieldstocheck[i]+"\'].value") == "") {
        alert("' .$GLOBALS['strPleaseEnter'].' "+fieldnames[i]);
        eval("document.subscribeform.elements[\'"+fieldstocheck[i]+"\'].focus()");

        return false;
      }
    }
  }
  for (i=0;i<groupstocheck.length;i++) {
    if (!checkGroup(groupstocheck[i],groupnames[i])) {
      return false;
    }
  }
  ';
    if ($GLOBALS['pagedata']['emaildoubleentry'] == 'yes') {
        $html .= '
  if (! compareEmail()) {
    alert("' .str_replace('"', '\"', $GLOBALS['strEmailsNoMatch']).'");

    return false;
  }';
    }

    $html .= '
  if (! checkEmail()) {
    alert("' .str_replace('"', '\"', $GLOBALS['strEmailNotValid']).'");

    return false;
  }';

    $html .= '

  return true;
}

var fieldstocheck = new Array();
var fieldnames = new Array();
function addFieldToCheck(value,name)
{
  fieldstocheck[fieldstocheck.length] = value;
  fieldnames[fieldnames.length] = name;
}
var groupstocheck = new Array();
var groupnames = new Array();
function addGroupToCheck(value,name)
{
  groupstocheck[groupstocheck.length] = value;
  groupnames[groupnames.length] = name;
}

function compareEmail()
{
  return (document.subscribeform.elements["email"].value == document.subscribeform.elements["emailconfirm"].value);
}

function checkEmail()
{
  var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(document.subscribeform.elements["email"].value);
}

function checkGroup(name,value)
{
  option = -1;
  for (i=0;i<document.subscribeform.elements[name].length;i++) {
    if (document.subscribeform.elements[name][i].checked) {
      option = i;
    }
  }
  if (option == -1) {
    alert ("' .$GLOBALS['strPleaseEnter'].' "+value);

    return false;
  }

  return true;
}

</script>';
    $html .= formStart('name="subscribeform"');
    // @@@ update
    if (isset($_SESSION['adminloggedin']) && $_SESSION['adminloggedin']) {
        $html .= '<div class="adminmessage"><p><b>'.s('You are logged in as administrator (%s) of this phpList system',
                $_SESSION['logindetails']['adminname']).'</b></p>';
        $html .= '<p>'.s('You are therefore offered the following choice, which your subscribers will not see when they load this page.').'</p>';
        $html .= '<p><a href="'.$GLOBALS['adminpages'].'" class="button">'.s('Go back to admin area').'</a></p>';
        $html .= '<p><b>'.s('Please choose').'</b>: <br/><input type=radio name="makeconfirmed" value="1"> '.s('Make this subscriber confirmed immediately').'
      <br/><input type=radio name="makeconfirmed" value="0" checked> ' .s('Send this subscriber a request for confirmation email').' </p></div>';
    }
    $html .= '<table border=0>';
    $html .= ListAttributes($attributes, $attributedata, $GLOBALS['pagedata']['htmlchoice'], 0,
        $GLOBALS['pagedata']['emaildoubleentry']);
    $html .= '</table>';

//obsolete, moved to rssmanager plugin
//  if (ENABLE_RSS) { // replaced bij display
//    $html .= rssOptions($data);
//   }

    foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
        //  dbg($plugin->name);
        if ($plugin->enabled) {
            $html .= $plugin->displaySubscriptionChoice($GLOBALS['pagedata']);
        }
    }
    $html .= ListAvailableLists('', $GLOBALS['pagedata']['lists']);

    if (empty($GLOBALS['pagedata']['button'])) {
        $GLOBALS['pagedata']['button'] = $GLOBALS['strSubmit'];
    }
    if (USE_SPAM_BLOCK) {
        $html .= '<div style="display:none"><input type="text" name="VerificationCodeX" value="" size="20"></div>';
    }
    $html .= '<input type=submit name="subscribe" value="'.$GLOBALS['pagedata']['button'].'" onClick="return checkform();">';
    if (SHOW_UNSUBSCRIBELINK) {
        $html .= ' &nbsp;&nbsp; <a href="'.getConfig('unsubscribeurl').'&id='.$id.'">'.$GLOBALS['strUnsubscribe'].'</a>';
    }
    $html .='</form>';
    $html .= $GLOBALS['PoweredBy'];
    $html .= $GLOBALS['pagedata']['footer'];
    unset($_SESSION['subscriberConfirmed']);

    return $html;
}

function confirmPage($id)
{
    global $tables, $envelope;
    if (!$_GET['uid']) {
        FileNotFound();
    }
    $req = Sql_Query(sprintf('select * from %s where uniqid = "%s"', $tables['user'], sql_escape($_GET['uid'])));
    $userdata = Sql_Fetch_Array($req);
    if ($userdata['id']) {
        $html = '<ul>';
        $lists = '';
        $currently = Sql_Fetch_Assoc_Query("select confirmed from {$tables['user']} where id = ".$userdata['id']);
        $blacklisted = isBlackListed($userdata['email']);
        foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
            $plugin->subscriberConfirmation($id, $userdata);
        }
        Sql_Query("update {$tables['user']} set confirmed = 1,blacklisted = 0, optedin = 1 where id = ".$userdata['id']);
        $subscriptions = array();
        $req = Sql_Query(sprintf('select list.id,name,description from %s list, %s listuser where listuser.userid = %d and listuser.listid = list.id and list.active',
            $tables['list'], $tables['listuser'], $userdata['id']));
        if (!Sql_Affected_Rows()) {
            $lists = "\n * ".$GLOBALS['strNoLists'];
            $html .= '<li>'.$GLOBALS['strNoLists'].'</li>';
        }
        while ($row = Sql_fetch_array($req)) {
            array_push($subscriptions, $row['id']);
            $lists .= "\n *".stripslashes($row['name']);
            $html .= '<li class="list"><b>'.stripslashes($row['name']).'</b><div class="listdescription">'.stripslashes($row['description']).'</div></li>';
        }
        $html .= '</ul>';
        if ($blacklisted) {
            unBlackList($userdata['id']);
            addUserHistory($userdata['email'], 'Confirmation',
                s('Subscriber removed from Blacklist for manual confirmation of subscription'));
        }

        if (empty($_SESSION['subscriberConfirmed'])) {
            $_SESSION['subscriberConfirmed'] = array();
        }
        //# 17513 - don't process confirmation if the subscriber is already confirmed
        if (empty($currently['confirmed']) && empty($_SESSION['subscriberConfirmed'][$userdata['email']])) {
            addUserHistory($userdata['email'], 'Confirmation', "Lists: $lists");

            $confirmationmessage = str_ireplace('[LISTS]', $lists,
                getUserConfig("confirmationmessage:$id", $userdata['id']));

            if (!TEST) {
                sendMail($userdata['email'], getConfig("confirmationsubject:$id"), $confirmationmessage,
                    system_messageheaders(), $envelope);
                $adminmessage = $userdata['email'].' has confirmed their subscription';
                if ($blacklisted) {
                    $adminmessage .= "\n\n".s('Subscriber has been removed from blacklist');
                }
                sendAdminCopy('List confirmation', $adminmessage, $subscriptions);
                addSubscriberStatistics('confirmation', 1);
            }
        } else {
            $html .= $GLOBALS['strAlreadyConfirmed'];
        }
        $_SESSION['subscriberConfirmed'][$userdata['email']] = time();
        $info = $GLOBALS['strConfirmInfo'];
    } else {
        logEvent('Request for confirmation for invalid user ID: '.substr($_GET['uid'], 0, 150));
        $html = 'Error: '.$GLOBALS['strUserNotFound'];
        $info = $GLOBALS['strConfirmFailInfo'];
    }

    $res = '<title>'.$GLOBALS['strConfirmTitle'].'</title>';
    $res .= $GLOBALS['pagedata']['header'];
    $res .= '<h3>'.$info.'</h3>';
    $res .= $html;
    $res .= '<p>'.$GLOBALS['PoweredBy'].'</p>';
    $res .= $GLOBALS['pagedata']['footer'];

    return $res;
}

/* unfinished
function subscribePage2($id)
{
  list($attributes,$attributedata) = PageAttributes($GLOBALS['pagedata']);
  $selected_lists = explode(',',$GLOBALS['pagedata']["lists"]);
  $html = '<title>'.$GLOBALS["strSubscribeTitle"].'</title>';
  $html .= '<link rel="stylesheet" type="text/css" href="styles/minimal.css" media="screen"/>';
  $html .= '</head><body>';
  $html .= '<div id="phplistform">';
  $html .= formStart();
  $html .= '<fieldset class="phplist"><legend>'.strip_tags($GLOBALS['pagedata']['intro']).'</legend>';
  $html .= ListAttributes2011($attributes,$attributedata,$GLOBALS['pagedata']["htmlchoice"],0,$GLOBALS['pagedata']['emaildoubleentry']);
  $html .= ListAvailableLists("",$GLOBALS['pagedata']["lists"]);

  if (empty($GLOBALS['pagedata']['button'])) {
    $GLOBALS['pagedata']['button'] = $GLOBALS['strSubmit'];
  }
  if (USE_SPAM_BLOCK) {
    $html .= '<div style="display:none"><input type="text" name="VerificationCodeX" value="" size="20"></div>';
  }
  $html .= '<button type="submit" name="subscribe">'.$GLOBALS['pagedata']["button"].'</button>
    </form>
    <p><a href="'.getConfig("unsubscribeurl").'&id='.$id.'">'.$GLOBALS["strUnsubscribe"].'</a></p>
  '.$GLOBALS["PoweredBy"];
  $html .= '</div>';## id=phplistform

  return $html;
}
*/

function unsubscribePage($id)
{
    global $tables;
    $email = '';
    $userid = 0;
    $msg = '';
    //# for unsubscribe, don't validate host
    $GLOBALS['check_for_host'] = 0;
    $res = '<title>'.$GLOBALS['strUnsubscribeTitle'].'</title>'."\n";
    $res .= $GLOBALS['pagedata']['header'];
    if (isset($_GET['uid'])) {
        $userdata = Sql_Fetch_Array_Query(sprintf('select email,id,blacklisted from %s where uniqid = "%s"',
            $tables['user'], sql_escape($_GET['uid'])));
        $email = $userdata['email'];
        $displayEmail = obfuscateEmailAddress($userdata['email']);
        $userid = $userdata['id'];
        $isBlackListed = $userdata['blacklisted'] != '0';
        $blacklistRequest = false;
    } else {
        if (isset($_REQUEST['email'])) {
            $email = $_REQUEST['email'];
            $displayEmail = obfuscateEmailAddress($email);
        }
        if (!validateEmail($email)) {
            $email = '';
        }

        //0013076: Blacklisting posibility for unknown users
        // Set flag for blacklisting
        $blacklistRequest = $_GET['p'] == 'blacklist' || $_GET['p'] == 'donotsend';

        // only proceed when user has confirm the form
        if ($blacklistRequest && is_email($email)) {
            $_POST['unsubscribe'] = 1;
            $_POST['unsubscribereason'] = s('Forwarded receiver requested blacklist');
        }
    }
    if (UNSUBSCRIBE_JUMPOFF || !empty($_GET['jo'])) {
        $_POST['unsubscribe'] = 1;
        $_REQUEST['email'] = $email;
        if (!empty($_GET['jo'])) {
            $blacklistRequest = true;
            $_POST['unsubscribereason'] = s('"Jump off" used by subscriber, reason not requested');
        } else {
            $_POST['unsubscribereason'] = s('"Jump off" set, reason not requested');
        }
    }
    foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
        //    print $pluginname.'<br/>';
        if ($plugin->unsubscribePage($email)) {
            return;
        }
    }

    if (!empty($email) && isset($_POST['unsubscribe']) &&
        isset($_REQUEST['email']) && isset($_POST['unsubscribereason'])
    ) {

        //# all conditions met, do the unsubscribe

        //0013076: Blacklisting posibility for unknown users
        // It would be better to do this above, where the email is set for the other cases.
        // But to prevent vulnerabilities let's keep it here for now. [bas]
        if (!$blacklistRequest) {
            $query = Sql_Fetch_Row_Query(sprintf('select id,email,blacklisted from %s where email = "%s"',
                $tables['user'], sql_escape($email)));
            $userid = $query[0];
            $email = $query[1];
            $isBlackListed = !empty($query[2]);
        }

        if (!$userid) {
            //0013076: Blacklisting posibility for unknown users
            if ($blacklistRequest && !empty($email)) {
                addUserToBlacklist($email, $_POST['unsubscribereason']);
                addSubscriberStatistics('blacklist', 1);
                $res .= '<h3>'.$GLOBALS['strUnsubscribedNoConfirm'].'</h3>';
            } else {
                $res .= $GLOBALS['strNoListsFound']; //'Error: '.$GLOBALS["strUserNotFound"];
                logEvent('Request to unsubscribe non-existent user: '.substr($email, 0, 150));
            }
        } else {
            $subscriptions = array();
            $listsreq = Sql_Query(sprintf('select listid from %s where userid = %d', $GLOBALS['tables']['listuser'],
                $userid));
            while ($row = Sql_Fetch_Row($listsreq)) {
                array_push($subscriptions, $row[0]);
            }

            //# 17753 - do not actually remove the list-membership when unsubscribing
            //   $result = Sql_query(sprintf('delete from %s where userid = %d',$tables["listuser"],$userid));
            $lists = '  * '.$GLOBALS['strAllMailinglists']."\n";

            if (empty($isBlackListed)) { // only process when not already marked as blacklisted
                // add user to blacklist
                addUserToBlacklist($email, nl2br(strip_tags($_POST['unsubscribereason'])));
                addUserHistory($email, 'Unsubscription', "Unsubscribed from $lists");
                $unsubscribemessage = str_replace('[LISTS]', $lists, getUserConfig("unsubscribemessage:$id", $userid));
                if (UNSUBSCRIBE_CONFIRMATION) {
                    sendMail($email, getUserConfig("unsubscribesubject:$id"), stripslashes($unsubscribemessage),
                        system_messageheaders($email), '', true);
                }
                $reason = $_POST['unsubscribereason'] ? "Reason given:\n".stripslashes($_POST['unsubscribereason']) : 'No Reason given';
                sendAdminCopy('List unsubscription', $email." has unsubscribed\n$reason", $subscriptions);
                addSubscriberStatistics('unsubscription', 1);
            }
        }

    if ($userid) {
        if (UNSUBSCRIBE_CONFIRMATION) {
            $res .= '<h3>' . $GLOBALS['strUnsubscribeDone'] . '</h3>';
        } else {
            $res .= '<h3>' . $GLOBALS['strUnsubscribedNoConfirm'] . '</h3>';
        }
    }

    //0013076: Blacklisting posibility for unknown users
        //if ($blacklistRequest) {
        //$res .= '<h3>'.$GLOBALS["strYouAreBlacklisted"] ."</h3>";
        //}
        $res .= $GLOBALS['PoweredBy'].'</p>';
        $res .= $GLOBALS['pagedata']['footer'];

        return $res;
    } elseif (isset($_POST['unsubscribe']) && !is_email($email) && !empty($email)) {
        $msg = '<span class="error">'.$GLOBALS['strEnterEmail'].'</span><br>';
    }

    $res .= '<h3>'.$GLOBALS['strUnsubscribeInfo'].'</h3>'.
        $msg.'<form method="post" action=""><input type="hidden" name="p" value="unsubscribe" />';
    if (empty($displayEmail) && !isset($_POST['email']) || empty($email)) {
        $res .= '<p>'.$GLOBALS['strEnterEmail'].': <input type="text" name="email" value="'.$email.'" size="40" /></p>';
    } else {
        $res .= '<p><input type="hidden" name="email" value="'.$email.'" />'.$GLOBALS['strEmail'].': '.$displayEmail.'</p>';
    }

    if (!$email) {
        $res .= '<input type="submit" name="unsubscribe" value="'.$GLOBALS['strContinue'].'"></form>';
        $res .= $GLOBALS['PoweredBy'];
        $res .= $GLOBALS['pagedata']['footer'];

        return $res;
    }

    $current = Sql_Fetch_Array_query(sprintf('select list.id as listid,user.uniqid as userhash, user.password as password
    from %s as list,%s as listuser,%s as user where list.id = listuser.listid and user.id = listuser.userid and user.email = "%s"',
        $tables['list'], $tables['listuser'], $tables['user'], sql_escape($email)));
    $some = $current['listid'];
    if (ASKFORPASSWORD && !empty($user['password'])) {
        // it is safe to link to the preferences page, because it will still ask for
        // a password
        $hash = $current['userhash'];
    } elseif (isset($_GET['uid']) && $_GET['uid'] == $current['userhash']) {
        // they got to this page from a link in an email
        $hash = $current['userhash'];
    } else {
        $hash = '';
    }

    $finaltext = $GLOBALS['strUnsubscribeFinalInfo'];
    $pref_url = getConfig('preferencesurl');
    $sep = strpos($pref_url, '?') !== false ? '&' : '?';
    $finaltext = str_ireplace('[preferencesurl]', $pref_url.$sep.'uid='.$hash, $finaltext);

    if (!$some) {
        //0013076: Blacklisting posibility for unknown users
        if (!$blacklistRequest) {
            $res .= '<b>'.$GLOBALS['strNoListsFound'].'</b></ul>';
        }
        $res .= '<p><input type=submit value="'.$GLOBALS['strUnsubscribe'].'">';
    } else {
        if ($blacklistRequest) {
            $res .= $GLOBALS['strExplainBlacklist'];
        } elseif (!UNSUBSCRIBE_JUMPOFF) {
            list($r, $c) = explode(',', getConfig('textarea_dimensions'));
            if (!$r) {
                $r = 5;
            }
            if (!$c) {
                $c = 65;
            }
            $res .= $GLOBALS['strUnsubscribeRequestForReason'];
            $res .= sprintf('<br/><textarea name="unsubscribereason" cols="%d" rows="%d" wrap="virtual"></textarea>',
                    $c, $r).$finaltext;
        }
        $res .= '<p><input type=submit name="unsubscribe" value="'.$GLOBALS['strUnsubscribe'].'"></p>';
    }
    $res .= '</form>';
    $res .= '<p>'.$GLOBALS['PoweredBy'].'</p>';
    $res .= $GLOBALS['pagedata']['footer'];

    return $res;
}

//#######################################
if (!function_exists('htmlspecialchars_decode')) {
    function htmlspecialchars_decode($string, $quote_style = ENT_COMPAT)
    {
        return strtr($string, array_flip(get_html_translation_table(HTML_SPECIALCHARS, $quote_style)));
    }
}
function forwardPage($id)
{
    global $tables;
    $ok = true;
    $subtitle = '';
    $info = '';
    $html = '';
    $form = '';
    $personalNote = '';

    //# Check requirements
    // message
    $mid = 0;
    if (isset($_REQUEST['mid'])) {
        $mid = sprintf('%d', $_REQUEST['mid']);
        $messagedata = loadMessageData($mid);
        $mid = $messagedata['id'];
        if ($mid) {
            $subtitle = $GLOBALS['strForwardSubtitle'].' '.stripslashes($messagedata['subject']);
        }
    } //mid set

    // user
    if (!isset($_REQUEST['uid']) || !$_REQUEST['uid']) {
        FileNotFound();
    }

    //# get userdata
    $req = Sql_Query(sprintf('select * from %s where uniqid = "%s"', $tables['user'], sql_escape($_REQUEST['uid'])));
    $userdata = Sql_Fetch_Array($req);
    //# verify that this subscriber actually received this message to forward, otherwise they're not allowed
    $allowed = Sql_Fetch_Row_Query(sprintf('select userid from %s where userid = %d and messageid = %d',
        $GLOBALS['tables']['usermessage'], $userdata['id'], $mid));
    if (empty($userdata['id']) || $allowed[0] != $userdata['id']) {
        //# when sending a test email as an admin, the entry isn't there yet
        if (empty($_SESSION['adminloggedin']) || $_SESSION['adminloggedin'] != $_SERVER['REMOTE_ADDR']) {
            FileNotFound('<br/><i>'.$GLOBALS['I18N']->get('When testing the phpList forward functionality, you need to be logged in as an administrator.').'</i><br/>');
        }
    }

    $firstpage = 1; //# is this the initial page or a followup

    // forward addresses
    $forwardemail = '';
    if (isset($_REQUEST['email']) && !empty($_REQUEST['email'])) {
        $firstpage = 0;
        $forwardPeriodCount = Sql_Fetch_Array_Query(sprintf('select count(user) from %s where date_add(time,interval %s) >= now() and user = %d and status ="sent" ',
            $tables['user_message_forward'], FORWARD_EMAIL_PERIOD, $userdata['id']));
        $forwardemail = stripslashes($_REQUEST['email']);
        $emails = explode("\n", $forwardemail);
        $emails = trimArray($emails);
        $forwardemail = implode("\n", $emails);
        //0011860: forward to friend, multiple emails
        $emailCount = $forwardPeriodCount[0];
        foreach ($emails as $index => $email) {
            $emails[$index] = trim($email);
            if (is_email($email)) {
                ++$emailCount;
            } else {
                $info .= sprintf('<br />'.$GLOBALS['strForwardInvalidEmail'], $email);
                $ok = false;
            }
        }
        if ($emailCount > FORWARD_EMAIL_COUNT) {
            $info .= '<br />'.$GLOBALS['strForwardCountReached'];
            $ok = false;
        }
    } else {
        $ok = false;
    }
    // subscriber name
    if (!empty($_REQUEST['subscriberName'])) {
        $subscriberName = htmlspecialchars_decode(stripslashes($_REQUEST['subscriberName']));
        $userdata['subscriberName'] = $subscriberName;
    } else {
        $subscriberName = '';
        $ok = false;
    }
    //0011996: forward to friend - personal message
    // text cannot be longer than max, to prevent very long text with only linefeeds total cannot be longer than twice max
    if (FORWARD_PERSONAL_NOTE_SIZE && isset($_REQUEST['personalNote'])) {
        if (strlen(strip_newlines($_REQUEST['personalNote'])) > FORWARD_PERSONAL_NOTE_SIZE || strlen($_REQUEST['personalNote']) > FORWARD_PERSONAL_NOTE_SIZE * 2) {
            $info .= '<BR />'.$GLOBALS['strForwardNoteLimitReached'];
            $ok = false;
        }
        $personalNote = strip_tags(htmlspecialchars_decode(stripslashes($_REQUEST['personalNote'])));
        $userdata['personalNote'] = $personalNote;
    }

    if ($userdata['id'] && $mid) {
        if ($ok && count($emails)) { //# All is well, send it
            require_once 'admin/sendemaillib.php';

            //0013845 Lead Ref Scheme
            if (FORWARD_FRIEND_COUNT_ATTRIBUTE) {
                $iCountFriends = FORWARD_FRIEND_COUNT_ATTRIBUTE;
            } else {
                $iCountFriends = 0;
            }
            if ($iCountFriends) {
                $nFriends = intval(UserAttributeValue($userdata['id'], $iCountFriends));
            }

            //# remember the lists for this message in order to notify only those admins
            //# that own them
            $messagelists = array();
            $messagelistsreq = Sql_Query(sprintf('select listid from %s where messageid = %d',
                $GLOBALS['tables']['listmessage'], $mid));
            while ($row = Sql_Fetch_Row($messagelistsreq)) {
                array_push($messagelists, $row[0]);
            }

            foreach ($emails as $index => $email) {
                //0011860: forward to friend, multiple emails
                $done = Sql_Fetch_Array_Query(sprintf('select user,status,time from %s where forward = "%s" and message = %d',
                    $tables['user_message_forward'], $email, $mid));
                $info .= '<br />'.$email.': ';
                if ($done['status'] === 'sent') {
                    $info .= $GLOBALS['strForwardAlreadyDone'];
                } elseif (isBlackListed($email)) {
                    $info .= $GLOBALS['strForwardBlacklistedEmail'];
                } else {
                    if (!TEST) {
                        // forward the message
                        // sendEmail will take care of blacklisting

//## CHECK $email vs $forwardemail

                        if (sendEmail($mid, $email, 'forwarded', $userdata['htmlemail'], array(), $userdata)) {
                            $info .= $GLOBALS['strForwardSuccessInfo'];
                            sendAdminCopy(s('Message Forwarded'),
                                s('%s has forwarded message %d to %s', $userdata['email'], $mid, $email),
                                $messagelists);
                            Sql_Query(sprintf('insert into %s (user,message,forward,status,time)
                 values(%d,%d,"%s","sent",now())',
                                $tables['user_message_forward'], $userdata['id'], $mid, $email));
                            if ($iCountFriends) {
                                ++$nFriends;
                            }
                        } else {
                            $info .= $GLOBALS['strForwardFailInfo'];
                            sendAdminCopy(s('Message Forwarded'),
                                s('%s tried forwarding message %d to %s but failed', $userdata['email'], $mid, $email),
                                $messagelists);
                            Sql_Query(sprintf('insert into %s (user,message,forward,status,time)
                values(%d,%d,"%s","failed",now())',
                                $tables['user_message_forward'], $userdata['id'], $mid, $email));
                            $ok = false;
                        }
                    }
                }
            } // foreach friend
            if ($iCountFriends) {
                saveUserAttribute($userdata['id'], $iCountFriends,
                    array('name' => FORWARD_FRIEND_COUNT_ATTRIBUTE, 'value' => $nFriends));
            }
        } //ok & emails
    } else { // no valid sender
        logEvent(s('Forward request from invalid user ID: %s', substr($_REQUEST['uid'], 0, 150)));
        $info .= '<BR />'.$GLOBALS['strForwardFailInfo'];
        $ok = false;
    }
    /*
      $data = PageData($id);
      if (isset($data['language_file']) && is_file(dirname(__FILE__).'/texts/'.basename($data['language_file']))) {
        @include dirname(__FILE__).'/texts/'.basename($data['language_file']);
      }
    */

//# BAS Multiple Forward
    //# build response page
    $form = '<form method="post" action="">';
    $form .= sprintf('<input type=hidden name="mid" value="%d">', $mid);
    $form .= sprintf('<input type=hidden name="id" value="%d">', $id);
    $form .= sprintf('<input type=hidden name="uid" value="%s">', $userdata['uniqid']);
    $form .= sprintf('<input type=hidden name="p" value="forward">');
    if (!$ok) {
        //0011860: forward to friend, multiple emails
        if (FORWARD_EMAIL_COUNT == 1) {
            $format = <<<'END'
<div class="required"><label for="email">%s</label></div>
<input type=text name="email" id="email" value="%s" size=50 class="attributeinput">
END;
            $form .= sprintf($format, $GLOBALS['strForwardEnterEmail'], $forwardemail);
        } else {
            $labelText = sprintf($GLOBALS['strForwardEnterEmails'], FORWARD_EMAIL_COUNT);
            $format = <<<'END'
<div class="required"><label for="email">%s</label></div>
<textarea name="email" id="email" rows="%d" cols="50" class="attributeinput">%s</textarea>
END;
            $form .= sprintf($format, $labelText, min(10, FORWARD_EMAIL_COUNT), $forwardemail);
        }
        $format = <<<'END'
<div class="required"><label for="subscriberName">%s</label></div>
<input type=text name="subscriberName" id="subscriberName" value="%s" size=50 class="attributeinput">
END;
        $form .= sprintf($format, $GLOBALS['strForwardForwardingName'], htmlspecialchars($subscriberName));

        //0011996: forward to friend - personal message
        if (FORWARD_PERSONAL_NOTE_SIZE) {
            $labelText= sprintf($GLOBALS['strForwardPersonalNote'], FORWARD_PERSONAL_NOTE_SIZE);
            $cols = 50;
            $rows = min(10, ceil(FORWARD_PERSONAL_NOTE_SIZE / 40));
            $format = <<<'END'
<div><label for="personalNote">%s</div>
<textarea type="text" name="personalNote" id="personalNote" rows="%d" cols="%d" class="attributeinput">%s</textarea>
</label>
END;
            $form .= sprintf($format, $labelText, $rows, $cols, $personalNote);
        }
        $form .= sprintf('<br /><input type="submit" value="%s"></form>', $GLOBALS['strContinue']);
    }

//## END BAS

//## Michiel, remote response page

    $remote_content = '';
    if (preg_match("/\[URL:([^\s]+)\]/i", $messagedata['message'], $regs)) {
        if (isset($regs[1]) && strlen($regs[1])) {
            $url = $regs[1];
            if (!preg_match('/^http/i', $url)) {
                $url = 'http://'.$url;
            }
            $remote_content = fetchUrl($url);
        }
    }

    if (!empty($remote_content) && preg_match('/\[FORWARDFORM\]/', $remote_content, $regs)) {
        if ($firstpage) {
            //# this is the initial page, not a follow up one.
            $remote_content = str_replace($regs[0], $info.$form, $remote_content);
        } else {
            $remote_content = str_replace($regs[0], $info, $remote_content);
        }
        $res = $remote_content;
    } else {
        $res = '<title>'.$GLOBALS['strForwardTitle'].'</title>';
        $res .= $GLOBALS['pagedata']['header'];
        $res .= '<h3>'.$subtitle.'</h3>';
        if ($ok) {
            $res .= '<h4>'.$info.'</h4>';
        } elseif (!empty($info)) {
            $res .= '<div class="error missing">'.$info.'</div>';
        }
        $res .= $form;
        $res .= '<p>'.$GLOBALS['PoweredBy'].'</p>';
        $res .= $GLOBALS['pagedata']['footer'];
    }
//## END MICHIEL

    return $res;
}
