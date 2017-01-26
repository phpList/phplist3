<?php

// view prepared message
require_once dirname(__FILE__).'/accesscheck.php';

ob_end_clean();
$id = sprintf('%d', $_GET['id']);
if (!$id) {
    return '';
}

$message = Sql_Fetch_Array_Query("select * from {$tables['message']} where status = 'prepared' and id = ".$id);
if ($message['htmlformatted']) {
    $content = stripslashes($message['message']);
} else {
    $content = nl2br(stripslashes($message['message']));
}
if ($message['template']) {
    echo previewTemplate($message['template'], $_SESSION['logindetails']['id'], $content, $message['footer']);
} else {
    echo nl2br($content."\n\n".$message['footer']);
}
exit;
