<form method="post" action="">
<table class="spageeditForm">
<?php
require_once dirname(__FILE__).'/accesscheck.php';

# configure subscribe page

$subselect = '';
$access = accessLevel("spage");
switch ($access) {
  case "owner":
    $subselect = " where owner = ".$_SESSION["logindetails"]["id"];break;
  case "all":
    $subselect = "";break;
  case "none":
  default:
    $subselect = " where id = 0";break;
}
if (isset($_GET['id'])) {
  $id = sprintf('%d',$_GET['id']);
} else {
  $id = 0;
}

if (isset($_POST["save"]) || isset($_POST["activate"]) || isset($_POST["deactivate"])) {
  $owner = $_POST["owner"];
  $title = removeXss($_POST['title']);

  if (!$owner)
    $owner = $_SESSION['logindetails']['id'];
  if ($id) {
    Sql_Query(sprintf('update %s set title = "%s",owner = %d where id = %d',
      $tables["subscribepage"],$title,$owner,$id));
   } else {
    Sql_Query(sprintf('insert into %s (title,owner) values("%s",%d)',
      $tables["subscribepage"],$title,$owner));
     $id = Sql_Insert_Id($tables['subscribepage'], 'id');
  }
  Sql_Query(sprintf('delete from %s where id = %d',$tables["subscribepage_data"],$id));
  foreach (array("title","language_file","intro","header","footer","thankyoupage","button","htmlchoice","emaildoubleentry") as $item) {
    Sql_Query(sprintf('insert into %s (name,id,data) values("%s",%d,"%s")',
      $tables["subscribepage_data"],$item,$id,$_POST[$item]));
  }

  foreach (array("subscribesubject","subscribemessage","confirmationsubject","confirmationmessage") as $item) {
    SaveConfig("$item:$id",stripslashes($_POST[$item]),0);
  }
/*   dbg($_POST); */
/*   print("<pre>"); */
/*   print_r($_POST); */
/*   print("</pre>"); */
  ## rewrite attributes
  Sql_Query(sprintf('delete from %s where id = %d and name like "attribute___"',
    $tables["subscribepage_data"],$id));

  $attributes = "";
  if (isset($_POST['attr_use']) && is_array($_POST['attr_use'])) {
    $cnt=0;
    while (list($att,$val) = each ($_POST['attr_use'])) {
//BUGFIX 15285 - note 50677 (part 1: Attribute order) - by tipichris - mantis.phplist.com/view.php?id=15285
     // $default = $attr_default[$att];
     // $order = $attr_listorder[$att];
     // $required = $attr_required[$att];
      $default = $_POST['attr_default'][$att];
      ## rather crude sanitisation
      //      $default = preg_replace('/[^\w -\.]+/','',$default);
      // use unicode matching to keep non-ascii letters
      $default = preg_replace('/[^\p{L} -\.]+/u','',$default);
      $order = sprintf('%d',$_POST['attr_listorder'][$att]);
      $required = !empty($_POST['attr_required'][$att]);
//END BUGFIX 15285 - note 50677 (part 1)     

      Sql_Query(sprintf('insert into %s (id,name,data) values(%d,"attribute%03d","%s")',
        $tables["subscribepage_data"],$id,$att,
        $att.'###'.$default.'###'.$order.'###'.$required));
      $cnt++;
      $attributes .= $att.'+';
    }
  }
  Sql_Query(sprintf('replace into %s (id,name,data) values(%d,"attributes","%s")',
     $tables["subscribepage_data"],$id,$attributes));
  if (isset($_POST['list']) && is_array($_POST['list'])) {
    Sql_Query(sprintf('replace into %s (id,name,data) values(%d,"lists","%s")',
       $tables["subscribepage_data"],$id,join(',',$_POST['list'])));
  }
 
//obsolete, moved to rssmanager plugin 
//  if (ENABLE_RSS) {
//    Sql_Query(sprintf('replace into %s (id,name,data) values(%d,"rssintro","%s")',
//       $tables["subscribepage_data"],$id,$rssintro));
//    Sql_Query(sprintf('replace into %s (id,name,data) values(%d,"rss","%s")',
//       $tables["subscribepage_data"],$id,join(',',$rss)));
//    Sql_Query(sprintf('replace into %s (id,name,data) values(%d,"rssdefault","%s")',
//       $tables["subscribepage_data"],$id,$rssdefault));
//  }

  ### Store plugin data
  foreach ($GLOBALS['plugins'] as $plugin) {
    $plugin->processSubscribePageEdit($id);
  } 
  

  if (!empty($_POST['activate'])) {
    Sql_Query(sprintf('update %s set active = 1 where id = %d',
      $tables["subscribepage"],$id));
     Redirect("spage");
    exit;
  } elseif (!empty($_POST['deactivate'])) {
    Sql_Query(sprintf('update %s set active = 0 where id = %d',
      $tables["subscribepage"],$id));
     Redirect("spage");
    exit;
  }

}
@ob_end_flush();

