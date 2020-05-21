<?php

require_once dirname(__FILE__).'/accesscheck.php';

$columns = array('messages', 'lists', 'bounces', 'blacklist');

if (!defined('PHPLISTINIT')) {
    exit;
}

$filterpanel = $countpanel = $paging = '';

if (!isset($_SESSION['userlistfilter']) || !$_SESSION['userlistfilter']) {
    $_SESSION['userlistfilter'] = array();
}
if (isset($_GET['sortby'])) {
    $sortby = removeXss($_GET['sortby']);
    //# only allow spaces and word chars
    $sortby = preg_replace('/[^\w ]+/', '', $sortby);
} else {
    $sortby = '';
}
if (strtolower($sortby) == 'password') {
    $sortby = '';
}

if (isset($_GET['delete'])) {
    $delete = sprintf('%d', $_GET['delete']);
} else {
    $delete = 0;
}
//print $_GET["delete"].' '.$delete .isSuperUser();exit;

if (isset($_GET['start'])) {
    $start = sprintf('%d', $_GET['start']);
} else {
    $start = 0;
}

$searchdone = 1;
if (!empty($_GET['start'])) {
    $start = sprintf('%d', $_GET['start']);
} else {
    $start = 0;
}
$unconfirmed = !empty($_GET['unconfirmed']) ? sprintf('%d', $_GET['unconfirmed']) : 0;
$blacklisted = !empty($_GET['blacklisted']) ? sprintf('%d', $_GET['blacklisted']) : 0;
if (isset($_GET['sortorder'])) {
    if ($_GET['sortorder'] == 'asc') {
        $sortorder = 'asc';
    } else {
        $sortorder = 'desc';
    }
} else {
    $sortorder = 'desc';
}
if (isset($_GET['listid'])) {
    $listid = sprintf('%d', $_GET['listid']);
} else {
    $listid = 0;
}
if (isset($_GET['find'])) {
    if (!isset($_GET['findby'])) {
        $_GET['findby'] = '';
    }

    if ($_GET['find'] == 'NULL') {
        $_SESSION['userlistfilter']['find'] = '';
        $_SESSION['userlistfilter']['findby'] = '';
    } else {
        $_SESSION['userlistfilter']['find'] = removeXss($_GET['find']);
        $_SESSION['userlistfilter']['findby'] = removeXss($_GET['findby']);
    }
} else {
    $_SESSION['userlistfilter']['find'] = '';
    $_SESSION['userlistfilter']['findby'] = '';
}

$find = $_SESSION['userlistfilter']['find'];
$findby = $_SESSION['userlistfilter']['findby'];
if (!$findby) {
    $findby = 'email';
}
$findtables = '';
$findbyselect = '';
$findfield = '';
$findfieldname = '';
$find_url = '';

// hmm interesting, if they select a findby but not a find, use the Sql wildcard:
if ($findby && !$find) {
    // this is very slow, so instead erase the findby.
    //  $find = '%';
    $findby = '';
}

$system_findby = array(
    'email',
    'foreignkey',
    'uniqid',
);

if ($findby && $find && !in_array($findby, $system_findby)) {
    $find_url = '&amp;find='.urlencode($find).'&amp;findby='.urlencode($findby);
    $findatt = Sql_Fetch_Array_Query(sprintf('select id,tablename,type,name from %s where id = %d',
        $tables['attribute'], $findby));
    switch ($findatt['type']) {
        case 'textline':
        case 'hidden':
            $findtables = ','.$tables['user_attribute'];
            $findbyselect = sprintf(' %s.userid = %s.id and
              %s.attributeid = %d and %s.value like "%%%s%%"', $tables['user_attribute'], $tables['user'],
                $tables['user_attribute'], $findby, $tables['user_attribute'], sql_escape($find));
            $findfield = $tables['user_attribute'].'.value as display, '.$tables['user'].'.bouncecount';
            $findfieldname = $findatt['name'];
            break;
        case 'select':
        case 'radio':
            $findtables = ','.$tables['user_attribute'].','.$table_prefix.'listattr_'.$findatt['tablename'];
            $findbyselect = sprintf(' %s.userid = %s.id and
              %s.attributeid = %d and %s.value = %s.id and
              %s.name like "%%%s%%"', $tables['user_attribute'], $tables['user'], $tables['user_attribute'], $findby,
                $tables['user_attribute'], $table_prefix.
                'listattr_'.$findatt['tablename'], $table_prefix.
                'listattr_'.$findatt['tablename'], sql_escape($find));
            $findfield = $table_prefix.'listattr_'.$findatt['tablename'].'.name as display, '.$tables['user'].'.bouncecount';
            $findfieldname = $findatt['name'];
            break;
    }
} else {
    $findtables = '';
    $findbyselect = sprintf(' %s like "%%%s%%"', $findby, sql_escape($find));
    $findfield = $tables['user'].'.bouncecount,'.$tables['user'].'.foreignkey';
    $findfieldname = 'Email';
    $find_url = '&amp;find='.urlencode($find);
}

