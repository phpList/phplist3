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
    $status .= $c.' '.$GLOBALS['I18N']->get('subscribers deleted')."<br/>\n";
}
