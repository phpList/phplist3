<?php

@include 'gchartphp/gChart.php';

if (class_exists('utility')) {
    $util = new utility();
}
if (!function_exists('monthName')) {
    function monthName($month, $short = 0)
    {
        $months = array(
            '',
            $GLOBALS['I18N']->get('January'),
            $GLOBALS['I18N']->get('February'),
            $GLOBALS['I18N']->get('March'),
            $GLOBALS['I18N']->get('April'),
            $GLOBALS['I18N']->get('May'),
            $GLOBALS['I18N']->get('June'),
            $GLOBALS['I18N']->get('July'),
            $GLOBALS['I18N']->get('August'),
            $GLOBALS['I18N']->get('September'),
            $GLOBALS['I18N']->get('October'),
            $GLOBALS['I18N']->get('November'),
            $GLOBALS['I18N']->get('December'),
        );
        $shortmonths = array(
            '',
            $GLOBALS['I18N']->get('Jan'),
            $GLOBALS['I18N']->get('Feb'),
            $GLOBALS['I18N']->get('Mar'),
            $GLOBALS['I18N']->get('Apr'),
            $GLOBALS['I18N']->get('May'),
            $GLOBALS['I18N']->get('Jun'),
            $GLOBALS['I18N']->get('Jul'),
            $GLOBALS['I18N']->get('Aug'),
            $GLOBALS['I18N']->get('Sep'),
            $GLOBALS['I18N']->get('Oct'),
            $GLOBALS['I18N']->get('Nov'),
            $GLOBALS['I18N']->get('Dec'),
        );
        if ($short) {
            return $shortmonths[intval($month)];
        } else {
            return $months[intval($month)];
        }
    }
}

$systemstats = array(
    array(
        'name'  => 'New Subscribers',
        'query' => sprintf('select count(id) as num,year(entered) year,month(entered) month from %s group by year(entered), month(entered) order by entered desc',
            $GLOBALS['tables']['user']),
    ),
    array(
        'name'  => 'Total Subscribers',
        'query' => sprintf('select count(id) as num,year(entered) year,month(entered) month from %s group by year(entered), month(entered) order by entered asc',
            $GLOBALS['tables']['user']),
        'collate' => true,
    ),
    array(
        'name'  => 'Current Subscribers',
        'query' => sprintf('select count(id) as num,year(now()) year,month(now()) month from %s',
            $GLOBALS['tables']['user']),
    ),
    array(
        'name'  => 'Sent Messages by month',
        'query' => sprintf('select count(entered) as num,year(entered) as year,month(entered) month from %s where status = "sent" group by year(entered), month(entered) order by entered desc',
            $GLOBALS['tables']['usermessage']),
    ),
    array(
        'name'  => 'Sent Messages by year',
        'query' => sprintf('select count(entered) as num,year(entered) as year from %s where status = "sent" group by year(entered) order by entered desc',
            $GLOBALS['tables']['usermessage']),
        'range' => 'year',
    ),
    array(
        'name'  => 'Opened Messages',
        'query' => sprintf('select count(entered) as num,year(entered) as year,month(entered) month from %s where viewed is not null and status = "sent" group by year(entered), month(entered) order by entered desc',
            $GLOBALS['tables']['usermessage']),
    ),
    array(
        'name'  => 'Campaigns',
        'query' => sprintf('select count(entered) as num,year(entered) as year,month(entered) month from %s where status = "sent" group by year(entered), month(entered) order by entered desc',
            $GLOBALS['tables']['message']),
    ),
    array(
        'name'  => 'Campaigns by year',
        'query' => sprintf('select count(entered) as num,year(entered) as year from %s where status = "sent" group by year(entered) order by entered desc',
            $GLOBALS['tables']['message']),
        'range' => 'year',
    ),
    array(
        'name'  => 'Bounces',
        'query' => sprintf('select count(id) as num,year(date) year,month(date) month from %s group by year(date), month(date) order by date desc',
            $GLOBALS['tables']['bounce']),
    ),
    array(
        'name'  => 'Blacklist Additions',
        'query' => sprintf('select count(email) as num,year(added) as year,month(added) month from %s group by year(added), month(added) order by added desc',
            $GLOBALS['tables']['user_blacklist']),
    ),
    array(
        'name'  => 'Spam Complaints',
        'query' => sprintf('select count(bl.email) as num,year(added) as year,month(added) month from %s bl,%s bldata where bl.email = bldata.email and bldata.name = "reason" and bldata.data = "blacklisted due to spam complaints" group by year(added), month(added) order by added desc',
            $GLOBALS['tables']['user_blacklist'], $GLOBALS['tables']['user_blacklist_data']),
    ),
    array(
        'name'  => 'User Clicks',
        'query' => sprintf('select count(distinct(userid)) as num ,year(firstclick) as year,month(firstclick) month  from %s where forwardid not in (select id from %s where url like "%%/lists/?p=unsubscribe") group by year(firstclick), month(firstclick) order by firstclick desc',
            $GLOBALS['tables']['linktrack_uml_click'], $GLOBALS['tables']['linktrack_forward']),
    ),
    array(
        'name'  => 'Unsubscribe Clicks',
        'query' => sprintf('select count(distinct(userid)) as num ,year(firstclick) as year,month(firstclick) month  from %s where forwardid in (select id from %s where url like "%%/lists/?p=unsubscribe") group by year(firstclick), month(firstclick) order by firstclick desc',
            $GLOBALS['tables']['linktrack_uml_click'], $GLOBALS['tables']['linktrack_forward']),
    ),
    array(
        'name'  => 'Next subscriberid',
        'query' => sprintf('select Auto_increment as num, year(now()) as year, month(now()) as month FROM information_schema.tables where table_name="%s" AND table_schema="%s"',
            $GLOBALS['tables']['user'], $GLOBALS['database_name']),
    ),
);

