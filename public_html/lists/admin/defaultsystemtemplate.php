<?php

## add default system template
## this should be part of the "UI theme"

print '<h2>Default system template</h2>';

$template = ' <div id="container" style="margin: 0 20px; min-width: 920px">
      <div id="wrapper" style="padding: 20px; background-color: #FFFFFF">
        <div id="mainContent" style="margin-right: 390px; min-height: 400px">
          <div class="panel" style="position: relative;
border: 6px solid #5DAEE1; 
margin-bottom: 20px;
background-color: #5DAEE1; 
-moz-border-radius:6px; 
-webkit-border-radius:6px; 
border-radius:6px;
-moz-box-shadow: 1px 1px 8px rgba(0, 0, 0, 0.4);
-webkit-box-shadow: 1px 1px 8px rgba(0, 0, 0, 0.4);
box-shadow: 1px 1px 8px rgba(0, 0, 0, 0.6);
">
            <div class="content" style="padding: 5px; 
-moz-border-radius:3px; 
-webkit-border-radius:3px; 
border-radius:3px;
background-color: #F2F2F2
">
              <h3 style="display: block; background-color: #5DAEE1; color: #FFF; margin: 0; padding: 3px 5px 10px 5px; line-height: 1.2; font-size: 18px; letter-spacing: 0; text-transform:capitalize;">[SUBJECT]</h3>
              [CONTENT]
              [SIGNATURE]
            </div>
          </div>
        </div>
      </div>
    </div>
';

$exists = Sql_Fetch_Row_Query(sprintf('select * from %s where title = "System Template"',$GLOBALS['tables']['template']));
if ($exists[0]) {
  print '<p>'.$GLOBALS['I18N']->get('The default system template already exists').'</p>';
  print '<p>'.PageLinkButton('templates',$GLOBALS['I18N']->get('Go back to templates')).'</p>';
} else {
  Sql_Query(sprintf('insert into %s (title,template,listorder) values("System Template","%s",0)',
    $GLOBALS['tables']['template'],addslashes($template)));
  $newid = Sql_Insert_Id();
  saveConfig('systemmessagetemplate',$newid);
  print '<p>'.$GLOBALS['I18N']->get('The default system template has been added as template with ID').' '.$newid.' </p>';
  print '<p>'.PageLinkButton('templates',$GLOBALS['I18N']->get('Go back to templates')).'</p>';
  print '<p>'.PageLinkButton('template&amp;id='.$newid,$GLOBALS['I18N']->get('Edit template')).'</p>';

}
  

