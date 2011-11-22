<?php
require_once dirname(__FILE__).'/accesscheck.php';

if (isset($_POST['daysago'])) {
  $daysago = sprintf('%d',$_POST['daysago']);
} else {
  $daysago = 0;
}

if (!$_SESSION['logindetails']['superuser']) {
  print '<p class="information">'.$GLOBALS['I18N']->get('Sorry, only super users can purge RSS items from the database').'</p>';
  return;
}

$count = 0;
if ($daysago) {
  $req = Sql_Query(sprintf('select id from %s where date_add(added,interval %d day) < now()',$GLOBALS['tables']['rssitem'],$daysago));
  while ($row = Sql_Fetch_Row($req)) {
    Sql_Query(sprintf('delete from %s where itemid = %d',$GLOBALS['tables']['rssitem_data'],$row[0]));
    Sql_Query(sprintf('delete from %s where itemid = %d',$GLOBALS['tables']['rssitem_user'],$row[0]));
    Sql_Query(sprintf('delete from %s where id = %d',$GLOBALS['tables']['rssitem'],$row[0]));
    $count++;
  }
  printf ('<p class="information">'.$GLOBALS['I18N']->get('%d RSS items purged').'</p>',$count);
}

print '<p class="information">'.$GLOBALS['I18N']->get('Purge RSS items from database').'</p>';
print '<p class="information">'.$GLOBALS['I18N']->get('Enter the number of days to go back purging entries').'</p>';
print '<p class="information">'.$GLOBALS['I18N']->get('All entries that are older than the number of days you enter will be purged.').'</p>';
print '<form method="post" action=""><input type=text name="daysago" value="30" size=7>
</form>';
?>
