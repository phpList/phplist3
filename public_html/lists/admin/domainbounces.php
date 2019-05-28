<?php

require_once dirname(__FILE__).'/accesscheck.php';

if (isset($_GET['delay'])) {
    $_SESSION['LoadDelay'] = sprintf('%d', $_GET['delay']);
} else {
    unset($_SESSION['LoadDelay']);
}
if (isset($_GET['domain'])) {
    $domain = (string) $_GET['domain'];
}
if (isset($_GET['bounces'])) {
    $bounces = (int) $_GET['bounces'];
}
echo '<div id="contentdiv"></div>';
echo asyncLoadContent('./?page=pageaction&action=domainbounces&ajaxed=true&domain='.$domain.'&bounces='.$bounces.addCsrfGetToken());
