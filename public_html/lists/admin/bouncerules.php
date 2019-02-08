<?php

require_once dirname(__FILE__).'/accesscheck.php';

if (isset($_GET['type']) && $_GET['type'] == 'candidate') {
    $type = 'candidate';
    $url = '&type=candidate';
} else {
    $type = 'active';
    $url = '&type=active';
}

if (isset($_POST['tagaction']) && isset($_POST['tagged']) && is_array($_POST['tagged']) && count($_POST['tagged'])) {
    switch ($_POST['tagaction']) {
        case 'delete':
            foreach ($_POST['tagged'] as $key => $val) {
                Sql_Query(sprintf('delete from %s where id = %d', $GLOBALS['tables']['bounceregex'], $key));
            }
            break;
        case 'activate':
            foreach ($_POST['tagged'] as $key => $val) {
                Sql_Query(sprintf('update %s set status = "active" where id = %d', $GLOBALS['tables']['bounceregex'],
                    $key));
            }
            break;
        case 'deactivate':
            foreach ($_POST['tagged'] as $key => $val) {
                Sql_Query(sprintf('update %s set status = "candidate" where id = %d', $GLOBALS['tables']['bounceregex'],
                    $key));
            }
            break;
    }
    Redirect('bouncerules'.$url);
}

if (isset($_POST['listorder']) && is_array($_POST['listorder'])) {
    foreach ($_POST['listorder'] as $ruleid => $order) {
        Sql_Query(sprintf('update %s set listorder = %d where id = %d', $GLOBALS['tables']['bounceregex'], $order,
            $ruleid));
    }
}

if (isset($_GET['del']) && $_GET['del']) {
    Sql_Query(sprintf('delete from %s where id = %d', $GLOBALS['tables']['bounceregex'], $_GET['del']));
    Redirect('bouncerules'.$url);
}

if (isset($_POST['newrule']) && $_POST['newrule']) {
    Sql_Query(sprintf('insert into %s (regex, regexhash, action,comment,admin,status) values("%s","%s","%s","%s",%d,"active")',
        $GLOBALS['tables']['bounceregex'], sql_escape($_POST['newrule']), md5(sql_escape($_POST['newrule'])), sql_escape($_POST['action']),
        sql_escape($_POST['comment']), $_SESSION['logindetails']['id']), 1);
    $num = Sql_Affected_Rows();
    if ($num < 0) {
        echo '<p class="actionresult alert alert-info">'.$GLOBALS['I18N']->get('That rule exists already').'</p>';
    } else {
        Redirect('bouncerules'.$url);
    }
}
$count = Sql_Query(sprintf('select status, count(*) as num from %s group by status',
    $GLOBALS['tables']['bounceregex']));
while ($row = Sql_Fetch_Array($count)) {
    printf($GLOBALS['I18N']->get('Number of %s rules: %d').'<br/>', $row['status'], $row['num']);
}

$tabs = new WebblerTabs();
$tabs->addTab($GLOBALS['I18N']->get('active'), PageUrl2('bouncerules&amp;type=active'));
$tabs->addTab($GLOBALS['I18N']->get('candidate'), PageUrl2('bouncerules&amp;type=candidate'));
if ($type == 'candidate') {
    $tabs->setCurrent($GLOBALS['I18N']->get('candidate'));
} else {
    $tabs->setCurrent($GLOBALS['I18N']->get('active'));
}
echo "<p><div class='minitabs'>\n";
echo $tabs->display();
echo "</div></p>\n";

$some = 1;
$req = Sql_Query(sprintf('select * from %s where status = "%s" order by listorder,regex',
    $GLOBALS['tables']['bounceregex'], $type));
$ls = new WebblerListing($GLOBALS['I18N']->get('Bounce Regular Expressions'));
if (!Sql_Num_Rows($req)) {
    echo $GLOBALS['I18N']->get('No Rules found');
    $some = 0;
} else {
    echo formStart('class="bouncerulesListing"');
}

while ($row = Sql_Fetch_Array($req)) {
    $element = $GLOBALS['I18N']->get('rule').' '.$row['id'];
    $ls->addElement($element, PageUrl2('bouncerule&amp;id='.$row['id']));
    if ($type == 'candidate') {
        // check if it matches an active rule
        $activerule = matchedBounceRule($row['regex'], 1);
        if ($activerule) {
            $ls->addColumn($element, $GLOBALS['I18N']->get('match'),
                PageLink2('bouncerule&amp;id='.$activerule, $GLOBALS['I18N']->get('match')));
        }
    }

    $ls->addColumn($element, $GLOBALS['I18N']->get('expression'),
        '<a name="'.$row['id'].'"></a>'.shortenTextDisplay(htmlspecialchars($row['regex']), 50));
    $ls->addColumn($element, $GLOBALS['I18N']->get('action'), $GLOBALS['bounceruleactions'][$row['action']]);
//  $num = Sql_Fetch_Row_Query(sprintf('select count(*) from %s where regex = %d',$GLOBALS['tables']['bounceregex_bounce'],$row['id']));
//  $ls->addColumn($element,$GLOBALS['I18N']->get('#bncs'),$num[0]);
    $ls->addColumn($element, $GLOBALS['I18N']->get('#bncs'), $row['count']);

    $ls->addColumn($element, $GLOBALS['I18N']->get('tag'),
        sprintf('<input type="checkbox" name="tagged[%d]" value="%d">', $row['id'], $row['listorder']));
    $ls->addColumn($element, $GLOBALS['I18N']->get('order'),
        sprintf('<input type="text" name="listorder[%d]" value="%d" size=3>', $row['id'], $row['listorder']));
    $ls->addColumn($element, $GLOBALS['I18N']->get('del'),
        PageLink2('bouncerules&del='.$row['id'].$url, $GLOBALS['I18N']->get('del')));
}
echo $ls->display();
if ($some) {
    echo '<p class="information">'.$GLOBALS['I18N']->get('with tagged rules: ').' ';
    printf('<b>%s</b> <input type="checkbox" name="tagaction" value="delete"><br/>', $GLOBALS['I18N']->get('delete'));
    if ($type == 'candidate') {
        printf('<b>%s</b> <input type="checkbox" name="tagaction" value="activate"><br/>',
            $GLOBALS['I18N']->get('make active'));
    } else {
        printf('<b>%s</b> <input type="checkbox" name="tagaction" value="deactivate"><br/>',
            $GLOBALS['I18N']->get('make inactive'));
    }
    echo ' <p class="submit"><input type="submit" name="doit" value="'.$GLOBALS['I18N']->get('Save Changes').'"></p>';
    echo '</form>';
}
echo '<hr/>';
echo '<h3>'.$GLOBALS['I18N']->get('add a new rule').'</h3>';
echo '<form method=post>';
echo '<table class="bouncerulesAction">';
printf('<tr><td>%s</td><td><input type=text name="newrule" size=30></td></tr>',
    $GLOBALS['I18N']->get('Regular Expression'));
printf('<tr><td>%s</td><td><select name="action">', $GLOBALS['I18N']->get('Action'));
foreach ($GLOBALS['bounceruleactions'] as $action => $desc) {
    printf('<option value="%s">%s</option>', $action, $desc);
}
echo '</select></td></tr>';
printf('<tr><td colspan="2">%s</td></tr><tr><td colspan="2"><textarea name="comment" rows=10 cols=65></textarea></td></tr>',
    $GLOBALS['I18N']->get('Memo for this rule'));
echo '<tr><td colspan="2"><p class="submit"><input type="submit" name="add" value="'.$GLOBALS['I18N']->get('Add new Rule').'"></td></tr>';
echo '</table></form>';
