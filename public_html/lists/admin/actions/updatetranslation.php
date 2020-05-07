<?php

//updatetranslation
//sleep(20);
$lan = '';
if (isset($_GET['lan'])) {
    $lan = $_GET['lan'];
    $lan = preg_replace('/[^\w_]/', '', $lan);
}
$LU = getTranslationUpdates();
if (!$LU || !is_object($LU)) {
    echo Error(s('Unable to fetch list of languages, please check your network or try again later'));

    return;
}

$translations = array();
foreach ($LU->translation as $update) {
    if ($update->iso == $lan) {
        //  $status = $update->updateurl;
        $translationUpdate = fetchUrlDirect((string) $update->updateurl);
        $translations = parsePo($translationUpdate);
        break;
    }
}

if (count($translations)) {
    $I18N->updateDBtranslations($translations, time(), $lan);
    $status = s('updated %d language terms', count($translations));
} else {
    $status = s('No language terms found');
}
