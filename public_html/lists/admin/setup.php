<?php

require_once dirname(__FILE__).'/accesscheck.php';

// quick installation checklist

// initialise database
// setup config values
// configure attributes
// create lists
// create subscribe pages
// add subscribers

$alldone = 1;

$html = '';
$html .= '<table class="setupMain">';

$link = PageLink2('initialise', $GLOBALS['I18N']->get('Go there'));
if (!empty($link)) {
    $html .=
        '<tr><td>'.$GLOBALS['I18N']->get('Initialise Database').'</td>
  <td>' .$link.'</td><td>';

    if (Sql_Table_Exists($tables['config'], 1)) {
        $html .= $GLOBALS['img_tick'];
    } else {
        $html .= $GLOBALS['img_cross'];
        $alldone = 0;
    }

    $html .= '</td></tr>';
}

$link = PageLink2('admin&amp;id=1', s('Go there'));
if (!empty($link) && $GLOBALS['require_login']) {
    $html .= '<tr><td>'.s('Change admin password').' </td>
  <td>' .$link.'</td><td>';
    $curpwd = Sql_Fetch_Row_Query("select password from {$tables['admin']} where loginname = \"admin\"");
    if ($curpwd[0] != 'phplist' && $curpwd[0] != encryptPass('phplist')) {
        $html .= $GLOBALS['img_tick'];
    } else {
        $alldone = 0;
        $html .= $GLOBALS['img_cross'];
    }

    $html .= '</td></tr>';
}

$link = PageLink2('configure', $GLOBALS['I18N']->get('Go there'));
if (!empty($link)) {
    $html .= '<tr><td>'.$GLOBALS['I18N']->get('Verify Settings').'</td>
    <td>' .$link.'</td><td>';
    $data = Sql_Fetch_Row_Query("select value from {$tables['config']} where item = \"subscribeurl\"");
    if ($data[0]) {
        $html .= $GLOBALS['img_tick'];
    } else {
        $alldone = 0;
        $html .= $GLOBALS['img_cross'];
    }

    $html .= '</td></tr>';
}

$html .= '<tr><td>'.s('Configure attributes').'</td>
<td>' .PageLink2('attributes', s('Go there')).'</td><td>';
$req = Sql_Query("select * from {$tables['attribute']}");
if (Sql_Affected_Rows()) {
    $html .= $GLOBALS['img_tick'];
} else {
    $alldone = 0;
    $html .= $GLOBALS['img_cross'];
}

$html .= '</td></tr>';

$html .= '<tr><td>'.s('Create public lists').'</td>
<td>' .PageLink2('list', s('Go there')).'</td><td>';
$req = Sql_Query(sprintf('select id from %s where active <> 0', $tables['list']));
if (Sql_Affected_Rows()) {
    $html .= $GLOBALS['img_tick'];
} else {
    $alldone = 0;
    $html .= $GLOBALS['img_cross'];
}
$html .= '</td></tr>';

$html .= '<tr><td>'.s('Create a subscribe page').'</td>
<td>' .PageLink2('spage', s('Go there')).'</td><td>';
$req = Sql_Query("select * from {$tables['subscribepage']}");
if (Sql_Affected_Rows()) {
    $html .= $GLOBALS['img_tick'];
} else {
    $alldone = 0;
    $html .= $GLOBALS['img_cross'];
}

$html .= '</td></tr>';
$html .= '<tr><td>'.s('Add some subscribers').'</td>
<td>' .PageLink2('import', s('Go there')).'</td><td>';
$req = Sql_Fetch_Row_Query("select count(*) from {$tables['user']}");
if ($req[0] > 2) {
    $html .= $GLOBALS['img_tick'];
} else {
    $alldone = 0;
    $html .= $GLOBALS['img_cross'];
}

$html .= '</td></tr>';

$html .= '</table>';

if ($alldone) {
    $html .= Info($GLOBALS['I18N']->get('Congratulations, phpList is set up, you are ready to start mailing'),
            1).'<br/>'.PageLinkActionButton('send', s('Start a message campaign'));
    unset($_SESSION['firstinstall']);
}

$panel = new UIPanel($GLOBALS['I18N']->get('configuration steps'), $html);
echo $panel->display();
