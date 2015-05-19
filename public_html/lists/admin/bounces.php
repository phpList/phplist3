<?php

require_once dirname(__FILE__).'/accesscheck.php';

$result = '';

if (isset($_REQUEST['delete']) && $_REQUEST['delete']) {
  # delete the index in delete
  $result .= $GLOBALS['I18N']->get('deleting').' '.$_REQUEST['delete']."..\n";
  if ($GLOBALS["require_login"] && !isSuperUser()) {
  } else {
    deleteBounce($_REQUEST['delete']);
  }
  $result .= $GLOBALS['I18N']->get('done');
  print ActionResult($result);
}

$tabs = new WebblerTabs();
$tabs->addTab(s("processed"),PageUrl2("bounces&amp;tab=processed"),'processed');
$tabs->addTab(s("unidentified"),PageUrl2("bounces&amp;tab=unidentified"),'unidentified');

if (!isset($_GET['tab'])) $_GET['tab'] = 'processed';
$currentTab = 'processed';
switch ($_GET['tab']) {
  case 'unidentified':
    $status_compare = '=';
    $status = 'unidentified';
    $currentTab = 'unidentified';
    break;
  case 'processed':
  default:
    $status_compare = '!=';
    $status = 'processed';
    break;
}  
$tabs->setCurrent($currentTab);

if (ALLOW_DELETEBOUNCE && isset($_GET['action']) && $_GET['action']) {
  switch($_GET['action']) {
    case "deleteunidentified":
      Sql_Query(sprintf('delete from %s where comment = "unidentified bounce" and date_add(date,interval 2 month) < now()',$tables["bounce"]));
      break;
    case "deleteprocessed":
      Sql_Query(sprintf('delete from %s where comment != "not processed" and date_add(date,interval 2 month) < now()',$tables["bounce"]));
      break;
    case "deleteall":
      Sql_Query(sprintf('delete from %s',$tables["bounce"]));
      break;
    case "reset":
      Sql_Query(sprintf('update %s set bouncecount = 0',$tables["user"]));
      Sql_Query(sprintf('update %s set bouncecount = 0',$tables["message"]));
      Sql_Query(sprintf('delete from %s',$tables["bounce"]));
      Sql_Query(sprintf('delete from %s',$tables["user_message_bounce"]));
   }
}

# view bounces
$count = Sql_Query(sprintf('select count(*) from %s where status '.$status_compare. ' "unidentified bounce"',$tables["bounce"]));
$totalres = Sql_fetch_Row($count);
$total = $totalres[0];
$find_url = '';
if (isset($_GET['start'])) {
  $start = sprintf('%d',$_GET['start']);
} else {
  $start = 0;
}
$offset = $start;
$baseurl = "bounces&amp;start=$start&amp;tab=$currentTab";
$limit = MAX_USER_PP;

if ($total > MAX_USER_PP) {
  $paging = simplePaging("bounces&amp;tab=$currentTab",$start,$total,MAX_USER_PP,$status . ' '.$GLOBALS['I18N']->get('bounces') );
  $query = sprintf('select * from %s where status %s "unidentified bounce" order by date desc limit %s offset %s', $tables['bounce'], $status_compare, $limit,$offset);
  $result = Sql_Query($query);
} else {
  $paging = '';
  $query = sprintf('select * from %s where status '.$status_compare. ' "unidentified bounce" order by date desc', $tables['bounce']);
  $result = Sql_Query($query);
}

$buttons = new ButtonGroup(new Button(PageURL2("bounces"),'delete'));
$buttons->addButton(
  new ConfirmButton(
    $GLOBALS['I18N']->get('are you sure you want to delete all unidentified bounces older than 2 months') . "?",
    PageURL2("$baseurl&action=deleteunidentified"),
    $GLOBALS['I18N']->get('delete all unidentified (&gt; 2 months old)')));
$buttons->addButton(
  new ConfirmButton(
    $GLOBALS['I18N']->get('are you sure you want to delete all bounces older than 2 months') . "?",
    PageURL2("$baseurl&action=deleteprocessed"),
    $GLOBALS['I18N']->get('delete all processed (&gt; 2 months old)')));
$buttons->addButton(
  new ConfirmButton(
    $GLOBALS['I18N']->get('are you sure you want to delete all bounces') . "?",
    PageURL2("$baseurl&action=deleteall"),
    $GLOBALS['I18N']->get('Delete all')));
if (ALLOW_DELETEBOUNCE) {
  print $buttons->show();
}


print "<div class='actions'>\n";
print PageLinkButton('listbounces',$GLOBALS['I18N']->get('view bounces by list'));

print "<div class='minitabs'>\n";
print $tabs->display();
print "</div>\n";

print "</div><!-- .actions div-->\n";

if (!Sql_Num_Rows($result)) {
  switch ($status) {
    case 'unidentified':
      print '<p class="information">' . s('no unidentified bounces available') . "</p>";
      break;
    case 'processed':
      print '<p class="information">' . s('no processed bounces available') . "</p>";
      break;

    }
}

$ls = new WebblerListing($status . ' '.s('bounces'));
$ls->usePanel($paging);
while ($bounce = Sql_fetch_array($result)) {
#@@@ not sure about these ones - bounced list message
  $element = $bounce["id"];
  $ls->addElement($element,PageUrl2('bounce&type='.$status.'&id='.$bounce["id"]));
  if (preg_match("#bounced list message ([\d]+)#",$bounce["status"],$regs)) {
    $messageid = sprintf('<a href="./?page=message&amp;id=%d">%d</a>',$regs[1],$regs[1]);
  } elseif ($bounce["status"] == "bounced system message") {
    $messageid = $GLOBALS['I18N']->get('System Message');
  } else {
    $messageid = $GLOBALS['I18N']->get('Unknown');
  }
  
/*  if (preg_match('/Action: delayed\s+Status: 4\.4\.7/im',$bounce["data"])) {
    $ls->addColumn($element,'delayed',$GLOBALS['img_tick']);
  } else {
    $ls->addColumn($element,'delayed',$GLOBALS['img_cross']);
  }
*/
  $ls->addColumn($element,$GLOBALS['I18N']->get('message'),$messageid);
  if (preg_match("#([\d]+) bouncecount increased#",$bounce["comment"],$regs)) {
    $userid = sprintf('<a href="./?page=user&amp;id=%d">%d</a>',$regs[1],$regs[1]);
  } elseif (preg_match("#([\d]+) marked unconfirmed#",$bounce["comment"],$regs)) {
    $userid = sprintf('<a href="./?page=user&amp;id=%d">%d</a>',$regs[1],$regs[1]);
  } else {
    $userid = $GLOBALS['I18N']->get('Unknown');
  }
  $ls->addColumn($element,$GLOBALS['I18N']->get('user'),$userid);
  $ls->addColumn($element,$GLOBALS['I18N']->get('date'),$bounce["date"]);

/*
  printf( "<tr><td>[ <a href=\"javascript:deleteRec('%s');\">%s</a> |
   %s ] </td><td>%s</td><td>%s</td><td>%s</td></tr>\n",
   PageURL2("bounces",$GLOBALS['I18N']->get('delete'),"s=$start&amp;delete=".$bounce["id"]),
   $GLOBALS['I18N']->get('delete'),
   PageLinkButton("bounce",$GLOBALS['I18N']->get('Show'),"s=$start&amp;id=".$bounce["id"]),
   $messageid,
   $userid,
   $bounce["date"]
   );
*/
}
#print "</table>";
print $ls->display();
