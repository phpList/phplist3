<?php
require_once dirname(__FILE__).'/accesscheck.php';
if (!ALLOW_IMPORT) {
  print '<p class="information">'.$GLOBALS['I18N']->get('import is not available').'</p>';
  return;
}

# import from a different PHPlist installation

if ($require_login && !isSuperUser()) {
  $access = accessLevel("import4");
  if ($access == "owner")
    $subselect = " where owner = ".$_SESSION["logindetails"]["id"];
  elseif ($access == "all")
    $subselect = "";
  elseif ($access == "none")
    $subselect = " where id = 0";
}

function connectLocal() {
  $database_connection = Sql_Connect(
    $GLOBALS["database_host"],
    $GLOBALS["database_user"],
    $GLOBALS["database_password"],
    $GLOBALS["database_name"]);
   return $database_connection;
}
function connectRemote() {
  return Sql_Connect($_POST["remote_host"],
  $_POST["remote_user"],
  $_POST["remote_password"],
  $_POST["remote_database"]);
}

$result = Sql_query("SELECT id,name FROM ".$tables["list"]." $subselect ORDER BY listorder");
while ($row = Sql_fetch_array($result)) {
  $available_lists[$row["id"]] = $row["name"];
  $some = 1;
}
if (!$some)
 # @@@@ not sure about this one:
 echo $GLOBALS['I18N']->get('No lists available').', '.PageLink2("editlist",$GLOBALS['I18N']->get('add_list'));
#foreach ($_POST as $key => $val) {
#  print "$key => $val<br/>";
#}

