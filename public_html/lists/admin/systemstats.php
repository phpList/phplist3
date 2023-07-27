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

