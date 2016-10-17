<?php

require_once dirname(__FILE__) . '/accesscheck.php';

if (!$GLOBALS['commandline']) {
    @ob_end_flush();
} else {
    @ob_end_clean();
    print ClineSignature();
    ## when on cl, doit immediately
    $_GET['doit'] = 'yes';
    ob_start();
}

function output($message)
{
    if ($GLOBALS['commandline']) {
        @ob_end_clean();
        print strip_tags($message) . "\n";
        ob_start();
    } else {
        print $message;
        # output some stuff to make sure it's not buffered in the browser, hmm, would be nice to find a better way for this
        for ($i = 0; $i < 10000; ++$i) {
            print '  ' . "\n";
        }
        flush();
        @ob_end_flush();
    }
    flush();
}

$dbversion = getConfig('version');
if (!$dbversion) {
    $dbversion = 'Older than 1.4.1';
}
output('<p class="information">' . $GLOBALS['I18N']->get('Your database version') . ': ' . $dbversion . '</p>');

if ($GLOBALS['database_module'] == 'mysql.inc') {
    print Warn(s('Please edit your config file and change "mysql.inc" to "mysqli.inc" to avoid future PHP incompatibility') .
        resourceLink('http://resources.phplist.com/system/mysql-mysqli-update')
    );
}

