<?php

require_once dirname(__FILE__).'/accesscheck.php';
if (!ALLOW_IMPORT) {
    echo '<p class="information">'.$GLOBALS['I18N']->get('import is not available').'</p>';

    return;
}

if (!defined('PHPLISTINIT')) {
    exit;
}

ignore_user_abort();
set_time_limit(500);
$illegal_cha = array(
    ',',
    ';',
    ':',
    '#',
    "\t",
);
$attributes = array();

if (!isset($everyone_groupid)) {
    $everyone_groupid = -1;
}

if (!is_dir($GLOBALS['tmpdir']) || !is_writable($GLOBALS['tmpdir'])) {
    Warn(sprintf($GLOBALS['I18N']->get('The temporary directory for uploading (%s) is not writable, so import will fail'),
        $GLOBALS['tmpdir']));
}
$post_max_size = phpcfgsize2bytes(ini_get('post_max_size'));
$file_max_size = phpcfgsize2bytes(ini_get('upload_max_filesize'));

if (($post_max_size < $file_max_size) && WARN_ABOUT_PHP_SETTINGS) {
    Warn(sprintf(s('The maximum POST size is smaller than the maximum upload filesize. If your upload file is too large, import will fail. See the PHP documentation at <a href="%s">%s</a>',
        'http://php.net/post_max_size', 'http://php.net/post_max_size')));
}

require dirname(__FILE__).'/structure.php';
require dirname(__FILE__).'/inc/importlib.php';
require dirname(__FILE__).'/CsvReader.php';
register_shutdown_function('my_shutdown');

