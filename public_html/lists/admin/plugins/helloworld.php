<?php
class helloworld extends phplistPlugin {
  var $name = "Hello World";
  var $coderoot = "plugins/defaultplugin/";

  function helloworld() {
  }

  function adminmenu() {
    return array(
      "helloworld" => "Example plugin, dynamic page"
    );
  }

}
?>
