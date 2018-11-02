<?php
require_once dirname(__FILE__).'/accesscheck.php';

$attributes = array();
@ob_end_flush();

function readentry($file)
{
    if (preg_match('/\.\./', $file)) {
        return;
    }
    if (!preg_match('/data/', $file)) {
        return;
    }
    $fp = fopen($file, 'r');
    $found = '';
    while (!feof($fp)) {
        $buffer = fgets($fp, 4096);
        if (strpos($buffer, '#') === false) {
            $found = $buffer;
            fclose($fp);

            return $found;
        }
    }
    fclose($fp);

    return '';
}

$files = scandir('data');
foreach ($files as $file) {
    if (is_file("data/$file")) {
        $entry = readentry("data/$file");
        $attributes[$entry] = $file;
    }
}

if (!empty($_POST['selected']) && is_array($_POST['selected'])) {
    $selected = $_POST['selected'];
    foreach ($selected as $key => $val) {
        $entry = readentry("data/$val");
        list($name, $desc) = explode(':', $entry);
        echo '<br/><br/>'.$GLOBALS['I18N']->get('Loading')." $desc<br/>\n";
        $lc_name = str_replace(' ', '', strtolower(str_replace('.txt', '', $val)));
        $lc_name = preg_replace("/[\W]/", '', $lc_name);

        if ($lc_name == '') {
            Fatal_Error($GLOBALS['I18N']->get('Name cannot be empty:')." $lc_name");
        }


        $typeValue = 'select';
        $terms = 'termsofservice';
        $adult = 'subscriberisanadult';

        if ($lc_name == '') {
            Fatal_Error($GLOBALS['I18N']->get('Name cannot be empty:')." $lc_name");
        }
        $lc_name = getNewAttributeTablename($lc_name);

        if(substr($lc_name, 0, strlen($terms)) === $terms){
            $typeValue = 'checkbox';
            if(getConfig('domain')!==null && getConfig('domain')!==''){
                $name.= getConfig('domain');
            } else $name.= 'our website';

        }
        if(substr($lc_name, 0, strlen($adult)) === $adult){
            $typeValue= 'checkbox';
        }

            $query = sprintf('insert into %s (name,type,required,tablename) values("%s","%s",%d,"%s")',
            $tables['attribute'], addslashes($name), $typeValue, 1, $lc_name);
        Sql_Query($query);
        $insertid = Sql_Insert_id();

        $query = "create table $table_prefix"."listattr_$lc_name (id integer not null primary key auto_increment, name varchar(255), unique (name(150)),listorder integer default 0)";
        Sql_Query($query);
        $fp = fopen("data/$val", 'r');
        $header = '';
        while (!feof($fp)) {
            $buffer = fgets($fp, 4096);
            if (strpos($buffer, '#') === false) {
                if (!$header) {
                    $header = $buffer;
                } elseif (trim($buffer) != '') {
                    Sql_Query(sprintf('insert into %slistattr_%s (name) values("%s")', $table_prefix, $lc_name,
                        trim($buffer)));
                }
            }
        }
        fclose($fp);
    }
    echo $GLOBALS['I18N']->get('done').'<br/><br/>';

    echo PageLinkButton('attributes', $GLOBALS['I18N']->get('return to editing attributes'));

//@@@@ not sure about this one:  print '<p class="button">'.PageLink2("attributes",$GLOBALS['I18N']->get('continue')).'</p>';
} else {
    ?>


    <?php echo formStart(' class="defaultsAdd"') ?>
    <?php
    reset($attributes);
    foreach ($attributes as $key => $attribute) {
        if (strstr($key, ':')) {
            list($name, $desc) = explode(':', $key);
            if ($name && $desc) {
                printf('<input type="checkbox" name="selected[]" value="%s" />%s<br/>', $attribute, $desc);
            }
        }
    }
    echo '<input class="submit" type="submit" value="'.$GLOBALS['I18N']->get('Add').'" /></form>';
}
?>
