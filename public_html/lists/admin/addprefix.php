<?php

require_once dirname(__FILE__).'/accesscheck.php';

// convert the database to add the prefix
// dangerous, should not be used, particularly not if the prefix already exists

if (!$GLOBALS['table_prefix']) {
    echo 'No prefix defined, nothing to do';

    return;
}

include 'structure.php';

foreach ($DBstruct as $table => $value) {
    if ($table != $tables[$table]) {
        Sql_Verbose_Query("drop table if exists $tables[$table]", 0);
        Sql_Verbose_Query("alter table $table rename $tables[$table]", 0);
    }
}

$req = Sql_Verbose_Query('select tablename from '.$tables['attribute']);
while ($row = Sql_Fetch_Row($req)) {
    set_time_limit(500);
    if (Sql_Table_Exists('listattr_'.$row[0]) && $table_prefix) {
        Sql_Verbose_Query("alter table listattr_$row[0] rename $table_prefix".'listattr_'.$row[0]);
    }
    if (Sql_Table_Exists($table_prefix.'listattr_'.$row[0])) {
        Sql_Query("alter table $table_prefix".'listattr_'.$row[0].' add column listorder integer default 0', 0);
    }
}
flush();
