<?php

require_once dirname(__FILE__).'/accesscheck.php';
/*

Languages, countries, and the charsets typically used for them
http://www.w3.org/International/O-charset-lang.html

*/

//# pick up languages from the lan directory
$landir = dirname(__FILE__).'/locale/';
$d = opendir($landir);
while ($lancode = readdir($d)) {
    //  print "<br/>".$lancode;
    if (!in_array($landir,
            array_keys($LANGUAGES)) && is_dir($landir.'/'.$lancode) && is_file($landir.'/'.$lancode.'/language_info')
    ) {
        $lan = @parse_ini_file($landir.'/'.$lancode.'/language_info');
        if (!isset($lan['gettext'])) {
            $lan['gettext'] = $lancode;
        }
        if (!isset($lan['dir'])) {
            $lan['dir'] = 'ltr';
        }
        if (!empty($lan['name']) && !empty($lan['charset'])) {
            $LANGUAGES[$lancode] = array($lan['name'], $lan['charset'], $lan['charset'], $lan['gettext'], $lan['dir']);
        }

//    print '<br/>'.$landir.'/'.$lancode;
    }
}

//# pick up other languages from DB
if (Sql_table_exists('i18n')) {
    $req = Sql_Query(sprintf('select lan,translation from %s where
    original = "language-name" and lan not in ("%s")', $GLOBALS['tables']['i18n'],
        implode('","', array_keys($LANGUAGES))));
    while ($row = Sql_Fetch_Assoc($req)) {
        $LANGUAGES[$row['lan']] = array($row['translation'], 'UTF-8', 'UTF-8', $row['lan']);
    }
}

function lanSort($a, $b)
{
    return strcmp(strtolower($a[3]), strtolower($b[3]));
}

uasort($LANGUAGES, 'lanSort');
//print '<pre>';
//var_dump($LANGUAGES);exit;

if (!empty($GLOBALS['SessionTableName'])) {
    require_once dirname(__FILE__).'/sessionlib.php';
}
@session_start();

if (isset($_POST['setlanguage']) && !empty($_POST['setlanguage']) && is_array($LANGUAGES[$_POST['setlanguage']])) {
    //# just in case
    $setlanguage = preg_replace('/[^\w_-]+/', '', $_POST['setlanguage']);
    $_SESSION['adminlanguage'] = array(
        'info'    => $setlanguage,
        'iso'     => $setlanguage,
        'charset' => $LANGUAGES[$setlanguage][1],
        'dir'     => $LANGUAGES[$setlanguage][4],
    );
    SetCookie ( 'preferredLanguage', $setlanguage,time()+31536000);
} elseif (empty($_SESSION['adminlanguage']) && isset($_COOKIE['preferredLanguage'])) {
    $setlanguage = preg_replace('/[^\w_-]+/', '', $_COOKIE['preferredLanguage']);
    $_SESSION['adminlanguage'] = array(
        'info'    => $setlanguage,
        'iso'     => $setlanguage,
        'charset' => $LANGUAGES[$setlanguage][1],
        'dir'     => $LANGUAGES[$setlanguage][4],
    );
}
//  var_dump($_SESSION['adminlanguage'] );

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
        $accept_lan = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    } else {
        $accept_lan = array($GLOBALS['default_system_language']);
    }
    $detectlan = '';

    /* @@@TODO
     * we need a mapping from Accept-Language to gettext, see below
     *
     * eg nl-be becomes nl_BE
     *
     * currently "nl-be" will become "nl" and not "nl_BE";
     */

    foreach ($accept_lan as $lan) {
        if (!$detectlan) {
            if (preg_match('/^([\w-]+)/', $lan, $regs)) {
                $code = $regs[1];
                if (isset($LANGUAGES[$code])) {
                    $detectlan = $code;
                } elseif (strpos($code, '-') !== false) {
                    list($language, $country) = explode('-', $code);
                    if (isset($LANGUAGES[$language])) {
                        $detectlan = $language;
                    }
                }
            }
        }
    }
    if (!$detectlan) {
        $detectlan = $GLOBALS['default_system_language'];
    }

    $_SESSION['adminlanguage'] = array(
        'info'    => $detectlan,
        'iso'     => $detectlan,
        'charset' => $LANGUAGES[$detectlan][1],
        'dir'     => $LANGUAGES[$detectlan][4],
    );
}

