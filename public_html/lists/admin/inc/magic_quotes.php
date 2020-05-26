<?php

// experiment, see whether we can correct the magic quotes centrally

function addSlashesArray($array)
{
    foreach ($array as $key => $val) {
        if (is_array($val)) {
            $array[$key] = addSlashesArray($val);
        } else {
            $array[$key] = addslashes($val);
        }
    }

    return $array;
}

function removeSlashes(&$value, $key)
{
    $value = stripslashes($value);
}

function stripSlashesArray($array)
{
    array_walk_recursive($array, 'removeSlashes');

    return $array;
}

$_POST = addSlashesArray($_POST);
$_GET = addSlashesArray($_GET);
$_REQUEST = addSlashesArray($_REQUEST);
$_COOKIE = addSlashesArray($_COOKIE);

function removeXss($string)
{
    if (is_array($string)) {
        $return = array();
        foreach ($string as $key => $val) {
            $return[removeXss($key)] = removeXss($val);
        }

        return $return;
    }
    //$string = preg_replace('/<script/im','&lt;script',$string);
    $string = htmlspecialchars($string);
    return $string;
}

function disableJavascript($content) {
    ## disallow Javascript
    $content = str_ireplace('<script','< script',$content);
    $content = str_ireplace('onmouseenter','on mouse enter',$content);
    $content = str_ireplace('onmouseover','on mouse over',$content);
    $content = str_ireplace('onmouseout','on mouse out',$content);
    $content = str_ireplace('onmousemove','on mouse move',$content);
    $content = str_ireplace('onmousedown','on mouse down',$content);
    $content = str_ireplace('onclick','on click',$content);
    $content = str_ireplace('ondblclick','on dbl click',$content);
    $content = str_ireplace('onload','on load',$content);
    $content = str_ireplace('onunload','on unload',$content);
    $content = str_ireplace('onerror','on error',$content);
    $content = str_ireplace('onresize','on resize',$content);
    $content = str_ireplace('onblur','on blue',$content);
    $content = str_ireplace('onchange','on change',$content);
    $content = str_ireplace('onfocus','on focus',$content);
    $content = str_ireplace('onselect','on select',$content);
    $content = str_ireplace('onsubmit','on submit',$content);
    $content = str_ireplace('onreset','on reset',$content);
    $content = str_ireplace('onkeyup','on keyup',$content);
    $content = str_ireplace('onkeydown','on keydown',$content);
    $content = str_ireplace('ontoggle','on toggle',$content);
    return $content;
}

/*
foreach ($_POST as $key => $val) {
  print "POST: $key = $val<br/>";
}
foreach ($_GET as $key => $val) {
  print "GET: $key = $val<br/>";
}
foreach ($_REQUEST as $key => $val) {
  print "REQ: $key = $val<br/>";
}
foreach ($_REQUEST as $key => $val) {
  print "COOKIE: $key = $val<br/>";
}
*/
