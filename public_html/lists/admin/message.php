<?php
require_once dirname(__FILE__).'/accesscheck.php';
verifyCsrfGetToken();

$id = sprintf('%d', $_GET['id']);
if (!$id) {
    echo s('Please select a message to display')."\n";
    exit;
}

$access = accessLevel('message');
//print "Access: $access";
switch ($access) {
    case 'owner':
        $subselect = ' where owner = '.$_SESSION['logindetails']['id'];
        $owner_select_and = ' and owner = '.$_SESSION['logindetails']['id'];
        break;
    case 'all':
        $subselect = '';
        $owner_select_and = '';
        break;
    case 'none':
    default:
        $subselect = ' where id = 0';
        $owner_select_and = ' and owner = 0';
        break;
}

if (!empty($_POST['resend']) && is_array($_POST['list'])) {
    if (!empty($_POST['list']['all'])) {
        $res = Sql_query(sprintf('select id from %s %s', $tables['list'], $subselect));
        while ($list = Sql_fetch_array($res)) {
            $result = Sql_query(sprintf('insert into %s (messageid,listid,entered) values(%d,%d,now())',
                $tables['listmessage'], $id, $list['id']));
        }
    } elseif (!empty($_POST['list']['allactive'])) {
        $res = Sql_query(sprintf('select id from %s where active %s', $tables['list'], $owner_select_and));
        while ($list = Sql_fetch_array($res)) {
            $result = Sql_query(sprintf('insert into %s (messageid,listid,entered) values(%d,%d,now())',
                $tables['listmessage'], $id, $list['id']));
        }
    } else {
        foreach ($_POST['list'] as $key => $val) {
            if ($val == $key) {
                $result = Sql_query(sprintf('insert into %s (messageid,listid,entered) values(%d,%d,now())',
                    $tables['listmessage'], $id, $key));
            }
        }
    }
    Sql_Query("update $tables[message] set status = \"submitted\" where id = $id");
    $_SESSION['action_result'] = s('campaign requeued');
    $messagedata = loadMessageData($id);
    $finishSending = mktime($messagedata['finishsending']['hour'], $messagedata['finishsending']['minute'], 0,
        $messagedata['finishsending']['month'], $messagedata['finishsending']['day'],
        $messagedata['finishsending']['year']);
    if ($finishSending < time()) {
        $_SESSION['action_result'] .= '<br />'.s('This campaign is scheduled to stop sending in the past. No mails will be sent.');
        $_SESSION['action_result'] .= '<br />'.PageLinkButton('send&amp;id='.$messagedata['id'].'&amp;tab=Scheduling',
                s('Review Scheduling'));
    }

    Redirect('messages&tab=active');
    exit;
}

require_once $coderoot.'structure.php';

$result = Sql_Fetch_Assoc_query(sprintf('select id, subject from %s where id = %d %s', $tables['message'], $id,
    $owner_select_and));
if (empty($result['id'])) {
    echo s('No such campaign');

    return;
}

// Fetch message details
$msgdata = loadMessageData($id);

// Set heading to campaign title (not necessarily the subject)
$campaignTitle = $msgdata['campaigntitle'];

if ($msgdata['status'] == 'draft' || $msgdata['status'] == 'suspended') {
    echo '<div class="actions">';
    echo '<p>'.PageLinkButton('send&amp;id='.$id, s('Edit this message')).'</p>';
    echo '</div>';
} else {
    echo '<div class="actions">';

    // Print edit campaign button
    $editbutton = new ConfirmButton(
        s('Editing an active or finished campaign will place it back in the draft queue, continue?'),
        PageURL2('send&id='.$id),
        s('Edit campaign'));
    echo '<div class="pull-right">'.$editbutton->show().'</div>';

    // Print view campaign statistics button
    echo PageLinkButton( 'statsoverview&id='.$id, s('Statistics'), s('View statistics'));

    echo '</div>';
}

$content = '<table class="messageView">';
$format = '<tr><td valign="top" class="dataname">%s</td><td valign="top">%s</td></tr>';

$content .= sprintf($format, s('Subject'), htmlentities($msgdata['subject']));
$content .= sprintf($format, s('entered'), formatDateTime( stripslashes($msgdata['entered'] )));
$content .= sprintf($format, s('fromfield'), htmlentities(stripslashes($msgdata['fromfield'])));
$content .= sprintf($format, s('HTML content'), stripslashes($msgdata['message']));
$content .= sprintf($format, s('Text content'), htmlentities(stripslashes($msgdata['textmessage'])));
$content .= sprintf($format, s('footer'), stripslashes($msgdata['footer']));

$finishSending = mktime($msgdata['finishsending']['hour'], $msgdata['finishsending']['minute'], 0,
    $msgdata['finishsending']['month'], $msgdata['finishsending']['day'], $msgdata['finishsending']['year']);
$embargoTime = mktime($msgdata['embargo']['hour'], $msgdata['embargo']['minute'], 0,
    $msgdata['embargo']['month'], $msgdata['embargo']['day'], $msgdata['embargo']['year']);
