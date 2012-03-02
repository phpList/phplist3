<?php

# sql functions, currently only set up to work with MySql
# replace functions in this file to make it work with other Databases

if (!function_exists("mysql_connect")) {
  print "Fatal Error: Mysql is not supported in your PHP, recompile and try again.";
  exit;
}

function Sql_Connect($host,$user,$password,$database) {
  if ($host && $user)
    $db = mysql_connect($host , $user ,$password );
  $errno = mysql_errno();
  if (!$errno) {
    $res = mysql_select_db($database,$db);
    $errno = mysql_errno();
  }
  if ($errno) {
    switch ($errno) {
      case 1049: # unknown database
        Fatal_Error("unknown database, cannot continue");
        exit;
      case 1045: # access denied
        Fatal_Error("Cannot connect to database, access denied. Please contact the administrator");
        exit;
      case 2002:
        Fatal_Error("Cannot connect to database, Sql server is not running. Please contact the administrator");
        exit;
      case 1040: # too many connections
        Fatal_Error("Sorry, the server is currently too busy, please try again later.");
        exit;
      case 2006: # "gone away"
        Fatal_Error("Sorry, the server is currently too busy, please try again later.");
        exit;
      case 0:
        break;
      default:
        print SQL_Error($GLOBALS['database_connection'],$errno);
    }
 #   print "Cannot connect to Database, please check your configuration";
    exit;
  }
  if (!$db) {
    print "Cannot connect to Database, please check your configuration";
    exit;
  }
#  if (strcasecmp($GLOBALS['strCharSet'], 'utf-8') == 0) {
    mysql_query ("SET NAMES 'utf8'");
#  }
  unset($GLOBALS['lastquery']);
  return $db;
}

function Sql_has_error ($dbconnection) {
  return mysql_errno($dbconnection);
}

function Sql_Error ($dbconnection,$errno = 0) {
  $msg = mysql_error($dbconnection);
  if (empty($GLOBALS['commandline'])) {
/*
    output('DB error'.$errno);
    print debug_print_backtrace(); 
*/
    return '<div id="dberror">Database error '. $errno.' while doing query '.$GLOBALS['lastquery']. ' ' .$msg.'</div>';
  } else {
    print 'Database error '.$errno.' while doing query '.$GLOBALS['lastquery']. ' ' .$msg."\n";
  }
    if (function_exists("logevent")) {
      logevent("Database error: $msg");
    }

#  return "<table class="x" border=1><tr><td class=\"error\">Database Error</td></tr><tr><td><!--$errno: -->$msg</td></tr></table>";
}

function Sql_Check_error($dbconnection,$errno = 0) {
  if (!$errno)
    $errno = Sql_has_error($dbconnection);
  if ($errno) {
    switch ($errno) {
      case 1049: # unknown database
        Fatal_Error("unknown database, cannot continue");
        exit;
      case 1045: # access denied
        Fatal_Error("Cannot connect to database, access denied. Please contact the administrator");
        exit;
      case 2002:
        Fatal_Error("Cannot connect to database, Sql server is not running. Please contact the administrator");
        exit;
      case 1040: # too many connections
        Fatal_Error("Sorry, the server is currently too busy, please try again later.");
        exit;
      case 0:
        break;
      default:
        print Sql_error($dbconnection,$errno);
    }
    return 1;
  }
}

