#!/usr/bin/php
<?php

/* Scripts to convert tabs to two spaces and trim trailing spaces */
/* Written by ThG for phplist */

/* Suggested usage (literally, including backslashes):
 *  $ find . \( -name "*.php" -or -name "*.inc" \) -exec ./convert_tabs.php {} \;
 */

if ($argc < 2) {
    die('Usage: '.$argv[0]." <filename>\n");
}

$file = $argv[1];
$fd = fopen($argv[1], 'r+t') or die("Cannot open file \"$file\"\n");

$data = explode("\n", fread($fd, filesize($file)));

for ($i = count($data); $i > 0; --$i) {
    $data[$i - 1] = rtrim(str_replace("\t", '  ', $data[$i - 1]));
}

fseek($fd, 0);
fwrite($fd, implode("\n", $data));
