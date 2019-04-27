<?php
require_once dirname(__FILE__) . '/accesscheck.php';

$subselect = '';
$report = '';
if (!ALLOW_IMPORT) {
    echo '<p class="information">' . $GLOBALS['I18N']->get('import is not available') . '</p>';

    return;
}

ignore_user_abort();
set_time_limit(500);
ob_end_flush();

if (!isset($GLOBALS['tmpdir'])) {
    $GLOBALS['tmpdir'] = ini_get('upload_tmp_dir');
}
if (!is_dir($GLOBALS['tmpdir']) || !is_writable($GLOBALS['tmpdir'])) {
    $GLOBALS['tmpdir'] = ini_get('upload_tmp_dir');
}
//if (ini_get("open_basedir")) {
if (!is_dir($GLOBALS['tmpdir']) || !is_writable($GLOBALS['tmpdir'])) {
    Warn($GLOBALS['I18N']->get('The temporary directory for uploading is not writable, so import will fail') . ' (' . $GLOBALS['tmpdir'] . ')');
}

$import_lists = getSelectedLists('importlists');
$_POST['importlists'] = $import_lists;

if (isset($_REQUEST['import'])) {
    if (!verifyToken()) {
        echo Error(s('Invalid security token, please reload the page and try again'));

        return;
    }

    $test_import = (isset($_POST['import_test']) && $_POST['import_test'] == 'yes');

    $omit_invalid = (isset($_POST['omit_invalid']) && $_POST['omit_invalid'] == 'yes');

    if (empty($_FILES['import_file'])) {
        Fatal_Error($GLOBALS['I18N']->get('No file was specified. Maybe the file is too big?'));

        return;
    }
    if (!$_FILES['import_file']) {
        Fatal_Error($GLOBALS['I18N']->get('File is either too large or does not exist.'));

        return;
    }
    if (filesize($_FILES['import_file']['tmp_name']) > (IMPORT_FILESIZE * 1000000)) {
        Fatal_Error($GLOBALS['I18N']->get('File too big, please split it up into smaller ones'));

        return;
    }

    //# disallow some extensions. Won't avoid all problems, but will help with the most common ones.
    $extension = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));
    if (in_array($extension, array('xls', 'ods', 'ots', 'fods', 'xlsx', 'xlt', 'dif', 'dbf', 'html', 'slk'))) {
        Fatal_Error(s('Please upload a plain text file only. You cannot use a spreadsheet. You can only upload a plain text file with one email address per line.'));

        return;
    }

    // don't send notification, but use processqueue instead
    $_POST['notify'] = 'no';
    if (!$_POST['notify'] && !$test_import) {
        Fatal_Error($GLOBALS['I18N']->get('Please choose whether to sign up immediately or to send a notification'));

        return;
    }
    $notify = $_POST['notify'];
    if (isset($_POST['throttle_import'])) {
        $throttle_import = sprintf('%d', $_POST['throttle_import']);
    } else {
        $throttle_import = 0;
    }

    if ($_FILES['import_file'] && filesize($_FILES['import_file']['tmp_name']) > 10) {
        $newfile = $GLOBALS['tmpdir'] . '/import' . $GLOBALS['installation_name'] . time();
        move_uploaded_file($_FILES['import_file']['tmp_name'], $newfile);
        if (!($fp = fopen($newfile, 'r'))) {
            Fatal_Error($GLOBALS['I18N']->get('Cannot read file. It is not readable !') . ' (' . $newfile . ')');

            return;
        }
        $email_list = fread($fp, filesize($newfile));
        fclose($fp);
    } elseif ($_FILES['import_file']) {
        Fatal_Error($GLOBALS['I18N']->get('Something went wrong while uploading the file. Empty file received. Maybe the file is too big, or you have no permissions to read it.'));

        return;
    }

    // Clean up email file
    $email_list = trim($email_list);
    $email_list = str_replace("\r", "\n", $email_list);
    $email_list = str_replace("\n\r", "\n", $email_list);
    $email_list = str_replace("\n\n", "\n", $email_list);

    if (isset($_REQUEST['import_record_delimiter'])) {
        $import_record_delimiter = $_REQUEST['import_record_delimiter'];
    } else {
        $import_record_delimiter = "\n";
    }

    // Change delimiter for new line.
    if (isset($import_record_delimiter) && $import_record_delimiter != '' && $import_record_delimiter != "\n") {
        $email_list = str_replace($import_record_delimiter, "\n", $email_list);
    }

    // Split file/emails into array
    $email_list = explode("\n", $email_list);

    // Parse the lines into records
    $hasinfo = 0;
    foreach ($email_list as $line) {
        $info = '';
        $email = trim($line); //# just take the entire line up to the first space to be the email
        if (strpos($email, ' ')) {
            list($email, $info) = explode(' ', $email);
        }
        if (!is_email($email) && $omit_invalid) {
            unset($email, $info);
        }
        //# actually looks like the "info" bit will get lost, but
        //# in a way, that doesn't matter
        $user_list[$email] = array(
            'info' => $info,
        );
    }

    if (count($email_list) > 300 && !$test_import) {
        // this is a possibly a time consuming process, so lets show a progress bar
        //  print '<script language="Javascript" type="text/javascript"> document.write(progressmeter); start();</script>';
        flush();
        // increase the memory to make sure we are not running out
        ini_set('memory_limit', '16M');
    }

    // View test output of emails
    if ($test_import) {
        echo '<p>' . $GLOBALS['I18N']->get('There should only be ONE email per line.') . ' ' . $GLOBALS['I18N']->get('If the output looks ok, go') . ' <a href="javascript:history.go(-1)">' . $GLOBALS['I18N']->get('back') . '</a> ' . $GLOBALS['I18N']->get('to resubmit for real') . '.</p>
        <p><strong>' . $GLOBALS['I18N']->get('Test output:') . '</strong></p>
        <hr/>';
        $i = 1;
        foreach ($user_list as $email => $data) {
            $email = trim($email);
            if (strlen($email) > 4) {
                echo "<b>$email</b><br/>";
                $html = '';
                foreach (array('info') as $item) {
                    if ($user_list[$email][$item]) {
                        $html .= "$item -> " . $user_list[$email][$item] . '<br/>';
                    }
                }
                if ($html) {
                    echo "<blockquote>$html</blockquote>";
                }
            }
            if ($i == 50) {
                break;
            }
            ++$i;
        }

        // Do import
    } else {
        file_put_contents($newfile . '.data', serialize($_POST));

        echo '<h3>' . s('Importing %d subscribers to %d lists, please wait', count($email_list),
                count($import_lists)) . '</h3>';
        echo $GLOBALS['img_busy'];
        echo '<div id="progresscount" style="width: 200; height: 50;">Progress</div>';
        echo '<br/> <iframe id="import1" src="./?page=pageaction&action=import1&ajaxed=true&omitinvalid=' . $omit_invalid . '&file=' . urlencode(basename($newfile)) . addCsrfGetToken() . '" scrolling="no" height="50"></iframe>';
    } // end else
    // print '<p class="button">'.PageLink2("import1",$GLOBALS['I18N']->get('Import some more emails')).'</p>';
} else {
    echo FormStart(' enctype="multipart/form-data" name="import"');

    if (!isSuperUser()) {
        $access = accessLevel('import1');
        switch ($access) {
            case 'owner':
                $subselect = ' where owner = ' . $_SESSION['logindetails']['id'];
                break;
            case 'all':
                $subselect = '';
                break;
            case 'none':
            default:
                $subselect = ' where id = 0';
                break;
        }
    }

    $result = Sql_query('SELECT id,name FROM ' . $tables['list'] . "$subselect ORDER BY listorder");
    $c = 0;
    if (Sql_Affected_Rows() == 1) {
        $row = Sql_fetch_array($result);
        printf('<input type="hidden" name="listname[%d]" value="%s"><input type="hidden" name="importlists[%d]" value="%d">' . $GLOBALS['I18N']->get('adding_users') . ' <b>%s</b>',
            $c, stripslashes($row['name']), $c, $row['id'], stripslashes($row['name']));
    } else {
        echo '<h3>' . s('Select the lists to add the emails to') . '</h3>';
        echo ListSelectHTML($import_lists, 'importlists', $subselect);
    } ?>


    <script language="Javascript" type="text/javascript">

        var fieldstocheck = new Array();
        var fieldnames = new Array();

        function addFieldToCheck(value, name) {
            fieldstocheck[fieldstocheck.length] = value;
            fieldnames[fieldnames.length] = name;
        }

    </script>
    <div class="panel">
        <div class="content">
            <table class="import1" border="1">
                <tr>
                    <td colspan="2"><?php echo $GLOBALS['I18N']->get('The file you upload will need to contain the emails you want to add to these lists. Anything after the email will be added as attribute "Info" of the Subscriber. You can specify the rest of the attributes of these subscribers below. Warning: the file needs to be plain text. Do not upload binary files like a Word Document.'); ?></td>
                </tr>
                <tr>
                    <td><?php echo $GLOBALS['I18N']->get('File containing emails:'); ?></td>
                    <td><input type="file" name="import_file"></td>
                </tr>
                <tr>
                    <td colspan="2"><?php echo $GLOBALS['I18N']->get('If you check "Test Output", you will get the list of parsed emails on screen, and the database will not be filled with the information. This is useful to find out whether the format of your file is correct. It will only show the first 50 records.'); ?></td>
                </tr>
                <tr>
                    <td><?php echo $GLOBALS['I18N']->get('Test output:'); ?></td>
                    <td><input type="checkbox" name="import_test" value="yes" checked="checked"/></td>
                </tr>
                <tr>
                    <td><?php echo s('Omit Invalid') ?>:</td>
                    <td><input type="checkbox" name="omit_invalid" checked="checked" value="yes"/></td>
                </tr>
                <!--tr><td colspan="2"><?php echo $GLOBALS['I18N']->get('If you choose "send notification email" the subscribers you are adding will be sent the request for confirmation of subscription to which they will have to reply. This is recommended, because it will identify invalid emails.'); ?></td></tr>
<tr><td><?php echo $GLOBALS['I18N']->get('Send Notification email'); ?><input type="radio" name="notify" value="yes"></td><td><?php echo $GLOBALS['I18N']->get('Make confirmed immediately'); ?><input type="radio" name="notify" value="no"></td></tr>
<tr><td colspan="2"><?php echo $GLOBALS['I18N']->get('If you are going to send notification to users, you may want to add a little delay between messages') ?></td></tr>
<tr><td><?php echo $GLOBALS['I18N']->get('Notification throttle') ?>:</td><td><input type="text" name="throttle_import" size="5"> <?php echo $GLOBALS['I18N']->get('(default is nothing, will send as fast as it can)') ?></td></tr-->
                <?php
                include_once dirname(__FILE__) . '/subscribelib2.php';
                echo ListAllAttributes(); ?>

                <tr>
                    <td><p class="input"><input type="submit" name="import"
                                                value="<?php echo $GLOBALS['I18N']->get('import'); ?>"></p></td>
                    <td>&nbsp;</td>
                </tr>
            </table>
        </div>
    </div>
    </form>
    <?php

} ?>

