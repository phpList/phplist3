<?php

require_once dirname(__FILE__).'/accesscheck.php';

// for now redirect to export

// it would be good to rewrite this to export the user search selection
// in the users page instead.

header('Location: ./?page=export');
exit;
