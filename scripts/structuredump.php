<?php

## dump the structure file for language parsing

define('PHPLISTINIT',1);

include "public_html/lists/admin/structure.php";
print '<?php '.PHP_EOL;

foreach ($DBstruct as $table => $tStruct) {
  foreach ($tStruct as $column => $cStruct) {
  #  var_dump($cStruct);
    $val = $cStruct[1];
    $val = str_replace('sysexp:','',$val);
    $val = str_replace('sys:','',$val);
    $val = preg_replace('/^index$/','',$val);
    $val = preg_replace('/^unique$/','',$val);
    $val = preg_replace('/^id$/i','',$val);

    if (!empty($val)) {
      print 'get("'.$val .'")'.PHP_EOL;
    }
  }
}
