<?php
require_once dirname(__FILE__).'/accesscheck.php';

echo '<hr/><p class="information">';

$access = accessLevel('sendprepared');

switch ($access) {
  case 'owner':
    $subselect = ' where owner = '.$_SESSION['logindetails']['id'];break;
  case 'all':
    $subselect = '';break;
  case 'none':
  default:
    $subselect = ' where id = 0';break;
}

if ($message && $list) {
    $msg_req = Sql_Query("select * from {$tables['message']} where id = $message");
    $msg = Sql_Fetch_Array($msg_req);
    $lists = array();
    if (is_array($list)) {
        if ($list['all']) {
            $res = Sql_query('select * from '.$tables['list']." $subselect");
            while ($row = Sql_fetch_array($res)) {
                if ($row['active']) {
                    array_push($lists, $row['id']);
                }
            }
        } else {
            while (list($key, $val) = each($list)) {
                if ($val == 'signup') {
                    array_push($lists, $key);
                }
            }
        }
    }

    foreach ($lists as $list) {
        $owner = listOwner($list);
        if (!is_array($listowners[$owner])) {
            $listowners[$owner] = array();
        }
        array_push($listowners[$owner], $list);
    }

    while (list($owner, $lists) = each($listowners)) {
        $query = sprintf('insert into %s
      (subject,fromfield,tofield,replyto,message,footer,status,
      entered,userselection,htmlformatted,sendformat,template,owner)
      values("%s","%s","%s","%s","%s","%s","submitted",now(),"%s",%d,"%s",%d,%d)',
        $tables['message'],
        addslashes($msg['subject']),
        addslashes($msg['fromfield']),
        addslashes($msg['tofield']),
        addslashes($msg['replyto']),
        addslashes($msg['message']."\n##LISTOWNER=".$owner),
        addslashes($msg['footer']),
        $msg['userselection'],
        $msg['htmlformatted'],
        $msg['sendformat'],
        $msg['template'], $owner
    );
        Sql_Query($query);
        $messageid = Sql_Insert_Id();
        foreach ($lists as $list) {
            $result = Sql_query("insert into {$tables['listmessage']} (messageid,listid,entered) values($messageid,$list,now())");
        }
    }
    $done = 1;
    ?>
  <h3>Message Queued for sending</h3>
  <?php #echo $num ?> <!--users apply (at the moment, independent of list membership)<p class="x">-->
<?php

} elseif ($send && !$message) {
    ?>
  Please select a message<br/>
  <?php

} elseif ($send && !$list) {
    ?>
 Please select a list to send to<br/>
  <?php

}

if (!$done) {
    print 'To send a prepared message, check the radio button next to the message you want to send and click "Send"';
    print formStart('name="sendpreparedform" class="sendpreparedSend" ');

    $req = Sql_Query("select * from {$tables['message']} where status = 'prepared'");
    if (!Sql_Affected_Rows()) {
        Error('No prepared messages found. You need to '.PageLink2('preparesend', 'Prepare').' one first');
    }
    while ($message = Sql_Fetch_Array($req)) {
        print '<hr/>Subject: <b>'.$message['subject'].'</b>, ';
        print 'From: <b>'.$message['fromfield'].'</b> <br/>';
        print 'Send this message <input type="radio" name="message" value="'.$message['id'].'" /><br/><br/>';
        print '<p class="information">[start of message]</p>';
        print '<iframe src="?page=viewmessage&embed=yes&omitall=yes&amp;id='.$message['id'].'"
    scrolling="auto" width=100% height=450 margin=0 frameborder=0>
  </iframe>';
        print '<p class="information">[end of message]</p>';
    }

    $html = '<hr/><p class="information">Please select the lists you want to send it to:
<ul>
<li><input type="checkbox" name="list[all]" value="signup" />All Lists</li>
';

    $result = Sql_query("SELECT * FROM {$tables['list']} $subselect");
    $num = 0;
    while ($row = Sql_fetch_array($result)) {
        $html .= '<li><input type=checkbox name=list['.$row['id'].'] value=signup ';
        if ($list[$row['id']] == 'signup') {
            $html .= 'checked="checked"';
        }
        $html .= ' />'.$row['name'];
        if ($row['active']) {
            $html .= ' (List is Active)';
        } else {
            $html .= ' (List is not Active)';
        }

        $desc = nl2br(StripSlashes($row['description']));

        $html .= "<br/>$desc</li>";
        $some = 1;
        $list = $row['id'];
        ++$num;
    }

    if (!$some) {
        echo $html.'Sorry there are currently no lists available';
    }
    if ($num == 1) {
        print '<input type="hidden" name="list['.$list.']" value="signup" />';
    } else {
        print $html;
        $buttonmsg = ' to the Selected Mailinglists';
    }

    ?>
</ul>
<input class="submit" type="submit" name=send value="Send Message <?php echo $buttonmsg?>" onclick="document.sendpreparedform.submit()" />
</form>
<?php 
} ?>
