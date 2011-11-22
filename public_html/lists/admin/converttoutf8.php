<?php

@ob_end_flush();

$dbname = $GLOBALS["database_name"];
if (empty($dbname)) {
  print "Cannot determine name of the database";
  exit;
}


Sql_Query('use information_schema');
$req = Sql_Query('select * from columns where table_schema = "'.$dbname.'" and CHARACTER_SET_NAME = "latin1"');

$columns = array();
$tables = array();
while ($row = Sql_Fetch_Assoc($req)) {
  ## make sure to only touch our own tables, in case we share with other applications
  if (in_array($row['TABLE_NAME'],array_values($GLOBALS['tables']))) {
    $columns[] = $row;
    $tables[$row['TABLE_NAME']] = $row['TABLE_NAME'];
  }
#  var_dump($row);
}

## this would be nice, but isn't allowed
#Sql_query('update columns set CHARACTER_SET_NAME = "utf8" where table_schema = "icarchivesdb" and CHARACTER_SET_NAME = "latin1"');

if (sizeof($tables) == 0) {
  print $GLOBALS['I18N']->get('No tables to process, your database has probably been converted already');
}

Sql_Query('use '.$dbname);

foreach ($tables as $table) {
  Sql_Query(sprintf('alter table %s charset utf8',$table));
}

foreach ($columns as $column) {
  print $column['TABLE_NAME']."<br/>";
  flush();

  ## first convert to binary to avoid Mysql doing character conversion

/*
  Sql_Query(sprintf('alter table %s change column %s %s blob(%s)',
    $column['TABLE_NAME'],$column['COLUMN_NAME'],$column['COLUMN_NAME'],$column['CHARACTER_MAXIMUM_LENGTH']));
*/


  Sql_Query(sprintf('alter table %s change column %s %s %s character set utf8',
    $column['TABLE_NAME'],$column['COLUMN_NAME'],$column['COLUMN_NAME'],$column['COLUMN_TYPE']));
}
    
