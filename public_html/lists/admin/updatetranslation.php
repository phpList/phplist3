<?php

require_once dirname(__FILE__) . '/accesscheck.php';

## fetch updated translation
#var_dump($LANGUAGES);

if (!Sql_Table_exists($GLOBALS['tables']['i18n'])) {
    include dirname(__FILE__) . '/structure.php';
    Sql_Create_Table($GLOBALS['tables']['i18n'], $DBstruct['i18n']);
}
if (isset($_GET['lan'])) { ## Non-JS version
    include 'actions/updatetranslation.php';
}
$force = !empty($_GET['force']);

$LU = getTranslationUpdates();
if (!$LU || !is_object($LU)) {
    print Error(s('Unable to fetch list of languages, please check your network or try again later'));

    return;
}

#var_dump($LU);
print '<ul>';
foreach ($LU->translation as $lan) {
    #  var_dump($lan);
    $lastupdated = getConfig('lastlanguageupdate-' . $lan->iso);
    if (!empty($LANGUAGES[(string)$lan->iso])) {
        $lan_name = $LANGUAGES[(string)$lan->iso][0];
    } else {
        $lan_name = $lan->name;
    }
    if ($force || ($lan->iso == $_SESSION['adminlanguage']['iso'] && $lan->lastmodified > $lastupdated)) {
        $updateLink = pageLinkAjax('updatetranslation&lan=' . $lan->iso, $lan_name);
    } else {
        $updateLink = $lan_name;
    }
    if (empty($lastupdated)) {
        $lastupdated = s('Never');
    } else {
        $lastupdated = date('Y-m-d', $lastupdated);
    }

    $count = Sql_Fetch_Row_Query(sprintf('select count(*) from %s where lan = "%s" and original = "language-name"',
        $tables['i18n'], $lan->iso));
    if ($count[0] == 0) {
        ## insert a dummy translation entry, so to record the language
#    print '<h1>'.$count[0].'</h1>';
        Sql_Query(sprintf('insert into %s (lan,original,translation) values("%s","%s","%s")', $tables['i18n'],
            $lan->iso, 'language-name', $lan->name));
    }

    if ($lan->iso == $_SESSION['adminlanguage']['iso']) {
        printf('<li><strong>%s %s: %s, %s: %s</strong></li>', $updateLink, s('Last updated'), $lastupdated,
            s('Last modified'), date('Y-m-d', (int)$lan->lastmodified));
    } else {
        printf('<li>%s %s: %s, %s: %s</li>', $updateLink, s('Last updated'), $lastupdated, s('Last modified'),
            date('Y-m-d', (int)$lan->lastmodified));
    }
}
print '</ul>';
