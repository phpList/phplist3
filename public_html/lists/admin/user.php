<?php

require_once dirname(__FILE__).'/accesscheck.php';
require_once dirname(__FILE__).'/date.php';

if (!defined('PHPLISTINIT')) {
    exit;
}

$id = sprintf('%d', isset($_GET['id']) ? $_GET['id'] : 0);
$delete = sprintf('%d', isset($_GET['delete']) ? $_GET['delete'] : 0);
$start = isset($_GET['start']) ? sprintf('%d', $_GET['start']) : 0;
if (isset($_GET['findby'])) {
    $findby = $_GET['findby'];
} else {
    $findby = '';
}
if (isset($_GET['find'])) {
    $find = $_GET['find']; //# I guess we should check on validity of it as well
} else {
    $find = '';
}

$date = new Date();
if (isset($_GET['find'])) {
    $find = preg_replace('/\W/', '', $_GET['find']);
} else {
    $find = '';
}
if (isset($_GET['findby'])) {
    $findby = preg_replace('/\W/', '', $_GET['findby']);
} else {
    $findby = '';
}

$access = accessLevel('user');
switch ($access) {
    case 'owner':
        $subselect = sprintf(' and %s.owner = %d', $tables['list'], $_SESSION['logindetails']['id']);
        $subselect_where = sprintf(' where %s.owner = %d', $tables['list'], $_SESSION['logindetails']['id']);
        break;
    case 'all':
        $subselect = '';
        $subselect_where = '';
        break;
    case 'view':
        $subselect = '';
        if (count($_POST)) {
            echo Error(s('You only have privileges to view this page, not change any of the information'));

            return;
        }
        break;
    case 'none':
    default:
        $subselect = ' and '.$tables['list'].'.id = 0';
        $subselect_where = ' where '.$tables['list'].'.owner = 0';
        break;
}

require dirname(__FILE__).'/structure.php';

$struct = $DBstruct['user'];
$feedback = '';

