<div id="progressbar"></div>
<hr/>

<?php
require_once dirname(__FILE__).'/accesscheck.php';

if (!isSuperUser()) {
    echo s('Sorry, this page can only be used by super admins');

    return;
}

if (!is_object('date')) {
    include_once dirname(__FILE__).'/date.php';
}

ob_end_flush();
$from = new date('from');
$to = new date('to');
$fromval = $toval = $todo = '';

$find = (!isset($find)) ? '' : $find;
$num = 0;
if (isset($_REQUEST['num'])) {
    $num = sprintf('%d', $_REQUEST['num']);
}

$findtables = '';
$findbyselect = sprintf(' email like "%%%s%%"', $find);
$findfield = $tables['user'].'.email as display, '.$tables['user'].'.bouncecount';
$findfieldname = 'Email';
$find_url = '&find='.urlencode($find);

function resendConfirm($id)
{
    global $tables, $envelope;
    $userdata = Sql_Fetch_Array_Query("select * from {$tables['user']} where id = $id");
    $lists_req = Sql_Query(sprintf('select %s.name from %s,%s where
    %s.listid = %s.id and %s.userid = %d',
        $tables['list'], $tables['list'],
        $tables['listuser'], $tables['listuser'], $tables['list'], $tables['listuser'], $id));
    while ($row = Sql_Fetch_Row($lists_req)) {
        $lists .= '  * '.$row[0]."\n";
    }

    if ($userdata['subscribepage']) {
        $subscribemessage = str_replace('[LISTS]', $lists,
            getUserConfig('subscribemessage:'.$userdata['subscribepage'], $id));
        $subject = getConfig('subscribesubject:'.$userdata['subscribepage']);
    } else {
        $subscribemessage = str_replace('[LISTS]', $lists, getUserConfig('subscribemessage', $id));
        $subject = getConfig('subscribesubject');
    }

    logEvent(s('Resending confirmation request to').' '.$userdata['email']);
    if (!TEST) {
        return sendMail($userdata['email'], $subject, $_REQUEST['prepend'].$subscribemessage,
            system_messageheaders($userdata['email']), $envelope);
    }
}

function fixEmail($email)
{
    // we don't fix mails marked duplicate, as that makes them valid, which we don't want.
    if (strpos($email, 'duplicate') !== false) {
        return $email;
    }

    if (preg_match('#(.*)@.*hotmail.*#i', $email, $regs)) {
        $email = $regs[1].'@hotmail.com';
    }
    if (preg_match('#(.*)@.*aol.*#i', $email, $regs)) {
        $email = $regs[1].'@aol.com';
    }
    if (preg_match('#(.*)@.*yahoo.*#i', $email, $regs)) {
        $email = $regs[1].'@yahoo.com';
    }
// $email = str_replace(" ","",$email);
    $email = preg_replace('#,#', '.', $email);
    $email = str_replace("\.\.", "\.", $email);
//  $email = preg_replace("#[^\w]$#","",$email);
//  $email = preg_replace("#\.$#","",$email);
    $email = preg_replace("#\.cpm$#i", "\.com", $email);
    $email = preg_replace("#\.couk$#i", "\.co\.uk", $email);

    return $email;
}

