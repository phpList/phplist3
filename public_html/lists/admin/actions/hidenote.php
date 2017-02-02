<?php

//# hiding notes

if ($_GET['note']) {
    if (!isset($_SESSION['suppressinfo']) || !is_array($_SESSION['suppressinfo'])) {
        $_SESSION['suppressinfo'] = array();
    }
    $_SESSION['suppressinfo'][$_GET['note']] = 'hide';
    $status = ' ';
}
