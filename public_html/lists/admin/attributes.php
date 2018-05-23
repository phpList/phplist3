<?php
require_once dirname(__FILE__).'/accesscheck.php';
if (!defined('PHPLISTINIT')) {
    exit;
}

$types = array('textline', 'checkbox', 'checkboxgroup', 'radio', 'select', 'hidden', 'textarea', 'date');
if ((defined('IN_WEBBLER') && IN_WEBBLER) || (defined('WEBBLER') && WEBBLER)) {
    $types[] = 'avatar';
}

$formtable_exists = Sql_Table_exists('formfield');

//ob_end_flush();
//foreach ($_POST as $key => $val) {
//  print "$key = ".print_r($val)."<br/>";
//}
//return;
echo '<div class="panel"><div class="header"></div><div class="content">';
if (isset($_POST['action'])) {
    if (isset($_POST['name'])) {
        foreach ($_POST['name'] as $id => $val) {
            if (!$id && isset($_POST['name'][0]) && $_POST['name'][0] != '') {
                // it is a new one
                $lc_name = getNewAttributeTablename($_POST['name'][0]);
                if ($lc_name == 'email') {
                    echo Warn(s('Email is a system attribute'));
                }

                //print "New attribute: ".$lc_name."<br/>";
                if (empty($_POST['required'][0])) {
                    $nRequired = 0;
                } else {
                    $nRequired = $_POST['required'][0];
                }

                $query = sprintf('insert into %s (name,type,listorder,default_value,required,tablename) values("%s","%s",%d,"%s",%d,"%s")',
                    $tables['attribute'], sql_escape(strip_tags($_POST['name'][0])), sql_escape($_POST['type'][0]),
                    $_POST['listorder'][0], sql_escape($_POST['default'][0]), $nRequired, $lc_name);
                Sql_Query($query);
                $insertid = Sql_Insert_id();
                // text boxes and hidden fields do not have their own table
                if ($_POST['type'][$id] != 'textline' && $_POST['type'][$id] != 'hidden') {
                    $query = "create table $table_prefix"."listattr_$lc_name (id integer not null primary key auto_increment, name varchar(255), unique (name(150)), listorder integer default 0)";
                    Sql_Query($query);
                } else {
                    // and they cannot currently be required, changed 29/08/01, insert javascript to require them, except for hidden ones :-)
                    if (isset($_POST['type'][$id]) && $_POST['type'][$id] == 'hidden') {
                        Sql_Query("update {$tables['attribute']} set required = 0 where id = $insertid");
                    }
                }
                if ($_POST['type'][$id] == 'checkbox') {
                    // with a checkbox we know the values
//          Sql_Query('insert into '.$table_prefix.'listattr_'.$lc_name.' (name) values("Checked")');
//          Sql_Query('insert into '.$table_prefix.'listattr_'.$lc_name.' (name) values("Unchecked")');
                    // we cannot "require" checkboxes, that does not make sense
                    Sql_Query("update {$tables['attribute']} set required = 1 where id = $insertid");
                }
                if ($_POST['type'][$id] == 'checkboxgroup') {
                    Sql_Query("update {$tables['attribute']} set required = 0 where id = $insertid");
                }

                // fix all existing users to have a record for this attribute, even with empty data
                $req = Sql_Query("select id from {$tables['user']}");
                while ($row = Sql_Fetch_Row($req)) {
                    Sql_Query(sprintf('insert ignore into %s (attributeid,userid) values(%d,%d)',
                        $tables['user_attribute'], $insertid, $row[0]));
                }
            } elseif ($_POST['name'][$id] != '') {
                // it is a change
                // get the original type

                $req = Sql_Fetch_Row_Query("select type,tablename from {$tables['attribute']} where id = $id");
                $existingtype = $req[0];
                //print "Existing attribute: ".$_POST["name"][$id]." new type:".$_POST["type"][$id]." existing type: ".$req[0]."<br/>";
                if ($_POST['type'][$id] != $existingtype) {
                    switch ($existingtype) {
                        case 'textline':
                        case 'hidden':
                        case 'date':
                            print s('Converting %s from %s to %s', htmlentities($_POST['name'][$id]), $existingtype,
                                    htmlentities($_POST['type'][$id])).'<br/>';
                            switch ($_POST['type'][$id]) {
                                case 'radio':
                                case 'checkboxgroup':
                                case 'select':
                                    $lc_name = getNewAttributeTablename($req[1]);
                                    Sql_Query("update {$tables['attribute']} set tablename = \"$lc_name\" where id = $id");
                                    Sql_Query("create table $table_prefix"."listattr_$lc_name (id integer not null primary key auto_increment, name varchar(255), unique (name(150)),listorder integer default 0)");
                                    $attreq = Sql_Query("select distinct value from {$tables['user_attribute']} where attributeid = $id");
                                    while ($row = Sql_Fetch_Row($attreq)) {
                                        $attindexreq = Sql_Query("select id from $table_prefix"."listattr_$lc_name where name = \"$row[0]\"");
                                        if (!Sql_Affected_Rows()) {
                                            Sql_Query("insert into $table_prefix"."listattr_$lc_name (name) values(\"$row[0]\")");
                                            $attid = Sql_Insert_Id();
                                        } else {
                                            $attindex = Sql_Fetch_Row($attindexreq);
                                            $attid = $attindex[0];
                                        }
                                        Sql_Query("update {$tables['user_attribute']} set value = $attid where attributeid = $id and value = \"$row[0]\"");
                                    }
                                    break;
                                case 'checkbox':
                                    // in case of checkbox we just need to set the value to "on"
                                    Sql_Query("update {$tables['user_attribute']} set value = \"off\" where attributeid = $id and (value = 0 or value = \"off\")");
                                    Sql_Query("update {$tables['user_attribute']} set value = \"on\" where attributeid = $id and (value = 1 or value = \"on\") ");
                                case 'date':
                                    $attreq = Sql_Query("select * from {$tables['user_attribute']} where attributeid = $id");
                                    while ($row = Sql_Fetch_Array($attreq)) {
                                        //                  if (strlen($row["value"] > 5)) {
                                        Sql_Query(sprintf('update %s set value = "%s" where attributeid = %d and userid = %d',
                                            $tables['user_attribute'], parseDate($row['value']), $row['attributeid'],
                                            $row['userid']));
//                  }
                                    }
                                    break;
                            }
                            break;
                        case 'radio':
                        case 'select':
                        case 'checkbox':
                            if ($_POST['type'][$id] != 'date' && $_POST['type'][$id] != 'hidden' && $_POST['type'][$id] != 'textline') {
                                break;
                            }
                            echo s('Converting %s from %s to %s', htmlentities($_POST['name'][$id]), $existingtype,
                                    htmlentities($_POST['type'][$id])).'<br/>';
                            // we are turning a radio,select or checkbox into a hidden or textline field
                            $valuereq = Sql_Query("select id,name from $table_prefix"."listattr_$req[1]");
                            while ($row = Sql_Fetch_Row($valuereq)) {
                                Sql_Query("update {$tables['user_attribute']} set value = \"$row[1]\" where attributeid = $id and value=\"$row[0]\"");
                            }
                            Sql_Query("drop table $table_prefix"."listattr_$req[1]");
                            break;
                        case 'checkboxgroup':
                            if ($_POST['type'][$id] == 'hidden' || $_POST['type'][$id] == 'textline') {
                                echo s('Converting %s from %s to %s', htmlentities($_POST['name'][$id]), $existingtype,
                                        htmlentities($_POST['type'][$id])).'<br/>';
                                // we are changing a checkbox group into a hidden or textline
                                // take the first value!
                                $valuereq = Sql_Query("select id,name from $table_prefix"."listattr_$req[1]");
                                while ($row = Sql_Fetch_Row($valuereq)) {
                                    Sql_Query("update {$tables['user_attribute']} set value = \"$row[1]\" where attributeid = $id and value like \"$row[0]%\"");
                                }
                                Sql_Query("drop table if exists $table_prefix"."listattr_$req[1]");
                            } elseif ($_POST['type'][$id] == 'radio' || $_POST['type'][$id] == 'select') {
                                $valuereq = Sql_Query("select userid,value from {$tables['user_attribute']} where attributeid = $id");
                                // take the first value!
                                while ($row = Sql_Fetch_Row($valuereq)) {
                                    $values = explode(',', $row[1]);
                                    Sql_Query("update {$tables['user_attribute']} set value = \"$values[0]\" where attributeid = $id and userid = \"$row[0]\"");
                                }
                            }
                            break;
                    }
                }
                if (empty($_POST['required'][$id])) {
                    $nRequired = 0;
                } else {
                    $nRequired = $_POST['required'][$id];
                }
                $query = sprintf('update %s set name = "%s" ,type = "%s" ,listorder = %d,default_value = "%s" ,required = %d where id = %d',
                    $tables['attribute'], sql_escape(strip_tags($_POST['name'][$id])), sql_escape($_POST['type'][$id]),
                    $_POST['listorder'][$id], sql_escape($_POST['default'][$id]), $nRequired, $id);
                Sql_Query($query);
                // save keywordlib seperately in case the DB hasn't been upgraded
                if ((defined('IN_WEBBLER') && IN_WEBBLER) || (defined('WEBBLER') && WEBBLER)) {
                    Sql_Query(sprintf('update ignore %s set keywordlib = %d where id = %d',
                        $GLOBALS['tables']['attribute'], $_POST['keywordlib'][$id], $id));
                }
            }
        }
    }
} elseif (isset($_POST['tagaction']) && is_array($_POST['tag'])) {
    ksort($_POST['tag']);
    if (isset($_POST['tagaction']['delete'])) {
        foreach ($_POST['tag'] as $k => $id) {
            // check for dependencies
            $id = sprintf('%d', $id);
            if ($formtable_exists) {
                $req = Sql_Query("select * from formfield where attribute = $id");
                $candelete = !Sql_Affected_Rows();
            } else {
                $candelete = 1;
            }
            if ($candelete) {
                echo s('deleting')." $id<br/>";
                $row = Sql_Fetch_Row_Query("select tablename,type from {$tables['attribute']} where id = $id");
                Sql_Query("drop table if exists $table_prefix"."listattr_$row[0]");
                Sql_Query("delete from {$tables['attribute']} where id = $id");
                // delete all user attributes as well
                Sql_Query("delete from {$tables['user_attribute']} where attributeid = $id");
            } else {
                echo Error($GLOBALS['I18N']->get('Cannot delete attribute, it is being used by the following forms:').'<br/>');
                while ($row = Sql_Fetch_Array($req)) {
                    echo PageLink2('editelements&id='.$row['form'].'&option="edit_elements"&pi="formbuilder"',
                            'form '.$row['form'].'')."<br/>\n";
                }
            }
        }
    } elseif (isset($_POST['tagaction']['merge'])) {
        $first = array_shift($_POST['tag']);
        $firstdata = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d', $tables['attribute'], $first));
        $first = $firstdata['id'];
        if (!count($_POST['tag'])) {
            echo Error(s('cannot merge just one attribute'));
        } else {
            $cbg_initiated = 0;
            foreach ($_POST['tag'] as $attid) {
                echo s('Merging %s into %d', htmlspecialchars($attid), htmlspecialchars($first)).'<br/>';

                $attdata = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d', $tables['attribute'],
                    $attid));
                if ($attdata['type'] != $firstdata['type']) {
                    echo Error(s('Can only merge attributes of the same type'));
                } else {
                    // debugging: check values for every user. This is very memory demanding, so you'll need to
                    // add loads of memory to actually use it.
                    /*
                              $before = array();
                              $second = array();
                              $after = array();
                              $req = Sql_Query(sprintf('select * from %s where attributeid = %d',$tables["user_attribute"],$first));
                              while ($row = Sql_Fetch_Array($req)) {
                                $before[$row["userid"]] = $row["value"];
                              }
                              $req = Sql_Query(sprintf('select * from %s where attributeid = %d',$tables["user_attribute"],$attid));
                              while ($row = Sql_Fetch_Array($req)) {
                                $second[$row["userid"]] = $row["value"];
                              }
                    */
                    $valuestable = sprintf('%slistattr_%s', $table_prefix, $firstdata['tablename']);
                    if ($firstdata['type'] == 'checkbox' && !$cbg_initiated) {
                        // checkboxes are merged into a checkbox group
                        // set that up first
                        Sql_query(sprintf('create table %s
            (id integer not null primary key auto_increment, name varchar(255), unique (name(150)),
            listorder integer default 0)', $valuestable), 1);
                        Sql_query(sprintf('insert into %s (name) values("%s")', $valuestable, $firstdata['name']));
                        $val = Sql_Insert_Id();
                        Sql_query(sprintf('update %s set value="%s" where attributeid = %d',
                            $tables['user_attribute'], $val, $first));
                        Sql_query(sprintf('update %s set type="checkboxgroup" where id = %d',
                            $tables['attribute'], $first));
                        $cbg_initiated = 1;
                    }
                    switch ($firstdata['type']) {
                        case 'textline':
                        case 'hidden':
                        case 'textarea':
                        case 'date':
                            Sql_query(sprintf('delete from %s where attributeid = %d and value = ""',
                                $tables['user_attribute'], $first));
                            // we can just keep the data and mark it as the first attribute
                            Sql_query(sprintf('update ignore %s set attributeid = %d where attributeid = %d',
                                $tables['user_attribute'], $first, $attid), 1);
                            // delete the ones that didn't copy across, because there was a value already
                            Sql_query(sprintf('delete from %s where id = %d',
                                $tables['attribute'], $attid));
                            // mark forms to use the merged attribute
                            if ($formtable_exists) {
                                Sql_Query(sprintf('update formfield set attribute = %d where attribute = %d', $first,
                                    $attid), 1);
                            }
                            break;
                        case 'radio':
                        case 'select':
                            // merge user values
                            Sql_Query(sprintf('delete from %s where attributeid = %d and value = ""',
                                $tables['user_attribute'], $first));
                            $req = Sql_Query(sprintf('select * from %s',
                                $table_prefix.'listattr_'.$attdata['tablename']));
                            while ($val = Sql_Fetch_Array($req)) {
                                // check if it already exists
                                $exists = Sql_Fetch_row_Query(sprintf('select id from %s where name = "%s"',
                                    $valuestable, $val['name']));
                                if (!$exists[0]) {
                                    Sql_Query(sprintf('insert into %s (name) values("%s")',
                                        $valuestable, $val['name']));
                                    $val_index = Sql_Insert_id();
                                } else {
                                    $val_index = $exists[0];
                                }
                                Sql_Query(sprintf('update %s set value = %d where attributeid = %d',
                                    $tables['user_attribute'], $val_index, $attid));
                            }
                            Sql_Query(sprintf('update %s set attributeid = %d where attributeid = %d',
                                $tables['user_attribute'], $first, $attid), 1);
                            Sql_Query(sprintf('drop table %s', $table_prefix.'listattr_'.$attdata['tablename']), 1);
                            Sql_Query(sprintf('delete from %s where id = %d',
                                $tables['attribute'], $attid));
                            // mark forms to use the merged attribute
                            if ($formtable_exists) {
                                Sql_Query(sprintf('update formfield set attribute = %d where attribute = %d', $first,
                                    $attid), 1);
                            }
                            break;
                        case 'checkbox':
                            $exists = Sql_Fetch_row_Query(sprintf('select id from %s where name = "%s"',
                                $valuestable, $attdata['name']));
                            if (!$exists[0]) {
                                Sql_Query(sprintf('insert into %s (name) values("%s")',
                                    $valuestable, $attdata['name']));
                                $val_index = Sql_Insert_id();
                            } else {
                                $val_index = $exists[0];
                            }
                            Sql_Query(sprintf('update %s set value = concat(value,",","%s") where attributeid = %d and (value != 0 or value != "off") ',
                                $tables['user_attribute'], $val_index, $first));
                            Sql_Query(sprintf('delete from %s where id = %d',
                                $tables['attribute'], $attid));
                            // mark forms to use the merged attribute
                            if ($formtable_exists) {
                                Sql_Query(sprintf('update formfield set attribute = %d where attribute = %d', $first,
                                    $attid), 1);
                            }
                            break;
                        case 'checkboxgroup':
                            // hmm, this is a tricky one.
                            print Error(s('Sorry, merging of checkbox groups is not implemented yet'));
                            break;
                    }

                    /*
                              $req = Sql_Query(sprintf('select * from %s where attributeid = %d',$tables["user_attribute"],$first));
                              while ($row = Sql_Fetch_Array($req)) {
                                $after[$row["userid"]] = $row["value"];
                              }
                              foreach ($before as $userid => $value) {
                                printf("\n".'<br/>%d before -> %s and %s<br/>after ->%s',$userid,$value,$second[$userid],$after[$userid]);
                              }
                    */
                }
            }
        }
    }
}
Sql_Query("update {$tables['attribute']} set required = 0 where type = \"checkboxgroup\" or type = \"hidden\"");

