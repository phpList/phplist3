<?php

@ob_end_clean();
$id = sprintf('%d', $_GET['id']);
if (!$id) {
    return '';
}
/*
$message = Sql_Fetch_Assoc_Query(sprintf('select * from %s where id = %d',$GLOBALS['tables']['message'],$id));
if ($message['id'] != $id) return '';
$messagedata = loadMessageData($id);

$totalsent = $message['astext'] +
  $message['ashtml'] +
  $message['astextandhtml'] +
  $message['aspdf'] +
  $message['astextandpdf'];
*/
$status = '';
//$status = 'select count(userid) as num,status from '.$GLOBALS['tables']['usermessage'].' where messageid = '.$id.'  group by status<br/>';
$req = Sql_Query(sprintf('select count(userid) as num,status from '.$GLOBALS['tables']['usermessage'].' where messageid = %d group by status',
    $id));
while ($row = Sql_Fetch_Assoc($req)) {
    if (!empty($row['num'])) {
        $status .= $row['status'].' '.$row['num'].'<br/>';
    }
}//$status = $totalsent;
