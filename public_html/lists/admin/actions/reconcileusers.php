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
}
