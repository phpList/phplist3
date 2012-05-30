<?php
require_once dirname(__FILE__).'/accesscheck.php';


class imageUpload {
  var $type = "image";
  var $description = "Image";

  function image() {
  }

  function viewImageLink($id,$width,$height,$text) {
    return sprintf('<a href="javascript:viewImage(\'?page=image&amp;id=%d\',%d,%d);">%s</a>',$id,$width,$height,$text);
  }

  function showInput($name,$value,$template_id = 0) {
    # find image in database
    global $config,$tables;
    $html = '<table class="classimage" border="0">';
    if ($name && $template_id) {
      $req = Sql_Query(sprintf('select * from %s where template = %d
      and filename = "%s"',$tables["templateimage"],$template_id,$name));
      $imdata = Sql_Fetch_array($req);
      $width = $imdata["width"];
      $height = $imdata["height"];
    }
    $originalname = $name;
    $name = safeImageName($name);
    $html .= '
    <script language="Javascript">
       function unCheck'.$name.'() {
        if (this.document.forms[0].'.$name.') {
          if ((this.document.forms[0].'.$name.'_keep.checked) && (this.document.forms[0].'.$name.'.value != ""))
       this.document.forms[0].'.$name.'_keep.checked = false;
         }
       }
    </script>
    ';
    if (!empty($imdata["data"]) && !empty($imdata["width"]) && !empty($imdata["height"])) {
      $html .= '<tr><td colspan=3>'.$GLOBALS['I18N']->get('An image exists on the server, check this box to keep the existing one').' <input type="checkbox" name="'.$name.'_keep" value="yes" checked>&nbsp;';
      $html .= sprintf('%s</td></tr>',$this->viewImageLink($imdata["id"],$imdata["width"],$imdata["height"],$GLOBALS['I18N']->get('View Image')));
    }
    else
    $html .= '<tr><td colspan=3><input type="hidden" name="'.$name.'_keep" value="no">'.$GLOBALS['I18N']->get('No Image was found').'</td></tr>';
    $html .= '<tr><td colspan=2>'.$GLOBALS['I18N']->get('Upload new image').':</td><td><input type="hidden" name="'.$name.'_originalname" value="'.$originalname.'"><input type=file name="'.$name.'" onChange="unCheck'.$name.'();"></td></tr>';
#    $html .= '<tr><td colspan=2>Caption: </td><td><input type=text name="'.$name.'_caption" size="40" value="'.$imdata["caption"].'"></td></tr>';
#    $html .= '<tr><td colspan=2>Alt Tag: </td><td><input type=text name="'.$name.'_alttag" size="40" value="'.$imdata["alttag"].'"></td></tr>';
    return $html . '</table>';
  }

  function getSubData($parent,$fielddata) {
    if ($fielddata[type] != "image" || !$fielddata[data])
      # invalid call
      return "";

    $result = array();
    $req = Sql_Query(sprintf('select * from image where id = %d',$fielddata[data]));
    $att = Sql_Fetch_Array($req);
    while (list($key,$val) = each ($att))
      $result[$fielddata[name].".".$key] = $val;
    return $result;
  }

  function fix_php_upload_bug($tmp) {
    global $config;
 #   dbg("Fixing upload bug in $tmp");
 #   copy($tmp,"/tmp/prefix.jpg");
    $infile=fopen($tmp,"r"); // Open the file for the copy
    $outfile=fopen("$tmp.new","w"); // create a new temp file
    $file=fopen("$tmp.stripped","w"); // create a new temp file for the stripped stuff for debugging

    $header=fgets($infile,255); //get the 1st line (netscape sometimes doesn't add a Content-type line)
    fwrite($file,$header,strlen($header)); //copying contents to new temp file
    // if its more than just a \r\n sequence then
    // aargh, now I'm getting even more headers in the file
    while (strlen($header)>2) {
      $header=fgets($infile,255); //get next line also
      fwrite($file,$header,strlen($header)); //copying contents to new temp file
    }
    fclose($file);
    if (!$config["debug"])
      unlink("$tmp.stripped");

    while(!feof($infile)) { // Loop through the remaining file
      $temp=fread($infile,128);
      fwrite($outfile,$temp,strlen($temp)); //copying contents to new temp file
    }
    fclose($outfile);
    fclose($infile);
    copy("$tmp.new","$tmp"); //replace the original with our new bug fixed file
    unlink("$tmp.new"); //and delete the new file
    return filesize($tmp); //return a true file size
  }

  function detect_php_upload_bug($tmp) {
    ## extremely old workaround of PHP Bug that was fixed years ago.
    return 0;
  }

  function uploadImage($imagename,$templateid) {
    global $tables;
    global $config;
    $imagename = safeImageName($imagename);
    $tmpimagefile = $_FILES[$imagename]['tmp_name'];
    $originalname =  $_POST[$imagename.'_originalname'];
    $filename = $_FILES[$imagename]["name"];
    $type = $_FILES[$imagename]["type"];
    $keep = $_REQUEST[$imagename."_keep"];

#    dbg("Uploading Name $imagename, File $tmpimagefile, Location $location, Type $type, ". $$type . " Location ". $$location);
#    dbg("existing $existingid - $keep => ".$$keep);
    if ($filename && $tmpimagefile && $tmpimagefile != "none" && ltrim($keep) != "yes") {
#      dbg("Uploading $tmpimagefile");
      if (!$type && $type = $this->detect_php_upload_bug($tmpimagefile))
        $this->fix_php_upload_bug($tmpimagefile);
        list($width,$height) = GetImageSize($tmpimagefile);

      if ($width && $height) {
        $fd = fopen ($tmpimagefile, "r");
        $contents = fread ($fd, filesize ($tmpimagefile));
        fclose ($fd);
      } else {
        dbg("Error detecting size of $tmpimagefile");
        copy($tmpimagefile,"/tmp/invalidUpload.jpg");
      }
      Sql_Query(sprintf('delete from %s where template = %d and filename = "%s"',
        $tables["templateimage"],$templateid,$originalname));
      Sql_query(sprintf('insert into %s (template,filename,mimetype,width,height,data)
       values(%d,"%s","%s",%d,%d,"%s")',
       $tables["templateimage"],$templateid,
       $originalname,$type,$width,$height,base64_encode($contents))
    );
      return Sql_Insert_Id($tables['templateimage'], 'id');
    } elseif (trim($keep) == "yes") {
      # do nothing...
    }
    return 0;
  }
}
?>
