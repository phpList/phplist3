<?php
/**
 * Add default templates including default system template
 *
 */
echo '<h2>Default templates suit</h2>';
$systemtemplate = '<div style="margin:0; text-align:center; width:100%; background:#EEE;min-width:240px;height:100%;"><br />
    <div style="width:96%;margin:0 auto; border-top:6px solid #369;border-bottom: 6px solid #369;background:#DEF;" >
        <h3 style="margin-top:5px;background-color:#69C; font-weight:normal; color:#FFF; text-align:center; margin-bottom:5px; padding:10px; line-height:1.2; font-size:21px; text-transform:capitalize;">[SUBJECT]</h3>
        <div style="text-align:justify;background:#FFF;padding:20px; border-top:2px solid #369;min-height:200px;font-size:13px; border-bottom:2px solid #369;">[CONTENT]<div style="clear:both"></div></div>
        <div style="clear:both;background:#69C;font-weight:normal; padding:10px;color:#FFF;text-align:center;font-size:11px;margin:5px 0px">[FOOTER]<br/>[SIGNATURE]</div>
    </div>
<br /></div>';

$template1 = '<html>
<head>
	<title></title>
</head>
<body>
<div id="shell" style="margin: 0px; background: none repeat scroll 0% 0% rgb(235, 235, 235); color: rgb(102, 102, 102); padding: 0px; text-align: center; width: 100%;"><noscript>
	<table cellspacing="0" border="0" class="required" style="margin: 0px auto;" width="600" cellpadding="10">
		<tr>
			<td align="right" style="font-size: 14px; font-family: Arial; line-height: 1.3em; text-align: left;">
				<p style="font-size: 14px; margin: 10px; line-height: 1.3em; font-family: Arial; padding: 0px; text-align: right;"><a href="[PREFERENCESURL]" title="Change subscription options" style="color: #4A4A4A; text-decoration: none;">Change Subscription</a> : <a href="[UNSUBSCRIBEURL]" title="Unsubscribe" style="color: #4A4A4A; text-decoration: none;">Unsubscribe</a></p>
			</td>
		</tr>
	</table>
	</noscript>
<table cellpadding="0" cellspacing="0" id="Box" style="margin: 10px auto; background: none repeat scroll 0% 0% rgb(255, 255, 255); color: rgb(102, 102, 102); padding: 0pt;" width="580">
	<tbody>
		<tr>
			<td style="font-size: 14px; line-height: 1.3em; font-family: Arial; text-align: left;">
			<table border="0" cellpadding="0" cellspacing="0" id="Header" style="background: none repeat scroll 0% 0% rgb(255, 255, 255);" width="600">
				<tbody>
					<tr valign="top">
						<td style="font-size: 14px; line-height: 1.3em; font-family: Arial; text-align: left;"><a href="[WEBSITE]" title="[ORGANISATION_NAME]"><img alt="***" src="[LOGO:290]" style="display: block;margin-left: auto;margin-right: auto;" /></a></td>
					</tr>
				</tbody>
			</table>
			<!--content table starts-->

			<table border="0" cellpadding="0" cellspacing="0" class="content" style="background-color: rgb(255, 255, 255); color: rgb(102, 102, 102);" width="600">
				<tbody>
					<tr valign="top">
						<td style="font-size: 14px; line-height: 1.3em; font-family: Arial; text-align: left;"><img alt="" height="20" src="pad.png" style="border: 0px none;" width="20" /></td>
						<td class="maincontent" style="font-size: 14px; font-family: Arial; line-height: 1.3em; text-align: left;">
						<h1 style="font-size: 1.8em; margin: 1em 0pt; color: rgb(87, 164, 210); padding: 0pt; letter-spacing: -1px;">[SUBJECT]</h1>

						<p style="font-size: 14px; margin: 0.5em 0pt 1em; line-height: 1.3em; font-family: Arial; text-align: left;">[CONTENT]</p>
						</td>
						<td style="font-size: 14px; line-height: 1.3em; font-family: Arial; text-align: left;"><img alt="" height="20" src="pad.png" style="border: 0px none;" width="20" /></td>
					</tr>
				</tbody>
			</table>
			<!--content table ends--><!--content table starts-->

			<table border="0" cellpadding="0" cellspacing="0" class="content" style="background-color: rgb(255, 255, 255); color: rgb(102, 102, 102);" width="600">
				<tbody>
					<tr valign="top">
						<td style="font-size: 14px; line-height: 1.3em; font-family: Arial; text-align: left;"><img alt="" height="20" src="pad.png" style="border: 0px none;" width="20" /></td>
						<td class="maincontent" style="font-size: 14px; margin:5px;font-family: Arial; line-height: 1.3em; text-align: left;">
						<p style="font-size: 14px; margin: 0.5em 0pt; line-height: 1.3em; font-family: Arial; text-align: left;">[FOOTER]</p>
						</td>
						<td style="font-size: 14px; line-height: 1.3em; font-family: Arial; text-align: left;"><img alt="" height="20" src="pad.png" style="border: 0px none;" width="20" /></td>
					</tr>
				</tbody>
			</table>
			</td>
		</tr>
	</tbody>