## initialise values from defaults
$data = array();
$data["title"] = $GLOBALS['I18N']->get('Title of this set of lists');
$data["button"] = $strSubmit;
$data["intro"] = $strSubscribeInfo;
$data['language_file'] = '';#$GLOBALS['language_module'];
$data["header"] = getConfig("pageheader");
$data["footer"] = getConfig("pagefooter");
$data["thankyoupage"] = '<h3>'.$GLOBALS["strThanks"].'</h3>'."\n". $GLOBALS["strEmailConfirmation"];
$data["subscribemessage"] = getConfig("subscribemessage");
$data["subscribesubject"] = getConfig("subscribesubject");
$data["confirmationmessage"] = getConfig("confirmationmessage");
$data["confirmationsubject"] = getConfig("confirmationsubject");
$data["htmlchoice"] = "checkforhtml";
$data["emaildoubleentry"] = "yes";
$data["rssdefault"] = "daily";                                                                                                                          //Leftover from the preplugin era
$data["rssintro"] = $GLOBALS['I18N']->get('Please indicate how often you want to receive messages');  //Leftover from the preplugin era
//$rss = array_keys($rssfrequencies);   //Obsolete by rssmanager plugin
$selected_lists = array();
$attributedata = array();

if ($id) {
  ## Fill values from database
  $req = Sql_Query(sprintf('select * from %s where id = %d',$tables["subscribepage_data"],$id));
  while ($row = Sql_Fetch_Array($req)) {
    $data[$row["name"]] = $row["data"];
  }
  $ownerreq = Sql_Fetch_Row_Query(sprintf('select owner from %s where id = %d',$GLOBALS['tables']['subscribepage'],$id));
  $data['owner'] = $ownerreq[0];
  $attributes = explode('+',$data["attributes"]);
//  if (isset($data['rss'])) {                      //Obsolete by rssmanager plugin
//    $rss = explode(",",$data["rss"]);
//  } else { 
//    $rss = array();
//  }
  foreach ($attributes as $attribute) {
    if (!empty($data[sprintf('attribute%03d',$attribute)])) {
        list($attributedata[$attribute]["id"],
        $attributedata[$attribute]["default_value"],
        $attributedata[$attribute]["listorder"],
        $attributedata[$attribute]["required"]) = explode('###',$data[sprintf('attribute%03d',$attribute)]);
     }
  }
  if (isset($data['lists'])) {
    $selected_lists = explode(',',$data["lists"]);
  } else {
    $selected_lists = array();
  }
  printf('<input type="hidden" name="id" value="%d" />',$id);
  $data["subscribemessage"] = getConfig("subscribemessage:$id");
  $data["subscribesubject"] = getConfig("subscribesubject:$id");
  $data["confirmationmessage"] = getConfig("confirmationmessage:$id");
  $data["confirmationsubject"] = getConfig("confirmationsubject:$id");
}

print '<tr><td colspan="2"><h3>'.$GLOBALS['I18N']->get('General Information').'</h3></td></tr>';

