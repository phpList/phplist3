<?php

require_once dirname(__FILE__).'/accesscheck.php';

//$spb ='<li>';
//$spe = '</li>';
// Replace the $spb & $spe by <li id="li_ID"> & </li>
$html = '<ul>';
$html .= '<li id="users">'.PageLink2('users', $GLOBALS['I18N']->get('search subscribers')).'</li>';
$html .= '<li id="attributes">'.PageLink2('attributes',
        $GLOBALS['I18N']->get('manage subscriber attributes')).'</li>';

$attributehtml = '';
if ($tables['attribute'] && Sql_Table_Exists($tables['attribute'])) {
    $attrmenu = array();
    $res = Sql_Query("select * from {$tables['attribute']}", 1);
    while ($row = Sql_Fetch_array($res)) {
        if ($row['type'] == 'checkboxgroup' || $row['type'] == 'select' || $row['type'] == 'radio') {
            $attrmenu['editattributes&amp;id='.$row['id']] = strip_tags($row['name']);
        }
    }
}
foreach ($attrmenu as $page => $desc) {
    $link = PageLink2($page, $desc);
    if ($link) {
        $attributehtml .= '<li>'.$link.'</li>';
    }
}
if (!empty($attributehtml)) {
    $html .= '<li id="edit-values">'.$GLOBALS['I18N']->get('edit values for attributes').'<ul>'.$attributehtml.'</ul>';
}

$html .= '<li id="reconcileusers">'.PageLink2('reconcileusers',
        $GLOBALS['I18N']->get('Reconcile Subscribers')).'</li>';
$html .= '<li id="massunconfirm">'.PageLink2('suppressionlist', $GLOBALS['I18N']->get('Suppression list')).'</li>';
$html .= '<li id="massremove">'.PageLink2('massremove', $GLOBALS['I18N']->get('Bulk remove subscribers')).'</li>';
$html .= '<li id="usercheck">'.PageLink2('usercheck', $GLOBALS['I18N']->get('Verify subscribers')).'</li>';
if (ALLOW_IMPORT) {
    $html .= '<li id="import">'.PageLink2('import', $GLOBALS['I18N']->get('Import subscribers')).'</li>';
}
$html .= '<li id="export">'.PageLink2('export', $GLOBALS['I18N']->get('Export subscribers')).'</li>';
$html .= '</ul>';

$p = new UIPanel($GLOBALS['I18N']->get('subscriber management functions'), $html);
echo $p->display();