if (!defined('WEBBLER')) {
    ob_end_flush();
}
if (!empty($_GET['reset']) && $_GET['reset'] == 'yes') {
    clearImport();
    echo '<h3>'.$GLOBALS['I18N']->get('Import cleared').'</h3>';
    echo PageLinkButton('import2', s('Continue'));

    return;
} else {
    // if ((!empty($_SESSION["import_file"]) || !empty($_SESSION["test_import"])) && (!isset($_GET['confirm']) || $_GET['confirm'] != 'yes')) {
    $button = new ConfirmButton(
        s('Are you sure you want to reset the import session?'),
        PageURL2('import2&reset=yes', 'reset', ''),
        s('Reset Import session'),'','btn btn-lg confirm');

    echo '<div class="fright row pull-right text-right col-sm-5 col-xs-12 ">'.$button->show().'</div>';
    // }
}
if (isset($_POST['import'])) {
    if (!verifyToken()) {
        echo Error(s('Invalid security token, please reload the page and try again'),
            'http://resources.phplist.com/documentation/errors/securitytoken');

        return;
    }

    $test_import = (isset($_POST['import_test']) && $_POST['import_test'] == 'yes');
    $_SESSION['test_import'] = $test_import;

    if (!$_FILES['import_file']) {
        Fatal_Error($GLOBALS['I18N']->get('File is either too large or does not exist.'));

        return;
    }
    if (empty($_FILES['import_file'])) {
        Fatal_Error($GLOBALS['I18N']->get('No file was specified. Maybe the file is too big? '));

        return;
    }

    //# disallow some extensions. Won't avoid all problems, but will help with the most common ones.
    $extension = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));
    if (in_array($extension, array('xls', 'ods', 'ots', 'fods', 'xlsx', 'xlt', 'dif', 'dbf', 'html', 'slk'))) {
        Fatal_Error(s('Please upload a plain text file only. You cannot use a spreadsheet. You need to export the data from the spreadsheet into a TAB delimited text file'));

        return;
    }

    if (!defined('IMPORT_FILESIZE')) {
        define('IMPORT_FILESIZE', 1);
    }
    if (!$GLOBALS['commandline'] && filesize($_FILES['import_file']['tmp_name']) > (IMPORT_FILESIZE * 1000000)) {
        // if we allow more, we will certainly run out of memory
        Fatal_Error($GLOBALS['I18N']->get('File too big, please split it up into smaller ones'));

        return;
    }
    /*
      if (!preg_match("/^[0-9A-Za-z_\.\-\s \(\)]+$/", $_FILES["import_file"]["name"])) {
        Fatal_Error($GLOBALS['I18N']->get('Use of wrong characters in filename, allowed are: ') . "0-9A-Za-z[SPACE]_.-()");
        return;
      }
    */

    //# set notify to always "no". Confirmation should run through the first campaing
    $_POST['notify'] = 'no';
    if (!$_POST['notify'] && !$test_import) {
        Fatal_Error($GLOBALS['I18N']->get('Please choose whether to sign up immediately or to send a notification'));

        return;
    } else {
        $_SESSION['notify'] = $_POST['notify'];
    }

    if ($_FILES['import_file'] && $_FILES['import_file']['size'] > 10) {
        //  $newfile = $GLOBALS['tmpdir'] . '/' . basename($_FILES['import_file']['name']) . time();
        $newfile = $GLOBALS['tmpdir'].'/'.'csvimport'.$GLOBALS['installation_name'].time();
        if (!$GLOBALS['commandline']) {
            move_uploaded_file($_FILES['import_file']['tmp_name'], $newfile);
        } else {
            copy($_FILES['import_file']['tmp_name'], $newfile);
        }
        $_SESSION['import_file'] = $newfile;
        if (!($fp = fopen($newfile, 'r'))) {
            Fatal_Error(sprintf($GLOBALS['I18N']->get('Cannot read %s. file is not readable !'), $newfile));

            return;
        }
        fclose($fp);
    } elseif ($_FILES['import_file']) {
        Fatal_Error($GLOBALS['I18N']->get('Something went wrong while uploading the file. Empty file received. Maybe the file is too big, or you have no permissions to read it.'));

        return;
    }
    if (isset($_POST['import_record_delimiter']) && $_POST['import_record_delimiter'] != '') {
        $_SESSION['import_record_delimiter'] = $_POST['import_record_delimiter'];
    } else {
        $_SESSION['import_record_delimiter'] = "\n";
    }

    if (!isset($_POST['import_field_delimiter']) || $_POST['import_field_delimiter'] == '' || $_POST['import_field_delimiter'] == 'TAB') {
        $_SESSION['import_field_delimiter'] = "\t";
    } else {
        $_SESSION['import_field_delimiter'] = $_POST['import_field_delimiter'];
    }
    $_SESSION['show_warnings'] = !empty($_POST['show_warnings']);
    $_SESSION['assign_invalid'] = $_POST['assign_invalid'];
    $_SESSION['omit_invalid'] = !empty($_POST['omit_invalid']);
    if (isset($_POST['lists']) && is_array($_POST['lists'])) {
        $_SESSION['lists'] = getSelectedLists('lists');
    } else {
        $_SESSION['lists'] = array();
    }
    if (isset($_POST['groups']) && is_array($_POST['groups'])) {
        $_SESSION['groups'] = $_POST['groups'];
    } else {
        $_SESSION['groups'] = array();
    }
    $_SESSION['grouptype'] = isset($_POST['grouptype']) ? sprintf('%d', $_POST['grouptype']) : '';
    $_SESSION['overwrite'] = !empty($_POST['overwrite']);
    $_SESSION['notify'] = $_POST['notify']; // yes or no
    // $_SESSION["listname"] = $_POST["listname"];
    $_SESSION['retainold'] = !empty($_POST['retainold']);
    $_SESSION['throttle_import'] = !empty($_POST['throttle_import']) ? sprintf('%d', $_POST['throttle_import']) : 0;
}

if (!empty($_GET['confirm'])) {
    $_SESSION['test_import'] = '';
}

