<?php

$id = sprintf('%d',isset($_GET["id"]) ? $_GET['id']:0);
$delete = sprintf('%d',isset($_GET['delete']) ? $_GET["delete"]:0);
$date = new Date();
$newuser = 0;
$feedback = '';

$access = accessLevel("user");
switch ($access) {
  case "owner":
    $subselect = sprintf(' and %s.owner = %d',$tables["list"],$_SESSION["logindetails"]["id"]);
    $subselect_where = sprintf(' where %s.owner = %d',$tables["list"],$_SESSION["logindetails"]["id"]);break;
  case "all":
    $subselect = "";$subselect_where = '';break;
  case "view":
    $subselect = "";
    if (sizeof($_POST)) {
      print Error($GLOBALS['I18N']->get('You only have privileges to view this page, not change any of the information'));
      return;
    }
    break;
  case "none":
  default:
    $subselect = " and ".$tables["list"].".id = 0";
    $subselect_where = " where ".$tables["list"].".owner = 0";break;
}

if ($access != "all") {
  $delete_message =$GLOBALS['I18N']->get('Delete will remove subscriber from the list');
} else {
  $delete_message = $GLOBALS['I18N']->get('Delete will remove subscriber from the system');
}

$usegroups = Sql_Table_exists("groups") && Sql_Table_exists('user_group');
$error_exist= 0;


