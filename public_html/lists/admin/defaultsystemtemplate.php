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
$template1 = '<div style="max-width:800px;width:100%;background:#FFF;display:block;margin:0px auto;font-family: Arial,sans-serif;text-align:center;padding:30px 0">

<table style="width:100%;" cellpadding=0 cellspacing=0>
<tbody>
<tr>
<td style="text-align:left;width:70%">
    <div style="display:inline-block;max-width:100%;font-size:30px;margin-bottom:10px"><a href="[WEBSITE]" style="color:#FF9900;text-decoration:none"><img src="[LOGO:400]" style="width:100%" alt="NEWSLETTER" /></a></div>
</td>
<td style="text-align:right;padding-bottom:6px" valign="bottom">
    <div style="width:30px;height:30px;background:#FF9900;display:inline-block;margin-right:7px;margin-bottom:0px;float:right"></div> 
    <div style="width:8px;height:8px;background:#000000;display:inline-block;margin-right:7px;margin-bottom:7px"></div><br /> 
    <div style="width:15px;height:15px;background:#000000;display:inline-block;margin-right:7px;"></div> 
</td>
</tr>
</tbody>
</table>

<table style="background:#3366FF;color:#FFFFFF;width:100%;" cellpadding=0 cellspacing=0>
<tbody>
<tr>
    <td rowspan="2" style="width:15px;padding:0;margin:0;background:#FFF;border-left:7px solid #3366FF;height:150px"></td>
    <td rowspan="2" style="width:6px;padding:0;margin:0;background:#FFF;border-left:7px solid #3366FF;height:150px"></td>
	<td style="text-align:right;vertical-align:top;padding-top:7px" valign="top;"> 
        <div style="width:8px;height:8px;background:#FFFFFF;display:inline-block;margin-right:7px;float:right"></div> 
        <div style="width:15px;height:15px;background:#FFFFFF;display:inline-block;margin-right:7px;float:right"></div> 
	</td>
</tr>
<tr>
	<td style="padding:10px 20px;text-align:right;font-size:30px;vertical-align:bottom" valign="bottom">[SUBJECT]</td>
</tr>
</tbody>
</table>

<hr style="border:0px;border-bottom: 5px solid #FF9900;margin:5px 0" />


<table style="background:#EEEEEE;color:#333333;width:100%;margin:10px 0;">
<tbody><tr><td style="padding:30px 5%;font-size:16px">[CONTENT]</td></tr></tbody>
</table>

<hr style="border:0px;border-bottom: 5px solid #3366FF;margin:5px 0px" />

<table style="background:#FF9900;width:100%;padding:0;margin:0" cellpadding=0 cellspacing=0>
<tbody><tr>
    <td style="color:#000000;font-size:11px;padding:0;margin:0;padding:20px">[FOOTER]</td>
    <td rowspan="2" style="width:6px;padding:0;margin:0;background:#FFFFFF;border-right:7px solid #FF9900;height:100px"></td>
    <td rowspan="2" style="width:15px;padding:0;margin:0;background:#FFFFFF;border-right:7px solid #FF9900;height:100px"></td>
</tr></tbody>
</table>

<hr style="border:0px;border-bottom: 5px solid #000000;margin:5px 0" />

<table style="width:100%" cellpadding=0 cellspacing=0;>
<tbody><tr>
<td>
    <div style="width:8px;height:8px;background:#3366FF;display:inline-block;margin-right:10px"></div> 
    <div style="width:8px;height:8px;background:#3366FF;display:inline-block;margin-right:10px"></div> 
    <div style="width:8px;height:8px;background:#3366FF;display:inline-block;margin-right:10px"></div> 
</td>
<td style="text-align:right">
    <a href="[FORWARDURL]" style="font-size:11px;font-weight:bold;text-decoration:none;color:#FF9900">FORWARD THIS MESSAGE</a>