if (empty($findfield)) {
    $findfield = 'email';
}

if ($require_login && !isSuperUser()) {
    $access = accessLevel('users');
    switch ($access) {
        case 'owner':
            $table_list = $tables['user'].','.$tables['listuser'].','.$tables['list'].$findtables;
            $subselect = "{$tables['user']}.id = {$tables['listuser']}.userid and {$tables['listuser']}.listid = {$tables['list']}.id and {$tables['list']}.owner = ".$_SESSION['logindetails']['id'];
            if ($unconfirmed) {
                $subselect .= ' and !confirmed ';
            }
            if ($blacklisted) {
                $subselect .= ' and blacklisted ';
            }
            if ($find && $findbyselect) {
                $listquery = "select DISTINCT {$tables['user']}.email,{$tables['user']}.id,$findfield,confirmed from ".$table_list." where $subselect and $findbyselect";
                $count = Sql_query("SELECT count(distinct {$tables['user']}.id) FROM ".$table_list." where $subselect and $findbyselect");
                $unconfirmedcount = Sql_query("SELECT count(distinct {$tables['user']}.id) FROM ".$table_list." where $subselect and !confirmed and $findbyselect");
            } else {
                $listquery = "SELECT DISTINCT {$tables['user']}.email,{$tables['user']}.id,$findfield,confirmed FROM ".$table_list." WHERE $subselect";
                $count = Sql_query("SELECT count(distinct {$tables['user']}.id) FROM ".$table_list." WHERE $subselect");
                $unconfirmedcount = Sql_query("SELECT count(distinct {$tables['user']}.id) FROM ".$table_list." WHERE !confirmed and $subselect");
            }
            break;
        case 'all':
        case 'view':
            $table_list = $tables['user'].$findtables;
            if ($find && $findbyselect) {
                if ($unconfirmed) {
                    $findbyselect .= ' and !confirmed ';
                }
                if ($blacklisted) {
                    $findbyselect .= ' and blacklisted ';
                }
                $listquery = "select DISTINCT {$tables['user']}.email,{$tables['user']}.id,$findfield,{$tables['user']}.confirmed from ".$table_list." where $findbyselect";
                $count = Sql_query('SELECT count(*) FROM '.$table_list." where $findbyselect");
                $unconfirmedcount = Sql_query('SELECT count(*) FROM '.$table_list." where !confirmed && $findbyselect");
            } else {
                $listquery = "select DISTINCT {$tables['user']}.email,{$tables['user']}.id,$findfield,{$tables['user']}.confirmed from ".$table_list;
                $count = Sql_query('SELECT count(*) FROM '.$table_list);
                $unconfirmedcount = Sql_query('SELECT count(*) FROM '.$table_list.' where !confirmed');
                $searchdone = 0;
            }
            $delete_message = '<br />'.$GLOBALS['I18N']->get('Delete will delete user and all listmemberships').'<br />';
            break;
        case 'none':
        default:
            print Error($GLOBALS['I18N']->get('Your privileges for this page are insufficient'));

            return;
    }
    $delete_message = '<br />'.$GLOBALS['I18N']->get('Delete will delete user from the list').'<br />';
} else {
    //# is superuser
    $table_list = $tables['user'].$findtables;
    if ($find && $findbyselect) {
        if ($unconfirmed) {
            $findbyselect .= ' and !confirmed ';
        }
        if ($blacklisted) {
            $findbyselect .= ' and blacklisted ';
        }
        $listquery = "select {$tables['user']}.email,{$tables['user']}.id,$findfield,{$tables['user']}.confirmed from ".$table_list." where $findbyselect";
        $count = Sql_query('SELECT count(*) FROM '.$table_list." where $findbyselect");
        $unconfirmedcount = Sql_query('SELECT count(*) FROM '.$table_list." where !confirmed and $findbyselect");
    } else {
        $subselect = '';
        if ($unconfirmed || $blacklisted) {
            $subselect = ' where ';
            if ($unconfirmed && $blacklisted) {
                $subselect .= ' !confirmed and blacklisted ';
            } elseif ($unconfirmed) {
                $subselect .= ' !confirmed ';
            } else {
                $subselect .= ' blacklisted';
            }
        } else {
            $searchdone = 0;
        }
        $listquery = "select {$tables['user']}.email,{$tables['user']}.id,$findfield,{$tables['user']}.confirmed from ".$table_list.' '.$subselect;
        $count = Sql_query('SELECT count(*) FROM '.$table_list.' '.$subselect);
        $unconfirmedcount = Sql_query('SELECT count(*) FROM '.$table_list.' where !confirmed');
    }
    $delete_message = '<br />'.$GLOBALS['I18N']->get('Delete will delete user and all listmemberships').'<br />';
}

