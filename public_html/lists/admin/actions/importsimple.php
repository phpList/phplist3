<?php

if (!empty($_GET['list'])) {
    //  print $GLOBALS['I18N']->get('import to').' '.listName($_GET['list']);

    include dirname(__FILE__).'/../importsimple.php';
}
$status = '  ';
