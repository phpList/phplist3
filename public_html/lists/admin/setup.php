<?php
require_once dirname(__FILE__).'/accesscheck.php';

# quick installation checklist

# initialise database
# setup config values
# configure attributes
# create lists
# create subscribe pages
# add subscribers

$alldone = 1;

$html = '';
$html .= '<table class="setupMain">';

$link = PageLink2("initialise",$GLOBALS['I18N']->get('go_there'));
if (!empty($link)) {
  $html .=
  '<tr><td>'.$GLOBALS['I18N']->get('initialise_database').'</td>
  <td>'.$link.'</td><td>';

  if (Sql_Table_Exists($tables["config"],1)) {
    $html .= $GLOBALS["img_tick"];
  } else {
    $html .= $GLOBALS["img_cross"];
    $alldone = 0;  
  }

  $html .= '</td></tr>';
}

$link = PageLink2("admin&amp;id=1",$GLOBALS['I18N']->get('go_there'));
if (!empty($link) && $GLOBALS["require_login"]) {
  $html .= '<tr><td>'.$GLOBALS['I18N']->get('change_admin_passwd').' </td>
  <td>'.$link.'</td><td>';
  $query
  = " select password"
  . " from ${tables['admin']}"
  . " where loginname = 'admin'";
  $curpwd = Sql_Fetch_Row_Query($query);
  if ($curpwd[0] != "phplist") {
    $html .= $GLOBALS["img_tick"];
  } else {
    $alldone = 0;  
    $html .= $GLOBALS["img_cross"];
  }

  $html .= '</td></tr>';
}

$link = PageLink2("configure",$GLOBALS['I18N']->get('go_there'));
if (!empty($link)) {
  $html .= '<tr><td>'.$GLOBALS['I18N']->get('Verify Settings').'</td>
    <td>'.$link.'</td><td>';
  $query
  = " select value"
  . " from ${tables['config']}"
  . " where item = 'admin_address'";
  $data = Sql_Fetch_Row_Query($query);
  if ($data[0]) {
    $html .= $GLOBALS["img_tick"];
  } else {
    $alldone = 0;  
    $html .= $GLOBALS["img_cross"];
  }

  $html .= '</td></tr>';
}

$html .= '<tr><td>'.$GLOBALS['I18N']->get('config_attribs').'</td>
<td>'.PageLink2("attributes",$GLOBALS['I18N']->get('go_there')).'</td><td>';
$req = Sql_Query("select * from {$tables["attribute"]}");
if (Sql_Affected_Rows()) {
  $html .= $GLOBALS["img_tick"];
} else {
  $alldone = 0;  
  $html .= $GLOBALS["img_cross"];
}

$html .= '</td></tr>';

$html .= '<tr><td>'.$GLOBALS['I18N']->get('create_lists').'</td>
<td>'.PageLink2("list",$GLOBALS['I18N']->get('go_there')).'</td><td>';
$req = Sql_Query("select * from ${tables['list']} where active <> 0");
if (Sql_Affected_Rows()) {
  $html .= $GLOBALS["img_tick"];
} else {
  $alldone = 0;  
  $html .= $GLOBALS["img_cross"];
}
$html .= '</td></tr>';

$html .= '<tr><td>'.$GLOBALS['I18N']->get('create_subscr_pages').'</td>
<td>'.PageLink2("spage",$GLOBALS['I18N']->get('go_there')).'</td><td>';
$req = Sql_Query("select * from {$tables["subscribepage"]}");
if (Sql_Affected_Rows()) {
  $html .= $GLOBALS["img_tick"];
} else {
  $alldone = 0;  
  $html .= $GLOBALS["img_cross"];
}

$html .= '</td></tr>';
$html .= '<tr><td>'.$GLOBALS['I18N']->get('Add some subscribers').'</td>
<td>'.PageLink2("import",$GLOBALS['I18N']->get('go_there')).'</td><td>';
$req = Sql_Query("select * from {$tables["user"]}");
if (Sql_Affected_Rows()) {
  $html .= $GLOBALS["img_tick"];
} else {
  $alldone = 0;  
  $html .= $GLOBALS["img_cross"];
}

$html .= '</td></tr>';

$html .= '</table>';

if ($alldone) {
  $html .= Info($GLOBALS['I18N']->get('Congratulations, phpList is set up, you are ready to start mailing')).'<br/>'.PageLinkActionButton('send',$GLOBALS['I18N']->get('Start a message campaign'));
  unset($_SESSION['firstinstall']);
}

$panel = new UIPanel($GLOBALS['I18N']->get('configuration steps'),$html);
print $panel->display();
