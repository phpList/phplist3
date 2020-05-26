<?php

require_once dirname(__FILE__).'/accesscheck.php';

$subselectimp = ''; //# do not use subselect: https://mantis.phplist.com/view.php?id=16725
@ob_end_flush();

if (!ALLOW_IMPORT) {
    echo '<p class="information">'.s('import is not available').'</p>';

    return;
}

//# most basic possible import: big text area to paste emails in
$selected_lists = array();

//# this needs to be outside of anything, so that (ajaxed) addition of a list can be processed
$selected_lists = getSelectedLists('importlists');

$actionresult = '';
$content = '';

if (!empty($_POST['importcontent'])) {
    $lines = explode("\n", $_POST['importcontent']);
    $count['imported'] = 0;
    $count['duplicate'] = 0;
    $count['processed'] = 0;
    $count['addedtolist'] = 0;
    $count['invalid'] = 0;
    $count['foundonblacklist'] = 0;

    $rejectReport = array(
        'invalid' => '',
    );

    $total = count($lines);
    foreach ($lines as $line) {
        if (trim($line) == '') {
            continue;
        }

        //# do some basic clearing
        $line = cleanEmail($line);
        $uniqid = getUniqid();

        if (!empty($_POST['checkvalidity'])) {
            $isValid = validateEmail($line);
        } else {
            $isValid = true;
        }
        if (!empty($_POST['confirm'])) {
            $isConfirmed = '1';
        } else {
            $isConfirmed= '0';
        }

        if ($isValid) {
            //# I guess everyone will import all their users wanting to receive HTML ....
            $query = sprintf('insert into %s (email,entered,htmlemail,confirmed,uniqid)
                values("%s",now(),1,"%d","%s")', $tables['user'], $line, $isConfirmed, $uniqid);
            $result = Sql_query($query, 1);
            $userid = Sql_insert_id();
            if (empty($userid)) {
                ++$count['duplicate'];
                //# mark the subscriber confirmed, don't touch blacklisted
                //# hmm, maybe not, can be done on the reconcile page
                //   Sql_Query(sprintf('update %s set confirmed = 1 where email = "%s"', $tables["user"], $line));
                $idreq = Sql_Fetch_Row_Query(sprintf('select id from %s where email = "%s"', $tables['user'], $line));
                $userid = $idreq[0];
            } else {
                ++$count['imported'];
                addUserHistory($line, s('import_by').' '.adminName(), '');
            }

            //# do not add them to the list(s) when blacklisted
            $isBlackListed = isBlackListed($line);
            if (!$isBlackListed) {
                $addition = false;
                foreach ($selected_lists as $k => $listid) {
                    $query = 'insert ignore into '.$tables['listuser']." (userid,listid,entered) values($userid,$listid,now())";
                    $result = Sql_query($query);
                    $addition = $addition || Sql_Affected_Rows() == 1;
                }
                if ($addition) {
                    ++$count['addedtolist'];
                }
            } else {
                //# mark blacklisted, just in case ##17288
                Sql_Query(sprintf('update %s set blacklisted = 1 where id = %d', $tables['user'], $userid));
                ++$count['foundonblacklist'];
            }
        } else {
            ++$count['invalid'];
            $rejectReport['invalid'] .= "\n".$line; //# @TODO hmm, this can blow up
        }

        ++$count['processed'];
        if ($count['processed'] % 100 == 0) {
            echo $count['processed'].' / '.$total.' '.s('Imported').'<br/>';
            flush();
        }
    }
    $report = s('%d lines processed', $count['processed']).PHP_EOL;
    $report .= s('%d email addresses added to the list(s)', $count['addedtolist']).PHP_EOL;
    $report .= s('%d new email addresses imported', $count['imported']).PHP_EOL;
    $report .= s('%d email addresses already existed in the database', $count['duplicate']).PHP_EOL;
    if (!empty($count['invalid'])) {
        $report .= s('%d invalid email addresses', $count['invalid']).PHP_EOL;
        $report .= s('Invalid addresses will be reported in the report that is sent to %s',
                getConfig('admin_address')).PHP_EOL;
    }
    if ($count['foundonblacklist']) {
        $report .= s('%d addresses were blacklisted and have not been subscribed to the list',
                $count['foundonblacklist']).PHP_EOL;
    }

    echo ActionResult(nl2br($report));

    if ($_GET['page'] == 'importsimple') {
        if (!empty($_GET['list'])) {
            $toList = sprintf('&list=%d', $_GET['list']);
        } else {
            $toList = '';
        }
        echo '<div class="actions">
    '
            .PageLinkButton('send&new=1'.$toList, s('Send a campaign'))
            .PageLinkButton('importsimple', s('Import some more emails'))

            .'</div>';
    }

    if (!empty($rejectReport['invalid'])) {
        $report .= "\n\n".s('Rejected email addresses').":\n";
        $report .= $rejectReport['invalid'];
    }

    sendMail(getConfig('admin_address'), s('phplist Import Results'), $report);
    foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
        $plugin->importReport($report);
    }

    return;
}

if (!isSuperUser()) {
    $access = accessLevel('import1');
    switch ($access) {
        case 'owner':
            $subselectimp = ' where owner = '.$_SESSION['logindetails']['id'];
            break;
        case 'all':
            $subselectimp = '';
            break;
        case 'none':
        default:
            $subselectimp = ' where id = 0';
            break;
    }
}

if (isset($_GET['list'])) {
    $id = sprintf('%d', $_GET['list']);
    if (!empty($subselectimp)) {
        $subselectimp .= ' and id = '.$id;
    } else {
        $subselectimp .= ' where id = '.$id;
    }
}
//print PageLinkDialog('addlist',s('Add a new list'));
echo FormStart(' enctype="multipart/form-data" name="import"');

$result = Sql_query('SELECT id,name FROM '.$tables['list']."$subselectimp ORDER BY listorder");
$total = Sql_Num_Rows($result);
$c = 0;
if ($total == 1) {
    $row = Sql_fetch_array($result);
    $content .= sprintf('<input type="hidden" name="listname[%d]" value="%s"><input type="hidden" name="importlists[%d]" value="%d">'.s('Adding subscribers').' <b>%s</b>',
        $c, disableJavascript(stripslashes($row['name'])), $c, $row['id'], disableJavascript(stripslashes($row['name'])));
} else {
    $content .= '<p>'.s('Select the lists to add the emails to').'</p>';
    $content .= ListSelectHTML($selected_lists, 'importlists', $subselectimp);
}

$content .= '<p class="information">'.
    s('Please enter the emails to import, one per line, in the box below and click "Import Emails"');
//s('<b>Warning</b>: the emails you import will not be checked on validity. You can do this later on the "reconcile subscribers" page.');
$content .= '</p>';
$content .= '<div class="field"><textarea name="importcontent" rows="10" cols="40"></textarea></div>';
$content .= '<div class="field"><input type="checkbox" name="checkvalidity" value="1" checked="checked" /> '.s('Check to skip emails that are not valid').'</div>';
$content .= '<div class="field"><input type="checkbox" name="confirm" value="1" checked="checked" /> '.s('Confirm email addresses by default').'</div>';
$content .= '<div class="field"><input type="submit" name="doimport" value="'.s('Import emails').'" ></div>';

$panel = new UIPanel('', $content);
echo $panel->display();
echo '</form>';
