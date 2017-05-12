<?php

$req = Sql_Query(sprintf('select l.name,count(l.id) from %s l group by l.name',$GLOBALS['tables']['list']));
cl_output(s('Merging lists'));
while ($row = Sql_Fetch_Row($req)) {
    if ($row[1] > 1) {
        cl_output(s('List name: "%s", duplicates: %d',$row[0],$row[1]));
        $req2 = Sql_Query(sprintf('select l.id from %s l where name = "%s" order by l.id',$GLOBALS['tables']['list'],$row[0]));
        $first = Sql_Fetch_Row($req2);
        cl_output(s('First list: %d',$first[0]));
        while ($dup = Sql_Fetch_Row($req2)) {
            cl_output('List id: '.$dup[0]);
            $listusers = Sql_Query(sprintf('update ignore %s lu set listid = %d where listid = %d',$GLOBALS['tables']['listuser'],$first[0],$dup[0]));
            $num = Sql_Affected_Rows();
            cl_output(s('%d subscribers moved',$num));
            $listusers = Sql_Query(sprintf('update ignore %s lm set listid = %d where listid = %d',$GLOBALS['tables']['listmessage'],$first[0],$dup[0]));
            $num = Sql_Affected_Rows();
            cl_output(s('%d campaigns moved',$num));
            Sql_Query(sprintf('delete from %s where listid = %d',$GLOBALS['tables']['listuser'],$dup[0]));
            Sql_Query(sprintf('delete from %s where listid = %d',$GLOBALS['tables']['listmessage'],$dup[0]));
            Sql_Query(sprintf('delete from %s where id = %d',$GLOBALS['tables']['list'],$dup[0]));
        }

    }
}
$status = s('All done');