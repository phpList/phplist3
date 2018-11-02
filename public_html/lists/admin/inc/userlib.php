<?php

/**
 * library with user functions
 *  *.
 */
require_once dirname(__FILE__).'/accesscheck.php';

function initialiseUserSession()
{
    if (!is_array($_SESSION['userdata'])) {
        $_SESSION['userdata'] = array();
    }
}

function getEveryoneGroupID()
{
    $ev_req = Sql_Fetch_Row_Query('select id from groups where name = "Everyone"');
    $everyone_groupid = $ev_req[0];
    if (!$everyone_groupid) {
        Sql_Query('insert into groups (name) values("Everyone")');
        $everyone_groupid = Sql_Insert_Id();
    }

    return $everyone_groupid;
}

function getUniqid($table = '')
{
    global $tables;
    if (!$table) {
        if ($tables['user']) {
            $table = $tables['user'];
        } else {
            $table = 'user';
        }
    }
    $id = bin2hex(random_bytes(16));

    return $id;
}

function obfuscateString($string)
{
    $l = strlen(($string));
    if ($l > 3) {
        $obf = substr($string, 0, 1);
        $obf .= str_repeat('*', $l - 2);
        $obf .= substr($string, -1, 1);
    } else {
        $obf = str_repeat('*', $l);
    }

    return $obf;
}

function obfuscateEmailAddress($emailAddress)
{
    if (is_email($emailAddress)) {
        list($userName, $domain) = explode('@', strtolower($emailAddress));

        $obf = obfuscateString($userName).'@';

        $domainParts = explode('.', $domain);
        $TLD = array_pop($domainParts);

        foreach ($domainParts as $dPart) {
            $obf .= obfuscateString($dPart).'.';
        }

        return $obf.$TLD;
    }

    return $emailAddress;
}

function userSelect($fieldname, $current = '')
{
    global $tables;
    $html = sprintf('<select name="%s">', $fieldname);
    $req = Sql_Query(sprintf('select id,email from %s order by email', $tables['user']));
    while ($row = Sql_Fetch_Array($req)) {
        $html .= sprintf('<option value="%d" %s>%s</option>', $row['id'],
            $current == $row['id'] ? 'selected="selected"' : '', $row['email']);
    }
    $html .= '</select>';

    return $html;
}

/**
 * Delete a subscriber's records from user group table
 */
function deleteUserGroup($id)
{
    if (Sql_table_exists('user_group')) {
        Sql_Query(sprintf('delete from user_group where userid = %d', $id), 1);
    }
}

/**
 * Trigger deleteUser() hook for plugins
 */
function triggerDeleteUserPluginsHook($id)
{
    global $plugins;

    // allow plugins to delete their data
    foreach ($plugins as $plugin) {
        $plugin->deleteUser($id);
    }
}

/**
 * Delete a subscriber's records from blacklist tables
 */
function deleteUserBlacklistRecords($id)
{
    global $tables;
    $userEmail = getUserEmail($id);

    Sql_Query('delete from '.$tables['user_blacklist'].' where email = "'.$userEmail.'"');
    Sql_Query('delete from '.$tables['user_blacklist_data'].' where email = "'.$userEmail.'"');
}

/**
 * Delete a subscriber's records except from blacklist tables
 */
function deleteUserRecordsLeaveBlacklistRecords($id)
{
    global $tables;
    
    Sql_Query('delete from '.$tables['linktrack_uml_click'].' where userid = '.$id);
    Sql_Query('delete from '.$tables['listuser'].' where userid = '.$id);
    Sql_Query('delete from '.$tables['usermessage'].' where userid = '.$id);
    Sql_Query('delete from '.$tables['user_attribute'].' where userid = '.$id);
    Sql_Query('delete from '.$tables['user_history'].' where userid = '.$id);
    Sql_Query('delete from '.$tables['user_message_bounce'].' where user = '.$id);
    Sql_Query('delete from '.$tables['user_message_forward'].' where user = '.$id);
    Sql_Query('delete from '.$tables['user'].' where id = '.$id);
    Sql_Query('delete from '.$tables['user_message_view'].' where userid = '.$id);
}

/**
 * Delete a subscriber but leave blacklist data
 */
function deleteUserLeaveBlacklist($id)
{
    deleteUserRecordsLeaveBlacklistRecords($id);
    deleteUserGroup($id);
    triggerDeleteUserPluginsHook($id);
}

/**
 * Delete a subscriber including blacklist data
 */
function deleteUserIncludeBlacklist($id)
{
    // Note: deleteUserBlacklistRecords() must be executed first, else the ID 
    // to email lookup fails due to the missing record
    deleteUserBlacklistRecords($id);
    deleteUserRecordsLeaveBlacklistRecords($id);
    deleteUserGroup($id);
    triggerDeleteUserPluginsHook($id);
}

/**
 * Wrapper for backwards compatibility
 */
function deleteUser($id)
{
    deleteUserLeaveBlacklist($id);
}

