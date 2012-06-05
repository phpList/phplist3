<?php
require_once dirname(__FILE__).'/accesscheck.php';

$subselect = '';
@ob_end_flush();

if (!ALLOW_IMPORT) {
  print '<p class="information">'.$GLOBALS['I18N']->get('import is not available').'</p>';
  return;
}

## most basic possible import: big text area to paste emails in
$selected_lists = array();

## this needs to be outside of anything, so that (ajaxed) addition of a list can be processed
$selected_lists = getSelectedLists('importlists');

$actionresult = '';
$content = '';

if (!empty($_POST['importcontent'])) {
  $lines = explode("\n",$_POST['importcontent']);
  $count['imported'] = 0;
  $count['duplicate'] = 0;
  $count['processed'] = 0;
  $count['invalid'] = 0;
  
  $total = count($lines);
  foreach ($lines as $line) {
    if (trim($line) == '') continue;

    ## do some basic clearing
    $line = cleanEmail($line);
    $uniqid = getUniqid();

    if (!empty($_POST['checkvalidity'])) {
      $isValid = validateEmail($line);
    } else {
      $isValid = true;
    }

    if ($isValid) {
      ## I guess everyone will import all their users wanting to receive HTML ....
      $query = sprintf('insert into %s (email,entered,htmlemail,confirmed,uniqid)
                values("%s",now(),1,1,"%s")', $tables["user"], $line, $uniqid);
      $result = Sql_query($query, 1);
      $userid = Sql_insert_id();
      if (empty($userid)) {
        $count['duplicate']++;
        ## mark the subscriber confirmed, don't touch blacklisted
        ## hmm, maybe not, can be done on the reconcile page
     #   Sql_Query(sprintf('update %s set confirmed = 1 where email = "%s"', $tables["user"], $line));
        $idreq = Sql_Fetch_Row_Query(sprintf('select id from %s where email = "%s"', $tables["user"], $line));
        $userid = $idreq[0];
      } else {
        $count['imported']++;
        addUserHistory($line,$GLOBALS['I18N']->get('import_by').' '.adminName(),'');
      }

      foreach($selected_lists as $k => $listid) {
        $query = "replace into ".$tables["listuser"]." (userid,listid,entered) values($userid,$listid,current_timestamp)";
        $result = Sql_query($query);
      }
    } else {
      $count['invalid']++;
    }

    $count['processed']++;
    if ($count['processed'] % 100 == 0) {
      print $count['processed'].' / '.$total.' '.$GLOBALS['I18N']->get('Imported').'<br/>';
      flush();
    }
  }
  $report = sprintf($GLOBALS['I18N']->get('%d lines processed')."\n",$count['processed']);
  $report .= sprintf($GLOBALS['I18N']->get('%d emails imported')."\n",$count['imported']);
  $report .= sprintf($GLOBALS['I18N']->get('%d duplicates')."\n",$count['duplicate']);
  $report .= sprintf($GLOBALS['I18N']->get('%d invalidated')."\n",$count['invalid']);

  print ActionResult(nl2br($report));

  if ($_GET['page'] == 'importsimple') {
    print '<div class="actions">
    '
    .PageLinkButton('send',$GLOBALS['I18N']->get('Send a campaign'))
    .PageLinkButton('importsimple',$GLOBALS['I18N']->get('Import some more emails'))
    
    .'</div>';
  }
  sendMail(getConfig("admin_address"), $GLOBALS['I18N']->get('phplist Import Results'), $report);
  foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
    $plugin->importReport($report);
  }
  return;
}

if ($GLOBALS["require_login"] && !isSuperUser()) {
  $access = accessLevel("import1");
  switch ($access) {
    case "owner":
      $subselect = " where owner = ".$_SESSION["logindetails"]["id"];break;
    case "all":
      $subselect = "";break;
    case "none":
    default:
      $subselect = " where id = 0";break;
  }
}

if (isset($_GET['list'])) {
  $id = sprintf('%d',$_GET['list']);
  if (!empty($subselect)) {
    $subselect .= ' and id = '.$id;
  } else {
    $subselect .= ' where id = '.$id;
  }
} 
#print PageLinkDialog('addlist',$GLOBALS['I18N']->get('Add a new list'));
print FormStart(' enctype="multipart/form-data" name="import"');

$result = Sql_query("SELECT id,name FROM ".$tables["list"]."$subselect ORDER BY listorder");
$total = Sql_Num_Rows($result);
$c=0;
if ($total == 1) {
  $row = Sql_fetch_array($result);
  $content .= sprintf('<input type="hidden" name="listname[%d]" value="%s"><input type="hidden" name="importlists[%d]" value="%d">'.$GLOBALS['I18N']->get('Adding subscribers').' <b>%s</b>',$c,stripslashes($row["name"]),$c,$row["id"],stripslashes($row["name"]));
} else {
  $content .= '<p>'.$GLOBALS['I18N']->get('Select the lists to add the emails to').'</p>';

  $content .= ListSelectHTML($selected_lists,'importlists',$subselect);
}

$content .= '<p class="information">'.
$GLOBALS['I18N']->get('Please enter the emails to import, one per line, in the box below and click "Import Emails"');
#$GLOBALS['I18N']->get('<b>Warning</b>: the emails you import will not be checked on validity. You can do this later on the "reconcile subscribers" page.');
$content .= '</p>';
$content .= '<div class="field"><input type="checkbox" name="checkvalidity" value="1" checked="checked" /> '.$GLOBALS['I18N']->get('Check to skip emails that are not valid').'</div>';
$content .= '<div class="field"><input type="submit" name="doimport" value="'.$GLOBALS['I18N']->get('Import emails').'" ></div>';
$content .= '<div class="field"><textarea name="importcontent" rows="10" cols="40"></textarea></div>';

$panel = new UIPanel('',$content);
print $panel->display();
print '</form>';
