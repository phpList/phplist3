<?php
require_once dirname(__FILE__).'/accesscheck.php';

ini_set("track_errors",true);
function Error($msg) {
	logError($msg);
  print "Error: $msg<br>\n";
}

function Fatal_error($msg) {
	global $config;
 # logError($msg);
  $emailmsg = ' Fatal Error '.$config["websiteurl"]."\n\n".
    $PHP_SELF." ".$page.", $msg";
  sendError($emailmsg);
  Error($msg);
  exit;
}

function FileUploadError($errno) {
	if (!$errno)
  	return;
	switch ($errno) {
  	case 1:
    	return "The file is too big";
    case 2:
    	return "The file is too big;";
    case 3:
    	return "File was only partially uploaded.";
    case 4:
    	return "No file was uploaded.";
    default:
    	return "Unknown upload error";
  }
}

function listArray($array,$indent = 0) {
	if (!is_array($array))
  	return $array;
	if ($indent)
	  $prefix = str_repeat(" ",$indent);
  else
  	$prefix = "";
	$res = "\n".$prefix . "### start array ###";
  while (list($key,$val) = each ($array)) {
    $res .= "\n".$prefix ."$key => ";
    if (is_array($val)) {
      $res .= listArray($val,$indent+2);
    } else {
      $res .= $prefix . $val;
    }
    $res .= "\n";
  }
  $res .= "\n### end array ###\n";
  return $res;
}

function backtrace() {
	$msg = "";
  if (function_exists("debug_backtrace")) {
	  $debug = debug_backtrace();
  	while (list($key,$val) = each($debug)) {
	  	$msg .= $key .'=>'."<br/>\n";
      if (is_array($val))
      	$msg .= listArray($val);
      else
      	$msg .= $val;
    }
  } else {
  	return 'backtrace not available';
  }
  return $msg;
}

function sendError($msg, $email = "") {
	global $config;
  $subjectmsg = substr($msg,0,25);

 // debug_backtrace();

  $emailmsg = $msg .  '

  ==================

  SYSTEM VALS:
  ';
  $emailmsg .= 'PHP VERSION: '.phpversion().'
  backtrace:
';
  if (function_exists("debug_backtrace")) {
	  $debug = debug_backtrace();
  	while (list($key,$val) = each($debug)) {
	  	$emailmsg .= $key .'=>'.listArray($val)."\n";
    }
  } else {
  	$emailmsg .= 'not available';
  }

  $emailmsg .= '

  POST VALS:
  ';
  foreach ($_POST as $key => $val) {
  	$emailmsg .= $key .'->'.listArray($val)."\n";
  }
  $emailmsg .= '

  GET VALS:
  ';
  foreach ($_GET as $key => $val) {
  	$emailmsg .= $key .'->'.$val."\n";
  }
  $emailmsg .= '

  SESSION VALS:
  ';
  if (is_array($_SESSION))
  foreach ($_SESSION as $key => $val) {
  	$emailmsg .= $key .'->'.listArray($val)."\n";
  }
  $emailmsg .= '

  SERVER VALS:
  ';
  foreach ($_SERVER as $key => $val) {
  	$emailmsg .= $key .'->'.$val."\n";
  }
  $emailmsg .= '

  CONFIG VALS:
  ';
  reset($config);
  foreach ($config as $key => $val) {
  	$emailmsg .= $key .'->'.$val."\n";
  }

  $config["mail_errors"] = $config["mail_errors"] ?$config["mail_errors"]:"webbler_errors@tincan.co.uk";
  if ($email) {
  	$destination = $email;
  } elseif ($config["mail_errors"]) {
  	$destination = $config["mail_errors"];
  } else {
  	$destination = 'sysadmin@tincan.co.uk';
  }
  #mail ($config["mail_errors"],$config["websiteurl"]." Webbler error",$emailmsg);
  if (is_dir($config["code_root"] ."/../spool/mail")) {
	  $fname = tempnam($config["code_root"] ."/../spool/mail/","msg");
    dbg("Writing error to $fname");
  	$fp = @fopen($fname,"w");
    fwrite($fp,"To: ".$destination."\n");
    fwrite($fp,"Subject: ".$config["websiteurl"]." WE $subjectmsg\n");
    #eg 11 Jun 2003 10:20:00 -0000
    fwrite($fp,"Date: ".date("j M Y H:i:s O")."\n");
    fwrite($fp,"\n");
    fwrite($fp,$emailmsg);
    fclose($fp);
    chmod($fname,0666);
  } else {
  	dbg("No error spool directory found in ".$config["coderoot"] ."/../spool/mail");
  }
}


