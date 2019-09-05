<?php

require __DIR__ . '/../../public_html/lists/admin/AnalyticsQuery.php';
require __DIR__ . '/../../public_html/lists/admin/analytics.php';

/*
 * simulate phplist configuration settings and plugins
 */
$config = [];
$plugins = [];

function getConfig($param)
{
    global $config;

    return $config[$param];
}

class AnalyticsTest extends PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function createMatomoAnalyticsQuery()
    {
        global $config;

        $config = ['analytic_tracker' => 'matomo'];

        $tracker = getAnalyticsQuery();
        $this->assertInstanceOf('MatomoAnalyticsQuery', $tracker);
    }

    /**
     * @test
     */
    public function createGoogleAnalyticsQuery()
    {
        global $config;

        $config = ['analytic_tracker' => 'google'];

        $tracker = getAnalyticsQuery();
        $this->assertInstanceOf('GoogleAnalyticsQuery', $tracker);
    }

    /**
     * @test
     * @dataProvider addTrackingParametersDataProvider
     */
    public function addTrackingParameters($url, $format, $messageData, $expected)
    {
        global $config;

        $config = ['analytic_tracker' => 'google'];

        $analytics = getAnalyticsQuery();
        $parameters = $analytics->trackingParameters($format, $messageData);
        $prefix = $analytics->prefix();
        $newUrl = addAnalyticsTracking($url, $parameters, $prefix);
        $this->assertEquals($expected, $newUrl);
    }

    public function addTrackingParametersDataProvider()
    {
        return [
            'no tracking parameters' => [
                'https://mysite.com/index.php?p1=aaa',
                'HTML',
                ['utm_source' => 'newsource', 'utm_medium' => 'newmedium', 'utm_campaign' => 'newcampaign'],
                'https://mysite.com/index.php?p1=aaa&utm_source=newsource&utm_medium=newmedium&utm_campaign=newcampaign&utm_content=HTML',
            ],
            'no parameters' => [
                'https://mysite.com/index.php',
                'HTML',
                ['utm_source' => 'newsource', 'utm_medium' => 'newmedium', 'utm_campaign' => 'newcampaign'],
                'https://mysite.com/index.php?utm_source=newsource&utm_medium=newmedium&utm_campaign=newcampaign&utm_content=HTML',
            ],
            'only tracking parameters' => [
                'https://mysite.com/index.php?utm_source=mysource&utm_medium=mymedium&utm_campaign=mycampaign&utm_content=mycontent',
                'HTML',
                ['utm_source' => 'newsource', 'utm_medium' => 'newmedium', 'utm_campaign' => 'newcampaign'],
                'https://mysite.com/index.php?utm_source=newsource&utm_medium=newmedium&utm_campaign=newcampaign&utm_content=HTML',
            ],
            'trailing tracking parameters' => [
                'https://mysite.com/index.php?p1=aaa&utm_source=mysource&utm_medium=mymedium&utm_campaign=mycampaign&utm_content=mycontent',
                'text',
                ['utm_source' => 'newsource', 'utm_medium' => 'newmedium', 'utm_campaign' => 'newcampaign'],
                'https://mysite.com/index.php?p1=aaa&utm_source=newsource&utm_medium=newmedium&utm_campaign=newcampaign&utm_content=text',
            ],
            'leading tracking parameters' => [
                'https://mysite.com/index.php?utm_source=mysource&utm_medium=mymedium&utm_campaign=mycampaign&utm_content=mycontent&p1=aaa',
                'text',
                ['utm_source' => 'newsource', 'utm_medium' => 'newmedium', 'utm_campaign' => 'newcampaign'],
                'https://mysite.com/index.php?p1=aaa&utm_source=newsource&utm_medium=newmedium&utm_campaign=newcampaign&utm_content=text',
            ],
            'leading and trailing other parameters' => [
                'https://mysite.com/index.php?p1=aaa&utm_source=mysource&utm_medium=mymedium&utm_campaign=mycampaign&utm_content=mycontent&p2=bbb',
                'text',
                ['utm_source' => 'newsource', 'utm_medium' => 'newmedium', 'utm_campaign' => 'newcampaign'],
                'https://mysite.com/index.php?p1=aaa&p2=bbb&utm_source=newsource&utm_medium=newmedium&utm_campaign=newcampaign&utm_content=text',
            ],
            'leading and trailing other parameters with fragment' => [
                'https://mysite.com/index.php?p1=aaa&utm_source=mysource&utm_medium=mymedium&utm_campaign=mycampaign&utm_content=mycontent&p2=bbb#fragment',
                'HTML',
                ['utm_source' => 'newsource', 'utm_medium' => 'newmedium', 'utm_campaign' => 'newcampaign'],
                'https://mysite.com/index.php?p1=aaa&p2=bbb&utm_source=newsource&utm_medium=newmedium&utm_campaign=newcampaign&utm_content=HTML#fragment',
            ],
            'an empty tracking parameter' => [
                'https://mysite.com/index.php?utm_source=mysource&utm_medium=mymedium&utm_campaign=mycampaign&utm_content=mycontent',
                'HTML',
                ['utm_source' => 'newsource', 'utm_medium' => 'newmedium', 'utm_campaign' => ''],
                'https://mysite.com/index.php?utm_source=newsource&utm_medium=newmedium&utm_content=HTML',
            ],
        ];
    }
}
