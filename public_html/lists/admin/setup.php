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
$html .= '<tr><th>'.s('Step').'</th> <th> '.s('Status').'</th> </tr>';

$link = PageLink2('initialise', s('Initialise Database'));
if (!empty($link)) {
    $html .=
        '<tr> <td>' .$link.'</td><td>';

    if (Sql_Table_Exists($tables['config'], 1)) {
        $html .= $GLOBALS['img_tick'];
    } else {
        $html .= $GLOBALS['img_cross'];
        $alldone = 0;
    }

    $html .= '</td></tr>';
}

$link = PageLink2('configure', s('Verify Settings'));
if (!empty($link)) {
    $html .= '<tr><td>' .$link.'</td><td>';
    $data = Sql_Fetch_Row_Query("select value from {$tables['config']} where item = \"subscribeurl\"");
    if ($data[0]) {
        $html .= $GLOBALS['img_tick'];
    } else {
        $alldone = 0;
        $html .= $GLOBALS['img_cross'];
    }

    $html .= '</td></tr>';
}

$html .= '<tr><td>'.PageLink2('attributes', s('Configure attributes')).'</td><td>';
$req = Sql_Query("select * from {$tables['attribute']}");
if (Sql_Affected_Rows()) {
    $html .= $GLOBALS['img_tick'];
} else {
    $alldone = 0;
    $html .= $GLOBALS['img_cross'];
}

$html .= '</td></tr>';

$html .= '<tr><td>'.PageLink2('list', s('Create public lists')).'</td><td>';
$req = Sql_Query(sprintf('select id from %s where active <> 0', $tables['list']));
if (Sql_Affected_Rows()) {
    $html .= $GLOBALS['img_tick'];
} else {
    $alldone = 0;
    $html .= $GLOBALS['img_cross'];
}
$html .= '</td></tr>';

$html .= '<tr><td>'.PageLink2('spage', s('Create a subscribe page')).'</td><td>';
$req = Sql_Query("select * from {$tables['subscribepage']}");
if (Sql_Affected_Rows()) {
    $html .= $GLOBALS['img_tick'];
} else {
    $alldone = 0;
    $html .= $GLOBALS['img_cross'];
}

$html .= '</td></tr>';
$html .= '<tr><td>'.PageLink2('import', s('Add some subscribers')).'</td><td>';
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
    $html .= Info(s('Congratulations, phpList is set up, you are ready to start mailing'),
            1).'<br/>'.PageLinkActionButton('send', s('Start a message campaign'));
    unset($_SESSION['firstinstall']);
}

$panel = new UIPanel(s('configuration steps'), $html);
echo $panel->display();
