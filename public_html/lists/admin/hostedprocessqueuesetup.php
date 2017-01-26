<?php

$fopenAllowed = ini_get('allow_url_fopen');

if (!empty($_POST['apikey'])) {
    if (!verifyToken()) {
        echo Error($GLOBALS['I18N']->get('No Access'));

        return;
    }
    $streamContext = stream_context_create(array(
        'http' => // even though we use https, this has to be http
            array(
                'timeout' => 10, // this should be fast, so let's not wait too long
            ),
    ));

    $check = @file_get_contents(PQAPI_URL.'&cmd=verifykey&key='.trim($_POST['apikey']), false, $streamContext);
    $check = trim($check);
    if (!empty($check) && strpos($check, 'KEYPASS') !== false) {
        SaveConfig('PQAPIkey', trim(str_replace('"', '', strip_tags($_POST['apikey']))), 0);
        SaveConfig('pqchoice', 'phplistdotcom', 0);
        //# if we have active campaigns, start them now
        $_SESSION['action_result'] = s('Remote queue processing settings were saved successfully');
        $count = Sql_Fetch_Row_Query(sprintf("select count(*) from %s where status not in ('draft', 'sent', 'prepared', 'suspended') and embargo <= now()",
            $tables['message']));
        if ($count[0] > 0) {
            $_SESSION['action_result'] .= '<br/>'.activateRemoteQueue();
        }
        Redirect('messages&tab=active');
    } else {
        if (!empty($http_response_header[0]) && strpos($http_response_header[0], '200 OK') !== false) {
            $_SESSION['action_result'] = s('Error, the API key is incorrect');
        } else {
            $_SESSION['action_result'] = s('Error, unable to connect to the phpList.com server for checking. Please verify that your webserver is able to connect to https://pqapi.phplist.com').'<br/><a href="./?page=processqueue&pqchoice=local" class="button">'.s('Use local processing instead').'</a>';
        }
        Redirect('hostedprocessqueuesetup');
    }
}

$existingKey = getConfig('PQAPIkey');

echo '<h2>'.s('Process the queue using the service from phpList.com').'</h2>';

if ($fopenAllowed) {
    echo '<p>'.s('This is only possible if your phpList installation is not behind a firewall').'</p>';
} else {
    echo '<p>'.s('Your PHP settings do not allow this functionality. Please set "allow_url_fopen" in your php.ini to be "on" to continue.').'</p>';
    echo '  <a href="./?page=processqueue&pqchoice=local" class="button">'.s('Use local processing instead').'</a>';

    return;
}
echo formStart();

echo '<h3>Step 1. Create an account on phpList.com </h3>';
echo '<p>For the purpose of remote processing go to <a href="https://www.phplist.com/createaccount" target="_blank" class="button">Create Account</a> and follow the steps.</p>';
echo '<p>If you use the normal registration procedure it will create a phpList Hosted account for you, which is not what you need for remote processing.</p>';
echo '<p>Once you are registered, go to your account page and request an API key</p>';
echo '<h3>Step 2. Enter the API key here</h3>';

echo '<label for="apikey">'.s('API key from phpList.com').'</label><input type="text" name="apikey" value="'.$existingKey.'">';
echo '<button type="submit" class="button">'.s('Continue setup').'</button>';
echo '</form>';
