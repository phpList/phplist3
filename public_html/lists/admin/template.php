<?php

require_once dirname(__FILE__) . '/accesscheck.php';

$actionresult = '';

if (!empty($_FILES['file_template']) && is_uploaded_file($_FILES['file_template']['tmp_name'])) {
    $content = file_get_contents($_FILES['file_template']['tmp_name']);
} elseif (isset($_POST['template'])) {
    $content = $_POST['template'];
} else {
    $content = '';
}
$sendtestresult = '';
$testtarget = getConfig('admin_address');
$systemTemplateID = getConfig('systemmessagetemplate');

if (isset($_REQUEST['id'])) {
    $id = sprintf('%d', $_REQUEST['id']);
} else {
    $id = 0;
}

function getTemplateImages($content)
{
    $html_images = array();
    $image_types = array(
        'gif' => 'image/gif',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpe' => 'image/jpeg',
        'bmp' => 'image/bmp',
        'png' => 'image/png',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'swf' => 'application/x-shockwave-flash',
    );
    // Build the list of image extensions
    while (list($key) = each($image_types)) {
        $extensions[] = $key;
    }
    preg_match_all('/"([^"]+\.(' . implode('|', $extensions) . '))"/Ui', stripslashes($content), $images);
    while (list($key, $val) = each($images[1])) {
        if (isset($html_images[$val])) {
            ++$html_images[$val];
        } else {
            $html_images[$val] = 1;
        }
    }

    return $html_images;
}

function getTemplateLinks($content)
{
    preg_match_all('/href="([^"]+)"/Ui', stripslashes($content), $links);

    return $links[1];
}

$msg = '';
$checkfullimages = !empty($_POST['checkfullimages']) ? 1 : 0;
$checkimagesexist = !empty($_POST['checkimagesexist']) ? 1 : 0;
$checkfulllinks = !empty($_POST['checkfulllinks']) ? 1 : 0;
$baseurl = '';

