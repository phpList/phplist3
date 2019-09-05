<?php

class GoogleAnalyticsQuery implements AnalyticsQuery
{
    public function trackingParameters($emailFormat, $messageData)
    {
        return array(
            'utm_source' => $messageData['utm_source'],
            'utm_medium' => $messageData['utm_medium'],
            'utm_campaign' => $messageData['utm_campaign'],
            'utm_content' => $emailFormat,
        );
    }

    public function editableParameters($messageData)
    {
        return array(
            'utm_source' => 'phpList',
            'utm_medium' => 'email',
            'utm_campaign' => $messageData['subject'],
        );
    }

    public function prefix()
    {
        return 'utm_';
    }
}

class MatomoAnalyticsQuery implements AnalyticsQuery
{
    public function trackingParameters($emailFormat, $messageData)
    {
        return array(
            'pk_source' => $messageData['pk_source'],
            'pk_medium' => $messageData['pk_medium'],
            'pk_campaign' => $messageData['pk_campaign'],
            'pk_content' => $emailFormat,
        );
    }

    public function editableParameters($messageData)
    {
        return array(
            'pk_source' => 'phpList',
            'pk_medium' => 'email',
            'pk_campaign' => $messageData['subject'],
        );
    }

    public function prefix()
    {
        return 'pk_';
    }
}

/**
 * Return an instance of a class that will provide the parameters for an analytic tracker.
 *
 * @return AnalyticsQuery class instance
 */
function getAnalyticsQuery()
{
    global $analyticsqueryplugin;

    $config = getConfig('analytic_tracker');

    if ($config == 'plugin' && $analyticsqueryplugin) {
        return $analyticsqueryplugin;
    }

    return $config == 'matomo'
        ? new MatomoAnalyticsQuery()
        : new GoogleAnalyticsQuery();
}

/**
 * Add analytic query parameters to the URL also removing any existing values.
 *
 * @param string $url
 * @param array  $trackingParameters query parameters as key => value
 * @param string $prefix tracking parameter prefix
 *
 * @return string
 */
function addAnalyticsTracking($url, $trackingParameters, $prefix)
{
    $position = strpos($url, '#');

    if ($position !== false) {
        $baseUrl = substr($url, 0, $position);
        $fragment = substr($url, $position);
    } else {
        $baseUrl = $url;
        $fragment = '';
    }

    if (strpos($baseUrl, $prefix) !== false) {
        // Take off existing tracking code. The regex can leave a trailing ? or & which needs to be removed.
        $regex = sprintf('/%s.+?=[^&]+(&|$)/', $prefix);
        $baseUrl = rtrim(preg_replace($regex, '', $baseUrl), '?&');
    }

    // re-construct the URL but exclude any empty parameters
    $trackingcode = http_build_query(array_filter($trackingParameters));
    $separator = strpos($baseUrl, '?') ? '&' : '?';

    return $baseUrl.$separator.$trackingcode.$fragment;
}