if (!empty($_POST["change"]) && ($access == "owner"|| $access == "all")) {
  if (!verifyToken()) {
    print Error($GLOBALS['I18N']->get('Invalid security token, please reload the page and try again'));
    return;
  }
  if (isset($_POST['email'])) {
    ## let's not validate here, an admin can add anything as an email, if they like
    $email = $_POST['email'];
  } else {
    $email = '';
  }

/*
  if (empty($_POST['password']) && !$id) {
    print $GLOBALS['I18N']->get('Error adding empty password, please check that the password is complete');
    $error_exist = 1;
  }
*/

  if (!$error_exist){
     if (!$id) {
       $id = addNewUser($_POST['email']);
       $newuser = 1;
     }
   
     if (!$id) {
       print $GLOBALS['I18N']->get('Error adding subscriber, please check that the subscriber exists');
       $error_exist = 1;
       //return;
     }
  }


  /************ BEGIN <whitout_error IF block>  (end in line 264) **********************/
  if (!$error_exist){
     # read the current values to compare changes
     $old_data = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d',$tables["user"],$id));
     $old_data = array_merge($old_data,getUserAttributeValues('',$id));
   
     # and membership of lists
     $old_listmembership = array();
     $req = Sql_Query("select * from {$tables["listuser"]} where userid = $id");
     while ($row = Sql_Fetch_Array($req)) {
       $old_listmembership[$row["listid"]] = listName($row["listid"]);
     }
   
     while (list ($key,$val) = each ($struct)) {
       if (is_array($val)) {
         if (isset($val[1]) && strpos($val[1],':')) {
           list($a,$b) = explode(":",$val[1]);
         } else {
           $a = $b = '';
         }
         if (strpos($a,"sys") === false && $val[1]) {
           if ($key == "password" && ENCRYPTPASSWORD) {
             if (!empty($_POST[$key])){
               Sql_Query("update {$tables["user"]} set $key = \"".encryptPass($_POST[$key])."\" where id = $id");
             }
           } else {
             if ($key != "password" || !empty($_POST[$key])){
               if ($key == "password") {
                 $_POST[$key] = hash("sha256",$_POST[$key]);
               }

               Sql_Query("update {$tables["user"]} set $key = \"".sql_escape($_POST[$key])."\" where id = $id");
             }
           }
         } elseif ((!$require_login || ($require_login && isSuperUser())) && $key == "confirmed") {
           Sql_Query("update {$tables["user"]} set $key = \"".sql_escape($_POST[$key])."\" where id = $id");
         }
       }
     }
   
     if ( !empty($_FILES) && is_array($_FILES) ) { ## only avatars are files
        foreach ($_FILES['attribute']['name'] as $key => $val) {
           if (!empty($_FILES['attribute']['name'][$key])) {
              $tmpnam = $_FILES['attribute']['tmp_name'][$key];
              $size = $_FILES['attribute']['size'][$key];
   
              if ($size < MAX_AVATAR_SIZE) {
                 $avatar = file_get_contents($tmpnam);
                 Sql_Query(sprintf('replace into %s (userid,attributeid,value)
                 values(%d,%d,"%s")',$tables["user_attribute"],$id,$key,base64_encode($avatar)));
              } elseif ($size) {
                print Error($GLOBALS['I18N']->get('Uploaded avatar file too big'));
              }
           } 
        }
     }
   
     if (isset($_POST['attribute']) && is_array($_POST['attribute'])) {
       foreach ($_POST['attribute'] as $key => $val) {
         Sql_Query(sprintf('replace into %s (userid,attributeid,value)
           values(%d,%d,"%s")',$tables["user_attribute"],$id,$key,sql_escape($val)));
       }
     }
   
     if (isset($_POST['dateattribute']) && is_array($_POST["dateattribute"]))
     foreach ($_POST["dateattribute"] as $attid => $attname) {
       if (isset($_POST[normalize($attname).'_novalue'])) {
         $value = "";
       } else {
         $value = $date->getDate($attname);
       }
       Sql_Query(sprintf('replace into %s (userid,attributeid,value)
         values(%d,%d,"%s")',$tables["user_attribute"],$id,$attid,$value));
     }

     if (isset($_POST['cbattribute']) && is_array($_POST['cbattribute'])) {
       while (list($key,$val) = each ($_POST['cbattribute'])) {
         if (isset($_POST['attribute'][$key]) && $_POST['attribute'][$key] == "on") {
           Sql_Query(sprintf('replace into %s (userid,attributeid,value)
             values(%d,%d,"on")',$tables["user_attribute"],$id,$key));
         } else {
           Sql_Query(sprintf('replace into %s (userid,attributeid,value)
             values(%d,%d,"")',$tables["user_attribute"],$id,$key));
         }
       }
     }
   
     if (isset($_POST['cbgroup']) && is_array($_POST['cbgroup'])) {
       while (list($key,$val) = each ($_POST['cbgroup'])) {
         $field = "cbgroup".$val;
         if (isset($_POST[$field]) && is_array($_POST[$field])) {
           $newval = array();
           foreach ($_POST[$field] as $fieldval) {
             array_push($newval,sprintf('%0'.$checkboxgroup_storesize.'d',$fieldval));
           }
           $value = join(",",$newval);
         } else {
           $value = "";
         }
         Sql_Query(sprintf('replace into %s (userid,attributeid,value)
           values(%d,%d,"%s")',$tables["user_attribute"],$id,$val,$value));
       }
     }
     if ($usegroups && empty($GLOBALS['config']['usergroup_types'])) {
       ## old method, using checkboxes
       Sql_Query("delete from user_group where userid = $id");
       if (is_array($_POST["groups"])) {
         foreach ($_POST["groups"] as $group) {
           Sql_Query(sprintf('insert into user_group (userid,groupid) values(%d,%d)',$id,$group));
           $feedback .= "<br/>".$GLOBALS['I18N']->get('User added to group').' '.groupName($group);
         }
       }
     } elseif ($usegroups) {
       ## new method, allowing a group membership type
       $newgrouptype = sprintf('%d',$_POST['newgrouptype']);
       $newgroup = sprintf('%d',$_POST['newgroup']);
       
       if (!empty($newgrouptype) && !empty($newgroup)) {
         Sql_Query(sprintf('insert into user_group (userid,groupid,type) values(%d,%d,%d)',$id,$newgroup,$newgrouptype));
         $feedback .= "<br/>".$GLOBALS['I18N']->get('User added to group').' '.groupName($newgroup);
       } 
       ## make sure they're in the everyone group
       Sql_Query(sprintf('insert ignore into user_group (userid,groupid,type) values(%d,%d,0)',$id,getEveryoneGroupID()));
     }
       
     # submitting page now saves everything, so check is not necessary
     if ($subselect == "") {
       Sql_Query("delete from {$tables["listuser"]} where userid = $id");
     } else {
       # only unsubscribe from the lists of this admin
       $req = Sql_Query("select id from {$tables["list"]} $subselect_where");
       while ($row = Sql_Fetch_Row($req)) {
         Sql_Query("delete from {$tables["listuser"]} where userid = $id and listid = $row[0]");
       }
     }
     if (isset($_POST["subscribe"]) && is_array($_POST["subscribe"])) {
       foreach ($_POST["subscribe"] as $ind => $lst) {
         Sql_Query("insert into {$tables["listuser"]} (userid,listid) values($id,$lst)");
         $feedback .= '<br/>'.sprintf($GLOBALS['I18N']->get('Subscriber added to list %s'),ListName($lst));
       }
       $feedback .= "<br/>";
     }
     $history_entry = '';
     $current_data = Sql_Fetch_Array_Query(sprintf('select * from %s where id = %d',$tables["user"],$id));
     $current_data = array_merge($current_data,getUserAttributeValues('',$id));
   
     foreach ($current_data as $key => $val) {
       if (!is_numeric($key))
       if (isset($old_data[$key]) && $old_data[$key] != $val && $key != "modified") {
         if ($old_data[$key] == '') $old_data[$key] = '(no data)';
         $history_entry .= "$key = $val\nchanged from $old_data[$key]\n";
        }
     }
     if (!$history_entry) {
       $history_entry = "\nNo data changed";
     }

     # check lists
     $listmembership = array();
     $req = Sql_Query("select * from {$tables["listuser"]} where userid = $id");

     while ($row = Sql_Fetch_Array($req)) {
       $listmembership[$row["listid"]] = listName($row["listid"]);
     }

     # i'll do this once I can test it on a 4.3 server
     #if (function_exists("array_diff_assoc")) {
     if (0) {
       # it requires 4.3
       $subscribed_to = array_diff_assoc($listmembership, $old_listmembership);
       $unsubscribed_from = array_diff_assoc($old_listmembership,$listmembership);
       foreach ($subscribed_to as $key => $desc) {
         $history_entry .= "Subscribed to $desc\n";
       }
       foreach ($unsubscribed_to as $key => $desc) {
         $history_entry .= "Unsubscribed from $desc\n";
       }
     } else {
       $history_entry .= "\nList subscriptions:\n";
       foreach ($old_listmembership as $key => $val) {
         $history_entry .= "Was subscribed to: $val\n";
       }
       foreach ($listmembership as $key => $val) {
         $history_entry .= "Is now subscribed to: $val\n";
       }
       if (!sizeof($listmembership)) {
         $history_entry .= "Not subscribed to any lists\n";
       }
     }
   
     addUserHistory($email,"Update by ".adminName($_SESSION["logindetails"]["id"]),$history_entry);
     if (!empty($newuser)) {
       Redirect("user&id=$id");
       exit;
     }

     Info($GLOBALS['I18N']->get('Changes saved').$feedback);
  }
  /************ END <whitout_error IF block>  (start in line 71) **********************/
}
   
   if (isset($delete) && $delete && $access != "view") {
     # delete the index in delete
     print $GLOBALS['I18N']->get('Deleting')." $delete ..\n";
     if ($require_login && !isSuperUser()) {
       $lists = Sql_query("SELECT listid FROM {$tables["listuser"]},{$tables["list"]} where userid = ".$delete." and $tables[listuser].listid = $tables[list].id $subselect ");
       while ($lst = Sql_fetch_array($lists))
         Sql_query("delete from {$tables["listuser"]} where userid = $delete and listid = $lst[0]");
     } else {
       deleteUser($delete);
     }
     print '..'.$GLOBALS['I18N']->get('Done')."<br /><hr/><br />\n";
   }
   
   if ($usegroups && !empty($GLOBALS['config']['usergroup_types']) && $access != "view") {
     ## check for deletion of group membership
     $delgroup = sprintf('%d',$_GET['delgroup']);
     $delgrouptype = sprintf('%d',$_GET['deltype']);
     if (!empty($delgroup)) {# && !empty($delgrouptype)) {
       Sql_Query(sprintf('delete from user_group where userid = %d and groupid = %d and type = %d',$id,$delgroup,$delgrouptype));
       print "<br/>".$GLOBALS['I18N']->get('Subscriber removed from group').' '.groupName($delgroup).' ';
       print PageLink2('user&amp;id='.$id,$GLOBALS['I18N']->get('Continue'));
       return;
     }
   }


/********* NORMAL FORM DISPLAY ***********/
$membership = "";
$subscribed = array();
if ($id) {
  $result = Sql_query(sprintf('select * from %s where id = %d', $tables["user"],$id));

  if (!Sql_Affected_Rows()) {
    Fatal_Error($GLOBALS['I18N']->get('No such subscriber'));
    return;
  }

  $user = sql_fetch_array($result);
  $lists = Sql_query("SELECT listid,name FROM {$tables["listuser"]},{$tables["list"]} where userid = ".$user["id"]." and $tables[listuser].listid = $tables[list].id $subselect ");

  while ($lst = Sql_fetch_array($lists)) {
    $membership .= "<li>".PageLink2("editlist",$lst["name"],"id=".$lst["listid"]).'</li>';
    array_push($subscribed,$lst["listid"]);
  }

  if (!$membership)
  $membership = $GLOBALS['I18N']->get('No Lists');

  if (empty($returnurl)) { $returnurl = ''; }

  print '<div class="actions">';
  //printf('&nbsp;&nbsp;<a href="%s" class="button">%s</a>',getConfig("preferencesurl").
         //'&amp;uid='.$user["uniqid"],$GLOBALS['I18N']->get('update page'));
  //printf('&nbsp;&nbsp;<a href="%s" class="button">%s</a>',getConfig("unsubscribeurl").'&amp;uid='.$user["uniqid"],$GLOBALS['I18N']->get('unsubscribe page'));
  print '&nbsp;&nbsp;'.PageLinkButton("userhistory&amp;id=$id",$GLOBALS['I18N']->get('History'));
  if (!empty($GLOBALS['config']['plugins']) && is_array($GLOBALS['config']['plugins'])) {
    foreach ($GLOBALS['config']['plugins'] as $pluginName => $plugin) {
      print $plugin->userpageLink($id);
    }
  }

  if ($access != "view")
  printf(" <a class=\"delete button\" href=\"javascript:deleteRec('%s');\">delete</a> %s<h3>%s</h3>",
         PageURL2("user","","delete=$id&amp;$returnurl"),$delete_message,$user["email"]);

  print '</div>';
} else {

  if (!empty($_POST["subscribe"])){
     foreach($_POST["subscribe"] AS $idx => $listid){
        array_push($subscribed, $listid);
     }
  }

  $id = 0;
  print '<h3>'.s('Add a new subscriber').'</h3>';
  if (empty($_POST['email'])) {
    print formStart();
    print s('Email address').': '.'<input type="text" name="email" value="" />';
    print '<input type="submit" name="change" value="'.s('Continue').'">';
    print '</form>';
    return;
  }
}

  #print '<h3>'.$GLOBALS['I18N']->get('Subscriber details')."</h3>";
  print formStart('enctype="multipart/form-data"');
  if ( empty ($list) ) { $list = ''; }
  print '<input type="hidden" name="list" value="'.$list.'" /><input type="hidden" name="id" value="'.$id.'" />';
  if ( empty ($returnpage) ) { $returnpage = ''; }
  if ( empty ($returnoption) ) { $returnoption = ''; }
  print '<input type="hidden" name="returnpage" value="'.$returnpage.'" /><input type="hidden" name="returnoption" value="'.$returnoption.'" />';

  reset($struct);

  $userdetailsHTML = $mailinglistsHTML = $groupsHTML =  '';
  $userdetailsHTML .= '<table class="userAdd" border="1">';


  while (list ($key,$val) = each ($struct)) {
    @list($a,$b) = explode(":",$val[1]);

    if (!isset($user[$key]))
    $user[$key] = "";

    if ($key == "confirmed") {
      if (!$require_login || ($require_login && isSuperUser())) {
        $userdetailsHTML .= sprintf('<tr><td class="dataname">%s (1/0)</td><td><input type="text" name="%s" value="%s" size="5" /></td></tr>'."\n",$GLOBALS['I18N']->get($b),$key,htmlspecialchars($user[$key]));
      } else {
        $userdetailsHTML .= sprintf('<tr><td class="dataname">%s</td><td>%s</td></tr>',$b,$user[$key]);
      }
    } elseif ($key == "password") {
      $userdetailsHTML .= sprintf('<tr><td class="dataname">%s</td><td><input type="text" name="%s" value="%s" size="30" /></td></tr>'."\n",$val[1],$key,"");
    }
    /*
    } elseif ($key == "password" && ENCRYPTPASSWORD) {
      $userdetailsHTML .= sprintf('<tr><td>%s (%s)</td><td><input type="text" name="%s" value="%s" size="30" /></td></tr>'."\n",$GLOBALS['I18N']->get('encrypted'),$val[1],$key,"");
    */
    elseif ($key == "blacklisted") {
      $userdetailsHTML .= sprintf('<tr><td class="dataname">%s</td><td>%s</td></tr>',$GLOBALS['I18N']->get($b),isBlackListed($user['email']));
    } else {
      if (!strpos($key,'_')) {
        if (strpos($a,"sys") !== false)
          $userdetailsHTML .= sprintf('<tr><td class="dataname">%s</td><td>%s</td></tr>',$GLOBALS['I18N']->get($b),$user[$key]);
        elseif ($val[1])
          $userdetailsHTML .= sprintf('<tr><td class="dataname">%s</td><td><input type="text" name="%s" value="%s" size="30" /></td></tr>'."\n",$GLOBALS['I18N']->get($val[1]),$key,htmlspecialchars($user[$key]));
      }
    }
  }

  if (empty($GLOBALS['config']['hide_user_attributes']) && !defined('HIDE_USER_ATTRIBUTES')) {
    $res = Sql_Query("select * from $tables[attribute] order by listorder");

    while ($row = Sql_fetch_array($res)) {
      if (!empty($id)) {
         $val_req = Sql_Fetch_Row_Query("select value from $tables[user_attribute] where userid = $id and attributeid = $row[id]");
         $row["value"] = $val_req[0];
      } elseif (!empty($_POST["attribute"][$row["id"]])) {
         $row["value"] = $_POST["attribute"][$row["id"]];
      } else {
        $row['value'] = '';
      }

      if ($row["type"] == "date") {
        $userdetailsHTML .= sprintf('<input class="attributeinput" type="hidden" name="dateattribute[%d]" value="%s" />',$row["id"],$row["name"]);
        $novalue = trim($row["value"]) == "" ? "checked":"";
        $userdetailsHTML .= sprintf('<tr><td class="dataname">%s<!--%s--></td><td>%s&nbsp; Not set: <input type="checkbox" name="%s_novalue" %s /></td></tr>'."\n",stripslashes($row["name"]),$row["value"],$date->showInput($row["name"],"",$row["value"]),normalize(stripslashes($row["name"])),$novalue);
      } elseif ($row["type"] == "checkbox") {
        $checked = $row["value"] == "on" ? 'checked="checked"':'';
        $userdetailsHTML .= sprintf('<tr><td class="dataname">%s</td><td><input class="attributeinput" type="hidden" name="cbattribute[%d]" value="%d" />
                          <input class="attributeinput" type="checkbox" name="attribute[%d]" value="on" %s />
                </td></tr>'."\n",stripslashes($row["name"]),$row["id"],$row["id"],$row["id"],$checked);
      } elseif ($row["type"] == "checkboxgroup") {
        $userdetailsHTML .= sprintf ('
             <tr><td valign="top" class="dataname">%s</td><td>%s</td>
             </tr>',stripslashes($row["name"]),UserAttributeValueCbGroup($id,$row["id"]));
      } elseif ($row["type"] == "textarea") {
        $userdetailsHTML .= sprintf ('
             <tr><td valign="top" class="dataname">%s</td><td><textarea name="attribute[%d]" rows="10" cols="40" class="wrap virtual">%s</textarea></td>
             </tr>',stripslashes($row["name"]),$row["id"],htmlspecialchars(stripslashes($row["value"])));
      } elseif ($row["type"] == "avatar") {
        $userdetailsHTML .= sprintf ('<tr><td valign="top" class="dataname">%s</td><td>',stripslashes($row["name"]));
        if ($row['value']) {
          $userdetailsHTML .= sprintf('<img src="./?page=avatar&amp;user=%d&amp;avatar=%s" /><br/>',$id,$row['id']);
        }
        $userdetailsHTML .= sprintf ('<input type="file" name="attribute[%d]" /><br/>MAX: %d Kbytes</td>
             </tr>',$row["id"],MAX_AVATAR_SIZE/1024);
      } else {
      if ($row["type"] != "textline" && $row["type"] != "hidden")
        $userdetailsHTML .= sprintf ("<tr><td class='dataname'>%s</td><td>%s</td></tr>\n",stripslashes($row["name"]),UserAttributeValueSelect($id,$row["id"]));
      else
        $userdetailsHTML .= sprintf('<tr><td class="dataname">%s</td><td><input class="attributeinput" type="text" name="attribute[%d]" value="%s" size="30" /></td></tr>'."\n",$row["name"],$row["id"],htmlspecialchars(stripslashes($row["value"])));
      }
    }
  }

  if ($access != "view")
  $userdetailsHTML .=  '<tr><td colspan="2" class="bgwhite"><input class="submit" type="submit" name="change" value="'.$GLOBALS['I18N']->get('Save Changes').'" /></td></tr>';
  $userdetailsHTML .= '</table>';

  if (isBlackListed($user["email"])) {
     $userdetailsHTML .= '<h3>'.$GLOBALS['I18N']->get('Subscriber is blacklisted. No emails will be sent to this email address.').'</h3>';
  }

  $mailinglistsHTML .= "<h3>".$GLOBALS['I18N']->get('Mailinglist membership').":</h3>";
  $mailinglistsHTML .= '<table class="userListing" border="1"><tr>';
  $req = Sql_Query("select * from {$tables["list"]} $subselect_where order by listorder,name");
  $c = 0;
  while ($row = Sql_Fetch_Array($req)) {
    $c++;
    if ($c % 1 == 0)
      $mailinglistsHTML .= '</tr><tr>';
    if (in_array($row["id"],$subscribed)) {
      $bgcol = '#F7E7C2';
      $subs = 'checked="checked"';
    } else {
      $bgcol = '#ffffff';
      $subs = "";
    }
    $mailinglistsHTML .=sprintf ('<td class="tdcheck" bgcolor="%s"><input type="checkbox" name="subscribe[]" value="%d" %s /> %s</td>',
      $bgcol,$row["id"],$subs,stripslashes($row["name"]));
  }
  $mailinglistsHTML .= '</tr>';
  if ($access != "view")
    $mailinglistsHTML .= '<tr><td class="bgwhite"><input class="submit" type="submit" name="change" value="'.$GLOBALS['I18N']->get('Save Changes').'" /></td></tr>';

  $mailinglistsHTML .= '</table>';

  if ($usegroups) {
    $groupsHTML  .= "<h3>".$GLOBALS['I18N']->get('Group Membership').":</h3>";
    $groupsHTML  .= '<table class="userGroup" border="1">';
    $groupsHTML  .= '<tr><td colspan="2"><hr width="50%" /></td></tr>
  <tr><td colspan="2">'.$GLOBALS['I18N']->get('Please select the groups this subscriber is a member of').'</td></tr>
  <tr><td colspan="2">';
    
    if (empty($GLOBALS['config']['usergroup_types'])) {
      
      ## old method, list of checkboxes
    
      $selected_groups = array();
      if ($id) {
        $req = Sql_Query("select groupid from user_group where userid = $id");
        while ($row = Sql_Fetch_Row($req))
          array_push($selected_groups,$row[0]);
      }

      $req = Sql_Query("select * from groups");
      $c = 1;
      while ($row = Sql_Fetch_array($req)) {
        if ($row["name"] != "Everyone") {
          $groupsHTML  .= sprintf ('<i>%s</i><input type="checkbox" name="groups[]" value="%d" %s />&nbsp;&nbsp;',
          $row["name"],$row["id"],in_array($row["id"],$selected_groups)?'checked="checked"':''
              );
        } else {
          $groupsHTML  .=sprintf ('<b>%s</b>&nbsp;&nbsp;<input type="hidden" name="groups[]" value="%d" />',
          $row["name"],$row["id"]
              );
        }
        if ($c % 5 == 0)
          $groupsHTML  .= "<br/>";
        $c++;
      }
    } else {
      $current_groups = array();
      if ($id) {
        $req = Sql_Query("select groupid,type from user_group where userid = $id");
        $groupsHTML  .= '<ol>';
        while ($row = Sql_Fetch_Assoc($req)) {
          ## the config needs to start real types with 1, type index 0 will be considered no-value
          $membership_type = $GLOBALS['config']['usergroup_types'][$row['type']];
          if (empty($membership_type) || empty($row['type'])) {
            # $membership_type = 'undefined'; an entry "undefined of everyone" was showing in the backend
            continue;
          }
          $groupname = groupName($row['groupid']);
          $deleteLink = '';
          if (strtolower($groupname) != 'everyone') {
            $deleteLink =  PageLink2('user&amp;id='.$id.'&amp;delgroup='.$row['groupid'].'&amp;deltype='.$row['type'],'del');
          }
          $groupsHTML  .=sprintf('<li><strong>%s</strong> of <i>%s</i> %s</li>',$membership_type,$groupname,$deleteLink);
        }
        $groupsHTML  .= '</ol>';
      }

      $req = Sql_Query('select * from groups where name != "everyone"');
      $c = 1;
      
      while ($row = Sql_Fetch_array($req)) {
        $groups[$row['id']] = $row['name'];
      }
      
      $groupsHTML  .= '<hr/>Add new group membership:<br/><br/>';
      $groupsHTML  .= '<select name="newgrouptype">';
      foreach ($GLOBALS['config']['usergroup_types'] as $key => $val) {
        $groupsHTML  .=sprintf ('    <option value="%d">%s</option>',$key,$val);
      }
      $groupsHTML  .= '</select>';
      $groupsHTML  .= ' of ';
      $groupsHTML  .= '<select name="newgroup">';
      foreach ($groups as $key => $val) {
        $groupsHTML  .=sprintf ('<option value="%d">%s</option>',$key,$val);
      }
      $groupsHTML  .= '</select>';
    }  

    $groupsHTML  .= '</td></tr>';
    if ($access != "view")
      $groupsHTML  .= '<tr><td><input type="submit" name="change" value="'.$GLOBALS['I18N']->get('Save changes').'" /></td></tr>';
    $groupsHTML  .= '</table>';
  }

print '<div class="tabbed">';
print '<ul>';
print '<li><a href="#details">'.$GLOBALS['I18N']->get('Details').'</a></li>';
print '<li><a href="#lists">'.$GLOBALS['I18N']->get('Lists').'</a></li>';
if ($usegroups) {
  print '<li><a href="#groups">Groups</a></li>';
}
print '</ul>';

$p = new UIPanel('',$userdetailsHTML);
print '<div id="details">'.$p->display().'</div>';

$p = new UIPanel('',$mailinglistsHTML);
print '<div id="lists">'.$p->display().'</div>';

if ($usegroups) {
  $p = new UIPanel($GLOBALS['I18N']->get('Groups'),$groupsHTML);
  print '<div id="groups">'.$p->display().'</div>';
}
print '</div>'; ## end of tabbed



print '</form>';
