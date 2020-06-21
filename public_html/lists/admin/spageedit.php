<?php

require_once dirname(__FILE__).'/accesscheck.php';

// configure subscribe page

$subselect = '';
$access = accessLevel('spage');
switch ($access) {
    case 'owner':
        $subselect = ' where active and owner = '.$_SESSION['logindetails']['id'];
        break;
    case 'all':
        $subselect = ' where active';
        break;
    case 'none':
    default:
        $subselect = ' where id = 0';
        break;
}
if (isset($_GET['id'])) {
    $id = sprintf('%d', $_GET['id']);
} else {
    $id = 0;
}

$sendtestresult = '';
$testtarget = '';

if (isset($_POST['save'])) {
    if (!verifyToken()) {
        echo Error(s('Invalid security token, please reload the page and try again'));

        return;
    }
    $owner = (int) $_POST['owner'];
    $title = $_POST['title']; //# danger, make sure to escape

    if (!$owner) {
        $owner = $_SESSION['logindetails']['id'];
    }
    if ($id) {
        Sql_Query(sprintf('update %s set title = "%s",owner = %d where id = %d',
            $tables['subscribepage'], sql_escape(strip_tags($title)), $owner, $id));
    } else {
        Sql_Query(sprintf('insert into %s (title,owner) values("%s",%d)',
            $tables['subscribepage'], sql_escape($title), $owner));
        $id = Sql_Insert_id();
    }
    Sql_Query(sprintf('delete from %s where id = %d', $tables['subscribepage_data'], $id));

    foreach (array(
                 'title',
                 'language_file',
                 'intro',
                 'header',
                 'footer',
                 'thankyoupage',
                 'button',
                 'htmlchoice',
                 'emaildoubleentry',
                 'showcategories',
                 'ajax_subscribeconfirmation',
             ) as $item) {
        Sql_Query(sprintf('insert into %s (name,id,data) values("%s",%d,"%s")',
            $tables['subscribepage_data'], $item, $id, sql_escape($_POST[$item])));
    }

    foreach (array(
                 'subscribesubject',
                 'subscribemessage',
                 'confirmationsubject',
                 'confirmationmessage',
                 'unsubscribesubject',
                 'unsubscribemessage',
             ) as $item) {
        SaveConfig("$item:$id", stripslashes($_POST[$item]), 0);
    }
    //# rewrite attributes
    Sql_Query(sprintf('delete from %s where id = %d and name like "attribute___"',
        $tables['subscribepage_data'], $id));

    $attributes = '';
    if (isset($_POST['attr_use']) && is_array($_POST['attr_use'])) {
        $cnt = 0;
        foreach ($_POST['attr_use'] as $att => $val) {
            //BUGFIX 15285 - note 50677 (part 1: Attribute order) - by tipichris - mantis.phplist.com/view.php?id=15285
            // $default = $attr_default[$att];
            // $order = $attr_listorder[$att];
            // $required = $attr_required[$att];
            $default = $_POST['attr_default'][$att];
            //# rather crude sanitisation
            //      $default = preg_replace('/[^\w -\.]+/','',$default);
            // use unicode matching to keep non-ascii letters
            if (!is_numeric($default)) { //# https://mantis.phplist.com/view.php?id=17532
                $default = preg_replace('/[^\p{L} -\.]+/u', '', $default);
            }
            $order = sprintf('%d', $_POST['attr_listorder'][$att]);
            $required = !empty($_POST['attr_required'][$att]);
//END BUGFIX 15285 - note 50677 (part 1)

            Sql_Query(sprintf('insert into %s (id,name,data) values(%d,"attribute%03d","%s")',
                $tables['subscribepage_data'], $id, $att,
                $att.'###'.$default.'###'.$order.'###'.$required));
            ++$cnt;
            $attributes .= $att.'+';
        }
    }
    Sql_Query(sprintf('replace into %s (id,name,data) values(%d,"attributes","%s")',
        $tables['subscribepage_data'], $id, $attributes));
    if (isset($_POST['list']) && is_array($_POST['list'])) {
        Sql_Query(sprintf('replace into %s (id,name,data) values(%d,"lists","%s")',
            $tables['subscribepage_data'], $id, implode(',', $_POST['list'])));
    }

    //## Store plugin data
    foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
        $plugin->processSubscribePageEdit($id);
    }

    $_SESSION['action_result'] = s('Subscribe page information saved');
    Sql_Query(sprintf('update %s set active = 1 where id = %d',
        $tables['subscribepage'], $id));
    Redirect('spage');
}
@ob_end_flush();
echo formStart(' class="spageEdit" ');