function mergeUser($userid)
{
    $duplicate = Sql_Fetch_Array_Query("select * from {$GLOBALS['tables']['user']} where id = $userid");
    printf('<br/>%s', $duplicate['email']);
    if (preg_match('/^duplicate[^ ]* (.*)/', $duplicate['email'], $regs)) {
        echo '-> '.$regs[1];
        $email = $regs[1];
    } elseif (preg_match("/^([^ ]+@[^ ]+) \(\d+\)/", $duplicate['email'], $regs)) {
        echo '-> '.$regs[1];
        $email = $regs[1];
    } else {
        $email = '';
    }
    if ($email) {
        $orig = Sql_Fetch_Row_Query(sprintf('select id from %s where email = "%s"', $GLOBALS['tables']['user'],
            $email));
        if ($orig[0]) {
            echo ' '.s('user found');
            $umreq = Sql_Query("select * from {$GLOBALS['tables']['usermessage']} where userid = ".$duplicate['id']);
            while ($um = Sql_Fetch_Array($umreq)) {
                Sql_Query(sprintf('update %s set userid = %d, entered = "%s" where userid = %d and entered = "%s"',
                    $GLOBALS['tables']['usermessage'], $orig[0], $um['entered'], $duplicate['id'], $um['entered']), 1);
            }
            $bncreq = Sql_Query("select * from {$GLOBALS['tables']['user_message_bounce']} where user = ".$duplicate['id']);
            while ($bnc = Sql_Fetch_Array($bncreq)) {
                Sql_Query(sprintf('update %s set user = %d, time = "%s" where user = %d and time = "%s"',
                    $GLOBALS['tables']['user_message_bounce'], $orig[0], $bnc['time'], $duplicate['id'], $bnc['time']),
                    1);
            }
            Sql_Query("delete from {$GLOBALS['tables']['listuser']} where userid = ".$duplicate['id']);
            Sql_Query("delete from {$GLOBALS['tables']['user_message_bounce']} where user = ".$duplicate['id']);
            Sql_Query("delete from {$GLOBALS['tables']['usermessage']} where userid = ".$duplicate['id']);
            if (MERGE_DUPLICATES_DELETE_DUPLICATE) {
                deleteUser($duplicate['id']);
            }
        } else {
            echo ' '.s('no user found');
            // so it must be save to rename the original to the actual email
            Sql_Query(sprintf('update %s set email = "%s" where id = %d', $GLOBALS['tables']['user'], $email, $userid));
        }
        flush();
    } else {
        echo '-> '.s('unable to find original email');
    }
}

function moveUser($userid)
{
    global $tables;
    $newlist = $_GET['list'];
    Sql_Query(sprintf('delete from %s where userid = %d', $tables['listuser'], $userid));
    Sql_Query(sprintf('insert into %s (userid,listid,entered) values(%d,%d,now())', $tables['listuser'], $userid,
        $newlist));
}

function addUniqID($userid)
{
    Sql_query(sprintf('update %s set uniqid = "%s" where id = %d', $GLOBALS['tables']['user'], getUniqID(), $userid));
}

