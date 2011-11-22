<?php
class sidebar extends phplistPlugin {
  var $name = "Mozilla Sidebar";
  var $coderoot = "plugins/sidebar/";

  function helloworld() {
  }

  function adminmenu() {
    return array(
      "main" => "Mozilla Sidebar"
    );
  }

}
?>
