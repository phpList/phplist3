<?php

require_once dirname(__FILE__).'/accesscheck.php';

if (!$GLOBALS['commandline']) {
    @ob_end_flush();
} else {
    //# when on cl, doit immediately
    $_GET['doit'] = 'yes';
}
$force = isset($cline['f']) || isset($_GET['force']);

function output($message)
{
    if ($GLOBALS['commandline']) {
        @ob_end_clean();
        echo strip_tags($message)."\n";
        ob_start();
    } else {
        echo $message;
        // output some stuff to make sure it's not buffered in the browser, hmm, would be nice to find a better way for this
        for ($i = 0; $i < 10000; ++$i) {
            echo '  '."\n";
        }
        flush();
        @ob_end_flush();
    }
    flush();
}

output("");
$dbversion = getConfig('version');
$releaseDBversion = getConfig('releaseDBversion'); // release version check
$inUpgrade = getConfig('in-upgrade-to');
if (!empty($inUpgrade) && $inUpgrade == VERSION) {
    if (!$force) {
        if ($GLOBALS['commandline']) {
            output(s('Another process is already upgrading this installation. Use -f to force upgrade, but only if you are sure the other process is no longer active.'));
        } else {
            output(s('Another process is already upgrading this installation. Click %s to force upgrade, but only if you are sure the other process is no longer active.', PageLinkButton('upgrade&doit=yes&force=1', s('Force Upgrade'))));
        }

        return;
    }
}

if (!$dbversion) {
    $dbversion = 'Older than 1.4.1';
}
output('<p class="information">'.$GLOBALS['I18N']->get('Your database version').': '.$dbversion.'</p>');

if ($GLOBALS['database_module'] == 'mysql.inc') {
    echo Warn(s('Please edit your config file and change "mysql.inc" to "mysqli.inc" to avoid future PHP incompatibility').
        resourceLink('http://resources.phplist.com/system/mysql-mysqli-update')
    );
}

if (!versionCompare($dbversion,'2.11.11') && $dbversion!=='dev') {
    Fatal_Error(s('Your version is older than 3.2.0 and cannot be upgraded to this version. Please upgrade to 3.2.0 first and then try again.'));
    return;
}

// only action upgrade if necessary
if ($force && $dbversion == VERSION  && defined('RELEASEDATE') && RELEASEDATE <= $releaseDBversion) {
    output(s('Your database is already the correct version (%s), including release date version (%s), there is no need to upgrade',$dbversion, $releaseDBversion));
    clearMaintenanceMode();
   unset($_GET['doit']);
}