if (($require_login && !isSuperUser()) || !$require_login || isSuperUser()) {
    $action_result = '';
    $access = accessLevel('reconcileusers');
    switch ($access) {
        case 'all':
            if (isset($_GET['option']) && $_GET['option']) {
                set_time_limit(600);
                switch ($_GET['option']) {
                    case 'markallconfirmed':
                        $list = sprintf('%d', $_GET['list']);
                        if ($list == 0) {
                            $action_result .= s('Marking all subscribers confirmed');
                            Sql_Query("update {$tables['user']} set confirmed = 1");
                        } else {
                            $action_result .= sprintf(s('Marking all subscribers on list %s confirmed'),
                                ListName($list));
                            Sql_Query(sprintf('UPDATE %s, %s SET confirmed =1 WHERE  %s.id = %s.userid AND %s.listid= %d',
                                $tables['user'], $tables['listuser'], $tables['user'], $tables['listuser'],
                                $tables['listuser'], $list));
                        }
                        $total = Sql_Affected_Rows();
                        $action_result .= "<br/>$total ".s('subscribers apply').'<br/>';
                        break;
                    case 'adduniqid':
                        $action_result .= s('Creating UniqID for all subscribers who do not have one');
                        $req = Sql_Query("select * from {$tables['user']} where uniqid is NULL or uniqid = \"\"");
                        $total = Sql_Affected_Rows();
                        $action_result .= "<br/>$total ".s('subscribers apply').'<br/>';
                        $todo = 'addUniqID';
                        break;
                    case 'markallhtml':
                        $action_result .= s('Marking all subscribers to receive HTML');
                        Sql_Query("update {$tables['user']} set htmlemail = 1");
                        $total = Sql_Affected_Rows();
                        $action_result .= "<br/>$total ".s('subscribers apply').'<br/>';
                        break;
                    case 'markalltext':
                        $action_result .= s('Marking all subscribers to receive text');
                        Sql_Query("update {$tables['user']} set htmlemail = 0");
                        $total = Sql_Affected_Rows();
                        $action_result .= "<br/>$total ".s('subscribers apply').'<br/>';
                        break;
                    case 'nolists':
                        $action_result .= s('Deleting subscribers who are not on any list');
                        $req = Sql_Query(sprintf('select %s.id from %s
              left join %s on %s.id = %s.userid
              where userid is NULL',
                            $tables['user'], $tables['user'], $tables['listuser'], $tables['user'],
                            $tables['listuser']));
                        $total = Sql_Affected_Rows();
                        $action_result .= "<br/>$total ".s('subscribers apply').'<br/>';
                        $todo = 'deleteUser';
                        break;
                    case 'nolistsnewlist':
                        $list = sprintf('%d', $_GET['list']);
                        $action_result .= s('Moving subscribers who are not on any list to').' '.ListName($list);
                        $req = Sql_Query(sprintf('select %s.id from %s
              left join %s on %s.id = %s.userid
              where userid is NULL',
                            $tables['user'], $tables['user'], $tables['listuser'], $tables['user'],
                            $tables['listuser']));
                        $total = Sql_Affected_Rows();
                        $action_result .= "<br/>$total ".s('subscribers apply').'<br/>';
                        $todo = 'moveUser';
                        break;
                    case 'bounces':
                        $action_result .= s('Deleting subscribers with more than').' '.$num.' '.s('bounces');
                        $req = Sql_Query(sprintf('select id from %s
              where bouncecount > %d',
                            $tables['user'], $num
                        ));
                        $total = Sql_Affected_Rows();
                        $action_result .= "<br/>$total ".s('subscribers apply').'<br/>';
                        $todo = 'deleteUser';
                        break;
                    case 'blacklist':
                        $action_result .= s('Blacklisting subscribers with more than').' '.$num.' '.s('bounces');
                        $req = Sql_Query(sprintf('select id from %s
              where bouncecount > %d',
                            $tables['user'], $num
                        ));
                        $total = Sql_Affected_Rows();
                        $action_result .= "<br/>$total ".s('subscribers apply').'<br/>';
                        $todo = 'addUserToBlackListID';
                        break;
                    case 'resendconfirm':
                        $fromval = $from->getDate('from');
                        $toval = $from->getDate('to');
                        $action_result .= s('Resending request for confirmation to subscribers who signed up after').' '.$fromval.' '.s('and before').' '.$toval;
                        $req = Sql_Query(sprintf('select id from %s
              where entered > "%s" and entered < "%s" and !confirmed',
                            $tables['user'], $fromval, $toval
                        ));
                        $total = Sql_Affected_Rows();
                        $action_result .= "<br/>$total ".s('subscribers apply').'<br/>';
                        $todo = 'resendConfirm';
                        break;
                    case 'deleteunconfirmed':
                        $fromval = $from->getDate('from');
                        $toval = $from->getDate('to');
                        $action_result .= s('Deleting unconfirmed subscribers who signed up after').' '.$fromval.' '.s('and before').' '.$toval;
                        $req = Sql_Query(sprintf('select id from %s
              where entered > "%s" and entered < "%s" and !confirmed',
                            $tables['user'], $fromval, $toval
                        ));
                        $total = Sql_Affected_Rows();
                        $action_result .= "<br/>$total ".s('subscribers apply').'<br/>';
                        $todo = 'deleteuser';
                        break;
                    case 'mergeduplicates':
                        $action_result .= s('Trying to merge duplicates');
                        $req = Sql_Verbose_Query(sprintf('select id from %s where (email like "duplicate%%" or email like "%% (_)") and (foreignkey = "" or foreignkey is null)',
                            $tables['user']));
                        $total = Sql_Affected_Rows();
                        $action_result .= "<br/>$total ".s('subscribers apply').'<br/>';
                        $todo = 'mergeUser';
                    case 'invalidemail':
                    case 'fixinvalidemail':
                    case 'deleteinvalidemail':
                    case 'markinvalidunconfirmed':
                    case 'removestaleentries':
                        break;

                    default:
//            Info("Sorry, I don't know how to ".$_GET["option"]);
//            return;
                }
                $c = 1;
                @ob_end_flush();
                if ($todo && $req) {
                    while ($user = Sql_Fetch_Array($req)) {
                        if ($c % 10 == 0) {
                            echo "<br/>$c/$total\n";
                            flush();
                        }
                        set_time_limit(60);
                        if (function_exists($todo)) {
                            $todo($user['id']);
                        } else {
                            Fatal_Error(s("Don't know how to").' '.$todo);

                            return;
                        }
                        ++$c;
                    }
                }
                if (!empty($total)) {
                    echo "$total/$total<br/>";
                }
            }
            if (isset($_GET['option']) && $_GET['option'] == 'invalidemail') {
                //include dirname(__FILE__).'/actions/listinvalid.php';
                echo '<div id="listinvalid">LISTING</div>';
            } elseif (isset($_GET['option']) && $_GET['option'] == 'fixinvalidemail') {
                Info(s('Trying to fix subscribers with an invalid email'));
                flush();
                $req = Sql_Query("select id,email from {$tables['user']}");
                $c = 0;
                while ($row = Sql_Fetch_Array($req)) {
                    set_time_limit(60);
                    //    if (checkMemoryAvail())
                    if (!is_email($row['email'])) {
                        ++$c;
                        $fixemail = fixEmail($row['email']);
                        if (is_email($fixemail)) {
                            Sql_Query(sprintf('update %s set email = "%s" where id = %d', $tables['user'], $fixemail,
                                $row['id']), 0);
                            $list .= PageLink2('user&amp;id='.$row['id'].'&returnpage=reconcileusers&returnoption=fixinvalidemail',
                                    s('User').' '.$row['id']).'    ['.$row['email'].'] => fixed to '.$fixemail.'<br/>';
                            ++$fixed;
                        } else {
                            ++$notfixed;
                            $list .= PageLink2('user&amp;id='.$row['id'].'&returnpage=reconcileusers&returnoption=fixinvalidemail',
                                    s('User').' '.$row['id']).'    ['.$row['email'].']<br/>';
                        }
                    }
                }
                echo $fixed.' '.s('subscribers fixed').'<br/>'.$notfixed.' '.s('subscribers could not be fixed').'<br/>'.$list."\n";
            } elseif (isset($_GET['option']) && $_GET['option'] == 'deleteinvalidemail') {
                include 'actions/reconcileusers.php';
            } elseif (isset($_GET['option']) && $_GET['option'] == 'markinvalidunconfirmed') {
                Info(s('Marking subscribers with an invalid email as unconfirmed'));
                flush();
                $req = Sql_Query("select id,email from {$tables['user']}");
                $c = 0;
                while ($row = Sql_Fetch_Array($req)) {
                    set_time_limit(60);
                    if (!is_email($row['email'])) {
                        ++$c;
                        Sql_Query("update {$tables['user']} set confirmed = 0 where id = {$row['id']}");
                    }
                }
                echo $c.' '.s('subscribers updated')."<br/>\n";
            } elseif (isset($_GET['option']) && $_GET['option'] == 'removestaleentries') {
                Info(s('Cleaning some user tables of invalid entries'));
                // some cleaning up of data:
                $req = Sql_Verbose_Query("select {$tables['usermessage']}.userid
          from {$tables['usermessage']} left join {$tables['user']} on {$tables['usermessage']}.userid = {$tables['user']}.id
          where {$tables['user']}.id IS NULL group by {$tables['usermessage']}.userid");
                echo Sql_Affected_Rows().' '.s('entries apply').'<br/>';
                while ($row = Sql_Fetch_Row($req)) {
                    Sql_Query("delete from {$tables['usermessage']} where userid = $row[0]");
                }
                $req = Sql_Verbose_Query("select {$tables['user_attribute']}.userid
          from {$tables['user_attribute']} left join {$tables['user']} on {$tables['user_attribute']}.userid = {$tables['user']}.id
          where {$tables['user']}.id IS NULL group by {$tables['user_attribute']}.userid");
                echo Sql_Affected_Rows().' '.s('entries apply').'<br/>';
                while ($row = Sql_Fetch_Row($req)) {
                    Sql_Query("delete from {$tables['user_attribute']} where userid = $row[0]");
                }
                $req = Sql_Verbose_Query("select {$tables['listuser']}.userid
          from {$tables['listuser']} left join {$tables['user']} on {$tables['listuser']}.userid = {$tables['user']}.id
          where {$tables['user']}.id IS NULL group by {$tables['listuser']}.userid");
                echo Sql_Affected_Rows().' '.s('entries apply').'<br/>';
                while ($row = Sql_Fetch_Row($req)) {
                    Sql_Query("delete from {$tables['listuser']} where userid = $row[0]");
                }
                $req = Sql_Verbose_Query("select {$tables['usermessage']}.userid
          from {$tables['usermessage']} left join {$tables['user']} on {$tables['usermessage']}.userid = {$tables['user']}.id
          where {$tables['user']}.id IS NULL group by {$tables['usermessage']}.userid");
                echo Sql_Affected_Rows().' '.s('entries apply').'<br/>';
                while ($row = Sql_Fetch_Row($req)) {
                    Sql_Query("delete from {$tables['usermessage']} where userid = $row[0]");
                }
                $req = Sql_Verbose_Query("select {$tables['user_message_bounce']}.user
          from {$tables['user_message_bounce']} left join {$tables['user']} on {$tables['user_message_bounce']}.user = {$tables['user']}.id
          where {$tables['user']}.id IS NULL group by {$tables['user_message_bounce']}.user");
                echo Sql_Affected_Rows().' '.s('entries apply').'<br/>';
                while ($row = Sql_Fetch_Row($req)) {
                    Sql_Query("delete from {$tables['user_message_bounce']} where user = $row[0]");
                }
            }

            $table_list = $tables['user'].$findtables;
            if ($find) {
                $listquery = "select {$tables['user']}.id,$findfield,{$tables['user']}.confirmed from ".$table_list." where $findbyselect";
                $count = Sql_query('SELECT count(*) FROM '.$table_list." where $findbyselect");
                $unconfirmedcount = Sql_query('SELECT count(*) FROM '.$table_list." where !confirmed && $findbyselect");
                if ($_GET['unconfirmed']) {
                    $listquery .= ' and !confirmed';
                }
            } else {
                $listquery = "select {$tables['user']}.id,$findfield,{$tables['user']}.confirmed from ".$table_list;
                $count = Sql_query('SELECT count(*) FROM '.$table_list);
                $unconfirmedcount = Sql_query('SELECT count(*) FROM '.$table_list.' where !confirmed');
            }
            $delete_message = ('<br />'.s('Delete will delete user and all listmemberships').'<br />');
            break;
        case 'none':
        default:
            $table_list = $tables['user'];
            $subselect = ' where id = 0';
            break;
    }
}

if (isset($_GET['delete'])) {
    $delete = sprintf('%d', $_GET['delete']);
    // delete the index in delete
    echo "deleting $delete ..\n";
    deleteUser($delete);
    echo '... '.s('Done')."<br/><hr/><br/>\n";
    Redirect("users&start=$start");
}

$totalres = Sql_fetch_Row($unconfirmedcount);
$totalunconfirmed = $totalres[0];
$totalres = Sql_fetch_Row($count);
$total = $totalres[0];

if (!empty($action_result)) {
    echo '<div class="actionresult">'.$action_result.'</div>';
}

echo '<p class="information"><b>'.number_format($total).' '.s('subscribers').'</b>';
echo $find ? ' '.s('found') : ' '.s('in the database');
echo '</p>';

function snippetListsSelector($optionAll = false)
{
    static $optionList;
    if (empty($optionList)) {
        global $tables;
        $optionList = '';

        $req = Sql_Query(sprintf('select id,name from %s order by listorder', $tables['list']));
        while ($row = Sql_Fetch_Row($req)) {
            $optionList .= sprintf('<option value="%d">%s</option>', $row[0], stripslashes($row[1]));
        }
    }

    $result = '<select name="list">;';
    if ($optionAll) {
        $result .= sprintf('<option value="0">%s</option>', s('-All-'));
    }
    $result .= $optionList;
    $result .= '</select>';

    return $result;
}

echo '<ul class="reconcile">';
//echo '<li>'.PageLinkButton("reconcileusers&amp;option=nolists",s("Delete all subscribers who are not subscribed to any list")).'</li>';
//echo '<li>'.PageLinkButton("reconcileusers&amp;option=invalidemail",s("Find users who have an invalid email")).'</li>';
//echo '<li>'.PageLinkButton("reconcileusers&amp;option=adduniqid",s("Make sure that all users have a UniqID")).'</li>';
//echo '<li>'.PageLinkButton("reconcileusers&amp;option=markinvalidunconfirmed",s("Mark all users with an invalid email as unconfirmed")).'</li>';
echo '<li>'.PageLinkButton('reconcileusers&amp;option=markallhtml',
        s('Mark all subscribers to receive HTML')).'</li>';
echo '<li>'.PageLinkButton('reconcileusers&amp;option=markalltext',
        s('Mark all subscribers to receive text')).'</li>';
//echo '<li>'.s('To try to (automatically)').' '. PageLinkButton("reconcileusers&amp;option=fixinvalidemail",s("Fix emails for users who have an invalid email")).'</li>';
//echo '<li>'.PageLinkButton("reconcileusers&amp;option=removestaleentries",s("Remove Stale entries from the database")).'</li>';
//echo '<li>'.PageLinkButton("reconcileusers&amp;option=mergeduplicates",s("Merge Duplicate Users")).'</li>';
echo '</ul>';
?>
<hr/>
<form method="get">
    <input type="hidden" name="page" value="reconcileusers"/>
    <input type="hidden" name="option" value="markallconfirmed"/>
    <p class="information">
        <?php
        echo sprintf(s('Mark all subscribers on list %s confirmed'), snippetListsSelector(true));
        ?>
    </p>
    <input class="submit" type="submit" value="<?php echo s('Click here') ?>"/>
</form>

<hr/>
<form method="get">
    <input type="hidden" name="page" value="reconcileusers"/>
    <input type="hidden" name="option" value="nolistsnewlist"/>
    <p class="information">
        <?php
        echo sprintf(s('To move all subscribers who are not subscribed to any list to %s'),
            snippetListsSelector());
        ?>
    </p>
    <input class="submit" type="submit" value="<?php echo s('Click here') ?>"/>
</form>

<hr/>
<form method="get">
    <input type="hidden" name="page" value="reconcileusers"/>
    <input type="hidden" name="option" value="bounces"/>
    <p class="information"><?php echo s('To delete all subscribers with more than') ?>
        <select name="num">
            <option>1</option>
            <option>2</option>
            <option selected="selected">5</option>
            <option>10</option>
            <option>15</option>
            <option>20</option>
            <option>50</option>
        </select> <?php echo s('bounces') ?> <input class="button" type="submit"
                                                                        value="<?php echo s('Click here') ?>"/>
</form>

<form method="get">
    <input type="hidden" name="page" value="reconcileusers"/>
    <input type="hidden" name="option" value="blacklist"/>
    <p class="information"><?php echo s('To blacklist all subscribers with more than') ?>
        <select name="num">
            <option>1</option>
            <option>2</option>
            <option selected="selected">5</option>
            <option>10</option>
            <option>15</option>
            <option>20</option>
            <option>50</option>
        </select> <?php echo s('bounces') ?> <input class="button" type="submit"
                                                                        value="<?php echo s('Click here') ?>"/>
</form>

<p class="information"><?php echo s('Note: this will use the total count of bounces on a subscriber, not consecutive bounces') ?></p>

<hr/>

<div id="deleteinvalid">
    <?php echo PageLinkAjax('reconcileusers&amp;option=deleteinvalidemail',
        s('Delete subscribers who have an invalid email address'), '', 'button');
    ?>
</div>

<hr/>

<div id="relinksystembounces">
    <?php echo PageLinkAjax('reconcileusers&amp;option=relinksystembounces',
        s('Associate system bounces to subscriber profiles'), '', 'button');
    ?>
</div>
<hr/>

<div id="deleteblacklistedunsubscribed">
    <?php echo PageLinkAjax('reconcileusers&amp;option=deleteblacklistedunsubscribed',
        s('Delete subscribers who are blacklisted because they unsubscribed'), '', 'button');
    ?>
</div>

<div id="deleteblacklisted">
    <?php echo PageLinkAjax('reconcileusers&amp;option=deleteblacklisted',
        s('Delete all blacklisted subscribers'), '', 'button');
    ?>
</div>

<?php
/*
<!--form method="get">
<table class="reconcileForm"><tr><td colspan="2">
<?php echo s('To resend the request for confirmation to users who signed up and have not confirmed their subscription')?></td></tr>
<tr><td><?php echo s('Date they signed up after')?>:</td><td><?php echo $from->showInput("","",$fromval);?></td></tr>
<tr><td><?php echo s('Date they signed up before')?>:</td><td><?php echo $to->showInput("","",$toval);?></td></tr>
<tr><td colspan="2"><?php echo s('Text to prepend to email')?>:</td></tr>
<tr><td colspan="2"><textarea name="prepend" rows="10" cols="60">
<?php echo s(' Sorry to bother you: we are cleaning up our database and  it appears that you have previously signed up to our mailinglists  and not confirmed your subscription.  We would like to give you the opportunity to re-confirm your  subscription. The instructions on how to confirm are below.   ')?>
</textarea>
</td></tr>
</table>
<input type="hidden" name="page" value="reconcileusers" />
<input type="hidden" name="option" value="resendconfirm" />
<input class="submit" type="submit" value="<?php echo s('Click here')?>" /></form-->
*/
?>
<hr/>
<form method="get">
    <fieldset>
        <legend><?php echo s('To delete subscribers who signed up and have not confirmed their subscription') ?></legend>
        <div class="field"><label
                for="fromdate"><?php echo s('Date they signed up after') ?></label>
            <div id="fromdate"><?php echo $from->showInput('', '', $fromval); ?></div>
        </div>
        <div class="field"><label for="todate"><?php echo s('Date they signed up before') ?></label>
            <div id="todate"><?php echo $to->showInput('', '', $toval); ?></div>
        </div>
    </fieldset>
    <input type="hidden" name="page" value="reconcileusers"/>
    <input type="hidden" name="option" value="deleteunconfirmed"/>
    <input class="submit" type="submit" value="<?php echo s('Click here') ?>"></form>
</form>
