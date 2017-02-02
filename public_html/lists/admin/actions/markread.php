<?php

$readmessagesconf = getConfig('readnews'.$_SESSION['logindetails']['id']);
$readmessages = unserialize($readmessagesconf);

$new = $_GET['id'];
$readmessages[] = $new;
SaveConfig('readnews'.$_SESSION['logindetails']['id'], serialize($readmessages), 0, 1);
$status = '<script type="text/javascript"> $(".closethisone").hide();</script>';
