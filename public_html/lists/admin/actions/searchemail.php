<?php

//[ { "id": "Alcedo atthis", "label": "Common Kingfisher", "value": "Common Kingfisher" }, { "id": "Haliaeetus leucoryphus", "label": "Pallasâ€™s Fish Eagle", "value": "Pallasâ€™s Fish Eagle" }, { "id": "Ceryle alcyon", "label": "Belted Kingfisher", "value": "Belted Kingfisher" } ]

exit; //# unfinished code

$req = Sql_Query(sprintf('select id,email as label, email as value from %s where email like "%s%%" limit 10',
    $tables['user'], $_GET['term']));
$results = array();
while ($row = Sql_Fetch_Assoc($req)) {
    $results[] = $row;
}
//var_dump($results);

//# hmm, doesn't work
//print json_encode($results);

$out = '[ ';
foreach ($results as $match) {
    $out .= '{ "id": "'.$match['id'].'", "label:"'.$match['label'].'", "value": "'.$match['value'].'" },';
}
$out = substr($out, 0, -1);
echo $out.']';
exit;
