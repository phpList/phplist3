<?php

require_once dirname(__FILE__).'/accesscheck.php';
$access = accessLevel('members');

if (isset($_REQUEST['id'])) {
    $id = sprintf('%d', $_REQUEST['id']);
} else {
    $id = 0;
}
if (isset($_GET['start'])) {
    $start = sprintf('%d', $_GET['start']);
} else {
    $start = 0;
}

if (isset($_GET['tab']) && $_GET['tab'] == 'unconfirmed') {
    $confirmedSelection = ' (!u.confirmed or u.blacklisted)';
    $pagingKeep = 'tab=unconfirmed';
} else {
    $pagingKeep = 'tab=confirmed';
    $confirmedSelection = ' u.confirmed and !u.blacklisted';
}
$listAll = false;

switch ($access) {
    case 'owner':
        if ($id) {
            $rs = Sql_Query(sprintf('select id from '.$tables['list'].' where owner = %d and id = %d',
                $_SESSION['logindetails']['id'], $id));
            if (!Sql_Affected_Rows()) {
                Fatal_Error($GLOBALS['I18N']->get('You do not have enough privileges to view this page'));

                return;
            }
        }
        break;
    case 'all':
    case 'view':
        $subselect = '';
        break;
    case 'none':
    default:
        if ($id) {
            Fatal_Error($GLOBALS['I18N']->get('You do not have enough privileges to view this page'));

            return;
        }
        $subselect = ' where id = 0';
        break;
}

function addUserForm($listid)
{
    //nizar 'value'
    $html = formStart(' class="membersAdd" ').'<input type="hidden" name="listid" value="'.$listid.'" />
  ' .$GLOBALS['I18N']->get('Add a user').': <input type="text" name="new" value="" size="40" id="emailsearch"/>
     <input class="submit" type="submit" name="add" value="' .$GLOBALS['I18N']->get('Add').'" />
  </form>';

    return $html;
}

if (!empty($id)) {
    echo '<h3>'.$GLOBALS['I18N']->get('Members of').' '.ListName($id).'</h3>';
    echo '<div class="actions">';
    echo '<div class="pull-right">'.PageLinkButton('editlist', $GLOBALS['I18N']->get('edit list details'), "id=$id", 'pill-l').'</div>';
    echo PageLinkButton("export&amp;list=$id", $GLOBALS['I18N']->get('Download subscribers'), '', 'pill-c');
    echo PageLinkDialog("importsimple&amp;list=$id", $GLOBALS['I18N']->get('Import Subscribers to this list'), '',
        'pill-r');
    echo '</div><div class="clearfix"></div>';
} else {
    if ($_REQUEST['id'] != 'all') {
        Redirect('list');
    } else {
        $id = 'all';
        $listAll = true;
    }
    echo '<div class="actions">';
    echo PageLinkButton('export&list=all', $GLOBALS['I18N']->get('Download subscribers'), '', 'pill-c');
    echo '</div>';
}

if (!empty($_POST['importcontent'])) {
    include dirname(__FILE__).'/importsimple.php';
}

