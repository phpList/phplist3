<?php

require_once dirname(__FILE__).'/accesscheck.php';

//## add "Ajaxable actions" that just return the result, but show in the full page when not ajaxed

$ajax = isset($_GET['ajaxed']);

if ($ajax) {
    @ob_end_clean();
    @ob_start();
    if (is_file(dirname(__FILE__).'/ui/'.$GLOBALS['ui'].'/pagetop_minimal.php')) {
        include_once dirname(__FILE__).'/ui/'.$GLOBALS['ui'].'/pagetop_minimal.php';
    }
}
$status = s('Failed');
if (!empty($_GET['action'])) {
    $action = basename($_GET['action']);
    if (is_file(dirname(__FILE__).'/actions/'.$action.'.php')) {
        include dirname(__FILE__).'/actions/'.$action.'.php';
    } elseif (!empty($_GET['origpage'])) {
        $action = basename($_GET['origpage']);
        if (is_file(dirname(__FILE__).'/actions/'.$action.'.php')) {
            include dirname(__FILE__).'/actions/'.$action.'.php';
        }
    }
} else {
    Redirect('home');
}

echo $status;
if (0 && !empty($GLOBALS['developer_email'])) {
    echo '<br/><a href="'.$_SERVER['REQUEST_URI'].'" target="_blank">'.$_SERVER['REQUEST_URI'].'</a>';
}

if ($ajax) {
    echo '</body></html>';
    exit;
}