//# this interferes with the frontend if an admin is logged in.
//# better split the frontend and backend charSets at some point
//if (!isset($GLOBALS['strCharSet'])) {
$GLOBALS['strCharSet'] = $_SESSION['adminlanguage']['charset'];

//var_dump($_SESSION['adminlanguage']);
//print '<h1>'. $GLOBALS['strCharSet'].'</h1>';

// internationalisation (I18N)

class phplist_I18N
{
    public $defaultlanguage = 'en';
    public $language = 'en';
    public $basedir = '';
    public $dir = 'ltr';
    private $hasGettext = false;
    private $hasDB = false;
    private $lan = array();

    public function __construct()
    {
        $this->basedir = dirname(__FILE__).'/locale/';
        $this->defaultlanguage = $GLOBALS['default_system_language'];
        $this->language = $GLOBALS['default_system_language'];

        if (isset($_SESSION['adminlanguage']) && isset($GLOBALS['LANGUAGES'][$_SESSION['adminlanguage']['iso']])) {
            $this->language = $_SESSION['adminlanguage']['iso'];
            $this->dir = $_SESSION['adminlanguage']['dir'];
        } else {
            unset($_SESSION['adminlanguage']);
            $this->language = $GLOBALS['default_system_language'];
        }
        if (function_exists('gettext')) {
            $this->hasGettext = true;
        }
        if (isset($_SESSION['hasI18Ntable'])) {
            $this->hasDB = $_SESSION['hasI18Ntable'];
        } elseif (Sql_Check_For_Table('i18n')) {
            $_SESSION['hasI18Ntable'] = true;
            $this->hasDB = true;
        } else {
            $_SESSION['hasI18Ntable'] = false;
        }
    }

    public function gettext($text)
    {
        bindtextdomain('phplist', './locale');
        textdomain('phplist');

        /* gettext is a bit messy, at least on my Ubuntu 10.10 machine
         *
         * if eg language is "nl" it won't find it. It'll need to be "nl_NL";
         * also the Ubuntu system needs to have the language installed, even if phpList has it
         * it won't find it, if it's not on the system
         *
         * So, to e.g. get "nl" gettext support in phpList (on ubuntu, but presumably other linuxes), you'd have to do
         * cd /usr/share/locales
         * ./install-language-pack nl_NL
         * dpkg-reconfigure locales
         *
         * but when you use "nl_NL", the language .mo can still be in "nl".
         * However, it needs "nl/LC_MESSAGES/phplist.mo s, put a symlink LC_MESSAGES to itself
         *
         * the "utf-8" strangely enough needs to be added but can be spelled all kinds
         * of ways, eg "UTF8", "utf-8"
         *
         *
         * AND then of course the lovely Accept-Language vs gettext
         * https://bugs.php.net/bug.php?id=25051
         *
         * Accept-Language is lowercase and with - and gettext is country uppercase and with underscore
         *
         * More ppl have come across that: http://grep.be/articles/php-accept
         *
        */

        //# so, to get the mapping from "nl" to "nl_NL", use a gettext map in the related directory
        if (is_file(dirname(__FILE__).'/locale/'.$this->language.'/gettext_code')) {
            $lan_map = file_get_contents(dirname(__FILE__).'/locale/'.$this->language.'/gettext_code');
            $lan_map = trim($lan_map);
        } else {
            //# try to do "fr_FR", or "de_DE", might work in most cases
            //# hmm, not for eg fa_IR or zh_CN so they'll need the above file
            // http://www.gnu.org/software/gettext/manual/gettext.html#Language-Codes
            $lan_map = $this->language.'_'.strtoupper($this->language);
        }

        putenv('LANGUAGE='.$lan_map.'.utf-8');
        setlocale(LC_ALL, $lan_map.'.utf-8');
        bind_textdomain_codeset('phplist', 'UTF-8');
        $gt = gettext($text);
        if ($gt && $gt != $text) {
            return $gt;
        }
    }