if (isset($_REQUEST['processtags']) && $access != 'view') {
    $msg = $GLOBALS['I18N']->get('Processing').' .... <br/>';
    if (isset($_POST['tagaction']) && !empty($_POST['user']) && is_array($_POST['user'])) {
        switch ($_POST['tagaction']) {
            case 'move':
                $cnt = 0;
                foreach ($_POST['user'] as $key => $val) {
                    Sql_query(sprintf('delete from %s where listid = %d and userid = %d', $tables['listuser'], $id,
                        $key));
                    Sql_query(sprintf('insert ignore into %s (listid,userid, entered) values(%d,%d, now())', $tables['listuser'],
                        $_POST['movedestination'], $key));
                    if (Sql_Affected_rows() == 1) { // 0 means they were already on the list
                        ++$cnt;
                    }
                }
                $msg = $cnt.' '.$GLOBALS['I18N']->get('subscribers were moved to').' '.listName($_POST['movedestination']);
                break;
            case 'copy':
                $cnt = 0;
                foreach ($_POST['user'] as $key => $val) {
                    Sql_query(sprintf('insert ignore into %s (listid,userid, entered)
            values(%d,%d, now());', $tables['listuser'], $_POST['copydestination'], $key));
                    if (Sql_Affected_rows() == 1) {
                        ++$cnt;
                    }
                }
                $msg = $cnt.' '.$GLOBALS['I18N']->get('subscribers were copied to').' '.listName($_POST['copydestination']);
                break;
            case 'delete':
                $cnt = 0;
                foreach ($_POST['user'] as $key => $val) {
                    Sql_query(sprintf('delete from %s where listid = %d and userid = %d', $tables['listuser'], $id,
                        $key));
                    if (Sql_Affected_rows()) {
                        ++$cnt;
                    }
                }
                $msg = $cnt.' '.$GLOBALS['I18N']->get('subscribers were deleted from this list');
                break;
            default: // do nothing
                break;
        }
    }
    if ($_POST['tagaction_all'] != 'nothing') {

        /*
         * even though the page lists only confirmed subscribers, the action
         * on "all subscribers" should include the non-confirmed ones
         */
        $req = Sql_Query(sprintf('select userid from %s where listid = %d', $tables['listuser'], $id));
        switch ($_POST['tagaction_all']) {
            case 'move':
                $cnt = 0;
                while ($user = Sql_Fetch_Row($req)) {
                    Sql_query(sprintf('delete from %s where listid = %d and userid = %d', $tables['listuser'], $id,
                        $user[0]));
                    Sql_query(sprintf('insert ignore into %s (listid,userid, entered) values(%d,%d, now())', $tables['listuser'],
                        $_POST['movedestination_all'], $user[0]));
                    if (Sql_Affected_rows() == 1) { // 0 means they were already on the list
                        ++$cnt;
                    }
                }
                $msg = $cnt.' '.$GLOBALS['I18N']->get('subscribers were moved to').' '.listName($_POST['movedestination_all']);
                break;
            case 'copy':
                $cnt = 0;
                while ($user = Sql_Fetch_Row($req)) {
                    Sql_query(sprintf('insert ignore into %s (listid,userid, entered) values(%d,%d, now())', $tables['listuser'],
                        $_POST['copydestination_all'], $user[0]));
                    if (Sql_Affected_rows() == 1) {
                        ++$cnt;
                    }
                }
                $msg = $cnt.' '.$GLOBALS['I18N']->get('subscribers were copied to').' '.listName($_POST['copydestination_all']);
                break;
            case 'delete':
                Sql_Query(sprintf('delete from %s where listid = %d', $tables['listuser'], $id));
                $msg = Sql_Affected_Rows().' '.$GLOBALS['I18N']->get('subscribers were deleted from this list');
                break;
            default: // do nothing
        }
    }
    echo '<div class="actionresult">'.$msg.'</div>';
}

if ($listAll) {
    echo '<p>'.s('The "list of all subscribers" is not a real list, but it gives you access to all subscribers in your system. There may be more subscribers in your system than are members of your lists.').'</p>';
}

if (isset($_POST['add'])) {
    if ($_POST['new']) {
        $result = Sql_query(sprintf('select * from %s where email = "%s"', $tables['user'], $_POST['new']));
        if (Sql_affected_rows()) {
            echo '<p>'.$GLOBALS['I18N']->get('Users found, click add to add this user').":<br /><ul>\n";
            while ($user = Sql_fetch_array($result)) {
                printf('<li>[ '.PageLink2('members', $GLOBALS['I18N']->get('Add'),
                        "add=1&amp;id=$id&amp;doadd=".$user['id']).' ] %s </li>',
                    $user['email']);
            }
            echo "</ul>\n";
        } else {
            echo '<p class="information">'.$GLOBALS['I18N']->get('No user found with that email').'</p><table class="membersForm">'.formStart(' class="membersSubscribe" ');
            require dirname(__FILE__).'/subscribelib2.php'; ?>
            <?php
            // pass the entered email on to the form
            $_REQUEST['email'] = $_POST['new'];
            /*      printf('
                  <tr><td><div class="required">%s</div></td>
                  <td class="attributeinput"><input type="text" name="email" value="%s" size="%d">
                  <script language="Javascript" type="text/javascript">addFieldToCheck("email","%s");</script></td></tr>',
                  $strEmail,$email,$textlinewidth,$strEmail);
            */
            echo ListAllAttributes(); ?>
            <!--nizar 5 lignes -->
            <tr>
                <td colspan=2><input type="hidden" name="action" value="insert"><input
                        type="hidden" name="doadd" value="yes"><input type="hidden" name="id" value="<?php echo
                    $id ?>"><input type="submit" name="subscribe"
                                   value="<?php echo $GLOBALS['I18N']->get('add user') ?>"></form></td>
            </tr></table>
            <?php
            return;
        }
    }
}
if (isset($_REQUEST['doadd'])) {
    if ($_POST['action'] == 'insert') {
        $email = trim($_POST['email']);
        echo $GLOBALS['I18N']->get('Inserting user')." $email";
        $result = Sql_query(sprintf('
      insert into %s (email,entered,confirmed,htmlemail,uniqid)
       values("%s",now(),1,%d,"%s")',
            $tables['user'], $email, !empty($_POST['htmlemail']) ? '1' : '0', getUniqid()));
        $userid = Sql_insert_id();
        $query = "insert into $tables[listuser] (userid,listid,entered)
 values($userid,$id,now())";
        $result = Sql_query($query);
        // remember the users attributes
        $res = Sql_Query("select * from $tables[attribute]");
        while ($row = Sql_Fetch_Array($res)) {
            $fieldname = 'attribute'.$row['id'];
            $value = $_POST[$fieldname];
            if (is_array($value)) {
                $newval = array();
                foreach ($value as $val) {
                    array_push($newval, sprintf('%0'.$checkboxgroup_storesize.'d', $val));
                }
                $value = implode(',', $newval);
            }
            Sql_Query(sprintf('replace into %s (attributeid,userid,value) values("%s","%s","%s")',
                $tables['user_attribute'], $row['id'], $userid, $value));
        }
    } else {
        $query = "replace into $tables[listuser] (userid,listid,entered)
 values({$_REQUEST['doadd']},$id,now())";
        $result = Sql_query($query);
    }
    echo '<br /><font color=red size=+2>'.$GLOBALS['I18N']->get('User added').'</font><br />';
}
if (isset($_REQUEST['delete'])) {
    verifyCsrfGetToken();
    $delete = sprintf('%d', $_REQUEST['delete']);
    // single delete the index in delete
    $_SESSION['action_result'] = s('Removing %d from this list ', $delete)." ..\n";
    $result = Sql_Query(sprintf('delete from %s where listid = %d and userid = %d', $tables['listuser'], $id, $delete));
    $_SESSION['action_result'] .= '... '.$GLOBALS['I18N']->get('Done')."<br />\n";
    Redirect("members&$pagingKeep&id=$id");
}
if (!empty($id) || $listAll) {
    if (!$listAll) {
        $query = sprintf(' select count(*) from %s lu join %s u on lu.userid = u.id where lu.listid = %d and '.$confirmedSelection,
            $tables['listuser'], $tables['user'], $id);
        $result = Sql_Query($query);
    } else {
        $query = 'select count(*) from '.$tables['user']
            .' u where '.$confirmedSelection;
        $result = Sql_Query($query);
    }
    $row = Sql_Fetch_row($result);
    $total = $row[0];
    $offset = $start;

    $paging = '';
    if ($total > MAX_USER_PP) {
        if ($start > 0) {
            $listing = sprintf(s('Listing subscriber %d to %d', $start, ($start + MAX_USER_PP)));
            $limit = "limit $start,".MAX_USER_PP;
        } else {
            $listing = s('Listing subscriber 1 to 50');
            $limit = 'limit 0,50';
        }

        $paging = simplePaging("members&$pagingKeep&amp;id=".$id, $start, $total, MAX_USER_PP,
            $GLOBALS['I18N']->get('subscribers'));
    }
    if (!$listAll) {
        $result = Sql_Query(sprintf('select u.* from %s lu join %s u on lu.userid = u.id where lu.listid = %d and '.$confirmedSelection.' limit %d offset %d',
            $tables['listuser'], $tables['user'], $id, MAX_USER_PP, $offset));
    } else {
        $query = sprintf(' select u.* from %s u where '.$confirmedSelection.' limit %d offset %d', $tables['user'],
            MAX_USER_PP, $offset);
        $result = Sql_Query($query);
    }

    $tabs = new WebblerTabs();
    $tabs->addTab(s('confirmed'), PageUrl2('members&id='.$id), 'confirmed');
    $tabs->addTab(s('unconfirmed'), PageUrl2('members&tab=unconfirmed&id='.$id), 'unconfirmed');
    if (!empty($_GET['tab'])) {
        $tabs->setCurrent($_GET['tab']);
    } else {
        $_GET['tab'] = 'confirmed';
        $tabs->setCurrent('confirmed');
    }
    echo "<div class='minitabs'>\n";
    echo $tabs->display();
    echo "</div>\n";

    echo '<p>'.s('%d subscribers', $total).'</p>';

    echo formStart(' name="users" class="membersProcess" ');
    printf('<input type="hidden" name="id" value="%d" />', $id);

    if (!$listAll) {
        echo '<input type="checkbox" name="checkall" class="checkallcheckboxes" />'.$GLOBALS['I18N']->get('Tag all users in this page');
    }
    $columns = array();
    $columns = explode(',', getConfig('membership_columns'));
    // $columns = array('country','Lastname');
    $ls = new WebblerListing($GLOBALS['I18N']->get('Members'));
    $ls->usePanel($paging);
    while ($user = Sql_fetch_array($result)) {
        $element = shortenTextDisplay($user['email']);
        $ls->addElement($element, PageUrl2('user&amp;id='.$user['id']));
        $ls->setClass($element, 'row1');
        $ls_delete = '';
        if ($access != 'view') {
            $ls_delete = sprintf('<a title="'.$GLOBALS['I18N']->get('Delete').'" class="del" href="javascript:deleteRec(\'%s\');"></a>',
                PageURL2('members', '', "start=$start&$pagingKeep&id=$id&delete=".$user['id']));
        }
        $ls->addRow($element, '',
            ($user['confirmed'] && !$user['blacklisted']) ? $ls_delete.$GLOBALS['img_tick'] : $ls_delete.$GLOBALS['img_cross']);

        if ($access != 'view' && !$listAll) {
            $ls->addColumn($element, $GLOBALS['I18N']->get('tag'),
                sprintf('<input type="checkbox" name="user[%d]" value="1" />', $user['id']));
        } else {
            $ls->addColumn($element, '&nbsp;', '', $user['id']);
        }

        //# allow plugins to add columns
        foreach ($GLOBALS['plugins'] as $plugin) {
            $plugin->displayUsers($user, $element, $ls);
        }

        if (count($columns)) {
            // let's not do this when not required, adds rather many db requests
//      $attributes = getUserAttributeValues('',$user['id']);
//      foreach ($attributes as $key => $val) {
//          $ls->addColumn($user["email"],$key,$val);
//      }

            foreach ($columns as $column) {
                if (isset($attributes[$column]) && $attributes[$column]) {
                    $ls->addColumn($element, $column, $attributes[$column]);
                }
            }
        }
    }
    echo $ls->display();
}
if ($access == 'view') {
    return;
}
if ($listAll) {
    return;
}

?>
<div class="panel">
	<h3><?php echo s('Actions') ?></h3>
	<div class=" content well">
		<div class="row">
    		<div class="col-sm-6 membersProcess">
            	<h4 style="margin-bottom:0"><?php echo $GLOBALS['I18N']->get('What to do with "Tagged" users') ?>:</h4>
                <h6><?php echo $GLOBALS['I18N']->get('This will only process the users in this page that have the "Tag" checkbox checked') ?></h6>
                <div class="row col-sm-12"  style="margin:10px 0 5px">
                	<p><input type="radio" name="tagaction" value="delete"/> <?php echo $GLOBALS['I18N']->get('Delete') ?>
                    (<?php echo $GLOBALS['I18N']->get('from this list') ?>)</p>
                </div>
                <div class="clearfix" style="margin-bottom:10px;margin-top:-10px"></div>
            <?php
            $html = '';
            $res = Sql_Query("select id,name from {$tables['list']} $subselect");
            while ($row = Sql_Fetch_array($res)) {
                if ($row['id'] != $id) {
                    $html .= sprintf('    <option value="%d">%s</option>', $row['id'], $row['name']);
                }
            }
            if ($html) {
                ?>
					<div class="row col-sm-12" style="margin:0px 0 10px">
						<div class="fleft">
							<input type="radio" name="tagaction" value="move"/> <?php echo $GLOBALS['I18N']->get('Move').'&nbsp;'.$GLOBALS['I18N']->get('to').': &nbsp;&nbsp;' ?>
    					</div>
						<div class="fleft">
	                     	<select name="movedestination">
                                <?php echo $html ?>
	                         </select>
	                    </div>
                     </div>
                	<div class="row col-sm-12" style="margin:0px 0 10px">
						<div class="fleft">
	                		<input type="radio" name="tagaction" value="copy"/> <?php echo $GLOBALS['I18N']->get('Copy').'&nbsp;'.$GLOBALS['I18N']->get('to').':&nbsp;&nbsp;' ?>
    					</div>
						<div class="fleft">
	                    	<select name="copydestination">
                                <?php echo $html ?>
	                        </select>
	                    </div>
					</div>
                <div class="row col-sm-12" style="margin:0px 0 10px">
                    <input type="radio" name="tagaction" value="nothing" checked="checked"/><?php echo $GLOBALS['I18N']->get('Nothing') ?>
                </div>
                <?php
            } ?>
            </div>
            <br  class="visible-xs" />
            <div class="col-sm-6 membersProcess">
            	<h4 style="margin-bottom:0"><?php echo s('What to do with all subscribers') ?></h4>
                <h6><?php echo s('This will process all subscribers on this list, confirmed and unconfirmed') ?></h6>
                <div class="row col-sm-12" style="margin:10px 0 5px">
                    <p><input type="radio" name="tagaction_all" value="delete"/> <?php echo $GLOBALS['I18N']->get('Delete') ?>
                    (<?php echo $GLOBALS['I18N']->get('from this list') ?>)</p>
                </div>
                <div class="clearfix" style="margin-bottom:10px;margin-top:-10px"></div>
           <?php if ($html) {
                ?>
                	<div class="row col-sm-12"  style="margin:0px 0 10px">
						<div class="fleft">
	                		<input type="radio" name="tagaction_all" value="move"/> <?php echo $GLOBALS['I18N']->get('Move').'&nbsp;'.$GLOBALS['I18N']->get('to').':&nbsp;&nbsp' ?>
						</div>
						<div class="fleft">
	                    	<select name="movedestination_all">
                                <?php echo $html ?>
	                        </select>
						</div>
                    </div>
                	<div class="row col-sm-12" style="margin:0px 0 10px">
                		<div class="fleft">
	                		<input type="radio" name="tagaction_all" value="copy"/> <?php echo $GLOBALS['I18N']->get('Copy').'&nbsp;'.$GLOBALS['I18N']->get('to').':&nbsp;&nbsp' ?>
						</div>    
                		<div class="fleft">
	                    	<select name="copydestination_all">
                                <?php echo $html ?>
	                        </select>
	                    </div>
                    </div>
                <div class="row col-sm-12"  style="margin:0px 0">
	                <input type="radio" name="tagaction_all" value="nothing" checked="checked"/> <?php echo $GLOBALS['I18N']->get('Nothing') ?>
	            </div>
                <?php

            } ?>
            </div></div>
            <br />
            <div class="membersProcess">
                <input class="action-button" type="submit" name="processtags" value="<?php echo $GLOBALS['I18N']->get('do it') ?>"/>
            </div>
        </div>
</div>
</form>