$chartCount = 0;
foreach ($systemstats as $item) {
    ++$chartCount;
    if (!isset($item['range'])) {
        $item['range'] = 'month';
    }
    if (!isset($item['collate'])) {
        $item['collate'] = false;
    }

    $req = Sql_Query($item['query']);
    $ls = new WebblerListing('');
    $chartData = array();
    $collation = 0;
    while ($row = Sql_Fetch_Assoc($req)) {
        if (!isset($chartData[$row['year']]) || !is_array($chartData[$row['year']])) {
            $chartData[$row['year']] = array();
        }
        if ($item['collate']) {
            $collation = $collation + $row['num'];
            $row['num'] = $collation;
        }
        if ($item['range'] != 'year') {
            $ls->addElement($row['year'].' '.monthName($row['month']));
            $ls->addColumn($row['year'].' '.monthName($row['month']), '#', $row['num']);
            $chartData[$row['year']][$row['month']] = $row['num'];
        } else {
            $ls->addElement($row['year']);
            $ls->addColumn($row['year'], '#', $row['num']);
            $chartData[$row['year']][''] = $row['num'];
        }
        if (!empty($row['year']) && !empty($row['month']) && !empty($row['num'])) {
            cl_output($item['name'].'|'.$row['year'].'|'.$row['month'].'|'.$row['num']);
        }
    }

    unset($chartData['2000']);
    unset($chartData['2001']);
    unset($chartData['2002']);
    unset($chartData['2003']);
    unset($chartData['2004']);
    unset($chartData['2005']);
    unset($chartData['2006']);
    //unset($chartData['2007']);
    //unset($chartData['2008']);
    //unset($chartData['2009']);
    //unset($chartData['2011']);

    //var_dump($chartData);
    if (class_exists('gBarChart')) {
        $Chart = new gBarChart(800, 350);
        $max = 0;
        $min = 99999;
        $nummonths = 0;
        $chartData = array_reverse($chartData, true);
        foreach ($chartData as $year => $months) {
            /*
        print "<h3>$year</h3>";
        var_dump($months);
      */
            ksort($months);
            $Chart->addDataSet(array_values($months));
            $monthmax = $util->getMaxOfArray($months);
            if ($monthmax > $max) {
                $max = $monthmax;
            }
            $nummonths = count($months);
        }
        $Chart->setLegend(array_keys($chartData));
        //$Chart->setBarWidth(4,1,3);
        $Chart->setAutoBarWidth();
        $Chart->setColors(array('ff3344', '11ff11', '22aacc', '3333aa'));
        $Chart->setVisibleAxes(array('x', 'y'));
        $Chart->setDataRange(0, $max);
        $Chart->addAxisRange(0, 1, $nummonths);
        $Chart->addAxisRange(1, 0, $max);
        //$lineChart->addBackgroundFill('bg', 'EFEFEF');
        //$lineChart->addBackgroundFill('c', '000000');
    }

    echo '<div class="tabbed">';
    echo '<h3>'.$GLOBALS['I18N']->get($item['name']).'</h3>';
    if (!empty($Chart)) {
        echo '<ul>';
        echo '<li><a href="#graph'.$chartCount.'">Graph</a></li>';
        echo '<li><a href="#numbers'.$chartCount.'">Numbers</a></li>';
        echo '</ul>';
    }

    if (!empty($Chart)) {
        echo '<div id="graph'.$chartCount.'">';
        //  print $Chart->getUrl();
        echo '<img src="./?page=gchart&url='.urlencode($Chart->getUrl()).'" />';
        echo '</div>';
    }
    echo '<div id="numbers'.$chartCount.'">';
    echo $ls->display();
    echo '</div>';
    echo '</div>';
}
