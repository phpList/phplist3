<?php
/**
 * Build the news <ul> element from the rss feed items.
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

    return "<ul>$news</ul>";
}

/**
 * Generate the short and long community news lists from an rss feed then cache
 * in the session.
 */
$newsSize = 'long';

if (empty($_SESSION['adminloggedin'])
    || (isset($_GET['page']) && !in_array($_GET['page'], array('home', 'about', 'dashboard', 'community', 'login')))) {
    $newsSize = 'short';
}

if (isset($_SESSION['communitynews'][$newsSize])) {
    echo $_SESSION['communitynews'][$newsSize];

    return;
}

include 'onyxrss/onyx-rss.php';
$onyxRss = new ONYX_RSS();

if (!DEVVERSION) {
    $onyxRss->setDebugMode(false);
}
$onyxRss->setCachePath($GLOBALS['tmpdir']);
$onyxRss->setExpiryTime(1440);
$parseresult = $onyxRss->parse('https://www.phplist.org/newslist/feed/', 'phplistnews');

if ($parseresult) {
    $_SESSION['communitynews']['short'] = buildNews($onyxRss, 3);
    $_SESSION['communitynews']['long'] = buildNews($onyxRss, 10);
} else {
    $_SESSION['communitynews']['short'] = '';
    $_SESSION['communitynews']['long'] = '';
}

echo $_SESSION['communitynews'][$newsSize];
