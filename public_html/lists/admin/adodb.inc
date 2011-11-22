<?php

# configuration to make PHPlist work via AdoDB
# which will make it work with any database supported by AdoDB

if (is_file($adodb_inc_file)) {
  require_once $adodb_inc_file;
} 

if (!function_exists('ADONewConnection')) {
  print 'Error, AdoDB specified but not available';
  exit;
}

$GLOBALS['adodb_connection'] = ADONewConnection($site['adodb_driver']);
#$GLOBALS["adodb_connection"]->debug = true;

$GLOBALS["adodb_lastresult"] = "";

function Sql_Connect($host,$user,$password,$database) {
  if ($host && $user) {
    $GLOBALS["adodb_connection"]->Connect($host, $user, $password, $database);
    return 1;
  }
  return 0;
}

function Sql_Set_Search_Path($searchpath) {
  global $site;
  if (!in_array($site['adodb_driver'], array('postgres64, postgres7, postgres8'))) {
    return true;
  }
  $GLOBALS['searchpath'] = $searchpath;
  return Sql_Query("set search_path to $searchpath");
}

function Sql_has_error ($dbconnection) {
  return 0;
}

function Sql_Error ($errno = 0) {
  $msg = $GLOBALS["adodb_connection"]->ErrorMsg();
  $msg .= "<br>backtrace . " . print_r(debug_backtrace(), true);
  return '<div id="dberror">Database error '. $errno.' '.$msg.'</div>';
   if (function_exists("logevent")) {
     logevent("Database error: $msg");
   }
}

function Sql_Query($query,$ignore = 0) {
  $result = $GLOBALS["adodb_connection"]->Execute($query);
  $GLOBALS["adodb_lastresult"] = $result;
  if ($result) {
    return $result;
  } else {
    echo "<br>adodb.sq error with $query ";
    echo Sql_Error($GLOBALS['adodb_connection']->ErrorNo());
    // TODO Check if in transaction.
    echo "<br>adodb.sq FAIL";
    $GLOBALS['adodb_connection']->FailTrans();
    return 0;
  }
}

function Sql_Query_Params($query,$params,$ignore = 0) {
  $result = $GLOBALS["adodb_connection"]->Execute($query,$params);
  $GLOBALS["adodb_lastresult"] = $result;
  if ($result) {
    return $result;
  } else {
    echo "<br>adodb.sqp error with $query with params "; print_r( $params );
    echo Sql_Error($GLOBALS['adodb_connection']->ErrorNo());
    // TODO Check if in transaction.
    echo "<br>adodb.sqp FAIL";
    $GLOBALS['adodb_connection']->FailTrans();
    return 0;
  }
}

function Sql_Replace($table, $values, $pk, $autoquote=true) {
  $result = $GLOBALS['adodb_connection']->Replace($table, $values, $pk, $autoquote);
  $GLOBALS['adodb_lastresult'] = $result;
  if ($result) {
    return $result;
  } else {
    echo "<br>adodb.sq error with replace on table $table";
    echo Sql_Error($GLOBALS['adodb_connection']->ErrorNo());
    echo "<br>adodb.sq FAIL";
    $GLOBALS['adodb_connection']->FailTrans();
    return 0;
  }
}

function Sql_Verbose_Query($query) {
  if (preg_match("/dev$/",VERSION))
    print "<b>$query</b><br>\n";
  flush();
  return Sql_Query($query);
}

function Sql_Fetch_Array(&$dbresult) {
  $array = $dbresult->FetchRow();
  return $array;
}

function Sql_Fetch_Assoc($dbresult) {
  $row = $dbresult->FetchRow();
  return $row;
}

function Sql_Fetch_Row(&$dbresult) {
  $row = $dbresult->FetchRow();
  return $row;
}

function Sql_Fetch_Row_Query($query) {
#  print "Sql Fetch_Row $query<br/>";
  $req = Sql_Query($query);
  return Sql_Fetch_Row($req);
}

