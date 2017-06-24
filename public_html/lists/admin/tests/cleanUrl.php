<?php

class cleanUrl extends phplistTest
{
    public $name = 'clean URL';
    public $purpose = 'Check cleaning of URLs for clicktracking';

    private $tests = array(

        'simple' => array(
            'orig'   => 'https://www.phplist.com',
            'result' => 'https://www.phplist.com',
        ),
        'https' => array(
            'orig'   => 'https://www.phplist.com',
            'result' => 'https://www.phplist.com',
        ),
        'param1' => array(
            'orig'   => 'https://www.phplist.com/?lid=2345',
            'result' => 'https://www.phplist.com/?lid=2345',
        ),
        'emptykey' => array( //# https://mantis.phplist.com/view.php?id=8980
            'orig'   => 'http://sub.domain.com/clk;6961dd9731;1544477399;e?http://www.domain.com/offer',
            'result' => 'http://sub.domain.com/clk;6961dd9731;1544477399;e?http://www.domain.com/offer',
        ),
        'paramisnull' => array(//# https://mantis.phplist.com/view.php?id=15615
            'orig'   => 'http://sub.domain.com/?param=0',
            'result' => 'http://sub.domain.com/?param=0',
        ),
        'googlemaps' => array(
            'orig'   => 'https://maps.google.co.nz/maps/ms?msa=0&msid=205172721934932230455.0004b9985e4a2669ddcab&ie=UTF8&t=h&ll=11.867351,-98.789062&spn=122.508405,263.320313&z=2&source=embed&mid=1364172689',
            'result' => 'https://maps.google.co.nz/maps/ms?msa=0&msid=205172721934932230455.0004b9985e4a2669ddcab&ie=UTF8&t=h&ll=11.867351,-98.789062&spn=122.508405,263.320313&z=2&source=embed&mid=1364172689',
        ),
        'doubleclick' => array(
            'orig'   => 'http://ad.doubleclick.net/clk;273332321;99419470;i?http://www.ticketmaster.com/ANNIE-NY-tickets/artist/1740456?camefrom=CFC_NED_ANNIE_2BWORLD&utm_source=BWW&utm_medium=email&utm_campaign=annie',
            'result' => 'http://ad.doubleclick.net/clk;273332321;99419470;i?http://www.ticketmaster.com/ANNIE-NY-tickets/artist/1740456?camefrom=CFC_NED_ANNIE_2BWORLD&utm_source=BWW&utm_medium=email&utm_campaign=annie',
        ),
        'cleanUID' => array(
            'orig'   => 'http://sub.domain.com/lists/?p=unsubscribe&uid=75edfb003756d00aa3bc58b3b630939c',
            'result' => 'http://sub.domain.com/lists/?p=unsubscribe',
        ),
        'cleanUIDSESSID' => array(
            'orig'   => 'http://sub.domain.com/lists/?p=unsubscribe&uid=75edfb003756d00aa3bc58b3b630939c&PHPSESSID=ABCDEFG',
            'result' => 'http://sub.domain.com/lists/?p=unsubscribe',
        ),
        'googlemapsUID' => array(
            'orig'   => 'https://maps.google.co.nz/maps/ms?msa=0&msid=205172721934932230455.0004b9985e4a2669ddcab&ie=UTF8&t=h&uid=75edfb003756d00aa3bc58b3b630939c&PHPSESSID=ABCDEFG&ll=11.867351,-98.789062&spn=122.508405,263.320313&z=2&source=embed&mid=1364172689',
            'result' => 'https://maps.google.co.nz/maps/ms?msa=0&msid=205172721934932230455.0004b9985e4a2669ddcab&ie=UTF8&t=h&ll=11.867351,-98.789062&spn=122.508405,263.320313&z=2&source=embed&mid=1364172689',
        ),
    );

    public function runtest()
    {
        $pass = 1;
        foreach ($this->tests as $test) {
            $result = cleanUrl($test['orig'], array('PHPSESSID', 'uid'));
            echo $result.' should be '.$test['result'];
            $pass = $pass && $result == $test['result'];
            if ($pass) {
                echo $GLOBALS['img_tick'];
            } else {
                echo $GLOBALS['img_cross'];
            }
            echo '<br/>';
        }

        return $pass;
    }
}
