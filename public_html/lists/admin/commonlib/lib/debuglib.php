<?php
function dbg($variable = "", $description= 'Value', $table_started= 0) {
  # WARNING recursive

  if (!$table_started)
    echo "<ul type='circle' style='border:1px solid #a0a0a0;padding-bottom:4px;padding-right:4px'>\n<li>{" . getenv("REQUEST_URI") . "} ";
  echo "<i>$description</i>: ";
  if (is_array($variable) || is_object($variable)) {
    if (is_array($variable)) {
      echo "(array)[" . count($variable) . "]";
    } else {
      echo "<B>(object)</B>[" . count($variable) . "]";
    }
    echo "<ul type='circle' style='border:1px solid #a0a0a0;padding-bottom:4px;padding-right:4px'>\n";
    foreach ($variable as $key => $value) {
      echo "<li>\"{$key}\" => ";
      dbg($value, '', TRUE);
      echo "</li>\n";
    }
    echo "</ul>\n";
  } else
    echo "(" . gettype($variable) . ") '{$variable}'\n";
  if (!$table_started)
    echo "</li></ul>\n";
}
?>
