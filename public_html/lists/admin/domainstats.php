<?php

require_once dirname(__FILE__) . '/accesscheck.php';

## for testing the loader allow a delay flag
if (isset($_GET['delay'])) {
    $_SESSION['LoadDelay'] = sprintf('%d', $_GET['delay']);
} else {
    unset($_SESSION['LoadDelay']);
}

print '<div id="contentdiv"></div>';
print '<script type="text/javascript">

        var loadMessage = \''.sjs('Content loading &mdash; do not refresh this page.').'\';
        var loadMessages = new Array(); 
        loadMessages[5] = \''.sjs('Content loading &mdash; if there is a lot of data to process, this may take some time').'\';
        loadMessages[30] = \''.sjs('Content loading &mdash; thank you for your patience').'\';
        loadMessages[60] = \''.sjs('Content loading').'\';
        loadMessages[90] = \''.sjs('Content loading').'\';
        loadMessages[120] = \''.sjs('Content loading').'\';
        loadMessages[150] = \''.sjs('Content loading').'\';
        loadMessages[180] = \''.sjs('Content loading').'\';
        loadMessages[210] = \''.sjs('Content loading &mdash; either a very large amount of data is being processed or the system is experiencing high demand').'\';
        loadMessages[240] = \''.sjs('Thank you for your patience &mdash; if the content fails to load in the next few minutes please report the issue').'\';
        var contentdivcontent = "./?page=pageaction&action=domainstats&ajaxed=true'. addCsrfGetToken() . '";
     </script>';
