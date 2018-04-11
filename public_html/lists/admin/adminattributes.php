<?php
require_once dirname(__FILE__).'/accesscheck.php';

ob_end_flush();
if (isset($_POST['action']) && $_POST['action'] == $GLOBALS['I18N']->get('Save Changes')) {
    if (isset($_POST['name'])) {
        echo '<script language="Javascript" type="text/javascript"> document.write(progressmeter); start();</script>';
    }
    flush();
    foreach ($_POST['name'] as $id => $val) {
        if (!$id && isset($_POST['name'][0]) && $_POST['name'][0] != '') {
            // it is a new one
            $lc_name = substr(preg_replace("/\W/", '', strtolower($_POST['name'][0])), 0, 10);
            if ($lc_name == '') {
                Fatal_Error($GLOBALS['I18N']->get('Name cannot be empty:')." $lc_name");
            }
            $rs = Sql_Query("select * from {$tables['adminattribute']} where tablename = \"$lc_name\"");
            if (Sql_Num_Rows($rs)) {
                Fatal_Error($GLOBALS['I18N']->get('Name is not unique enough'));
            }

            $query = sprintf('insert into %s
        (name,type,listorder,default_value,required,tablename)
        values("%s","%s",%d,"%s",%d,"%s")',
                $tables['adminattribute'],
                sql_escape($_POST['name'][0]),
                sql_escape($_POST['type'][0]),
                $_POST['listorder'][0],
                sql_escape($_POST['default'][0]), $_POST['required'][0], $lc_name);
            Sql_Query($query);
            $insertid = Sql_Insert_id();

            // text boxes and hidden fields do not have their own table
            if ($_POST['type'][$id] != 'textline' && $_POST['type'][$id] != 'hidden') {
                $query = "create table $table_prefix"."adminattr_$lc_name
          (id integer not null primary key auto_increment,
          name varchar(255), unique (name(150)),listorder integer default 0)";
                Sql_Query($query);
            } else {
                // and they cannot currently be required, changed 29/08/01,
                // insert javascript to require them, except for hidden ones :-)
                if (!empty($_POST['type']['id']) && $_POST['type']['id'] == 'hidden') {
                    Sql_Query("update {$tables['attribute']} set required = 0 where id = $insertid");
                }
            }
            if ($_POST['type'][$id] == 'checkbox') {
                // with a checkbox we know the values
                Sql_Query('insert into '.$table_prefix.'adminattr_'.$lc_name.' (name) values("Checked")');
                Sql_Query('insert into '.$table_prefix.'adminattr_'.$lc_name.' (name) values("Unchecked")');
                // we cannot "require" checkboxes, that does not make sense
                Sql_Query("update {$tables['adminattribute']} set required = 0 where id = $insertid");
            }
        } elseif (!empty($_POST['name'][$id])) {
            // it is a change
            $query = sprintf('update %s set name = "%s" ,listorder = %d,default_value = "%s" ,required = %d where id = %d',
                $tables['adminattribute'], sql_escape($_POST['name'][$id]),
                $_POST['listorder'][$id], sql_escape($_POST['default'][$id]), isset($_POST['required'][$id]), $id);
            Sql_Query($query);
        }
    }
    if (isset($_POST['delete'])) {
        foreach ($_POST['delete'] as $id => $val) {
            $res = Sql_Query("select tablename,type from {$tables['adminattribute']} where id = $id");
            $row = Sql_Fetch_Row($res);
            if ($row[1] != 'hidden' && $row[1] != 'textline') {
                Sql_Query("drop table $table_prefix"."adminattr_$row[0]");
            }
            Sql_Query("delete from {$tables['adminattribute']} where id = $id");
            // delete all admin attributes as well
            Sql_Query("delete from {$tables['admin_attribute']} where adminattributeid = $id");
        }
    }
}
?>



