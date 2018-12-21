<?php

require_once dirname(__FILE__).'/accesscheck.php';

/*
 *
 * page to handle any cron-related activity. Instead of having multiple cron entries, this
 * one page should be called to do the tasks. It should be called as often as possible, eg once every 5 minutes
 * or even once a minute.
 *
 * For now, the configuration is manually, there is no UI for it yet. Plugins can register their own cron activities.
 * TODO, work with eg https://github.com/mtdowling/cron-expression
 *
 */

if (!$GLOBALS['commandline']) {
    echo 'This page can only be called from the commandline';

    return;
}
$cronJobs = array(

    // at a later stage, these should be added
    // for now, that involves conflicts, as they all use similar function names in the files. (eg output and finish)
    // also page locking needs changing, as it's the same page (cron) for all of them
    // so, for now, we only handle plugin Cron Jobs

    //array(
    //    'plugin' => '',
    //    'page' => 'processqueue',
    //    'frequency'  => 1,    // once a minute
    //),
    //array(
    //'plugin' => '',
    //'page' => 'processbounces',
    //'frequency' => 1440, // once a day
    //),
);

foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
    $pluginJobs = $plugin->cronJobs();
    // cl_output($pluginname.' has '.sizeof($pluginJobs).' jobs');
    foreach ($pluginJobs as $job) {
        $cronJobs[] = array(
            'plugin'    => $pluginname,
            'page'      => $job['page'],
            'frequency' => $job['frequency'],
            //# @@ TODO extend it for eg a method to call on the plugin
            //'method' => 'method to call';
        );
    }
}

if (!count($cronJobs)) {
    cl_output(s('Nothing to do'));
    exit;
}

$maxNextRun = 0;
$now = time();
foreach ($cronJobs as $cronJob) {
    $cronID = $cronJob['plugin'].'|'.$cronJob['page'];
    $lastrun = getConfig(md5($cronID));
    if (empty($lastrun) || ($now - $lastrun > $cronJob['frequency'] * 60)) {
        cl_output('Need to run '.$cronJob['plugin'].' - '.$cronJob['page']);
        $cronJob['page'] = basename($cronJob['page'], '.php');
        $cmd_result = '';
        if (isset($GLOBALS['plugins'][$cronJob['plugin']]) && is_file($GLOBALS['plugins'][$cronJob['plugin']]->coderoot.$cronJob['page'].'.php')) {
            cl_output('running php '.$argv[0].' -c '.$GLOBALS['configfile'].' -m '.$cronJob['plugin'].' -p '.$cronJob['page']);
            exec('php '.$argv[0].' -c '.$GLOBALS['configfile'].' -m '.$cronJob['plugin'].' -p '.$cronJob['page'],
                $cmd_result);
        } elseif (empty($cronJob['plugin']) && is_file(__DIR__.'/'.$cronJob['page'].'.php')) {
            cl_output('running php '.$argv[0].' -c '.$GLOBALS['configfile'].' -p '.$cronJob['page']);
            exec('php '.$argv[0].' -c '.$GLOBALS['configfile'].' -p '.$cronJob['page'], $cmd_result);
        }
        SaveConfig(md5($cronID), time(), 0);
    } else {
        $nextRun = ($lastrun + $cronJob['frequency'] * 60) - $now;
        if ($nextRun > $maxNextRun) {
            $maxNextRun = $nextRun;
        }
        if (VERBOSE) {
            cl_output('Will run '.$cronJob['plugin'].' - '.$cronJob['page'].' in'.secs2time($nextRun));
        }
    }
}
//# tell how soon we need to run again, so that the calling system can relax a bit
if ($maxNextRun > 0) {
    cl_output('DELAYUNTIL='.(int) ($now + $maxNextRun));
}
#var_dump($cronJobs);
