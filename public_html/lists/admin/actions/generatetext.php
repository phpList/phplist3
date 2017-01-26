<?php

verifyCsrfGetToken();

// generate text content
$msgid = sprintf('%d', $_GET['id']);
$messagedata = loadMessageData($msgid);

//sleep(10); // to test the busy image

if (preg_match('/\[URL:(.+)\]/', $messagedata['message'], $regs)) {
    $content = fetchUrl($regs[1]);
//  $textversion = 'Fetched '.$regs[1];
    $textversion = HTML2Text($content);
} else {
    $textversion = HTML2Text($messagedata['message']);
}
setMessageData($msgid, 'textmessage', $textversion);

//# convert to feedback in the textarea
//# @@FIXME this fails when the text is large, or contains £
$textversion = trim($textversion);
$textversion = preg_replace("/\n/", '\\n', $textversion);
$textversion = preg_replace("/\r/", '', $textversion);
$textversion = htmlentities($textversion, ENT_IGNORE, 'UTF-8', true);

$status = '<script type="text/javascript">

$("#textmessage").html("' .str_replace('"', '&quot;', $textversion).'");
//$("#textmessage").load("./?page=pageaction&action=messagedata&field=textmessage&id=' .$msgid.'");
$("#generatetextversion").hide();

</script>
';
