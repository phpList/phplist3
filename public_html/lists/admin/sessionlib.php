<?php

require_once dirname(__FILE__).'/accesscheck.php';

// try to set the configuration
if (empty($GLOBALS['SessionTableName'])) {
    return;
}

// only necessary on main pages, not in lt/dl etc
if (basename($_SERVER['SCRIPT_NAME']) != 'index.php') {
    return;
}

$SessionTableName = $GLOBALS['SessionTableName'];


session_set_save_handler(
	'mysql_session_open',
	'mysql_session_close',
	'mysql_session_read',
	'mysql_session_write',
	'mysql_session_destroy',
	'mysql_session_gc'
);

if (!Sql_Table_exists($GLOBALS['SessionTableName'])) {
    Sql_Create_Table($GLOBALS['SessionTableName'], array(
        'sessionid'  => array('CHAR(32) NOT NULL PRIMARY KEY', ''),
        'lastactive' => array('INTEGER NOT NULL', ''),
        'data'       => array('LONGTEXT', ''),
    ));
}

function mysql_session_open($save_path, $session_name)
{
    return true;
}

function mysql_session_close()
{
    return true;
}

function mysql_session_read($SessionID)
{
    //	dbg("Reading session info for $SessionID");
    $SessionTableName = $GLOBALS['SessionTableName'];
    $SessionID = addslashes($SessionID);

    $session_data_req = sql_query("SELECT data FROM $SessionTableName WHERE sessionid = '$SessionID'");
    if (Sql_Affected_Rows() == 1) {
        $data = Sql_Fetch_Row($session_data_req);

        return $data[0];
    } else {
        return '';
    }
}

function mysql_session_write($SessionID, $val)
{
    //	dbg("writing session info for $SessionID");
    $SessionTableName = $GLOBALS['SessionTableName'];
    $SessionID = addslashes($SessionID);
    $val = addslashes($val);

    $SessionExists = sql_fetch_row_query("select count(*) from  $SessionTableName where sessionid = '$SessionID'");
    if ($SessionExists[0] == 0) {
        $retval = sql_query(sprintf('insert into %s (sessionid,lastactive,data) values("%s",UNIX_TIMESTAMP(NOW()),"%s")',
            $SessionTableName, $SessionID, $val));
    } else {
        $retval = sql_query(sprintf('update %s SET data = "%s", lastactive = UNIX_TIMESTAMP(NOW()) where sessionid = "%s"',
            $SessionTableName, $val, $SessionID));
        if (sql_affected_rows() < 0) {
            sendError("unable to update session data for session $SessionID");
        }
    }

    return $retval;
}

function mysql_session_destroy($SessionID)
{
    $SessionTableName = $GLOBALS['SessionTableName'];
    $SessionID = addslashes($SessionID);
    $retval = sql_query("DELETE FROM $SessionTableName WHERE sessionid = '$SessionID'");

    return $retval;
}

function mysql_session_gc($maxlifetime = 300)
{
    $SessionTableName = $GLOBALS['SessionTableName'];
    $CutoffTime = time() - $maxlifetime;
    $retval = sql_query("DELETE FROM $SessionTableName WHERE lastactive < $CutoffTime");

    return $retval;
}
