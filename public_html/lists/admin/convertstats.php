<?php
## 
if (!$GLOBALS["commandline"]) {
  @ob_end_flush();
  print '<p class="information">'.$GLOBALS['I18N']->get('Hint: this page also works from commandline').'</p>';
} else {
  @ob_end_clean();
  print ClineSignature();
  ## when on cl, doit immediately
  $_GET['doit'] = 'yes';
  ob_start();
}

function output ($message) {
  if ($GLOBALS["commandline"]) {
    @ob_end_clean();
    print strip_tags($message) . "\n";
    ob_start();
  } else {
    print $message."\n";
    flushbuffer();
    flush();
  }
  flush();
}

function flushbuffer() {
  for ($i = 0;$i<10000;$i++) {
    print " \n";
  }
  flush();
}

include dirname(__FILE__).'/structure.php';
set_time_limit(60000);
if (!Sql_Table_exists($GLOBALS['tables']['linktrack_forward']) ||
!Sql_Table_exists($GLOBALS['tables']['linktrack_ml']) ||
!Sql_Table_exists($GLOBALS['tables']['linktrack_uml_click'])
) {
  output("creating tables");
  Sql_Drop_Table($GLOBALS['tables']['linktrack_forward']);
  Sql_Drop_Table($GLOBALS['tables']['linktrack_ml']);
  Sql_Drop_Table($GLOBALS['tables']['linktrack_uml_click']);
  
  Sql_Create_Table($GLOBALS['tables']['linktrack_ml'],$DBstruct['linktrack_ml']);
  Sql_Create_Table($GLOBALS['tables']['linktrack_forward'],$DBstruct['linktrack_forward']);
  Sql_Create_Table($GLOBALS['tables']['linktrack_uml_click'],$DBstruct['linktrack_uml_click']);
  output("creating tables done");
}

$num = Sql_Fetch_Row_Query(sprintf('select count(*) from %s',$GLOBALS['tables']['linktrack']));
output("$num[0] entries still to convert");

$c = 0;
output("converting data<br/>");
$req = Sql_Query(sprintf('select * from %s limit 10000',$GLOBALS['tables']['linktrack']));
$total = Sql_Affected_Rows();
while ($row = Sql_Fetch_Array($req)) {
  $exists = Sql_Fetch_Row_Query(sprintf('select id from %s where url = "%s"',$GLOBALS['tables']['linktrack_forward'],$row['url']));
  if (!$exists[0]) {
    $personalise = preg_match('/uid=/',$row['forward']);
    Sql_Query(sprintf('insert into %s (url,personalise) values("%s",%d)',$GLOBALS['tables']['linktrack_forward'],$row['url'],$personalise));
    $fwdid = Sql_Insert_Id($GLOBALS['tables']['linktrack_forward'], 'id');
  } else {
    $fwdid = $exists[0];
  }
  set_time_limit(600);
  $messageid = $row['messageid'];
  $userid = $row['userid'];

#  Sql_Query(sprintf('update %s set forwardid = %d where linkid = %d',$GLOBALS['tables']['linktrack'],$fwdid,$row['linkid']));
  $ml = Sql_Fetch_Array_Query(sprintf('select * from %s where messageid = %d and forwardid = %d',
    $GLOBALS['tables']['linktrack_ml'],$messageid,$fwdid));

  if (empty($ml['messageid'])) {
    Sql_query(sprintf('insert into %s set total = 0,forwardid = %d,messageid = %d',
      $GLOBALS['tables']['linktrack_ml'],$fwdid,$messageid));
  } 
  Sql_Query(sprintf('update %s set total = total + 1 where forwardid = %d and messageid = %d',
    $GLOBALS['tables']['linktrack_ml'],$fwdid,$messageid));

  if (!empty($row['firstclick'])) {
    $msgtype_req = Sql_Fetch_Row_Query(sprintf('select data from %s where name = "Message Type" and linkid = %d and userid= %d and messageid = %d',$GLOBALS['tables']['linktrack_userclick'],$row['linkid'],$row['userid'],$row['messageid']));
    if ($msgtype_req[0] == 'HTML') {
      $msgtype = 'H';
    } elseif ($msgtype_req[0] == 'Text') {
      $msgtype = 'T';
    } else {
      $msgtype = '';
    }
    if ($msgtype == 'H') {
      Sql_Query(sprintf('update %s set clicked = clicked + 1, htmlclicked = htmlclicked + 1, firstclick =  "%s", latestclick = "%s" where forwardid = %d and messageid = %d',$GLOBALS['tables']['linktrack_ml'],$row['firstclick'],$row['latestclick'],$fwdid,$messageid));
    } elseif ($msgtype == 'T') {
      Sql_Query(sprintf('update %s set clicked = clicked + 1, textclicked = textclicked + 1, firstclick ="%s", latestclick = "%s"  where forwardid = %d and messageid = %d',$GLOBALS['tables']['linktrack_ml'],$row['firstclick'],$row['latestclick'],$fwdid,$messageid));
    } else {
      Sql_Query(sprintf('update %s set  clicked = clicked + 1, firstclick = "%s", latestclick = "%s"  where forwardid = %d and messageid = %d',$GLOBALS['tables']['linktrack_ml'],$row['firstclick'],$row['latestclick'],$fwdid,$messageid));
    }
    $uml = Sql_Fetch_Array_Query(sprintf('select * from %s where messageid = %d and forwardid = %d and userid = %d',
      $GLOBALS['tables']['linktrack_uml_click'],$messageid,$fwdid,$userid));
    
    if (empty($uml['messageid'])) {
      Sql_Query(sprintf('insert into %s set firstclick = "%s",latestclick = "%s",clicked = %d, forwardid = %d,messageid = %d,userid = %d',
        $GLOBALS['tables']['linktrack_uml_click'],$row['firstclick'],$row['latestclick'],$row['clicked'] - 1,$fwdid,$messageid,$userid));
    } 
    Sql_Query(sprintf('update %s set clicked = clicked + 1, firstclick = "%s", latestclick = "%s" where forwardid = %d and messageid = %d and userid = %d', $GLOBALS['tables']['linktrack_uml_click'],$row['firstclick'],$row['latestclick'],$fwdid,$messageid,$userid));
    
    if ($msgtype == 'H') {
      Sql_Query(sprintf('update %s set htmlclicked = htmlclicked + 1 where forwardid = %d and messageid = %d and userid = %d',
        $GLOBALS['tables']['linktrack_uml_click'],$fwdid,$messageid,$userid));
    } elseif ($msgtype == 'T') {
      Sql_Query(sprintf('update %s set textclicked = textclicked + 1 where forwardid = %d and messageid = %d and userid = %d',
        $GLOBALS['tables']['linktrack_uml_click'],$fwdid,$messageid,$userid));
    }
  }

  $c++;
  if ($c % 100 == 0) {
    print ". \n";
    flushbuffer();
  }
  if ($c % 1000 == 0) {
    output( "$c/$total<br/> ");
    flushbuffer();
  }

  flush();
  Sql_Query(sprintf('delete from %s where linkid = %d',$GLOBALS['tables']['linktrack'],$row['linkid']));

}
set_time_limit(6000);

output ($GLOBALS['I18N']->get('All done, optimizing table to recover space').'<br/>');
Sql_Query(sprintf('optimize table %s',$GLOBALS['tables']['linktrack']));

if (!$GLOBALS['commandline']) {
  print PageLink2('convertstats',$GLOBALS['I18N']->get('Convert some more'));
}
return;
