<?php
require_once dirname(__FILE__).'/accesscheck.php';
/*

Languages, countries, and the charsets typically used for them
http://www.w3.org/International/O-charset-lang.html

*/
## this array is now automatically build from the file system using the
## language_info file in each subdirectory of /lan/
$LANGUAGES = array(
"nl"=> array("Dutch ","UTF-8"," UTF-8, windows-1252 "),
"de" => array("Deutsch ","UTF-8","UTF-8, windows-1252 "),
"en" => array("English ","UTF-8","UTF-8, windows-1252 "),
"es"=>array("espa&ntilde;ol","UTF-8","UTF-8, windows-1252"),
#"fa" => array('Persian','utf-8','utf-8'),
"fr"=>array("fran&ccedil;ais ","UTF-8","UTF-8, windows-1252 "),
"pl"=>array("Polish ","UTF-8","UTF-8"),
"pt-br"=>array("portugu&ecirc;s ","UTF-8","UTF-8, windows-1252"),
"zh-tw" => array("Traditional Chinese","utf-8","utf-8"),
'cn' => array('Simplified Chinese',"utf-8","utf-8"),
"vi" => array("Vietnamese","utf-8","utf-8"),
);

$lanDBstruct = array(
  'language' => array(
    'iso' => array('varchar(10)',''),
    'name' => array('varchar(255)',''),
    'charset' => array('varchar(100)',''),
  ),
  'translation' => array(
    'tag' => array('varchar(255) not null','Tag for translation'),
    'page' => array('varchar(100) not null','Page it is used'),
    'lan' => array('varchar(10) not null','Language ISO'),
    'translation' => array('text','Translated text'),
    'index_1' => array('tagidx (tag)',''),
    'index_2' => array('pageidx (page)',''),
    'index_3' => array('lanidx (lan)',''),
  ),
);

if (DB_TRANSLATION) {
  $GLOBALS['tables']['translation'] = $GLOBALS['table_prefix'].'translation';
  $GLOBALS['tables']['language'] = $GLOBALS['table_prefix'].'language';
  if (!Sql_Table_Exists('phplist_translation')) {
    Sql_Create_Table($GLOBALS['tables']['translation'],$lanDBstruct['translation']);
    Sql_Create_Table($GLOBALS['tables']['language'],$lanDBstruct['language']);
  }
}

## pick up languages from the lan directory
$landir = dirname(__FILE__).'/lan/';
$d = opendir($landir);
while ($lancode = readdir($d)) {
#  print "<br/>".$dir;
  if (!in_array($landir,array_keys($LANGUAGES)) && is_dir($landir.'/'.$lancode) && is_file($landir.'/'.$lancode.'/language_info')) {
    $lan_info = file_get_contents($landir.'/'.$lancode.'/language_info');
    $lines = explode("\n",$lan_info);
    $lan = array();
    foreach ($lines as $line) {
      // use utf8 matching
      if (preg_match('/(\w+)=([\p{L}\p{N}&; \-\(\)]+)/u',$line,$regs)) {
#      if (preg_match('/(\w+)=([\w&; \-\(\)]+)/',$line,$regs)) {
#      if (preg_match('/(\w+)=(.+)/',$line,$regs)) {
        $lan[$regs[1]] = $regs[2];
      }
    }
    if (!empty($lan['name']) && !empty($lan['charset'])) {
      $LANGUAGES[$lancode] = array($lan['name'],$lan['charset'],$lan['charset']);
    }
    
#    print '<br/>'.$landir.'/'.$lancode;
  }
}
function lanSort($a,$b) {
  return strcmp(strtolower($a[0]),strtolower($b[0]));
}
uasort($LANGUAGES,"lanSort");

#ksort($LANGUAGES);
if (DB_TRANSLATION) {
  foreach ($LANGUAGES as $lancode => $laninfo) {
    Sql_Query(sprintf('insert ignore into %s (iso,name,charset) values("%s","%s","%s")',
      $GLOBALS['tables']['language'],$lancode,$laninfo[0],$laninfo[1]));
  }
}
#var_dump($LANGUAGES);

if (!empty($GLOBALS["SessionTableName"])) {
  require_once dirname(__FILE__).'/sessionlib.php';
}
@session_start();

if (isset($_POST['setlanguage']) && !empty($_POST['setlanguage']) && is_array($LANGUAGES[$_POST['setlanguage']])) {
  $_SESSION['adminlanguage'] = array(
    "info" => $_POST['setlanguage'],
    "iso" => $_POST['setlanguage'],
    "charset" => $LANGUAGES[$_POST['setlanguage']][1],
  );
}

