<?php
require_once dirname(__FILE__).'/accesscheck.php';

# make sure we have not send any output yet
ob_end_clean();

$id = !empty($_GET['id']) ? sprintf('%d',$_GET['id']) : 0;
if ($id) {
  $res = Sql_query("select * from {$tables["templateimage"]} where id = $id");
  $row = Sql_fetch_array($res);
}

if ($row["data"]) {
  if ($row["mimetype"]) {
    Header("Content-type: ".$row["mimetype"]);
  } else {
    header("Content-type: image/jpeg");
  }
  echo base64_decode($row["data"]);
} else {
  header("Content-Type: image/png");
  print base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAABGdBTUEAALGPC/xhBQAAAAZQTFRF////AAAAVcLTfgAAAAF0Uk5TAEDm2GYAAAABYktHRACIBR1IAAAACXBIWXMAAAsSAAALEgHS3X78AAAAB3RJTUUH0gQCEx05cqKA8gAAAApJREFUeJxjYAAAAAIAAUivpHEAAAAASUVORK5CYII=');
}

exit;
?>
