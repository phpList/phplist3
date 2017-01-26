<?php

require_once dirname(__FILE__).'/accesscheck.php';

// just make sure the file is not called directly
if (function_exists('system_messageheaders')) {
    $previous_buffer = ob_get_contents();
    if ($previous_buffer) {
        ob_end_clean();
    }
    ob_start();
    phpinfo();
    $parseresults = ob_get_contents();
    ob_end_clean();
    if ($previous_buffer) {
        ob_start();
        echo $previous_buffer;
    }
    $parseresults = preg_replace('#<style.*/style>#sim', '', $parseresults);
    $parseresults = preg_replace('#<!DOCTYPE.*<body>#sim', '', $parseresults);
    $parseresults = preg_replace('/ width="600"/', ' width="300"', $parseresults);
    $parseresults = preg_replace('/class="v"/', 'class="listinghdname"', $parseresults);
    $parseresults = preg_replace('/;/', '; ', $parseresults);
    $parseresults = preg_replace('/:/', ': ', $parseresults);
    $parseresults = preg_replace('/,/', ', ', $parseresults);
    $parseresults = preg_replace("/\//", ' /', $parseresults);
    $parseresults = preg_replace('/< /', '<', $parseresults);
    echo $parseresults;
}