?>

    <script language="Javascript" type="text/javascript">
        var warned = 0;
        function warn() {
            if (!warned)
                alert("<?php echo s('Warning, changing types of attributes can take a long time')?>");
            warned = 1;
        }
    </script>
<?php echo formStart() ?>
<?php
echo s('Load data from').' '.PageLinkButton('defaults',
        s('predefined defaults')).Help("predefineddefaults").'<br />';

$res = Sql_Query("select * from {$tables['attribute']} order by listorder");
if (Sql_Affected_Rows()) {
    echo '<h3>' .s('Existing attributes').'</h3>';
} else {
    echo '<p class="information">'.s('No Attributes have been defined yet').'</p>';
}
$c = 0;
echo '<div class="accordion">';
while ($row = Sql_Fetch_array($res)) {
    ++$c;
    echo '<h3><a name="'.$row['id'].'">'.
        s('Attribute').': '.$row['id'].' '.htmlspecialchars(stripslashes(strip_tags($row['name'])));
    if ($formtable_exists) {
        sql_query('select * from formfield where attribute = '.$row['id']);
        echo '  ('.s('used in').' '.Sql_affected_rows().' '.$GLOBALS['I18N']->get('forms').')';
    }

    echo '</a></h3><div><label>'.s('Tag').'
  <input type="checkbox" name="tag[' .$c.']" value="'.$row['id'].'" /></label>';

    echo '<label>'.s('Name').':</label>
  <input type="text" name="name[' .$row['id'].']" value="'.htmlspecialchars(stripslashes(strip_tags($row['name']))).'" size="40" />';
    echo '<label>'.s('Type').': </label>
  <!--<input type="hidden" name="type[' .$row['id'].']" value="'.$row['type'].'">'.$row['type'].'-->';

    echo '<select name="type['.$row['id'].']" onchange="warn();">';
    foreach ($types as $key => $val) {
        printf('<option value="%s" %s>%s</option>', $val, $val == $row['type'] ? 'selected="selected"' : '',
            s($val));
    }
    echo ' 
   </select>';

    if ((defined('IN_WEBBLER') && IN_WEBBLER) || (defined('WEBBLER') && WEBBLER)) {
        if ($row['type'] == 'select' || $row['type'] == 'radio' || $row['type'] == 'checkboxgroup') {
            echo ' '.s('authoritative list').'&nbsp;';
            printf('<select name="keywordlib[%d]"><option value="">-- select</option>', $row['id']);
            $req = Sql_Query(sprintf('select id,name from keywordlib order by listorder,name'));
            while ($kwlib = Sql_Fetch_Array($req)) {
                printf('<option value="%d" %s>%s</option>', $kwlib['id'],
                    $row['keywordlib'] == $kwlib['id'] ? 'selected="selected"' : '', htmlspecialchars($kwlib['name']));
            }
            echo '</select>';
        }
    }

    if ((!defined('IN_WEBBLER') || !IN_WEBBLER) && (!defined('WEBBLER') || !WEBBLER)) {
        if ($row['type'] == 'select' || $row['type'] == 'radio' || $row['type'] == 'checkboxgroup') {
            echo PageLinkButton('editattributes&id='.$row['id'], s('edit values'));
        }
    }

    echo '<label>'.s('Default Value').':</label>
  <input type="text" name="default[' .$row['id'].']" value="'.htmlspecialchars(stripslashes($row['default_value'])).'" size="40" />';
    echo '<label>'.s('Order of Listing').':</label>
  <input type="text" name="listorder[' .$row['id'].']" value="'.$row['listorder'].'" size="5" />';
    echo '<label>'.s('Is this attribute required ?').'<input type="checkbox" name="required['.$row['id'].']" value="1" ';
    echo $row['required'] ? 'checked="checked"' : '';
    echo  '/></label>';
    echo  '</div>';
}
echo '</div><br /><!--close accordion-->';
printf('<input class="submit" type="submit" name="action" value="%s" /> ', s('Save Changes'));
if ($c) {
    printf('<span class="buttonGroup"><input class="submit" type="submit" name="tagaction[delete]" value="%s" /> 
  <input class="submit" type="submit" name="tagaction[merge]" value="%s" />%s
  </span>', s('Delete tagged attributes'), s('Merge tagged attributes'),
        Help('mergeattributes'));
}