printf('<tr><td valign="top" class="labeltop">%s</td><td><input type="text" name="title" value="%s" size="60" /></td></tr>',
  $GLOBALS['I18N']->get('Title'),
  htmlspecialchars(stripslashes($data["title"])));

$language_file = $GLOBALS['language_module'];
if (is_dir(dirname(__FILE__).'/../texts')) {
  $language_files = array();
  $landir = dir(dirname(__FILE__).'/../texts');
  while (false !== ($direntry = $landir->read())) {
    if (is_file($landir->path.'/'.$direntry) && preg_match('/\.inc$/i',$direntry)) {
      $language_files[$direntry] = basename($direntry,'.inc');
    }
  }
  $landir->close();
}
asort($language_files);
$language_select = '<select name="language_file">';
$language_select .= '<option value="">--'.$GLOBALS['I18N']->get('default').'</option>';
foreach ($language_files as $key => $val) {
  $language_select .= sprintf('<option value="%s" %s>%s</option>',$key,$key == $data['language_file']? 'selected="selected"':'',$val);
}
$language_select .= '</select>';

printf('<tr><td valign="top" class="labeltop">%s</td><td>%s</td></tr>',
  $GLOBALS['I18N']->get('Language file to use'),$language_select);

printf('<tr><td valign="top" class="labeltop">%s</td><td><textarea name="intro" cols="60" rows="10" class="virtual">%s</textarea></td></tr>',
  $GLOBALS['I18N']->get('Intro'),
  htmlspecialchars(stripslashes($data["intro"])));
printf('<tr><td valign="top" class="labeltop">%s</td><td><textarea name="header" cols="60" rows="10" class="virtual">%s</textarea></td></tr>',
  $GLOBALS['I18N']->get('Header'),
  htmlspecialchars(stripslashes($data["header"])));
printf('<tr><td valign="top" class="labeltop">%s</td><td><textarea name="footer" cols="60" rows="10" class="virtual">%s</textarea></td></tr>',
  $GLOBALS['I18N']->get('Footer'),
  htmlspecialchars(stripslashes($data["footer"])));
printf('<tr><td valign="top" class="labeltop">%s</td><td><textarea name="thankyoupage" cols="60" rows="10" class="virtual">%s</textarea></td></tr>',
  $GLOBALS['I18N']->get('Thank you page'),
  htmlspecialchars(stripslashes($data["thankyoupage"])));
printf('<tr><td valign="top" class="labeltop">%s</td><td><input type="text" name="button" value="%s" size="60" /></td></tr>',
  $GLOBALS['I18N']->get('Text for Button'),
  htmlspecialchars($data["button"]));
printf('<tr><td valign="top" class="labeltop">%s</td><td>',  $GLOBALS['I18N']->get('HTML Email choice'));
printf ('<input type="radio" name="htmlchoice" value="textonly" %s />
  %s <br/>',
  $data["htmlchoice"] == "textonly"?'checked="checked"':'',
  $GLOBALS['I18N']->get('Don\'t offer choice, default to <b>text</b>'));
printf ('<input type="radio" name="htmlchoice" value="htmlonly" %s />
  %s <br/>',
  $data["htmlchoice"] == "htmlonly"?'checked="checked"':'',
  $GLOBALS['I18N']->get('Don\'t offer choice, default to <b>HTML</b>'));
printf ('<input type="radio" name="htmlchoice" value="checkfortext" %s />
  %s <br/>',
  $data["htmlchoice"] == "checkfortext"?'checked="checked"':'',
  $GLOBALS['I18N']->get('Offer checkbox for text'));
printf ('<input type="radio" name="htmlchoice" value="checkforhtml" %s />
  %s <br/>',
  $data["htmlchoice"] == "checkforhtml"?'checked="checked"':'',
  $GLOBALS['I18N']->get('Offer checkbox for HTML'));
printf ('<input type="radio" name="htmlchoice" value="radiotext" %s />
  %s <br/>',
  $data["htmlchoice"] == "radiotext"?'checked="checked"':'',
  $GLOBALS['I18N']->get('Radio buttons, default to text'));