if (!empty($_SESSION['import_file'])) {
    // output some stuff to make sure it's not buffered in the browser
    for ($i = 0; $i < 10000; ++$i) {
        echo '  '."\n";
    }
    output('<p class="information">'.$GLOBALS['I18N']->get('Reading emails from file ... '));
    flush();
    if (filesize($_SESSION['import_file']) > 50000) {
        @ini_set('memory_limit', memory_get_usage() + 50 * filesize($_SESSION['import_file']));
    }
    flush();

    if (!isset($_SESSION['import_attribute'])) {
        $_SESSION['import_attribute'] = array();
    }
    $csvReader = new CsvReader($_SESSION['import_file'], $_SESSION['import_field_delimiter']);
    $total = $csvReader->totalRows();
    output(sprintf('..'.$GLOBALS['I18N']->get('ok, %d lines').'</p>', $total));
    --$total; // now the number of subscribers to be imported
    $headers = $csvReader->getRow();
    $headers = array_unique($headers);
    $_SESSION['columnnames'] = $headers;

    //# possibly rewrite "nice" header names to short ones
    foreach ($headers as $headerid => $headerline) {
        if (in_array(strtolower($headerline), array_keys($system_attributes_nicename))) {
            $headers[$headerid] = $system_attributes_nicename[strtolower($headerline)];
        }
    }

    //## Compare header line with system and user attributes
    //# build user_attributes array
    $req = Sql_Query(sprintf('select id,name from %s order by listorder,name', $tables['attribute']));
    while ($row = Sql_Fetch_Array($req)) {
        $attributes[$row['id']] = $row['name'];
    }
    $used_systemattr = array();
    $used_attributes = array();

    if (isset($_SESSION['systemindex'])) {
        foreach ($_SESSION['systemindex'] as $system_att => $columnID) {
            $used_systemattr[] = strtolower($system_att);
        }
    }

//  var_dump($system_attributes);
    $system_attribute_reverse_map = array();
    for ($i = 0; $i < count($headers); ++$i) {
        $column = strip_tags($headers[$i]);
        //  print $i."<h3>$column</h3>".$_POST['column'.$i].'<br/>';
        $column = preg_replace('#/#', '', $column);
//    $dbg = "Field $i: $headers[$i] - $column - form/option:" . $_POST['column' . $i];
        if (in_array(strtolower($column), array_keys($system_attributes))
            || (isset($_POST['column'.$i]) && in_array(strtolower($_POST['column'.$i]),
                    array_keys($system_attributes)))
            || (isset($_SESSION['systemindex']) && in_array($i, array_values($_SESSION['systemindex'])))
        ) {
            //   print "System $column ".$_POST['column'.$i]. "=> $i<br/>";
            if (isset($_POST['column'.$i])) {
                $_SESSION['systemindex'][$_POST['column'.$i]] = $i;
            } else {
                $_SESSION['systemindex'][$column] = $i;
            }
            unset($_SESSION['import_attribute'][$column]);
            $system_attribute_reverse_map[strtolower($column)] = $i;
            array_push($used_systemattr, strtolower($column));
//      $dbg .= " =system";
        } elseif (strtolower($column) == 'list membership' ||
            (isset($_POST['column'.$i]) && $_POST['column'.$i] == 'skip') ||
            in_array($column, array_keys($skip_system_attributes)) ||
            in_array($column, array_values($skip_system_attributes))
        ) {
            //# skip was chosen or it's list membership, which we don't want to import since it's too complicated.
            $_SESSION['import_attribute'][$column] = array(
                'index'  => $i,
                'record' => 'skip',
                'column' => "$column",
            );
            array_push($used_systemattr, strtolower($column));
//      $dbg .= " =skip";
        } else {
            if (isset($_SESSION['import_attribute'][$column]['record']) && $_SESSION['import_attribute'][$column]['record']) {
                //# mapping has been defined
//        print $column.' is set<br/>';
//        $dbg .= " =known mapping in session: " . $_SESSION["import_attribute"][$column]["record"];
            } elseif (isset($_POST["column$i"])) {
                //# newly posted mapping
                if (in_array(strtolower($_POST['column'.$i]), array_keys($system_attributes))) {
                    //  print 'SYSTEM: '.$i. ' '.$_POST['column'.$i].'<br/>';
                    $type = '';
                    if (strpos($_POST['column'.$i], 'grouptype_') === 0) {
                        list($t, $type) = explode('_', $_POST['column'.$i]);
                        $type = sprintf('%d', $type);
                        $_SESSION['systemindex']['grouptype_'.$type] = $i;
                        $_SESSION['import_attribute'][$column] = array(
                            'index'  => $i,
                            'record' => 'grouptype_'.$type,
                            'column' => $column,
                            'type'   => $type,
                        );
                        array_push($used_systemattr, strtolower('grouptype_'.$type));
                    } else {
                        //  print "COLUMMN $column = $i<br/>";
                        $_SESSION['systemindex'][$column] = $i;
                        $_SESSION['import_attribute'][$column] = array(
                            'index'  => $i,
                            'record' => 'system',
                            //strtolower($column),#$system_attribute_reverse_map[strtolower($column)],
                            'column' => "$column",
                        );
                        array_push($used_systemattr, strtolower($_POST['column'.$i]));
                    }
                } else {
                    //# attribute mapping was chosen
                    $_SESSION['import_attribute'][$column] = array(
                        'index'  => $i,
                        'record' => $_POST["column$i"],
                        'column' => "$column",
                    );
                }
//        $dbg .= " =mapping chosen";
            } else {
                //# define mapping based on existing attribute or ask for it
                //@@ Why is $attributes not used
                $existing = Sql_Fetch_Row_Query('select id from '.$tables['attribute']." where name = \"$column\"");
                $_SESSION['import_attribute'][$column] = array(
                    'index'  => $i,
                    'record' => $existing[0],
                    'column' => $column,
                );
                array_push($used_attributes, $existing[0]);
                if ($existing[0]) {
                    //        $dbg .= " =known attribute id=" . $existing[0];
                } else {
                    //          $dbg .= " =request mapping";
                }
            }
        }
        //dbg('Imported column',$dbg, 'import'); //debug
    }
//  dbg($_SESSION["import_attribute"]);
//  var_dump($_SESSION["import_attribute"] );exit;

// var_dump($_SESSION);
//var_dump($used_systemattr);exit;
    //## build option list from known attributes
    $unused_systemattr = array_diff(array_keys($system_attributes), $used_systemattr);
    $unused_attributes = array_diff(array_keys($attributes), $used_attributes);
    $options = '<option value="new">-- '.$GLOBALS['I18N']->get('Create new one').'</option>';
    $options .= '<option value="skip">-- '.$GLOBALS['I18N']->get('Skip Column').'</option>';
    foreach ($unused_systemattr as $sysindex) {
        $options .= sprintf('<option value="%s">%s</option>', $sysindex, substr($system_attributes[$sysindex], 0, 25));
    }
    foreach ($unused_attributes as $attindex) {
        $options .= sprintf('<option value="%s">%s</option>', $attindex,
            substr(stripslashes($attributes[$attindex]), 0, 25));
    }
    //## use above selector for each unknown imported attribute
    $ls = new WebblerListing($GLOBALS['I18N']->get('Import Attributes'));
    $request_mapping = 0;
// var_dump($_SESSION["import_attribute"]);
// var_dump($_SESSION["systemindex"]);
// var_dump( $used_systemattr);
    foreach ($_SESSION['import_attribute'] as $column => $rec) {
        /*
      print '<pre>';
      print '<br/>'.$column.'<br/>';
      var_dump($rec);
      print '</pre>';
  */
        if (trim($column) != '' && !$rec['record']) {
            $request_mapping = 1;
            $ls->addElement($column);
            $ls->addColumn($column, $GLOBALS['I18N']->get('select'),
                '<select name="column'.$rec['index'].'">'.$options.'</select>');
        }
    }
    if ($request_mapping) {
        $ls->addButton($GLOBALS['I18N']->get('Continue'), 'javascript:document.importform.submit()');
        echo '<p class="information">'.$GLOBALS['I18N']->get('Please identify the target of the following unknown columns').'</p>';
        echo '<form name="importform" method="post">';
        echo $ls->display();
        echo '</form>';

        /*
            print '<pre>';
            var_dump($_SESSION['import_attribute']);
            print '</pre>';
        */

        return;
    }
}

