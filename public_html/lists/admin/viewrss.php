

<?php
require_once dirname(__FILE__).'/accesscheck.php';

$delete = sprintf('%d',$_GET['delete']);
$start = sprintf('%d',$_GET["start"]);
$id = sprintf('%d',$_GET['id']);

if ($delete) {
  # delete the index in delete
  print "Deleting $delete ..";
  $result = Sql_query("delete from ".$tables["rssitem"]." where id = $delete");
  $suc6 = Sql_Affected_Rows();
  $result = Sql_query("delete from ".$tables["rssitem_data"]." where itemid = $delete");
  $result = Sql_query("delete from ".$tables["rssitem_user"]." where itemid = $delete");
  if ($suc6)
    print "..Done";
  else
    print "..failed";
  print "<br /><hr /><br />\n";
}

if ($GLOBALS["require_login"] && !isSuperUser()) {
  $access = accessLevel("viewrss");
  $querytables = $tables["rssitem"].','.$tables["list"];
  switch ($access) {
    case "owner":
      $subselect = "where ".$tables["rssitem"].".list = ". $tables["list"].".id and ".$tables["list"].".owner = ".$_SESSION["logindetails"]["id"];
      if ($_GET["id"]) {
        $pagingurl = '&amp;id='.$_GET["id"];
        $subselect .= " and ". $tables["rssitem"].".list = ".$_GET["id"];
        print "RSS items for ".ListName($_GET["id"])."<br/>";
      }
      break;
    case "all":
      $subselect = "";break;
    case "none":
    default:
      $subselect = "where ". $tables["rssitem"].".list = ". $tables["list"].".id and ".$tables["list"].".owner = 0";break;
  }
} else {
  $querytables = $tables["rssitem"];
  $subselect = "";
  if ($_GET["id"]) {
    $pagingurl = '&amp;id='.$_GET["id"];
    $subselect = "where ". $tables["rssitem"].".list = ".$_GET["id"];
    print "RSS items for ".ListName($_GET["id"])."<br/>";
  }
}

$req = Sql_query("SELECT count(*) FROM $querytables $subselect");
$total_req = Sql_Fetch_Row($req);
$total = $total_req[0];
if (isset($start) && $start > 0) {
  $listing = "Listing item $start to " . ($start + MAX_MSG_PP);
  $limit = "limit $start,".MAX_MSG_PP;
} else {
  $listing =  "Listing item 1 to ".MAX_MSG_PP;
  $limit = "limit 0,".MAX_MSG_PP;
  $start = 0;
}
  print $total. " RSS Items</p>";
if ($total)
  printf ('<table class="viewrssListing" border="1"><tr><td colspan="4" align="center">%s</td></tr><tr><td>%s</td><td>%s</td><td>
          %s</td><td>%s</td></tr></table><hr/>',
          $listing,
          PageLink2("viewrss$pagingurl","&lt;&lt;","start=0"),
          PageLink2("viewrss$pagingurl","&lt;",sprintf('start=%d',max(0,$start-MAX_MSG_PP))),
          PageLink2("viewrss$pagingurl","&gt;",sprintf('start=%d',min($total,$start+MAX_MSG_PP))),
          PageLink2("viewrss$pagingurl","&gt;&gt;",sprintf('start=%d',$total-MAX_MSG_PP)));

?>
<table class="viewrssListing" border=1>

<?php

if ($total) {
  print "<td>Item info</td><td>Status</td><td>More</td></tr>";
  $result = Sql_query("SELECT * FROM $querytables $subselect order by added desc $limit");
  while ($rss = Sql_fetch_array($result)) {
 #   $uniqueviews = Sql_Fetch_Row_Query("select count(userid) from {$tables["usermessage"]} where viewed is not null and messageid = ".$msg["id"]);
    printf ('<tr><td valign="top"><table class="viewrssItem">
      <tr><td valign="top"><b>Title</b>:</td><td valign="top">%s</td></tr>
      <tr><td valign="top"><b>Link</b>:</td><td valign="top"><a href="%s" target="_blank">%s</a></td></tr>
      <tr><td valign="top"><b>Source</b>:</td><td valign="top">%s</td></tr>
      <tr><td valign="top"><b>Date Added</b>:</td><td valign="top">%s</td></tr>
      </table>
      </td>',
      $rss["title"],$rss["link"],$rss["link"],ereg_replace("&","& ",$rss["source"]),$rss["added"]);

    $status = sprintf('<table class="viewrssStatus" border=1>
      <tr><td>Processed</td><td>%d</td></tr>
      <tr><td>Text</td><td>%d</td></tr>
      <tr><td>HTML</td><td>%d</td></tr>
      </table>',
      $rss["processed"],$rss["astext"],$rss["ashtml"]);
    print '<td valign="top">'.$status.'</td>';
    print '<td valign=top><table class="viewrssStatus">';
    $data_req = Sql_Query(sprintf('select * from %s where tag != "title" and tag != "link" and itemid = %d',
      $tables["rssitem_data"],$rss["id"]));
    while ($data = Sql_Fetch_ArraY($data_req)) {
      printf('<tr><td valign=top><b>%s</b></td></td></tr><tr><td valign=top>%s</td></tr>',$data["tag"],$data["data"]);
    }
    print '</table></td>';
    print '</tr>';
  }
}

?>

</table>

