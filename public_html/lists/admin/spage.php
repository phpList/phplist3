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
if ( !isSuperUser()) {
    $access = accessLevel('list');
    switch ($access) {
        case 'owner':
            $subselect = ' where owner = '.$_SESSION['logindetails']['id'];
            break;
        case 'all':
            $subselect = '';
            break;
        case 'none':
        default:
            $subselect = ' where id = 0';
            break;
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

if (isset($_REQUEST['reset'])) {
    $reset = sprintf('%d', $_REQUEST['reset']);
} else {
    $reset = 0;
}


if ($reset) {
    $valuesToUpdate =  array(
        'header' => $defaultheader,
        'footer' => $defaultfooter,
    );

    foreach ($valuesToUpdate as $key => $value){
        $query = sprintf('update %s set data = "%s" where name = "%s" and id = %d', $tables['subscribepage_data'], sql_escape($value), $key, $reset);
        Sql_Query($query);
    }
}





echo formStart('name="pagelist" class="spageEdit" ');
echo '<input type="hidden" name="active[-1]" value="1" />'; //# to force the active array to exist
$ls = new WebblerListing($GLOBALS['I18N']->get('subscribe pages'));
$ls->setElementHeading($GLOBALS['I18N']->get('ID'));

$req = Sql_Query(sprintf('select * from %s %s order by title', $tables['subscribepage'], $subselect));
while ($p = Sql_Fetch_Array($req)) {
    $ls->addElement($p['id']);
    $ls->setClass($p['id'], 'row1');
    $ls->addColumn($p['id'], $GLOBALS['I18N']->get('Title'), strip_tags(stripslashes($p['title'])));
    if (($require_login && isSuperUser()) || !$require_login) {
        $ls->addColumn($p['id'], $GLOBALS['I18N']->get('Owner'), adminName($p['owner']));
        if ($p['id'] == $default) {
            $checked = 'checked="checked"';
        } else {
            $checked = '';
        }
        $ls->addColumn($p['id'], $GLOBALS['I18N']->get('default'),
            sprintf('<input type="radio" name="default" value="%d" %s onchange="document.pagelist.submit()" />',
                $p['id'], $checked),'',' text-center');
    } else {
        $adminname = '';
        $isdefault = '';
    }
    $ls->addColumn($p['id'], s('active'),
        sprintf('<input type="checkbox" name="active[%d]" value="1" %s  onchange="document.pagelist.submit()" />',
            $p['id'], $p['active'] ? 'checked="checked"' : ''),'',' text-center');
    $ls->addRow($p['id'],
        $p['active'] ? '<span class="yes" title="'.s('active').'"></span>' : '<span class="no" title="'.s('not active').'"></span>',
        sprintf('<span class="edit"><a class="button" href="%s&amp;id=%d" title="'.s('edit').'">%s</a></span>',
            PageURL2('spageedit', ''), $p['id'], s('edit')).
        sprintf('<span class="delete"><a class="button" href="javascript:deleteRec(\'%s\');" title="'.s('delete').'">%s</a></span>',
            PageURL2('spage', '', 'delete='.$p['id']), s('del')).
        sprintf('<span class="view"><a class="button" target="_blank" href="%s&amp;id=%d" title="'.s('view').'">%s</a></span>',
            getConfig('subscribeurl'), $p['id'], s('view')).
        sprintf('<span class="resettemplate"><a class="button"  href = "javascript:confirmOpenUrl(\''.htmlentities(s('Are you sure you want to reset this subscription page template?')).'\', \'%s\')" title="'.s('reset style to default').'">%s</a></span>',
            PageURL2('spage', '',  'reset='.$p['id']), s('reset styling to default'))
    );
}
echo '<p class="button pull-right">'.PageLink2('spageedit', s('Add a new subscribe page')).'</p><div class="clearfix"></div>';

echo $ls->display();
?>
</form>
