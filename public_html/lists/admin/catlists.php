<?php

require_once dirname(__FILE__).'/accesscheck.php';

$access = accessLevel('list');
switch ($access) {
    case 'owner':
        $subselect = ' where owner = '.$_SESSION['logindetails']['id'];
        break;
    case 'all':
        $subselect = '';
        break;
    case 'none':
    default:
        $subselect = ' where id = 0';
        break;
}
echo formStart('name="categoryedit"');

if (!isset($_GET['show']) || $_GET['show'] != 'all') {
    if (!empty($subselect)) {
        $subselect .= ' and ';
    } else {
        $subselect .= ' where ';
    }
    $subselect .= '(category is null or category = "")';
} else {
    $subselect = 'where true ';
}

$categories = listCategories();

if (!count($categories)) {
    //# try to fetch them from existing lists
    $req = Sql_Query(sprintf('select distinct category from %s where category != "" ', $tables['list']));
    while ($row = Sql_Fetch_Row($req)) {
        array_push($categories, $row[0]);
    }
    if (!count($categories)) {
        echo '<p>'.s('No list categories have been defined').'</p>';
        echo '<p>'.s('Once you have set up a few categories, come back to this page to classify your lists with your categories.').'</p>';
        echo '<p>'.PageLinkButton('configure&id=list_categories&ret=catlists',
                $I18N->get('Configure Categories')).'</p>';
        echo '<br/>';

        return;
    } else {
        saveConfig('list_categories', implode(',', $categories));
    }
}

if (!empty($_POST['category']) && is_array($_POST['category'])) {
    foreach ($_POST['category'] as $key => $val) {
        Sql_Query(sprintf('update %s set category = "%s" %s and id = %d ', $tables['list'], sql_escape($val),
            $subselect, $key));
    }
    if (isset($_GET['show']) && $_GET['show'] == 'all') {
        $_SESSION['action_result'] = s('Category assignments saved');
        Redirect('list');
    } else {
        Info(s('Categories saved'), true);
    }
}

$req = Sql_Query(sprintf('select * from %s %s', $tables['list'], $subselect));

if (!Sql_Affected_Rows()) {
    Info(s('All lists have already been assigned a category').'<br/>'.PageLinkButton('list', s('Back')), true);
}

echo '<div class="fright pull-right">'.PageLinkButton('catlists&show=all', s('Re-edit all lists')).'</div>';
echo '<div class="fright">'.PageLinkButton('configure&id=list_categories&ret=catlists',
        $I18N->get('Configure Categories')).'</div>';

$ls = new WebblerListing(s('Categorise lists'));
$aListCategories = listCategories();
if (count($aListCategories)) {
    while ($row = Sql_Fetch_Assoc($req)) {
        $ls->addELement($row['id']);
        $ls->addColumn($row['id'], $GLOBALS['I18N']->get('Name'), stripslashes($row['name']));
        $catselect = '<select name="category['.$row['id'].']">';
        $catselect .= '<option value="">-- '.s('choose category').'</option>';
        $catselect .= '<option value="">-- '.s('none').'</option>';
        foreach ($aListCategories as $category) {
            $category = trim($category);
            $catselect .= sprintf('<option value="%s" %s>%s</option>', $category,
                $category == $row['category'] ? 'selected="selected"' : '', $category);
        }
        $catselect .= '</select>';
        $ls->addColumn($row['id'], s('Category'), $catselect);
    }
}
$ls->addButton(s('save'), 'javascript:document.categoryedit.submit();');

echo $ls->display();
echo '</form>';