if (!$_POST["remote_host"] ||
  !$_POST["remote_user"] ||
  !$_POST["remote_password"] || !$_POST["remote_database"]) {
  printf( '
  <p class="information">'.$GLOBALS['I18N']->get('remote_server').'</p>
  <form method="post">
  <table class="importForm">
  <tr><td>'.$GLOBALS['I18N']->get('server').'</td><td><input type="text" name="remote_host" value="%s" size="30"></td></tr>
  <tr><td>'.$GLOBALS['I18N']->get('user').'</td><td><input type="text" name="remote_user" value="%s" size="30"></td></tr>
  <tr><td>'.$GLOBALS['I18N']->get('passwd').'</td><td><input type="text" name="remote_password" value="%s" size="30"></td></tr>
  <tr><td>'.$GLOBALS['I18N']->get('database').'</td><td><input type="text" name="remote_database" value="%s" size="30"></td></tr>
  <tr><td>'.$GLOBALS['I18N']->get('table_prefix').'</td><td><input type="text" name="remote_prefix" value="%s" size="30"></td></tr>
  <tr><td>'.$GLOBALS['I18N']->get('usertable_prefix').'</td><td><input type="text" name="remote_userprefix" value="%s" size="30"></td></tr>
  ',$_POST["remote_server"],$_POST["remote_user"],$_POST["remote_password"],
  $_POST["remote_database"],$_POST["remote_prefix"],$_POST["remote_userprefix"]);
  $c = 0;
  print '<tr><td colspan="2">';
  if (sizeof($available_lists) > 1)
    print $GLOBALS['I18N']->get('select_lists').'<br/>';
  print '<ul>';
  foreach ($available_lists as $index => $name) {
    printf('<li><input type="checkbox" name="lists[%d]" value="%d" %s>%s</li>',
      $c,$index,is_array($_POST["lists"]) && in_array($index,array_values($_POST["lists"]))?"checked":"",$name);
    $c++;
  }
  printf('
  <li><input type="checkbox" name="copyremotelists" value="yes" %s>'.$GLOBALS['I18N']->get('copy_lists').'</li>
  </ul></td></tr>
<tr><td>'.$GLOBALS['I18N']->get('users_as_html').'</td><td><input type="checkbox" name="markhtml" value="yes" %s></td></tr>
<tr><td colspan="2">'.$GLOBALS['I18N']->get('info_overwrite_existing').'</td></tr>
<tr><td>'.$GLOBALS['I18N']->get('overwrite_existing').'</td><td><input type="checkbox" name="overwrite" value="yes" %s></td></tr>
  <tr><td colspan="2"><p class="submit"><input type="submit" value="'.$GLOBALS['I18N']->get('continue').'"></p></td></tr>
  </table></form>
  ',$_POST["copyremotelists"] == "yes"?"checked":"",$_POST["markhtml"] == "yes"?"checked":"",$_POST["overwrite"] == "yes"?"checked":""
  );
} else {
  set_time_limit(600);
  ob_end_flush();
  include_once("structure.php");
  print $GLOBALS['I18N']->get('connecting_remote')."<br/>";
  flush();
  $remote = connectRemote();
  if (!$remote) {
    Fatal_Error($GLOBALS['I18N']->get('cant_connect'));
    return;
  }
  $remote_tables = array(
    "user" => $_POST["remote_userprefix"] . "user",
    "list" => $_POST["remote_prefix"] . "list",
    "listuser" => $_POST["remote_prefix"] . "listuser",
    "attribute" => $_POST["remote_userprefix"] . "attribute",
    "user_attribute" => $_POST["remote_userprefix"] . "user_attribute",
    "config" => $_POST["remote_prefix"] . "config",
  );
  print $GLOBALS['I18N']->get('getting_data').$_POST["remote_database"]."@".$_POST["remote_host"]."<br/>";

  $query = "select value from {$remote_tables["config"]} where item = ?";
  $rs = Sql_Query_Params($query, array('version'));
  $version = Sql_Fetch_Row($rs);
  print $GLOBALS['I18N']->get('remote_version')." $version[0]<br/>\n";
  $usercnt = Sql_Fetch_Row_Query("select count(*) from {$remote_tables["user"]}");
  print $GLOBALS['I18N']->get('remote_has')." $usercnt[0] ".$GLOBALS['I18N']->get('users')."<br/>";
  if (!$usercnt[0]) {
    Fatal_Error($GLOBALS['I18N']->get('no_users_to_copy'));
    return;
  }
  $totalusers = $usercnt[0];
  $listcnt = Sql_Fetch_Row_Query("select count(*) from {$remote_tables["list"]}");
  print $GLOBALS['I18N']->get('remote_has')." $listcnt[0] ".$GLOBALS['I18N']->get('lists')."<br/>";

  flush();
  print '<h3>'.$GLOBALS['I18N']->get('copying_lists').'</h3>';
  # first copy the lists across
  $listmap = array();
  $remote_lists = array();
  $lists_req = Sql_Query("select * from {$remote_tables["list"]}");
  while ($row = Sql_Fetch_Array($lists_req)) {
    array_push($remote_lists,$row);
  }

  connectLocal();
  foreach ($remote_lists as $list) {
    $query = sprintf('select id from %s where name = ?', $tables["list"]);
    $rs = Sql_Query_Params($query, array($list["name"]));
    $localid_req = Sql_Fetch_Row($rs);
    if ($localid_req[0]) {
      $listmap[$list["id"]] = $localid_req[0];
       print $GLOBALS['I18N']->get('list').' '.$list["name"] .$GLOBALS['I18N']->get('exists_locally')." <br/>\n";
    } elseif ($_POST["copyremotelists"]) {
      # BUG  This query is probably busted.  As they say in math,
      # this one is left as an exercise for the reader.
      $query = "";
      foreach ($DBstruct["list"] as $colname => $colspec) {
        if ($colname != "id" && $colname != "index" && $colname != "unique" && $colname != "primary key") {
          $query .= sprintf('%s = "%s",',$colname,addslashes($list[$colname]));
         }
       }
      $query = substr($query,0,-1);
       print $GLOBALS['I18N']->get('list')." ".$list["name"] .$GLOBALS['I18N']->get('created_locally')." <br/>\n";
      Sql_Query("insert into {$tables["list"]} set $query");
      $listmap[$list["id"]] = Sql_Insert_Id($tables['list'], 'id');
    } else {
       print $GLOBALS['I18N']->get('remote_list')." ".$list["name"] .$GLOBALS['I18N']->get('not_created')." <br/>\n";
    }
  }

  connectRemote();
  print '<h3>'.$GLOBALS['I18N']->get('copying_attribs').'</h3>';
  # now copy the attributes
  $attributemap = array();
  $remote_atts = array();
  $att_req = Sql_Query("select * from {$remote_tables["attribute"]}");
  while ($row = Sql_Fetch_Array($att_req)) {
    array_push($remote_atts,$row);
  }

  connectLocal();
  foreach ($remote_atts as $att) {
    $query = sprintf('select id from %s where name = ?', $tables["attribute"]);
    $rs = Sql_Query_Params($query, array($att["name"]));
    $localid_req = Sql_Fetch_Row($rs);
    if ($localid_req[0]) {
      $attributemap[$att["id"]] = $localid_req[0];
       print $GLOBALS['I18N']->get('attrib')." ".$att["name"] .$GLOBALS['I18N']->get('exists_locally')." <br/>\n";
    } else {
      $query = "";
      foreach ($DBstruct["attribute"] as $colname => $colspec) {
        if ($colname != "id" && $colname != "index" && $colname != "unique" && $colname != "primary key") {
          # BUG              here V
          $query .= sprintf('%s = "%s",',$colname,addslashes($att[$colname]));
         }
       }
      $query = substr($query,0,-1);#
       print $GLOBALS['I18N']->get('attrib')." ".$att["name"].$GLOBALS['I18N']->get('created_locally')." <br/>\n";
      Sql_Query("insert into {$tables["attribute"]} set $query");
      $attributemap[$att["id"]] = Sql_Insert_Id($tables['attribute'], 'id');
      if ($att["type"] == "select" || $att["type"] == "radio" || $att["type"] == "checkboxgroup") {
        $query = "create table if not exists $table_prefix"."listattr_".$att["tablename"]."
        (id integer not null primary key auto_increment,
        name varchar(255) unique,listorder integer default 0)";
        Sql_Query($query,0);
        connectRemote();
        $attvalue_req = Sql_Query("select id,name,listorder from ".$_POST["remote_prefix"]."listattr_".$att["tablename"]);
        $values = array();
        while ($value = Sql_Fetch_Array($attvalue_req)) {
          array_push($values,$value);
        }
        connectLocal();
        foreach ($values as $value) {
          # Do they all have the same primary key?
          $tn = $table_prefix . 'listattr_' . $att['tablename'];
          Sql_Replace($tn, array('name' => $value['name'], 'id' => $value['id'], 'listorder' => $value['listorder']), array('id'));
        }
      }
    }
  }

  print '<h3>'.$GLOBALS['I18N']->get('copying_users').'</h3>';
  # copy the users
  $usercnt = 0;
  $existcnt = 0;
  $newcnt = 0;
  while ($usercnt < $totalusers) {
    set_time_limit(60);
    connectRemote();
    $req = Sql_Query("select * from {$remote_tables["user"]} limit 1 offset $usercnt");
    $user = Sql_Fetch_Array($req);
    $usercnt++;
    $new = 0;
    if ($usercnt % 20 == 0) {
      print "$usercnt / $totalusers<br/>";
      flush();
    }
    connectLocal();
    $query = sprintf('select id from %s where email = ?', $tables["user"]);
    $rs = Sql_Query_Params($query, array($user['email']));
    $exists = Sql_Fetch_Row($rs);
    if ($exists[0]) {
      $existcnt++;
  #    print $user["email"] .$GLOBALS['I18N']->get('exists_locally')." ..";
      if ($_POST["overwrite"]) {
  #      print " .. ".$GLOBALS['I18N']->get('overwrite_local')."<br/>";
        # BUG
        $query = "replace into ".$tables["user"] . " set id = ".$exists[0].", ";
      } else {
  #      print " .. ".$GLOBALS['I18N']->get('keep_local')."<br/>";
      }
      $userid = $exists[0];
    } else {
      $newcnt++;
      $new = 1;
  #    print $user["email"] .$GLOBALS['I18N']->get('new_user')."<br/>";
      $query = "insert into ".$tables["user"]. " set ";
    }
    if ($query) {
      foreach ($DBstruct["user"] as $colname => $colspec) {
        if ($colname != "id" && $colname != "index" && $colname != "unique" && $colname != "primary key") {
          $query .= sprintf('%s = "%s",',$colname,addslashes($user[$colname]));
         }
       }
      $query = substr($query,0,-1);
      #print $query . "<br/>";
      Sql_Query($query);
      $userid = Sql_Insert_Id($tables['user'], 'id');
    }
    if ($userid && $_POST["markhtml"]) {
      $query = "update {$tables["user"]} set htmlemail = 1 where id = ?";
      Sql_Query_Params($query, array($userid));
    }

    if ($new || (!$new && $_POST["overwrite"])) {
      # now check for attributes and list membership
      connectRemote();
      $useratt = array();
      $query = "select * from {$remote_tables["user_attribute"]} ua, {$remote_tables["attribute"]} a where ua.attributeid = a.id and ua.userid = ?";
      $req = Sql_Query_Params($query, array($user[0]));
      while ($att = Sql_Fetch_Array($req)) {
        $value = "";
        switch ($att["type"]) {
          case "select":
          case "radio":
            $query = sprintf('select name from %slistattr_%s where id = ?',
              $_POST['remote_prefix'], $att['tablename']);
            $rs = Sql_Query_Params($query, array($att['value']));
            $valreq = Sql_Fetch_Row($rs);
            $value = $valreq[0];
            break;
          case "checkboxgroup":
            $valreq = Sql_Query(sprintf('select name from %slistattr_%s where id in (%s)',
              $_POST["remote_prefix"],$att["tablename"],$att["value"]));
            while ($vals = Sql_fetch_Row($valreq)) {
              $value .= $vals[0].',';
            }
            break;
        }
        $att["displayvalue"] = $value;
        array_push($useratt,$att);
      }
      $userlists = array();
      $userlists = array_merge($_POST["lists"],$userlists);
      if ($_POST["copyremotelists"]) {
        $query = "select * from {$remote_tables["listuser"]} lu, {$remote_tables["list"]} l where lu.listid = l.id and lu.userid = ?";
        $req = Sql_Query_Params($query, array($user[0]));
        while ($list = Sql_Fetch_Array($req)) {
        #  print $list["name"]."<br/>";
          array_push($userlists,$list);
        }
      }
      connectLocal();
      foreach ($useratt as $att) {
        $localattid = $attributemap[$att["attributeid"]];
        if (!localattid) {
          print $GLOBALS['I18N']->get('no_mapped_attrib')." ".$att["name"]."<br/>";
        } else {
          $query = "select tablename from {$tables["attribute"]} where id = ?";
          $rs = Sql_Query_Params($query, array($localattid));
          $tname = Sql_Fetch_Row($rs);
          switch ($att["type"]) {
            case "select":
            case "radio":
              $query = sprintf('select id from %slistattr_%s where name = ?', $table_prefix,$tname[0]);
              $rs = Sql_Query_Params($query, array($att["displayvalue"]));
              $valueid = Sql_Fetch_Row($rs);
              if (!$valueid[0]) {
                $tn = $table_prefix . 'listattr_' . $tname[0];
                $query = sprintf('insert into %s set name = ?', $tn);
                Sql_Query_Params($query, array($att["displayvalue"]));
                $att["value"] = Sql_Insert_Id($tn, 'id');
              } else {
                $att["value"] = $valueid[0];
              }
              break;
            case "checkboxgroup":
              $vals = explode(",",$att["displayvalue"]);
              array_pop($vals);
              $att["value"] = "";
              foreach ($vals as $val) {
                $query = sprintf('select id from %slistattr_%s where name = ?',
                  $table_prefix, $tname[0]);
                $rs = Sql_Query_Params($query, array($val));
                $valueid = Sql_Fetch_Row($rs);
                if (!$valueid[0]) {
                  $tn = $table_prefix . 'listattr_' . $tname[0];
                  $query = sprintf('insert into %s set name = ?', $tn);
                  Sql_Query_Params($query, array($val));
                  $att["value"] .= Sql_Insert_Id($tn, 'id').',';
                } else {
                  $att["value"] .= $valueid[0].",";
                }
              }
              $att["value"] = substr($att["value"],0,-1);
              break;
          }
          if ($att["value"]) {
            Sql_Replace($tables["user_attribute"], array('attributeid' => $localattid, 'userid' => $userid, 'value' => $att['value']), array('attributeid', 'userid'));
          }
        }
      }
    }
    if (is_array($userlists))
    foreach ($userlists as $list) {
      if ($listmap[$list["listid"]]) {
        Sql_Replace($tables["listuser"], array('listid' => $listmap[$list["listid"]], 'userid' => $userid), array('userid', 'listid'));
       } else {
        print $GLOBALS['I18N']->get('no_local_list')." ".$list["name"]."<br/>";
      }
    }
  }
  print "$totalusers / $totalusers<br/>";
  flush();
 # @@@@ Not sure about this one:
   printf('%s %d %s %s %d %s<br/>',$GLOBALS['I18N']->get('Done'),$newcnt,
   $GLOBALS['I18N']->get('new users'),
   $GLOBALS['I18N']->get('and'),
   $existcnt,$GLOBALS['I18N']->get('existing users'));
}
?>