printf ('<input type="radio" name="htmlchoice" value="radiohtml" %s />
  %s <br/>',
  $data["htmlchoice"] == "radiohtml"?'checked="checked"':'',
  $GLOBALS['I18N']->get('Radio buttons, default to HTML'));
print "</td></tr>";

printf('<tr><td valign="top" class="labeltop">'.$GLOBALS['I18N']->get('Display Email confirmation').'</td><td>');
printf ('<input type="radio" name="emaildoubleentry" value="yes" %s />%s<br/>',
  $data["emaildoubleentry"]=="yes"?'checked="checked"':'',
  $GLOBALS['I18N']->get('Display email confirmation'));
printf ('<input type="radio" name="emaildoubleentry" value="no" %s />%s<br/>',
  $data["emaildoubleentry"]=="no"?'checked="checked"':'',
  $GLOBALS['I18N']->get('Don\'t display email confirmation'));
print '</td></tr>';

print '<tr><td colspan="2"><h3>'.$GLOBALS['I18N']->get('Message they receive when they subscribe').'</h3></td></tr>';
printf('<tr><td valign="top" class="labeltop">%s</td><td><input type="text" name="subscribesubject" value="%s" size="60" /></td></tr>',
  $GLOBALS['I18N']->get('Subject'),
  htmlspecialchars(stripslashes($data["subscribesubject"])));
printf('<tr><td valign="top" class="labeltop">%s</td><td><textarea name="subscribemessage" cols="60" rows="10" class="virtual">%s</textarea></td></tr>',
  $GLOBALS['I18N']->get('Message'),
  htmlspecialchars(stripslashes($data["subscribemessage"])));
print '<tr><td colspan="2"><h3>'.$GLOBALS['I18N']->get('Message they receive when they confirm their subscription').'</h3></td></tr>';
printf('<tr><td valign="top" class="labeltop">%s</td><td><input type="text" name="confirmationsubject" value="%s" size="60" /></td></tr>',
  $GLOBALS['I18N']->get('Subject'),
  htmlspecialchars(stripslashes($data["confirmationsubject"])));
printf('<tr><td valign="top" class="labeltop">%s</td><td><textarea name="confirmationmessage" cols="60" rows="10" class="virtual">%s</textarea></td></tr>',
  $GLOBALS['I18N']->get('Message'),
  htmlspecialchars(stripslashes($data["confirmationmessage"])));
print '<tr><td colspan="2"><h3>'.$GLOBALS['I18N']->get('Select the attributes to use').'</h3></td></tr><tr><td colspan="2">';
  $req = Sql_Query(sprintf('select * from %s order by listorder',
    $tables["attribute"]));
  $checked = array();
  while ($row = Sql_Fetch_Array($req)) {
    if (isset($attributedata[$row["id"]]) && is_array($attributedata[$row["id"]])) {
      $checked[$row["id"]] = "checked";
      $bgcol = '#F7E7C2';
      $value = $attributedata[$row["id"]];
     } else {
      $checked[$row["id"]] = '';
      $value = $row;
      $bgcol = '#ffffff';
    }

  ?>
  <table class="spageeditListing" border="1" width="100%" bgcolor="<?php echo $bgcol?>">
  <tr><td colspan="2" width="150"><?php echo $GLOBALS['I18N']->get('Attribute')?>:<?php echo $row["id"] ?></td>
      <td colspan="2"><?php echo $GLOBALS['I18N']->get('Check this box to use this attribute in the page')?> <input type="checkbox" name="attr_use[<?php echo $row["id"] ?>]" value="1" <?php echo $checked[$row["id"]]?> /></td>
  </tr>
  <tr><td colspan="2"><?php echo $GLOBALS['I18N']->get('Name')?>: </td><td colspan="2"><h4><?php echo htmlspecialchars(stripslashes($row["name"])) ?></h4></td></tr>
  <tr><td colspan="2"><?php echo $GLOBALS['I18N']->get('Type')?>: </td><td colspan="2"><h4><?php echo $GLOBALS['I18N']->get($row["type"])?></h4></td></tr>
  <tr><td colspan="2"><?php echo $GLOBALS['I18N']->get('Default Value')?>: </td><td colspan="2"><input type="text" name="attr_default[<?php echo $row["id"]?>]" value="<?php echo htmlspecialchars(stripslashes($value["default_value"])) ?>" size="40" /></td></tr>
  <tr><td><?php echo $GLOBALS['I18N']->get('Order of Listing')?>: </td><td><input type="text" name="attr_listorder[<?php echo $row["id"]?>]" value="<?php echo $value["listorder"] ?>" size="5" /></td>
  <td><?php echo $GLOBALS['I18N']->get('Is this attribute required?')?>: </td><td><input type="checkbox" name="attr_required[<?php echo $row["id"]?>]" value="1" <?php echo $value["required"] ? 'checked="checked"': '' ?> /></td></tr>
  </table><hr/>
<?php
  }

