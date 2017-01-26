<?php

verifyCsrfGetToken();
//# hiding bounce header

$status = '';
if (isset($_GET['hideheader'])) {
    $_SESSION['hidebounceheader'] = true;
    $status = ' ';
}
