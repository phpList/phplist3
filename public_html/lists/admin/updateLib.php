<?php
/**
 * Get Current phpList Version.
 *
 * @param string $path Production version location
 * @return string|bool
 */
function getCurrentphpListVersion($path = '')
{
    $version = file_get_contents($path);
    $matches = array();
    preg_match_all('/define\(\"VERSION\",\"(.*)\"\);/', $version, $matches);

    if (isset($matches[1][0])) {
        return $matches[1][0];
    } else {
        return false;
    }
}

/**
 * Get response from server.
 *
 * @param string $path Production version location
 * @return mixed
 * @throws Exception
 */
function getResponse($path = '')
{
    $serverUrl = "https://download.phplist.org/version.json";
    $updateUrl = $serverUrl . '?version=' . getCurrentphpListVersion($path);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $updateUrl);
    $responseFromServer = curl_exec($ch);
    curl_close($ch);

    $responseFromServer = json_decode($responseFromServer, true);

    return $responseFromServer;
}

/**
 * Check for update and return a message only if there is an update available.
 *
 * @param string $path Production version location
 * @return string
 * @throws Exception
 */
function checkForUpdate($path = '')
{
    $serverResponse = getResponse($path);
    $version = isset($serverResponse['version']) ? $serverResponse['version'] : '';
    $enabledNotification = true;

    if (strpos($version, 'RC') && (getConfig('rc_notification') == 0)) {
        $enabledNotification = false;
    }
    $versionString = isset($serverResponse['versionstring']) ? $serverResponse['versionstring'] : '';

    if ($version !== '' && $version !== getCurrentphpListVersion($path) && version_compare(getCurrentphpListVersion($path), $version) && $enabledNotification) {
        $updateMessage = s('Update to ' . htmlentities($versionString) . ' is available.  ');
    } else {
        $updateMessage = '';
    }

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

    if (lastTimeCheck()) {

        return true;
    }

    return false;
}
