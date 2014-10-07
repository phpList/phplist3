<?php

if (!defined('PHPLISTINIT')) die();
verifyCsrfGetToken();

if ($_GET['update'] == 'tlds') {
  refreshTlds(true);
}

$status = s('Top level domains were updated successfully');
