<?php
require_once dirname(__FILE__) . '/accesscheck.php';


class imageUpload
{
    var $type = "image";
    var $description = "Image";

    function viewImageLink($id, $width, $height, $text)
    {
        return sprintf('<a href="javascript:viewImage(\'?page=image&amp;id=%d\',%d,%d);">%s</a>', $id, $width, $height,
            $text);
    }

    function showInput($name, $value, $template_id = 0)
    {
        # find image in database
        global $config, $tables;
        $html = '<table class="classimage" border="0">';
        if ($name) {
            $req = Sql_Query(sprintf('select * from %s where template = %d
      and filename = "%s"', $tables["templateimage"], $template_id, $name));
            $imdata = Sql_Fetch_array($req);
            $width = $imdata["width"];
            $height = $imdata["height"];
        }
        $originalname = $name;
        $name = safeImageName($name);
        if (!empty($imdata["data"]) && !empty($imdata["width"]) && !empty($imdata["height"])) {

            if ($originalname == 'organisation_logo') {
                $html .= '<tr><td colspan=3>' . s('Remove') . ' <input type="checkbox" name="' . $name . '_remove" value="yes" /> &nbsp;
      <p><i>' . s('Uploading a new image will replace the existing one, unless you check "remove"') . '</i></p>';
            }
#      $html .= sprintf('<img src="./?page=image&amp;id=%d&amp;m=250" width="250" alt="" class="logoimage" />',$imdata["id"]);
            $html .= '</td></tr>';
        } else {
            $html .= '<tr><td colspan=3><input type="hidden" name="' . $name . '_keep" value="no">' . s('No Image was found') . '</td></tr>';
        }
        $html .= '<tr><td colspan=2>' . $GLOBALS['I18N']->get('Upload new image') . ':</td><td><input type="hidden" name="' . $name . '_originalname" value="' . $originalname . '"><input type="file" name="' . $name . '" /></td></tr>';
        return $html . '</table>';
    }

    function getSubData($parent, $fielddata)
    {
        if ($fielddata[type] != "image" || !$fielddata[data]) # invalid call
        {
            return "";
        }

        $result = array();
        $req = Sql_Query(sprintf('select * from image where id = %d', $fielddata[data]));
        $att = Sql_Fetch_Array($req);
        foreach ($att as $key => $val) {
            $result[$fielddata[name] . "." . $key] = $val;
        }
        return $result;
    }

    function uploadImage($imagename, $templateid)
    {
        global $tables;
        global $config;
        $imagename = safeImageName($imagename);
        if (!isset($_FILES[$imagename])) {
            return 0;
        }
        $tmpimagefile = $_FILES[$imagename]['tmp_name'];
        $originalname = $_POST[$imagename . '_originalname'];
        $filename = $_FILES[$imagename]["name"];
        $type = $_FILES[$imagename]["type"];
        $remove = $_REQUEST[$imagename . "_remove"];

        if ($filename && $tmpimagefile && $tmpimagefile != "none" && ltrim($remove) != "yes") {
            list($width, $height) = GetImageSize($tmpimagefile);

            if ($width && $height) {
                $fd = fopen($tmpimagefile, "r");
                $contents = fread($fd, filesize($tmpimagefile));
                fclose($fd);
            } else {
                dbg("Error detecting size of $tmpimagefile");
                copy($tmpimagefile, "/tmp/invalidUpload.jpg");
            }
            Sql_Query(sprintf('delete from %s where template = %d and filename = "%s"',
                $tables["templateimage"], $templateid, $originalname));
            Sql_query(sprintf('insert into %s (template,filename,mimetype,width,height,data)
       values(%d,"%s","%s",%d,%d,"%s")',
                    $tables["templateimage"], $templateid,
                    $originalname, $type, $width, $height, base64_encode($contents))
            );
            return Sql_insert_id();
        } elseif (trim($remove) == "yes") {
            return 0;
        }
        return 0;
    }
}