function Sql_Query($query,$ignore = 0) {
  if (empty($GLOBALS['database_connection'])) {
    $GLOBALS['database_connection'] = Sql_Connect(
      $GLOBALS['database_host'],
      $GLOBALS['database_user'],
      $GLOBALS['database_password'],
      $GLOBALS['database_name']);
  }
  
  if (isset($GLOBALS['lastquery'])) {
    unset($GLOBALS['lastquery']);
  }
  if (isset($GLOBALS["developer_email"])) {
  #  if (preg_match("/dev$/",VERSION))
  #  print "<b>$query</b><br>\n";
  #  if ($GLOBALS["commandline"]) {
  #    ob_end_clean();
  #    print "Sql: $query\n";
  #    ob_start();
  #  }
  sqllog($query,'/tmp/queries.log');
  
  # time queries to see how slow they are, so they can
    # be optimized
    $now =  gettimeofday();
    $start = $now["sec"] * 1000000 + $now["usec"];
    $GLOBALS['lastquery'] = $query;
    # keep track of queries to see which ones to optimize
    if (function_exists('stripos')) {
      if (!stripos($query,'WebblerSessions') && !stripos($query,'cache')) {
        $store = $query;
        $store = preg_replace('/\d+/','X',$store);
        $store = trim($store);
        @mysql_query(sprintf('update querycount set count = count + 1 where query = "%s" and frontend = %d',$store,$frontend));
        if (mysql_affected_rows() != 2) {
          @mysql_query(sprintf('insert into querycount set count = count + 1 , query = "%s",phplist = 1',$store));
        }
      }
    }
  }
  
 # dbg($query);
  $GLOBALS["pagestats"]["number_of_queries"]++;
  $result = mysql_query($query,$GLOBALS["database_connection"]);
  if (!$ignore) {
    if (Sql_Check_Error($GLOBALS["database_connection"]))
      dbg("Sql error in $query");
  }
  if (isset($GLOBALS["developer_email"])) {
    # log time queries take
    $now = gettimeofday();
    $end = $now["sec"] * 1000000 + $now["usec"];
    $elapsed = $end - $start;
    if ($elapsed > 300000) {
      $query = substr($query,0,200);
      sqllog(' ['.$elapsed.'] '.$query,"/tmp/phplist-sqltimer.log");
    } else {
#      sqllog(' ['.$elapsed.'] '.$query,"/tmp/phplist-sqltimer.log");
    }
  }
  return $result;
}

function Sql_Close() {
  mysql_close($GLOBALS["database_connection"]);
  unset($GLOBALS["database_connection"]);
}

function Sql_Query_Params($query,$params,$ignore = 0) {
#  dbg($query);
#  dbg($params);

  if (!is_array($params)) $params = Array($params);

  foreach ($params as $index => $par) {
    $qmark = strpos  ( $query  , '?');
    if ($qmark === FALSE) {
      dbg("Error, more parameters than placeholders");
    } else {
      ## first replace the ? with some other placeholder, in case the parameters contain ? themselves
      $query = substr($query,0,$qmark).'"PARAM'.$index.'MARAP"'.substr($query,$qmark+1);
    }
  }
#  dbg($query);

  foreach ($params as $index => $par) {
    if (is_numeric($par)) {
      $query = str_replace('"PARAM'.$index.'MARAP"',sql_escape($par),$query);
    } else {
      $query = str_replace('PARAM'.$index.'MARAP',sql_escape($par),$query);
    }
  }

#  dbg($query);
#  print $query."<br/>";
  return Sql_Query($query);
}

function sqllog($msg,$logfile = "") {
  if (!$logfile) return;
  $fp = @fopen($logfile,"a");
  $line = "[".date("d M Y, H:i:s")."] ".getenv("REQUEST_URI").'('.$GLOBALS["pagestats"]["number_of_queries"].") $msg \n";
  @fwrite($fp,$line);
  @fclose($fp);
}

function db_db_Query($query,$database,$db_connection) {
  $res = mysql_db_query($database,$query,$db_connection);
  if (db_has_Error($db_connection))
    Sql_Error($db_connection);
  return $res;
}

function Sql_Verbose_Query($query,$ignore = 0) {
  if (preg_match("/dev/",VERSION)) {
    print "<b>$query</b><br>\n";
  }
  dbg($query);
  flush();
  if (!empty($GLOBALS["commandline"])) {
    ob_end_clean();
    print "Sql: $query\n";
    ob_start();
  }
  return Sql_Query($query,$ignore);
}

function Sql_Fetch_Array($dbresult) {
  return mysql_fetch_array($dbresult);
}

function Sql_Fetch_Assoc($dbresult) {
  return mysql_fetch_assoc($dbresult);
}