//# initialise values from defaults
$data = array();
$data['title'] = s('Subscribe to our newsletter');
$data['button'] = $strSubmit;
$data['intro'] = $strSubscribeInfo;
$data['language_file'] = ''; //$GLOBALS['language_module'];
$data['header'] = getConfig('pageheader');
$data['footer'] = getConfig('pagefooter');
$data['thankyoupage'] = '<h3>'.$GLOBALS['strThanks'].'</h3>'."\n".$GLOBALS['strEmailConfirmation'];
$data['ajax_subscribeconfirmation'] = getConfig('ajax_subscribeconfirmation');
$data['subscribemessage'] = getConfig('subscribemessage');
$data['subscribesubject'] = getConfig('subscribesubject');
$data['confirmationmessage'] = getConfig('confirmationmessage');
$data['confirmationsubject'] = getConfig('confirmationsubject');
$data['unsubscribemessage'] = getConfig('unsubscribemessage');
$data['unsubscribesubject'] = getConfig('unsubscribesubject');
$data['htmlchoice'] = 'htmlonly';
$data['emaildoubleentry'] = 'yes';
$data['rssdefault'] = 'daily'; //Leftover from the preplugin era
$data['rssintro'] = s('Please indicate how often you want to receive messages');  //Leftover from the preplugin era
$selected_lists = array();
$attributedata = array();
if(getConfig('displaycategories')==0) {
    $data['showcategories'] = 'no';
}else {
    $data['showcategories'] = 'yes';
}

if ($id) {
    //# Fill values from database
    $req = Sql_Query(sprintf('select * from %s where id = %d', $tables['subscribepage_data'], $id));
    while ($row = Sql_Fetch_Array($req)) {
        $data[$row['name']] = $row['data'];
    }
    $ownerreq = Sql_Fetch_Row_Query(sprintf('select owner from %s where id = %d', $GLOBALS['tables']['subscribepage'],
        $id));
    $data['owner'] = $ownerreq[0];
    $attributes = explode('+', $data['attributes']);
    foreach ($attributes as $attribute) {
        if (!empty($data[sprintf('attribute%03d', $attribute)])) {
            list($attributedata[$attribute]['id'],
                $attributedata[$attribute]['default_value'],
                $attributedata[$attribute]['listorder'],
                $attributedata[$attribute]['required']) = explode('###', $data[sprintf('attribute%03d', $attribute)]);
        }
    }
    if (isset($data['lists'])) {
        $selected_lists = explode(',', $data['lists']);
    } else {
        $selected_lists = array();
    }
    printf('<input type="hidden" name="id" value="%d" />', $id);
    $data['subscribemessage'] = getConfig("subscribemessage:$id");
    $data['subscribesubject'] = getConfig("subscribesubject:$id");
    $data['confirmationmessage'] = getConfig("confirmationmessage:$id");
    $data['confirmationsubject'] = getConfig("confirmationsubject:$id");
    $data['unsubscribemessage'] = getConfig("unsubscribemessage:$id");
    $data['unsubscribesubject'] = getConfig("unsubscribesubject:$id");
}

echo '<div class="accordion">';
$generalinfoHTML = '<h3><a name="general">'.s('General Information').'</a></h3>';
$generalinfoHTML .= '<div>';

$generalinfoHTML .= sprintf('<label for="title">%s</label><input type="text" name="title" value="%s" size="60" />',
    s('Title'),
    htmlspecialchars(stripslashes($data['title'])));

$language_file = $GLOBALS['language_module'];
if (is_dir(dirname(__FILE__).'/../texts')) {
    $language_files = array();
    $landir = dir(dirname(__FILE__).'/../texts');
    while (false !== ($direntry = $landir->read())) {
        if (is_file($landir->path.'/'.$direntry) && preg_match('/\.inc$/i', $direntry)) {
            $language_files[$direntry] = basename($direntry, '.inc');
        }
    }
    $landir->close();
}
asort($language_files);
$language_select = '<select name="language_file">';
$language_select .= '<option value="">--'.s('default').'</option>';
foreach ($language_files as $key => $val) {
    $language_select .= sprintf('<option value="%s" %s>%s</option>', $key,
        $key == $data['language_file'] ? 'selected="selected"' : '', $val);
}
$language_select .= '</select>';

