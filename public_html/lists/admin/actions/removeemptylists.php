<?php

$req = Sql_Query(sprintf('select l.id from %s l left join %s lu on l.id = lu.listid where lu.userid is null',$GLOBALS['tables']['list'],$GLOBALS['tables']['listuser']));
$count = Sql_Affected_Rows();
$status = s('%d lists deleted ',$count);
while ($row = Sql_Fetch_Row($req)) {
    Sql_Query(sprintf('delete from %s where id = %d', $GLOBALS['tables']['list'],$row[0]));
}