$totalres = Sql_fetch_Row($unconfirmedcount);
$totalunconfirmed = $totalres[0];
$totalres = Sql_fetch_Row($count);
$total = $totalres[0];

if ($start > $total) {
    $start = 0;
}

if (!empty($delete) && isSuperUser()) {
    // delete the index in delete
    $action_result = $GLOBALS['I18N']->get('deleting')." $delete ..\n";
    deleteUser($delete);

    $action_result .= '..'.$GLOBALS['I18N']->get('Done').'<br/><hr/>';
    $previous_search = '';
    if (!$find == '') {
        $previous_search = "&start=$start&find=$find&findby=$findby";
    }

    $_SESSION['action_result'] = $action_result;
    Redirect("users$previous_search");
} elseif (!empty($delete)) {
    echo ActionResult(s('Sorry, only super users can delete users'));
}

if (isset($add)) {
    if (isset($new)) {
        $query = 'insert into '.$tables['user']." (email,entered) values(\"$new\",now())";
        $result = Sql_query($query);
        $userid = Sql_insert_id();
        $query = 'insert into '.$tables['listuser']." (userid,listid,entered) values($userid,$id,now())";
        $result = Sql_query($query);
    }
    echo ActionResult($GLOBALS['I18N']->get('User added'));
}

// Make the totals human readable
$totalFormatted = number_format($total);
$totalunconfirmedFormatted = number_format($totalunconfirmed);

// Add messages to panel
$countpanel .= s('%s subscribers in total', $totalFormatted);
$countpanel .= '<br/>'.s('Subscribers with a red icon are either unconfirmed or blacklisted or both')." ($totalunconfirmedFormatted)<br/>";

$url = getenv('REQUEST_URI');
if ($unconfirmed) {
    $unc = 'checked="checked"';
} else {
    $unc = '';
}
if ($blacklisted) {
    $bll = 'checked="checked"';
} else {
    $bll = '';
}
if (!isset($start)) {
    $start = 0;
}

$filterpanel .= '<div class="filter">';
$filterpanel .= sprintf('<form method="get" name="listcontrol" action="">
  <input type="hidden" name="page" value="users" />
  <input type="hidden" name="start" value="%d" />
  <input type="hidden" name="find" value="%s" />
  <input type="hidden" name="findby" value="%s" />
  <label for="unconfirmed">%s:<input type="checkbox" name="unconfirmed" value="1" %s /></label>
  <label for="blacklisted">%s:<input type="checkbox" name="blacklisted" value="1" %s /></label>',
    $start,
    htmlspecialchars(stripslashes($find)),
    htmlspecialchars(stripslashes($findby)),
    $GLOBALS['I18N']->get('Show only unconfirmed users'),
    $unc,
    $GLOBALS['I18N']->get('Show only blacklisted users'),
    $bll);
//print '</td><td valign="top">';
$select = '';
foreach (array(
             'email',
             'bouncecount',
             'entered',
             'modified',
             'foreignkey',
         ) as $item) {
    $select .= sprintf('     <option value="%s" %s>%s</option>', $item, $item == $sortby ? 'selected="selected"' : '',
        $GLOBALS['I18N']->get($item));
}