$generalinfoHTML .= sprintf('<label for="language_file">%s</label>%s',
    s('Language file to use'), $language_select);

$generalinfoHTML .= sprintf('<label for="intro">%s</label><textarea name="intro" cols="60" rows="10" class="virtual">%s</textarea>',
    s('Intro'),
    htmlspecialchars(stripslashes($data['intro'])));
$generalinfoHTML .= sprintf('<label for="header">%s</label><textarea name="header" cols="60" rows="10" class="virtual">%s</textarea>',
    s('Header'),
    htmlspecialchars(stripslashes($data['header'])));
$generalinfoHTML .= sprintf('<label for="footer">%s</label><textarea name="footer" cols="60" rows="10" class="virtual">%s</textarea>',
    s('Footer'),
    htmlspecialchars(stripslashes($data['footer'])));
$generalinfoHTML .= sprintf('<label for="thankyoupage">%s</label><textarea name="thankyoupage" cols="60" rows="10" class="virtual">%s</textarea>',
    s('Thank you page'),
    htmlspecialchars(stripslashes($data['thankyoupage'])));

$generalinfoHTML .= sprintf('<label for="ajax_subscribeconfirmation">%s</label><textarea name="ajax_subscribeconfirmation" cols="60" rows="10" class="virtual">%s</textarea>',
    s('Text to display when subscription with an AJAX request was successful'),
    htmlspecialchars(stripslashes($data['ajax_subscribeconfirmation'])));

$generalinfoHTML .= sprintf('<label for="button">%s</label><input type="text" name="button" value="%s" size="60" />',
    s('Text for Button'),
    htmlspecialchars($data['button']));
$generalinfoHTML .= sprintf('<label for="htmlchoice">%s</label>', s('HTML Email choice'));
$generalinfoHTML .= sprintf('<input type="radio" name="htmlchoice" value="textonly" %s />
  %s <br/>',
    $data['htmlchoice'] == 'textonly' ? 'checked="checked"' : '',
    s('Don\'t offer choice, default to <b>text</b>'));
$generalinfoHTML .= sprintf('<input type="radio" name="htmlchoice" value="htmlonly" %s />
  %s <br/>',
    $data['htmlchoice'] == 'htmlonly' ? 'checked="checked"' : '',
    s('Don\'t offer choice, default to <b>HTML</b>'));
$generalinfoHTML .= sprintf('<input type="radio" name="htmlchoice" value="checkfortext" %s />
  %s <br/>',
    $data['htmlchoice'] == 'checkfortext' ? 'checked="checked"' : '',
    s('Offer checkbox for text'));
$generalinfoHTML .= sprintf('<input type="radio" name="htmlchoice" value="checkforhtml" %s />
  %s <br/>',
    $data['htmlchoice'] == 'checkforhtml' ? 'checked="checked"' : '',
    s('Offer checkbox for HTML'));
$generalinfoHTML .= sprintf('<input type="radio" name="htmlchoice" value="radiotext" %s />
  %s <br/>',
    $data['htmlchoice'] == 'radiotext' ? 'checked="checked"' : '',
    s('Radio buttons, default to text'));
$generalinfoHTML .= sprintf('<input type="radio" name="htmlchoice" value="radiohtml" %s />
  %s <br/>',
    $data['htmlchoice'] == 'radiohtml' ? 'checked="checked"' : '',
    s('Radio buttons, default to HTML'));

$generalinfoHTML .= sprintf('<label for="emaildoubleentry">%s</label>',
    s('Display Email confirmation'));
$generalinfoHTML .= sprintf('<input type="radio" name="emaildoubleentry" value="yes" %s />%s<br/>',
    $data['emaildoubleentry'] == 'yes' ? 'checked="checked"' : '',
    s('Display email confirmation'));
$generalinfoHTML .= sprintf('<input type="radio" name="emaildoubleentry" value="no" %s />%s<br/>',
    $data['emaildoubleentry'] == 'no' ? 'checked="checked"' : '',
    s('Don\'t display email confirmation'));

$generalinfoHTML .= '</div>';