echo '<br /><br /><br /><div id="new-attribute">
<a name="new"></a>
<h3>' .s('Add new Attribute').'</h3>
<div class="alert alert-warning">' .s('Warning: Storage of sensitive personal data such as race, health, and sexual orientation is regulated by some data protection laws').'. <a href="https://www.phplist.org/manual/ch048_gdpr.xhtml" target="_blank">' .s('Read more'). '&hellip;</a></div>

<div class="label pull-left"><label class="label">' .s('Name').': </label></div>
<div class="field"><input type="text" name="name[0]" value="" size="40" /></div>
<div class="label pull-left"><label class="label">' .s('Type').': </label></div>
<div class="field"><select name="type[0]">';
foreach ($types as $key => $val) {
    printf('     <option value="%s" %s>%s</option>', $val, '', s($val));
}
echo'
</select></div>
<div class="label pull-left"><label class="label">' .s('Default Value').': </label></div>
<div class="field"><input type="text" name="default[0]" value="" size="40" /></div>
<div class="label pull-left"><label class="label">' .s('Order of Listing').': </label></div>
<div class="field"><input type="text" name="listorder[0]" value="" size="5" /></div>
<div class="label pull-left"><label class="label">' .s('Is this attribute required?').': </label></div>
<div class="field"><input type="checkbox" name="required[0]" value="1" checked="checked" /></div>

<div class="field"><input class="submit" type="submit" name="action" value="' .s('Save Changes').'" /></div>
</div>
</form>
</div></div>
';
