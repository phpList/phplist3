<div id="configurecontent"></div>

<?php
require_once dirname(__FILE__) . '/accesscheck.php';
/*
if ($_GET["firstinstall"] || $_SESSION["firstinstall"]) {
  $_SESSION["firstinstall"] = 1;
  print "<p class="x">" . $GLOBALS['I18N']->get('checklist for installation') . "</p>";
  require "setup.php";
}
*/

if (isset($_GET['resetdefault']) && $_GET['resetdefault'] == 'yes') {
    Sql_Query(sprintf('delete from %s where editable and item in ("%s")', $GLOBALS['tables']['config'],
        implode('","', array_keys($default_config))));
    $_SESSION['action_result'] = s('The settings have been reset to the phpList default');
    Redirect('configure');
}

if (empty($_REQUEST['id'])) {
    $id = '';
} else {
    $id = $_REQUEST['id'];
    if (!isset($default_config[$id])) {
        print $GLOBALS['I18N']->get('invalid request');

        return;
    }
}
#print '<div class="actions">'.PageLinkButton('configure&resetdefault=yes',s('Reset to default')).'</div>';

if (empty($_GET['id'])) {
    ## @@TODO might be an idea to allow reset on an "id" as well
    $button = new ConfirmButton(
        s('Are you sure you want to reset the configuration to the default?'),
        PageURL2('configure&resetdefault=yes', 'reset', ''),
        s('Reset to default'));

    print '<div class="fright">' . $button->show() . '</div>';
    print Info(s('You can edit all of the values in this page, and click the "save changes" button once to save all the changes you made.'),
        1);
}

$configCategories = array();
$configTypes = array();

foreach ($default_config as $item => $details) {
    if (empty($details['category'])) {
        $details['category'] = 'other';
    }
    if (empty($details['type'])) {
        $details['type'] = 'undefined';
    }
    if (!isset($configCategories[strtolower($details['category'])])) {
        $configCategories[strtolower($details['category'])] = array();
    }
    if (!isset($configTypes[$details['type']])) {
        $configTypes[$details['type']] = array();
    }
    $configTypes[$details['type']][] = $item;
    $configCategories[strtolower($details['category'])][] = $item;
}
#var_dump($configCategories);
#var_dump($configTypes);

print formStart(' class="configForm" enctype="multipart/form-data" ');
# configure options
reset($default_config);
if (!empty($_REQUEST['save'])) {
    if (!verifyToken()) {
        print Error(s('Invalid security token, please reload the page and try again'));

        return;
    }
    $info = $default_config[$id];
    $haserror = 0;
    if (is_array($_POST['values'])) {
        foreach ($_POST['values'] as $id => $value) {
            if (isset($default_config[$id])) {
                $info = $default_config[$id];
                if ($id == 'website' || $id == 'domain') {
                    $value = str_replace('[DOMAIN]', '', $value);
                    $value = str_replace('[WEBSITE]', '', $value);
                }
                if (empty($value) && !$info['allowempty']) {
                    #    Error($info['description']. ' ' . $GLOBALS['I18N']->get('cannot be empty'));
                    $haserror = $info['description'] . ' ' . $GLOBALS['I18N']->get('cannot be empty');
                } else {
                    $haserror = SaveConfig($id, $value);
                }
            }
        }
        if (!$haserror) {
            print '<div class="actionresult">' . s('Changes saved') . '</div>';
            unset($id);
        } else {
            print '<div class="actionresult error">' . $haserror . '<br/>' . s('Changes not saved') . '</div>';
            unset($id);
        }
#    Redirect("configure");
#    exit;
    }
    $item = $_REQUEST['save'];
    $item = str_replace('item_', '', $item);

    if (isset($_POST['ret']) && $_POST['ret'] == 'catlists') {
        $_SESSION['action_result'] = s('Categories saved');
        Redirect('catlists');
    }

    if (in_array($item, array_keys($default_config))) {
        Redirect('configure#item_' . $item);
        exit;
    }
}

if (empty($id)) {
    $alternate = 1;

    foreach ($configCategories as $configCategory => $configItems) {
        $some = 0;
        $categoryHTML = '<fieldset id="' . $configCategory . '">';
        $categoryHTML .= '<legend>' . s($configCategory) . ' ' . s('settings') . '</legend>';

        foreach ($configItems as $configItem) {
            $dbvalue = getConfig($configItem);
            if (isset($dbvalue)) {
                $value = $dbvalue;
            } else {
                $value = $default_config[$configItem]['value'];
            }
            $displayValue = nl2br(htmlspecialchars(stripslashes($value)));
            if ($default_config[$configItem]['type'] == 'boolean') {
                if ($value) {
                    $displayValue = s('Yes');
                } else {
                    $displayValue = s('No');
                }
            }
            if ($default_config[$configItem]['type'] == 'image') {
                if ($value) {
                    $displayValue = sprintf('<img src="./?page=image&amp;id=%d&amp;m=300" />', $value);
                }
            }

            if (!in_array($configItem, $GLOBALS['noteditableconfig'])) {
                $some = 1;

                $resourceLink = sprintf('<a class="resourcereference" href="http://resources.phplist.com/%s/config:%s" target="_blank">?</a>',
                    $_SESSION['adminlanguage']['iso'], $configItem);
                ## disable this until the resources wiki is organised properly
                $resourceLink = '';

                $categoryHTML .= sprintf('<div class="shade%d"><div class="configEdit" id="item_%s"><a href="%s" class="ajaxable" title="%s">%s</a> <b>%s</b> %s</div>',
                    $alternate, $configItem, PageURL2('configure', '', "id=$configItem"), s('edit this value'),
                    s('edit'), $default_config[$configItem]['description'], $resourceLink);
                $categoryHTML .= sprintf('<div id="edit_%s" class="configcontent">%s</div></div>', $configItem,
                    $displayValue);
                if ($alternate == 1) {
                    $alternate = 2;
                } else {
                    $alternate = 1;
                }
            }
        }
        $categoryHTML .= '</fieldset>';
        if ($some) {
            print $categoryHTML;
        }
    }
    print '</form>';
} else {
    include dirname(__FILE__) . '/actions/configure.php';
}
