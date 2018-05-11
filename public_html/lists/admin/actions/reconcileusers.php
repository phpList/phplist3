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
}
