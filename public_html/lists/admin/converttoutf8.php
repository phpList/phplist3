<?php

$isUTF8 = getConfig('UTF8converted');

## convert DB to UTF-8
if (!$GLOBALS['commandline']) {
    ob_end_flush();
    # make sure the browser doesn't buffer it
    for ($i = 0; $i < 10000; ++$i) {
        print ' ' . "\n";
    }
}

if (isset($cline['f'])) { ## force
    unset($isUTF8);
}

## check diskspace. this operation duplicates the space required.
$maxsize = 0;
$req = Sql_Query('select (data_length+index_length) tablesize 
  from information_schema.tables
  where table_schema="' . $GLOBALS['database_name'] . '"');

while ($row = Sql_Fetch_Assoc($req)) {
    if ($row['tablesize'] > $maxsize) {
        $maxsize = $row['tablesize'];
    }
}
$maxsize = (int)$maxsize;
$avail = disk_free_space('/'); ## we have no idea where MySql stores the data, so this is only a crude check and warning.

$maxsize = (int)($maxsize * 1.2); ## add another 20%

$require_confirmation = !isset($_GET['force']) || $_GET['force'] != 'yes';

if ($maxsize > $avail && $require_confirmation) {
    print '<div class="error">' . s('Converting to UTF-8 requires sufficient diskspace on your system.') . '<br/>';
    print s('The maximum table size in your system is %s and space available on the root filesystem is %s, which means %s is required.',
        formatBytes($maxsize), formatBytes($avail), formatBytes($maxsize - $avail));
    print ' ' . s('This is not a problem if your Database server is on a different filesystem. Click the button to continue.');

    print ' ' . s('Otherwise, free up some diskspace and try again');
    print '<br/>' . PageLinkButton('converttoutf8&force=yes', s('Confirm UTF8 conversion'));

    print '</div>';

    return;
}

cl_output(s('Converting DB to use UTF-8, please wait'));

if (empty($isUTF8)) {
    set_time_limit(5000);

    print s('Converting DB to use UTF-8, please wait') . '<br/>';
    ## convert to UTF8
    $dbname = $GLOBALS['database_name'];
    if (!empty($dbname)) {
        ## the conversion complains about a key length
        Sql_Query(sprintf('alter table ' . $GLOBALS['tables']['user_blacklist_data'] . ' change column email email varchar(150) not null unique'));

        $req = Sql_Query('select * from information_schema.columns where table_schema = "' . $dbname . '" and CHARACTER_SET_NAME != "utf8"');

        $dbcolumns = array();
        $dbtables = array();
        while ($row = Sql_Fetch_Assoc($req)) {
            ## make sure to only change our own tables, in case we share with other applications
            if (in_array($row['TABLE_NAME'], array_values($GLOBALS['tables']))) {
                $dbcolumns[] = $row;
                $dbtables[$row['TABLE_NAME']] = $row['TABLE_NAME'];
            }
        }

        cl_output($GLOBALS['I18N']->get('Upgrading the database to use UTF-8, please wait'));
        foreach ($dbtables as $dbtable) {
            set_time_limit(600);
            print($GLOBALS['I18N']->get('Upgrading table ') . ' ' . $dbtable) . '<br/>';
            flush();
            cl_output($GLOBALS['I18N']->get('Upgrading table ') . ' ' . $dbtable);
            Sql_Query(sprintf('alter table %s default charset utf8', $dbtable), 1);
        }

        foreach ($dbcolumns as $dbcolumn) {
            set_time_limit(600);
            print($GLOBALS['I18N']->get('Upgrading column ') . ' ' . $dbcolumn['COLUMN_NAME']) . '<br/>';
            flush();
            cl_output($GLOBALS['I18N']->get('Upgrading column ') . ' ' . $dbcolumn['COLUMN_NAME']);
            Sql_Query(sprintf('alter table %s change column %s %s %s character set utf8',
                $dbcolumn['TABLE_NAME'], $dbcolumn['COLUMN_NAME'], $dbcolumn['COLUMN_NAME'], $dbcolumn['COLUMN_TYPE']),
                1);
        }
        cl_output($GLOBALS['I18N']->get('upgrade to UTF-8, done'));
        saveConfig('UTF8converted', date('Y-m-d H:i'), 0);
    } else {
        print s('Unable to determine the name of the database to convert');
    }
} else {
    print s('The DB was already converted to UTF-8 on') . ' ' . $isUTF8;
    cl_output(s('The DB was already converted to UTF-8 on') . ' ' . $isUTF8);
}

print '<br/>' . s('All Done');
