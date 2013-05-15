<?php
if (!defined('PHPLISTINIT')) exit;

if (!isset($_SESSION["userselection"]) || !is_array($_SESSION["userselection"]) || $_GET["reset"]) {
  $_SESSION["userselection"] = array();
}
$tables = array(
  "attribute" => "attribute",
  "user_attribute" => "user_attribute",
);
$GLOBALS["table_prefix"] = "phplist_";
if ($_GET["deleterule"]) {
  unset($_SESSION["userselection"]["criterion".$_GET["deleterule"]]);
  Redirect($_GET["page"]."&id=$id&tab=".$_GET["tab"]);
}
$baseurl = PageURL2($_GET["page"].'&id='.$_GET["id"]);

# should move this to common library area
function parseDate($strdate,$format = 'Y-m-d') {
  # parse a string date into a date
  $strdate = trim($strdate);
  if (strlen($strdate) < 6) {
    $newvalue = 0;
  } elseif  (preg_match("#(\d{2,2}).(\d{2,2}).(\d{4,4})#",$strdate,$regs)) {
    $newvalue = mktime(0,0,0,$regs[2],$regs[1],$regs[3]);
  } elseif (preg_match("#(\d{4,4}).(\d{2,2}).(\d{2,2})#",$value,$regs)) {
    $newvalue = mktime(0,0,0,$regs[3],$regs[1],$regs[1]);
  } elseif (preg_match("#(\d{2,2}).(\w{3,3}).(\d{2,4})#",$value,$regs)) {
    $newvalue = strtotime($value);
  } elseif (preg_match("#(\d{2,4}).(\w{3,3}).(\d{2,2})#",$value,$regs)) {
    $newvalue = strtotime($value);
  } else {
    $newvalue = strtotime($value);
    if ($newvalue < 0) {
      $newvalue = 0;
    }
  }
  if ($newvalue) {
    return date($format,$newvalue);
  } else {
    return "";
  }
}

# new criteria system, add one by one:
if ($_POST["criteria_attribute"]) {
  $operator = $_POST["criteria_operator"];
  if (is_array($_POST["criteria_values"])) {
    $values = join(", ",$_POST["criteria_values"]);
  } else {
    $values = $_POST["criteria_values"];
  }
  foreach ($_POST["attribute_names"] as $key => $val) {
    $att_names[$key] = $val;
  }
  $newcriterion = array(
    "attribute" => sprintf('%d',$_POST["criteria_attribute"]),
    "attribute_name" => $att_names[$_POST["criteria_attribute"]],
    "operator" => $operator,
    "values" => $values,
  );
  # find out what number we are
  $num = $_SESSION["userselection"]["num"];
  if (!$num) $num = 1;
  # store this one
#    print $att_names[$_POST["criteria_attribute"]];
#    print $_POST["attribute_names[".$_POST["criteria_attribute"]."]"];
  print '<p class="information">Adding '.$newcriterion["attribute_name"]." ".$newcriterion["operator"]." ".$newcriterion["values"]."</p>";
  $_SESSION["userselection"]["criterion$num"] = delimited($newcriterion);
  # increase number
  $_SESSION["userselection"]["num"]++;
}
if (isset($_POST["criteria_match"])) {
  $_SESSION["criteria_overall_operator"] = $_POST["criteria_match"];
}

$num = sprintf('%d',$_SESSION["userselection"]["num"]);
#print '<br/>'.$num . " criteria already defined";
$ls = new WebblerListing("Existing criteria");
$used_attributes = array();
$delete_base = sprintf('%s&amp;id=%d&amp;tab=%s',$_GET["page"],$_GET["id"],$_GET["tab"]);
$tc = 0; # table counter
$mainoperator = $_SESSION["criteria_overall_operator"] == "all"? ' and ':' or ';