</table>
</div>
</body>
</html>

';
$template2 = '<!doctype html>
<html>
<head><meta name="viewport" content="width=device-width" /><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>[SUBJECT]</title>
	<style type="text/css">/* -------------------------------------
          GLOBAL RESETS
      ------------------------------------- */
      
      /*All the styling goes here*/
      
      img {
        border: none;
        -ms-interpolation-mode: bicubic;
        max-width: 100%;
      }
    
      /* -------------------------------------
          BODY & CONTAINER
      ------------------------------------- */
      .body {
        background-color: #f6f6f6;
        width: 100%;
      }
      /* Set a max-width, and make it display as block so it will automatically stretch to that width, but will also shrink down on a phone or something */
      .templatecontainer {
        display: block;
        margin: 0 auto !important;
        /* makes it centered */
        max-width: 580px;
        padding: 10px;
        width: 580px;
      }
      .logo {
      padding-bottom: 10px;
      }
      
      /* This should also be a block element, so that it will fill 100% of the .container */
      .templatecontent {
        box-sizing: border-box;
        display: block;
        margin: 0 auto;
        max-width: 580px;
        padding: 10px;
      }
      /* -------------------------------------
          HEADER, FOOTER, MAIN
      ------------------------------------- */
      .main {
        background: #ffffff;
        border-radius: 3px;
        width: 100%;
      }
      .wrapper {
        box-sizing: border-box;
        padding: 20px;
      }
      .templatecontent-block {
        padding-bottom: 10px;
        padding-top: 10px;
      }
      .footer {
        clear: both;
        margin-top: 10px;
        text-align: center;
        width: 100%;
      }
        .footer td,
        .footer p,
        .footer span,
        .footer a {
          color: #999999;
          font-size: 12px;
          text-align: center;
      }
      /* -------------------------------------
          TYPOGRAPHY
      ------------------------------------- */
      h1,
      h2,
      h3,
      h4 {
        color: #000000;
        font-family: sans-serif;
        font-weight: 400;
        line-height: 1.4;
        margin: 0;
        margin-bottom: 30px;
      }
      h1 {
        font-size: 35px;
        font-weight: 300;
        text-align: center;
        text-transform: capitalize;
      }
      p,
      ul,
      ol {
        font-family: sans-serif;
        font-size: 14px;
        font-weight: normal;
        margin: 0;
        margin-bottom: 15px;
      }
        p li,
        ul li,
        ol li {
          list-style-position: inside;
          margin-left: 5px;
      }
      /* -------------------------------------
          BUTTONS
      ------------------------------------- */
      .templatebtn {
        box-sizing: border-box;
        width: 100%; }
        .templatebtn > tbody > tr > td {
          padding-bottom: 15px; }
        .templatebtn table {
          width: auto;
      }
        .templatebtn table td {
          background-color: #ffffff;
          border-radius: 5px;
          text-align: center;
      }
        .templatebtn a {
          background-color: #ffffff;
          border: solid 1px #3498db;
          border-radius: 5px;
          box-sizing: border-box;
          color: #3498db;
          cursor: pointer;
          display: inline-block;
          font-size: 14px;
          font-weight: bold;
          margin: 0;
          padding: 12px 25px;
          text-decoration: none;
          text-transform: capitalize;
      }
      .templatebtn-primary table td {
        background-color: #3498db;
      }
      .templatebtn-primary a {
        background-color: #3498db;
        border-color: #3498db;
        color: #ffffff;
      }
      /* -------------------------------------
          OTHER STYLES THAT MIGHT BE USEFUL
      ------------------------------------- */
      .last {
        margin-bottom: 0;
      }
      .first {
        margin-top: 0;
      }
      .align-center {
        text-align: center;
      }
      .align-right {
        text-align: right;
      }
      .align-left {
        text-align: left;
      }
      .clear {
        clear: both;
      }
      .mt0 {
        margin-top: 0;
      }
      .mb0 {
        margin-bottom: 0;
      }
      .preheader {
        color: transparent;
        display: none;
        height: 0;
        max-height: 0;
        max-width: 0;
        opacity: 0;
        overflow: hidden;
        mso-hide: all;
        visibility: hidden;
        width: 0;
      }
      .powered-by a {
        text-decoration: none;
      }
      hr {
        border: 0;
        border-bottom: 1px solid #f6f6f6;
        margin: 20px 0;
      }
      /* -------------------------------------
          RESPONSIVE AND MOBILE FRIENDLY STYLES
      ------------------------------------- */
      @media only screen and (max-width: 620px) {
        table[class=body] h1 {
          font-size: 28px !important;
          margin-bottom: 10px !important;
        }
        table[class=body] p,
        table[class=body] ul,
        table[class=body] ol,
        table[class=body] td,
        table[class=body] span,
        table[class=body] a {
          font-size: 16px !important;
        }
        table[class=body] .wrapper,
        table[class=body] .article {
          padding: 10px !important;
        }
        table[class=body] .templatecontent {
          padding: 0 !important;
        }
        table[class=body] .templatecontainer {
          padding: 0 !important;
          width: 100% !important;
        }
        table[class=body] .main {
          border-left-width: 0 !important;
          border-radius: 0 !important;
          border-right-width: 0 !important;
        }
        table[class=body] .templatebtn table {
          width: 100% !important;
        }
        table[class=body] .templatebtn a {
          width: 100% !important;
        }
        table[class=body] .img-responsive {
          height: auto !important;
          max-width: 100% !important;
          width: auto !important;
        }
      }
      /* -------------------------------------
          PRESERVE THESE STYLES IN THE HEAD
      ------------------------------------- */
      @media all {
        .ExternalClass {
          width: 100%;
        }
        .ExternalClass,
        .ExternalClass p,
        .ExternalClass span,
        .ExternalClass font,
        .ExternalClass td,
        .ExternalClass div {
          line-height: 100%;
        }
        .apple-link a {
          color: inherit !important;
          font-family: inherit !important;
          font-size: inherit !important;
          font-weight: inherit !important;
          line-height: inherit !important;
          text-decoration: none !important;
        }
        .templatebtn-primary table td:hover {
          background-color: #34495e !important;
        }
        .templatebtn-primary a:hover {
          background-color: #34495e !important;
          border-color: #34495e !important;
        }
      }
	</style>
