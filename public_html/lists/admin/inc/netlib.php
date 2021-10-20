<?php

require_once dirname(__FILE__).'/accesscheck.php';

function getClientIP()
{
    static $the_ip = null;

    if ($the_ip !== null) {
        return $the_ip;
    }
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        //logEvent("apache_request_headers");
    } else {
        $headers = $_SERVER;
        //logEvent("_SERVER");
    }

    if (array_key_exists('X-Forwarded-For', $headers)) {
        //logEvent("server1=".$headers['X-Forwarded-For']);
    }

    if (array_key_exists('HTTP_X_FORWARDED_FOR', $headers)) {
        //logEvent("server2=".$headers['HTTP_X_FORWARDED_FOR']);
    }

    if (array_key_exists('X-Forwarded-For', $headers)) {
        $forwarded_for = $headers['X-Forwarded-For'];
        $forwarded_list = explode(',', $forwarded_for);
        $forwarded_list = array_map('trim', $forwarded_list);
        $the_ip = array_shift($forwarded_list);

        if (filter_var($the_ip, FILTER_VALIDATE_IP)) {
            //logEvent("X-Forwarded-For ip=".$the_ip);
            return $the_ip;
        }
    }

    if (array_key_exists('HTTP_X_FORWARDED_FOR', $headers)) {
        $forwarded_for = $headers['HTTP_X_FORWARDED_FOR'];
        $forwarded_list = explode(',', $forwarded_for);
        $forwarded_list = array_map('trim', $forwarded_list);
        $the_ip = array_shift($forwarded_list);

        if (filter_var($the_ip, FILTER_VALIDATE_IP)) {
            //logEvent("HTTP_X_FORWARDED_FOR ip=".$the_ip);
            return $the_ip;
        }
    }

    $the_ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
    //logEvent("REMOTE_ADDR ip=".$the_ip);

    return $the_ip;
}