    public function getCachedTranslation($text)
    {
        if (!isset($_SESSION['translations']) || !is_array($_SESSION['translations'])) {
            return false;
        }
        if (isset($_SESSION['translations'][$text])) {
            $age = time() - $_SESSION['translations'][$text]['ts'];
            if ($age < 3600) { //# timeout after a while
                return $_SESSION['translations'][$text]['trans'];
            } else {
                unset($_SESSION['translations'][$text]);
            }
        }
    }

    public function setCachedTranslation($text, $translation)
    {
        if (!isset($_SESSION['translations']) || !is_array($_SESSION['translations'])) {
            $_SESSION['translations'] = array();
        }
        // mark it as translated even if not, to avoid fetching it every time
        if (empty($translation)) {
            $translation = $text;
        }
        $_SESSION['translations'][$text] = array(
            'trans' => $translation,
            'ts'    => time(),
        );
    }

    public function resetCache()
    {
        unset($_SESSION['translations']);
    }

    public function databaseTranslation($text)
    {
        if (!$this->hasDB) {
            return '';
        }
        if (empty($GLOBALS['database_connection'])) {
            return '';
        }
        if ($cache = $this->getCachedTranslation($text)) {
            return $cache;
        }

        $tr = Sql_Fetch_Row_Query(sprintf('select translation from '.$GLOBALS['tables']['i18n'].' where original = "%s" and lan = "%s"',
            sql_escape(trim($text)), $this->language), 1);
        if (empty($tr[0])) {
            $tr = Sql_Fetch_Row_Query(sprintf('select translation from '.$GLOBALS['tables']['i18n'].' where original = "%s" and lan = "%s"',
                sql_escape($text), $this->language), 1);
        }
        if (empty($tr[0])) {
            $tr = Sql_Fetch_Row_Query(sprintf('select translation from '.$GLOBALS['tables']['i18n'].' where original = "%s" and lan = "%s"',
                sql_escape(str_replace('"', '\"', $text)), $this->language), 1);
        }
        $translated = !empty($tr[0]) ? stripslashes($tr[0]) : '';
        $this->setCachedTranslation($text, $translated);

        return $translated;
    }

    public function pageTitle($page)
    {
        //# try gettext and otherwise continue
        if ($this->hasGettext) {
            $gettext = $this->gettext($page);
            if (!empty($gettext)) {
                return $gettext;
            }
        }
        $page_title = '';
        $dbTitle = $this->databaseTranslation('pagetitle:'.$page);
        if ($dbTitle) {
            //# quite a few translators keep the pagetitle: in the translation
            $dbTitle = str_ireplace('pagetitle:', '', $dbTitle);
            $page_title = $dbTitle;
        } elseif (is_file(dirname(__FILE__).'/locale/'.$this->language.'/pagetitles.php')) {
            include dirname(__FILE__).'/locale/'.$this->language.'/pagetitles.php';
        } elseif (is_file(dirname(__FILE__).'/lan/'.$this->language.'/pagetitles.php')) {
            include dirname(__FILE__).'/lan/'.$this->language.'/pagetitles.php';
        }
        if (preg_match('/pi=([\w]+)/', $page, $regs)) {
            //# @@TODO call plugin to ask for title
            if (isset($GLOBALS['plugins'][$regs[1]])) {
                $title = $GLOBALS['plugins'][$regs[1]]->pageTitle($page);
            } else {
                $title = $regs[1].' - '.$page;
            }
        } elseif (!empty($page_title)) {
            $title = $page_title;
        } else {
            $title = ucfirst($page);
        }

        return $title;
    }

    public function pageTitleHover($page)
    {
        $hoverText = '';
        $dbTitle = $this->databaseTranslation('pagetitlehover:'.$page);
        if ($dbTitle) {
            $dbTitle = str_ireplace('pagetitlehover:', '', $dbTitle);
            $hoverText = $dbTitle;
        } else {
            $hoverText = $this->pageTitle($page);
            //# is this returns itself, wipe it, so the linktext is used instead
            if ($hoverText == $page) {
                $hoverText = '';
            }
        }
        if (!empty($hoverText)) {
            return $hoverText;
        }

        return '';
    }

