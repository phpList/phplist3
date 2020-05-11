<?php

/**
 * @brief Ajax handler for generating the message body preview line shown in some mail clients below the subject
 * @note based on generatetext.php
 */

verifyCsrfGetToken();

// generate text content
$msgid = sprintf('%d', $_GET['id']);
$messagedata = loadMessageData($msgid);
// shorten to 90 chars (max message preview length)
$shortMessageData = substr( $messagedata['message'], 0, 90);

// Check if the campaign content is a 'send as a web page' url, and if so, convert that to text instead
if (preg_match('/\[URL:(.+)\]/', $shortMessageData, $regs)) {
    $content = fetchUrl($regs[1]);
    $previewText = HTML2Text($content);
} else {
    $previewText = HTML2Text($shortMessageData);
}

// convert to visual preview
// FIXME this fails when the text is large, or contains £

// replace newlines with spaces
$previewText = str_replace("\r", "", $previewText);
$previewText = str_replace("\n", "", $previewText);
// replace escaped newlines
$previewText = trim($previewText);
$previewText = preg_replace("/\n/", '\\n', $previewText);
$previewText = preg_replace("/\r/", '', $previewText);

// fix entities
$previewText = htmlentities($previewText, ENT_IGNORE, 'UTF-8', true);

// replace quotes
$previewText = str_replace('"', '&quot;', $previewText);

$status = 
'<script type="text/javascript">
    $("#messagepreview").val("' .$previewText.'");
</script>
';
