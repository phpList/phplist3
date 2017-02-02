<?php

if (!defined('PHPLISTINIT')) {
    die();
}
verifyCsrfGetToken();

if ($_GET['update'] == 'tlds') {
    refreshTlds(true);
    $tlds = explode('|', getConfig('internet_tlds'));
}

$status = s('Top level domains were updated successfully');
$status .= '<br/>'.s('%d Top Level Domains', count($tlds));