for ($i = 1; $i<=$num;$i++) {
  $crit_data = parseDelimitedData($_SESSION["userselection"]["criterion$i"]);
  if ($crit_data["attribute"]) {
    array_push($used_attributes,$crit_data["attribute"]);
    $ls->addElement('<!--'.$crit_data["attribute"].'-->'.$crit_data["attribute_name"]);
    $ls->addColumn('<!--'.$crit_data["attribute"].'-->'.$crit_data["attribute_name"],"operator",$crit_data["operator"]);
    $ls->addColumn('<!--'.$crit_data["attribute"].'-->'.$crit_data["attribute_name"],"values",$crit_data["values"]);
    $ls->addColumn('<!--'.$crit_data["attribute"].'-->'.$crit_data["attribute_name"],"remove",PageLink2($delete_base."&amp;deleterule=".$i,"Remove"));
    $attribute = $_POST["criteria"][$i];
    
    # hmm, rather get is some other way, this is a bit unnecessary
    $type = Sql_Fetch_Row_Query("select type from {$tables["attribute"]} where id = ".$crit_data["attribute"]);
    $operator = "";
    switch($type[0]) {
      case "checkboxgroup":
        if ($tc) {
          $where_clause .= " $mainoperator ";
          $select_clause .= " left join $tables[user_attribute] as table$tc on table0.userid = table$tc.userid ";
        } else {
          $select_clause = "table$tc.userid from $tables[user_attribute] as table$tc ";
        }

        $where_clause .= " ( table$tc.attributeid = ".$crit_data["attribute"]." and (";
        if ($crit_data["operator"] == "is") {
          $operator = ' or ';
          $compare = ' > ';
        } else {
          $operator = ' and ';
          $compare = ' <  ';
        }
        foreach (explode(",",$crit_data["values"]) as $val) {
          if (isset($or_clause)) {
            $or_clause .= " $operator ";
          }
          $or_clause .= "find_in_set('$val',table$tc.value) $compare 0";
        }
        $where_clause .= $or_clause . ") ) ";
        break;
      case "checkbox":
        $value = $crit_data["values"][0];

        if ($tc) {
          $where_clause .= " $mainoperator ";
          $select_clause .= " left join $tables[user_attribute] as table$tc on table0.userid = table$tc.userid ";
        } else {
          $select_clause = "table$tc.userid from $tables[user_attribute] as table$tc";
        }

        $where_clause .= " ( table$tc.attributeid = ".$crit_data["attribute"]." and ";
        if ($crit_data["operator"] == "isnot") {
          $where_clause .= ' not ';
        }
        if ($value) {
          $where_clause .= "( length(table$tc.value) and table$tc.value != \"off\" and table$tc.value != \"0\") ";
        } else {
          $where_clause .= "( table$tc.value = \"\" or table$tc.value = \"0\" or table$tc.value = \"off\") ";
        }
        $where_clause .= ' ) ';
        break;
      case "date":
        $date_value = parseDate($crit_data["values"]);
        if (!$date_value) {
          break;
        }
        if (isset($where_clause)) {
          $where_clause .= " $mainoperator ";
          $select_clause .= " left join $tables[user_attribute] as table$tc on table0.userid = table$tc.userid ";
        } else {
          $select_clause = " table$tc.userid from $tables[user_attribute] as table$tc ";
        }

        $where_clause .= ' ( table'.$tc.'.attributeid = '.$crit_data["attribute"].' and table'.$tc.'.value != "" and table'.$tc.'.value ';
      
        switch ($crit_data["operator"]) {
          case "is":
            $where_clause .= ' = "'.$date_value . '" )';break;
          case "isnot":
            $where_clause .= ' != "'.$date_value . '" )';break;
          case "isbefore":
            $where_clause .= ' <= "'.$date_value . '" )';break;
          case "isafter":
            $where_clause .= ' >= "'.$date_value . '" )';break;
        }
#        $where_clause .= " )";
        break;
      default:
        if (isset($where_clause)) {
          $where_clause .= " $mainoperator ";
          $select_clause .= " left join $tables[user_attribute] as table$tc on table0.userid = table$tc.userid ";
        } else {
          $select_clause = " table$tc.userid from $tables[user_attribute] as table$tc ";
        }

        $where_clause .= " ( table$tc.attributeid = ".$crit_data["attribute"]." and table$tc.value ";
        if ($crit_data["operator"] == "isnot") {
          $where_clause .= ' not in (';
        } else {
          $where_clause .= ' in (';
        }
        $where_clause .= $crit_data["values"] . ") )";
    }
    $tc++;
  }
}
if ($num) {
  $userselection_query = "select $select_clause where $where_clause";
#    $count_query = addslashes($count_query);
  if ($_GET["calculate"]) {
    ob_end_flush();
    print "<h3>$userselection_query</h3>";
    print '<p class="information">Calculating ...';
    flush();
    
    $req = Sql_Query($userselection_query);
    print '.. '.Sql_Num_Rows($req). " users apply</p>";
  }

  $ls->addButton("Calculate",$baseurl.'&amp;tab='.$_GET["tab"].'&amp;calculate="1"');
  $ls->addButton("Reset",$baseurl.'&amp;tab='.$_GET["tab"].'&amp;reset="1"');
  $existing_criteria = $ls->display();
}


