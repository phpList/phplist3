
<?php
require_once dirname(__FILE__).'/accesscheck.php';

if (isset($_POST['default']) && $_POST['default']) {
    saveConfig('defaultsubscribepage', $_POST['default']);
}
if (isset($_POST['active']) && is_array($_POST['active'])) {
    Sql_Query(sprintf('update %s set active = 0', $GLOBALS['tables']['subscribepage']));
    foreach ($_POST['active'] as $sPageId => $active) {
        Sql_Query(sprintf('update %s set active = 1 where id = %d', $GLOBALS['tables']['subscribepage'], $sPageId));
    }
}

$default = getConfig('defaultsubscribepage');

$subselect = '';
if ($GLOBALS['require_login'] && !isSuperUser()) {
    $access = accessLevel('list');
    switch ($access) {
    case 'owner':
      $subselect = ' where owner = '.$_SESSION['logindetails']['id'];break;
    case 'all':
      $subselect = '';break;
    case 'none':
    default:
      $subselect = ' where id = 0';break;
  }
}

if (isset($_REQUEST['delete'])) {
    $delete = sprintf('%d', $_REQUEST['delete']);
} else {
    $delete = 0;
}
if ($delete) {
    Sql_Query(sprintf('delete from %s where id = %d',
    $tables['subscribepage'], $delete));
    Sql_Query(sprintf('delete from %s where id = %d',
    $tables['subscribepage_data'], $delete));
    Info($GLOBALS['I18N']->get('Deleted')." $delete");
}
print formStart('name="pagelist" class="spageEdit" ');
print '<input type="hidden" name="active[-1]" value="1" />';## to force the active array to exist
$ls = new WebblerListing($GLOBALS['I18N']->get('subscribe pages'));

$req = Sql_Query(sprintf('select * from %s %s order by title', $tables['subscribepage'], $subselect));
while ($p = Sql_Fetch_Array($req)) {
    $ls->addElement($p['id']);
    $ls->setClass($p['id'], 'row1');
    $ls->addColumn($p['id'], $GLOBALS['I18N']->get('title'), stripslashes($p['title']));
    if (($require_login && isSuperUser()) || !$require_login) {
        $ls->addColumn($p['id'], $GLOBALS['I18N']->get('owner'), adminName($p['owner']));
        if ($p['id'] == $default) {
            $checked = 'checked="checked"';
        } else {
            $checked = '';
        }
        $ls->addColumn($p['id'], $GLOBALS['I18N']->get('default'), sprintf('<input type="radio" name="default" value="%d" %s onchange="document.pagelist.submit()" />', $p['id'], $checked));
    } else {
        $adminname = '';
        $isdefault = '';
    }
    $ls->addColumn($p['id'], s('active'), sprintf('<input type="checkbox" name="active[%d]" value="1" %s  onchange="document.pagelist.submit()" />', $p['id'], $p['active'] ? 'checked="checked"' : ''));
    $ls->addRow($p['id'], $p['active'] ? '<span class="yes" title="'.$GLOBALS['I18N']->get('active').'"></span>' : '<span class="no" title="'.$GLOBALS['I18N']->get('not active').'"></span>',
    sprintf('<span class="edit"><a class="button" href="%s&amp;id=%d" title="'.$GLOBALS['I18N']->get('edit').'">%s</a></span>',
    PageURL2('spageedit', ''), $p['id'], $GLOBALS['I18N']->get('edit')).
    sprintf('<span class="delete"><a class="button" href="javascript:deleteRec(\'%s\');" title="'.$GLOBALS['I18N']->get('delete').'">%s</a></span>',
    PageURL2('spage', '', 'delete='.$p['id']), $GLOBALS['I18N']->get('del')).
    sprintf('<span class="view"><a class="button" href="%s&amp;id=%d" title="'.$GLOBALS['I18N']->get('view').'">%s</a></span>',
    getConfig('subscribeurl'), $p['id'], $GLOBALS['I18N']->get('view')));
}
print $ls->display();
print '<p class="button">'.PageLink2('spageedit', s('Add a new subscribe page')).'</p>';
?>
</form>