</td>
</tr></tbody>
</table>
</div>
';
$template2 = '<p><style type="text/css">#ushuaia h1{font-size:36px}#ushuaia h2{font-size:30px}#ushuaia h3{font-size:24px}#ushuaia h4{font-size:21px}#ushuaia h1,#ushuaia h2,#ushuaia h3,#ushuaia h4,#ushuaia b,#ushuaia a {font-family:\'Times New Roman\', Georgia, serif !Important;color:#000 !important} #ushuaia p{font-family:\'Times New Roman\',Georgia,serif !important;color:#000 !important}</style></p>
<div style="margin:0; text-align:center; width:98%; background:#EEE;min-width:240px;height:100%;" id="ushuaia">
<div style="width:98%;margin:0 auto;border:10px dashed #000;">
<table height="100%" align="center" width="98%">
    <tbody>
        <tr>
            <td height="100px" bgcolor="#EEE" style="border-bottom:1px dotted #888">
            <h3 style="margin:10px;text-align:center;font-weight:normal; line-height:1; font-size:70px;">[SUBJECT]</h3>
            </td>
        </tr>
        <tr>
            <td bgcolor="#FFF" style="color:#666;margin:10px">
            <div style="margin:30px;color:#000 !Important">[CONTENT]</div>
            </td>
        </tr>
        <tr>
            <td style="background:#CCC;padding:10px;color:#000;text-align:center;font-size:14px;margin:1px 0px;border-top:1px dotted #666">[FOOTER]</td>
        </tr>
        <tr>
            <td style="background:#666;padding:5px;color:#FFF;text-align:center;font-size:11px;margin:1px 0px">[SIGNATURE]</td>
        </tr>
    </tbody>
</table>
</div>
<p>&nbsp;</p>
</div>';
$template3 = '<p><style type="text/css">#baires h1,#baires h2,#baires h3,#baires h4,#baires b,#baires a {color:#000 !important} #baires p{color:#666 !important}#baires_footer a{color:#EEE !important}</style></p>
<div style="margin:0; text-align:center; width:100%; background:#EEE;min-width:240px;height:100%;" id="baires">
<div style="width:96%;margin:0 auto; border:10px double #000;">
<div style="background:#000;padding:5px 0px 5px 15px;color:#777;letter-spacing:20px;text-align:center;font-size:15px;margin:1px 0px">NEWSLETTER</div>
<table height="100%" width="100%" style="font-family:Times New Roman, Garamond, serif;">
    <tbody>
        <tr>
            <td bgcolor="#666" align="right" width="35" valign="top"><br />
            <h3 style="margin:10px;text-align:right;font-weight:normal; color:#FFF !important; line-height:1.2; font-size:30px;">[SUBJECT]</h3>
            </td>
            <td bgcolor="#FFF" style="color:#666;margin:10px">
            <div style="margin:10px">[CONTENT]</div>
            </td>
        </tr>
        <tr>
            <td style="background:#000;padding:5px;height:50px;color:#FFF !important" id="baires_footer" colspan="2">[FOOTER]</td>
        </tr>
        <tr>
            <td style="background:#333;height:20px;text-align:center" colspan="2">[SIGNATURE]</td>
        </tr>
    </tbody>
</table>
</div>
</div>';
echo formStart();
echo '<div> 
  <input type="radio" name="template" value="systemtemplate" checked>  System Template<br>
  <input type="radio" name="template" value="template1"> Template 1<br>
  <input type="radio" name="template" value="template2"> Template 2<br> 
  <input type="radio" name="template" value="template3"> Template 3<br> 
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
        case 'template3':
            $title = "Template three";
            $content = $template3;
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
        if ($title === 'System Template') {
            saveConfig('systemmessagetemplate', $newid);
        }
        echo '<p>' . s('The selected default template has been added as template with ID') . ' ' . $newid . ' </p>';
        echo '<p>' . PageLinkButton('templates', s('Go back to templates')) . '</p>';
        echo '<p>' . PageLinkButton('template&amp;id=' . $newid, s('Edit the added template')) . '</p>';
    }
}