$transactionHTML = '<h3><a name="transaction">'.s('Transaction messages').'</a></h3>';

$transactionHTML .= '<div>';
$transactionHTML .= '<h4>'.s('Message they receive when they subscribe').'</h4>';
$transactionHTML .= sprintf('<label for="subscribesubject">%s</label><input type="text" name="subscribesubject" value="%s" size="60" />',
    s('Subject'),
    htmlspecialchars(stripslashes($data['subscribesubject'])));
$transactionHTML .= sprintf('<label for="subscribemessage">%s</label><textarea name="subscribemessage" cols="60" rows="10" class="virtual">%s</textarea>',
    s('Message'),
    htmlspecialchars(stripslashes($data['subscribemessage'])));
$transactionHTML .= '<h4>'.s('Message they receive when they confirm their subscription').'</h4>';
$transactionHTML .= sprintf('<label for="confirmationsubject">%s</label><input type="text" name="confirmationsubject" value="%s" size="60" />',
    s('Subject'),
    htmlspecialchars(stripslashes($data['confirmationsubject'])));
$transactionHTML .= sprintf('<label for="confirmationmessage">%s</label><textarea name="confirmationmessage" cols="60" rows="10" class="virtual">%s</textarea>',
    s('Message'),
    htmlspecialchars(stripslashes($data['confirmationmessage'])));
$transactionHTML .= '<h4>'.s('Message they receive when they unsubscribe').'</h4>';
$transactionHTML .= sprintf('<label for="unsubscribesubject">%s</label><input type="text" name="unsubscribesubject" value="%s" size="60" />',
    s('Subject'),
    htmlspecialchars(stripslashes($data['unsubscribesubject'])));
$transactionHTML .= sprintf('<label for="unsubscribemessage">%s</label><textarea name="unsubscribemessage" cols="60" rows="10" class="virtual">%s</textarea>',
    s('Message'),
    htmlspecialchars(stripslashes($data['unsubscribemessage'])));

$sendtest_content = sprintf('<div class="sendTest" id="sendTest">
    ' .$sendtestresult.'
    <input class="submit" type="submit" name="sendtest" value="%s"/>  %s: 
    <input type="text" name="testtarget" size="40" value="' .htmlspecialchars($testtarget).'"/><br />%s
    </div>',
    s('Send test message'), s('to email addresses'),
    s('(comma separate addresses - all must be existing subscribers)'));
$testpanel = new UIPanel(s('Send Test'), $sendtest_content);
$testpanel->setID('testpanel');
//$transactionHTML .= $testpanel->display();

$transactionHTML .= '</div>';

$attributesHTML = '<h3><a name="attributes">'.s('Select the attributes to use').'</a></h3>';
$attributesHTML .= '<div>';
$hasAttributes = false;

$req = Sql_Query(sprintf('select * from %s order by listorder', $tables['attribute']));
$checked = array();
while ($row = Sql_Fetch_Array($req)) {
    $hasAttributes = true;
    if (isset($attributedata[$row['id']]) && is_array($attributedata[$row['id']])) {
        $checked[$row['id']] = 'checked';
        $bgcol = '#F7E7C2';
        $value = $attributedata[$row['id']];
    } else {
        $checked[$row['id']] = '';
        $value = $row;
        $bgcol = '#ffffff';
    }

    $attributesHTML .= '<table class="spageeditListing" border="1" width="100%" bgcolor="'.$bgcol.'">';
    $attributesHTML .= '<tr><td colspan="2" width="150">'.s('Attribute').': '.$row['id'].'</td>';
    $attributesHTML .= '<td colspan="2">'.s('Check this box to use this attribute in the page').'<input type="checkbox" name="attr_use['.$row['id'].']" value="1" '.$checked[$row['id']].' /></td></tr>';
    $attributesHTML .= '<tr><td colspan="2">'.s('Name').': </td><td colspan="2"><h4>'.htmlspecialchars(stripslashes($row['name'])).'</h4></td></tr>';
    $attributesHTML .= '<tr><td colspan="2">'.s('Type').': </td><td colspan="2"><h4>'.s($row['type']).'</h4></td></tr>';
    $attributesHTML .= '<tr><td colspan="2">'.s('Default Value').': </td><td colspan="2"><input type="text" name="attr_default['.$row['id'].']" value="'.htmlspecialchars(stripslashes($value['default_value'])).'" size="40" /></td></tr>';
    $attributesHTML .= '<tr><td>'.s('Order of Listing').': </td><td><input type="text" name="attr_listorder['.$row['id'].']" value="'.$value['listorder'].'" size="5" /></td>';
    $attributesHTML .= '<td>'.s('Is this attribute required?').': </td><td><input type="checkbox" name="attr_required['.$row['id'].']" value="1" ';
    $attributesHTML .= $value['required'] ? 'checked="checked"' : '';
    $attributesHTML .= ' /></td></tr>';
    $attributesHTML .= '</table><hr/>';
}

