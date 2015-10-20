<?php

$now = time();

$status = '';
$GLOBALS['I18N']->initFSTranslations();

if (time() - $now < 4) {
    sleep(4);
}

$status = '<script type="text/javascript">$("#dialog").dialog(\'close\'); document.location = document.location; </script>';