    public function formatText($text)
    {
        // we've decided to spell phplist with uc L
        $text = str_ireplace('phplist', 'phpList', $text);

        if (isset($GLOBALS['developer_email'])) {
            if (!empty($_SESSION['show_translation_colours'])) {
                return '<span style="color:#A704FF">'.$text.'</span>';
            }
        }
        return $text;
    }

    /**
     * obsolete.
     */
    public function missingText($text)
    {
        if (isset($GLOBALS['developer_email'])) {
            if (isset($_GET['page'])) {
                $page = $_GET['page'];
            } else {
                $page = 'home';
            }
            $pl = $prefix = '';
            if (!empty($_GET['pi'])) {
                $pl = $_GET['pi'];
                $pl = preg_replace('/\W/', '', $pl);
                $prefix = $pl.'_';
            }

            $msg = '

      Undefined text reference in page ' .$page.'

      ' .$text;

            $page = preg_replace('/\W/', '', $page);

            //sendMail($GLOBALS["developer_email"],"phplist dev, missing text",$msg);
            $line = "'".str_replace("'", "\'", $text)."' => '".str_replace("'", "\'", $text)."',";
//      if (is_file($this->basedir.'/en/'.$page.'.php') && $_SESSION['adminlanguage']['iso'] == 'en') {
            if (empty($prefix) && $_SESSION['adminlanguage']['iso'] == 'en') {
                $this->appendText($this->basedir.'/en/'.$page.'.php', $line);
            } else {
                $this->appendText('/tmp/'.$prefix.$page.'.php', $line);
            }

            if (!empty($_SESSION['show_translation_colours'])) {
                return '<span style="color: #FF1717">'.$text.'</span>'; //MISSING TEXT
            }
        }

        return $text;
    }

    public function appendText($file, $text)
    {
        return;
        $filecontents = '';
        if (is_file($file)) {
            $filecontents = file_get_contents($file);
        } else {
            $filecontents = '<?php

$lan = array(

);

      ?>';
        }

//    print "<br/>Writing $text to $file";
        $filecontents = preg_replace("/\n/", '@@NL@@', $filecontents);
        $filecontents = str_replace(');', '  '.$text."\n);", $filecontents);
        $filecontents = str_replace('@@NL@@', "\n", $filecontents);

        $dir = dirname($file);
        if (!is_writable($dir) || (is_file($file) && !is_writable($file))) {
            $newfile = basename($file);
            $file = '/tmp/'.$newfile;
        }

        file_put_contents($file, $filecontents);
    }

    public function initFSTranslations($language = '')
    {
        if (empty($language)) {
            $language = $this->language;
        }
        $translations = parsePO(file_get_contents(dirname(__FILE__).'/locale/'.$language.'/phplist.po'));
        $time = filemtime(dirname(__FILE__).'/locale/'.$language.'/phplist.po');
        $this->updateDBtranslations($translations, $time, $language);
    }

    public function updateDBtranslations($translations, $time, $language = '')
    {
        if (empty($language)) {
            $language = $this->language;
        }
        if (count($translations)) {
            foreach ($translations as $orig => $trans) {
                Sql_Query('replace into '.$GLOBALS['tables']['i18n'].' (lan,original,translation) values("'.$language.'","'.sql_escape($orig).'","'.sql_escape($trans).'")');
            }
        }
        $this->resetCache();
        saveConfig('lastlanguageupdate-'.$language, $time, 0);
    }

    public function getTranslation($text)
    {

        //# try DB, as it will be the latest
        if ($this->hasDB) {
            $db_trans = $this->databaseTranslation($text);
            if (!empty($db_trans)) {
                return $this->formatText($db_trans);
            } elseif (is_file(dirname(__FILE__).'/locale/'.$this->language.'/phplist.po')) {
                if (function_exists('getConfig')) {
                    $lastUpdate = getConfig('lastlanguageupdate-'.$this->language);
                    $thisUpdate = filemtime(dirname(__FILE__).'/locale/'.$this->language.'/phplist.po');
                    if (LANGUAGE_AUTO_UPDATE && $thisUpdate > $lastUpdate && !empty($_SESSION['adminloggedin'])) {
                        //# we can't translate this, as it'll be recursive
                        $GLOBALS['pagefooter']['transupdate'] = '<script type="text/javascript">initialiseTranslation("Initialising phpList in your language, please wait.");</script>';
                    }
                }
                //$this->updateDBtranslations($translations,$time);
            }
        }

        //# next try gettext, although before that works, it requires loads of setting up
        //# but who knows
        if ($this->hasGettext) {
            $gettext = $this->gettext($text);
            if (!empty($gettext)) {
                return $this->formatText($gettext);
            }
        }

        return '';
    }

