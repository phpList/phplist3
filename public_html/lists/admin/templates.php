<?php

require_once dirname(__FILE__).'/accesscheck.php';

echo '<p class="button pull-right fright">'.PageLink2('template', s('Add new Template')).'</p>';

if (isset($_GET['delete'])) {
    // delete the index in delete
    $delete = sprintf('%d', $_GET['delete']);
    echo '<div class="actionresult alert alert-info">';
    echo s('Template with ID')." $delete ".s('deleted');
    echo '</div>';
    $result = Sql_query('delete from '.$tables['template']." where id = $delete");
    $result = Sql_query('delete from '.$tables['templateimage']." where template = $delete");
}
if (isset($_POST['defaulttemplate'])) {
    saveConfig('defaultmessagetemplate', sprintf('%d', $_POST['defaulttemplate']));
}
if (isset($_POST['systemtemplate'])) {
    saveConfig('systemmessagetemplate', sprintf('%d', $_POST['systemtemplate']));
}

$req = Sql_Query("select * from {$tables['template']} order by listorder");
if (!Sql_Affected_Rows()) {
    echo '<p class="information">'.s('No template have been defined').'</p>';
}

$defaulttemplate = getConfig('defaultmessagetemplate');
$systemtemplate = getConfig('systemmessagetemplate');
echo formStart('name="templates" class="templatesEdit" ');
$ls = new WebblerListing(s('Campaign templates'));
$ls->setElementHeading('Template');
while ($row = Sql_fetch_Array($req)) {
    $img_template = '<img src="images/no-image-template.png" />';
    if (file_exists('templates/'.$row['id'].'.jpg')) {
        $img_template = '<img src="templates/'.$row['id'].'.jpg" />';
    }
    $element = $row['title'];
    $ls->addElement($element, PageUrl2('template&amp;id='.$row['id']));
//  $imgcount = Sql_Fetch_Row_query(sprintf('select count(*) from %s where template = %d',
//    $GLOBALS['tables']['templateimage'],$row['id']));
//  $ls->addColumn($element,s('# imgs'),$imgcount[0]);
//  $ls->addColumn($element,s('View'),);
    $ls->addColumn($element, s('Campaign Default'),
        sprintf('<input type=radio name="defaulttemplate" value="%d" %s onchange="document.templates.submit();">',
            $row['id'], $row['id'] == $defaulttemplate ? 'checked' : ''));
    $ls->addColumn($element, s('System').Help('systemmessage'),
        sprintf('<input type=radio name="systemtemplate" value="%d" %s onchange="document.templates.submit();">',
            $row['id'], $row['id'] == $systemtemplate ? 'checked' : ''));
    $ls->addColumn($element, s('Action'),
        PageLinkDialogOnly(
            'viewtemplate&amp;id='.$row['id']
            , $GLOBALS['img_view']
        ).
        '<span class="edit">'.
            PageLinkButton('template', $GLOBALS['I18N']->get('Edit'), 'id='.$row['id'], '', s('Edit'))
        .'</span>
        <span class="delete alignButtons">
            <a class="button" href="javascript:deleteRec(\''.PageUrl2('templates', '', 'delete='.$row['id']).'\')" title="'.s('delete').'">'.
                s('delete')
            .'</a>
        </span>'
    );

}
echo $ls->display();

echo '</form>';

echo '<p class="button">'.PageLink2('defaultsystemtemplate',
            s('Add templates from default selection')).'</p>';

