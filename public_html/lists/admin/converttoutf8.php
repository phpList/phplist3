<?php

## convert DB to UTF-8

$isUTF8 = getConfig('UTF8converted');

if (empty($isUTF8)) {
  
  set_time_limit(5000);
  
  print "Converting DB to use UTF-8, please wait<br/>";
  ## convert to UTF8
  $dbname = $GLOBALS["database_name"];
  if (!empty($dbname)) {
    ## the conversion complains about a key length
    Sql_Query(sprintf('alter table '.$GLOBALS['tables']['user_blacklist_data'].' change column email email varchar(150) not null unique'));

    Sql_Query('use information_schema');
    $req = Sql_Query('select * from columns where table_schema = "'.$dbname.'" and CHARACTER_SET_NAME != "utf8"');

    $dbcolumns = array();
    $dbtables = array();
    while ($row = Sql_Fetch_Assoc($req)) {
      ## make sure to only change our own tables, in case we share with other applications
      if (in_array($row['TABLE_NAME'],array_values($GLOBALS['tables']))) {
        $dbcolumns[] = $row;
        $dbtables[$row['TABLE_NAME']] = $row['TABLE_NAME'];
      }
    }

    Sql_Query('use '.$dbname);

    cl_output($GLOBALS['I18N']->get('Upgrading the database to use UTF-8, please wait'));
    foreach ($dbtables as $dbtable) {
      set_time_limit(600);
      cl_output($GLOBALS['I18N']->get('Upgrading table ').' '.$dbtable);
      Sql_Query(sprintf('alter table %s default charset utf8',$dbtable),1);
    }

    foreach ($dbcolumns as $dbcolumn) {
      set_time_limit(600);
      cl_output($GLOBALS['I18N']->get('Upgrading column ').' '.$dbcolumn['COLUMN_NAME']);
      Sql_Query(sprintf('alter table %s change column %s %s %s character set utf8',
        $dbcolumn['TABLE_NAME'],$dbcolumn['COLUMN_NAME'],$dbcolumn['COLUMN_NAME'],$dbcolumn['COLUMN_TYPE']),1);
    }
    cl_output($GLOBALS['I18N']->get('upgrade to UTF-8, done'));
    saveConfig('UTF8converted',date('Y-m-d H:i'),0);
  } else {
    print "Unable to determine the name of the database to convert";
  }
} else {
  print "The DB was already converted to UTF-8 on ".$isUTF8;
}

print "All Done";