$repeatuntilTime = mktime($msgdata['repeatuntil']['hour'], $msgdata['repeatuntil']['minute'], 0,
    $msgdata['repeatuntil']['month'], $msgdata['repeatuntil']['day'], $msgdata['repeatuntil']['year']);
$requeueuntilTime = mktime($msgdata['requeueuntil']['hour'], $msgdata['requeueuntil']['minute'], 0,
    $msgdata['requeueuntil']['month'], $msgdata['requeueuntil']['day'], $msgdata['requeueuntil']['year']);

if ($embargoTime > time()) {
    $content .= sprintf($format, s('Embargoed until'), formatDateTime( date('Y-m-d H:i', $embargoTime )));
}
if ($finishSending > time()) {
    $content .= sprintf($format, s('Stop sending after'), formatDateTime( date('Y-m-d H:i', $finishSending )));
}
if (!empty($msgdata['repeatinterval'])) {
    $content .= sprintf($format, s('Repeating'),
        s('every %s until %s', s($repetitionLabels[$msgdata['repeatinterval']]),
            formatDateTime( date('Y-m-d H:i', $repeatuntilTime ))));
}
if (!empty($msgdata['requeueinterval'])) {
    $content .= sprintf($format, s('Requeueing'),
        s('every %s until %s', s($repetitionLabels[$msgdata['requeueinterval']]),
            formatDateTime( date('Y-m-d H:i', $requeueuntilTime ))));
}

foreach ($plugins as $pi) {
    if ($piAdd = $pi->viewMessage($id, $msgdata)) {
        $content .= sprintf($format, $piAdd[0], $piAdd[1]);
    }
}

if (ALLOW_ATTACHMENTS) {
    $content .= '<tr><td colspan="2"><h3>'.s('Attachments for this campaign').'</h3></td></tr>';
    $req = Sql_Query("select * from {$tables['message_attachment']},{$tables['attachment']}
    where {$tables['message_attachment']}.attachmentid = {$tables['attachment']}.id and
    {$tables['message_attachment']}.messageid = $id");
    if (!Sql_Affected_Rows()) {
        $content .= '<tr><td colspan="2">'.s('No attachments').'</td></tr>';
    }
    while ($att = Sql_Fetch_array($req)) {
        $content .= sprintf('<tr><td>%s:</td><td>%s</td></tr>', s('Filename'),  htmlentities($att['remotefile']));
        $content .= sprintf('<tr><td>%s:</td><td>%s</td></tr>', s('Size'),
            formatBytes($att['size']));
        $content .= sprintf('<tr><td>%s:</td><td>%s</td></tr>', s('Mime Type'), htmlentities($att['mimetype']));
        $content .= sprintf('<tr><td>%s:</td><td>%s</td></tr>', s('Description'),
            htmlentities($att['description']));
    }
    // print '</table>';
}

$content .= sprintf(
    '<tr id="targetlists"><td colspan="2"><h4>%s:</h4></td></tr>',
    empty($msgdata['sent'])
        ? s('This campaign will be sent to subscribers who are member of the following lists')
        : s('This campaign has been sent to subscribers who are members of the following lists')
);

$lists_done = array();
$result = Sql_Query(sprintf('select l.name, l.id from %s lm, %s l where lm.messageid = %d and lm.listid = l.id',
    $tables['listmessage'], $tables['list'], $id));
if (!Sql_Affected_Rows()) {
    $content .= '<tr><td colspan="2">'.s('None yet').'</td></tr>';
}
while ($lst = Sql_fetch_array($result)) {
    array_push($lists_done, $lst['id']);
    $content .=
        '<tr>
    <td>' . PageLinkButton('members&amp;id=' . $lst['id'], stripslashes($lst['name']), '', '', s('View Members')) . '</td>
</tr>';
}

if ($msgdata['excludelist']) {
    $content .= '<tr><td colspan="2"><h4>'.s('Except when they were also member of these lists').':</h4></td></tr>';
    $result = Sql_Query(sprintf('select l.name, l.id from %s l where id in (%s)', $tables['list'],
        implode(',', $msgdata['excludelist'])));
    while ($lst = Sql_fetch_array($result)) {
        $content .= sprintf('<tr><td><!--%d--></td><td>%s</td></tr>', $lst['id'], stripslashes($lst['name']));
    }
}
$content .= '</table>';

$panel = new UIPanel(htmlspecialchars($campaignTitle), $content);
echo $panel->display();
?>

    <a name="resend"></a><p class="information"><?php echo s('Send this campaign to another list'); ?>:</p>
<?php echo formStart(' class="messageResend" ') ?>
    <input type="hidden" name="id" value="<?php echo $id ?>"/>

<?php

if (count($lists_done)) {
    if (empty($subselect)) {
        $subselect .= ' where id not in ('.implode(',', $lists_done).')';
    } else {
        $subselect .= ' and id not in ('.implode(',', $lists_done).')';
    }
}
$selectAgain = listSelectHTML(array(), 'list', $subselect, '');

echo $selectAgain;

echo '<input class="submit" type="submit" name="resend" value="'.s('Resend').'" /></form>';