if (!empty($_POST['change']) && ($access == 'owner' || $access == 'all')) {
    if (!verifyToken()) {
        echo Error(s('Invalid security token, please reload the page and try again'));

        return;
    }
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        //# let's not validate here, an admin can add anything as an email, if they like, well, except for HTML
        $email = trim(strip_tags($_POST['email']));
    } else {
        $email = '';
    }

    // validate that an email address has been entered
    if ($email == '') {
        $_SESSION['action_result'] = s('email address cannot be empty');
        $location = $id == 0 ? 'user' : "user&id=$id";
        Redirect($location);
    }

    if ($id == 0) {
        $id = addNewUser($email);
        Redirect("user&id=$id");
    }

    // validate that the email address does not already exist
    $queryResult = Sql_Fetch_Row_Query(sprintf("select 1 from %s where email = '%s' and id != %d", $tables['user'], sql_escape($email), $id));

    if ($queryResult) {
        $_SESSION['action_result'] = s('email address %s already exists', $email);
        Redirect("user&id=$id");
    }

    /************ BEGIN <whitout_error IF block>  (end in line 264) **********************/
    // read the current values to compare changes
    $old_data = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d', $tables['user'], $id));
    $old_data = array_merge($old_data, getUserAttributeValues('', $id));

    // and membership of lists
    $old_listmembership = array();
    $req = Sql_Query("select * from {$tables['listuser']} where userid = $id");
    while ($row = Sql_Fetch_Array($req)) {
        $old_listmembership[$row['listid']] = listName($row['listid']);
    }

    foreach ($struct as $key => $val) {
        if (is_array($val)) {
            if (isset($val[1]) && strpos($val[1], ':')) {
                list($a, $b) = explode(':', $val[1]);
            } else {
                $a = $b = '';
            }
            if (strpos($a, 'sys') === false && $val[1]) {
                if ($key == 'password') {
                    if (!empty($_POST[$key])) {
                        Sql_Query("update {$tables['user']} set $key = \"".encryptPass($_POST[$key])."\" where id = $id");
                    }
                } elseif ($key == 'email') { ## we don't want html in the email, but other fields, we may
                    if (!empty($email)) {
                        Sql_Query("update {$tables['user']} set $key = \"".$email."\" where id = $id");
                    }
                } else {
                    if ($key != 'password' || !empty($_POST[$key])) {
                        if ($key == 'password') {
                            $_POST[$key] = hash('sha256', $_POST[$key]);
                        }

                        Sql_Query("update {$tables['user']} set $key = \"".sql_escape($_POST[$key])."\" where id = $id");
                    }
                }
            } elseif ((!$require_login || ($require_login && isSuperUser())) && $key == 'confirmed') {
                Sql_Query("update {$tables['user']} set $key = \"".sql_escape($_POST[$key])."\" where id = $id");
            }
        }
    }

    if (!empty($_FILES) && is_array($_FILES)) { //# only avatars are files
        foreach ($_FILES['attribute']['name'] as $key => $val) {
            if (!empty($_FILES['attribute']['name'][$key])) {
                $tmpnam = $_FILES['attribute']['tmp_name'][$key];
                $size = $_FILES['attribute']['size'][$key];

                if ($size < MAX_AVATAR_SIZE) {
                    $avatar = file_get_contents($tmpnam);
                    Sql_Query(sprintf('replace into %s (userid,attributeid,value)
             values(%d,%d,"%s")', $tables['user_attribute'], $id, $key, base64_encode($avatar)));
                } elseif ($size) {
                    echo Error(s('Uploaded avatar file too big'));
                }
            }
        }
    }

    if (isset($_POST['attribute']) && is_array($_POST['attribute'])) {
        foreach ($_POST['attribute'] as $key => $val) {
            Sql_Query(sprintf('replace into %s (userid,attributeid,value)
       values(%d,%d,"%s")', $tables['user_attribute'], $id, $key, sql_escape($val)));
        }
    }

    if (isset($_POST['dateattribute']) && is_array($_POST['dateattribute'])) {
        foreach ($_POST['dateattribute'] as $attid => $fields) {
            if (isset($fields['novalue'])) {
                $value = '';
            } else {
                $value = sprintf('%04d-%02d-%02d', $fields['year'], $fields['month'], $fields['day']);
            }
            Sql_Query(sprintf('replace into %s (userid,attributeid,value)
       values(%d,%d,"%s")', $tables['user_attribute'], $id, $attid, $value));
        }
    }

    if (isset($_POST['cbattribute']) && is_array($_POST['cbattribute'])) {
        foreach ($_POST['cbattribute'] as $key => $val) {
            if (isset($_POST['attribute'][$key]) && $_POST['attribute'][$key] == 'on') {
                Sql_Query(sprintf('replace into %s (userid,attributeid,value)
         values(%d,%d,"on")', $tables['user_attribute'], $id, $key));
            } else {
                Sql_Query(sprintf('replace into %s (userid,attributeid,value)
         values(%d,%d,"")', $tables['user_attribute'], $id, $key));
            }
        }
    }

    if (isset($_POST['cbgroup']) && is_array($_POST['cbgroup'])) {
        foreach ($_POST['cbgroup'] as $key => $val) {
            $field = 'cbgroup'.$val;
            if (isset($_POST[$field]) && is_array($_POST[$field])) {
                $newval = array();
                foreach ($_POST[$field] as $fieldval) {
                    array_push($newval, sprintf('%0'.$checkboxgroup_storesize.'d', $fieldval));
                }
                $value = implode(',', $newval);
            } else {
                $value = '';
            }
            Sql_Query(sprintf('replace into %s (userid,attributeid,value)
       values(%d,%d,"%s")', $tables['user_attribute'], $id, $val, $value));
        }
    }

    $new_lists = array_values($_POST['subscribe']);
    $new_subscriptions = array();
    array_shift($new_lists); // remove dummy
    foreach ($new_lists as $list) {
        $listID = sprintf('%d', $list);
        $new_subscriptions[$listID] = listName($listID);
    }

    $subscribed_to = array_diff_assoc($new_subscriptions, $old_listmembership);
    $unsubscribed_from = array_diff_assoc($old_listmembership, $new_subscriptions);

    // submitting page now saves everything, so check is not necessary
    if ($subselect == '') {
        foreach ($unsubscribed_from as $listId => $listName) {
            Sql_Query(sprintf('delete from %s where userid = %d and listid = %d', $tables['listuser'], $id,
                $listId));
            $feedback .= '<br/>'.sprintf(s('Subscriber removed from list %s'), $listName);
        }
    } elseif (count($unsubscribed_from)) {
        // only unsubscribe from the lists of this admin
        $req = Sql_Query("select id,name from {$tables['list']} $subselect_where and id in (".implode(',',
                array_keys($unsubscribed_from)).')');
        while ($row = Sql_Fetch_Row($req)) {
            Sql_Query("delete from {$tables['listuser']} where userid = $id and listid = $row[0]");
            $feedback .= '<br/>'.sprintf(s('Subscriber removed from list %s'), $row[1]);
        }
    }
    if (count($subscribed_to)) {
        foreach ($subscribed_to as $listID => $listName) {
            Sql_Query("insert into {$tables['listuser']} (userid,listid,entered,modified) values($id,$listID,now(),now())");
            $feedback .= '<br/>'.sprintf(s('Subscriber added to list %s'), $listName);
        }
        $feedback .= '<br/>';
    }
    $history_entry = '';
    $current_data = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d', $tables['user'], $id));
    $current_data = array_merge($current_data, getUserAttributeValues('', $id));

    foreach ($current_data as $key => $val) {
        if (!is_numeric($key)) {
            if (isset($old_data[$key]) && $old_data[$key] != $val && $key != 'modified') {
                if ($old_data[$key] == '') {
                    $old_data[$key] = s('(no data)');
                }
                $history_entry .= "$key = $val\n".s('changed from')." $old_data[$key]\n";
            }
        }
    }
    if (!$history_entry) {
        $history_entry = "\n".s('No data changed')."\n";
    }

    foreach ($subscribed_to as $key => $desc) {
        $history_entry .= s('Subscribed to %s', $desc)."\n";
    }
    foreach ($unsubscribed_from as $key => $desc) {
        $history_entry .= s('Unsubscribed from %s', $desc)."\n";
    }

    addUserHistory($email, s('Update by %s', adminName($_SESSION['logindetails']['id'])), $history_entry);
    $_SESSION['action_result'] = s('Changes saved').$feedback;
    Redirect("user&id=$id");
    /************ END <whitout_error IF block>  (start in line 71) **********************/
}

if (isset($delete) && $delete && $access != 'view') {
    verifyCsrfGetToken();
    // delete the index in delete
    $_SESSION['action_result'] = s('Deleting').' '.s('Subscriber').' '.s('ID')." $delete ..\n";
    if ($require_login && !isSuperUser()) {
        // If the user does not permission to permanently delete, delete
        // subscriptoins instead

        // Get all lists subscriber is a member of
        $lists = Sql_query("
            SELECT
                listid
            FROM
                {$tables['listuser']},{$tables['list']}
            WHERE
                userid = ".$delete."
                AND $tables[listuser].listid = $tables[list].id
                $subselect
        ");
        while ($lst = Sql_fetch_array($lists)) {
            Sql_query("
                DELETE FROM
                    {$tables['listuser']}
                WHERE
                    userid = $delete
                    AND listid = $lst[0]
            ");
        }
    } else {
        // permanently delete subscriber
        deleteUser($delete);
    }
    $_SESSION['action_result'] .= s('Done')."\n";
    Redirect('user');
}

/********* NORMAL FORM DISPLAY ***********/
$membership = '';
$subscribed = array();
if ($id) {
    $result = Sql_query(sprintf('select * from %s where id = %d', $tables['user'], $id));

    if (!Sql_Affected_Rows()) {
        Fatal_Error(s('No such subscriber'));

        return;
    }

    $user = sql_fetch_array($result);
    $lists = Sql_query("SELECT listid,name FROM {$tables['listuser']},{$tables['list']} where userid = ".$user['id']." and $tables[listuser].listid = $tables[list].id $subselect ");

    while ($lst = Sql_fetch_array($lists)) {
        $membership .= '<li>'.PageLink2('editlist', cleanListName($lst['name']), 'id='.$lst['listid']).'</li>';
        array_push($subscribed, $lst['listid']);
    }

    if (!$membership) {
        $membership = s('No Lists');
    }

    echo '<div class="actions">';
    echo PageLinkButton("exportuserdata&amp;id=$id", s('Download subscriber data'));
    if (!isBlackListed($user['email'])) {
        echo
            '<a 
            class="confirm btn btn-default" 
            href="' . getConfig('preferencesurl') . "&amp;uid=" . $user['uniqid'] . '">' .
            s('Preferences page') . '</a>';
    }

    // Trigger hook to add additional buttons from plugins
    if (!empty($GLOBALS['config']['plugins']) && is_array($GLOBALS['config']['plugins'])) {
        foreach ($GLOBALS['config']['plugins'] as $pluginName => $plugin) {
            echo $plugin->userpageLink($id);
        }
    }

    if ($access == 'all') {
        $delete = new ConfirmButton(
            htmlspecialchars(s('Are you sure you want to remove this subscriber from the system.')),
            PageURL2("user&delete=$id".addCsrfGetToken(), 'button', s('remove subscriber')),
            s('remove subscriber'),'','btn btn-lg btn-danger pull-right');
        echo $delete->show();
    }

    echo '</div><div class="clearfix"></div>';
} else {
    if (!empty($_POST['subscribe'])) {
        foreach ($_POST['subscribe'] as $idx => $listid) {
            array_push($subscribed, $listid);
        }
    }

    $id = 0;
    echo '<h3>'.s('Add a new subscriber').'</h3>';
    if (empty($_POST['email'])) {
        echo formStart();
        echo s('Email address').': '.'<input type="text" name="email" value="" />';
        echo '<input type="submit" name="change" value="'.s('Continue').'">';
        echo '</form>';

        return;
    }
}

echo formStart('enctype="multipart/form-data"');
if (empty($list)) {
    $list = '';
}
echo '<input type="hidden" name="list" value="'.$list.'" /><input type="hidden" name="id" value="'.$id.'" />';
if (empty($returnpage)) {
    $returnpage = '';
}
if (empty($returnoption)) {
    $returnoption = '';
}
echo '<input type="hidden" name="returnpage" value="'.$returnpage.'" /><input type="hidden" name="returnoption" value="'.$returnoption.'" />';

reset($struct);

$userdetailsHTML = $mailinglistsHTML = '';
if (isBlackListed($user['email'])) {
    $userdetailsHTML .= '<h3 class="alert-info">'.s('Subscriber is blacklisted. No emails will be sent to this email address.').'</h3>';
}
$userdetailsHTML .= '<table class="userAdd" border="1">';

foreach ($struct as $key => $val) {
    @list($a, $b) = explode(':', $val[1]);

    if (!isset($user[$key])) {
        $user[$key] = '';
    }

    if ($key == 'confirmed') {
        if (!$require_login || ($require_login && isSuperUser())) {
            $userdetailsHTML .= sprintf('<tr><td class="dataname">%s (1/0)</td><td><input type="text" name="%s" value="%s" size="5" /></td></tr>'."\n",
                s($b), $key, htmlspecialchars(stripslashes($user[$key])));
        } else {
            $userdetailsHTML .= sprintf('<tr><td class="dataname">%s</td><td>%s</td></tr>', $b,
                stripslashes($user[$key]));
        }
    } elseif ($key == 'password') {
        $userdetailsHTML .= sprintf('<tr><td class="dataname">%s</td><td><input type="text" name="%s" value="%s" size="30" /></td></tr>'."\n",
            $val[1], $key, '');
    } elseif ($key == 'blacklisted') {
        $userdetailsHTML .= sprintf('<tr><td class="dataname">%s</td><td>%s', s($b),
            $user[$key] || isBlackListed($user['email']) ? s('Yes') : s('No'));

        if (!($user[$key] || isBlackListed($user['email']))) {
            $userdetailsHTML .= '<span class="fright button">'.PageLinkAjax('user&blacklist=1&id='.$user['id'],
                    s('Add to blacklist')).'</span>';
        } elseif (UNBLACKLIST_IN_PROFILE) {
            $userdetailsHTML .= '<span class="fright button">'.PageLinkAjax('user&unblacklist=1&id='.$user['id'],
                    s('Remove from blacklist')).'</span>';
        }
        $userdetailsHTML .= '</td></tr>';
    } else {
        if (!strpos($key, '_')) {
            if (strpos($a, 'sys') !== false) {
                if ($key === 'modified' || $key === 'entered' || $key === 'passwordchanged') {
                    $userdetailsHTML .= sprintf('<tr><td class="dataname">%s</td><td>%s</td></tr>',
                        s($b), stripslashes(formatDateTime($user[$key])));
                } else {
                    $userdetailsHTML .= sprintf('<tr><td class="dataname">%s</td><td>%s</td></tr>',
                        s($b), stripslashes($user[$key]));
                }
            } elseif ($val[1]) {
                $userdetailsHTML .= sprintf('<tr><td class="dataname">%s</td><td><input type="text" name="%s" value="%s" size="30" /></td></tr>'."\n",
                    s($val[1]), $key, htmlspecialchars(stripslashes($user[$key])));
            }
        }
    }
}

if (empty($GLOBALS['config']['hide_user_attributes']) && !defined('HIDE_USER_ATTRIBUTES')) {
    $res = Sql_Query("select * from $tables[attribute] order by listorder");

    while ($row = Sql_fetch_array($res)) {
        if (!empty($id)) {
            $val_req = Sql_Fetch_Row_Query("select value from $tables[user_attribute] where userid = $id and attributeid = $row[id]");
            $row['value'] = $val_req[0];
        } elseif (!empty($_POST['attribute'][$row['id']])) {
            $row['value'] = $_POST['attribute'][$row['id']];
        } else {
            $row['value'] = '';
        }

        if ($row['type'] == 'date') {
            $namePrefix = sprintf('dateattribute[%d]', $row['id']);
            $novalue = trim($row['value']) == '' ? 'checked' : '';
            $userdetailsHTML .= sprintf(
                '<tr><td class="dataname">%s<!--%s--></td>
        <td>%s&nbsp; Not set: <input type="checkbox" name="%s[novalue]" %s /></td></tr>' ."\n",
                stripslashes($row['name']),
                $row['value'],
                $date->showInput($namePrefix, '', $row['value']),
                $namePrefix,
                $novalue
            );
        } elseif ($row['type'] == 'checkbox') {
            $checked = $row['value'] == 'on' ? 'checked="checked"' : '';
            $userdetailsHTML .= sprintf('<tr><td class="dataname">%s</td><td><input class="attributeinput" type="hidden" name="cbattribute[%d]" value="%d" />
                        <input class="attributeinput" type="checkbox" name="attribute[%d]" value="on" %s />
              </td></tr>' ."\n", stripslashes($row['name']), $row['id'], $row['id'], $row['id'], $checked);
        } elseif ($row['type'] == 'checkboxgroup') {
            $userdetailsHTML .= sprintf('
           <tr><td valign="top" class="dataname">%s</td><td>%s</td>
           </tr>', stripslashes($row['name']), UserAttributeValueCbGroup($id, $row['id']));
        } elseif ($row['type'] == 'textarea') {
            $userdetailsHTML .= sprintf('
           <tr><td valign="top" class="dataname">%s</td><td><textarea name="attribute[%d]" rows="10" cols="40" class="wrap virtual">%s</textarea></td>
           </tr>', stripslashes($row['name']), $row['id'], str_replace(array('>', '<'), array('&gt;', '&lt;'), stripslashes($row['value'])));
        } elseif ($row['type'] == 'avatar') {
            $userdetailsHTML .= sprintf('<tr><td valign="top" class="dataname">%s</td><td>',
                stripslashes($row['name']));
            if ($row['value']) {
                $userdetailsHTML .= sprintf('<img src="./?page=avatar&amp;user=%d&amp;avatar=%s" /><br/>', $id,
                    $row['id']);
            }
            $userdetailsHTML .= sprintf('<input type="file" name="attribute[%d]" /><br/>MAX: %d Kbytes</td>
           </tr>', $row['id'], MAX_AVATAR_SIZE / 1024);
        } else {
            if ($row['type'] != 'textline' && $row['type'] != 'hidden') {
                $userdetailsHTML .= sprintf("<tr><td class='dataname'>%s</td><td>%s</td></tr>\n",
                    stripslashes($row['name']), UserAttributeValueSelect($id, $row['id']));
            } else {
                $userdetailsHTML .= sprintf('<tr><td class="dataname">%s</td><td><input class="attributeinput" type="text" name="attribute[%d]" value="%s" size="30" /></td></tr>'."\n",
                    $row['name'], $row['id'], str_replace('"', '&#x22;', stripslashes($row['value'])));
            }
        }
    }
}

$userdetailsHTML .= '</table>';

if ($access != 'view') {
    $userdetailsHTML .= '<input class="submit" type="submit" name="change" value="'.s('Save Changes').'" />';
}

$mailinglistsHTML .= '<h3>'.s('Mailinglist membership').':</h3>';
// a dummy entry, to make the array show up in POST even if all checkboxes are unchecked
$mailinglistsHTML .= '<input type="hidden" name="subscribe[]" value="-1" />';
$mailinglistsHTML .= '<table class="userListing" border="1"><tr>';
$req = Sql_Query("select * from {$tables['list']} $subselect_where order by listorder,name");
$c = 0;
while ($row = Sql_Fetch_Array($req)) {
    ++$c;
    if ($c % 1 == 0) {
        $mailinglistsHTML .= '</tr><tr>';
    }
    if (in_array($row['id'], $subscribed)) {
        $bgcol = '#F7E7C2';
        $subs = 'checked="checked"';
    } else {
        $bgcol = '#ffffff';
        $subs = '';
    }
    $mailinglistsHTML .= sprintf('<td class="tdcheck" bgcolor="%s"><input type="checkbox" name="subscribe[]" value="%d" %s /> %s</td>',
        $bgcol, $row['id'], $subs, stripslashes(cleanListName($row['name'])));
}
$mailinglistsHTML .= '</tr>';
if ($access != 'view') {
    $mailinglistsHTML .= '<tr><td class="bgwhite"><input class="submit" type="submit" name="change" value="'.s('Save Changes').'" /></td></tr>';
}

$mailinglistsHTML .= '</table>';

echo '<div class="tabbed">';
echo '<ul>';
echo '<li><a href="#details">'.s('Details').'</a></li>';
echo '<li><a href="#lists">'.s('Lists').'</a></li>';
echo '<li><a href="./?page=pageaction&action=campaigns&ajaxed=true&id='.$id .addCsrfGetToken().'">'.s('Campaigns').'</a></li>';
echo '<li><a href="./?page=pageaction&action=bounces&ajaxed=true&id='.$id .addCsrfGetToken().'">'.s('Bounces').'</a></li>';
echo '<li><a href="./?page=pageaction&action=subscription&ajaxed=true&id='.$id .addCsrfGetToken().'">'.s('Subscription').'</a></li>';

echo '</ul>';

$p = new UIPanel('', $userdetailsHTML);
echo '<div id="details">'.$p->display().'</div>';

$p = new UIPanel('', $mailinglistsHTML);
echo '<div id="lists">'.$p->display().'</div>';

echo '</div>'; //# end of tabbed

if (isset($_GET['unblacklist'])) {
    $unblacklist = sprintf('%d', $_GET['unblacklist']);
    unBlackList($unblacklist);
    Redirect('user&id='.$unblacklist);
}

echo '</form>';
