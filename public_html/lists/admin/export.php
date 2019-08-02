<?php
require_once dirname(__FILE__).'/accesscheck.php';

// export users from PHPlist

include dirname(__FILE__).'/date.php';

$fromdate = '';
$todate = '';
$from = new date('from');
$to = new date('to');
if (isset($_REQUEST['list'])) {
    if (isset($_GET['list'])) {
        $list = sprintf('%d', $_GET['list']);
    } elseif (isset($_POST['column']) && $_POST['column'] == 'listentered') {
        $list = sprintf('%d', $_POST['list']);
    } else {
        $list = 0;
    }
} else {
    $list = 0;
}

$access = accessLevel('export');
switch ($access) {
    case 'owner':
        if ($list) {
            $check = Sql_Fetch_Assoc_Query(sprintf('select id from %s where owner = %d and id = %d',
                $GLOBALS['tables']['list'], $_SESSION['logindetails']['id'], $list));
            if (empty($check['id'])) {
                echo Error(s('That is not your list'));

                return;
            }
        }
        $querytables = $GLOBALS['tables']['list'].' list INNER JOIN '.$GLOBALS['tables']['listuser'].' listuser ON listuser.listid = list.id'.
            ' INNER JOIN '.$GLOBALS['tables']['user'].' user ON listuser.userid = user.id';
        $subselect = ' list.id = '.$list.' and list.owner = '.$_SESSION['logindetails']['id'];
        $ownerselect_where = ' where owner = '.$_SESSION['logindetails']['id'];
        break;
    case 'all':
        if ($list) {
            $querytables = $GLOBALS['tables']['user'].' user'.', '.$GLOBALS['tables']['listuser'].' listuser ON user.id = listuser.userid';
            $subselect = '';
        } else {
            $querytables = $GLOBALS['tables']['user'].' user';
            $subselect = '';
        }
        $ownerselect_where = '';
        break;
    case 'none':
    default:
        $querytables = $GLOBALS['tables']['user'].' user';
        $subselect = ' and user.id = 0';
        $ownerselect_where = ' where owner = 0';
        break;
}

require dirname(__FILE__).'/structure.php';
if (isset($_POST['processexport'])) {
    if (!verifyToken()) { //# csrf check
        echo Error($GLOBALS['I18N']->get('Invalid security token. Please reload the page and try again.'));

        return;
    }
    $_SESSION['export'] = array();
    $_SESSION['export']['column'] = $_POST['column'];
    $_SESSION['export']['cols'] = $_POST['cols'];
    $_SESSION['export']['attrs'] = $_POST['attrs'];
    $_SESSION['export']['fromdate'] = $from->getDate('from');
    $_SESSION['export']['todate'] = $to->getDate('to');
    $_SESSION['export']['list'] = $list;

    echo $GLOBALS['img_busy'];
    echo '<div id="progresscount" style="width: 200; height: 50;">Progress</div>';
    echo '<br/> <iframe id="export" src="./?page=pageaction&action=export&ajaxed=true'.addCsrfGetToken().'" scrolling="no" height="50"></iframe>';

    return;
}

if ($list) {
    echo s('Export subscribers on %s', ListName($list));
}

echo formStart();
$checked = 'nodate';
if (isset($_GET['list']) && $_GET['list'] == 'all') {
    $checked = 'nodate';
}

?>

<?php echo $GLOBALS['I18N']->get('What date needs to be used:'); ?><br/>
    <input type="radio" name="column"
           value="nodate" <?php echo $checked == 'nodate' ? ' checked="checked"' : '' ?> /> <?php echo s('Any date'); ?> (<?php echo s('Export all subscribers'); ?>)
    <br/>
    <input type="radio" name="column"
           value="entered" <?php echo $checked == 'entered' ? ' checked="checked"' : '' ?>/> <?php echo s('When they signed up'); ?>
    <br/>
    <input type="radio" name="column"
           value="modified" <?php echo $checked == 'modified' ? ' checked="checked"' : '' ?>/> <?php echo s('When the record was changed'); ?>
    <br/>
    <input type="radio" name="column"
           value="historyentry" <?php echo $checked == 'historyentry' ? ' checked="checked"' : '' ?>/> <?php echo s('Based on changelog'); ?>
    <br/>
    <input type="radio" name="column"
           value="listentered" <?php echo $checked == 'listentered' ? ' checked="checked"' : '' ?>/> <?php echo s('When they subscribed to'); ?>


<?php
if (empty($list)) {
    echo '<select name="list">';
    $req = Sql_Query(sprintf('select * from %s %s', $GLOBALS['tables']['list'], $ownerselect_where));
    while ($row = Sql_Fetch_Array($req)) {
        printf('<option value="%d">%s</option>', $row['id'], $row['name']);
    }
    echo '</select>';
} else {
    printf('<input type="hidden" name="list" value="%d" />', $list);
    echo '<strong>'.listName($list).'</strong><br/><br/>';
}
?>
    <div id="exportdates">
        <?php echo $GLOBALS['I18N']->get('Date From:'); ?><?php echo $from->showInput('', '', $fromdate); ?>
        <?php echo $GLOBALS['I18N']->get('Date To:'); ?><?php echo $to->showInput('', '', $todate); ?>
    </div>


<?php echo s('Select the columns to include in the export'); ?>
    <div><input type="checkbox" name="selectallcheckbox" id="selectallcheckbox" checked="checked"/><label
            for="selectallcheckbox"><?php echo s('Select all') ?> </label></div>

<?php
$cols = array();
foreach ($DBstruct['user'] as $key => $val) {
    if (strpos($val[1], 'sys') === false) {
        printf("\n".'<br/><input type="checkbox" name="cols[]" value="%s" checked="checked" /> %s ', $key, $val[1]);
    } elseif (preg_match('/sysexp:(.*)/', $val[1], $regs)) {
        printf("\n".'<br/><input type="checkbox" name="cols[]" value="%s" checked="checked" /> %s ', $key, $regs[1]);
    }
}
$res = Sql_Query("select id,name,tablename,type from {$tables['attribute']} order by listorder");
$attributes = array();
while ($row = Sql_fetch_array($res)) {
    printf("\n".'<br/><input type="checkbox" name="attrs[]" value="%s" checked="checked" /> %s ', $row['id'],
        stripslashes(htmlspecialchars($row['name'])));
}

?>

    <p class="submit"><input type="submit" name="processexport" id="processexport" value="<?php echo s('Export'); ?>">
    </p></form>

<?php
