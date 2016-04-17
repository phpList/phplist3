<?php

#updatetranslation
#sleep(20);
$lan = '';
if (isset($_GET['lan'])) {
    $lan = $_GET['lan'];
    $lan = preg_replace('/[^\w_]/', '', $lan);
}
$LU = getTranslationUpdates();
if (!$LU || !is_object($LU)) {
    print Error(s('Unable to fetch list of languages, please check your network or try again later'));

    return;
}

$translations = array();
foreach ($LU->translation as $update) {
    if ($update->iso == $lan) {
        #  $status = $update->updateurl;
        $translationUpdate = fetchUrl($update->updateurl);
        $translations = parsePo($translationUpdate);
    }
}

$status = '';
if (count($translations)) {
    $I18N->updateDBtranslations($translations, time());
    $status = sprintf(s('updated %d language terms'), count($translations));
} else {
    $status = Error(s('Network error updating language, please try again later'));
}