function Sql_Fetch_Row($dbresult) {
  return mysql_fetch_row($dbresult);
}

function Sql_Fetch_Row_Query($query,$ignore = 0) {
  $req = Sql_Query($query,$ignore);
  return Sql_Fetch_Row($req);
}

function Sql_Fetch_Array_Query($query,$ignore = 0) {
  $req = Sql_Query($query,$ignore);
  return Sql_Fetch_Array($req);
}

function Sql_Fetch_Assoc_Query($query,$ignore = 0) {
  $req = Sql_Query($query,$ignore);
  return Sql_Fetch_Assoc($req);
}

function Sql_Affected_Rows() {
  return mysql_affected_rows($GLOBALS["database_connection"]);
}

function Sql_Num_Rows($result = "") {
  return mysql_num_rows($result);
}

function Sql_Insert_Id() {
  return mysql_insert_id($GLOBALS["database_connection"]);
}

function Sql_Result($result,$index,$column) {
  return mysql_result($result,$index,$column);
}

function Sql_Free_Result($dbresult) {
  mysql_free_result($dbresult);
};

function Sql_Table_exists($table,$refresh = 0) {
  ## table is the full table name including the prefix
  if (!empty($_GET['pi']) || $refresh || !isset($_SESSION) || !isset($_SESSION["dbtables"]) || !is_array($_SESSION["dbtables"])) {
    $_SESSION["dbtables"] = array();
    $req = Sql_Query("show tables");
    while ($row = Sql_Fetch_Row($req)) {
      array_push($_SESSION["dbtables"],$row[0]);
    }
  }
  return in_array($table,$_SESSION["dbtables"]);
}

function Sql_Table_Column_Exists($table,$column) {
  ## table is the full table name including the prefix
  if (Sql_Table_exists($table)) {
    $req = Sql_Query("show columns from $table");
    while ($row = Sql_Fetch_Row($req)) {
      if ($row[0] == $column)
        return 1;
    }
  }
}

function Sql_Check_For_Table($table) {
  ## table is the full table name including the prefix, or the abbreviated one without prefix
  return Sql_Table_exists($table) || Sql_Table_exists($GLOBALS['tables'][$table]);
}

function createTable($table) {
  ## table is the abbreviated table name one without prefix
  if (!isset($GLOBALS['tables'][$table])) return false;
  include dirname(__FILE__).'/structure.php';
  if (!empty($DBstruct[$table]) && is_array($DBstruct[$table])) {
    Sql_Create_table($GLOBALS['tables'][$table],$DBstruct[$table]);
    return true;
  }
  return false;
}

function Sql_create_Table ($table,$structure) {
  $query = "CREATE TABLE $table (\n";
  while (list($column, $val) = each($structure)) {
    if (preg_match('/index_\d+/',$column)) {
      $query .= "index " . $structure[$column][0] . ",";
    } elseif (preg_match('/unique_\d+/',$column)) {
      $query .= "unique " . $structure[$column][0] . ",";
    } else {
      $query .= "$column " . $structure[$column][0] . ",";
    }
  }
  # get rid of the last ,
  $query = substr($query,0,-1);
  $query .= "\n) default character set utf8";
  # submit it to the database
  $res = Sql_Query($query);
  unset($_SESSION["dbtables"]);
}

function Sql_Drop_Table($table) {
#  print '<br/>DROP '.$table;
  return mysql_query('drop table if exists '.$table);
}

function sql_escape($text) {
  return mysql_real_escape_string($text);
}

function Sql_Replace ($table,$values,$pk) {

  $query = ' replace into '.$table.' set ';
  foreach ($values as $key => $val) {
    if (is_numeric($val) || $val == 'current_timestamp') {
      $query .= ' '.$key .'= '.sql_escape($val).',';
    } else {
      $query .= ' '.$key .'="'.sql_escape($val).'",';
    }
  }
  $query = substr($query,0,-1);
 # output($query);
  return Sql_Query($query);
}

function Sql_Set_Search_Path($searchpath) {
  return;
}

?>