    public function get($text)
    {
        if (trim($text) == '') {
            return '';
        }
        if (strip_tags($text) == '') {
            return $text;
        }

        // spelling mistake, retry with old spelling
        if ($text == 'over threshold, user marked unconfirmed' && empty($translation)) {
            return $this->get('over threshold, user marked unconfirmed');
        }
        $translation = $this->getTranslation($text);

        if (!empty($translation)) {
            return $translation;
        } else {
            return $this->missingText($text);
        }
    }
}

function getTranslationUpdates()
{
    //# @@@TODO add some more error handling
    $LU = false;
    $lan_update = fetchUrl(TRANSLATIONS_XML);
    if (!empty($lan_update)) {
        $LU = @simplexml_load_string($lan_update);
    }

    return $LU;
}

$I18N = new phplist_I18N();
if (!empty($setlanguage)) {
    $I18N->resetCache();
}
/* add a shortcut that seems common in other apps
 * function s($text)
 * @param $text string the text to find
 * @params 2-n variable - parameters to pass on to the sprintf of the text
 * @return translated text with parameters filled in
 *
 *
 * eg s("This is a %s with a %d and a %0.2f","text",6,1.98765);
 *
 * will look for the translation of the string and substitute the parameters
 *
 **/

function s($text)
{
    //# allow overloading with sprintf parameters
    $translation = $GLOBALS['I18N']->get($text);

    if (func_num_args() > 1) {
        $args = func_get_args();
        array_shift($args);
        $translation = vsprintf($translation, $args);
    }

    return $translation;
}

/**
 * function snbr
 * similar to function s, but without overloading params
 * will return the translated text with spaces turned to &nbsp; so that they won't wrap
 * mostly useful for buttons.
 */
function snbr($text)
{
    $trans = s($text);
    $trans = str_replace(' ', '&nbsp;', $trans);

    return $trans;
}

/**
 * function sJS
 * get the translation from the S function, but escape single quotes for use in Javascript.
 */
function sjs($text)
{
    $trans = s($text);
    $trans = str_replace("'", "\'", $trans);

    return $trans;
}

/**
 * function sHtmlEntities
 * get the translation from the s function, but escape it by using htmlentities.
 */
function sHtmlEntities ($text) {
    return htmlentities(s($text));
}

function parsePo($translationUpdate)
{
    $translation_lines = explode("\n", $translationUpdate);
    $original = '';
    $flagOrig = $flagTrans = false;
    $translation = '';
    $translations = array();
    foreach ($translation_lines as $line) {
        if (preg_match('/^msgid "(.*)"/', $line, $regs)) {
            $original = $regs[1];
            $flagOrig = true;
        } elseif (preg_match('/^msgstr "(.*)"/', $line, $regs)) {
            $flagOrig = false;
            $flagTrans = true;
            $translation = $regs[1];
        } elseif (preg_match('/^"(.*)"/', $line, $regs) && !(preg_match('/^#/', $line) || preg_match('/^\s+$/',
                    $line) || $line == '')
        ) {
            //# wrapped to multiple lines, can be both original and translation
            if ($flagTrans) {
                $translation .= $regs[1];
            } else {
                $original .= $regs[1];
            }
        } elseif (preg_match('/^#/', $line) || preg_match('/^\s+$/', $line) || $line == '') {
            $original = $translation = '';
            $flagOrig = $flagTrans = false;
        }
        if (!empty($original) && !empty($translation)) {
            $translations[trim($original)] = trim($translation);
        }
    }

    return $translations;
}