/*
if (!empty($_SESSION['show_translation_colours'])) {
  $GLOBALS['pageheader']['translationtools'] = '
    <script type="text/javascript" src="js/jquery.contextMenu.js"></script>
    <link rel="stylesheet" href="js/jquery.contextMenu.css" />
    <ul id="translationMenu" class="contextMenu">
    <li class="translate">
        <a href="#translate">Translate</a>
    </li>
    <li class="quit separator">
        <a href="#quit">Quit</a>
    </li>
</ul>
  <script type="text/javascript">
  $(document).ready(function(){
    $(".translate").contextMenu({
        menu: "translationMenu"
    },
    function(action, el, pos) {
      alert(
          "Action: " + action + "\n\n" +
          "Element ID: " + $(el).attr("id") + "\n\n" +
          "X: " + pos.x + "  Y: " + pos.y + " (relative to element)\n\n" +
          "X: " + pos.docX + "  Y: " + pos.docY+ " (relative to document)"
      );
    });
  });
  </script>

  ';
}
*/

if (!isset($_SESSION['adminlanguage']) || !is_array($_SESSION['adminlanguage'])) {
  if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $accept_lan = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
  } else {
    $accept_lan = array('en'); # @@@ maybe make configurable?
  }
  $detectlan = '';
  foreach ($accept_lan as $lan) {
    if (!$detectlan) {
      if (preg_match('/^([\w-]+)/',$lan,$regs)) {
        $code = $regs[1];
        if (isset($LANGUAGES[$code])) {
          $detectlan = $code;
        } elseif (strpos($code,'-') !== false) {
          list($language,$country) = explode('-',$code);
          if (isset($LANGUAGES[$language])) {
            $detectlan = $language;
          }
        }
      }
    }
  }
  if (!$detectlan) {
    $detectlan = 'en';
  }

  $_SESSION['adminlanguage'] = array(
    "info" => $detectlan,
    "iso" => $detectlan,
    "charset" => $LANGUAGES[$detectlan][1],
  );
}

## this interferes with the frontend if an admin is logged in. 
## better split the frontend and backend charSets at some point
#if (!isset($GLOBALS['strCharSet'])) {
  $GLOBALS['strCharSet'] = $_SESSION['adminlanguage']['charset'];
#
#var_dump($_SESSION['adminlanguage']);
#print '<h1>'. $GLOBALS['strCharSet'].'</h1>';

# internationalisation (I18N)

class phplist_I18N {
  var $defaultlanguage = 'en';
  var $language = 'en';
  var $basedir = '';

  function phplist_I18N() {
    $this->basedir = dirname(__FILE__).'/lan/';
    if (isset($_SESSION['adminlanguage']) && is_dir($this->basedir.$_SESSION['adminlanguage']['iso'])) {
      $this->language = $_SESSION['adminlanguage']['iso'];
    } else {
#      logEvent('Invalid language '.$_SESSION['adminlanguage']['iso']);
#      print "Not set or found: ".$_SESSION['adminlanguage']['iso'];
      unset($_SESSION['adminlanguage']);
      $this->language = 'en';
#      exit;
    }
  }

  function pageTitle($page) {
    $page_title = '';
    include dirname(__FILE__).'/lan/'.$this->language.'/pagetitles.php';
    if (!empty($page_title)) {
      $title = $page_title;
    } elseif (preg_match('/pi=([\w]+)/',$page,$regs)) {
      $title = $regs[1];
    } else {
      $title = $page;
    }
    return $title;
  }

  function formatText($text) {
    # we've decided to spell phplist with on L
    $text = str_ireplace('PHPlist','phpList',$text);

    if (isset($GLOBALS["developer_email"])) {
      if (!empty($_SESSION['show_translation_colours'])) {
        return '<span style="color:#A704FF">'.str_replace("\n","",$text).'</span>';
      }
#       return 'TE'.$text.'XT';
    }
#    return '<span class="translateabletext">'.str_replace("\n","",$text).'</span>';
    return str_replace("\n","",$text);
  }

  function missingText($text) {
    if (isset($GLOBALS["developer_email"])) {
      if (isset($_GET['page'])) {
        $page = $_GET["page"];
      } else {
        $page = 'main page';
      }
      $pl = $prefix = '';
      if (!empty($_GET['pi'])) {
        $pl = $_GET['pi'];
        $pl = preg_replace('/\W/','',$pl);
        $prefix = $pl.'_';
      }

      $msg = '

      Undefined text reference in page '.$page.'

      '.$text;

      #sendMail($GLOBALS["developer_email"],"phplist dev, missing text",$msg);
      $line = "'".str_replace("'","\'",$text)."' => '".str_replace("'","\'",$text)."',";
#      if (is_file($this->basedir.'/en/'.$page.'.php') && $_SESSION['adminlanguage']['iso'] == 'en') {
      if (empty($prefix) && $_SESSION['adminlanguage']['iso'] == 'en') {
        $this->appendText($this->basedir.'/en/'.$page.'.php',$line);
      } else {
        $this->appendText('/tmp/'.$prefix.$page.'.php',$line);
      }

      if (!empty($_SESSION['show_translation_colours'])) {
        return '<span style="color: #FF1717">'.$text.'</span>';#MISSING TEXT
      }
    }
    return $text;
  }

