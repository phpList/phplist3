<?php

require_once dirname(__FILE__).'/accesscheck.php';

function parseRSSTemplate($template, $data)
{
    foreach ($data as $key => $val) {
        if (!preg_match("#^\d+$#", $key)) {
            //      print "$key => $val<br/>";
            $template = preg_replace('#\['.preg_quote($key).'\]#i', $val, $template);
        }
    }
    $template = preg_replace("/\[[A-Z\. ]+\]/i", '', $template);

    return $template;
}

function updateRSSStats($items, $type)
{
    global $tables;
    if (!is_array($items)) {
        return;
    }
    foreach ($items as $item) {
        Sql_Query("update {$tables['rssitem']} set $type = $type + 1 where id = $item");
    }
}

function rssUserHasContent($userid, $messageid, $frequency)
{
    global $tables;
    switch ($frequency) {
        case 'weekly':
            $interval = 'interval 7 day';
            break;
        case 'monthly':
            $interval = 'interval 1 month';
            break;
        case 'daily':
        default:
            $interval = 'interval 1 day';
            break;
    }

    $cansend_req = Sql_Query(sprintf('select date_add(last,%s) < now() from %s where userid = %d',
        $interval, $tables['user_rss'], $userid));
    $exists = Sql_Affected_Rows();
    $cansend = Sql_Fetch_Row($cansend_req);
    if (!$exists || $cansend[0]) {
        // we can send this user as far as the frequency is concerned
        // now check whether there is actually some content

        // check what lists to use. This is the intersection of the lists for the
        // user and the lists for the message
        $lists = array();
        $listsreq = Sql_Query(sprintf('
      select %s.listid from %s,%s where %s.listid = %s.listid and %s.userid = %d and
      %s.messageid = %d',
            $tables['listuser'], $tables['listuser'], $tables['listmessage'],
            $tables['listuser'], $tables['listmessage'],
            $tables['listuser'], $userid, $tables['listmessage'], $messageid));
        while ($row = Sql_Fetch_Row($listsreq)) {
            array_push($lists, $row[0]);
        }
        if (!count($lists)) {
            return 0;
        }
        $liststosend = implode(',', $lists);
        // request the rss items that match these lists and that have not been sent to this user
        $itemstosend = array();
        $max = sprintf('%d', getConfig('rssmax'));
        if (!$max) {
            $max = 30;
        }

        $itemreq = Sql_Query("select {$tables['rssitem']}.*
      from {$tables['rssitem']} where {$tables['rssitem']}.list in ($liststosend) order by added desc, list,title limit $max");
        while ($item = Sql_Fetch_Array($itemreq)) {
            Sql_Query("select * from {$tables['rssitem_user']} where itemid = {$item['id']} and userid = $userid");
            if (!Sql_Affected_Rows()) {
                array_push($itemstosend, $item['id']);
            }
        }
        //  print "<br/>Items to send for user $userid: ".sizeof($itemstosend);
        // if it is less than the threshold return nothing
        $threshold = getConfig('rssthreshold');
        if (count($itemstosend) >= $threshold) {
            return $itemstosend;
        } else {
            return array();
        }
    }

    return array();
}