print '</td></tr>'; #this is ok

//obsolete, moved to rssmanager plugin 
//if (ENABLE_RSS) {
//  print '<tr><td colspan="2"><h3>'.$GLOBALS['I18N']->get('rss settings').'</h3></td></tr>';
//  printf('<tr><td valign=top>'.$GLOBALS['I18N']->get('Intro Text').'</td><td>
//  <textarea name=rssintro rows=3 cols=60>%s</textarea></td></tr>',
//    htmlspecialchars(stripslashes($data["rssintro"])));
//  foreach ($rssfrequencies as $key => $val) {
//    printf('<tr><td colspan="2"><input type=checkbox name="rss[]" value="%s" %s> %s %s
//    (%s <input type=radio name="rssdefault" value="%s" %s>)
//    </td></tr>',
//
//    $key,in_array($key,$rss)?'checked="checked"':'',
//    $GLOBALS['I18N']->get('Offer option to receive'),
//    $GLOBALS['I18N']->get($val),
//    $GLOBALS['I18N']->get('default'),
//    $key,$data["rssdefault"] == $key ? 'checked="checked"':''
//    );
//  }
//  print "<tr><td colspan="2"><hr/></td></tr>";
//}

  ### allow plugins to add rows
  foreach ($GLOBALS['plugins'] as $plugin) {
    print $plugin->displaySubscribepageEdit($data);
  } 
  
print '<tr><td colspan="2"><h3>'.$GLOBALS['I18N']->get('Select the lists to offer').'</h3></td></tr>';

$req = Sql_query("SELECT * FROM {$tables["list"]} $subselect order by listorder");
if (!Sql_Affected_Rows())
  print '<tr><td colspan="2">'.$GLOBALS['I18N']->get('No lists available, please create one first').'</td></tr>';
while ($row = Sql_Fetch_Array($req)) {
  printf ('<tr><td valign="top" width="150"><input type="checkbox" name="list[%d]" value="%d" %s /> %s</td><td>%s</td></tr>',
    $row["id"],$row["id"],in_array($row["id"],$selected_lists)?'checked="checked"':'',stripslashes($row["name"]),stripslashes($row["description"]));
}

print '</table>';
if ($GLOBALS["require_login"] && (isSuperUser() || accessLevel("spageedit") == "all")) {
  if (!isset($data['owner'])) {
    $data['owner'] = 0;
  }
  print '<br/>'.$GLOBALS['I18N']->get('Owner').': <select name="owner">';
  $admins = $GLOBALS["admin_auth"]->listAdmins();
  foreach ($admins as $adminid => $adminname) {
    printf ('<option value="%d" %s>%s</option>',$adminid,$adminid == $data['owner']? 'selected="selected"':'',$adminname);
  }
  print '</select>';
}

print '
<input class="submit" type="submit" name="save" value="'.$GLOBALS['I18N']->get('Save Changes').'" />;
<input class="submit" type="submit" name="activate" value="'.$GLOBALS['I18N']->get('Save and Activate').'" />
<input class="submit" type="submit" name="deactivate" value="'.$GLOBALS['I18N']->get('Save and Deactivate').'" />
</form>';

?>