function addNewUser($email, $password = '')
{
    if (empty($GLOBALS['tables']['user'])) {
        $GLOBALS['tables']['user'] = 'user';
    }
    /*
        "id" => array("integer not null primary key auto_increment","sys:ID"),
        "email" => array("varchar(255) not null","Email"),
        "confirmed" => array("tinyint default 0","sys:Is the email of this user confirmed"),
        "entered" => array("datetime","sys:Time Created"),
        "modified" => array("timestamp","sys:Time modified"),
        "uniqid" => array("varchar(255)","sys:Unique ID for User"),
        "unique" => array("(email)","sys:unique"),
        "htmlemail" => array("tinyint default 0","Send this user HTML emails"),
        "subscribepage" => array("integer","sys:Which page was used to subscribe"),
        "rssfrequency" => array("varchar(100)","rss Frequency"), // Leftover from the preplugin era
        "password" => array("varchar(255)","Password"),
        "passwordchanged" => array("datetime","sys:Last time password was changed"),
        "disabled" => array("tinyint default 0","Is this account disabled?"),
        "extradata" => array("text","Additional data"),
    */
    // insert into user db
    $exists = Sql_Fetch_Row_Query(sprintf('select id from %s where email = "%s"',
        $GLOBALS['tables']['user'], $email));
    if ($exists[0]) {
        return $exists[0];
    }

    $blacklist = isBlackListed($email);
    $passwordEnc = encryptPass($password);
    Sql_Query(sprintf('insert into %s set email = "%s", blacklisted = "%d",
    entered = now(),modified = now(),password = "%s",
    passwordchanged = now(),disabled = 0,
    uniqid = "%s",htmlemail = 1, uuid = "%s"
    ', $GLOBALS['tables']['user'], $email, $blacklist, $passwordEnc, getUniqid(), (string) uuid::generate(4)));

    $id = Sql_Insert_Id();

    return $id;
}

function getAttributeIDbyName($sName)
{
    // Looks for an attribute named sName.
    // Returns table ID or 0 if not found.
    // Can also be used as 'isAttribute'

    if (empty($sName)) {
        return 0;
    }
    global $usertable_prefix, $tables;
    // workaround for integration webbler/phplist
    if (!isset($usertable_prefix)) {
        $usertable_prefix = '';
    }
    if ($tables['attribute']) {
        $att_table = $tables['attribute'];
        $user_att_table = $tables['user_attribute'];
    } else {
        $att_table = 'attribute';
        $user_att_table = 'user_attribute';
    }

    $res = Sql_Query(sprintf('SELECT id FROM %s%s WHERE name = "%s"',
        $usertable_prefix, $att_table, $sName));
    $row = Sql_Fetch_row($res);

//  dbg($row,'$$row');
    return $row[0];
}

/**
 * Returns attribute name for ID.
 *
 * @param $iAttribute
 *
 * @return unknown_type
 */
function getAttributeNamebyID($iAttribute)
{
    if (empty($iAttribute)) {
        return;
    }
    global $usertable_prefix;
    // workaround for integration webbler/phplist
    if (!isset($usertable_prefix)) {
        $usertable_prefix = '';
    }
    if ($tables['attribute']) {
        $att_table = $tables['attribute'];
        $user_att_table = $tables['user_attribute'];
    } else {
        $att_table = 'attribute';
        $user_att_table = 'user_attribute';
    }

    $res = Sql_Query(sprintf('SELECT name FROM %s%s WHERE id = %d',
        $usertable_prefix, $att_table, $iAttribute));
    $row = Sql_Fetch_row($res);

//  dbg($row,'$$row');
    return $row[0];
}

function AttributeValue($table, $value)
{
    global $table_prefix;
    // workaround for integration webbler/phplist
    if (!isset($table_prefix)) {
        $table_prefix = 'phplist_';
    }

    if (strpos($value, ',') !== false) {
        $result = '';
        $res = Sql_Query(sprintf('select name from %slistattr_%s where id in (%s)',
            $table_prefix, $table, $value));
        while ($row = Sql_Fetch_row($res)) {
            $result .= $row[0].'; ';
        }

        return substr($result, 0, -2);
    } elseif ($value) {
        $res = Sql_Query(sprintf('select name from %slistattr_%s where id = %d',
            $table_prefix, $table, $value));
        $row = Sql_Fetch_row($res);

        return $row[0];
    } else {
        //    return "Invalid Attribute Index";
    }

    return '';
}

/**
 * Convert subscriber ID to email address simply
 */
function getUserEmail($id)
{
    global $tables;
    
    $userid = Sql_Fetch_Row_Query("select email from {$tables['user']} where id = \"$id\"");    
    return $userid[0];
}

function existUserID($id = 0)
{
    global $table_prefix, $tables;
    // workaround for integration webbler/phplist
    if (!isset($table_prefix)) {
        $table_prefix = 'phplist_';
    }

    if (isset($tables['attribute'])) {
        $usertable = $tables['user'];
    } else {
        $usertable = 'user';
    }

    $userid = Sql_Fetch_Row_Query("select id from {$usertable} where id = \"$id\"");

    return $userid[0];
}

function getUserAttributeValues($email = '', $id = 0, $bIndexWithShortnames = false)
{
    global $table_prefix, $tables;
    if (!$email && !$id) {
        return;
    }
    // workaround for integration webbler/phplist
    if (!isset($table_prefix)) {
        $table_prefix = 'phplist_';
    }

    if (isset($tables['attribute'])) {
        $att_table = $tables['attribute'];
        $user_att_table = $tables['user_attribute'];
        $usertable = $tables['user'];
    } else {
        $att_table = 'attribute';
        $user_att_table = 'user_attribute';
        $usertable = 'user';
    }
    $result = array();
    if ($email && !$id) {
        $userid = Sql_Fetch_Row_Query("select id from {$usertable} where email = \"$email\"");
        $id = $userid[0];
    }
    if (!$id) {
        return $result;
    }
    /* https://mantis.phplist.org/view.php?id=17708
     * instead of only returning the attributes for which a subscriber has a value
     * return all attributes
     */
    //$att_req = Sql_Query(sprintf('select
    //%s.name,%s.id from %s,%s
    //where %s.userid = %s and %s.id = %s.attributeid',
    //$att_table,
    //$att_table,
    //$user_att_table,
    //$att_table,
    //$user_att_table,
    //$id,
    //$att_table,
    //$user_att_table
    //));

    $att_req = Sql_Query(sprintf('select id,name from %s', $att_table));
    while ($att = Sql_fetch_array($att_req)) {
        if ($bIndexWithShortnames) {
            $result['attribute'.$att['id']] = UserAttributeValue($id, $att['id']);
        } else {
            $result[$att['name']] = UserAttributeValue($id, $att['id']);
        }
    }

    return $result;
}

function UserAttributeValue($user = 0, $attribute = 0)
{
    // workaround for integration webbler/phplist
    global $table_prefix, $tables;
    if (!isset($table_prefix)) {
        $table_prefix = 'phplist_';
    }
    if (!$user || !$attribute) {
        return;
    }

    if (isset($tables['attribute'])) {
        $att_table = $tables['attribute'];
        $user_att_table = $tables['user_attribute'];
    } else {
        $att_table = 'attribute';
        $user_att_table = 'user_attribute';
    }
    $att = Sql_Fetch_array_Query("select * from $att_table where id = $attribute");
    switch ($att['type']) {
        case 'checkboxgroup':
            //     print "select value from $user_att_table where userid = $user and attributeid = $attribute";
            $val_ids = Sql_Fetch_Row_Query("select value from $user_att_table where userid = $user and attributeid = $attribute");
            if ($val_ids[0]) {
                //       print '<br/>1 <b>'.$val_ids[0].'</b>';
                if (function_exists('cleancommalist')) {
                    $val_ids[0] = cleanCommaList($val_ids[0]);
                }
                //# make sure the val_ids as numbers
                $values = explode(',', $val_ids[0]);
                $ids = array();
                foreach ($values as $valueIndex) {
                    $iValue = sprintf('%d', $valueIndex);
                    if ($iValue) {
                        $ids[] = $iValue;
                    }
                }
                if (!count($ids)) {
                    return '';
                }
                $val_ids[0] = implode(',', $ids);
                //       print '<br/>2 <b>'.$val_ids[0].'</b>';
                $value = '';
                $res = Sql_Query("select $table_prefix".'listattr_'.$att['tablename'].".name
          from $user_att_table,$table_prefix".'listattr_'.$att['tablename']."
          where $user_att_table".'.userid = '.$user." and
          $table_prefix".'listattr_'.$att['tablename'].".id in ($val_ids[0]) and
          $user_att_table".'.attributeid = '.$attribute);
                while ($row = Sql_Fetch_row($res)) {
                    $value .= $row[0].'; ';
                }
                $value = substr($value, 0, -2);
            } else {
                $value = '';
            }
            break;
        case 'select':
        case 'radio':
            $res = Sql_Query("select $table_prefix".'listattr_'.$att['tablename'].".name
        from $user_att_table,$table_prefix".'listattr_'.$att['tablename']."
        where $user_att_table".'.userid = '.$user." and
        $table_prefix".'listattr_'.$att['tablename'].".id = $user_att_table".".value and
        $user_att_table".'.attributeid = '.$attribute);
            $row = Sql_Fetch_row($res);
            $value = $row[0];
            break;
        default:
            $res = Sql_Query(sprintf('select value from %s where
        userid = %d and attributeid = %d', $user_att_table, $user, $attribute));
            $row = Sql_Fetch_row($res);
            $value = $row[0];
    }

    return stripslashes($value);
}

function userName()
{
    global $config;
    if (!is_array($config['nameattributes'])) {
        return '';
    }
    $res = '';
    foreach ($config['nameattributes'] as $att) {
        if (isset($_SESSION['userdata'][$att]['displayvalue'])) {
            $res .= $_SESSION['userdata'][$att]['displayvalue'].' ';
        }
    }

    return rtrim($res);
}

/**
 * Check if a subscriber is blacklisted in blacklist data tables
 * @note Ignores the user table
 */
function isBlackListed($email = '', $immediate = true)
{
    if (!$email) {
        return 0;
    }
    if (!Sql_Table_exists($GLOBALS['tables']['user_blacklist'])) {
        return 0;
    }
    if (!$immediate) {
        // allow 5 minutes to send the last message acknowledging unsubscription
        $gracetime = sprintf('%d', $GLOBALS['blacklist_gracetime']);
        if (!$gracetime || $gracetime > 15 || $gracetime < 0) {
            $gracetime = 5;
        }
    } else {
        $gracetime = 0;
    }
    $req = Sql_Query(sprintf('select * from %s where email = "%s" and date_add(added,interval %d minute) < now()',
        $GLOBALS['tables']['user_blacklist'], sql_escape($email), $gracetime));

    return Sql_Affected_Rows();
}

/**
 * Check if a subscriber is blacklisted in the user table
 * @note Ignores blacklist data tables
 */
function isBlackListedID($userid = 0)
{
    if (!$userid) {
        return 0;
    }
    $email = Sql_Fetch_Row_Query("select email from {$GLOBALS['tables']['user']} where id = $userid");

    return isBlackListed($email[0]);
}

function unBlackList($userid = 0)
{
    if (!$userid) {
        return;
    }
    $email = Sql_Fetch_Row_Query("select email from {$GLOBALS['tables']['user']} where id = $userid");
    Sql_Query(sprintf('delete from %s where email = "%s"',
        $GLOBALS['tables']['user_blacklist'], $email[0]));
    Sql_Query(sprintf('delete from %s where email = "%s"',
        $GLOBALS['tables']['user_blacklist_data'], $email[0]));
    Sql_Query(sprintf('update %s set blacklisted = 0 where id = %d', $GLOBALS['tables']['user'], $userid));
    if (isset($_SESSION['logindetails']['adminname'])) {
        $msg = s('Removed from blacklist by %s', $_SESSION['logindetails']['adminname']);
    } else {
        $msg = s('Removed from blacklist');
    }
    addUserHistory($email[0], $msg, '');
}

function addUserToBlackList($email, $reason = '')
{
    Sql_Query(sprintf('update %s set blacklisted = 1 where email = "%s"',
        $GLOBALS['tables']['user'], addslashes($email)));
    //0012262: blacklist only email when email bounces. (not users): Function split so email can be blacklisted without blacklisting user
    addEmailToBlackList($email, $reason);
}

function addUserToBlackListID($id, $reason = '')
{
    Sql_Query(sprintf('update %s set blacklisted = 1 where id = %s',
        $GLOBALS['tables']['user'], $id));
    //0012262: blacklist only email when email bounces. (not users): Function split so email can be blacklisted without blacklisting user
    $email = Sql_Fetch_Row_Query("select email from {$GLOBALS['tables']['user']} where id = $id");
    addEmailToBlackList($email[0], $reason);
}

function addEmailToBlackList($email, $reason = '', $date = '')
{
    if (empty($date)) {
        $sqldate = 'now()';
    } else {
        $sqldate = '"'.$date.'"';
    }
    //0012262: blacklist only email when email bounces. (not users): Function split so email can be blacklisted without blacklisting user
    Sql_Query(sprintf('insert ignore into %s (email,added) values("%s",%s)',
        $GLOBALS['tables']['user_blacklist'], sql_escape($email), $sqldate));
    // save the reason, and other data
    Sql_Query(sprintf('insert ignore into %s (email,name,data) values("%s","%s","%s")',
        $GLOBALS['tables']['user_blacklist_data'], sql_escape($email),
        'reason', addslashes($reason)));
    foreach (array('REMOTE_ADDR') as $item) { // @@@do we want to know more?
        if (isset($_SERVER[$item])) {
            Sql_Query(sprintf('insert ignore into %s (email,name,data) values("%s","%s","%s")',
                $GLOBALS['tables']['user_blacklist_data'], addslashes($email),
                $item, addslashes($_SERVER[$item])));
        }
    }
    addUserHistory($email, s('Added to blacklist'), s('Added to blacklist for reason %s', $reason));
    //# call plugins to tell them
    if (isset($GLOBALS['plugins']) && is_array($GLOBALS['plugins'])) {
        foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
            if (method_exists($plugin, 'blacklistEmail')) {
                $plugin->blacklistEmail($email);
            }
        }
    }
}

function UserAttributeValueSelect($user = 0, $attribute = 0)
{
    //  if (!$user || !$attribute) return;
    global $table_prefix, $tables;
    // workaround for integration webbler/phplist
    if (!isset($table_prefix)) {
        $table_prefix = 'phplist_';
    }

    if ($tables['attribute']) {
        $att_table = $tables['attribute'];
        $user_att_table = $tables['user_attribute'];
    } else {
        $att_table = 'attribute';
        $user_att_table = 'user_attribute';
    }
    if (!Sql_Table_exists($att_table)) {
        return "broken attribute $attribute";
    }
    $att = Sql_Fetch_array_Query("select * from $att_table where id = $attribute");
    // $value = UserAttributeValue($att["tablename"],$attribute);
    $value = UserAttributeValue($user, $attribute);
//  $html = 'Value: '.$value;
    $html = sprintf('<select name="attribute[%d]" style="attributeinput" >', $attribute);
    $res = Sql_Query("select id,name from $table_prefix".'listattr_'.$att['tablename'].' order by listorder,name');
    if (!Sql_Affected_Rows()) {
        return '(No values available)';
    }
    $html .= '<option value="0">-- no value</option>';
    while ($row = Sql_Fetch_Row($res)) {
        if ($row[1] != '') {
            $html .= sprintf('<option value="%d" %s>%s </option>', $row[0],
                $row[1] == $value ? 'selected="selected"' : '', $row[1]);
        }
    }

    return $html.'</select>';
}

function UserAttributeValueCbGroup($user = 0, $attribute = 0)
{
    //  if (!$user || !$attribute) return;
    global $table_prefix, $tables;
    if ($tables['attribute']) {
        $att_table = $tables['attribute'];
        $user_att_table = $tables['user_attribute'];
    } else {
        $att_table = 'attribute';
        $user_att_table = 'user_attribute';
    }
    // workaround for integration webbler/phplist
    if (!isset($table_prefix)) {
        $table_prefix = 'phplist_';
    }

    $att = Sql_Fetch_array_Query("select * from $att_table where id = $attribute");
    $values_req = Sql_Fetch_Row_Query("select value from $user_att_table where userid = $user and attributeid = $attribute");
    $values = explode(',', $values_req[0]);
    $html = sprintf('<input type="hidden" name="cbgroup[]" value="%d" /><table>', $attribute);
    // $html = sprintf('<select name="attribute[%d]" style="attributeinput" >',$attribute);
    $res = Sql_Query("select id,name from $table_prefix".'listattr_'.$att['tablename'].' order by listorder,name');
    if (!Sql_Affected_Rows()) {
        return '(No values available)';
    }
    while ($row = Sql_Fetch_Row($res)) {
        $html .= sprintf('<tr><td><input type="checkbox" name="cbgroup%d[]" value="%d" %s /></td><td>%s</td></tr>',
            $attribute, $row[0], in_array($row[0], $values) ? 'checked' : '', $row[1]);
    }

    return $html.'</table>';
}

function userGroups($loginname)
{
    $result = array();
    if (Sql_Table_exists('user_group')) {
        $req = Sql_Query(sprintf('select groupid from user_group,user where user_group.userid = user.id and user.email = "%s"',
            addslashes($loginname)));
        while ($row = Sql_Fetch_Row($req)) {
            array_push($result, $row[0]);
        }
        $ev = getEveryoneGroupID();
        array_push($result, $ev);
    }

    return $result;
}

function is_email($email)
{
    if (function_exists('idn_to_ascii') && mb_strlen($email) != strlen($email)) {
        $elements = explode('@', strrev($email), 2);
        if (!empty($elements[0]) && !empty($elements[1])) {
            $email = strrev($elements[1]).'@'.idn_to_ascii(strrev($elements[0]));
        }
        unset($elements);
    }

    //@@ dont_require_validemail should be replaced by EMAIL_ADDRESS_VALIDATION_LEVEL
    if (isset($GLOBALS['config']) && isset($GLOBALS['config']['dont_require_validemail']) && $GLOBALS['config']['dont_require_validemail']) {
        return 1;
    }

    $email = trim($email);
    //# remove XN-- before matching
    $email = preg_replace('/@XN--/i', '@', $email);

    //# do some basic validation first
    // quite often emails have two @ signs
    $ats = substr_count($email, '@');
    if ($ats != 1) {
        return 0;
    }

    //# fail on emails starting or ending "-" or "." in the pre-at, seems to happen quite often, probably cut-n-paste errors
    if (preg_match('/^-/', $email) ||
        preg_match('/-@/', $email) ||
        preg_match('/\.@/', $email) ||
        preg_match('/^\./', $email) ||
        preg_match('/^\-/', $email) ||
        strpos($email, '\\') === 0
    ) {
        return 0;
    }

    $tlds = getConfig('internet_tlds');
    if (empty($tlds)) {
        $tlds = 'ac|ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|asia|at|au|aw|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cat|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cs|cu|cv|cx|cy|cz|de|dev|dj|dk|dm|do|dz|ec|edu|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|home|hr|ht|hu|id|ie|il|im|in|info|int|io|iq|ir|is|it|jm|je|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|loc|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mil|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nt|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tel|tf|tg|th|tj|tk|tm|tn|to|tp|tr|travel|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw';
    }

    switch (EMAIL_ADDRESS_VALIDATION_LEVEL) {
        case 0: // No email address validation.
            return 1;
            break;

        case 2: // RFC821 email validation without escaping and quoting of local part
        case 3: // RFC821 email validation.
            // $email is a valid address as defined by RFC821
            // Except:
            //   Length of domainPart is not checked
            //   Not accepted are CR and LF even if escaped by \
            //   Not accepted is Folding
            //   Not accepted is literal domain-part (eg. [1.0.0.127])
            //   Not accepted is comments (eg. (this is a comment)@example.com)
            // Extra:
            //   topLevelDomain can only be one of the defined ones
            $escapedChar = '\\\\[\\x01-\\x09\\x0B-\\x0C\\x0E-\\x7F]';   // CR and LF excluded for safety reasons
            $unescapedChar = "[a-zA-Z0-9!#$%&'*\+\-\/=?^_`{|}~]";
            if (EMAIL_ADDRESS_VALIDATION_LEVEL == 2) {
                $char = "$unescapedChar";
            } else {
                $char = "($unescapedChar|$escapedChar)";
            }
            $dotString = "$char((\.)?$char){0,63}";

            $qtext = '[\\x01-\\x09\\x0B-\\x0C\\x0E-\\x21\\x23-\\x5B\\x5D-\\x7F]'; // All but <LF> x0A, <CR> x0D, quote (") x22 and backslash (\) x5c
            $qchar = "$qtext|$escapedChar";
            $quotedString = "\"($qchar){1,62}\"";
            if (EMAIL_ADDRESS_VALIDATION_LEVEL == 2) {
                $localPart = "$dotString";  // without escaping and quoting of local part
            } else {
                $localPart = "($dotString|$quotedString)";
            }
            $topLevelDomain = '('.$tlds.')';
            $domainLiteral = "((([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])\.){3}([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5]))";

            $domainPart = "([a-zA-Z0-9](-?[a-zA-Z0-9])*(\.[a-zA-Z0-9](-?[a-zA-Z0-9])*)*\.$topLevelDomain|$domainLiteral)";
            $validEmailPattern = "/^$localPart@$domainPart$/i"; // result: /^(([a-zA-Z0-9!#$%&'*\+\-\/=?^_`{|}~]|\\[\x01-\x09\x0B-\x0C\x0E-\x7F])((\.)?([a-zA-Z0-9!#$%&'*\+\-\/=?^_`{|}~]|\\[\x01-\x09\x0B-\x0C\x0E-\x7F])){0,63}|"([\x01-\x09\x0B-\x0C\x0E-\x21\x23-\x5B\x5D-\x7F]|\\[\x01-\x09\x0B-\x0C\x0E-\x7F]){1,62}")@([a-zA-Z0-9](-?[a-zA-Z0-9])*(\.[a-zA-Z](-?[a-zA-Z0-9])*)*\.(ac|ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|at|au|aw|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cat|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cs|cu|cv|cx|cy|cz|de|dev|dj|dk|dm|do|dz|ec|edu|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|home|hr|ht|hu|id|ie|il|im|in|info|int|io|iq|ir|is|it|jm|je|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|loc|ls|lt|lu|lv|ly|ma|mc|md|mg|mh|mil|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nt|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|quipu|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)|((([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])\.){3}([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])))$/i

            if (preg_match($validEmailPattern, $email)) {
                return 1;
            } else {
                return 0;
            }
            break;

        default: // 10.4 style email validation

            // hmm, it seems people are starting to have emails with & and ' or ` chars in the name

            $pattern = "/^[\&\'-_.[:alnum:]]+@((([[:alnum:]]|[[:alnum:]][[:alnum:]-]*[[:alnum:]])\.)+('.$tlds.')|(([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])\.){3}([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5]))$/i";

            if (preg_match($pattern, $email)) {
                return 1;
            } else {
                return 0;
            }
            break;
    }
}

function addUserHistory($email, $msg, $detail)
{
    global $table_prefix, $tables;
    if (isset($tables['user'])) {
        $user_table = $tables['user'];
    } else {
        $user_table = 'user';
    }
    if (isset($tables['user_history'])) {
        $user_his_table = $tables['user_history'];
    } else {
        $user_his_table = 'user_history';
    }
    if (empty($detail)) { //# ok duplicated, but looks better :-)
        $detail = $msg;
    }

    $sysinfo = '';
    $sysarrays = array_merge($_ENV, $_SERVER);
    if (isset($GLOBALS['userhistory_systeminfo']) && is_array($GLOBALS['userhistory_systeminfo'])) {
        foreach ($GLOBALS['userhistory_systeminfo'] as $key) {
            if (isset($sysarrays[$key])) {
                $sysinfo .= "\n$key = $sysarrays[$key]";
            }
        }
    } elseif (isset($GLOBALS['config']['userhistory_systeminfo']) && is_array($GLOBALS['config']['userhistory_systeminfo'])) {
        foreach ($GLOBALS['config']['userhistory_systeminfo'] as $key) {
            if ($sysarrays[$key]) {
                $sysinfo .= "\n$key = $sysarrays[$key]";
            }
        }
    } else {
        $default = array('HTTP_USER_AGENT', 'HTTP_REFERER', 'REMOTE_ADDR', 'REQUEST_URI');
        foreach ($sysarrays as $key => $val) {
            if (in_array($key, $default)) {
                $sysinfo .= "\n".strip_tags($key).' = '.htmlspecialchars($val);
            }
        }
    }

    $userid = Sql_Fetch_Row_Query("select id from $user_table where email = \"$email\"");
    if ($userid[0]) {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = '';
        }
        Sql_Query(sprintf('insert into %s (ip,userid,date,summary,detail,systeminfo)
            values("%s",%d,now(),"%s","%s","%s")', $user_his_table, $ip, $userid[0], sql_escape($msg),
            sql_escape(htmlspecialchars($detail)), sql_escape($sysinfo)));
    }
}

function validateEmail($email)
{
    if (!empty($GLOBALS['config']['dont_require_validemail'])) {
        return 1;
    }
    //if (!isset($GLOBALS["check_for_host"])) {
    $GLOBALS['check_for_host'] = 0;
    //}
    if (!empty($email) && $GLOBALS['check_for_host']) {
        if (strpos($email, '@')) {
            list($username, $domaincheck) = explode('@', $email);
            // checking for an MX is not sufficient
            //    $mxhosts = array();
            //    $validhost = getmxrr ($domaincheck,$mxhosts);
            $validhost = checkdnsrr($domaincheck, 'MX') || checkdnsrr($domaincheck, 'A');
        } else {
            $validhost = 0;
        }
    } else {
        $validhost = 1;
    }
    $pluginValidated = true;
    foreach ($GLOBALS['plugins'] as $plName => $plugin) {
        $pluginValidated = $pluginValidated && $plugin->validateEmailAddress($email);
    }

    return $pluginValidated && $validhost && is_email($email);
}

function validMod10($no)
{
    $dups = array();
    $rev = strrev($no);
    for ($i = 0; $i < strlen($rev); ++$i) {
        if ($i % 2 == 1) {
            array_push($dups, substr($rev, $i, 1) * 2);
        } else {
            array_push($dups, substr($rev, $i, 1));
        }
    }
    $total = 0;
    foreach ($dups as $dig) {
        for ($i = 0; $i < strlen($dig); ++$i) {
            $total += substr($dig, $i, 1);
        }
        // print "$dig - $total<br/>";
    }

    return $total % 10 == 0;

// print "$no";
}

function validateCC($ccno)
{
    // credit card validation routines here
    // major credit cards that you might want to validate.

    //CARD TYPE Prefix Length Check digit algorithm
    //MASTERCARD 51-55 16 mod 10
    //VISA 4 13,16 mod 10
    //AMEX 34,37 15 mod 10
    //Diners Club/Carte Blanche 300-305,36,38 14 mod 10
    //Discover 6011 16 mod 10
    //enRoute 2014,2149 15 any
    //JCB 3 16 mod 10
    //JCB 2131,1800 15 mod 10
    $ccno = preg_replace("/\D/", '', $ccno);
    $length = strlen($ccno);
    $firsttwo = substr($ccno, 0, 2);
    $firstthree = substr($ccno, 0, 3);
    $first = substr($ccno, 0, 1);
    $firstfour = substr($ccno, 0, 4);

    if ($firsttwo >= 51 && $firsttwo <= 55) { // Mastercard
        return $length == 16 && validMod10($ccno);
    } elseif ($first == 4) { // visa
        return ($length == 13 || $length == 16) && validMod10($ccno);
    } elseif ($firsttwo == 34 || $firsttwo == 37) { // Amex
        return $length == 15 && validMod10($ccno);
    } elseif (($firstthree >= 300 && $firstthree <= 305) // Diners1
        || ($firsttwo == 36 || $firsttwo == 38)
    ) { // Diners2
        return $length == 14 && validMod10($ccno);
    } elseif ($firstfour == 6011) { // discover
        return $length == 16 && validMod10($ccno);
    } elseif ($firstfour == 2014 || $firstfour == 2149) { // enRoute
        return $length == 15;
    } else {
        // if it is not any of the above, we do not know how to validate it

        // reject 4 and 15 1s anyway apart when request is from tincan offices
        if ($ccno == '4111111111111111') {
            return 0;
        }
    }

    return 1;
}

function loadCCvalidationFile($ccrangefile)
{
    if (!is_file($ccrangefile)) {
        return array();
    }
    $range = array();
    $fp = fopen($ccrangefile, 'rb');
    $contents = fread($fp, filesize($ccrangefile));
    fclose($fp);
    $lines = explode("\n", $contents);
    foreach ($lines as $line) {
        if (!preg_match("/^\s*#/", $line) && !preg_match("/^\s+$/", $line)) {
            if (preg_match("#(\d+),(\d+),(\d+)#", $line, $regs)) {
                //        print "RANGE".$line."<br/>";
                array_push($range, array(
                    'start'   => substr($regs[1], 0, 6),
                    'end'     => substr($regs[2], 0, 6),
                    'company' => sprintf('%02d', $regs[3]),
                ));
                //   dbg($regs[1]. " ". $regs[2]. " -> ".$regs[3]);
            } elseif (preg_match("#\((\d+)\)\s*=\s*'(.*)'#", $line, $regs)) {
                //        print "COMPANY".$line."<br/>";
                $company[sprintf('%02d', $regs[1])] = $regs[2];
                //   dbg($regs[1]. " = " . $regs[2]);
            }
        }
    }

    return array($range, $company);
}

function ccCompany($ccno)
{
    global $config;
    $ccrangefile = $config['code_root'].'/'.$config['uploader_dir'].'/codelib/ccvalidation.txt';
    list($ranges, $companies) = loadCCvalidationFile($ccrangefile);
    $first6 = substr($ccno, 0, 6);
    if (is_array($ranges)) {
        foreach ($ranges as $range) {
            //  dbg($range["start"]);
//    print "CHECKING ".$range["start"].' TO '.$range["end"].'<br/>';
            if ($range['start'] <= $first6 && $range['end'] >= $first6) {
                return array($range['company'], $companies[$range['company']]);
            }
        }
    }

    return -1;
}

function checkCCrange($ccno)
{
    global $config;
    $ccrangefile = $config['code_root'].'/'.$config['uploader_dir'].'/codelib/ccvalidation.txt';
    if (!is_file($ccrangefile) || !is_array($config['cc_accept_company'])) {
        return 1;
    }
    list($companyid, $companyname) = ccCompany($ccno);
    if ($companyid > 0 && in_array($companyid, $config['cc_accept_company'])) {
        //  dbg($ccno . " is valid for company $companyid $companyname");
        return 1;
    } elseif ($companyid < 0) {
        return -1;
    } else {
        return 0;
    }
}

function validateCCExpiry($ccexpiry)
{
    // expiry date validation here
    $mon = substr($ccexpiry, 0, 2);
    if (strlen($ccexpiry) == 5) {
        // I presume it is with a separator
        $year = substr($ccexpiry, 3, 2);
    } elseif (strlen($ccexpiry) == 4) {
        $year = substr($ccexpiry, 2, 2);
    } else {
        return 0;
    }
    $yeardiff = $year - date('y');

    return $mon < 13 && $yeardiff < 9 && (($year > date('y')) || ($year == date('y') && $mon >= date('m')));
}

function obscureCreditCard($cardno)
{
    if (strlen($cardno) < 5) {
        return $cardno;
    }

    $res = substr($cardno, strlen($cardno) - 4, 4);
    for ($i = 0; $i < strlen($cardno) - 4; ++$i) {
        $prefix .= '*';
    }
    $res = $prefix.$res;

    return $res;
}

function loadUser($loginname = '')
{
    if (!Sql_Table_exists('user')) {
        return;
    }
    initialiseUserSession();
    if (!$loginname) {
        if ($_SESSION['userloggedin'] != '' && $_SESSION['username'] != '') {
            $loginname = $_SESSION['username'];
        } else {
            return '';
        }
    }
    $att_req = Sql_Query(sprintf('select attribute.id,
    %s.name,%s.type,
    %s.value,%s.tablename from %s,%s,%s
    where %s.userid = %s.id and %s.email = "%s" and %s.id = %s.attributeid',
        'attribute',
        'attribute',
        'user_attribute',
        'attribute',
        'user',
        'user_attribute',
        'attribute',
        'user_attribute',
        'user',
        'user',
        addslashes($loginname),
        'attribute',
        'user_attribute'
    ));
    while ($att = Sql_fetch_array($att_req)) {
        //   if (!defined($_SESSION["userdata"]["attribute".$att["id"]])) {
        $_SESSION['userdata']['attribute'.$att['id']] = array(
            'name'         => $att['name'],
            'value'        => $att['value'],
            'type'         => $att['type'],
            'attid'        => $att['id'],
            'displayvalue' => $att['value'],
        );
        switch ($att['type']) {
            case 'textline':
            case 'hidden':
                $_SESSION['userdata']['attribute'.$att['id']]['displayvalue'] =
                    $att['value'];
                break;
            case 'creditcardno':
                $_SESSION['userdata']['attribute'.$att['id']]['displayvalue'] =
                    obscureCreditCard($att['value']);
                break;
            case 'select':
                $_SESSION['userdata']['attribute'.$att['id']]['displayvalue'] =
                    AttributeValue($att['tablename'], $att['value']);
                break;
            case 'date':
                $_SESSION['userdata']['attribute'.$att['id']]['displayvalue'] =
                    formatDate($att['value']);
                break;
        }
//    }
    }
    $d_req = Sql_Fetch_Array_Query("select * from user where email = \"$loginname\"");
    $_SESSION['userid'] = $d_req['id'];
    foreach (array('email', 'disabled', 'confirmed', 'htmlemail', 'uniqid', 'password', 'foreignkey') as $field) {
        //   if (!defined($_SESSION["userdata"][$field])) {
        $_SESSION['userdata'][$field] = array(
            'name'         => $field,
            'value'        => $d_req[$field],
            'type'         => 'static',
            'displayvalue' => $d_req[$field],
        );
//     }
    }
    $_SESSION['usergroups'] = userGroups($loginname);
    if (is_array($GLOBALS['config']['usergreeting'])) {
        $_SESSION['usergreeting'] = '';
        foreach ($GLOBALS['config']['usergreeting'] as $att) {
            $_SESSION['usergreeting'] .= $_SESSION['userdata'][$att]['displayvalue'].' ';
        }
        $_SESSION['usergreeting'] = rtrim($_SESSION['usergreeting']);
    }
    dbg('done loading user');

    return 1;
}

function addKeywordLibrary($name)
{
    $req = Sql_Query(sprintf('select id from keywordlib where name = "%s"', $name));
    if (Sql_affected_Rows()) {
        $row = Sql_Fetch_Row($req);

        return $row[0];
    }
    Sql_Query(sprintf('insert into keywordlib (name) values("%s")', $name));

    return Sql_Insert_id();
}

function getNewAttributeTablename($name)
{
    global $table_prefix, $tables;
    if ($tables['attribute']) {
        $table = $tables['attribute'];
    } else {
        $table = 'attribute';
    }
    $lc_name = substr(preg_replace("/\W/", '', strtolower($name)), 0, 25);
//  if ($lc_name == "") Fatal_Error("Name cannot be empty: $lc_name");
    if (!$lc_name) {
        $lc_name = 'attribute';
    }
    Sql_Query("select * from $table where tablename = \"$lc_name\"");
//  if (Sql_Affected_Rows()) Fatal_Error("Name is not unique enough");
    $c = 1;
    $basename = $lc_name;
    while (Sql_Affected_Rows() && $c < 100) {
        $lc_name = $basename.$c;
        Sql_Query("select * from $table where tablename = \"$lc_name\"");
        ++$c;
    }

    return $lc_name;
}

function isGuestAccount()
{
    if (!is_array($_SESSION['userdata'])) {
        return 1;
    }
    if ($GLOBALS['config']['guestaccount_attribute']) {
        return $_SESSION['userdata'][$GLOBALS['config']['guestaccount_attribute']]['value'];
    }
    if ($GLOBALS['config']['guestaccount_email_match']) {
        return preg_match($GLOBALS['config']['guestaccount_email_match'], $_SESSION['userdata']['email']['value']);
    }
}

function saveUserAttribute($userid, $attid, $data)
{
    global $usertable_prefix, $table_prefix, $tables;
    // workaround for integration webbler/phplist
    if (!isset($usertable_prefix)) {
        $usertable_prefix = '';
    }
    if (!isset($table_prefix)) {
        $table_prefix = 'phplist_';
    }
    if (!empty($tables['attribute'])) {
        $att_table = $usertable_prefix.$tables['attribute'];
        $user_att_table = $usertable_prefix.$tables['user_attribute'];
    } else {
        $att_table = $usertable_prefix.'attribute';
        $user_att_table = $usertable_prefix.'user_attribute';
    }

    if (!is_array($data)) {
        $tmp = $data;
        $data = Sql_Fetch_Assoc_Query(sprintf('select * from %s where id = %d', $att_table, $attid));
        $data['value'] = $tmp;
        $data['displayvalue'] = $tmp;
    }
    // dbg($data,'$data to store for '.$userid.' '.$attid);

    if ($data['nodbsave']) {
        //   dbg($attid, "Not saving, nodbsave");
        return;
    }
    if ($attid == 'emailcheck' || $attid == 'passwordcheck') {
        //   dbg($attid, "Not saving, emailcheck/passwordcheck");
        return;
    }

    if (!$data['type']) {
        $data['type'] = 'textline';
    }

    if ($data['type'] == 'static' || $data['type'] == 'password' || $data['type'] == 'htmlpref') {
        if (!empty($GLOBALS['config']['dontsave_userpassword']) && $data['type'] == 'password') {
            $data['value'] = 'not authoritative';
        }
        Sql_Query(sprintf('update user set %s = "%s" where id = %d',
            $attid, $data['value'], $userid));
        dbg('Saving', $data['value'], DBG_TRACE);
        if ($data['type'] == 'password') {
            Sql_Query(sprintf('update user set passwordchanged = now(),password="%s" where id = %d',
                hash('sha256', $data['value']), $userid));
        }

        return 1;
    }

    $attributetype = $data['type'];
    $attid_req = Sql_Fetch_Row_Query(sprintf('
    select id,type,tablename from %s where id = %d', $att_table, $attid));
    if (!$attid_req[0]) {
        $attid_req = Sql_Fetch_Row_Query(sprintf('
      select id,type,tablename from %s where name = "%s"', $att_table, $data['name']));
        if (!$attid_req[0]) {
            if (!empty($data['name']) && $GLOBALS['config']['autocreate_attributes']) {
                //      Dbg("Creating new Attribute: ".$data["name"]);
                sendError('creating new attribute '.$data['name']);
                $atttable = getNewAttributeTablename($data['name']);
                Sql_Query(sprintf('insert into %s (name,type,tablename) values("%s","%s","%s")', $att_table,
                    $data['name'], $data['type'], $atttable));
                $attid = Sql_Insert_Id();
            } else {
                //     dbg("Not creating new Attribute: ".$data["name"]);
                // sendError("Not creating new attribute ".$data["name"]);
            }
        } else {
            $attid = $attid_req[0];
            if (empty($attributetype)) {
                $attributetype = $attid_req[1];
            }
            $atttable = $attid_req[2];
        }
    } else {
        $attid = $attid_req[0];
        if (empty($attributetype)) {
            $attributetype = $attid_req[1];
        }
        $atttable = $attid_req[2];
    }

    if (!$atttable && !empty($data['name'])) {
        $atttable = getNewAttributeTablename($data['name']);
        // fix attribute without tablename
        Sql_Query(sprintf('update %s set tablename ="%s" where id = %d',
            $att_table, $atttable, $attid));
//   sendError("Attribute without Tablename $attid");
    }

    switch ($attributetype) {
        case 'static':
        case 'password':
            //  dbg('SAVING STATIC OR  PASSWORD');
            if (!empty($GLOBALS['config']['dontsave_userpassword']) && $data['type'] == 'password') {
                $data['value'] = 'not authoritative';
            }
            Sql_Query(sprintf('update user set %s = "%s" where id = %d',
                $attid, $data['value'], $userid));
            break;
        case 'select':
            $curval = Sql_Fetch_Row_Query(sprintf('select id from '.$table_prefix.'listattr_%s
        where name = "%s"', $atttable, $data['displayvalue']), 1);
            if (!$curval[0] && $data['displayvalue'] && $data['displayvalue'] != '') {
                Sql_Query(sprintf('insert into '.$table_prefix.'listattr_%s (name) values("%s")', $atttable,
                    $data['displayvalue']));
                sendError('Added '.$data['displayvalue']." to $atttable");
                $valid = Sql_Insert_id();
            } else {
                $valid = $curval[0];
            }
            Sql_Query(sprintf('replace into %s (userid,attributeid,value)
        values(%d,%d,"%s")', $user_att_table, $userid, $attid, $valid));

            break;
        case 'avatar':
            if (is_array($_FILES)) { //# only avatars are files, for now
                if (!defined('MAX_AVATAR_SIZE')) {
                    define('MAX_AVATAR_SIZE', 100000);
                }

                $formfield = 'attribute'.$attid.'_file'; //# the name of the fileupload element
                if (!empty($_FILES[$formfield]['name']) && !empty($_FILES[$formfield]['tmp_name'])) {
                    $tmpnam = $_FILES[$formfield]['tmp_name'];
                    move_uploaded_file($tmpnam, '/tmp/avatar'.$userid.'.jpg');
                    $size = filesize('/tmp/avatar'.$userid.'.jpg');
//          dbg('New size: '.$size);
                    if ($size < MAX_AVATAR_SIZE) {
                        $avatar = file_get_contents('/tmp/avatar'.$userid.'.jpg');
                        Sql_Query(sprintf('replace into %s (userid,attributeid,value)
              values(%d,%d,"%s")', $user_att_table, $userid, $attid, base64_encode($avatar)));
                        unlink('/tmp/avatar'.$userid.'.jpg');
                    }
                }
            }
            break;
        default:
            Sql_Query(sprintf('replace into %s (userid,attributeid,value)
        values(%d,%d,"%s")', $user_att_table, $userid, $attid, $data['value']));
            break;
    }

    return 1;
}

function saveUserByID($userid, $data)
{
    dbg("Saving user by id $userid");
    foreach ($data as $key => $val) {
        if (preg_match("/^attribute(\d+)/", $key, $regs)) {
            $attid = $regs[1];
        } else {
            $attid = $key;
        }
        dbg("Saving attribute $key, $attid, $val for $userid");
        if ($userid && $attid && $data[$key]['type'] != 'userfield' && !$data[$key]['nodbsave']) {
            saveUserAttribute($userid, $attid, $val);
        }
    }
}

function saveUser($loginname, $data)
{
    dbg("Saving user $loginname");
    // saves user to database
    $id_req = Sql_Fetch_Row_Query("select id from user where email = \"$loginname\"");
    if ($id_req[0]) {
        $userid = $id_req[0];
        foreach ($data as $key => $val) {
            if (preg_match("/^attribute(\d+)/", $key, $regs)) {
                $attid = $regs[1];
            }
            //     dbg("Saving attribute $key, $attid, $val for $loginname, $userid");
            if ($userid && $attid) {
                saveUserAttribute($userid, $key, $val);
            }
        }
    }

    return 1;
}

function saveUserData($username, $fields)
{
    // saves data in session, not in database
    if (!is_array($_SESSION['userdata'])) {
        initialiseUserSession();
    }
    if (!empty($GLOBALS['usersaved'])) {
        return;
    }
    if (!$username) {
        $username = 'Unknown User';
    }
    dbg("Saving user in session $username", '', DBG_TRACE);

    $res = '';
    $required_fields = explode(',', $_POST['required']);
    if ($_POST['unrequire']) {
        $unrequired_fields = explode(',', $_POST['unrequire']);
        $required_fields = array_diff($required_fields, $unrequired_fields);
    } else {
        $unrequired_fields = array();
    }
    $required_formats = explode(',', $_POST['required_formats']);
    $description_fields = explode(',', $_POST['required_description']);

    reset($fields);
//  dbg("Checking fields");
    foreach ($fields as $fname => $fielddetails) {
        dbg('Saving user Saving '.$fname.' to session '.$_POST[$fname]);
        //   dbg($fielddetails);
        $key = $fname;
        $val = $_POST[$fname];
        if (strpos($key, 'required') === false && $key != 'unrequire' &&
            $fields[$key]['type'] != 'separator' &&
            $fields[$key]['type'] != 'emailcheck' &&
            $fields[$key]['type'] != 'passwordcheck'
        ) {
            //   dbg($fname ." of type ".$fields[$key]["type"]);
            if (!is_array($_SESSION['userdata'][$key])) {
                $_SESSION['userdata'][$key] = array();
            }
            $_SESSION['userdata'][$key]['name'] = $fields[$key]['name'];
            $_SESSION['userdata'][$key]['type'] = $fields[$key]['type'];
            if ($fields[$key]['type'] == 'date') {
                $_SESSION['userdata'][$key]['value'] = sprintf('%04d-%02d-%02d',
                    $_POST['year'][$key], $_POST['month'][$key], $_POST['day'][$key]);
                $_SESSION['userdata'][$key]['displayvalue'] = $_SESSION['userdata'][$key]['value'];
            } elseif ($fields[$key]['type'] == 'creditcardno') {
                // dont overwrite known CC with ***
                if (!preg_match("#^\*+#", $val)) {
                    $_SESSION['userdata'][$key]['value'] = ltrim($val);
                }
            } else {
                $_SESSION['userdata'][$key]['value'] = ltrim($val);
            }
            if ($fields[$key]['type'] == 'select') {
                if (!empty($val) && is_array($fields[$key]['values'])) {
                    $_SESSION['userdata'][$key]['displayvalue'] = $fields[$key]['values'][$val];
                }
            } elseif ($fields[$key]['type'] == 'checkboxgroup') {
                if (is_array($val)) { // if val is empty join crashes
                    $_SESSION['userdata'][$key]['value'] = implode(',', $val);
                }
            } elseif ($fields[$key]['type'] == 'creditcardno') {
                // erase any non digits from the CC numbers
                $_SESSION['userdata'][$key]['value'] = preg_replace("/\D/", '', $_SESSION['userdata'][$key]['value']);
                $_SESSION['userdata'][$key]['displayvalue'] = obscureCreditCard($_SESSION['userdata'][$key]['value']);
            } elseif ($fields[$key]['name'] == 'Card Number') {
                $_SESSION['userdata'][$key]['value'] = preg_replace("/\D/", '', $_SESSION['userdata'][$key]['value']);
                $_SESSION['userdata'][$key]['displayvalue'] = obscureCreditCard($_SESSION['userdata'][$key]['value']);
                /*          $_SESSION["userdata"][$key]["displayvalue"] = substr($_SESSION["userdata"][$key]["displayvalue"],0,4);
                          for ($i=0;$i<strlen($_SESSION["userdata"][$key]["value"]-4);$i++) {
                            $_SESSION["userdata"][$key]["displayvalue"] .= '*';
                          }
                */
            } else {
                $_SESSION['userdata'][$key]['displayvalue'] = $val;
            }

            foreach ($fielddetails as $field_attr => $field_attr_value) {
                if (!isset($_SESSION['userdata'][$key][$field_attr]) && !preg_match("/^\d+$/", $key)
                    && !preg_match("/^\d+$/", $field_attr)
                ) {
                    $_SESSION['userdata'][$key][$field_attr] = $field_attr_value;
                }
            }
            // save it to the DB as well
        } else {
            //       dbg("Not checking ".$fname ." of type ".$fields[$key]["type"]);
        }
    }

    // fix UK postcodes to correct format
    if ($_SESSION['userdata'][$GLOBALS['config']['country_attribute']]['displayvalue'] == 'United Kingdom' && isset($_SESSION['userdata'][$GLOBALS['config']['postcode_attribute']]['value'])) {
        $postcode = $_SESSION['userdata'][$GLOBALS['config']['postcode_attribute']]['value'];
        $postcode = strtoupper(str_replace(' ', '', $postcode));
        if (preg_match("/(.*)(\d\w\w)$/", $postcode, $regs)) {
            $_SESSION['userdata'][$GLOBALS['config']['postcode_attribute']]['value'] = trim($regs[1]).' '.$regs[2];
            $_SESSION['userdata'][$GLOBALS['config']['postcode_attribute']]['displayvalue'] = trim($regs[1]).' '.$regs[2];
        }
    }

    dbg('Checking required fields');
    reset($required_fields);
    foreach ($required_fields as $index => $field) {
        $type = $fields[$field]['type'];
        // dbg("$field of type $type");
        if ($type != 'userfield' && $type != '') { //## @@@ need to check why type is not set
            if ($field && !$_SESSION['userdata'][$field]['value']) {
                $res = 'Information missing: '.$description_fields[$index];
                break;
            } elseif ($required_formats[$index] && !preg_match(stripslashes($required_formats[$index]),
                    $_SESSION['userdata'][$field]['value'])
            ) {
                $res = 'Sorry, you entered an invalid '.$description_fields[$index].': '.$_SESSION['userdata'][$field]['value'];
                break;
            } elseif ($field == 'email' && !validateEmail($_SESSION['userdata'][$field]['value'])) {
                $res = 'Sorry, the following field cannot be validated: '.$description_fields[$index].': '.$_SESSION['userdata'][$field]['value'];
                break;
            } elseif ($field == 'emailcheck' && $_SESSION['userdata']['email']['value'] != $_SESSION['userdata']['emailcheck']['value']) {
                $res = 'Emails entered are not the same';
                break;
            } elseif ($field == 'cardtype' && $_SESSION['userdata'][$field]['value'] == 'WSWITCH' && !preg_match("/\d/",
                    $_SESSION['userdata']['attribute82']['value'])
            ) {
                $res = 'Sorry, a Switch Card requires a valid issue number. If you have a new Switch card without an issue number, please use 0 as the issue number.';
                break;
            } elseif ($field == 'cardtype' && isset($_SESSION['userdata'][$field]['value']) && $_SESSION['userdata'][$field]['value'] != 'WSWITCH' && $_SESSION['userdata']['attribute82']['value']) {
                $res = 'Sorry, an issue number is not valid when not using a Switch Card';
                break;
            } elseif (($type == 'creditcardno' || $field == 'cardnumber') && isset($_SESSION['userdata'][$field]['value']) && !checkCCrange($_SESSION['userdata'][$field]['value'])) {
                list($cid, $cname) = ccCompany($_SESSION['userdata'][$field]['value']);
                if (!$cname) {
                    $cname = '(Unknown Credit card)';
                }
                $res = "Sorry, we currently don't accept $cname cards";
                break;
            } elseif (($type == 'creditcardno' || $field == 'cardnumber') && isset($_SESSION['userdata'][$field]['value']) && !validateCC($_SESSION['userdata'][$field]['value'])) {
                $res = 'Sorry, you entered an invalid '.$description_fields[$index]; //.": ".$_SESSION["userdata"][$field]["value"];
                break;
            } elseif (($type == 'creditcardexpiry' || $field == 'cardexpiry') && isset($_SESSION['userdata'][$field]['value']) && !validateCCExpiry($_SESSION['userdata'][$field]['value'])) {
                $res = 'Sorry, you entered an invalid '.$description_fields[$index].': '.$_SESSION['userdata'][$field]['value'];
                break;
            }
        }
    }
    if (0 && isset($_SESSION['userdata'][$GLOBALS['config']['country_attribute']]['displayvalue']) && $_SESSION['userdata'][$GLOBALS['config']['country_attribute']]['displayvalue'] == 'United Kingdom' && isset($_SESSION['userdata'][$GLOBALS['config']['postcode_attribute']]['value'])) {
        $postcode = $_SESSION['userdata'][$GLOBALS['config']['postcode_attribute']]['displayvalue'];
        if (!preg_match("/(.*)(\d\w\w)$/", $postcode, $regs)) {
            $res = 'That does not seem to be a valid UK postcode';
        } elseif (!preg_match("/^[\s\w\d]+$/", $postcode, $regs)) {
            $res = 'That does not seem to be a valid UK postcode';
        }
    }
    /*  if (is_array($GLOBALS["config"]["bocs_dpa"])) {
        if (!is_array($_SESSION["DPA"]))
          $_SESSION["DPA"] = array();
        foreach ($GLOBALS["config"]["bocs_dpa"] as $dpaatt => $val) {
          if ($_SESSION["userdata"][$dpaatt]["displayvalue"]) {
            $_SESSION["DPA"][$val] = "Y";
          } else {
            $_SESSION["DPA"][$val] = "N";
          }
        }
      }*/
    // if no error in form check for subscriptions
    if (!$res && is_object($GLOBALS['config']['plugins']['phplist'])) {
        $phplist = $GLOBALS['config']['plugins']['phplist'];
        foreach ($_SESSION['userdata'] as $key => $field) {
            if (($field['formtype'] == 'List Subscription' || $field['type'] == 'List Subscription') && $field['listid']) {
                $listid = $field['listid'];
                if ($field['value'] && isset($_SESSION['userdata']['email'])) {
                    if ($phplist->addEmailToList($_SESSION['userdata']['email']['value'], $listid)) {
                        $phplist->confirmEmail($_SESSION['userdata']['email']['value']);
                        //  sendError("User added to list: $listid");
                    } else {
                        // sendError("Error adding user to list: $listid");
                    }
                } //else {
                //$phplist->removeEmailFromList($_SESSION["userdata"]["email"]["value"],$listid);
                //}
            }
        }
    }
    $GLOBALS['usersaved'] = time();

    return $res;
}