  function appendText($file,$text) {
    $filecontents = '';
    if (is_file($file)) {
      $filecontents = file_get_contents($file);
    } else {
      $filecontents = '<?php

$lan = array(

);

      ?>';
    }

#    print "<br/>Writing $text to $file";
    $filecontents = preg_replace("/\n/","@@NL@@",$filecontents);
    $filecontents = str_replace(');','  '.$text."\n);",$filecontents);
    $filecontents = str_replace("@@NL@@","\n",$filecontents);

    $dir = dirname($file);
    if (!is_writable($dir) || (is_file($file) && !is_writable($file))) {
      $newfile = basename($file);
      $file = '/tmp/'.$newfile;
    }

    file_put_contents($file,$filecontents);
  }

  function getPluginBasedir() {
    $pl = $_GET['pi'];
    $pl = preg_replace('/\W/','',$pl);
    $pluginroot = '';
    if (isset($GLOBALS['plugins'][$pl]) && is_object($GLOBALS['plugins'][$pl])) {
      $pluginroot = $GLOBALS['plugins'][$pl]->coderoot;
    }
    if (is_dir($pluginroot.'/lan/')) {
      return $pluginroot.'/lan/';
    } else {
      return $pluginroot.'/';
    }
  }
  
  function getTranslation($text,$page,$basedir) { 
    if (is_file($basedir.'/'.$this->language.'/'.$page.'.php')) {
      @include $basedir.'/'.$this->language.'/'.$page.'.php';
    } elseif (!isset($GLOBALS['developer_email'])) {
      @include $basedir.'/'.$this->defaultlanguage.'/'.$page.'.php';
    }
    if (isset($lan) && is_array($lan) && isset($lan[$text])) {
      return $this->formatText($lan[$text]);
    }
    if (isset($lan) && is_array($lan) && isset($lan[strtolower($text)])) {
      return $this->formatText($lan[strtolower($text)]);
    }
    if (isset($lan) && is_array($lan) && isset($lan[strtoupper($text)])) {
      return $this->formatText($lan[strtoupper($text)]);
    }
    if (is_file($basedir.'/'.$this->language.'.php')) {
      @include $basedir.'/'.$this->language.'.php';
    } elseif (!isset($GLOBALS['developer_email'])) {
      @include $basedir.'/'.$this->defaultlanguage.'.php';
    }
    if (is_file($basedir.'/'.$this->language.'/common.php')) {
      @include $basedir.'/'.$this->language.'/common.php';
    } elseif (!isset($GLOBALS['developer_email'])) {
      @include $basedir.'/'.$this->defaultlanguage.'/common.php';
    }
    if (isset($lan) && is_array($lan) && isset($lan[$text])) {
      return $this->formatText($lan[$text]);
    }
    if (isset($lan) && is_array($lan) && isset($lan[strtolower($text)])) {
      return $this->formatText($lan[strtolower($text)]);
    }
    if (isset($lan) && is_array($lan) && isset($lan[strtoupper($text)])) {
      return $this->formatText($lan[strtoupper($text)]);
    }

    if (is_file($basedir.'/'.$this->language.'/frontend.php')) {
      @include $basedir.'/'.$this->language.'/frontend.php';
    } elseif (!isset($GLOBALS['developer_email'])) {
      @include $basedir.'/'.$this->defaultlanguage.'/frontend.php';
    }
    if (isset($lan) && is_array($lan) && isset($lan[$text])) {
      return $this->formatText($lan[$text]);
    }
    if (isset($lan) && is_array($lan) && isset($lan[strtolower($text)])) {
      return $this->formatText($lan[strtolower($text)]);
    }
    if (isset($lan) && is_array($lan) && isset($lan[strtoupper($text)])) {
      return $this->formatText($lan[strtoupper($text)]);
    }
    return '';
  }
  
  
  function get($text) {
    if (trim($text) == "") return "";
    if (strip_tags($text) == "") return $text;
    $translation = '';
    
    $this->basedir = dirname(__FILE__).'/lan/';
    if (isset($_GET['origpage']) && !empty($_GET['ajaxed'])) { ## used in ajaxed requests
      $page = basename($_GET["origpage"]);
    } elseif (isset($_GET["page"])) {
      $page = basename($_GET["page"]);
    } else {
      $page = "home";
    }
      
    if (!empty($_GET['pi'])) {
      $plugin_languagedir = $this->getPluginBasedir();
      if (is_dir($plugin_languagedir)) {
        $translation = $this->getTranslation($text,$page,$plugin_languagedir);
      }
    }
    
    ## if a plugin did not return the translation, find it in core
    if (empty($translation)) {
      $translation = $this->getTranslation($text,$page,$this->basedir);
    }
  
    # spelling mistake, retry with old spelling
    if ($text == 'over threshold, user marked unconfirmed' && empty($translation)) {
      return $this->get('over treshold, user marked unconfirmed');
    }
    
    if (!empty($translation)) {
      return $translation;
    } else {
      return $this->missingText($text);
    }
  }
}

$I18N = new phplist_I18N();

/* add a shortcut that seems common in other apps */
function s($text) {
  print $GLOBALS['I18N']->get($text);
}

?>