<?php
echo formStart(' class="adminattributesListing" ');
$res = Sql_Query("select * from {$tables['adminattribute']} order by listorder");
if (Sql_Num_Rows($res)) {
    $title = $GLOBALS['I18N']->get('Existing attributes:');
} else {
    $title = $GLOBALS['I18N']->get('No Attributes have been defined yet');
}

echo '<div class="panel"><h3>'.$title.'</h3><div class="content">';

while ($row = Sql_Fetch_array($res)) {
    ?>
    <table class="attributeSet" border="1">
        <tr>
            <td colspan="2"><?php echo $GLOBALS['I18N']->get('Attribute:').$row['id'] ?></td>
            <td colspan="2"><?php echo $GLOBALS['I18N']->get('Delete'); ?> <input type="checkbox" name="delete[<?php echo $row['id'] ?>]" value="1"/></td>
        </tr>
        <tr>
            <td colspan="2"><?php echo $GLOBALS['I18N']->get('Name:'); ?> </td>
            <td colspan="2"><input type="text" name="name[<?php echo $row['id'] ?>]"
                                   value="<?php echo htmlspecialchars(stripslashes($row['name'])) ?>" size="40"/></td>
        </tr>
        <tr>
            <td colspan="2"><?php echo $GLOBALS['I18N']->get('Type'); ?> </td>
            <td colspan="2"><input type="hidden" name="type[<?php echo $row['id'] ?>]"
                                   value="<?php echo $row['type'] ?>"/><?php echo $row['type'] ?></td>
        </tr>
        <tr>
            <td colspan="2"><?php echo $GLOBALS['I18N']->get('Default Value:'); ?> </td>
            <td colspan="2"><input type="text" name="default[<?php echo $row['id'] ?>]"
                                   value="<?php echo htmlspecialchars(stripslashes($row['default_value'])) ?>"
                                   size="40"/></td>
        </tr>
        <tr>
            <td><?php echo $GLOBALS['I18N']->get('Order of Listing:'); ?> </td>
            <td><input type="text" name="listorder[<?php echo $row['id'] ?>]" value="<?php echo $row['listorder'] ?>"
                       size="5"/></td>
            <td><?php echo $GLOBALS['I18N']->get('Is this attribute required?:'); ?> </td>
            <td><input type="checkbox" name="required[<?php echo $row['id'] ?>]"
                       value="1" <?php echo $row['required'] ? 'checked="checked"' : '' ?> /></td>
        </tr>
    </table>
    <hr/>
    <?php

} ?>

<a name="new"></a>
<h3><?php echo $GLOBALS['I18N']->get('Add a new Attribute:'); ?></h3>
<table class="attributeNew" border="1">
    <tr>
        <td colspan="2"><?php echo $GLOBALS['I18N']->get('Name:'); ?> </td>
        <td colspan="2"><input type="text" name="name[0]" value="" size="40"/></td>
    </tr>
    <tr>
        <td colspan="2"><?php echo $GLOBALS['I18N']->get('Type'); ?> </td>
        <td colspan="2"><select name="type[0]">
                <?php
                $types = array('textline', 'hidden'); //'radio','select','checkbox',
                foreach ($types as $key => $val) {
                    printf('<option value="%s" %s>%s</option>', $val, '', $val);
                }
                ?>
            </select></td>
    </tr>
    <tr>
        <td colspan="2"><?php echo $GLOBALS['I18N']->get('Default Value:'); ?> </td>
        <td colspan="2"><input type="text" name="default[0]" value="" size="40"/></td>
    </tr>
    <tr>
        <td><?php echo $GLOBALS['I18N']->get('Order of Listing:'); ?> </td>
        <td><input type="text" name="listorder[0]" value="" size="5"/></td>
        <td><?php echo $GLOBALS['I18N']->get('Is this attribute required?:'); ?> </td>
        <td><input type="checkbox" name="required[0]" value="1" checked="checked"></td>
    </tr>
</table>
</div></div>
<input class="submit" type="submit" name="action" value="<?php echo $GLOBALS['I18N']->get('Save Changes'); ?>">
</form>
