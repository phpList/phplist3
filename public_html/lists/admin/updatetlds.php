<?php

if (!$GLOBALS['commandline']) {
    @ob_end_flush();
    echo '<p class="information">'.s('This page only works from commandline').'</p>';
    return;
}

if (isset($cline['f'])) {
    refreshTlds(true);
} else {
    refreshTlds(false);
}
$tlds = explode('|', getConfig('internet_tlds'));
cl_output(s('Now we have %d top level domains',count($tlds)));