if (!empty($_POST['action']) && $_POST['action'] == 'addimages') {
    if (!$id) {
        $msg = $GLOBALS['I18N']->get('No such template');
    } else {
        $content_req = Sql_Fetch_Row_Query("select template from {$tables['template']} where id = $id");
        $images = getTemplateImages($content_req[0]);

        if (count($images)) {
            include 'class.image.inc';
            $image = new imageUpload();
            while (list($key, $val) = each($images)) {
                # printf('Image name: <b>%s</b> (%d times used)<br />',$key,$val);
                $image->uploadImage($key, $id);
            }
            $msg = $GLOBALS['I18N']->get('Images stored');
        } else {
            $msg = $GLOBALS['I18N']->get('No images found');
        }
    }
    $_SESSION['action_result'] = $msg . '<br/>' . s('Template saved and ready for use in campaigns');
    Redirect('templates');

    return;
    //print '<p class="actionresult">'.$msg.'</p>';
    //$msg = '';
} elseif (!empty($_POST['save']) || !empty($_POST['sendtest'])) { ## let's save when sending a test
    $templateok = 1;
    $title = $_POST['title'];
    if (!empty($title) && strpos($content, '[CONTENT]') !== false) {
        $images = getTemplateImages($content);

        //   var_dump($images);

        if (($checkfullimages || $checkimagesexist) && count($images)) {
            foreach ($images as $key => $val) {
                if (!preg_match('#^https?://#i', $key)) {
                    if ($checkfullimages) {
                        $actionresult .= $GLOBALS['I18N']->get('Image') . " $key => " . $GLOBALS['I18N']->get('"not full URL') . "<br/>\n";
                        $templateok = 0;
                    }
                } else {
                    if ($checkimagesexist) {
                        $imageFound = testUrl($key);
                        if ($imageFound != 200) {
                            $actionresult .= $GLOBALS['I18N']->get('Image') . " $key => " . $GLOBALS['I18N']->get('does not exist') . "<br/>\n";
                            $templateok = 0;
                        }
                    }
                }
            }
        }
        if ($checkfulllinks) {
            $links = getTemplateLinks($content);
            foreach ($links as $key => $val) {
                if (!preg_match('#^https?://#i', $val) && !preg_match('#^mailto:#i', $val)
                    && !(strtoupper($val) == '[PREFERENCESURL]' || strtoupper($val) == '[UNSUBSCRIBEURL]' || strtoupper($val) == '[BLACKLISTURL]' || strtoupper($val) == '[FORWARDURL]' || strtoupper($val) == '[CONFIRMATIONURL]')
                ) {
                    $actionresult .= $GLOBALS['I18N']->get('Not a full URL') . ": $val<br/>\n";
                    $templateok = 0;
                }
            }
        }
    } else {
        if (!$title) {
            $actionresult .= $GLOBALS['I18N']->get('No Title') . '<br/>';
        } else {
            $actionresult .= $GLOBALS['I18N']->get('Template does not contain the [CONTENT] placeholder') . '<br/>';
        }
        $templateok = 0;
    }
    if ($templateok) {
        if (!$id) {
            Sql_Query(sprintf('insert into %s (title) values("%s")', $tables['template'], sql_escape($title)));
            $id = Sql_Insert_id();
        }
        Sql_Query(sprintf('update %s set title = "%s",template = "%s" where id = %d',
            $tables['template'], sql_escape($title), sql_escape($content), $id));
        Sql_Query(sprintf('select * from %s where filename = "%s" and template = %d',
            $tables['templateimage'], 'powerphplist.png', $id));
        if (!Sql_Affected_Rows()) {
            Sql_Query(sprintf('insert into %s (template,mimetype,filename,data,width,height)
      values(%d,"%s","%s","%s",%d,%d)',
                $tables['templateimage'], $id, 'image/png', 'powerphplist.png',
                $newpoweredimage,
                70, 30));
        }
        $actionresult .= '<p class="information">' . s('Template saved') . '</p>';

        ## ##17419 don't prompt for remote images that exist
        $missingImages = array();
        while (list($key, $val) = each($images)) {
            $key = trim($key);
            if (preg_match('~^https?://~i', $key)) {
                $imageFound = testUrl($key);
                if (!$imageFound) {
                    $missingImages[$key] = $val;
                }
            } else {
                $missingImages[$key] = $val;
            }
        }

        if (count($missingImages) && empty($_POST['sendtest'])) {
            include dirname(__FILE__) . '/class.image.inc';
            $image = new imageUpload();
            print '<h3>' . $GLOBALS['I18N']->get('Images') . '</h3><p class="information">' . $GLOBALS['I18N']->get('Below is the list of images used in your template. If an image is currently unavailable, please upload it to the database.') . '</p>';
            print '<p class="information">' . $GLOBALS['I18N']->get('This includes all images, also fully referenced ones, so you may choose not to upload some. If you upload images, they will be included in the campaigns that use this template.') . '</p>';
            print formStart('enctype="multipart/form-data" class="template1" ');
            print '<input type="hidden" name="id" value="' . $id . '" />';
            ksort($images);
            reset($images);
            while (list($key, $val) = each($images)) {
                $key = trim($key);
                if (preg_match('~^https?://~i', $key)) {
                    $missingImage = true;
                    $imageFound = testUrl($key);
                    if ($imageFound != 200) {
                        printf($GLOBALS['I18N']->get('Image name:') . ' <b>%s</b> (' . $GLOBALS['I18N']->get('%d times used') . ')<br/>',
                            $key, $val);
                        print $image->showInput($key, $val, $id);
                    }
                } else {
                    printf($GLOBALS['I18N']->get('Image name:') . ' <b>%s</b> (' . $GLOBALS['I18N']->get('%d times used') . ')<br/>',
                        $key, $val);
                    print $image->showInput($key, $val, $id);
                }
            }

            print '<input type="hidden" name="id" value="' . $id . '" /><input type="hidden" name="action" value="addimages" />
        <input class="submit" type="submit" name="addimages" value="' . $GLOBALS['I18N']->get('Save Images') . '" /></form>';
            if (empty($_POST['sendtest'])) {
                return;
            }
            #    return;
        } else {
            $_SESSION['action_result'] = s('Template was successfully saved');
#      print '<p class="information">'.$GLOBALS['I18N']->get('Template does not contain local images')."</p>";
            if (empty($_POST['sendtest'])) {
                Redirect('templates');

                return;
            }
            #    return;
        }
    } else {
        $actionresult .= $GLOBALS['I18N']->get('Some errors were found, template NOT saved!');
        $data['title'] = $title;
        $data['template'] = $content;
    }
    if (!empty($_POST['sendtest'])) {
        ## check if it's the system message template or a normal one:

        $targetEmails = explode(',', $_POST['testtarget']);
        $testtarget = '';

        if ($id == $systemTemplateID) {
            $actionresult .= '<h3>' . $GLOBALS['I18N']->get('Sending test') . '</h3>';
            foreach ($targetEmails as $email) {
                if (validateEmail($email)) {
                    $testtarget .= $email . ', ';
                    $actionresult .= $GLOBALS['I18N']->get('Sending test "Request for confirmation" to') . ' ' . $email . '  ';
                    if (sendMail($email, getConfig('subscribesubject'), getConfig('subscribemessage'))) {
                        $actionresult .= s('OK');
                    } else {
                        $actionresult .= s('FAILED');
                    }
                    $actionresult .= '<br/>';
                    $actionresult .= $GLOBALS['I18N']->get('Sending test "Welcome" to') . ' ' . $email . '  ';
                    if (sendMail($email, getConfig('confirmationsubject'), getConfig('confirmationmessage'))) {
                        $actionresult .= s('OK');
                    } else {
                        $actionresult .= s('FAILED');
                    }
                    $actionresult .= '<br/>';
                    $actionresult .= $GLOBALS['I18N']->get('Sending test "Unsubscribe confirmation" to') . ' ' . $email . '  ';
                    if (sendMail($email, getConfig('unsubscribesubject'), getConfig('unsubscribemessage'))) {
                        $actionresult .= s('OK');
                    } else {
                        $actionresult .= s('FAILED');
                    }
                } elseif (trim($email) != '') {
                    $actionresult .= '<p>' . $GLOBALS['I18N']->get('Error sending test messages to') . ' ' . htmlspecialchars($email) . '</p>';
                }
            }
        } else {
            ## Sending test emails of non system templates to be added.
            $actionresult .= '<p>' . s('Sending a test from templates only works for the system template.') . ' ' .
                s('To test your template, go to campaigns and send a test campaign using the template.') .
                '</p>';
        }
        if (empty($testtarget)) {
            $testtarget = getConfig('admin_address');
        }
        $testtarget = preg_replace('/, $/', '', $testtarget);
    }
}
if (!empty($actionresult)) {
    print '<div class="actionresult">' . $actionresult . '</div>';
}

