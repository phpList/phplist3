<?php

require_once dirname(__FILE__).'/accesscheck.php';

if (!Sql_Table_exists($GLOBALS['tables']['i18n'])) {
    include dirname(__FILE__).'/structure.php';
    Sql_Create_Table($GLOBALS['tables']['i18n'], $DBstruct['i18n']);
}
if (isset($_GET['lan'])) { //# Non-JS version
    include 'actions/updatetranslation.php';
}
$force = !empty($_GET['force']);

$LU = getTranslationUpdates();
if (!$LU || !is_object($LU)) {
    echo Error(s('Unable to fetch list of languages, please check your network or try again later'));

    return;
}
/*
 * To sort the languages we need to create an array from $LU->translation, which is a SimpleXMLElement object.
 */
$languages = iterator_to_array($LU->translation, false);
usort(
    $languages,
    function($a, $b) {
        return strcasecmp($a->iso, $b->iso);
    }
);
$ls = new WebblerListing(s('Language translations'));
$ls->setElementHeading(s('Language'));

foreach ($languages as $lan) {
    $count = Sql_Fetch_Row_Query(sprintf(
        'SELECT count(*)
        FROM %s
        WHERE lan = "%s" AND original = "language-name"',
        $tables['i18n'],
        $lan->iso
    ));

    if ($count[0] == 0) {
        // insert a dummy translation entry, so to record the language
        Sql_Query(sprintf(
            'INSERT INTO %s (lan,original,translation)
            VALUES("%s","%s","%s")',
            $tables['i18n'],
            $lan->iso,
            'language-name',
            $lan->name
        ));
    }
    $lastupdated = getConfig('lastlanguageupdate-'.$lan->iso);
    $isInstalled = $lastupdated != '';

    if (!$isInstalled && !$force) {
        continue;
    }

    if ($isInstalled) {
        if ($lan->lastmodified > $lastupdated) {
            $status = s('Update is available');
            $updateLink = pageLinkAjax('updatetranslation&lan='.$lan->iso, s('Update'));
        } else {
            $status = s('Up-to-date');
            $updateLink = $force ? pageLinkAjax('updatetranslation&lan='.$lan->iso, s('Update')) : '';
        }
    } else {
        $status = s('Not installed');
        $updateLink = pageLinkAjax('updatetranslation&lan='.$lan->iso, s('Install'));
    }
    $languageName = !empty($LANGUAGES[(string) $lan->iso]) ? $LANGUAGES[(string) $lan->iso][0] : $lan->name;
    $ls->addElement($languageName);
    $ls->addColumn($languageName, s('Code'), $lan->iso);
    $ls->addColumn($languageName, s('Translation status'), $status);
    $ls->addColumn($languageName, s('Action'), $updateLink);
}
echo $ls->display();