//

function Warn($msg) {
	logError("Warning: $msg");
  return "<table border=1><tr><td><font color=red>Warning: $msg</font></td></tr></table>";
}

function Info($msg) {
  print "<table border=1><tr><td><font color=green>Info: $msg</font></td></tr></table>";
}

function LogError($error) {
	$ref = getenv("HTTP_REFERER");
	if (function_exists("sql_Query"))
   Sql_Query(sprintf('insert into errorlog (request,error,entered,remoteuser)
   	values("%s","%s",now(),"%s")',
    getenv("REQUEST_URI"),$error."\nReferred from: ".$ref,getenv("REMOTE_ADDR")),1);
}

function Debug($msg) {
	global $config;
 # if (!$config["debug"])
  if (ini_get("safe_mode"))
  	return;
  if (!$config["debug"])
    return;
  if ($config["verbose"])
     print "\n".'<font class="debug">DBG: '.$msg.'</font><br>'."\n";
  elseif ($config["debug_log"]) {
    $fp = @fopen($config["debug_log"],"a");
    $line = "[".date("d M Y, H:i:s")."] ".getenv("REQUEST_URI").'('.$config["stats"]["number_of_queries"].") $msg \n";
    @fwrite($fp,$line);
    @fclose($fp);
  #  $fp = fopen($config["sql_log"],"a");
  #  fwrite($fp,"$line");
  #  fclose($fp);
  } else {
  #  Fatal_Error("Debugging not configured properly");
  }
}

if(!function_exists('dbg')) {
//  $_GLOBALS['head']['dbginfo'] = '<!--Using dbg from uploader/codelib/commonlib/lib/errorlib-->'; 
	function dbg($msg, $description, $nestingLevel) {
	  # bit of shorthand
	  Debug($msg);
	}
}

#if (!$config["debug"])
#  error_reporting(0);

  function userErrorHandler ($errno, $errmsg, $filename, $linenum, $vars) {
  	# whats the point of a user handler when it only
    # passes notices. unfortunaltey other errors dont get passed
    # so this wont be called too often
 # 	dbg("User error: $errno, $errmsg");
    global $config;
    $time=date("d M Y H:i:s");

    // Get the error type from the error number
    $errortype = array (
      1   =>  "Error",
      2   =>  "Warning",
      4   =>  "Parsing Error",
      8   =>	"Notice",
      16  =>  "Core Error",
      32  =>  "Core Warning",
      64  =>  "Compile Error",
      128 => "Compile Warning",
      256 =>  "User Error",
      512 => "User Warning",
      1024=>  "User Notice"
    );
    $errlevel=$errortype[$errno];

    //Write error to log file (CSV format)
    if (!isset($config["error_log"]))
    	$config["error_log"] = '/tmp/'.$config["name"]."_errors.csv";
    if($errno!=2 && $errno!=8) { //Terminate script if fatal error
      print "Sorry an error occurred: ($errno)".$errmsg;

      $errfile=fopen($config["error_log"],"a");
      fputs($errfile,"$time\t$filename\t$linenum\t($errlevel)\t$errmsg\n");
      fclose($errfile);

	    sendError($errno." ".$errmsg.'
      	File: '.$filename.'
        Line: '.$linenum.'
        Vars: '.$vars);
#			LogError($errno." ".$errmsg);
      die("A fatal error has occured. Script execution has been aborted");
    }
    return 1;
  }
#  dbg("set error handler to own");
  $old_error_handler = set_error_handler("userErrorHandler");
#}
?>