//## show summary
if (!empty($_SESSION['test_import'])) {
    if (!isset($_SESSION['systemindex']['email'])) {
        Fatal_Error($GLOBALS['I18N']->get('Cannot find column with email, you need to map at least one column to "Email"'),
            'http://resources.phplist.com/documentation/errors/importemailmapping');

        return;
    }
    $ls = new WebblerListing($GLOBALS['I18N']->get('Summary'));
    foreach ($_SESSION['systemindex'] as $column => $columnid) {
        $ls->addElement($_SESSION['columnnames'][$columnid]);
        $ls->addColumn($_SESSION['columnnames'][$columnid], $GLOBALS['I18N']->get('maps to'), 'system: '.$column);
    }
    foreach ($_SESSION['import_attribute'] as $column => $rec) {
        if (trim($column) != '') {
            $column = htmlspecialchars($column);
            $ls->addElement($column);
            if ($rec['record'] == 'new') {
                $ls->addColumn($column, $GLOBALS['I18N']->get('maps to'),
                    $GLOBALS['I18N']->get('Create new Attribute'));
            } elseif ($rec['record'] == 'skip') {
                $ls->addColumn($column, $GLOBALS['I18N']->get('maps to'), $GLOBALS['I18N']->get('Skip Column'));
            } elseif (is_numeric($rec['record'])) {
                $ls->addColumn($column, $GLOBALS['I18N']->get('maps to'), $attributes[$rec['record']]);
            } elseif (!empty($rec['record'])) {
                $ls->addColumn($column, $GLOBALS['I18N']->get('maps to'), $rec['record']);
            } else {
                $ls->addColumn($column, $GLOBALS['I18N']->get('maps to'), $GLOBALS['I18N']->get('none'));
            }
        }
    }
    echo $ls->display();
    //var_dump($_SESSION["import_attribute"]);
// print "SYSTEM INDEX";
// var_dump($_SESSION["systemindex"]);

    echo '<h3>';
    printf($GLOBALS['I18N']->get('%d lines will be imported'), $total);
    echo '</h3>';
    echo '<p>'.PageLinkButton($_GET['page'].'&amp;confirm=yes', $GLOBALS['I18N']->get('Confirm Import')).'</p>';
    echo '<h3>'.$GLOBALS['I18N']->get('Test Output').'</h3>';
//  dbg($_SESSION["import_attribute"]);
} elseif (isset($_GET['confirm']) || isset($_POST['import'])) {
    echo '<h3>'.s('Importing %d subscribers to %d lists, please wait', $total,
            count($_SESSION['lists'])).'</h3>';
    echo $GLOBALS['img_busy'];
    echo '<div id="progresscount" style="width: 200; height: 50;">Progress</div>';
    echo '<br/> <iframe id="import2" src="./?page=pageaction&action=import2&ajaxed=true'.addCsrfGetToken().'" scrolling="no" height="5" width="100"></iframe>';

    return;
}

