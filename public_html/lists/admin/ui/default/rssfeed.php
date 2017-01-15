<?php

/**
 * Build the news div element from the rss feed items.
 *
 * @param ONYX_RSS $rss onyx-rss instance
 * @param int      $max the maximum number of feed items to return
 *
 * @return string the generated html or an empty string
 */
function buildNews($rss, $max)
{
    if ($rss->numItems() == 0) {
        return '';
    }

    $news = '';
    $count = 0;
    // reset index so that feed items can be processed more than once
    $rss->rss['output_index'] = -1;

    while ($item = $rss->getNextItem()) {
        ++$count;

        if ($count > $max) {
            break;
        }
        $date = $item['pubdate'];
        $date = str_replace('00:00:00 +0000', '', $date);
        $date = str_replace('00:00:00 +0100', '', $date);
        $date = str_replace('+0000', '', $date);
        if (preg_match('/\d+:\d+:\d+/', $date, $regs)) {
            $date = str_replace($regs[0], '', $date);
        }

        //# remove the '<p>&nbsp;</p>' in the descriptions
        $desc = $item['description'];
        $desc = str_replace('<p>&nbsp;</p>', '', $desc);
        $desc = '';

        $news .= '<li>
    <div class="publisheddate">'.$date.'</div> <a href="'.$item['link'].'?utm_source=phplist-'.VERSION.'&utm_medium=newspanel&utm_content='.urlencode($item['title']).'&utm_campaign=newspanel" target="_blank">'.$item['title'].'</a>
    '.$desc.'
    </li>';
    }

    $format = <<<'END'
<div id="newsfeed" class="menutableright block">
<h3>%s</h3>
<ul>%s</ul>
</div>
END;

    return sprintf($format, s('phpList community news'), $news);
}

/**
 * Generate the short and long news sidebars from an rss feed then cache
 * in the session.
 */
$newsSize = 'long';

if (empty($_SESSION['adminloggedin'])
    || (isset($_GET['page']) && !in_array($_GET['page'], array('home', 'about', 'dashboard', 'community', 'login')))) {
    $newsSize = 'short';
}

if (isset($_SESSION['news'][$newsSize])) {
    echo $_SESSION['news'][$newsSize];

    return;
}

include dirname(__FILE__).'/onyx-rss.php';
$rss = new ONYX_RSS();
if (!DEVVERSION) {
    $rss->setDebugMode(false);
}
$rss->setCachePath($GLOBALS['tmpdir']);
$rss->setExpiryTime(1440);
$parseresult = $rss->parse('https://www.phplist.org/newslist/feed/', 'phplistnews');

if ($parseresult) {
    $_SESSION['news']['short'] = buildNews($rss, 3);
    $_SESSION['news']['long'] = buildNews($rss, 10);
} else {
    $_SESSION['news']['short'] = '';
    $_SESSION['news']['long'] = '';
}
echo $_SESSION['news'][$newsSize];
