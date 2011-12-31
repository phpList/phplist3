<?php

$id = sprintf('%d',$_GET['id']);
print previewTemplate($id,$_SESSION["logindetails"]["id"],$GLOBALS['I18N']->get('Sample Newsletter text'));

$status = ' ';