</head>
<body style=" background-color: #f6f6f6;font-family: sans-serif;-webkit-font-smoothing: antialiased;font-size: 14px;line-height: 1.4;margin: 0;padding: 0;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
<p>&nbsp;</p>

<table border="0" cellpadding="0" cellspacing="0" class="body" role="presentation" style="border-collapse: separate;mso-table-lspace: 0pt;mso-table-rspace: 0pt;width: 100%;">
	<tbody>
		<tr>
			<td style="font-family: sans-serif;font-size: 14px;vertical-align: top;">&nbsp;</td>
			<td class="container" style="font-family: sans-serif;font-size: 14px;vertical-align: top;">
			<div class="templatecontent">
			<div class="logo">
			<center><img src="[LOGO]" /></center>
			</div>
			<!-- START CENTERED WHITE CONTAINER -->

			<table class="main" role="presentation" style="border-collapse: separate;mso-table-lspace: 0pt;mso-table-rspace: 0pt;width: 100%;"><!-- START MAIN CONTENT AREA -->
				<tbody>
					<tr>
						<td class="wrapper" style="font-family: sans-serif;font-size: 14px;vertical-align: top;">
						<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse: separate;mso-table-lspace: 0pt;mso-table-rspace: 0pt;width: 100%;">
							<tbody>
								<tr>
									<td style="font-family: sans-serif;font-size: 14px;vertical-align: top;"><span class="preheader">This is preheader text. Some clients will show this text as a preview.</span> [CONTENT]</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<!-- END MAIN CONTENT AREA -->
				</tbody>
			</table>
			<!-- START FOOTER -->

			<div class="footer">
			<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse: separate;mso-table-lspace: 0pt;mso-table-rspace: 0pt;width: 100%;">
				<tbody>
					<tr>
						<td class="templatecontent-block" style="font-family: sans-serif;font-size: 14px;vertical-align: top;"><span class="apple-link">[FOOTER]</span>

						<p><a href="[FORWARDURL]" style="color: #3498db; text-decoration: underline;">Forward this message</a></p>
						</td>
					</tr>
				</tbody>
			</table>
			</div>
			<!-- END FOOTER --><!-- END CENTERED WHITE CONTAINER --></div>
			</td>
			<td>&nbsp;</td>
		</tr>
	</tbody>
</table>
</body>
</html>
';

echo formStart();
echo '<div> 
  <input type="radio" name="template" value="systemtemplate" checked>  System Template<br>
  <input type="radio" name="template" value="template1"> Template with Logo<br>
  <input type="radio" name="template" value="template2"> Simple Responsive Template<br> 
  <input type="submit" value="Select"  name="Submit">
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
            $title = "Template with Logo";
            $content = $template1;
            break;
        case 'template2':
            $title = "Simple Responsive Template";
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
        if ($title === 'System Template') {
            saveConfig('systemmessagetemplate', $newid);
        }
        echo '<p>' . s('The selected default template has been added as template with ID') . ' ' . $newid . ' </p>';
        echo '<p>' . PageLinkButton('templates', s('Go back to templates')) . '</p>';
        echo '<p>' . PageLinkButton('template&amp;id=' . $newid, s('Edit the added template')) . '</p>';
    }
}
