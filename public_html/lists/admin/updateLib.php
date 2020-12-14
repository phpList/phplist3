<?php

/**
 * Get response from server.
 *
 * @return mixed
 * @throws Exception
 */
function getResponse()
{
    $serverUrl = "https://download.phplist.org/version.json";
    $updateUrl = $serverUrl . '?version=' . VERSION;

    $responseFromServer = fetchUrl($updateUrl,array(),259200); ## cache for three days
    $responseFromServer = json_decode($responseFromServer, true);
    return $responseFromServer;
}

/**
 * Check for update and return a message only if there is an update available.
 *
 * @return string
 * @throws Exception
 */
function checkForUpdate()
{
    $serverResponse = getResponse();
    $version = isset($serverResponse['version']) ? $serverResponse['version'] : '';
    $enabledNotification = true;

    if (strpos($version, 'RC') && (getConfig('rc_notification') == 0)) {
        $enabledNotification = false;
    }
    $versionString = isset($serverResponse['versionstring']) ? $serverResponse['versionstring'] : '';

    if ($version !== '' && $version !== VERSION && version_compare(VERSION, $version) < 0 && $enabledNotification) {
        $updateMessage = s('A new version of phpList is available: %s',htmlentities($versionString));
    } else {
        $updateMessage = '';
    }

    ## why not just save it as epoch, makes calculations much easier
    SaveConfig('lastcheckupdate', date('m/d/Y h:i:s', time()), 0, true);

    return $updateMessage;
}

/**
 * Check every 3 days for a new update
 *
 * @return bool
 */
function lastTimeCheck()
{

    $doCheck = false;

    $currentTime = date('m/d/Y h:i:s', time());
    $lastCheckTime = getConfig('lastcheckupdate');
    $lastTimeFormattedDateTime = new DateTime($lastCheckTime);
    $currentTimeFormattedDateTime = new DateTime($currentTime);

    $interval = $currentTimeFormattedDateTime->diff($lastTimeFormattedDateTime);
    $dDiff = $interval->format('%a');

    if ($dDiff >= '3') {

        $doCheck = true;
    }

    return $doCheck;
}

/**
 * Show notification every 3 days only
 * @return bool
 */
function showUpdateNotification()
{
    # we can show all the time, the fetching is cached for three days
    return true;

    if (lastTimeCheck()) {

        return true;
    }

    return false;
}