//var_dump($system_attributes);
//## show progress and adjust working space
if (!empty($_SESSION['test_import'])) {
    $import_field_delimiter = $_SESSION['import_field_delimiter'];
    if ($total > 300 && !$_SESSION['test_import']) {
        // this is a possibly a time consuming process, so show a progress bar
        echo '<script language="Javascript" type="text/javascript"> document.write(progressmeter); start();</script>';
        flush();
        // increase the memory to make sure we are not running out
        ini_set('memory_limit', '32M');
    }

    //## store the chosen mappings in the $system_attribute_mapping list
    // print "A: ".sizeof($import_attribute);
    foreach ($system_attributes as $key => $val) {
        if (isset($_SESSION['systemindex'][$key])) {
            $system_attribute_mapping[$key] = $_SESSION['systemindex'][$key];
        }
    }

    // #Bas bugfix 0008106: import 'foreignkey' fails; bad SQL
    // When the user chose to map an unknown import attribute to a system attribute this attribute ends up in
    // $_SESSION["import_attribute"]. This code moves the attribute to the system mappings array

    /*
      foreach ($_SESSION["import_attribute"] as $column => $item) {
        if (!is_numeric($item["record"]) && $item["record"] != 'new' && $item["record"] != 'skip') {
          $system_attribute_mapping[$item["record"]] = $item["index"];
          unset ($_SESSION["import_attribute"][$column]);
        };
      };
    */

    //  dbg('$system_attribute_mapping', $system_attribute_mapping); //debug
    //  dbg('$_SESSION["import_attribute"]',$_SESSION["import_attribute"]); //debug

    //## Parse the lines into records
    //  print "<br/>Loading emails .. ";
    flush();
    $count = array();
    $count['email_add'] = 0;
    $count['exist'] = 0;
    $count['list_add'] = 0;
    $count['group_add'] = 0;
    $c = 1;
    $count['invalid_email'] = 0;
    $num_lists = count($_SESSION['lists']);
    $cnt = 0;
    $count['emailmatch'] = 0;
    $count['fkeymatch'] = 0;
    $count['dataupdate'] = 0;
    $count['duplicate'] = 0;
    $additional_emails = 0;

    while ($values = $csvReader->getRow()) {
        set_time_limit(60);
        // will contain attributes to store / change
        $user = array();
        $system_values = array();
        foreach ($system_attribute_mapping as $column => $index) {
            // print '<br/>'.$column . ' = '. $values[$index];
            if (!empty($values[$index])) {
                $system_values[$column] = $values[$index];
            } else {
                $system_values[$column] = '';
            }
        }
        //# Check completeness
        $index = clean($system_values['email']);
        $invalid = 0;
        if (!$index) {
            if ($_SESSION['show_warnings']) {
                Warn($GLOBALS['I18N']->get('Record has no email').
                    ": $c -> $line");
            }
            $index = $GLOBALS['I18N']->get('Invalid Email')." $c";
            $system_values['email'] = $_SESSION['assign_invalid'];
            $invalid = 1;
            ++$count['invalid_email'];
        }

        //if (sizeof($values) != (sizeof($_SESSION["import_attribute"]) + sizeof($system_attributes) - sizeof($unused_systemattr)) && !empty($_SESSION['test_import']) && !empty($_SESSION["show_warnings"]))
        //Warn("Record has more values than header indicated (" .
        //sizeof($values) . "!=" .
        //(sizeof($_SESSION["import_attribute"]) + sizeof($system_attributes) - sizeof($unused_systemattr)) .
        //"), this may cause trouble: $index");
        if (!$invalid || ($invalid && $_SESSION['omit_invalid'] != 'yes')) {
            $user['systemvalues'] = $system_values;
            reset($_SESSION['import_attribute']);
            $replace = array();
            foreach ($_SESSION['import_attribute'] as $key => $val) {
                if (!empty($values[$val['index']])) {
                    $user[$val['index']] = htmlspecialchars($values[$val['index']]);
                    $replace[$key] = htmlspecialchars($values[$val['index']]);
                }
            }
        } else {
            // Warn("Omitting invalid one: $email");
        }
        $user['systemvalues']['email'] = parseImportPlaceHolders($system_values['email'],
            array_merge($replace, $system_values, array(
                'number' => $c,
            )));
        $user['systemvalues']['email'] = cleanEmail($user['systemvalues']['email']);
        ++$c;
        if (!isset($user['systemvalues']['htmlemail'])) {
            $user['systemvalues']['htmlemail'] = 1;
        }
        if ($_SESSION['test_import']) {

            //   var_dump($user["systemvalues"]);#exit;
            $html = '';
            foreach ($user['systemvalues'] as $column => $value) {
                if (strpos($column, 'grouptype_') === 0) {
                    if (isset($system_attributes[$column])) {
                        $column = $system_attributes[$column];
                    }
                }
                if (!empty($column)) {
                    if ($value) {
                        $html .= "$column -> $value<br/>\n";
                    } else {
                        $html .= "$column -> ".$GLOBALS['I18N']->get('clear value')."<br/>\n";
                    }
                }
            }
            //    var_dump($_SESSION["systemindex"]);

            reset($_SESSION['import_attribute']);
            foreach ($_SESSION['import_attribute'] as $column => $item) {
                if (!empty($user[$item['index']])) {
                    if ($item['record'] == 'new') {
                        $html .= ' '.$GLOBALS['I18N']->get('New Attribute').': '.$item['column'];
                    } elseif ($item['record'] == 'skip') {
                        // forget about it
                        $html .= ' '.$GLOBALS['I18N']->get('Skip value').' '.$column.': ';
                    } elseif ($item['record'] != 'system') {
                        $html .= $attributes[$item['record']];
//            var_dump($attributes[$item['record']]);
                    } else {
                        $html .= $item['column'];
                    }
                    $html .= ' -> '.$user[$item['index']].'<br/>';
                }
            }
            if ($html) {
                echo '<blockquote>'.$html.'</blockquote><hr />';
            }
        }
        if ($c > 50) {
            break;
        }
    }

    printf($GLOBALS['I18N']->get('Test output<br/>If the output looks ok, click %s to submit for real').'<br/><br/>',
        PageLink2($_GET['page'].'&amp;confirm=yes', $GLOBALS['I18N']->get('Confirm Import')));

    echo '<div class="button btn btn-default">'.PageLink2($_GET['page'], $GLOBALS['I18N']->get('Import some more emails')).'</div>';

    return;
}
?>


    <?php echo formStart('enctype="multipart/form-data" name="import"'); ?>
    <?php

    /*
    if (Sql_Table_Exists($tables["list"])) {
    */
    if (isset($_GET['list'])) {
        $subselect .= sprintf(' and id= %d', $_GET['list']);
    }

    $result = Sql_query('SELECT id,name FROM '.$tables['list']." $subselect ORDER BY listorder");
    $c = 0;
    $some = Sql_Affected_Rows();

    if ($some == 1) {
        $row = Sql_fetch_array($result);
        printf('<input type="hidden" name="listname[%d]" value="%s"><input type="hidden" name="lists[%d]" value="%d">%s <b>%s</b>',
            $c, stripslashes($row['name']), $c, $row['id'], $GLOBALS['I18N']->get('Adding users to list'),
            stripslashes($row['name']));
    } else {
        echo '<h3>'.$GLOBALS['I18N']->get('Select the lists to add the emails to').'</h3>';
        /*
            while ($row = Sql_fetch_array($result)) {
              printf('<li><input type="hidden" name="listname[%d]" value="%s"><input type="checkbox" name="lists[%d]" value="%d">%s', $c, stripslashes($row["name"]), $c, $row["id"], stripslashes($row["name"]));
              $some = 1;
              $c++;
            }
        */

        if (!$some) {
            echo $GLOBALS['I18N']->get('No lists available').' '.PageLink2('editlist',
                    $GLOBALS['I18N']->get('Add a list'));
        } else {
            $selected_lists = getSelectedLists('lists');

            echo listSelectHTML($selected_lists, 'lists', $subselect, s('Select the lists to add the emails to'));
        }
    }
    /*
    }
    */

    if (defined('IN_WEBBLER') && Sql_Table_Exists('groups')) {
        $result = Sql_query('SELECT id,name FROM groups ORDER BY listorder');
        $c = 0;
        if (Sql_Affected_Rows() == 1) {
            $row = Sql_fetch_array($result);
            printf('<p class="information"><input type="hidden" name="groupname[%d]" value="%s"><input type="hidden" name="groups[%d]" value="%d">Adding users to group <b>%s</b></p>',
                $c, $row['name'], $c, $row['id'], $row['name']);
        } else {
            echo '<p class="information">'.$GLOBALS['I18N']->get('Select the groups to add the users to').'</p>';
            while ($row = Sql_fetch_array($result)) {
                if ($row['id'] == $everyone_groupid) {
                    printf('<li><input type="hidden" name="groupname[%d]" value="%s"><input type="hidden" name="groups[%d]" value="%d"><b>%s</b> - '.$GLOBALS['I18N']->get('automatically added'),
                        $c, $row['name'], $c, $row['id'], $row['name']);
                } else {
                    printf('<li><input type="hidden" name="groupname[%d]" value="%s"><input type="checkbox" name="groups[%d]" value="%d">%s',
                        $c, $row['name'], $c, $row['id'], $row['name']);
                }
                $some = 1;
                ++$c;
            }
        }

        if (!empty($GLOBALS['config']['usergroup_types'])) {
            echo '<p class="information">Select the default group membership type</p><select name="grouptype">';
            foreach ($GLOBALS['config']['usergroup_types'] as $ind => $val) {
                printf('<option value="%d">%s of group</option>', $ind, $val);
            }
            echo '</select>';
        }
    }
    ?>