if ($id) {
    $req = Sql_Query("select * from {$tables['template']} where id = $id");
    $data = Sql_Fetch_Array($req);
    ## keep POSTED data, even if not saved
    if (!empty($_POST['template'])) {
        $data['template'] = $content;
    }
} else {
    $data = array();
    $data['title'] = '';
    $data['template'] = '';
}

?>

<p class="information"><?php echo $msg ?></p>
<?php echo '<p class="button">' . PageLink2('templates', $GLOBALS['I18N']->get('List of Templates')) . '</p>'; ?>

<?php echo formStart(' enctype="multipart/form-data" class="template2" ') ?>
<input type="hidden" name="id" value="<?php echo $id ?>"/>
<div class="panel">
    <table class="templateForm">
        <tr>

            <td><?php echo $GLOBALS['I18N']->get('Title of this template') ?></td>
            <td><input type="text" name="title" value="<?php echo stripslashes(htmlspecialchars($data['title'])) ?>"
                       size="30"/></td>
        </tr>
        <tr>
            <td colspan="2"><?php echo s('Content of the template.') ?>
                <br/><?php echo s('The content should at least have <b>[CONTENT]</b> somewhere.') ?>
                <br/><?php echo s('You can upload a template file or paste the text in the box below'); ?></td>
        </tr>
        <tr>
            <td><?php echo s('Template file.') ?></td>
            <td><input type="file" name="file_template"/></td>
        </tr>
        <tr>
            <td colspan="2">

                <?php
                if ($GLOBALS['editorplugin']) {
                    print $GLOBALS['plugins'][$GLOBALS['editorplugin']]->editor('template',
                            stripslashes($data['template'])) . '</div>';
                } else {
                    print '<textarea name="template" id="template" cols="65" rows="20">';
                    print stripslashes(htmlspecialchars($data['template']));
                    print '</textarea>';
                }
                ?>
            </td>
        </tr>

        <!--tr>
  <td>Make sure all images<br/>start with this URL (optional)</td>
  <td><input type="text" name="baseurl" size="40" value="<?php echo htmlspecialchars($baseurl) ?>" /></td>
</tr-->
        <tr>
            <td><?php echo $GLOBALS['I18N']->get('Check that all links have a full URL') ?></td>
            <td><input type="checkbox" name="checkfulllinks" <?php echo $checkfulllinks ? 'checked="checked"' : '' ?> />
            </td>
        </tr>
        <tr>
            <td><?php echo $GLOBALS['I18N']->get('Check that all images have a full URL') ?></td>
            <td><input type="checkbox"
                       name="checkfullimages" <?php echo $checkfullimages ? 'checked="checked"' : '' ?> /></td>
        </tr>

        <?php if ($GLOBALS['can_fetchUrl']) {
            ?>
            <tr>
                <td><?php echo $GLOBALS['I18N']->get('Check that all external images exist') ?></td>
                <td><input type="checkbox"
                           name="checkimagesexist" <?php echo $checkimagesexist ? 'checked="checked"' : '' ?> /></td>
            </tr>
            <?php
        } ?>
        <tr>
            <td colspan="2"><input class="submit" type="submit" name="save"
                                   value="<?php echo $GLOBALS['I18N']->get('Save Changes') ?>"/></td>
        </tr>
    </table>
</div>
<?php $sendtest_content = sprintf('<div class="sendTest" id="sendTest">
    ' . $sendtestresult . '
    <input class="submit" type="submit" name="sendtest" value="%s"/>  %s: 
    <input type="text" name="testtarget" size="40" value="' . htmlspecialchars($testtarget) . '"/><br />%s
    </div>',
    $GLOBALS['I18N']->get('Send test message'), $GLOBALS['I18N']->get('to email addresses'),
    $GLOBALS['I18N']->get('(comma separate addresses - all must be existing subscribers)'));
$testpanel = new UIPanel($GLOBALS['I18N']->get('Send Test'), $sendtest_content);
$testpanel->setID('testpanel');
#  if ($systemTemplateID == $id) { ## for now, testing only for system message templates
print $testpanel->display();
#  }
?>

</form>