$att_js = '
<script language="Javascript" type="text/javascript">
  var values = Array();
  var operators = Array();
  var value_divs = Array();
  var value_default = Array();
';

if (sizeof($used_attributes)) {
  $already_used = ' and id not in ('.join(',',$used_attributes).')';
} else {
  $already_used = "";
}
$attreq = Sql_Query(sprintf('select * from %s where type in ("select","radio","date","checkboxgroup","checkbox") %s',$tables["attribute"],$already_used));
while ($att = Sql_Fetch_array($attreq)) {
  $att_drop .= sprintf('<option value="%d" %s>%s</option>',
    $att["id"],"",$att["name"]);
  $num = Sql_Affected_Rows();
  switch ($att["type"]) {
    case "select":case "radio":case "checkboxgroup":
      $att_js .= sprintf('value_divs[%d] = "criteria_values_select";'."\n",$att["id"]);
      $att_js .= sprintf('value_default[%d] = "";'."\n",$att["id"]);
      $value_req = Sql_Query(sprintf('select * from %s order by listorder,name',$GLOBALS["table_prefix"]."listattr_".$att["tablename"]));
      $num = Sql_Num_Rows($value_req);
      $att_js .= sprintf('values[%d] = new Array(%d);'."\n",$att["id"],$num+1);
      #$att_js .= sprintf('values[%d][0] =  new Option("[choose]","0",false,true);'."\n",$att["id"]);
      $c = 0;
      while ($value = Sql_Fetch_Array($value_req)) {
        $att_js .= sprintf('values[%d][%d] =  new Option("%s","%d",false,false);'."\n",$att["id"],$c,$value["name"],$value["id"]);
        $c++;
      }
      $att_js .= sprintf('operators[%d] = new Array(2);'."\n",$att["id"]);
      $att_js .= sprintf('operators[%d][0] =  new Option("IS","is",false,true);'."\n",$att["id"]);
      $att_js .= sprintf('operators[%d][1] =  new Option("IS NOT","isnot",false,true);'."\n",$att["id"]);
      break;
    case "checkbox":
      $att_js .= sprintf('value_divs[%d] = "criteria_values_select";'."\n",$att["id"]);
      $att_js .= sprintf('value_default[%d] = "";'."\n",$att["id"]);
      $att_js .= sprintf('values[%d] = new Array(%d);'."\n",$att["id"],2);
      $att_js .= sprintf('values[%d][0] =  new Option("%s",0,false,true);'."\n",$att["id"],"Unchecked");
      $att_js .= sprintf('values[%d][1] =  new Option("%s",1,false,true);'."\n",$att["id"],"Checked");
      $att_js .= sprintf('operators[%d] = new Array(2);'."\n",$att["id"]);
      $att_js .= sprintf('operators[%d][0] =  new Option("IS","is",false,true);'."\n",$att["id"]);
      $att_js .= sprintf('operators[%d][1] =  new Option("IS NOT","isnot",false,true);'."\n",$att["id"]);
      break;
    case "date":
      $att_js .= sprintf('value_divs[%d] = "criteria_values_text";'."\n",$att["id"]);
      $att_js .= sprintf('value_default[%d] = "dd-mm-yyyy";'."\n",$att["id"]);
      $att_js .= sprintf('values[%d] = new Array(%d);'."\n",$att["id"],1);
      $att_js .= sprintf('values[%d][%d] =  new Option("%s","%d",false,false);'."\n",$att["id"],$c,"Date","dd-mm-yyyy");
      $att_js .= sprintf('operators[%d] = new Array(4);'."\n",$att["id"]);
      $att_js .= sprintf('operators[%d][0] =  new Option("IS","is",false,true);'."\n",$att["id"]);
      $att_js .= sprintf('operators[%d][1] =  new Option("IS NOT","isnot",false,true);'."\n",$att["id"]);
      $att_js .= sprintf('operators[%d][2] =  new Option("IS BEFORE","isbefore",false,true);'."\n",$att["id"]);
      $att_js .= sprintf('operators[%d][3] =  new Option("IS AFTER","isafter",false,true);'."\n",$att["id"]);
  }
}
$att_js .= '

var browser = navigator.appName.substring ( 0, 9 );
var warned = browser != "Microsoft";

function findEl(name) {
  var div;
  if (document.getElementById){
    div = document.getElementById(name);
  } else if (document.all){
    div = document.all[name];
  }
  return div;
}

