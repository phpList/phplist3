<?php

## add default system template
## this should be part of the "UI theme"

print '<h2>Default system template</h2>';

$template = '<div style="margin:0; text-align:center; width:100%; background:#EEE;min-width:240px;height:100%;"><br />
    <div style="width:96%;margin:0 auto; border-top:6px solid #369;border-bottom: 6px solid #369;background:#DEF;" >
        <h3 style="margin-top:5px;background-color:#69C; font-weight:normal; color:#FFF; text-align:center; margin-bottom:5px; padding:10px; line-height:1.2; font-size:21px; text-transform:capitalize;">[SUBJECT]</h3>
        <div style="text-align:justify;background:#FFF;padding:20px; border-top:2px solid #369;min-height:200px;font-size:13px; border-bottom:2px solid #369;">[CONTENT]<div style="clear:both"></div></div>
        <div style="clear:both;background:#69C;font-weight:normal; padding:10px;color:#FFF;text-align:center;font-size:11px;margin:5px 0px">[FOOTER]<br/>[SIGNATURE]</div>
    </div>
<br /></div>';

$exists = Sql_Fetch_Row_Query(sprintf('select * from %s where title = "System Template"', $GLOBALS['tables']['template']));
if ($exists[0]) {
    print '<p>'.$GLOBALS['I18N']->get('The default system template already exists').'</p>';
    print '<p>'.PageLinkButton('templates', $GLOBALS['I18N']->get('Go back to templates')).'</p>';
} else {
    Sql_Query(sprintf('insert into %s (title,template,listorder) values("System Template","%s",0)',
    $GLOBALS['tables']['template'], addslashes($template)));
    $newid = Sql_Insert_Id();
    saveConfig('systemmessagetemplate', $newid);
    print '<p>'.$GLOBALS['I18N']->get('The default system template has been added as template with ID').' '.$newid.' </p>';
    print '<p>'.PageLinkButton('templates', $GLOBALS['I18N']->get('Go back to templates')).'</p>';
    print '<p>'.PageLinkButton('template&amp;id='.$newid, $GLOBALS['I18N']->get('Edit template')).'</p>';
}
