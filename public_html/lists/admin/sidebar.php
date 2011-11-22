<?php
require_once dirname(__FILE__).'/accesscheck.php';

# sidebar page

  global $pixel,$tables,$require_login;
  if ($require_login) {
    session_start();
  }
  $_SESSION["sidebar_enabled"] = "yes";
  $html = "";
  $html.= '<title>PHPlist Mozilla Menu</title>
    <link href="styles/phplist.css" type=text/css rel=stylesheet></head>
    <body bgcolor="#ffffff">';
  $spb ='<span class="menulinkleft">';
  $spe = '</span>';
  if ($require_login)
    $html .= $spb.SidebarLink("logout","Logout").'<br />'.$spe;
  $html .= $spb.SidebarLink("home","Home").$spe;
  $html .= $spb.SidebarLink("list","Lists").$spe;
  $html .= $spb.SidebarLink("users","Users").$spe;
  $html .= $spb.SidebarLink("messages","Messages").$spe;
  $html .= $spb.SidebarLink("send","Send a message").$spe;;
#  $html .= $spb.SidebarLink("import","Import Emails").$spe;
#  $html .= $spb.SidebarLink("export","Export Emails").$spe;
#  $html .= $spb.'<hr/>'.$spe;
#  $req = Sql_Query(sprintf('select * from %s where active',$tables["subscribepage"]));
#  if (Sql_Affected_Rows()) {
#    while ($row = Sql_Fetch_Array($req)) {
#      $html .= $spb.sprintf('<a href="%s&amp;id=%d" target="phplistwindow">%s</a>',getConfig("subscribeurl"),$row["id"],$row["title"]).$spe;
#     }
#  } else {
#    $html .= $spb.sprintf('<a href="%s" target="phplistwindow">%s</a>',getConfig("subscribeurl"),$GLOBALS["strSubscribeTitle"]).$spe;
#  }
#  $url = getConfig("unsubscribeurl");
#  if ($url)
#    $html .= $spb.'<a href="'.$url.'" target="phplistwindow">Unsubscribe</a>'.$spe;
#  else
#    $html .= $spb.'<a href="../?p=unsubscribe" target="phplistwindow">Sign Off</a>'.$spe;

#  $html .= $spb.'<hr/>'.$spe;
#  $html .= $spb.SidebarLink("attributes","Attributes").$spe;
#  if ($tables["attribute"] && Sql_Table_Exists($tables["attribute"])) {
#    $res = Sql_Query("select * from {$tables['attribute']}",1);
#    while ($row = Sql_Fetch_array($res)) {
#      if ($row["type"] != "checkbox" && $row["type"] != "textline" && $row["type"] != "hidden")
#        $html .= $spb.SidebarLink("editattributes",strip_tags($row["name"]),"id=".$row["id"]) .$spe;
#    }
#  }
#  $html .= $spb.'<hr/>'.$spe;
  $html .= $spb.SidebarLink("templates","Templates").$spe;
  $html .= $spb.SidebarLink("bounces","View Bounces").$spe;
#  $html .= $spb.'<hr/>'.$spe;
  $html .= $spb.SidebarLink("eventlog","Eventlog").$spe;
  $some = 0;
  if (checkAccess("getrss") && MANUALLY_PROCESS_RSS) {
    $some = 1;
    $rss .= $spb.SidebarLink("getrss","Get RSS feeds").$spe;
  }
  if (checkAccess("viewrss")) {
    $some = 1;
    $rss .= $spb.SidebarLink("viewrss","View RSS items").$spe;
  }
  if ($some && ENABLE_RSS)
    $html .= $rss;
  ob_end_clean();
  include "pagetop.php";
  print $html . $pixel;
  exit;
?>