$attributesHTML .= '</div>';

//## allow plugins to add tabs
$pluginsHTML = '';

foreach ($GLOBALS['plugins'] as $pluginname => $plugin) {
    $pluginHTML = $plugin->displaySubscribepageEdit($data);
    if (!empty($pluginHTML)) {
        $pluginsHTML .= '<h3><a name="'.$pluginname.'">'.s('Information needed for %s', $plugin->name).'</a></h3>';
        $pluginsHTML .= '<div>'.$pluginHTML.'</div>';
    }
}

$listsHTML = '<h3><a name="lists">'.s('Select the lists to offer').'</a></h3>';
$listsHTML .= '<div>';
$listsHTML .= sprintf('<label for="listcategories">%s</label><br/>',
    s('Display list categories'));
$listsHTML .= sprintf('<input type="radio" name="showcategories" value="no" %s />%s<br/>',
    $data['showcategories'] === 'no' ? 'checked="checked"' : '',
    s('Do not show list categories'));
$listsHTML .= sprintf('<input type="radio" name="showcategories" value="yes" %s />%s<br/>',
    $data['showcategories'] === 'yes' ? 'checked="checked"' : '',
    s('Display lists in labelled categories'));
$listsHTML .= '<p><label>'.s('Lists').'</label><br/>'.s('You can only select "public" lists for subscribe pages.');
$req = Sql_query("SELECT * FROM {$tables['list']} $subselect order by listorder");
if (!Sql_Affected_Rows()) {
    $listsHTML .= '<br/>'.s('No lists available, please create one first');
} else {

    $listsHTML .= '<br/>'.s('If you do not choose a list here, all public lists will be displayed.');
    $hideSingle = getConfig('hide_single_list');
    if ($hideSingle) {
        $listsHTML .= '<br/>'.s('If you choose one list only, a checkbox for this list will not be displayed and the subscriber will automatically be added to this list.');
//  } else {
//    $listsHTML .= s('If you choose one list only, a checkbox for this list will be displayed');
    }
}
$listsHTML .= '</p>';
while ($row = Sql_Fetch_Array($req)) {
    $listsHTML .= sprintf('<label><input type="checkbox" name="list[%d]" value="%d" %s /> %s</label><div>%s</div>',
        $row['id'], $row['id'], in_array($row['id'], $selected_lists) ? 'checked="checked"' : '',
        stripslashes($row['name']), htmlspecialchars(stripslashes($row['description'])));
}

$listsHTML .= '</div>';

//the order of tabs
echo $generalinfoHTML;
echo $listsHTML;
if ($hasAttributes) {
    echo $attributesHTML;
}
echo $transactionHTML;
echo $pluginsHTML;

echo '</div>'; // accordion

$ownerHTML = $singleOwner = '';
$adminCount = 0;
if (isSuperUser() || accessLevel('spageedit') == 'all') {
    if (!isset($data['owner'])) {
        $data['owner'] = 0;
    }
    $ownerHTML .= '<br/>'.s('Owner').': <select name="owner">';
    $admins = $GLOBALS['admin_auth']->listAdmins();
    $adminCount = count($admins);
    foreach ($admins as $adminid => $adminname) {
        $singleOwner = '<input type="hidden" name="owner" value="'.$adminid.'" />';
        $ownerHTML .= sprintf('<option value="%d" %s>%s</option>', $adminid,
            $adminid == $data['owner'] ? 'selected="selected"' : '', htmlentities($adminname));
    }
    $ownerHTML .= '</select>';

    if ($adminCount > 1) {
        echo $ownerHTML;
    } else {
        echo $singleOwner;
    }
}

echo '
<input class="submit" type="submit" name="save" value="' .s('Save Changes').'" />

</form>';