function changeDropDowns() {
  var choice = document.userselection.criteria_attribute.options[document.userselection.criteria_attribute.selectedIndex].value;
  if (choice == "")
    return;
  if (!warned) {
    alert("Warning, this functionality is buggy and unreliable with IE.\nIt will be better to use Mozilla, Firefox or Opera");
    warned = 1;
  }
  var value_el_select = findEl("criteria_values_select");
  var value_el_text = findEl("criteria_values_text");
  for (i=0;i<value_el_select.length;) {
    value_el_select.options[i] = null;
  }
  for (i=0;i<values[choice].length;i++) {
    value_el_select.options[i] = values[choice][i];
  }
  value_el_select.selectedIndex = 0;
  value_el_text.value = value_default[choice];

  for (i=0;i<document.userselection.criteria_operator.length;) {
    document.userselection.criteria_operator.options[i] = null;
  }
  for (i=0;i<operators[choice].length;i++) {
    document.userselection.criteria_operator.options[i] = operators[choice][i];
  }
  document.userselection.criteria_operator.selectedIndex = 0;
  var div1 = findEl("criteria_values_select");
  var div2 = findEl("criteria_values_text");
  var div3 = findEl(value_divs[choice]);
  div1.style.visibility = "hidden";
  div2.style.visibility = "hidden";
  div3.style.visibility = "visible";

}
</script>

';

$att_drop = '<select name="criteria_attribute" onchange="changeDropDowns()" class="criteria_element" >';
$att_drop .= '<option value="">[select attribute]</option>';
$att_names = '';# to remember them later
$attreq = Sql_Query(sprintf('select * from %s where type in ("select","radio","date","checkboxgroup","checkbox") %s',$tables["attribute"],$already_used));
while ($att = Sql_Fetch_array($attreq)) {
  $att_drop .= sprintf('<option value="%d" %s>%s</option>',
    $att["id"],"",substr(stripslashes($att["name"]),0,30).' ('.$att["type"].')');
  $att_names .= sprintf('<input type=hidden name="attribute_names[%d]" value="%s" />',$att["id"],stripslashes($att["name"]));
}
$att_drop .= '</select>'.$att_names;

$operator_drop = '
  <select name="criteria_operator" class="criteria_element" >
  <option value="is">IS</option>
  <option value="isnot">IS NOT</option>
  <option value="gt">IS GREATER THAN</option>
  <option value="lt">IS LESS THAN</option>
  <option value="before">IS BEFORE</option>
  <option value="after">IS AFTER</option>
</select>
';

$values_drop = '
<style type="text/css">
#criteria_values_select {
  visibility : hidden;
  background-color: #ffffff;
}
#criteria_values_select > option {
  background-color: #ffffff;
}
#criteria_values_text {
  visibility : hidden;
}
span.values_span {
  vertical-align: top;
  display: block;
}
input.criteria_element {
  vertical-align: top;
}
select.criteria_element {
  vertical-align: top;
}

</style>';
$values_drop .= '<span id="values_span" class="values_span">';
$values_drop .= '<input class="criteria_element" name="criteria_values" id="criteria_values_text" size="15" type="text"/>';
#  $values_drop .= '</span>';
#  $values_drop .= '<span id="values_select">';
$values_drop .= '<select class="criteria_element" name="criteria_values[]" id="criteria_values_select" multiple size="10"></select>';
$values_drop .= '</span>';

$existing_overall_operator = $_SESSION["criteria_overall_operator"] == "any" ? "any":"all";
$criteria_overall_operator =
  sprintf('Match all of these rules <input type="radio" name="criteria_match" value="all" %s />
    Match any of these rules <input type="radio" name="criteria_match" value="any" %s />',
    $existing_overall_operator == "all"? 'checked="checked"':'',$existing_overall_operator == "any"? 'checked="checked"':'');

$criteria_styles = '
<style type="text/css">

div.criteria_container {
  /*border: 1px solid black;
  background-color: #ffeeee;*/
  width: 100%;
  z-index: 8;
}
span.criteria_element {
  vertical-align: top;
  display: inline;
}
</style>';



$criteria_content = $criteria_overall_operator.$existing_criteria.$criteria_styles.$att_js.
'<div class="criteria_container">'.
'<span class="criteria_element">'.$att_drop.'</span>'.
'<span class="criteria_element">'.$operator_drop.'</span>'.
'<span class="criteria_element">'.$values_drop.'</span>'.
'<span class="criteria_element"><input type="submit" name="save" value="Add Criterion" /></span>';
'</div>';

print '<form name="userselection" method="post">';
print $criteria_content;
print '</form>';

