<?php
#error_reporting(E_ALL);
require_once dirname(__FILE__).'/accesscheck.php';

$msgid = empty($_GET['id']) ? 0 : sprintf('%d',$_GET['id']);

if (!$msgid) {
  $req = Sql_Query(sprintf('select message.id as messageid,message.subject,count(distinct user) as numusers from %s message, %s umb where message.id = umb.message and date_add(message.entered,interval 3 month) > current_timestamp group by message.id order by message.entered desc',$GLOBALS['tables']['message'],$GLOBALS['tables']['user_message_bounce']));
  $ls = new WebblerListing($GLOBALS['I18N']->get('Choose a message'));
  while ($row = Sql_Fetch_Array($req)) {
    $element = $GLOBALS['I18N']->get('message').' '.$row['messageid'];
    $ls->addElement($element,PageUrl2('msgbounces&amp;id='.$row['messageid']));
    $ls->addColumn($element,$GLOBALS['I18N']->get('subject'),$row['subject']);
    $ls->addColumn($element,$GLOBALS['I18N']->get('# bounced'),$row['numusers']);
  }
  print $ls->display();
  return;
}

$req = Sql_Query(sprintf('select message.id as messageid,message.subject,umb.user as userid,count(bounce) as numbounces from %s message, %s umb where message.id = umb.message and message.id = %d and date_add(message.entered,interval 3 month) > current_timestamp group by umb.user order by message.entered desc',$GLOBALS['tables']['message'],$GLOBALS['tables']['user_message_bounce'],$msgid));
$total = Sql_Affected_Rows();
$limit = '';
$numpp = 150;

$s = empty($_GET['s']) ? 0 : sprintf('%d',$_GET['s']);
if ($total > 500 && $_GET['type'] != 'dl') {
#  print Paging2('listbounces&amp;id='.$listid,$total,$numpp,'Page');
  $listing = sprintf($GLOBALS['I18N']->get("Listing %s to %s"),$s,$s+$numpp);
  $limit = "limit $s,".$numpp;
  print $total. " ".$GLOBALS['I18N']->get(" Total")."</p>";
  printf ('<table class="bouncesListing" border="1"><tr><td colspan=4 align=center>%s</td></tr><tr><td>%s</td><td>%s</td><td>
          %s</td><td>%s</td></tr></table><hr/>',
          $listing,
          PageLink2('msgbounces&amp;id='.$msgid,"&lt;&lt;","s=0"),
          PageLink2('msgbounces&amp;id='.$msgid,"&lt;",sprintf('s=%d',max(0,$s-$numpp))),
          PageLink2('msgbounces&amp;id='.$msgid,"&gt;",sprintf('s=%d',min($total,$s+$numpp))),
          PageLink2('msgbounces&amp;id='.$msgid,"&gt;&gt;",sprintf('s=%d',$total-$numpp)));
  $req = Sql_Query(sprintf('select message.id as messageid,message.subject,umb.user as userid,count(bounce) as numbounces from %s message, %s umb where message.id = umb.message and message.id = %d and date_add(message.entered,interval 3 month) > current_timestamp group by umb.user order by message.entered desc %s',$GLOBALS['tables']['message'],$GLOBALS['tables']['user_message_bounce'],$msgid,$limit));
}

print '<p class="button">'.PageLink2('msgbounces','Select another message');
print '&nbsp;'.PageLink2('msgbounces&type=dl&&amp;id='.$msgid,'Download emails');
print '</p>';
if ($_GET['type'] == 'dl') {
  ob_end_clean();
  Header("Content-type: text/plain");
  $filename = 'Bounces on message '.$msgid;
  header("Content-disposition:  attachment; filename=\"$filename\"");
}

$currentmsg = 0;
$ls = new WebblerListing('');
while ($row = Sql_Fetch_Array($req)) {
  if ($currentmsg != $row['messageid']) {
    if ($_GET['type'] != 'dl') {
      print $ls->display();
    }
    $currentmsg = $row['messageid'];
    flush();
    $ls = new WebblerListing($row['subject']);
  }
  $userdata = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d',
    $GLOBALS['tables']['user'],$row['userid']));
  if ($_GET['type'] == 'dl') {
    print $userdata['email']."\n";
  }

  $ls->addElement($row['userid'],PageUrl2('user&amp;id='.$row['userid']));
  $ls->addColumn($row['userid'],$GLOBALS['I18N']->get('email'),$userdata['email']);
  $ls->addColumn($row['userid'],$GLOBALS['I18N']->get('# bounces'),$row['numbounces']);
}
if ($_GET['type'] != 'dl') {
  print $ls->display();
} else {
  exit;
}
