<?php

require_once dirname(__FILE__).'/accesscheck.php';

echo Help('preparemessage', 'What is prepare a message');

$access = accessLevel('preparemessage');
switch ($access) {
    case 'owner':
        $subselect = ' where owner = '.$_SESSION['logindetails']['id'];
        $ownership = ' and owner = '.$_SESSION['logindetails']['id'];
        break;
    case 'all':
        $subselect = '';
        break;
    case 'none':
    default:
        $subselect = ' where id = 0';
        $ownership = ' and id = 0';
        break;
}

include 'send_core.php';

if (!$done) {
    echo '<p class="submit"><input type="submit" name=prepare value="Add message"></p></form>';
}