if ($dbversion == VERSION && !$force) {
    output($GLOBALS['I18N']->get('Your database is already the correct version, there is no need to upgrade'));

    echo '<p>'.PageLinkAjax('upgrade&update=tlds', s('update Top Level Domains'), '', 'button').'</p>';
    clearMaintenanceMode();

    echo subscribeToAnnouncementsForm();
} elseif (isset($_GET['doit']) && $_GET['doit'] == 'yes') {
    $success = 1;
    // once we are off, this should not be interrupted

    // mark the system to be in maintenance mode
    setMaintenanceMode(s('Upgrading phpList to version '.VERSION));

    // force send and other processes to stop
    Sql_Query(sprintf('delete from %s ',$GLOBALS['tables']['sendprocess']));

    ignore_user_abort(1);
    // rename tables if we are using the prefix
    include dirname(__FILE__).'/structure.php';
    foreach ($DBstruct as $table => $value) {
        set_time_limit(500);
        if (isset($table_prefix)) {
            if (Sql_Table_exists($table) && !Sql_Table_Exists($tables[$table])) {
                Sql_Verbose_Query("alter table $table rename $tables[$table]", 1);
            }
        }
    }
    @ob_end_flush();
    @ob_start();

    output('<p class="information">'.$GLOBALS['I18N']->get('Please wait, upgrading your database, do not interrupt').'</p>');

    flush();

    if (preg_match('/(.*?)-/', $dbversion, $regs)) {
        $dbversion = $regs[1];
    }

    //# lock this process
    SaveConfig('in-upgrade-to', VERSION, 1);

    if (version_compare($dbversion, '3.3.4','<')) {

        Sql_Query("alter table {$GLOBALS['tables']['bounce']} modify data mediumblob ");

        $indexesToRecreate = array(
            'urlcache' => array(
                'urlindex' => array('value' => 'url(255)', 'unique' => false),
            ),
            'linktrack_forward' => array(
                'urlindex' => array('value' => 'url(255)', 'unique' => false),
                'urlunique' => array('value' => 'urlhash', 'unique' => true),
            ),
            'bounceregex' =>  array(
                'regex' => array('value' => 'regexhash', 'unique'=> true),
            ),
        );

        $tablesToAlter = array(
            'urlcache' => array('url'),
            'linktrack_forward' => array('url'),
            'bounceregex' => array('regex'),
        );

        //add columns for hash values

        Sql_Query("alter table {$GLOBALS['tables']['linktrack_forward']} add  urlhash char(32) ");
        Sql_Query("alter table {$GLOBALS['tables']['bounceregex']} add  regexhash char(32) ");

        // add hash values

        Sql_Query("update {$GLOBALS['tables']['linktrack_forward']} set urlhash = md5(url) where urlhash is NULL ");
        Sql_Query("update {$GLOBALS['tables']['bounceregex']} set regexhash = md5(regex) where regexhash is NULL ");



        foreach($indexesToRecreate as $table => $indexes) {


            foreach($indexes as $indexName => $settings) {

                $exists = table_index_exists($GLOBALS['tables'][$table],$indexName);
                 if ($exists) {
                     Sql_Query("drop index $indexName on {$GLOBALS['tables'][$table]} ");
                 }
            }

            $alteringOperations = $tablesToAlter[$table];
            foreach($alteringOperations as $operation) {
                Sql_Query("alter table {$GLOBALS['tables'][$table]} modify $operation varchar(2083) ");
            }

            foreach($indexes as $indexName => $settings) {
                $createStmt = '';
                if($settings['unique'] === true) {
                    $createStmt = 'create unique index';
                } else {
                    $createStmt = 'create index';
                }

                Sql_Query("$createStmt $indexName on {$GLOBALS['tables'][$table]}({$settings['value']})");
            }

        }
    }

    // Update jQuery version referenced in public page HTML stored in the database
    if (version_compare($dbversion, '3.4.1', '<')) {

        // The new filename does not hard-code the jQuery version number
        $replacement = "jquery.min.js";

        // Replace jQuery version public page footers in config table
        $oldConfigFooter = getConfig('pagefooter');
        $matches = null;

        // Find and replace all references to version-specific jQuery files
        preg_match('/jquery-3.3.1.min.js/', $oldConfigFooter, $matches);
        if ($matches[0] == "jquery-3.3.1.min.js") {
            $pattern = "jquery-3.3.1.min.js";
        } else {
            $pattern = "jquery-1.12.1.min.js";
        }

        $newConfigFooter = str_replace($pattern, $replacement, $oldConfigFooter);
        SaveConfig('pagefooter', $newConfigFooter);

        //Replace jQuery version for each subscribe page data.
        $req = Sql_Query(sprintf('select data from %s where name = "footer"', $GLOBALS['tables']['subscribepage_data']));
        $footersArray = array();
        while ($row = Sql_Fetch_Assoc($req)) {
            $footersArray[] = $row['data'];
        }

        // Find and replace all references to version-specific jQuery files
        foreach ($footersArray as $key => $value) {
            preg_match('/jquery-3.3.1.min.js/', $value, $matches);
            if ($matches[0] == "jquery-3.3.1.min.js") {
                $pattern = "jquery-3.3.1.min.js";
            } else {
                $pattern = "jquery-1.12.1.min.js";
            }
            $newFooter = str_replace($pattern, $replacement, $value);
            Sql_Query(sprintf('update %s set data = "%s" where data = "%s" ', $GLOBALS['tables']['subscribepage_data'], sql_escape($newFooter), addslashes($value)));
        }
    }
    //# remember whether we've done this, to avoid doing it every time
    //# even thought that's not such a big deal
    $isUTF8 = getConfig('UTF8converted');

    if (empty($isUTF8)) {
        $maxsize = 0;
        $req = Sql_Query('select (data_length+index_length) tablesize
      from information_schema.tables
      where table_schema="' .$GLOBALS['database_name'].'"');

        while ($row = Sql_Fetch_Assoc($req)) {
            if ($row['tablesize'] > $maxsize) {
                $maxsize = $row['tablesize'];
            }
        }
        $maxsize = (int) ($maxsize * 1.2); //# add another 20%
        $row = Sql_Fetch_Row_Query('select @@datadir');
        $dataDir = $row[0];
        $avail = disk_free_space($dataDir);

        //# convert to UTF8
        $dbname = $GLOBALS['database_name'];
        if ($maxsize < $avail && !empty($dbname)) {
            //# the conversion complains about a key length
            Sql_Query(sprintf('alter table '.$GLOBALS['tables']['user_blacklist_data'].' change column email email varchar(150) not null unique'));

            $req = Sql_Query('select * from information_schema.columns where table_schema = "'.$dbname.'" and CHARACTER_SET_NAME != "utf8"');

            $dbcolumns = array();
            $dbtables = array();
            while ($row = Sql_Fetch_Assoc($req)) {
                //# make sure to only change our own tables, in case we share with other applications
                if (in_array($row['TABLE_NAME'], array_values($GLOBALS['tables']))) {
                    $dbcolumns[] = $row;
                    $dbtables[$row['TABLE_NAME']] = $row['TABLE_NAME'];
                }
            }

            Sql_Query('use '.$dbname);

            output($GLOBALS['I18N']->get('Upgrading the database to use UTF-8, please wait').'<br/>');
            foreach ($dbtables as $dbtable) {
                set_time_limit(3600);
                output($GLOBALS['I18N']->get('Upgrading table ').' '.$dbtable.'<br/>');
                Sql_Query(sprintf('alter table %s default charset utf8', $dbtable), 1);
            }

            foreach ($dbcolumns as $dbcolumn) {
                set_time_limit(600);
                output($GLOBALS['I18N']->get('Upgrading column ').' '.$dbcolumn['COLUMN_NAME'].'<br/>');
                Sql_Query(sprintf('alter table %s change column %s %s %s default character set utf8',
                    $dbcolumn['TABLE_NAME'], $dbcolumn['COLUMN_NAME'], $dbcolumn['COLUMN_NAME'],
                    $dbcolumn['COLUMN_TYPE']), 1);
            }
            output($GLOBALS['I18N']->get('upgrade to UTF-8, done').'<br/>');
            saveConfig('UTF8converted', date('Y-m-d H:i'), 0);
        } else {
            echo '<div class="error">'.s('Database requires converting to UTF-8.').'<br/>';
            echo s('However, there is too little diskspace for this conversion').'<br/>';
            echo s('Please do a manual conversion.').' '.PageLinkButton('converttoutf8',
                    s('Run manual conversion to UTF8'));
            echo '</div>';
        }
    }

    //# 2.11.7 and up
    Sql_Query(sprintf('alter table %s add column privileges text', $tables['admin']), 1);
    Sql_Query('alter table '.$tables['list'].' add column category varchar(255) default ""', 1);
    Sql_Query('alter table '.$tables['user_attribute'].' change column value value text');
    Sql_Query('alter table '.$tables['message'].' change column textmessage textmessage longtext');
    Sql_Query('alter table '.$tables['message'].' change column message message longtext');
    Sql_Query('alter table '.$tables['messagedata'].' change column data data longtext');
    Sql_Query('alter table '.$tables['bounce'].' add index statusidx (status(20))', 1);

    //# fetch the list of TLDs, if possible
    if (defined('TLD_AUTH_LIST')) {
        refreshTlds(true);
    }

    //# changed terminology
    Sql_Query(sprintf('update %s set status = "invalid email address" where status = "invalid email"',
        $tables['usermessage']));

    //# for some reason there are some config entries marked non-editable, that should be
    include_once dirname(__FILE__).'/defaultconfig.php';
    foreach ($default_config as $configItem => $configDetails) {
        if (empty($configDetails['hidden'])) {
            Sql_Query(sprintf('update %s set editable = 1 where item = "%s"', $tables['config'], $configItem));
        } else {
            Sql_Query(sprintf('update %s set editable = 0 where item = "%s"', $tables['config'], $configItem));
        }
    }

    //# replace old header and footer with the new one
    //# but only if there are untouched from the default, which seems fairly common
    $oldPH = @file_get_contents(dirname(__FILE__).'/ui/old_public_header.inc');
    $oldPH2 = preg_replace("/\n/", "\r\n", $oldPH); //# version with \r\n instead of \n

    $oldPF = @file_get_contents(dirname(__FILE__).'/ui/old_public_footer.inc');
    $oldPF2 = preg_replace("/\n/", "\r\n", $oldPF); //# version with \r\n instead of \n
    Sql_Query(sprintf('update %s set value = "%s" where item = "pageheader" and (value = "%s" or value = "%s")',
        $tables['config'], sql_escape($defaultheader), addslashes($oldPH), addslashes($oldPH2)));
    Sql_Query(sprintf('update %s set value = "%s" where item = "pagefooter" and (value = "%s" or value = "%s")',
        $tables['config'], sql_escape($defaultfooter), addslashes($oldPF), addslashes($oldPF2)));

    //# and the same for subscribe pages
    Sql_Query(sprintf('update %s set data = "%s" where name = "header" and (data = "%s" or data = "%s")',
        $tables['subscribepage_data'], sql_escape($defaultheader), addslashes($oldPH), addslashes($oldPH2)));
    Sql_Query(sprintf('update %s set data = "%s" where name = "footer" and (data = "%s" or data = "%s")',
        $tables['subscribepage_data'], sql_escape($defaultfooter), addslashes($oldPF), addslashes($oldPF2)));

    if (is_file(dirname(__FILE__).'/ui/'.$GLOBALS['ui'].'/old_public_header.inc')) {
        $oldPH = file_get_contents(dirname(__FILE__).'/ui/'.$GLOBALS['ui'].'/old_public_header.inc');
        $oldPH2 = preg_replace("/\n/", "\r\n", $oldPH); //# version with \r\n instead of \n
        $oldPF = file_get_contents(dirname(__FILE__).'/ui/'.$GLOBALS['ui'].'/old_public_footer.inc');
        $oldPF2 = preg_replace("/\n/", "\r\n", $oldPF); //# version with \r\n instead of \n
        $currentPH = getConfig('pageheader');
        $currentPF = getConfig('pagefooter');

        if (($currentPH == $oldPH2 || $currentPH."\r\n" == $oldPH2) && !empty($defaultheader)) {
            SaveConfig('pageheader', $defaultheader, 1);
            Sql_Query(sprintf('update %s set data = "%s" where name = "header" and data = "%s"',
                $tables['subscribepage_data'], sql_escape($defaultheader), addslashes($currentPH)));
            //# only try to change footer when header has changed
            if ($currentPF == $oldPF2 && !empty($defaultfooter)) {
                SaveConfig('pagefooter', $defaultfooter, 1);
                Sql_Query(sprintf('update %s set data = "%s" where name = "footer" and data = "%s"',
                    $tables['subscribepage_data'], sql_escape($defaultfooter), addslashes($currentPF)));
            }
        }
    }

    //# #17328 - remove list categories with quotes
    Sql_Query(sprintf("update %s set category = replace(category,\"\\\\'\",\" \")", $tables['list']));

    //# add uuid columns
    if (!Sql_Table_Column_Exists($GLOBALS['tables']['message'], 'uuid')) {
        Sql_Query(sprintf('alter table %s add column uuid varchar(36) default ""',
            $GLOBALS['tables']['message']));
        Sql_Query(sprintf('alter table %s add index uuididx (uuid)',
            $GLOBALS['tables']['message']));
    }
    if (!Sql_Table_Column_Exists($GLOBALS['tables']['linktrack_forward'], 'uuid')) {
        Sql_Query(sprintf('alter table %s add column uuid varchar(36) default ""',
            $GLOBALS['tables']['linktrack_forward']));
        Sql_Query(sprintf('alter table %s add index uuididx (uuid)',
            $GLOBALS['tables']['linktrack_forward']));
    }
    if (!Sql_Table_Column_Exists($GLOBALS['tables']['user'], 'uuid')) {
        Sql_Query(sprintf('alter table %s add column uuid varchar(36) default ""',
            $GLOBALS['tables']['user']));
        Sql_Query(sprintf('alter table %s add index uuididx (uuid)',
            $GLOBALS['tables']['user']));
    }
    // add uuids to those that do not have it
    $req = Sql_Query(sprintf('select id from %s where uuid = ""', $GLOBALS['tables']['user']));
    $numS = Sql_Affected_Rows();
    if ($numS > 500 && empty($GLOBALS['commandline'])) {

        // with a lot of subscrirbers this can take a very long time, causing a blank page for a long time (I had one system where it took almost an hour)
        //.This really needs to be loaded in Async mode, therefore I'm removing this for now
        // it is not strictly necessary to do this here, because processqueue does it as well.
        // that does mean that the first process queue may take a while.

        //   output(s('Giving a UUID to your subscribers and campaigns. If you have a lot of them, this may take a while.'));
        //   output(s('If the page times out, you can reload. Or otherwise try to run the upgrade from commandline instead.').' '.resourceLink('https://resources.phplist.com/system/commandline', s('Documentation how to set up phpList commandline')));
    } else {
        output(s('Giving a UUID to your subscribers and campaigns. If you have a lot of them, this may take a while.'));
        output(s('If the page times out, you can reload. Or otherwise try to run the upgrade from commandline instead.').' '.resourceLink('https://resources.phplist.com/system/commandline', s('Documentation how to set up phpList commandline')));
        while ($row = Sql_Fetch_Row($req)) {
            Sql_Query(sprintf('update %s set uuid = "%s" where id = %d', $GLOBALS['tables']['user'], (string)uuid::generate(4), $row[0]));
        }
    }

    // let's hope there aren't too many campaigns or links, otherwise the same timeout would apply.

    $req = Sql_Query(sprintf('select id from %s where uuid = ""', $GLOBALS['tables']['message']));
    while ($row = Sql_Fetch_Row($req)) {
        Sql_Query(sprintf('update %s set uuid = "%s" where id = %d', $GLOBALS['tables']['message'], (string) uuid::generate(4), $row[0]));
    }
    $req = Sql_Query(sprintf('select id from %s where uuid = ""', $GLOBALS['tables']['linktrack_forward']));
    while ($row = Sql_Fetch_Row($req)) {
        Sql_Query(sprintf('update %s set uuid = "%s" where id = %d', $GLOBALS['tables']['linktrack_forward'], (string) uuid::generate(4), $row[0]));
    }

    if (!Sql_Table_Exists($tables['admin_password_request'])) {
        createTable('admin_password_request');
    }

    if (!Sql_Table_exists($GLOBALS['tables']['user_message_view'])) {
        cl_output(s('Creating new table "user_message_view"'));
        createTable('user_message_view');
    }

    if (version_compare($dbversion, '3.3.3','<')) {
        // add a draft campaign for invite plugin
        addInviteCampaign();
    }

    if (version_compare($dbversion, '3.3.4','<')) {
        Sql_Query("alter table {$GLOBALS['tables']['bounce']} modify data mediumblob ");
    }

    if (version_compare($dbversion, '3.4.0-RC1','<')) {
        SaveConfig('secret', bin2hex(random_bytes(20)));
    }

    //# longblobs are better at mixing character encoding. We don't know the encoding of anything we may want to store in cache
    //# before converting, it's quickest to clear the cache
    clearPageCache();
    Sql_Query(sprintf('alter table %s change column content content longblob', $tables['urlcache']));

    //# unlock the upgrade process
    Sql_Query(sprintf('delete from %s where item = "in-upgrade-to"', $tables['config']));
    // mark the database to be our current version
    if ($success) {
        SaveConfig('version', VERSION, 0);
        if (defined('RELEASEDATE')) {
            SaveConfig('releaseDBversion', RELEASEDATE, 0);
        }
        // mark now to be the last time we checked for an update
        SaveConfig('lastcheckupdate', date('m/d/Y h:i:s', time()), 0, true);
        //# also clear any possible value for "updateavailable"
        Sql_Query(sprintf('delete from %s where item = "updateavailable"', $tables['config']));

        Info(s('Success'), 1);

        upgradePlugins(array_keys($GLOBALS['plugins']));

        echo subscribeToAnnouncementsForm();

//#  check for old click track data
        $num = Sql_Fetch_Row_Query(sprintf('select count(*) from %s', $GLOBALS['tables']['linktrack']));
        if ($num[0] > 0) {
            echo '<p class="information">'.$GLOBALS['I18N']->get('The clicktracking system has changed').'</p>';
            printf($GLOBALS['I18N']->get('You have %s entries in the old statistics table'), $num[0]).' ';
            echo ' '.PageLinkButton('convertstats', $GLOBALS['I18N']->get('Convert Old data to new'));
        }

        if ($GLOBALS['commandline']) {
            output($GLOBALS['I18N']->get('Upgrade successful'));
        }
    } else {
        Error('An error occurred while upgrading your database');
        if ($GLOBALS['commandline']) {
            output($GLOBALS['I18N']->get('Upgrade failed'));
        }
    }
    clearMaintenanceMode();
} else {
    echo '<p>'.s('Your database requires upgrading, please make sure to create a backup of your database first.').'</p>';
    echo '<p>'.s('If you have a large database, make sure you have sufficient diskspace available for upgrade.').'</p>';
    echo '<p>'.s('When you are ready click %s Depending on the size of your database, this may take quite a while. Please make sure not to interrupt the process, once it started.',
            PageLinkButton('upgrade&doit=yes', s('Upgrade'))).'</p>';
}
