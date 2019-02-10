<?php

verifyCsrfGetToken();

if ($_GET['option'] == 'deleteinvalidemail') {
    $status = s('Deleting subscribers with an invalid email').'<br/ >';

    flush();
    $req = Sql_Query("select id,email from {$tables['user']}");
    $c = 0;
    while ($row = Sql_Fetch_Array($req)) {
        set_time_limit(60);
        if (!is_email($row['email'])) {
            ++$c;
            deleteUser($row['id']);
        }
    }
    $status .= $c.' '.s('subscribers deleted')."<br/>\n";
} elseif ($_GET['option'] == 'deleteblacklisted') {

    // Delete subscribers who are blacklisted (for any reason)
    $status = s('Deleting blacklisted subscribers').'<br/ >';

    flush();
    $req = Sql_Query('
        SELECT
            id
        FROM
            '.$tables['user'].'
        WHERE
            blacklisted = 1'
    );
    $c = 0;
    while ($row = Sql_Fetch_Array($req)) {
        set_time_limit(60);
        ++$c;
        deleteUserIncludeBlacklist($row['id']);
    }
    $status .= $c.' '.s('subscribers deleted')."<br/>\n";

} elseif ($_GET['option'] == 'deleteblacklistedunsubscribed') {

    // Delete subscribers who are blacklisted because they unsubscribed
    $status = s('Deleting blacklisted subscribers').'<br/ >';

    flush();
    $req = Sql_Query('
        SELECT
            *
        FROM
            '.$tables['user'].' AS u
        LEFT JOIN
            '.$tables['user_blacklist'].' AS bl ON bl.email = u.email
        LEFT JOIN
            '.$tables['user_blacklist_data'].' AS bld ON bld.email = u.email
        WHERE
            blacklisted = 1
            AND bld.name = "reason"
            AND bld.data = \''.s('"Jump off" set, reason not requested').'\''
    );

    $c = 0;
    while ($row = Sql_Fetch_Array($req)) {
        set_time_limit(60);
        ++$c;
        deleteUserIncludeBlacklist($row['id']);
    }
    $status .= $c.' '.s('subscribers deleted')."<br/>\n";
} elseif ($_GET['option'] == 'relinksystembounces') {
    $status = s('Relinking transactional messages that bounced to the related subscriber profile').'<br/ >';
    $req = Sql_Query(sprintf('select count(id) from %s b where b.status = "bounced system message" and b.comment like "%% marked unconfirmed" and id not in (select distinct bounce from %s)',$tables['bounce'],$tables['user_message_bounce']));
    $totalRow = Sql_Fetch_Row($req);
    $total = $totalRow[0];
    $status .= s('%d to process',$total).'<br/>';
    flush();

    $cnt = 0;
    $done = 0;
    $req = Sql_Query(sprintf('select id,comment,date from %s where status = "bounced system message" and comment like "%% marked unconfirmed" and id not in (select distinct bounce from %s)',$tables['bounce'],$tables['user_message_bounce']));
    while ($row = Sql_Fetch_Assoc($req)) {
        ++$cnt;
        set_time_limit(60);
        if (preg_match('/([\d]+) marked unconfirmed/',$row['comment'],$regs)) {
            $exists = Sql_Fetch_Row_Query(sprintf('select count(*) from %s where user = %d and message = -1 and bounce = %d',$tables['user_message_bounce'],$regs[1],$row['id']));
            if (empty($exists[0])) {
                Sql_Query(sprintf('insert into %s (user,message,bounce,time) values(%d,-1,%d,"%s")',$tables['user_message_bounce'],$regs[1],$row['id'],$row['date']));
                ++$done;
            }
        }
    }
    $status .= s('%d bounces have been associated with a subscriber profile',$done);
}

