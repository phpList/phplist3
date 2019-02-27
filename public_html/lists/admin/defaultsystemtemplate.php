<?php
/**
 * Add default templates including default system template
 *
 */

echo '<h2>Default template</h2>';

$systemtemplate = '<div style="margin:0; text-align:center; width:100%; background:#EEE;min-width:240px;height:100%;"><br />
    <div style="width:96%;margin:0 auto; border-top:6px solid #369;border-bottom: 6px solid #369;background:#DEF;" >
        <h3 style="margin-top:5px;background-color:#69C; font-weight:normal; color:#FFF; text-align:center; margin-bottom:5px; padding:10px; line-height:1.2; font-size:21px; text-transform:capitalize;">[SUBJECT]</h3>
        <div style="text-align:justify;background:#FFF;padding:20px; border-top:2px solid #369;min-height:200px;font-size:13px; border-bottom:2px solid #369;">[CONTENT]<div style="clear:both"></div></div>
        <div style="clear:both;background:#69C;font-weight:normal; padding:10px;color:#FFF;text-align:center;font-size:11px;margin:5px 0px">[FOOTER]<br/>[SIGNATURE]</div>
    </div>
<br /></div>';

$template1 = '<div style="margin:0; text-align:center; width:100%; background:black;min-width:240px;height:100%;"><br />
    <div style="width:96%;margin:0 auto; border-top:6px solid #369;border-bottom: 6px solid #369;background:#DEF;" >
        <h3 style="margin-top:5px;background-color:#69C; font-weight:normal; color:#FFF; text-align:center; margin-bottom:5px; padding:10px; line-height:1.2; font-size:21px; text-transform:capitalize;">[SUBJECT]</h3>
        <div style="text-align:justify;background:#FFF;padding:20px; border-top:2px solid #369;min-height:200px;font-size:13px; border-bottom:2px solid #369;">[CONTENT]<div style="clear:both"></div></div>
        <div style="clear:both;background:#69C;font-weight:normal; padding:10px;color:#FFF;text-align:center;font-size:11px;margin:5px 0px">[FOOTER]<br/>[SIGNATURE]</div>
    </div>
<br /></div>';

$template2 = '<div style="margin:0; text-align:center; width:100%; background:red;min-width:240px;height:100%;"><br />
    <div style="width:96%;margin:0 auto; border-top:6px solid #369;border-bottom: 6px solid #369;background:#DEF;" >
     <div style="text-align:justify;background:#FFF;padding:20px; border-top:2px solid #369;min-height:200px;font-size:13px; border-bottom:2px solid #369;">[CONTENT]<div style="clear:both"></div></div>
        <div style="clear:both;background:#69C;font-weight:normal; padding:10px;color:#FFF;text-align:center;font-size:11px;margin:5px 0px">[FOOTER]<br/>[SIGNATURE]</div>
        </div>
<br /></div>';

echo formStart();

echo '<div> 
  <input type="radio" name="template" value="systemtemplate" checked>  System Template<br>
  <input type="radio" name="template" value="template1"> Template 1<br>
  <input type="radio" name="template" value="template2"> Template 2<br> 
  <input type="submit" value="Submit"  name="Submit">
</form>
</div>

';

if (isset($_POST['Submit'])) {

    $radioVal = $_POST['template'];

        switch ($radioVal) {

            case 'systemtemplate':

                $title = "System Template";
                $content = $systemtemplate;

                break;

            case 'template1':

                $title = "Template one";
                $content = $template1;

                break;

            case 'template2':

                $title = "Template two";
                $content = $template2;

                break;
        }

    $exists = Sql_Fetch_Row_Query(sprintf('select * from %s where title = "%s"',
        $GLOBALS['tables']['template'], $title));

    if ($exists[0]) {
        echo '<p>' . s('This default template already exists') . '</p>';
        echo '<p>' . PageLinkButton('templates', s('Go back to templates')) . '</p>';
    } else {

        Sql_Query(sprintf('insert into %s (title,template,listorder) values("%s","%s",0)',
            $GLOBALS['tables']['template'], $title, addslashes($content)));
        $newid = Sql_Insert_Id();

        saveConfig('systemmessagetemplate', $newid);

        echo '<p>' . s('The selected default template has been added as template with ID') . ' ' . $newid . ' </p>';
        echo '<p>' . PageLinkButton('templates', s('Go back to templates')) . '</p>';
        echo '<p>' . PageLinkButton('template&amp;id=' . $newid, s('Edit the added template')) . '</p>';
    }

}