if ($dbversion == VERSION) {
    output($GLOBALS['I18N']->get('Your database is already the correct version, there is no need to upgrade'));

    print '<p>' . PageLinkAjax('upgrade&update=tlds', s('update Top Level Domains'), '', 'button') . '</p>';

    print subscribeToAnnouncementsForm();
} elseif (isset($_GET['doit']) && $_GET['doit'] == 'yes') {
    $success = 1;
    # once we are off, this should not be interrupted
    ignore_user_abort(1);
    # rename tables if we are using the prefix
    include dirname(__FILE__) . '/structure.php';
    while (list($table, $value) = each($DBstruct)) {
        set_time_limit(500);
        if (isset($table_prefix)) {
            if (Sql_Table_exists($table) && !Sql_Table_Exists($tables[$table])) {
                Sql_Verbose_Query("alter table $table rename $tables[$table]", 1);
            }
        }
    }
    @ob_end_flush();
    @ob_start();

    output('<p class="information">' . $GLOBALS['I18N']->get('Please wait, upgrading your database, do not interrupt') . '</p>');

    flush();

    if (preg_match('/(.*?)-/', $dbversion, $regs)) {
        $dbversion = $regs[1];
    }
    switch ($dbversion) {
        case '1.4.1':
            # nothing changed,
        case '1.4.2':
            # nothing changed,
        case 'dev':
        case '1.4.3':
            foreach (array('admin', 'adminattribute', 'admin_attribute', 'task', 'admin_task') as $table) {
                if (!Sql_Table_Exists($table)) {
                    Sql_Create_Table($tables[$table], $DBstruct[$table]);
                    if ($table == 'admin') {
                        # create a default admin
                        Sql_Query(sprintf('insert into %s values(0,"%s","%s","%s",now(),now(),"%s","%s",now(),%d,0)',
                            $tables['admin'], 'admin', 'admin', '', $adminname, 'phplist', 1));
                    } elseif ($table == 'task') {
                        while (list($type, $pages) = each($system_pages)) {
                            foreach ($pages as $page) {
                                Sql_Query(sprintf('insert into %s (page,type) values("%s","%s")',
                                    $tables['task'], $page, $type));
                            }
                        }
                    }
                }
            }
            Sql_Query("alter table {$tables['list']} add column owner integer");
            Sql_Query("alter table {$tables['message']} change column status status enum('submitted','inprocess','sent','cancelled','prepared')");
            Sql_Query("alter table {$tables['template']} change column template template longblob");
            # previous versions did not cleanup properly, fix that here
            $req = Sql_Query("select userid from {$tables['user_attribute']} left join {$tables['user']} on {$tables['user_attribute']}.userid = {$tables['user']}.id where {$tables['user']}.id IS NULL");
            while ($row = Sql_Fetch_Row($req)) {
                Sql_query('delete from ' . $tables['user_attribute'] . ' where userid = ' . $row[0]);
            }
            $req = Sql_Query("select user from {$tables['user_message_bounce']} left join {$tables['user']} on {$tables['user_message_bounce']}.user = {$tables['user']}.id where {$tables['user']}.id IS NULL");
            while ($row = Sql_Fetch_Row($req)) {
                Sql_query('delete from ' . $tables['user_message_bounce'] . ' where user = ' . $row[0]);
            }
            $req = Sql_Query("select userid from {$tables['usermessage']} left join {$tables['user']} on {$tables['usermessage']}.userid = {$tables['user']}.id where {$tables['user']}.id IS NULL");
            while ($row = Sql_Fetch_Row($req)) {
                Sql_query('delete from ' . $tables['usermessage'] . ' where userid = ' . $row[0]);
            }

            $success = 1;
        case '1.5.0':
            # nothing changed
        case '1.5.1':
            # nothing changed
        case '1.6.0':
        case '1.6.1': # not released
            # nothing changed
        case '1.6.2':
            # something we should have done ages ago. make checkboxes save "on" value in user_attribute
            $req = Sql_Query("select * from {$tables['attribute']} where type = \"checkbox\"");
            while ($row = Sql_Fetch_Array($req)) {
                $req2 = Sql_Query("select * from $table_prefix" . "listattr_$row[tablename]");
                while ($row2 = Sql_Fetch_array($req2)) {
                    if ($row2['name'] == 'Checked') {
                        Sql_Query(sprintf('update %s set value = "on" where attributeid = %d and value = %d',
                            $tables['user_attribute'], $row['id'], $row2['id']));
                    }
                }
                Sql_Query(sprintf('update %s set value = "" where attributeid = %d and value != "on"',
                    $tables['user_attribute'], $row['id']));
                Sql_Query("drop table $table_prefix" . 'listattr_' . $row['tablename']);
            }
            Sql_Query("insert into {$tables['task']} (page,type) values(\"export\",\"user\")");
        case '1.6.3':
        case '1.6.4':
            Sql_Query("alter table {$tables['user']} add column bouncecount integer default 0");
            Sql_Query("alter table {$tables['message']} add column bouncecount integer default 0");
            # we actually never used these tables, so we can just as well drop and recreate them
            Sql_Query("drop table if exists {$tables['bounce']}");
            Sql_Query("drop table if exists {$tables['user_message_bounce']}");
            Sql_Query(sprintf('create table %s (
        id integer not null primary key auto_increment,
        date datetime,
        header text,
        data blob,
        status varchar(255),
        comment text)', $tables['bounce']));
            Sql_Query(sprintf('create table %s (
        id integer not null primary key auto_increment,
        user integer not null,
        message integer not null,
        bounce integer not null,
        time timestamp,
        index (user,message,bounce))',
                $tables['user_message_bounce']));
            Sql_Query("insert into {$tables['task']} (page,type) values(\"bounce\",\"system\")");
            Sql_Query("insert into {$tables['task']} (page,type) values(\"bounces\",\"system\")");
            Sql_Query("insert into {$tables['task']} (page,type) values(\"processbounces\",\"system\")");
        case '1.7.0':
        case '1.7.1':
        case '1.8.0':
            Sql_Query("insert into {$tables['task']} (page,type) values(\"spage\",\"system\")");
            Sql_Query("insert into {$tables['task']} (page,type) values(\"spageedit\",\"system\")");
            Sql_Query("alter table {$tables['user']} add column subscribepage integer default 0");
            Sql_Create_Table($tables['subscribepage'], $DBstruct['subscribepage']);
            Sql_Create_Table($tables['subscribepage_data'], $DBstruct['subscribepage_data']);
        case '1.9.0':
        case '1.9.1':
        case '1.9.2':
            # no changes
        case '1.9.3':
            # add some indexes to speed things up
            Sql_Query("alter table {$tables['bounce']} add index dateindex (date)");
            Sql_Create_Table($tables['eventlog'], $DBstruct['eventlog']);
            Sql_Query("alter table {$tables['sendprocess']} add column page varchar(100)");
            Sql_Query("alter table {$tables['message']} add column sendstart datetime");
            # some cleaning up of data:
            $req = Sql_Query("select {$tables['usermessage']}.userid
        from {$tables['usermessage']} left join {$tables['user']} on {$tables['usermessage']}.userid = {$tables['user']}.id
        where {$tables['user']}.id IS NULL group by {$tables['usermessage']}.userid");
            while ($row = Sql_Fetch_Row($req)) {
                Sql_Query("delete from {$tables['usermessage']} where userid = $row[0]");
            }
            $req = Sql_Query("select {$tables['user_attribute']}.userid
        from {$tables['user_attribute']} left join {$tables['user']} on {$tables['user_attribute']}.userid = {$tables['user']}.id
        where {$tables['user']}.id IS NULL group by {$tables['user_attribute']}.userid");
            while ($row = Sql_Fetch_Row($req)) {
                Sql_Query("delete from {$tables['user_attribute']} where userid = $row[0]");
            }
            $req = Sql_Query("select {$tables['listuser']}.userid
        from {$tables['listuser']} left join {$tables['user']} on {$tables['listuser']}.userid = {$tables['user']}.id
        where {$tables['user']}.id IS NULL group by {$tables['listuser']}.userid");
            while ($row = Sql_Fetch_Row($req)) {
                Sql_Query("delete from {$tables['listuser']} where userid = $row[0]");
            }
            $req = Sql_Query("select {$tables['usermessage']}.userid
        from {$tables['usermessage']} left join {$tables['user']} on {$tables['usermessage']}.userid = {$tables['user']}.id
        where {$tables['user']}.id IS NULL group by {$tables['usermessage']}.userid");
            while ($row = Sql_Fetch_Row($req)) {
                Sql_Query("delete from {$tables['usermessage']} where userid = $row[0]");
            }
            $req = Sql_Query("select {$tables['user_message_bounce']}.user
        from {$tables['user_message_bounce']} left join {$tables['user']} on {$tables['user_message_bounce']}.user = {$tables['user']}.id
        where {$tables['user']}.id IS NULL group by {$tables['user_message_bounce']}.user");
            while ($row = Sql_Fetch_Row($req)) {
                Sql_Query("delete from {$tables['user_message_bounce']} where user = $row[0]");
            }
        case '2.1.0':
        case '2.1.1':
            # oops deleted tables columns that should not have been deleted:
            if (!Sql_Table_Column_Exists($tables['message'], 'tofield')) {
                Sql_Query("alter table {$tables['message']} add column tofield varchar(255)");
            }
            if (!Sql_Table_Column_Exists($tables['message'], 'replyto')) {
                Sql_Query("alter table {$tables['message']} add column replyto varchar(255)");
            }
        case '2.1.2':
        case '2.1.3':
        case '2.1.4':
            Sql_Query("alter table {$tables['message']} change column asboth astextandhtml integer default 0");
            Sql_Query("alter table {$tables['message']} add column aspdf integer default 0");
            Sql_Query("alter table {$tables['message']} add column astextandpdf integer default 0");
            Sql_Query("alter table {$tables['message']} add column rsstemplate varchar(100)");
            Sql_Query("alter table {$tables['list']} add column rssfeed varchar(255)");
            Sql_Query("alter table {$tables['user']} add column rssfrequency varchar(100)");
            Sql_Create_Table($tables['message_attachment'], $DBstruct['message_attachment']);
            Sql_Create_Table($tables['attachment'], $DBstruct['attachment']);
            Sql_Create_Table($tables['rssitem'], $DBstruct['rssitem']);
            Sql_Create_Table($tables['rssitem_data'], $DBstruct['rssitem_data']);
            Sql_Create_Table($tables['user_rss'], $DBstruct['user_rss']);
            Sql_Create_Table($tables['rssitem_user'], $DBstruct['rssitem_user']);
        case '2.2.0':
        case '2.2.1':
            Sql_Query("alter table {$tables['user']} add column password varchar(255)");
            Sql_Query("alter table {$tables['user']} add column passwordchanged datetime");
            Sql_Query("alter table {$tables['user']} add column disabled tinyint default 0");
            Sql_Query("alter table {$tables['user']} add column extradata text");
            Sql_Query("alter table {$tables['message']} add column owner integer");
        case '2.3.0':
        case '2.3.1':
        case '2.3.2':
        case '2.3.3':
            Sql_Create_Table($tables['listrss'], $DBstruct['listrss']);
        case '2.3.4':
        case '2.4.0':
            Sql_Query("insert into {$tables['task']} (page,type) values(\"import3\",\"user\")");
            Sql_Query("insert into {$tables['task']} (page,type) values(\"import4\",\"user\")");
        case '2.5.0':
        case '2.5.1':
        case '2.5.2':
            Sql_Query("alter table {$tables['subscribepage']} add column owner integer");
            Sql_Query("alter ignore table {$tables['task']} add unique (page)");
        case '2.5.3':
        case '2.5.4':
            Sql_Query("alter table {$tables['user']} add column foreignkey varchar(100)");
            Sql_Query("alter table {$tables['user']} add index fkey (foreignkey)");
        case '2.5.5':
        case '2.5.6':
        case '2.5.7':
        case '2.5.8':
            # some very odd value managed to sneak in
            $cbgroups = Sql_Query("select id from {$tables['attribute']} where type = \"checkboxgroup\"");
            while ($row = Sql_Fetch_Row($cbgroups)) {
                Sql_Query("update {$tables['user_attribute']} set value = \"\" where attributeid = $row[0] and value=\"Empty\"");
            }
        case '2.6.0':
        case '2.6.1':
        case '2.6.2':
        case '2.6.3':
        case '2.6.4':
        case '2.6.5':
            Sql_Verbose_Query("alter table {$tables['message']} add column embargo datetime");
            Sql_Verbose_Query("alter table {$tables['message']} add column repeat integer default 0");
            Sql_Verbose_Query("alter table {$tables['message']} add column repeatuntil datetime");
            # make sure that current queued messages are sent
            Sql_Verbose_Query("update {$tables['message']} set embargo = now() where status = \"submitted\"");
            Sql_Query("alter table {$tables['message']} change column status status enum('submitted','inprocess','sent','cancelled','prepared','draft')");
        case '2.6.6':
        case '2.7.0':
        case '2.7.1':
        case '2.7.2':
            Sql_Create_Table($tables['user_history'], $DBstruct['user_history']);
        case '2.8.0':
            Sql_Query("alter table {$tables['message']} add column textmessage text");
        case '2.8.1':
        case '2.8.2':
        case '2.8.3':
        case '2.8.4':
        case '2.8.5':
        case '2.8.6':
            Sql_Query("alter table {$tables['user']} add index index_uniqid (uniqid)");
        case 'whatever versions we will get later':
            #Sql_Query("alter table table that altered");
            break;
        default:
            # an unknown version, so we do a generic upgrade, if the version is older than 1.4.1
            if ($dbversion > '1.4.1') {
                break;
            }
            Error('Sorry, your version is too old to safely upgrade');
            $success = 0;
            break;
    }

    # at 2.8.x we started to split into stable (2.8) and unstable (2.9)
    # so upgrading is now mixed between major.minor versions
    list($major, $minor, $sub) = explode('.', $dbversion);
    switch ($major) {
        case '2':
            if ($minor < 9 || ($minor == 9 && $sub == 0)) {
                Sql_Create_Table($tables['user_blacklist'], $DBstruct['user_blacklist']);
                Sql_Create_Table($tables['user_blacklist_data'], $DBstruct['user_blacklist_data']);
                Sql_Query("alter table {$tables['user']} add column blacklisted tinyint default 0");
                Sql_Query("alter table {$tables['message']} change column repeat repeatinterval integer default 0");
                set_time_limit(6000);
                # this one can take a long time
                Sql_Query("alter table {$tables['usermessage']} change column entered entered datetime, add column status varchar(255)");
                Sql_Query("update {$tables['usermessage']} set status =\"sent\"");
            }
            if ($minor < 9 || ($minor == 9 && $sub < 2)) {
                Sql_Create_Table($tables['messagedata'], $DBstruct['messagedata']);
            }
            if ($minor < 9 || ($minor == 9 && $sub < 2)) {
                Sql_Query("alter table {$tables['user_attribute']} add index userindex (userid)");
                Sql_Query("alter table {$tables['user_attribute']} add index attindex (attributeid)");
            }
            if ($minor < 9 || ($minor == 9 && $sub < 3)) {
                # this can take quite a while if there are a lot of users and or messages
                set_time_limit(60000);
                Sql_Query("alter table {$tables['usermessage']} add index userindex (userid)");
                Sql_Query("alter table {$tables['usermessage']} add index messageindex (messageid)");
                Sql_Query("alter table {$tables['usermessage']} add index enteredindex (entered)");
                Sql_Create_Table($tables['urlcache'], $DBstruct['urlcache']);
            }
            if ($minor < 9 || ($minor == 9 && $sub <= 4)) {
                Sql_Create_Table($tables['linktrack'], $DBstruct['linktrack']);
                Sql_Create_Table($tables['linktrack_userclick'], $DBstruct['linktrack_userclick']);
                SaveConfig('xormask', md5(uniqid(rand(), true)), 0);
            }
            if ($minor < 9 || ($minor == 9 && $sub < 5)) {
                Sql_Create_Table($tables['user_message_forward'], $DBstruct['user_message_forward']);
                Sql_Query("alter table {$tables['user_attribute']} add index userattid (attributeid,userid)");
                Sql_Query("alter table {$tables['user_attribute']} add index attuserid (userid,attributeid)");
                Sql_Query("alter table {$tables['message']} change column status status varchar(255)");
                Sql_Create_Table($tables['userstats'], $DBstruct['userstats']);
                Sql_Create_Table($tables['bounceregex'], $DBstruct['bounceregex']);
                Sql_Create_Table($tables['bounceregex_bounce'], $DBstruct['bounceregex_bounce']);
            }
            if ($minor < 11 || ($minor == 11 && $sub < 2)) {
                Sql_Create_Table($tables['linktrack_forward'], $DBstruct['linktrack_forward']);
                Sql_Create_Table($tables['linktrack_ml'], $DBstruct['linktrack_ml']);
                Sql_Create_Table($tables['linktrack_uml_click'], $DBstruct['linktrack_uml_click']);
            }
            if ($minor < 11 || ($minor == 11 && $sub < 3)) {
                Sql_Query(sprintf('alter table %s add column optedin tinyint default 0', $tables['user']));
            }
            if ($minor < 11 || ($minor == 11 && $sub < 5)) {
                Sql_Query(sprintf('alter table %s add column category varchar(255) default ""', $tables['list']));
                Sql_Query(sprintf('alter table %s add column requeueinterval integer default 0', $tables['message']));
                Sql_Query(sprintf('alter table %s add column requeueuntil datetime', $tables['message']));
            }
            if ($minor < 11 || ($minor == 11 && $sub < 7)) {
                Sql_Create_Table($tables['admin_password_request'], $DBstruct['admin_password_request'], 1);
                Sql_Create_Table($tables['admintoken'], $DBstruct['admintoken'], 1);
                Sql_Create_Table($tables['i18n'], $DBstruct['i18n'], 1);
                unset($_SESSION['hasI18Ntable']);
                $req = Sql_Query(sprintf('select loginname,password from %s where length(password) < %d',
                    $GLOBALS['tables']['admin'], $GLOBALS['hash_length']));
                while ($row = Sql_Fetch_Assoc($req)) {
                    $encryptedPassDB = hash(ENCRYPTION_ALGO, $row['password']);
                    $query = sprintf('update %s set password = "%s" where loginname = "%s"',
                        $GLOBALS['tables']['admin'], $encryptedPassDB, $row['loginname']);
                    Sql_Query($query);
                }
            }
            break;
    }

    ## add index on bounces, but ignore the error
    Sql_Query("create index statusindex on {$tables['user_attribute']} (status(10))", 1);
    Sql_Query("create index message_lookup using btree on {$tables['user_message_bounce']} (message)", 1);

    ## add index to i18n to avoid duplicate translations
    ## alter ignore doesn't seem to work on InnoDB: http://bugs.mysql.com/bug.php?id=40344
    # convert to MyIsam first @@Mysql Specific code !
    Sql_Query('alter table ' . $tables['i18n'] . ' engine MyIsam', 1);
    Sql_Query('alter ignore table ' . $tables['i18n'] . ' add unique lanorigunq (lan(10),original(200))', 1);

    ## mantis issue 9001, make sure that the "repeat" column in the messages table is renamed to repeatinterval
    # to avoid a name clash with Mysql 5.
    # problem is that this statement will fail if the DB is already running Mysql 5
    if (Sql_Table_Column_Exists($GLOBALS['tables']['message'], 'repeat')) {
        Sql_Query(sprintf('alter ignore table %s change column repeat repeatinterval integer default 0',
            $GLOBALS['tables']['message']));
    }

    # check whether it worked and otherwise throw an error to say it needs to be done manually
    if (Sql_Table_Column_Exists($GLOBALS['tables']['message'], 'repeat')) {
        print 'Error, unable to rename column "repeat" in the table ' . $GLOBALS['tables']['message'] . ' to be "repeatinterval"<br/>
      Please do this manually, refer to http://mantis.phplist.com/view.php?id=9001 for more information';
    }

    # fix the new powered by image for the templates
    Sql_Query(sprintf('update %s set data = "%s",width=70,height=30 where filename = "powerphplist.png"',
        $tables['templateimage'], $newpoweredimage));

    # update the system pages
    include_once dirname(__FILE__) . '/defaultconfig.php';

    ## remember whether we've done this, to avoid doing it every time
    ## even thought that's not such a big deal
    $isUTF8 = getConfig('UTF8converted');

    if (empty($isUTF8)) {
        $maxsize = 0;
        $req = Sql_Query('select (data_length+index_length) tablesize 
      from information_schema.tables
      where table_schema="' . $GLOBALS['database_name'] . '"');

        while ($row = Sql_Fetch_Assoc($req)) {
            if ($row['tablesize'] > $maxsize) {
                $maxsize = $row['tablesize'];
            }
        }
        $maxsize = (int)$maxsize;
        $avail = disk_free_space('/'); ## we have no idea where MySql stores the data, so this is only a crude check and warning.
        $maxsize = (int)($maxsize * 1.2); ## add another 20%

        ## convert to UTF8
        $dbname = $GLOBALS['database_name'];
        if ($maxsize < $avail && !empty($dbname)) {
            ## the conversion complains about a key length
            Sql_Query(sprintf('alter table ' . $GLOBALS['tables']['user_blacklist_data'] . ' change column email email varchar(150) not null unique'));

            $req = Sql_Query('select * from information_schema.columns where table_schema = "' . $dbname . '" and CHARACTER_SET_NAME != "utf8"');

            $dbcolumns = array();
            $dbtables = array();
            while ($row = Sql_Fetch_Assoc($req)) {
                ## make sure to only change our own tables, in case we share with other applications
                if (in_array($row['TABLE_NAME'], array_values($GLOBALS['tables']))) {
                    $dbcolumns[] = $row;
                    $dbtables[$row['TABLE_NAME']] = $row['TABLE_NAME'];
                }
            }

            Sql_Query('use ' . $dbname);

            output($GLOBALS['I18N']->get('Upgrading the database to use UTF-8, please wait') . '<br/>');
            foreach ($dbtables as $dbtable) {
                set_time_limit(3600);
                output($GLOBALS['I18N']->get('Upgrading table ') . ' ' . $dbtable . '<br/>');
                Sql_Query(sprintf('alter table %s default charset utf8', $dbtable), 1);
            }

            foreach ($dbcolumns as $dbcolumn) {
                set_time_limit(600);
                output($GLOBALS['I18N']->get('Upgrading column ') . ' ' . $dbcolumn['COLUMN_NAME'] . '<br/>');
                Sql_Query(sprintf('alter table %s change column %s %s %s default character set utf8',
                    $dbcolumn['TABLE_NAME'], $dbcolumn['COLUMN_NAME'], $dbcolumn['COLUMN_NAME'],
                    $dbcolumn['COLUMN_TYPE']), 1);
            }
            output($GLOBALS['I18N']->get('upgrade to UTF-8, done') . '<br/>');
            saveConfig('UTF8converted', date('Y-m-d H:i'), 0);
        } else {
            print '<div class="error">' . s('Database requires converting to UTF-8.') . '<br/>';
            print s('However, there is too little diskspace for this conversion') . '<br/>';
            print s('Please do a manual conversion.') . ' ' . PageLinkButton('converttoutf8',
                    s('Run manual conversion to UTF8'));
            print '</div>';
        }
    }

    ## 2.11.7 and up
    Sql_Query(sprintf('alter table %s add column privileges text', $tables['admin']), 1);
    Sql_Query('alter table ' . $tables['list'] . ' add column category varchar(255) default ""', 1);
    Sql_Query('alter table ' . $tables['user_attribute'] . ' change column value value text');
    Sql_Query('alter table ' . $tables['message'] . ' change column textmessage textmessage longtext');
    Sql_Query('alter table ' . $tables['message'] . ' change column message message longtext');
    Sql_Query('alter table ' . $tables['messagedata'] . ' change column data data longtext');
    Sql_Query('alter table ' . $tables['bounce'] . ' add index statusidx (status(20))', 1);

    ## fetch the list of TLDs, if possible
    if (defined('TLD_AUTH_LIST')) {
        refreshTlds(true);
    }

    ## changed terminology
    Sql_Query(sprintf('update %s set status = "invalid email address" where status = "invalid email"',
        $tables['usermessage']));

    ## for some reason there are some config entries marked non-editable, that should be
    include_once dirname(__FILE__) . '/defaultconfig.php';
    foreach ($default_config as $configItem => $configDetails) {
        if (empty($configDetails['hidden'])) {
            Sql_Query(sprintf('update %s set editable = 1 where item = "%s"', $tables['config'], $configItem));
        } else {
            Sql_Query(sprintf('update %s set editable = 0 where item = "%s"', $tables['config'], $configItem));
        }
    }

    ## replace old header and footer with the new one
    ## but only if there are untouched from the default, which seems fairly common
    $oldPH = @file_get_contents(dirname(__FILE__) . '/ui/old_public_header.inc');
    $oldPH2 = preg_replace("/\n/", "\r\n", $oldPH); ## version with \r\n instead of \n

    $oldPF = @file_get_contents(dirname(__FILE__) . '/ui/old_public_footer.inc');
    $oldPF2 = preg_replace("/\n/", "\r\n", $oldPF); ## version with \r\n instead of \n
    Sql_Query(sprintf('update %s set value = "%s" where item = "pageheader" and (value = "%s" or value = "%s")',
        $tables['config'], sql_escape($defaultheader), addslashes($oldPH), addslashes($oldPH2)));
    Sql_Query(sprintf('update %s set value = "%s" where item = "pagefooter" and (value = "%s" or value = "%s")',
        $tables['config'], sql_escape($defaultfooter), addslashes($oldPF), addslashes($oldPF2)));

    ## and the same for subscribe pages
    Sql_Query(sprintf('update %s set data = "%s" where name = "header" and (data = "%s" or data = "%s")',
        $tables['subscribepage_data'], sql_escape($defaultheader), addslashes($oldPH), addslashes($oldPH2)));
    Sql_Query(sprintf('update %s set data = "%s" where name = "footer" and (data = "%s" or data = "%s")',
        $tables['subscribepage_data'], sql_escape($defaultfooter), addslashes($oldPF), addslashes($oldPF2)));

    if (is_file(dirname(__FILE__) . '/ui/' . $GLOBALS['ui'] . '/old_public_header.inc')) {
        $oldPH = file_get_contents(dirname(__FILE__) . '/ui/' . $GLOBALS['ui'] . '/old_public_header.inc');
        $oldPH2 = preg_replace("/\n/", "\r\n", $oldPH); ## version with \r\n instead of \n
        $oldPF = file_get_contents(dirname(__FILE__) . '/ui/' . $GLOBALS['ui'] . '/old_public_footer.inc');
        $oldPF2 = preg_replace("/\n/", "\r\n", $oldPF); ## version with \r\n instead of \n
        $currentPH = getConfig('pageheader');
        $currentPF = getConfig('pagefooter');

        if (($currentPH == $oldPH2 || $currentPH . "\r\n" == $oldPH2) && !empty($defaultheader)) {
            SaveConfig('pageheader', $defaultheader, 1);
            Sql_Query(sprintf('update %s set data = "%s" where name = "header" and data = "%s"',
                $tables['subscribepage_data'], sql_escape($defaultheader), addslashes($currentPH)));
            ## only try to change footer when header has changed
            if ($currentPF == $oldPF2 && !empty($defaultfooter)) {
                SaveConfig('pagefooter', $defaultfooter, 1);
                Sql_Query(sprintf('update %s set data = "%s" where name = "footer" and data = "%s"',
                    $tables['subscribepage_data'], sql_escape($defaultfooter), addslashes($currentPF)));
            }
        }
    }

    ## #17328 - remove list categories with quotes
    Sql_Query(sprintf("update %s set category = replace(category,\"\\\\'\",\" \")", $tables['list']));


    ## add uuid columns
    if (!Sql_Table_Column_Exists($GLOBALS['tables']['message'], 'uuid')) {
        Sql_Query(sprintf('alter ignore table %s add column uuid varchar(36) default ""',
            $GLOBALS['tables']['message']));
    }
    if (!Sql_Table_Column_Exists($GLOBALS['tables']['linktrack_forward'], 'uuid')) {
        Sql_Query(sprintf('alter ignore table %s add column uuid varchar(36) default ""',
            $GLOBALS['tables']['linktrack_forward']));
    }
    if (!Sql_Table_Column_Exists($GLOBALS['tables']['user'], 'uuid')) {
        Sql_Query(sprintf('alter ignore table %s add column uuid varchar(36) default ""',
            $GLOBALS['tables']['user']));
    }
    # add uuids to those that do not have it
    $req = Sql_Query(sprintf('select id from %s where uuid = ""',$GLOBALS['tables']['user']));
    while ($row = Sql_Fetch_Row($req)) {
        Sql_Query(sprintf('update %s set uuid = "%s" where id = %d',$GLOBALS['tables']['user'],uuid::generate(4),$row[0]));
    }
    $req = Sql_Query(sprintf('select id from %s where uuid = ""',$GLOBALS['tables']['message']));
    while ($row = Sql_Fetch_Row($req)) {
        Sql_Query(sprintf('update %s set uuid = "%s" where id = %d',$GLOBALS['tables']['message'],uuid::generate(4),$row[0]));
    }
    $req = Sql_Query(sprintf('select id from %s where uuid = ""',$GLOBALS['tables']['linktrack_forward']));
    while ($row = Sql_Fetch_Row($req)) {
        Sql_Query(sprintf('update %s set uuid = "%s" where id = %d',$GLOBALS['tables']['linktrack_forward'],uuid::generate(4),$row[0]));
    }


    ## longblobs are better at mixing character encoding. We don't know the encoding of anything we may want to store in cache
    ## before converting, it's quickest to clear the cache
    clearPageCache();
    Sql_Query(sprintf('alter table %s change column content content longblob', $tables['urlcache']));

    # mark the database to be our current version
    if ($success) {
        SaveConfig('version', VERSION, 0);
        # mark now to be the last time we checked for an update
        SaveConfig('updatelastcheck', date('Y-m-d H:i:s', time()), 0, true);
        ## also clear any possible value for "updateavailable"
        Sql_Query(sprintf('delete from %s where item = "updateavailable"', $tables['config']));

        Info(s('Success'), 1);

        upgradePlugins(array_keys($GLOBALS['plugins']));

        print subscribeToAnnouncementsForm();

##  check for old click track data
        $num = Sql_Fetch_Row_Query(sprintf('select count(*) from %s', $GLOBALS['tables']['linktrack']));
        if ($num[0] > 0) {
            print '<p class="information">' . $GLOBALS['I18N']->get('The clicktracking system has changed') . '</p>';
            printf($GLOBALS['I18N']->get('You have %s entries in the old statistics table'), $num[0]) . ' ';
            print ' ' . PageLinkButton('convertstats', $GLOBALS['I18N']->get('Convert Old data to new'));
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
} else {
    print '<p>' . s('Your database requires upgrading, please make sure to create a backup of your database first.') . '</p>';
    print '<p>' . s('If you have a large database, make sure you have sufficient diskspace available for upgrade.') . '</p>';
    print '<p>' . s('When you are ready click %s Depending on the size of your database, this may take quite a while. Please make sure not to interrupt the process, once it started.',
            PageLinkButton('upgrade&doit=yes', s('Upgrade'))) . '</p>';
}
