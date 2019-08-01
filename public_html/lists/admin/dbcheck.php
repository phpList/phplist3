<?php

include dirname(__FILE__).'/structure.php';
if (!defined('PHPLISTINIT')) {
    exit;
}

unset($_SESSION['dbtables']);
$pass = true;

$ls = new WebblerListing(s('Database structure'));
$ls->setElementHeading(s('Table'));
foreach ($GLOBALS['tables'] as $table => $tablename) {
    $createlink = '';
    $indexes = $uniques = $engine = $category = '';

    $ls->addElement($table);
    if ($table != $tablename) {
        $ls->addColumn($table, s('real name'), $tablename);
    }
    if (Sql_Table_Exists($tablename)) {
        $req = Sql_Query("show columns from $tablename", 0);
        $columns = array();
        if (!Sql_Affected_Rows()) {
            $ls->addColumn($table, 'exist', $GLOBALS['img_cross']);
        }
        while ($row = Sql_Fetch_Array($req)) {
            $columns[strtolower($row['Field'])] = $row['Type'];
        }
        $tls = new WebblerListing($table);
        if (isset($DBstruct[$table])) {
            $struct = $DBstruct[$table];
        } else {
            $struct = '';
        }
        $haserror = 0;
        if (is_array($struct)) {
            $indexes = $uniques = $engine = $category = '';
            foreach ($struct as $column => $colstruct) {
                if (strpos($column, 'index_') === false &&
                    strpos($column, 'unique_') === false &&
                    $column != 'primary key' &&
                    $column != 'storage_engine' &&
                    $column != 'table_category'
                ) {
                    $tls->addElement($column);
                    $exist = isset($columns[strtolower($column)]);
                    if ($exist) {
                        $tls->addColumn($column, s('exist'), $GLOBALS['img_tick']);
                    } else {
                        $haserror = 1;
                        $tls->addColumn($column, s('exist'), $GLOBALS['img_cross']);
                    }
                } else {
                    if (strpos($column, 'index_') !== false) {
                        $indexes .= $colstruct[0].'<br/>';
                    }
                    if (strpos($column, 'unique_') !== false) {
                        $uniques .= $colstruct[0].'<br/>';
                    }
                    //          if ($column == "primary key")
                    if ($column == 'storage_engine') {
                        $engine = $colstruct[0];
                    }
                    if ($column == 'table_category') {
                        $category = $colstruct;
                    }
                }
            }
        }
    } else {
        $haserror = true;
        unset($tls);
        $createlink = PageUrl2('pageaction&action=createtable&table='.urlencode($table));
    }
    if (!$haserror) {
        $tls->collapse();
        $ls->addColumn($table, s('ok'), $GLOBALS['img_tick']);
    } else {
        $pass = false;
        $ls->addColumn($table, s('ok'), $GLOBALS['img_cross']);
    }
    if (!empty($indexes)) {
        $ls->addColumn($table, s('index'), $indexes);
    }
    if (!empty($uniques)) {
        $ls->addColumn($table, s('unique'), $uniques);
    }
    if (!empty($category)) {
        $ls->addColumn($table, s('category'), $category);
    }
    if (!empty($tls)) {
        $ls->addColumn($table, s('check'), $tls->display());
    }
    /*
      if (!empty($createlink)) {
        $ls->addColumn($table,"create",'<div> Table is missing <a href="'.$createlink.'" class="ajaxable">Create</a></div>');
      }
    */
}
echo $ls->display();
if ($pass) {
    cl_output('PASS');
    $dbversion = getConfig('version');
    if (empty($dbversion)) {
        SaveConfig('version', VERSION, 0);
        if (defined('RELEASEDATE')) {
            SaveConfig('releaseDBversion', RELEASEDATE, 0);
        }
    }
} else {
    cl_output('FAIL');
}
