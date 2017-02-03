<?php

require_once dirname(__FILE__).'/accesscheck.php';

// make sure we have not send any output yet
ob_end_clean();

$id = !empty($_GET['id']) ? sprintf('%d', $_GET['id']) : 0;
if ($id) {
    $res = Sql_query("select * from {$tables['templateimage']} where id = $id");
    $row = Sql_fetch_array($res);
}
if (isset($_GET['m'])) {
    $max = sprintf('%d', $_GET['m']);
} else {
    $max = 0;
}

if (!empty($row['data'])) {
    $imageContent = base64_decode($row['data']);
    if ($max) {
        $imSize = getimagesizefromstring($imageContent);
        $sizeW = $imSize[0];
        $sizeH = $imSize[1];
        if (($sizeH > $max) || ($sizeW > $max)) {
            if ($sizeH > $sizeW) {
                $sizefactor = (float) ($max / $sizeH);
            } else {
                $sizefactor = (float) ($max / $sizeW);
            }
            $newwidth = (int) ($sizeW * $sizefactor);
            $newheight = (int) ($sizeH * $sizefactor);

            $original = imagecreatefromstring($imageContent);
            $resized = imagecreatetruecolor($newwidth, $newheight);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefill($resized, 0, 0, $transparent);
            if (imagecopyresized($resized, $original, 0, 0, 0, 0, $newwidth, $newheight, $sizeW, $sizeH)) {
                header('Content-type: '.$imSize['mime']);
            } else {
                header('Content-type: image/jpeg');
            }
            echo imagejpeg($resized);
            exit;
        }
    }
    if ($row['mimetype']) {
        header('Content-type: '.$row['mimetype']);
    } else {
        header('Content-type: image/jpeg');
    }
    echo base64_decode($row['data']);
} else {
    header('Content-Type: image/png');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAABGdBTUEAALGPC/xhBQAAAAZQTFRF////AAAAVcLTfgAAAAF0Uk5TAEDm2GYAAAABYktHRACIBR1IAAAACXBIWXMAAAsSAAALEgHS3X78AAAAB3RJTUUH0gQCEx05cqKA8gAAAApJREFUeJxjYAAAAAIAAUivpHEAAAAASUVORK5CYII=');
}