<div class="panel">
    <div class="content">
        <table class="importcsvMain" border="1">
            <tr>
                <td colspan="2"><?php echo $GLOBALS['I18N']->get('The file you upload will need to have the attributes of the records on    the first line.     Make sure that the email column is called "email" and not something like "e-mail" or     "Email Address".     Case is not important.          If you have a column called "Foreign Key", this will be used for synchronisation between an     external database and the phpList database. The foreignkey will take precedence when matching     an existing subscriber. This will slow down the import process. If you use this, it is allowed to have     records without email, but an "Invalid Email" will be created instead. You can then do     a search on "invalid email" to find those records. Maximum size of a foreign key is 100.          Warning: the file needs to be plain text. Do not upload binary files like a Word Document.     ') ?>
                </td>
            </tr>
            <tr>
                <td style="width: 40%"><?php echo $GLOBALS['I18N']->get('File containing emails') ?>:<br/>
                </td>
                <td><input type="file" name="import_file">
                    <br/><?php printf($GLOBALS['I18N']->get('The following limits are set by your server:<br/>Maximum size of a total data sent to server: %s<br/>Maximum size of each individual file: %s'),
                        ini_get('post_max_size'), ini_get('upload_max_filesize'));
                    printf($GLOBALS['I18N']->get('phpList will not process files larger than %dMB'),
                        IMPORT_FILESIZE); ?>
                </td>
            </tr>
            <tr>
                <td><?php echo $GLOBALS['I18N']->get('Field Delimiter') ?>:</td>
                <td><input type="text" name="import_field_delimiter" size="5">
                    (<?php echo $GLOBALS['I18N']->get('default is TAB') ?>)
                </td>
            </tr>
            <!--tr><td><?php echo $GLOBALS['I18N']->get('Record Delimiter') ?>:</td><td><input type="text" name="import_record_delimiter" size="5"> (<?php echo $GLOBALS['I18N']->get('default is line break') ?>)</td></tr-->
            <tr>
                <td><?php echo $GLOBALS['I18N']->get('Test output') ?>:</td>
                <td><input type="checkbox" name="import_test" value="yes" checked="checked"/><?php echo Help('testoutput'); ?></td>
            </tr>

            <tr>
                <td><?php echo $GLOBALS['I18N']->get('Show Warnings') ?>:</td>
                <td><input type="checkbox" name="show_warnings" value="yes"/><?php echo Help('showwarnings'); ?></td>
            </tr>
            <tr>
                <td><?php echo $GLOBALS['I18N']->get('Omit Invalid') ?>:</td>
                <td><input type="checkbox" name="omit_invalid" value="yes" checked="checked"/><?php echo Help('omitinvalid'); ?></td>
            </tr>
            <tr>
                <td><?php echo $GLOBALS['I18N']->get('Assign Invalid') ?>:</td>
                <td><input type="text" name="assign_invalid" value="<?php echo $GLOBALS['assign_invalid_default'] ?>"/><?php echo Help('assigninvalid'); ?>
                </td>
            </tr>

            <tr>
                <td><?php echo $GLOBALS['I18N']->get('Overwrite Existing') ?>:</td>
                <td><input type="checkbox" name="overwrite" value="yes" checked="checked"/><?php echo Help('overwriteexisting'); ?></td>
            </tr>

            <tr>
                <td><?php echo $GLOBALS['I18N']->get('Retain Old User Email') ?>:</td>
                <td><input type="checkbox" name="retainold" value="yes"/><?php echo Help('retainoldemail'); ?></td>
            </tr>

            <?php
            //# we should not allow sending this, but run it through process queue instead
            //# https://mantis.phplist.com/view.php?id=16898
            ?>
            <!--tr><td colspan="2"><?php echo $GLOBALS['I18N']->get('If you choose "send notification email" the subscribers you are adding will be sent the request for confirmation of subscription to which they will have to reply. This is recommended, because it will identify invalid emails.') ?></td></tr>
<tr><td><?php echo $GLOBALS['I18N']->get('Send&nbsp;Notification&nbsp;email') ?>&nbsp;<input type="radio" name="notify" value="yes" /></td><td><?php echo $GLOBALS['I18N']->get('Make confirmed immediately') ?>&nbsp;<input type="radio" name="notify" value="no" checked="checked"/></td></tr>
<tr><td colspan="2"><?php echo $GLOBALS['I18N']->get('If you are going to send notification to users, you may want to add a little delay between messages') ?></td></tr>
<tr><td><?php echo $GLOBALS['I18N']->get('Notification throttle') ?>:</td><td><input type="text" name="throttle_import" size="5"> <?php echo $GLOBALS['I18N']->get('(default is nothing, will send as fast as it can)') ?></td></tr-->

            <tr>
                <td>
                    <div class="submit"><input type="submit" name="import"
                                               value="<?php echo $GLOBALS['I18N']->get('Import') ?>"></div>
                </td>
                <td>&nbsp;</td>
            </tr>
        </table>
    </div>
</div>
</form>
