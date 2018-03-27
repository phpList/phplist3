<?php

function my_shutdown()
{
    // print "Shutting down";
    // print connection_status(); # with PHP 4.2.1 buggy. http://bugs.php.net/bug.php?id=17774
}

function output($msg)
{
    if ($GLOBALS['commandline']) {
        @ob_end_clean();
        $msg = str_replace('<br/>', "\n", $msg);
        echo strip_tags($msg);
        @ob_start();
    } else {
        echo $msg."\n";
    }
}

function parseImportPlaceHolders($templ, $data)
{
    $retval = $templ;
    foreach ($data as $key => $val) {
        $key = strtoupper($key);
        //  dbg('Parsing '.$key.' in '.$templ);
        if (!is_array($val)) {
            $retval = preg_replace('/\['.preg_quote($key).'\]/i', $val, $retval);
        }
    }

    return $retval;
}

function clearImport()
{
    if (isset($_SESSION['import_file']) && is_file($_SESSION['import_file'])) {
        unlink($_SESSION['import_file']);
    }
    unset($_SESSION['import_file']);
    unset($_SESSION['systemindex']);
    unset($_SESSION['import_attribute']);
    unset($_SESSION['test_import']);
    unset($_SESSION['assign_invalid']);
    unset($_SESSION['overwrite']);
    unset($_SESSION['grouptype']);
}

//# identify system values from the database structure
$system_attributes = array();
reset($DBstruct['user']);
foreach ($DBstruct['user'] as $key => $val) {
    if (strpos($val[1], 'sys') === false && is_array($val)) {
        $system_attributes[strtolower($key)] = $val[1];  //# allow columns like "htmlemail" and "foreignkey"
        $system_attributes_nicename[strtolower($val[1])] = $key; //# allow columns like "Send this user HTML emails" and "Foreign Key"
    } else {
        $colname = $val[1];
        if (strpos($colname, ':')) {
            list($sys, $colname) = explode(':', $val[1]);
        }
        $skip_system_attributes[strtolower($key)] = $colname;
    }
}
$subselect = ' where id > 0 ';
if ( !isSuperUser()) {
    $access = accessLevel('import2');
    if ($access == 'owner') {
        $subselect = ' where owner = '.$_SESSION['logindetails']['id'];
    } elseif ($access == 'all') {
        $subselect = ' where id > 0 ';
    } elseif ($access == 'none') {
        $subselect = ' where id = 0 ';
    }
}

//# handle terminology change (from user to subscriber)
$system_attributes['send this user html emails'] = $system_attributes_nicename['send this subscriber html emails'];
$system_attributes_nicename['send this user html emails'] = $system_attributes_nicename['send this subscriber html emails'];

//# allow mapping a column to a comma separated list of group names
$system_attributes['groupmapping'] = 'Group Membership';
if (isset($GLOBALS['config']['usergroup_types'])) {
    foreach ($GLOBALS['config']['usergroup_types'] as $grouptype => $typedesc) {
        if (!empty($grouptype)) {
            $system_attributes['grouptype_'.$grouptype] = $typedesc.' of group';
        }
    }
}
if (!isset($GLOBALS['assign_invalid_default'])) {
    $GLOBALS['assign_invalid_default'] = s('Invalid email').' [number]';
}

$system_attribute_reverse_map = array_reverse($system_attributes, true);