$filterpanel .= sprintf('
  <label for="sortby">%s: <select name="sortby" onchange="document.listcontrol.submit();">
  <option value="0">-- default</option>
  %s
  </select></label>
  <label for="sortdesc">%s: <input type="radio" name="sortorder" id="sortdesc" value="desc" %s onchange="document.listcontrol.submit();" /></label>
  <label for="sortasc">%s: <input type="radio" name="sortorder" id="sortasc" value="asc" %s onchange="document.listcontrol.submit();" /></label>
  <input class="submit" type="submit" name="change" value="%s" />
  ',
    $GLOBALS['I18N']->get('Sort by'), $select,
    $GLOBALS['I18N']->get('desc'), $sortorder == 'desc' ? 'checked="checked"' : '',
    $GLOBALS['I18N']->get('asc'), $sortorder == 'asc' ? 'checked="checked"' : '',
    $GLOBALS['I18N']->get('Go'));
$filterpanel .= '</div>';

$order = '';
if ($sortby) {
    $order = ' order by '.$tables['user'].'.'.$sortby;
    if ($sortorder == 'asc') {
        $order .= ' asc';
    } else {
        $order .= ' desc';
    }
}
$find_url .= "&amp;sortby=$sortby&amp;sortorder=$sortorder&amp;unconfirmed=$unconfirmed&amp;blacklisted=$blacklisted";

$listing = '';
$dolist = 1;
if (true || $total > MAX_USER_PP) {
    if (isset($start) && $start) {
        $totalUserCount = number_format($start + MAX_USER_PP);
        $listing = sprintf($GLOBALS['I18N']->get('Listing user %d to %d'), $start, $totalUserCount);
        $limit = "limit $start,".MAX_USER_PP;
    } else {
        if ($total < USERSPAGE_MAX || $searchdone) {
            $listing = sprintf($GLOBALS['I18N']->get('Listing user %d to %d'), 1, 50);
            $limit = 'limit 0,50';
            $start = 0;
            $dolist = 1;
        } else {
            $dolist = 0;
        }
    }
    if ($dolist) {
        $paging = simplePaging('users'.$find_url, $start, $total, MAX_USER_PP, $GLOBALS['I18N']->get('Subscribers'));
        $result = Sql_query("$listquery $order $limit");
    } else {
        //    print Info($GLOBALS['I18N']->get('too many subscribers, use a search query to list some'),1);
        $result = 0;
    }
} else {
    $result = Sql_Query("$listquery $order");
}

$filterpanel .= '
<div class="usersFind">
<input type="hidden" name="id" value="' .$listid.'" />';

$filterpanel .= '<label for="find">'.$GLOBALS['I18N']->get('Find a user').'</label>';
$filterpanel .= '<input type="text" name="find" value="';
$filterpanel .= $find != '%' ? htmlspecialchars(stripslashes($find)) : '';
$filterpanel .= '" size="30" />';

$filterpanel .= '<select name="findby">';

$filterpanel .= '<option value="email" ';
$filterpanel .= $findby == 'email' ? 'selected="selected"' : '';
$filterpanel .= '>'.$GLOBALS['I18N']->get('Email').'</option>';
$filterpanel .= '<option value="foreignkey" ';
$filterpanel .= $findby == 'foreignkey' ? 'selected="selected"' : '';
$filterpanel .= '>'.$GLOBALS['I18N']->get('Foreign Key').'</option>';
$filterpanel .= '<option value="uniqid" ';
$filterpanel .= $findby == 'uniqid' ? 'selected="selected"' : '';
$filterpanel .= '>'.$GLOBALS['I18N']->get('Unique ID').'</option>';

$att_req = Sql_Query('select id,name from '.$tables['attribute'].' where type = "hidden" or type = "textline" or type = "select"');
while ($row = Sql_Fetch_Array($att_req)) {
    $filterpanel .= sprintf('<option value="%d" %s>%s</option>', $row['id'],
        $row['id'] == $findby ? 'selected="selected"' : '', substr($row['name'], 0, 20));
}

$filterpanel .= '</select><input class="submit" type="submit" value="'.s('Go').'" />&nbsp;&nbsp;<a href="./?page=users&amp;find=NULL" class="reset">'.s('reset').'</a>';
$filterpanel .= '</form></div>';
//$filterpanel .= '<tr><td colspan="4"></td></tr>
//</table>';

echo Info($countpanel);

$panel = new UIPanel($GLOBALS['I18N']->get('Find subscribers'), $filterpanel);
echo $panel->display();

