<?php

require_once dirname(__FILE__).'/accesscheck.php';

$_SESSION['show_translation_colours'] = 1;

echo '<p class="button">'.PageLink2('checki18n&amp;changedonly=yes', 'Show changes only').'</p>';
// translation check. See that every token has a text in a file and vv

function getFileI18Ntags($file)
{
    $res = array();
    if (!is_file($file)) {
        return $res;
    }
    $fd = fopen($file, 'r');
    $contents = fread($fd, filesize($file));
    fclose($fd);
    preg_match_all('/\$GLOBALS\[(["|\'])I18N\1\]->get\((["|\'])([^\2]+)\2\)/Uim', $contents, $globalsi18ntags);
    preg_match_all('/\$I18N->get\((["|\'])([^\1]+)\1\)/Uim', $contents, $i18ntags);
    for ($i = 0; $i < count($globalsi18ntags[0]); ++$i) {
        array_push($res, $globalsi18ntags[3][$i]);
    }
    for ($i = 0; $i < count($i18ntags[0]); ++$i) {
        array_push($res, $i18ntags[2][$i]);
    }

    return $res;
}

function checkI18NDir($rootdir)
{
    $dir = opendir($rootdir);
    while ($file = readdir($dir)) {
        $fileoutput = '';
        $some = 0;
        if (is_file($rootdir.'/'.$file)) {
            $fileoutput .= '<hr/><h3>'.$file.'</h3><br/>';
            $arr = getFileI18Ntags($rootdir.'/'.$file);
            $lan = array();
            //    include 'lan/en/'.$file;
            switch ($file) {
                case 'send_core.php':
                    $_GET['page'] = 'send';
                    break;
                case 'importcsv.php':
                    $_GET['page'] = 'import2';
                    break;
                default:
                    $_GET['page'] = basename($file, '.php');
            }
            /*    $page = $_GET['page'];
                include_once dirname(__FILE__)."/lan/".$_SESSION['adminlanguage']['iso']."/pagetitles.php";
                if (!strtolower($page_title) === 'phplist') {
                  print "No page title: $page<br/>";
                } else {
                  print "Page title: $page, $page_title<br/>";
                }
            */
            foreach ($arr as $tag) {
                $translation = $GLOBALS['I18N']->get(stripslashes($tag));
                if (!isset($_GET['changedonly']) || ($_GET['changedonly'] === 'yes' && preg_match('/ff1717/i',
                            $translation))
                ) {
                    $fileoutput .= "'".$tag.'\' =&gt; \''.$translation.'\',<br/>';
                    $some = 1;
                }
            }
            if ($some) {
                echo $fileoutput;
            }
            //      print "RES: $tag<br/>";
            //    }
            //      if (!in_array($tag,$lan)) {
            //        print "Missing: $tag<br/>";
            //      } else {
            //        print "Exists: $tag<br/>";
            //      }
            //    }
        }
    }
}

/*
print '
<script language="Javascript" type="text/javascript">

function selectAll() {
  document.form.content.focus();document.form.content.select();
}

</script>

<a href="javascript:selectAll()">Select All</a><br/>';

#print '<form name="form">';
print '<textarea name="content" rows="50" cols="60">';
*/
checkI18NDir(dirname(__FILE__));
//print '</textarea>';
echo '</form>';
