<?php

if (!empty($_POST['apikey'])) {
  if (!verifyToken()) { 
    print Error($GLOBALS['I18N']->get('No Access'));
    return;
  }
  $check = file_get_contents(PQAPI_URL.'&cmd=verifykey&key='.trim($_POST['apikey']));
  $check = trim($check);
  if (strpos($check,'KEYPASS') !== false) {
    SaveConfig('PQAPIkey',trim(str_replace('"','',strip_tags($_POST['apikey']))),0);
    SaveConfig('pqchoice','phplistdotcom',0);
    $_SESSION['action_result'] = s('Settings were saved successfully');
    Redirect('hostedprocessqueuesetup');
  } else {
    $_SESSION['action_result'] = s('Error, the API key is incorrect');
    Redirect('hostedprocessqueuesetup');
  }
}

$existingKey = getConfig('PQAPIkey');

print '<h2>'.s('Process the queue using the service from phpList.com').'</h2>';
print '<p>'.s('This is only possible if your phpList installation is not behind a firewall').'</p>';
print formStart();

print '<h3>Step 1. Create an account on phpList.com </h3>';
print '<p>For the purpose of remote processing go to <a href="https://www.phplist.com/createaccount" target="_blank" class="button">Create Account</a> and follow the steps.</p>';
print '<p>If you use the normal registration procedure it will create a phpList Hosted account for you, which is not what you need for remote processing.</p>';
print '<p>Once you are registered, go to your account page and request an API key</p>';
print '<h3>Step 2. Enter the API key here</h3>';

print '<label for="apikey">'.s('API key from phpList.com').'</label><input type="text" name="apikey" value="'.$existingKey.'">';
print '<button type="submit" class="button">'.s('Continue setup').'</button>';
print '</form>';