//if (($require_login && isSuperUser()) || !$require_login)
echo '<div class="actions">';
echo '<div id="add-csv-button" class="pull-left">'.PageLinkButton('dlusers', $GLOBALS['I18N']->get('Download all users as CSV file'),
        'nocache='.uniqid('')).'</div>';
echo '<div id="add-user-button" class="pull-right">'.PageLinkButton('adduser', $GLOBALS['I18N']->get('Add a User')).'</div>';
echo '</div><div class="clearfix"></div>';

$some = 0;

$ls = new WebblerListing(s('subscribers'));
$ls->setElementHeading(s('subscriber'));
$ls->usePanel($paging);
if ($result) {
    while ($user = Sql_fetch_array($result)) {
        $some = 1;
        $element = htmlspecialchars($user['email']);
        $ls->addElement($element, PageURL2("user&amp;start=$start&amp;id=".$user['id'].$find_url));
        $ls->setClass($element, 'row1');

        //# we make one column with the subscriber status being "on" or "off"
        //# two columns are too confusing and really unnecessary
        // ON = confirmed &&  !blacklisted

//    $ls->addColumn($user["email"], $GLOBALS['I18N']->get('confirmed'), $user["confirmed"] ? $GLOBALS["img_tick"] : $GLOBALS["img_cross"]);
        //   if (in_array("blacklist", $columns)) {
        $onblacklist = isBlackListed($element);
        //    $ls->addColumn($user["email"], $GLOBALS['I18N']->get('bl l'), $onblacklist ? $GLOBALS["img_tick"] : $GLOBALS["img_cross"]);
        //  }

        if ($user['confirmed'] && !$onblacklist) {
            $ls_confirmed = $GLOBALS['img_tick'];
        } else {
            $ls_confirmed = $GLOBALS['img_cross'];
        }

        $ls_del = '';
//    $ls->addColumn($user["email"], $GLOBALS['I18N']->get('del'), sprintf('<a href="%s" onclick="return deleteRec(\'%s\');">del</a>',PageUrl2('users'.$find_url), PageURL2("users&start=$start&delete=" .$user["id"])));
        if (isSuperUser()) {
            $ls_del = sprintf('<a href="javascript:deleteRec(\'%s\');" class="del">del</a>',
                PageURL2("users&start=$start&find=$find&findby=$findby&delete=".$user['id']));
        }
        /*    if (isset ($user['foreignkey'])) {
              $ls->addColumn($user["email"], $GLOBALS['I18N']->get('key'), $user["foreignkey"]);
            }
            if (isset ($user["display"])) {
              $ls->addColumn($user["email"], "&nbsp;", $user["display"]);
            }
        */
        if (in_array('lists', $columns)) {
            $lists = Sql_query('SELECT count(*) FROM '.$tables['listuser'].','.$tables['list'].' where userid = '.$user['id'].' and '.$tables['listuser'].'.listid = '.$tables['list'].'.id');
            $membership = Sql_fetch_row($lists);
            $ls->addColumn($element, $GLOBALS['I18N']->get('lists'), $membership[0]);
        }

        if (in_array('messages', $columns)) {
            $msgs = Sql_query('SELECT count(*) FROM '.$tables['usermessage'].' where userid = '.$user['id'].' and status = "sent"');
            $nummsgs = Sql_fetch_row($msgs);
            $ls_msgs = $GLOBALS['I18N']->get('msgs').':&nbsp;'.$nummsgs[0];
        }

        //## allow plugins to add columns
        if (isset($GLOBALS['plugins']) && is_array($GLOBALS['plugins'])) {
            foreach ($GLOBALS['plugins'] as $plugin) {
                if (method_exists($plugin, 'displayUsers')) {
                    $plugin->displayUsers($user, $element, $ls);
                }
            }
        }

        $ls_bncs = '';
        if (in_array('bounces', $columns) && !empty($user['bouncecount'])) {
            $ls_bncs = $GLOBALS['I18N']->get('bncs').': '.$user['bouncecount'];
        }
        $ls->addRow($element, "<div class='listinghdname gray'>".$ls_msgs.'<br />'.$ls_bncs.'</div>',
            $ls_del.'&nbsp;'.$ls_confirmed);
    }
    echo $ls->display();
    if (!$some && !$total) {
        $p = new UIPanel($GLOBALS['I18N']->get('no results'), $GLOBALS['I18N']->get('No users apply'));
        echo $p->display();
    }
}
