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

        var loadMessage = \''.sjs('Please wait, your request is being processed. Do not refresh this page.').'\';
        var loadMessages = new Array(); 
        loadMessages[5] = \''.sjs('Still loading the statistics').'\';
        loadMessages[30] = \''.sjs('It may seem to take a while, but there is a lot of data to crunch<br/>if you have a lot of subscribers and campaigns').'\';
        loadMessages[60] = \''.sjs('It should be soon now, your stats are almost there.').'\';
        loadMessages[90] = \''.sjs('This seems to take longer than expected, looks like there is a lot of data to work on.').'\';
        loadMessages[120] = \''.sjs('Still loading, please be patient, your statistics will show shortly.').'\';
        loadMessages[150] = \''.sjs('It will really be soon now until your statistics are here.').'\';
        loadMessages[180] = \''.sjs('Maybe get a coffee instead, otherwise it is like watching paint dry.').'\';
        loadMessages[210] = \''.sjs('Still not here, let\'s have another coffee then.').'\';
        loadMessages[240] = \''.sjs('Too much coffee, I\'m trembling.').'\';
        var contentdivcontent = "./?page=pageaction&action=domainstats&ajaxed=true'. addCsrfGetToken() . '";
     </script>';