function Sql_Fetch_Array_Query($query) {
  $req = Sql_Query($query);
  return Sql_Fetch_Array($req);
}

function Sql_Affected_Rows() {
  # This should be removed when all appropriated Sql_Affected_Rows() are
  # converted to Sql_Num_Rows().
  # Inserts return ADORecordSet_empty withouth sql attribute.  This should 
  # probably be flipped to include just the record set type that selects
  # return.
  if (get_class($GLOBALS['adodb_lastresult']) != 'ADORecordSet_empty'
    && stripos($GLOBALS['adodb_lastresult']->sql, 'select') !== false)
    {
    echo "<br>adocb.Sql_Affected_Rows():  Use Sql_Num_Rows() after select queries";
    Sql_Error();
    }
  return $GLOBALS["adodb_connection"]->affected_rows();
}

function Sql_Num_Rows($result="") {
  return $result->RecordCount();
}

function Sql_Insert_Id($table, $column) {
  return $GLOBALS['adodb_connection']->insert_id($table, $column);
}

function Sql_Table_exists($table) {
  global $database_name, $database_schema;
  if (isset($GLOBALS["dbtables"]) && is_array($GLOBALS["dbtables"])) {
    if (isset($GLOBALS["dbtables"][$table]))
      return 1;
  }
  if (!isset($GLOBALS["dbtables"]) || !is_array($GLOBALS["dbtables"])) {
    $GLOBALS["dbtables"] = array();
    # adodb::MetaTables() doesn't honor search_path.
    # information_schema only lists tables that you have some permission on.
    $query
    = " select table_name"
    . " from information_schema.tables"
    . " where table_catalog = ?"
    . "   and table_schema = ?";
    $req = Sql_Query_Params($query,array($database_name,$database_schema));
    while ($row = Sql_Fetch_Row($req)) {
  #    print $row[0]."<br/>";
      $GLOBALS["dbtables"][$row[0]] = $row[0];
  #    if ($row[0] == $table)
  #      return 1;
    }
  }
  if (!isset($GLOBALS["dbtables"][$table]))
    echo "<br>adodb.ste <b>Need table $table</b>";
  return isset($GLOBALS["dbtables"][$table]);
}

function Sql_Table_Column_Exists($table,$column) {
  if (Sql_Table_exists($table)) {
    $req = Sql_Query("show columns from $table");
    while ($row = Sql_Fetch_Row($req)) {
      if ($row[0] == $column)
        return 1;
    }
  }
}

function Sql_Check_For_Table($table) {
  return Sql_Table_exists($table);
}

function Sql_create_Table ($table,$structure) {
  $query = "CREATE TABLE $table (\n";
  while (list($column, $val) = each($structure)) {
    $query .= "$column " . $structure[$column][0] . ",";
  }
  # get rid of the last ,
  $query = substr($query,0,-1);
  $query .= "\n)";
  # submit it to the database
  $res = Sql_Verbose_Query($query);
}

# Check if it exists first.
# Tables should have foreign key constraints.  Cascade will drop
# tables that depend on this table first.
function Sql_Drop_Table($tablename, $cascade='') {
  # If the dbtables stuff from Sql_Table_Exists is cleared up,
  # then just use that.
  $query
  . ' select count(*)'
  . ' from information_schema.tables'
  . ' where table_schema = ?'
  . '   and table_name = ?';
  $rs = Sql_Query_Params($query, array($GLOBALS['searchpath'], $tablename));
  $row = Sql_Fetch_Row($rs);
  if ($row[0] == 0)
    return;
//else if ($row[0] != 1)
//  throw an exception
  else {
    Sql_Query("drop table $tablename $cascade");
  }
}

function Sql_Start_Transaction() {
  $GLOBALS['adodb_connection']->StartTrans();
}

function Sql_Commit_Transaction() {
  $result = $GLOBALS['adodb_connection']->CompleteTrans();
  echo $result ? "COMMIT" : "ROLLBACK";
  return $result;
}


?>
