<?php

$status = 'OK';
$disabled_plugins = unserialize(getConfig('plugins_disabled'));

if (isset($_GET['disable'])) {
  if (isset($GLOBALS['plugins'][$_GET['disable']])) {
    $disabled_plugins[$_GET['disable']] = 1;
  }
  saveConfig('plugins_disabled',serialize($disabled_plugins),0);
  $status = $GLOBALS['img_cross'];
} elseif (isset($_GET['enable'])) {
  if (isset($disabled_plugins[$_GET['enable']])) {
    unset($disabled_plugins[$_GET['enable']]);
  }
  saveConfig('plugins_disabled',serialize($disabled_plugins),0);
  $status = $GLOBALS['img_tick'];
}
#var_dump($_GET);

return $status;
