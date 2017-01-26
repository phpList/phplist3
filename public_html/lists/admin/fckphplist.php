<?php
require_once dirname(__FILE__).'/accesscheck.php';

if ($_GET['action'] == 'js') {
    ob_end_clean();
    $req = Sql_query("select name from {$tables['attribute']} where type in ('textline','select') order by listorder");
    $attnames = ';preferences url;unsubscribe url';
    $attcodes = ';[PREFERENCES];[UNSUBSCRIBE]';
    while ($row = Sql_Fetch_Row($req)) {
        $attnames .= ';'.strtolower(substr($row[0], 0, 15));
        $attcodes .= ';['.strtoupper($row[0]).']';
    }

    $imgdir = getenv('DOCUMENT_ROOT').$GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/';
    $enable_image_upload = is_dir($imgdir) && is_writable($imgdir) ? 'true' : 'false';

    $smileypath = $_SERVER['DOCUMENT_ROOT'].$GLOBALS['pageroot'].'/images/smiley';
    $smileyextensions = array('gif');
    $smileys = '';
    if ($dir = opendir($smileypath)) {
        while (false !== ($file = readdir($dir))) {
            list($fname, $ext) = explode('.', $file);
            if (in_array($ext, $smileyextensions)) {
                $smileys .= '"'.$file.'",';
            }
        }
    }
    $smileys = substr($smileys, 0, -1); ?>
    oTB_Items.Attribute      = new TBCombo( "Attributes"      , "doAttribute(this)"    , 'Attribute'    , '<?php echo $attnames ?>', '<?php echo $attcodes ?>') ;

    function doAttribute(combo)
    {
    if (combo.value != null && combo.value != "")
    insertHtml(combo.value);
    SetFocus();
    }

    config.BasePath = document.location.protocol + '//' + document.location.host +
    document.location.pathname.substring(0,document.location.pathname.lastIndexOf('/')+1) ;
    config.EditorAreaCSS = config.BasePath + 'css/fck_editorarea.css' ;
    config.BaseUrl = document.location.protocol + '//' + document.location.host + '/' ;
    config.EnableXHTML = false ;
    config.StartupShowBorders = false ;
    config.StartupShowDetails = false ;
    config.ForcePasteAsPlainText  = false ;
    config.AutoDetectPasteFromWord  = true ;
    config.UseBROnCarriageReturn  = true ;
    config.TabSpaces = 4 ;
    config.AutoDetectLanguage = true ;
    config.DefaultLanguage    = "en" ;
    config.SpellCheckerDownloadUrl = "http://www.rochen.com/ieSpellSetup201325.exe" ;
    config.ToolbarImagesPath = config.BasePath + "images/toolbar/" ;
    config.ToolbarSets["Default"] = [
    ['EditSource','-','Cut','Copy','Paste','PasteText','PasteWord','-','SpellCheck','Find','-','Undo','Redo','-','SelectAll','RemoveFormat','-','Link','RemoveLink','-','Image','Table','Rule','SpecialChar','Smiley','-','About'] ,
    ['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull','-','InsertOrderedList','InsertUnorderedList','-','Outdent','Indent','-','ShowTableBorders','ShowDetails','-','Zoom'] ,
    ['Attribute','-','FontFormat','-','Font','-','FontSize','-','TextColor','BGColor']
    ] ;

    //  ['FontStyle','-','FontFormat','-','Font','-','Attribute','-','FontSize','-','TextColor','BGColor']
    config.StyleNames  = ';Main Header;Blue Title;Centered Title' ;
    config.StyleValues = ';MainHeader;BlueTitle;CenteredTitle' ;
    config.ToolbarFontNames = ';Arial;Comic Sans MS;Courier New;Tahoma;Times New Roman;Verdana' ;
    config.LinkShowTargets = true ;
    config.LinkTargets = '_blank;_parent;_self;_top' ;
    config.LinkDefaultTarget = '_blank' ;
    config.ImageBrowser = <?php echo $enable_image_upload ?> ;
    config.ImageBrowserURL = config.BasePath + "../?page=fckphplist&amp;action=browseimage" ;
    config.ImageBrowserWindowWidth  = 600 ;
    config.ImageBrowserWindowHeight = 480 ;

    config.ImageUpload = <?php echo $enable_image_upload ?> ;
    // Page that effectivelly upload the image.
    config.ImageUploadURL = config.BasePath + "../?page=fckphplist&amp;action=uploadimage" ;
    config.ImageUploadWindowWidth  = 600 ;
    config.ImageUploadWindowHeight  = 480 ;
    config.ImageUploadAllowedExtensions = ".gif .jpg .jpeg .png" ;

    config.LinkBrowser = false ;
    config.LinkBrowserURL = config.BasePath + "../?page=fckphplist&amp;action=browsefile" ;
    config.LinkBrowserWindowWidth  = 400 ;
    config.LinkBrowserWindowHeight  = 250 ;

    config.LinkUpload = false ;
    config.LinkUploadURL = config.BasePath + "../?page=fckphplist&amp;action=uploadfile" ;

    //config.SmileyPath  = config.BasePath + "images/smiley/fun/" ;
    config.SmileyPath = document.location.protocol + '//' + document.location.host +'<?php echo $GLOBALS['pageroot'].'/images/smiley/' ?>'

    config.SmileyImages  = [<?php echo $smileys ?>] ;
    config.SmileyColumns = 8 ;
    config.SmileyWindowWidth  = 800 ;
    config.SmileyWindowHeight  = 600 ;

    <?php exit;
} elseif ($_GET['action'] == 'browseimage') {
    /*
 * FCKeditor - The text editor for internet
 * Copyright (C) 2003 Frederico Caldeira Knabben
 *
 * Licensed under the terms of the GNU Lesser General Public License
 * (http://www.opensource.org/licenses/lgpl-license.php)
 *
 * For further information go to http://www.fredck.com/FCKeditor/
 * or contact fckeditor@fredck.com.
 *
 * browse.php: Browse function.
 *
 * Authors:
 *   Frederic TYNDIUK (http://www.ftls.org/ - tyndiuk[at]ftls.org)
 */

// Init var :

  $IMAGES_BASE_URL = 'http://'.$_SERVER['SERVER_NAME'].$GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/';
    $IMAGES_BASE_DIR = getenv('DOCUMENT_ROOT').$GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/';

// End int var

// Thanks : php dot net at phor dot net
function walk_dir($path)
{
    if ($dir = opendir($path)) {
        while (false !== ($file = readdir($dir))) {
            if ($file[0] == '.') {
                continue;
            }
            if (is_dir($path.'/'.$file)) {
                $retval = array_merge($retval, walk_dir($path.'/'.$file));
            } elseif (is_file($path.'/'.$file)) {
                $retval[] = $path.'/'.$file;
            }
        }
        closedir($dir);
    }

    return $retval;
}

    function CheckImgExt($filename)
    {
        $img_exts = array('gif', 'jpg', 'jpeg', 'png');
        foreach ($img_exts as $this_ext) {
            if (preg_match("/\.$this_ext$/", $filename)) {
                return true;
            }
        }

        return false;
    }
    $files = array();
    foreach (walk_dir($IMAGES_BASE_DIR) as $file) {
        $file = preg_replace('#//+#', '/', $file);
        $IMAGES_BASE_DIR = preg_replace('#//+#', '/', $IMAGES_BASE_DIR);
        $file = preg_replace("#$IMAGES_BASE_DIR#", '', $file);
        if (CheckImgExt($file)) {
            $files[] = $file;  //adding filenames to array
        }
    }

    sort($files);  //sorting array

// generating $html_img_lst
foreach ($files as $file) {
    $html_img_lst .= "<a href=\"javascript:getImage('$file');\">$file</a><br/>\n";
}
    ob_end_clean(); ?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" >
<HTML>
  <HEAD>
    <TITLE>Image Browser</TITLE>
    <LINK rel="stylesheet" type="text/css" href="./FCKeditor/css/fck_dialog.css">
    <SCRIPT language="javascript" type="text/javascript">
var sImagesPath  = "<?php echo $IMAGES_BASE_URL; ?>";
var sActiveImage = "" ;

function getImage(imageName)
{
  sActiveImage = sImagesPath + imageName ;
  imgPreview.src = sActiveImage ;
}

function ok()
{
  window.setImage(sActiveImage) ;
  window.close() ;
}
    </SCRIPT>
  </HEAD>
  <BODY bottommargin="5" leftmargin="5" topmargin="5" rightmargin="5">
<TABLE class="fck1" cellspacing="1" cellpadding="1" border="0" width="100%" class="dlg" height="100%">
  <TR height="100%">
    <TD>
      <TABLE class="fck2" cellspacing="0" cellpadding="0" width="100%" border="0" height="100%">
        <TR>
          <TD width="45%" valign="top">
            <table class="fck3" cellpadding="0" cellspacing="0" height="100%" width="100%">
              <tr>
                <td width="100%">File : </td>
              </tr>
              <tr height="100%">
                <td>
                  <DIV class="ImagePreviewArea"><?php echo $html_img_lst ?></DIV>
                </td>
              </tr>
            </table>
          </TD>
          <TD width="10%" >&nbsp;&nbsp;&nbsp;</TD>
          <TD>
            <table class="fck3" cellpadding="0" cellspacing="0" height="100%" width="100%">
              <tr>
                <td width="100%">Preview : </td>
              </tr>
              <tr>
                <td height="100%" align="center" valign="middle">
                  <DIV class="ImagePreviewArea"><IMG id="imgPreview" border=1"/></DIV>
                </td>
              </tr>
            </table>
          </TD>
        </TR>
      </TABLE>
    </TD>
  </TR>
  <TR>
    <TD align="center">
      <p class="button"><INPUT type="button" value="OK"     onclick="ok();"></p> &nbsp;&nbsp;&nbsp;&nbsp;
      <p class="button"><INPUT type="button" value="Cancel" onclick="window.close();"></p><BR/>
    </TD>
  </TR>
</TABLE>
  </BODY>
</HTML>
<?php
exit;
} elseif ($_GET['action'] == 'uploadimage') {
    //  ob_end_clean();

    /*
     * FCKeditor - The text editor for internet
     * Copyright (C) 2003 Frederico Caldeira Knabben
     *
     * Licensed under the terms of the GNU Lesser General Public License
     * (http://www.opensource.org/licenses/lgpl-license.php)
     *
     * For further information go to http://www.fredck.com/FCKeditor/
     * or contact fckeditor@fredck.com.
     *
     * upload.php: Basic file upload manager for the editor. You have
     *   to have set a directory called "userimages" in the root folder
     *   of your web site.
     *
     * Authors:
     *   Frederic TYNDIUK (http://www.ftls.org/ - tyndiuk[at]ftls.org)
     */

// Init var :

    $UPLOAD_BASE_URL = 'http://'.$_SERVER['SERVER_NAME'].$GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/';
    $UPLOAD_BASE_DIR = getenv('DOCUMENT_ROOT').$GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/';

// End int var

    ?>

    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" >
    <HTML>
    <HEAD>
        <TITLE>File Uploader</TITLE>
        <LINK rel="stylesheet" type="text/css" href="./FCKeditor/css/fck_dialog.css">
    </HEAD>
    <BODY>
    <form>
        <TABLE eight="100%" width="100%">
            <TR>
                <TD align=center valign=middle><B>
                        Upload in progress...
                        <BR><BR>
                        <?php

                        if (file_exists($UPLOAD_BASE_DIR.$_FILES['FCKeditor_File']['name'])) {
                            echo 'Error : File '.$_FILES['FCKeditor_File']['name']." exists, can't overwrite it...";
                            echo '<BR><BR><INPUT type="button" value=" Cancel " onclick="window.close()">';
                        } else {
                            if (is_uploaded_file($_FILES['FCKeditor_File']['tmp_name'])) {
                                $savefile = $UPLOAD_BASE_DIR.$_FILES['FCKeditor_File']['name'];

                                if (move_uploaded_file($_FILES['FCKeditor_File']['tmp_name'], $savefile)) {
                                    chmod($savefile, 0666); ?>
                                    <SCRIPT
                                        language=javascript>window.opener.setImage('<?php echo $UPLOAD_BASE_URL.$_FILES['FCKeditor_File']['name']; ?>');
                                        window.close();</SCRIPT>";
                                    <?php

                                }
                            } else {
                                echo 'Error : ';
                                switch ($_FILES['FCKeditor_File']['error']) {
                                    case 0: //no error; possible file attack!
                                        echo 'There was a problem with your upload.';
                                        break;
                                    case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
                                        echo 'The file you are trying to upload is too big.';
                                        break;
                                    case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
                                        echo 'The file you are trying to upload is too big.';
                                        break;
                                    case 3: //uploaded file was only partially uploaded
                                        echo 'The file you are trying upload was only partially uploaded.';
                                        break;
                                    case 4: //no file was uploaded
                                        echo 'You must select an image for upload.';
                                        break;
                                    default: //a default error, just in case!  :)
                                        echo 'There was a problem with your upload.';
                                        break;
                                }
                            }
                            echo '<BR><BR><INPUT type="button" value=" Cancel " onclick="window.close()">';
                        } ?>
                    </B></TD>
            </TR>
        </TABLE>
    </form>
    </BODY>
    </HTML>
    <?php
//exit;
} elseif ($_GET['action'] == 'js2') {
    ob_end_clean();
    header('Content-type: text/plain');
    $req = Sql_query(sprintf('select name from %s where type in ("textline","select") order by listorder',
        $GLOBALS['tables']['attribute']));
    $attnames = ';preferences url;unsubscribe url';
    $attcodes = ';[PREFERENCES];[UNSUBSCRIBE]';
    while ($row = Sql_Fetch_Row($req)) {
        $attnames .= ';'.strtolower(substr($row[0], 0, 15));
        $attcodes .= ';['.strtoupper($row[0]).']';
    }

    $imgdir = $_SERVER['DOCUMENT_ROOT'].$GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/';
    $enable_image_upload = is_dir($imgdir) && is_writable($imgdir) ? 'true' : 'false';

    $smileypath = $_SERVER['DOCUMENT_ROOT'].$GLOBALS['pageroot'].'/images/smiley';
    $smileyextensions = array('gif');
    $smileys = '';
    if ($dir = opendir($smileypath)) {
        while (false !== ($file = readdir($dir))) {
            if (strpos($file, '.') !== false) {
                list($fname, $ext) = explode('.', $file);
                if (in_array($ext, $smileyextensions)) {
                    $smileys .= '"'.$file.'",';
                }
            }
        }
    }
    $smileys = substr($smileys, 0, -1); ?>
    /*
    * FCKeditor - The text editor for internet
    * Copyright (C) 2003-2005 Frederico Caldeira Knabben
    *
    * Licensed under the terms of the GNU Lesser General Public License:
    *    http://www.opensource.org/licenses/lgpl-license.php
    *
    * For further information visit:
    *    http://www.fckeditor.net/
    *
    * File Name: fckconfig.js
    *  Editor configuration settings.
    *  See the documentation for more info.
    *
    * File Authors:
    *    Frederico Caldeira Knabben (fredck@fckeditor.net)
    */

    FCKConfig.CustomConfigurationsPath = '' ;

    FCKConfig.EditorAreaCSS = FCKConfig.BasePath + 'css/fck_editorarea.css' ;

    FCKConfig.DocType = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' ;

    FCKConfig.BaseHref = '' ;

    FCKConfig.FullPage = false;

    FCKConfig.Debug = false ;

    FCKConfig.SkinPath = FCKConfig.BasePath + 'skins/default/' ;

    FCKConfig.PluginsPath = FCKConfig.BasePath + 'plugins/' ;

    // FCKConfig.Plugins.Add( 'placeholder', 'en,it' ) ;

    FCKConfig.AutoDetectLanguage  = true ;
    FCKConfig.DefaultLanguage   = 'en' ;
    FCKConfig.ContentLangDirection  = 'ltr' ;

    FCKConfig.EnableXHTML   = true ;  // Unsupported: Do not change.
    FCKConfig.EnableSourceXHTML = true ;  // Unsupported: Do not change.

    FCKConfig.ProcessHTMLEntities = true ;
    FCKConfig.IncludeLatinEntities  = true ;
    FCKConfig.IncludeGreekEntities  = true ;

    FCKConfig.FillEmptyBlocks = true ;

    FCKConfig.FormatSource    = true ;
    FCKConfig.FormatOutput    = true ;
    FCKConfig.FormatIndentator  = '' ;

    FCKConfig.GeckoUseSPAN  = true ;
    FCKConfig.StartupFocus  = false ;
    FCKConfig.ForcePasteAsPlainText = false ;
    FCKConfig.ForceSimpleAmpersand  = false ;
    FCKConfig.TabSpaces   = 0 ;
    FCKConfig.ShowBorders = true ;
    FCKConfig.UseBROnCarriageReturn = false ;
    FCKConfig.ToolbarStartExpanded  = true ;
    FCKConfig.ToolbarCanCollapse  = true ;
    FCKConfig.IEForceVScroll = false ;
    FCKConfig.IgnoreEmptyParagraphValue = true ;

    FCKConfig.ToolbarSets["Default"] = [
    ['Source','DocProps','-','NewPage','Preview'],
    ['Cut','Copy','Paste','PasteText','PasteWord','-','Print','SpellCheck'],
    ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat','Link','Unlink','Anchor'],
    ['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript'],
    ['OrderedList','UnorderedList','-','Outdent','Indent'],
    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
    ['Image','Flash','Table','Rule','Smiley','SpecialChar','UniversalKey','TextColor','BGColor'],
    '/',
    ['Style','FontFormat','FontName','FontSize'],
    ['About']
    ] ;

    // @@@ 'Save' taken out, gives the impression that the message is saved, but it isn't

    //@@@ need to add attribute selection

    FCKConfig.ToolbarSets["Basic"] = [
    ['Bold','Italic','-','OrderedList','UnorderedList','-','Link','Unlink','-','About']
    ] ;

    FCKConfig.ContextMenu = ['Generic','Link','Anchor','Image','Flash','Select','Textarea','Checkbox','Radio','TextField','HiddenField','ImageButton','Button','BulletedList','NumberedList','TableCell','Table','Form'] ;

    FCKConfig.FontColors = '000000,993300,333300,003300,003366,000080,333399,333333,800000,FF6600,808000,808080,008080,0000FF,666699,808080,FF0000,FF9900,99CC00,339966,33CCCC,3366FF,800080,999999,FF00FF,FFCC00,FFFF00,00FF00,00FFFF,00CCFF,993366,C0C0C0,FF99CC,FFCC99,FFFF99,CCFFCC,CCFFFF,99CCFF,CC99FF,FFFFFF' ;

    FCKConfig.FontNames   = 'Arial;Comic Sans MS;Courier New;Tahoma;Times New Roman;Verdana' ;
    FCKConfig.FontSizes   = '1/xx-small;2/x-small;3/small;4/medium;5/large;6/x-large;7/xx-large' ;
    FCKConfig.FontFormats = 'p;div;pre;address;h1;h2;h3;h4;h5;h6' ;

    FCKConfig.StylesXmlPath   = FCKConfig.EditorPath + 'fckstyles.xml' ;
    FCKConfig.TemplatesXmlPath  = FCKConfig.EditorPath + 'fcktemplates.xml' ;

    FCKConfig.SpellChecker      = '';//'ieSpell' ; // 'ieSpell' | 'SpellerPages'
    FCKConfig.IeSpellDownloadUrl  = '';//'http://www.iespell.com/rel/ieSpellSetup211325.exe' ;

    FCKConfig.MaxUndoLevels = 15 ;

    FCKConfig.DisableImageHandles = false ;
    FCKConfig.DisableTableHandles = false ;

    FCKConfig.LinkDlgHideTarget   = false ;
    FCKConfig.LinkDlgHideAdvanced = false ;

    FCKConfig.ImageDlgHideLink    = false ;
    FCKConfig.ImageDlgHideAdvanced  = false ;

    FCKConfig.FlashDlgHideAdvanced  = false ;

    FCKConfig.LinkBrowser = false ;

    FCKConfig.ImageBrowser = <?php echo $enable_image_upload ?> ;
    // PHP
    FCKConfig.ImageBrowserURL = FCKConfig.BasePath + 'filemanager/browser/default/browser.html?Type=Image&Connector=connectors/phplist/connector.php'

    FCKConfig.ImageBrowserWindowWidth  = screen.width * 0.7 ; // 70% ;
    FCKConfig.ImageBrowserWindowHeight = screen.height * 0.7 ;  // 70% ;

    // @@@ disabled for now
    FCKConfig.FlashBrowser = false ;

    // PHP
    FCKConfig.FlashBrowserURL = FCKConfig.BasePath + 'filemanager/browser/default/browser.html?Type=Flash&Connector=connectors/phplist/connector.php' ;
    FCKConfig.FlashBrowserWindowWidth  = screen.width * 0.7 ; //70% ;
    FCKConfig.FlashBrowserWindowHeight = screen.height * 0.7 ;  //70% ;

    FCKConfig.LinkUpload = false ;
    FCKConfig.LinkUploadURL = FCKConfig.BasePath + 'filemanager/upload/asp/upload.asp' ;
    // PHP // FCKConfig.LinkUploadURL = FCKConfig.BasePath + 'filemanager/upload/php/upload.php' ;
    FCKConfig.LinkUploadAllowedExtensions = "" ;      // empty for all
    FCKConfig.LinkUploadDeniedExtensions  = ".(php|php3|php5|phtml|asp|aspx|ascx|jsp|cfm|cfc|pl|bat|exe|dll|reg|cgi)$" ;  // empty for no one

    FCKConfig.ImageUpload = <?php echo $enable_image_upload ?> ;
    FCKConfig.ImageUploadURL = FCKConfig.BasePath + 'filemanager/upload/phplist/upload.php?Type=Image' ;
    FCKConfig.ImagePath = document.location.protocol + '//' + document.location.host +'<?php echo $GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/' ?>'

    FCKConfig.ImageUploadAllowedExtensions  = ".(jpg|gif|jpeg|png)$" ;    // empty for all
    FCKConfig.ImageUploadDeniedExtensions = "" ;              // empty for no one

    FCKConfig.FlashUpload = false ;
    FCKConfig.FlashUploadURL = FCKConfig.BasePath + 'filemanager/upload/asp/upload.asp?Type=Flash' ;
    // PHP // FCKConfig.FlashUploadURL = FCKConfig.BasePath + 'filemanager/upload/php/upload.php?Type=Flash' ;

    FCKConfig.FlashUploadAllowedExtensions  = ".(swf|fla)$" ;   // empty for all
    FCKConfig.FlashUploadDeniedExtensions = "" ;          // empty for no one

    FCKConfig.SmileyPath = document.location.protocol + '//' + document.location.host +'<?php echo $GLOBALS['pageroot'].'/images/smiley/' ?>'
    FCKConfig.SmileyImages  = [<?php echo $smileys ?>] ;
    FCKConfig.SmileyColumns = 8 ;
    FCKConfig.SmileyWindowWidth   = 320 ;
    FCKConfig.SmileyWindowHeight  = 240 ;

    if( window.console ) window.console.log( 'Config is loaded!' ) ;  // @Packager.Compactor.RemoveLine
    <?php
    exit;
} elseif ($_GET['action'] == 'js3') {
    @ob_end_clean();
    header('Content-type: text/plain');
    $req = Sql_query(sprintf('select name from %s where type in ("textline","select") order by listorder',
    $GLOBALS['tables']['attribute']));
    $attnames = ';preferences url;unsubscribe url';
    $attcodes = ';[PREFERENCES];[UNSUBSCRIBE]';
    while ($row = Sql_Fetch_Row($req)) {
        $attnames .= ';'.strtolower(substr($row[0], 0, 15));
        $attcodes .= ';['.strtoupper($row[0]).']';
    }

    $imgdir = $_SERVER['DOCUMENT_ROOT'].$GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/';
    $enable_image_upload = is_dir($imgdir) && is_writable($imgdir) ? 'true' : 'false';

    $imgdir = $_SERVER['DOCUMENT_ROOT'].'/'.UPLOADIMAGES_DIR.'/';
    $enable_image_upload = is_dir($imgdir) && is_writable($imgdir) ? 'true' : 'false';

    $smileypath = $_SERVER['DOCUMENT_ROOT'].$GLOBALS['pageroot'].'/images/smiley';
    $smileyextensions = array('gif');
    $smileys = '';
    if (is_dir($smileypath)) {
        if ($dir = opendir($smileypath)) {
            while (false !== ($file = readdir($dir))) {
                if (strpos($file, '.') !== false) {
                    list($fname, $ext) = explode('.', $file);
                    if (in_array($ext, $smileyextensions)) {
                        $smileys .= '"'.$file.'",';
                    }
                }
            }
        }
        $smileys = substr($smileys, 0, -1);
    } ?>
/*
* FCKeditor - The text editor for internet
* Copyright (C) 2003-2006 Frederico Caldeira Knabben
*
* Licensed under the terms of the GNU Lesser General Public License:
*    http://www.opensource.org/licenses/lgpl-license.php
*
* For further information visit:
*    http://www.fckeditor.net/
*
* "Support Open Source software. What about a donation today?"
*
* File Name: fckconfig.js
*  Editor configuration settings.
*
*  Follow this link for more information:
*  http://wiki.fckeditor.net/Developer%27s_Guide/Configuration/Configurations_Settings
*
* File Authors:
*    Frederico Caldeira Knabben (fredck@fckeditor.net)
*/

FCKConfig.CustomConfigurationsPath = '' ;

FCKConfig.EditorAreaCSS = FCKConfig.BasePath + 'css/fck_editorarea.css' ;
FCKConfig.ToolbarComboPreviewCSS = '' ;

FCKConfig.DocType = '' ;

FCKConfig.BaseHref = '' ;

FCKConfig.FullPage = false ;

FCKConfig.Debug = false ;
FCKConfig.AllowQueryStringDebug = true ;

FCKConfig.SkinPath = FCKConfig.BasePath + 'skins/default/' ;
FCKConfig.PreloadImages = [ FCKConfig.SkinPath + 'images/toolbar.start.gif', FCKConfig.SkinPath + 'images/toolbar.buttonarrow.gif' ] ;

FCKConfig.PluginsPath = FCKConfig.BasePath + 'plugins/' ;

// FCKConfig.Plugins.Add( 'autogrow' ) ;
FCKConfig.AutoGrowMax = 400 ;

// FCKConfig.ProtectedSource.Add( /<%[\s\S]*?%>/g ) ; // ASP style server side code <%...%>
// FCKConfig.ProtectedSource.Add( /<\?[\s\S]*?\?>/g ) ; // PHP style server side code
// FCKConfig.ProtectedSource.Add( /(
<asp:[^\>]+>[\s|\S]*?<\/asp:[^\>]+>)|(
<asp:[^\>]+\/>)/gi ) ;  // ASP.Net style tags
<asp:control>

    FCKConfig.AutoDetectLanguage = true ;
    FCKConfig.DefaultLanguage = 'en' ;
    FCKConfig.ContentLangDirection = 'ltr' ;

    FCKConfig.ProcessHTMLEntities = true ;
    FCKConfig.IncludeLatinEntities = true ;
    FCKConfig.IncludeGreekEntities = true ;

    FCKConfig.ProcessNumericEntities = false ;

    FCKConfig.AdditionalNumericEntities = '' ; // Single Quote: "'"

    FCKConfig.FillEmptyBlocks = true ;

    FCKConfig.FormatSource = true ;
    FCKConfig.FormatOutput = true ;
    FCKConfig.FormatIndentator = ' ' ;

    FCKConfig.ForceStrongEm = true ;
    FCKConfig.GeckoUseSPAN = false ;
    FCKConfig.StartupFocus = false ;
    FCKConfig.ForcePasteAsPlainText = false ;
    FCKConfig.AutoDetectPasteFromWord = true ; // IE only.
    FCKConfig.ForceSimpleAmpersand = false ;
    FCKConfig.TabSpaces = 0 ;
    FCKConfig.ShowBorders = true ;
    FCKConfig.SourcePopup = false ;
    FCKConfig.UseBROnCarriageReturn = false ; // IE only.
    FCKConfig.ToolbarStartExpanded = true ;
    FCKConfig.ToolbarCanCollapse = true ;
    FCKConfig.IgnoreEmptyParagraphValue = true ;
    FCKConfig.PreserveSessionOnFileBrowser = false ;
    FCKConfig.FloatingPanelsZIndex = 10000 ;

    FCKConfig.TemplateReplaceAll = true ;
    FCKConfig.TemplateReplaceCheckbox = true ;

    FCKConfig.ToolbarLocation = 'In' ;

    FCKConfig.ToolbarSets["Default"] = [
    ['Source','DocProps','-','NewPage','Preview'],
    ['Cut','Copy','Paste','PasteText','PasteWord','-','Print','SpellCheck'],
    ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat','Link','Unlink','Anchor'],
    ['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript'],
    ['OrderedList','UnorderedList','-','Outdent','Indent'],
    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
    ['Image','Table','Rule','Smiley','SpecialChar','UniversalKey','TextColor','BGColor'],
    '/',
    ['Style','FontFormat','FontName','FontSize'],
    ['About']
    ] ;

    FCKConfig.ToolbarSets["Basic"] = [
    ['Bold','Italic','-','OrderedList','UnorderedList','-','Link','Unlink','-','About']
    ] ;

    FCKConfig.ContextMenu =
    ['Generic','Link','Anchor','Image','Flash','Select','Textarea','Checkbox','Radio','TextField','HiddenField','ImageButton','Button','BulletedList','NumberedList','Table','Form']
    ;

    FCKConfig.FontColors =
    '000000,993300,333300,003300,003366,000080,333399,333333,800000,FF6600,808000,808080,008080,0000FF,666699,808080,FF0000,FF9900,99CC00,339966,33CCCC,3366FF,800080,999999,FF00FF,FFCC00,FFFF00,00FF00,00FFFF,00CCFF,993366,C0C0C0,FF99CC,FFCC99,FFFF99,CCFFCC,CCFFFF,99CCFF,CC99FF,FFFFFF'
    ;

    FCKConfig.FontNames = 'Arial;Comic Sans MS;Courier New;Tahoma;Times New Roman;Verdana' ;
    FCKConfig.FontSizes = '1/xx-small;2/x-small;3/small;4/medium;5/large;6/x-large;7/xx-large' ;
    FCKConfig.FontFormats = 'p;div;pre;address;h1;h2;h3;h4;h5;h6' ;

    FCKConfig.StylesXmlPath = FCKConfig.EditorPath + 'fckstyles.xml' ;
    FCKConfig.TemplatesXmlPath = FCKConfig.EditorPath + 'fcktemplates.xml' ;

    FCKConfig.SpellChecker = 'ieSpell' ; // 'ieSpell' | 'SpellerPages'
    FCKConfig.IeSpellDownloadUrl = 'http://wcarchive.cdrom.com/pub/simtelnet/handheld/webbrow1/ieSpellSetup240428.exe' ;

    FCKConfig.MaxUndoLevels = 15 ;

    FCKConfig.DisableObjectResizing = false ;
    FCKConfig.DisableFFTableHandles = true ;

    FCKConfig.LinkDlgHideTarget = false ;
    FCKConfig.LinkDlgHideAdvanced = false ;

    FCKConfig.ImageDlgHideLink = false ;
    FCKConfig.ImageDlgHideAdvanced = false ;

    FCKConfig.FlashDlgHideAdvanced = false ;

    // The following value defines which File Browser connector and Quick Upload
    // "uploader" to use. It is valid for the default implementaion and it is here
    // just to make this configuration file cleaner.
    // It is not possible to change this value using an external file or even
    // inline when creating the editor instance. In that cases you must set the
    // values of LinkBrowserURL, ImageBrowserURL and so on.
    // Custom implementations should just ignore it.
    var _FileBrowserLanguage = 'asp' ; // asp | aspx | cfm | lasso | perl | php | py
    var _QuickUploadLanguage = 'asp' ; // asp | aspx | cfm | lasso | php

    // Don't care about the following line. It just calculates the correct connector
    // extension to use for the default File Browser (Perl uses "cgi").
    var _FileBrowserExtension = _FileBrowserLanguage == 'perl' ? 'cgi' : _FileBrowserLanguage ;

    FCKConfig.LinkBrowser = false ;
    FCKConfig.LinkBrowserURL = FCKConfig.BasePath + 'filemanager/browser/default/browser.html?Connector=connectors/' +
    _FileBrowserLanguage + '/connector.' + _FileBrowserExtension ;
    FCKConfig.LinkBrowserWindowWidth = FCKConfig.ScreenWidth * 0.7 ; // 70%
    FCKConfig.LinkBrowserWindowHeight = FCKConfig.ScreenHeight * 0.7 ; // 70%

    FCKConfig.ImageBrowser = <?php echo $enable_image_upload ?> ;
    FCKConfig.ImageBrowserURL = FCKConfig.BasePath +
    'filemanager/browser/default/browser.html?Type=Image&Connector=connectors/phplist/connector.php'
    FCKConfig.ImageBrowserWindowWidth = FCKConfig.ScreenWidth * 0.7 ; // 70% ;
    FCKConfig.ImageBrowserWindowHeight = FCKConfig.ScreenHeight * 0.7 ; // 70% ;

    FCKConfig.FlashBrowser = false ;
    FCKConfig.FlashBrowserURL = FCKConfig.BasePath +
    'filemanager/browser/default/browser.html?Type=Flash&Connector=connectors/' + _FileBrowserLanguage + '/connector.' +
    _FileBrowserExtension ;
    FCKConfig.FlashBrowserWindowWidth = FCKConfig.ScreenWidth * 0.7 ; //70% ;
    FCKConfig.FlashBrowserWindowHeight = FCKConfig.ScreenHeight * 0.7 ; //70% ;

    FCKConfig.LinkUpload = false ;
    FCKConfig.LinkUploadURL = FCKConfig.BasePath + 'filemanager/upload/' + _QuickUploadLanguage + '/upload.' +
    _QuickUploadLanguage ;
    FCKConfig.LinkUploadAllowedExtensions = "" ; // empty for all
    FCKConfig.LinkUploadDeniedExtensions = ".(php|php3|php5|phtml|asp|aspx|ascx|jsp|cfm|cfc|pl|bat|exe|dll|reg|cgi)$" ;
    // empty for no one

    FCKConfig.ImageUpload = <?php echo $enable_image_upload ?> ;
    FCKConfig.ImageUploadURL = FCKConfig.BasePath + 'filemanager/upload/phplist/upload.php?Type=Image' ;
    FCKConfig.ImagePath = document.location.protocol + '//' + document.location.host
    +'<?php echo $GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/' ?>'

    FCKConfig.ImageUploadAllowedExtensions = ".(jpg|gif|jpeg|png)$" ; // empty for all
    FCKConfig.ImageUploadDeniedExtensions = "" ; // empty for no one

    FCKConfig.FlashUpload = false ;
    FCKConfig.FlashUploadURL = FCKConfig.BasePath + 'filemanager/upload/' + _QuickUploadLanguage + '/upload.' +
    _QuickUploadLanguage + '?Type=Flash' ;
    FCKConfig.FlashUploadAllowedExtensions = ".(swf|fla)$" ; // empty for all
    FCKConfig.FlashUploadDeniedExtensions = "" ; // empty for no one

    FCKConfig.SmileyPath = document.location.protocol + '//' + document.location.host
    +'<?php echo $GLOBALS['pageroot'].'/images/smiley/' ?>'
    FCKConfig.SmileyImages = [<?php echo $smileys ?>] ;
    FCKConfig.SmileyColumns = 8 ;
    FCKConfig.SmileyWindowWidth = 320 ;
    FCKConfig.SmileyWindowHeight = 240 ;

    <?php
    exit;
} elseif ($_GET['action'] == 'js4') {
    ob_end_clean();
    header('Content-type: text/plain');
    $req = Sql_query(sprintf('select name from %s where type in ("textline","select") order by listorder',
        $GLOBALS['tables']['attribute']));
    $attnames = ';preferences url;unsubscribe url';
    $attcodes = ';[PREFERENCES];[UNSUBSCRIBE]';
    while ($row = Sql_Fetch_Row($req)) {
        $attnames .= ';'.strtolower(substr($row[0], 0, 15));
        $attcodes .= ';['.strtoupper($row[0]).']';
    }

    $imgdir = $_SERVER['DOCUMENT_ROOT'].$GLOBALS['pageroot'].'/'.FCKIMAGES_DIR.'/';
    $enable_image_upload = is_dir($imgdir) && is_writable($imgdir) ? 'true' : 'false';

    if (defined('UPLOADIMAGES_DIR')) {
        $imgdir = $_SERVER['DOCUMENT_ROOT'].'/'.UPLOADIMAGES_DIR.'/';
        $enable_image_upload = is_dir($imgdir) && is_writable($imgdir) ? 'true' : 'false';
    }

    $smileypath = $_SERVER['DOCUMENT_ROOT'].$GLOBALS['pageroot'].'/images/smiley';
    $smileyextensions = array('gif');
    $smileys = '';
    if (is_dir($smileypath)) {
        if ($dir = opendir($smileypath)) {
            while (false !== ($file = readdir($dir))) {
                if (strpos($file, '.') !== false) {
                    list($fname, $ext) = explode('.', $file);
                    if (in_array($ext, $smileyextensions)) {
                        $smileys .= '"'.$file.'",';
                    }
                }
            }
        }
        $smileys = substr($smileys, 0, -1);
    } ?>
    /*
    * FCKeditor - The text editor for Internet - http://www.fckeditor.net
    * Copyright (C) 2003-2008 Frederico Caldeira Knabben
    *
    * == BEGIN LICENSE ==
    *
    * Licensed under the terms of any of the following licenses at your
    * choice:
    *
    * - GNU General Public License Version 2 or later (the "GPL")
    * http://www.gnu.org/licenses/gpl.html
    *
    * - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
    * http://www.gnu.org/licenses/lgpl.html
    *
    * - Mozilla Public License Version 1.1 or later (the "MPL")
    * http://www.mozilla.org/MPL/MPL-1.1.html
    *
    * == END LICENSE ==
    *
    * Editor configuration settings.
    *
    * Follow this link for more information:
    * http://docs.fckeditor.net/FCKeditor_2.x/Developers_Guide/Configuration/Configuration_Options
    */

    FCKConfig.CustomConfigurationsPath = '' ;

    FCKConfig.EditorAreaCSS = FCKConfig.BasePath + 'css/fck_editorarea.css' ;
    FCKConfig.EditorAreaStyles = '' ;
    FCKConfig.ToolbarComboPreviewCSS = '' ;

    FCKConfig.DocType = '' ;

    FCKConfig.BaseHref = '' ;

    FCKConfig.FullPage = false ;

    // The following option determines whether the "Show Blocks" feature is enabled or not at startup.
    FCKConfig.StartupShowBlocks = false ;

    FCKConfig.Debug = false ;
    FCKConfig.AllowQueryStringDebug = true ;

    FCKConfig.SkinPath = FCKConfig.BasePath + 'skins/default/' ;
    FCKConfig.SkinEditorCSS = '' ; // FCKConfig.SkinPath + "|
    <minified css>" ;
        FCKConfig.SkinDialogCSS = '' ; // FCKConfig.SkinPath + "|
        <minified css>" ;

            FCKConfig.PreloadImages = [ FCKConfig.SkinPath + 'images/toolbar.start.gif', FCKConfig.SkinPath +
            'images/toolbar.buttonarrow.gif' ] ;

            FCKConfig.PluginsPath = FCKConfig.BasePath + 'plugins/' ;

            // FCKConfig.Plugins.Add( 'autogrow' ) ;
            // FCKConfig.Plugins.Add( 'dragresizetable' );
            FCKConfig.AutoGrowMax = 400 ;

            // FCKConfig.ProtectedSource.Add( /<%[\s\S]*?%>/g ) ; // ASP style server side code <%...%>
            // FCKConfig.ProtectedSource.Add( /<\?[\s\S]*?\?>/g ) ; // PHP style server side code
            // FCKConfig.ProtectedSource.Add( /(
            <asp:
            [^\>]+>[\s|\S]*?<\/asp:[^\>]+>)|(
            <asp:
            [^\>]+\/>)/gi ) ; // ASP.Net style tags
            <asp:control>

                FCKConfig.AutoDetectLanguage = true ;
                FCKConfig.DefaultLanguage = 'en' ;
                FCKConfig.ContentLangDirection = 'ltr' ;

                FCKConfig.ProcessHTMLEntities = true ;
                FCKConfig.IncludeLatinEntities = true ;
                FCKConfig.IncludeGreekEntities = true ;

                FCKConfig.ProcessNumericEntities = false ;

                FCKConfig.AdditionalNumericEntities = '' ; // Single Quote: "'"

                FCKConfig.FillEmptyBlocks = true ;

                FCKConfig.FormatSource = true ;
                FCKConfig.FormatOutput = true ;
                FCKConfig.FormatIndentator = ' ' ;

                FCKConfig.EMailProtection = 'none' ; // none | encode | function
                FCKConfig.EMailProtectionFunction = 'mt(NAME,DOMAIN,SUBJECT,BODY)' ;

                FCKConfig.StartupFocus = false ;
                FCKConfig.ForcePasteAsPlainText = false ;
                FCKConfig.AutoDetectPasteFromWord = true ; // IE only.
                FCKConfig.ShowDropDialog = true ;
                FCKConfig.ForceSimpleAmpersand = false ;
                FCKConfig.TabSpaces = 0 ;
                FCKConfig.ShowBorders = true ;
                FCKConfig.SourcePopup = false ;
                FCKConfig.ToolbarStartExpanded = true ;
                FCKConfig.ToolbarCanCollapse = true ;
                FCKConfig.IgnoreEmptyParagraphValue = true ;
                FCKConfig.FloatingPanelsZIndex = 10000 ;
                FCKConfig.HtmlEncodeOutput = false ;

                FCKConfig.TemplateReplaceAll = true ;
                FCKConfig.TemplateReplaceCheckbox = true ;

                FCKConfig.ToolbarLocation = 'In' ;

                FCKConfig.ToolbarSets["Default"] = [
                ['Source','PasteWord','Link','Unlink'],
                ['Bold','Italic','Underline'],
                ['OrderedList','UnorderedList'],
                ['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
                ['Image','FontFormat','About']

                <?php
                $editor_secondrow = getConfig('editortoolbar_row2');
    if (!empty($editor_secondrow)) {
        echo ",'/',\n";
        $tools = cleanCommaList($editor_secondrow);
        $tools = str_replace("'", "\'", $tools);
        echo "['".implode("','", explode(',', $tools))."']";
    } ?>
                ] ;

                FCKConfig.ToolbarSets["Basic"] = [
                ['Bold','Italic','-','OrderedList','UnorderedList','-','Link','Unlink','-','About']
                ] ;

                FCKConfig.EnterMode = 'p' ; // p | div | br
                FCKConfig.ShiftEnterMode = 'br' ; // p | div | br

                FCKConfig.Keystrokes = [
                [ CTRL + 65 /*A*/, true ],
                [ CTRL + 67 /*C*/, true ],
                [ CTRL + 70 /*F*/, true ],
                [ CTRL + 83 /*S*/, true ],
                [ CTRL + 84 /*T*/, true ],
                [ CTRL + 88 /*X*/, true ],
                [ CTRL + 86 /*V*/, 'Paste' ],
                [ CTRL + 45 /*INS*/, true ],
                [ SHIFT + 45 /*INS*/, 'Paste' ],
                [ CTRL + 88 /*X*/, 'Cut' ],
                [ SHIFT + 46 /*DEL*/, 'Cut' ],
                [ CTRL + 90 /*Z*/, 'Undo' ],
                [ CTRL + 89 /*Y*/, 'Redo' ],
                [ CTRL + SHIFT + 90 /*Z*/, 'Redo' ],
                [ CTRL + 76 /*L*/, 'Link' ],
                [ CTRL + 66 /*B*/, 'Bold' ],
                [ CTRL + 73 /*I*/, 'Italic' ],
                [ CTRL + 85 /*U*/, 'Underline' ],
                [ CTRL + SHIFT + 83 /*S*/, 'Save' ],
                [ CTRL + ALT + 13 /*ENTER*/, 'FitWindow' ],
                [ SHIFT + 32 /*SPACE*/, 'Nbsp' ]
                ] ;

                FCKConfig.ContextMenu =
                ['Generic','Link','Anchor','Image','Flash','Select','Textarea','Checkbox','Radio','TextField','HiddenField','ImageButton','Button','BulletedList','NumberedList','Table','Form','DivContainer']
                ;
                FCKConfig.BrowserContextMenuOnCtrl = false ;
                FCKConfig.BrowserContextMenu = false ;

                FCKConfig.EnableMoreFontColors = true ;
                FCKConfig.FontColors =
                '000000,993300,333300,003300,003366,000080,333399,333333,800000,FF6600,808000,808080,008080,0000FF,666699,808080,FF0000,FF9900,99CC00,339966,33CCCC,3366FF,800080,999999,FF00FF,FFCC00,FFFF00,00FF00,00FFFF,00CCFF,993366,C0C0C0,FF99CC,FFCC99,FFFF99,CCFFCC,CCFFFF,99CCFF,CC99FF,FFFFFF'
                ;

                FCKConfig.FontFormats = 'p;h1;h2;h3;h4;h5;h6;pre;address;div' ;
                FCKConfig.FontNames = 'Arial;Comic Sans MS;Courier New;Tahoma;Times New Roman;Verdana' ;
                FCKConfig.FontSizes = 'smaller;larger;xx-small;x-small;small;medium;large;x-large;xx-large' ;

                FCKConfig.StylesXmlPath = FCKConfig.EditorPath + 'fckstyles.xml' ;
                FCKConfig.TemplatesXmlPath = FCKConfig.EditorPath + 'fcktemplates.xml' ;

                FCKConfig.SpellChecker = 'ieSpell' ; // 'ieSpell' | 'SpellerPages'
                FCKConfig.IeSpellDownloadUrl = 'http://www.iespell.com/download.php' ;
                FCKConfig.SpellerPagesServerScript = 'server-scripts/spellchecker.php' ; // Available extension: .php
                .cfm .pl
                FCKConfig.FirefoxSpellChecker = false ;

                FCKConfig.MaxUndoLevels = 15 ;

                FCKConfig.DisableObjectResizing = false ;
                FCKConfig.DisableFFTableHandles = true ;

                FCKConfig.LinkDlgHideTarget = false ;
                FCKConfig.LinkDlgHideAdvanced = false ;

                FCKConfig.ImageDlgHideLink = false ;
                FCKConfig.ImageDlgHideAdvanced = false ;

                FCKConfig.FlashDlgHideAdvanced = false ;

                FCKConfig.ProtectedTags = '' ;

                // This will be applied to the body element of the editor
                FCKConfig.BodyId = '' ;
                FCKConfig.BodyClass = '' ;

                FCKConfig.DefaultStyleLabel = '' ;
                FCKConfig.DefaultFontFormatLabel = '' ;
                FCKConfig.DefaultFontLabel = '' ;
                FCKConfig.DefaultFontSizeLabel = '' ;

                FCKConfig.DefaultLinkTarget = '' ;

                // The option switches between trying to keep the html structure or do the changes so the content looks
                like it was in Word
                FCKConfig.CleanWordKeepsStructure = false ;

                // Only inline elements are valid.
                FCKConfig.RemoveFormatTags =
                'b,big,code,del,dfn,em,font,i,ins,kbd,q,samp,small,span,strike,strong,sub,sup,tt,u,var' ;

                // Attributes that will be removed
                FCKConfig.RemoveAttributes = 'class,style,lang,width,height,align,hspace,valign' ;

                FCKConfig.CustomStyles =
                {
                'Red Title' : { Element : 'h3', Styles : { 'color' : 'Red' } }
                };

                // Do not add, rename or remove styles here. Only apply definition changes.
                FCKConfig.CoreStyles =
                {
                // Basic Inline Styles.
                'Bold' : { Element : 'strong', Overrides : 'b' },
                'Italic' : { Element : 'em', Overrides : 'i' },
                'Underline' : { Element : 'u' },
                'StrikeThrough' : { Element : 'strike' },
                'Subscript' : { Element : 'sub' },
                'Superscript' : { Element : 'sup' },

                // Basic Block Styles (Font Format Combo).
                'p' : { Element : 'p' },
                'div' : { Element : 'div' },
                'pre' : { Element : 'pre' },
                'address' : { Element : 'address' },
                'h1' : { Element : 'h1' },
                'h2' : { Element : 'h2' },
                'h3' : { Element : 'h3' },
                'h4' : { Element : 'h4' },
                'h5' : { Element : 'h5' },
                'h6' : { Element : 'h6' },

                // Other formatting features.
                'FontFace' :
                {
                Element : 'span',
                Styles : { 'font-family' : '#("Font")' },
                Overrides : [ { Element : 'font', Attributes : { 'face' : null } } ]
                },

                'Size' :
                {
                Element : 'span',
                Styles : { 'font-size' : '#("Size","fontSize")' },
                Overrides : [ { Element : 'font', Attributes : { 'size' : null } } ]
                },

                'Color' :
                {
                Element : 'span',
                Styles : { 'color' : '#("Color","color")' },
                Overrides : [ { Element : 'font', Attributes : { 'color' : null } } ]
                },

                'BackColor' : { Element : 'span', Styles : { 'background-color' : '#("Color","color")' } },

                'SelectionHighlight' : { Element : 'span', Styles : { 'background-color' : 'navy', 'color' : 'white' } }
                };

                // The distance of an indentation step.
                FCKConfig.IndentLength = 40 ;
                FCKConfig.IndentUnit = 'px' ;

                // Alternatively, FCKeditor allows the use of CSS classes for block indentation.
                // This overrides the IndentLength/IndentUnit settings.
                FCKConfig.IndentClasses = [] ;

                // [ Left, Center, Right, Justified ]
                FCKConfig.JustifyClasses = [] ;

                // The following value defines which File Browser connector and Quick Upload
                // "uploader" to use. It is valid for the default implementaion and it is here
                // just to make this configuration file cleaner.
                // It is not possible to change this value using an external file or even
                // inline when creating the editor instance. In that cases you must set the
                // values of LinkBrowserURL, ImageBrowserURL and so on.
                // Custom implementations should just ignore it.
                var _FileBrowserLanguage = 'php' ; // asp | aspx | cfm | lasso | perl | php | py
                var _QuickUploadLanguage = 'php' ; // asp | aspx | cfm | lasso | perl | php | py

                // Don't care about the following two lines. It just calculates the correct connector
                // extension to use for the default File Browser (Perl uses "cgi").
                var _FileBrowserExtension = _FileBrowserLanguage == 'perl' ? 'cgi' : _FileBrowserLanguage ;
                var _QuickUploadExtension = _QuickUploadLanguage == 'perl' ? 'cgi' : _QuickUploadLanguage ;

                FCKConfig.LinkBrowser = false ;
                FCKConfig.LinkBrowserURL = FCKConfig.BasePath + 'filemanager/browser/default/browser.html?Connector=' +
                encodeURIComponent( FCKConfig.BasePath + 'filemanager/connectors/' + _FileBrowserLanguage +
                '/connector.' + _FileBrowserExtension ) ;
                FCKConfig.LinkBrowserWindowWidth = FCKConfig.ScreenWidth * 0.7 ; // 70%
                FCKConfig.LinkBrowserWindowHeight = FCKConfig.ScreenHeight * 0.7 ; // 70%

                FCKConfig.ImageBrowser = <?php echo $enable_image_upload ?> ;

                //FCKConfig.ImageBrowserURL = FCKConfig.BasePath +
                'filemanager/browser/default/browser.html?Type=Image&Connector=connectors/' + _FileBrowserLanguage +
                '/connector.' + _FileBrowserExtension ;

                FCKConfig.ImageBrowserURL = FCKConfig.BasePath +
                'filemanager/browser/default/browser.html?Type=Image&Connector=' + encodeURIComponent(
                FCKConfig.BasePath + 'filemanager/connectors/phplist/connector.' + _FileBrowserExtension ) ;

                FCKConfig.ImageBrowserWindowWidth = FCKConfig.ScreenWidth * 0.7 ; // 70% ;
                FCKConfig.ImageBrowserWindowHeight = FCKConfig.ScreenHeight * 0.7 ; // 70% ;

                FCKConfig.FlashBrowser = false ;
                FCKConfig.FlashBrowserURL = FCKConfig.BasePath +
                'filemanager/browser/default/browser.html?Type=Flash&Connector=' + encodeURIComponent(
                FCKConfig.BasePath + 'filemanager/connectors/' + _FileBrowserLanguage + '/connector.' +
                _FileBrowserExtension ) ;
                FCKConfig.FlashBrowserWindowWidth = FCKConfig.ScreenWidth * 0.7 ; //70% ;
                FCKConfig.FlashBrowserWindowHeight = FCKConfig.ScreenHeight * 0.7 ; //70% ;

                FCKConfig.LinkUpload = false ;
                FCKConfig.LinkUploadURL = FCKConfig.BasePath + 'filemanager/connectors/' + _QuickUploadLanguage +
                '/upload.' + _QuickUploadExtension ;
                FCKConfig.LinkUploadAllowedExtensions =
                ".(7z|aiff|asf|avi|bmp|csv|doc|fla|flv|gif|gz|gzip|jpeg|jpg|mid|mov|mp3|mp4|mpc|mpeg|mpg|ods|odt|pdf|png|ppt|pxd|qt|ram|rar|rm|rmi|rmvb|rtf|sdc|sitd|swf|sxc|sxw|tar|tgz|tif|tiff|txt|vsd|wav|wma|wmv|xls|xml|zip)$"
                ; // empty for all
                FCKConfig.LinkUploadDeniedExtensions = "" ; // empty for no one

                FCKConfig.ImageUpload = <?php echo $enable_image_upload ?> ;
                //FCKConfig.ImageUploadURL = FCKConfig.BasePath + 'filemanager/connectors/' + _QuickUploadLanguage +
                '/upload.' + _QuickUploadExtension + '?Type=Image' ;
                FCKConfig.ImageUploadURL = FCKConfig.BasePath + 'filemanager/connectors/phplist/upload.php?Type=Image' ;
                FCKConfig.ImagePath = document.location.protocol + '//' + document.location.host
                +'<?php echo $GLOBALS['pageroot'].'/'.UPLOADIMAGES_DIR.'/' ?>'

                FCKConfig.ImageUploadAllowedExtensions = ".(jpg|gif|jpeg|png|bmp)$" ; // empty for all
                FCKConfig.ImageUploadDeniedExtensions = "" ; // empty for no one

                FCKConfig.FlashUpload = false ;
                FCKConfig.FlashUploadURL = FCKConfig.BasePath + 'filemanager/connectors/' + _QuickUploadLanguage +
                '/upload.' + _QuickUploadExtension + '?Type=Flash' ;
                FCKConfig.FlashUploadAllowedExtensions = ".(swf|flv)$" ; // empty for all
                FCKConfig.FlashUploadDeniedExtensions = "" ; // empty for no one

                FCKConfig.SmileyPath = FCKConfig.BasePath + 'images/smiley/msn/' ;
                FCKConfig.SmileyImages =
                ['regular_smile.gif','sad_smile.gif','wink_smile.gif','teeth_smile.gif','confused_smile.gif','tounge_smile.gif','embaressed_smile.gif','omg_smile.gif','whatchutalkingabout_smile.gif','angry_smile.gif','angel_smile.gif','shades_smile.gif','devil_smile.gif','cry_smile.gif','lightbulb.gif','thumbs_down.gif','thumbs_up.gif','heart.gif','broken_heart.gif','kiss.gif','envelope.gif']
                ;
                FCKConfig.SmileyColumns = 8 ;
                FCKConfig.SmileyWindowWidth = 320 ;
                FCKConfig.SmileyWindowHeight = 210 ;

                FCKConfig.BackgroundBlockerColor = '#ffffff' ;
                FCKConfig.BackgroundBlockerOpacity = 0.50 ;

                FCKConfig.MsWebBrowserControlCompat = false ;

                FCKConfig.PreventSubmitHandler = false ;
                <?php
                exit;
} elseif ($_GET['action']) {
    echo 'Sorry, not implemented';
}

                ?>
