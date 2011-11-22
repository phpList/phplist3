<?php
require_once dirname(__FILE__).'/accesscheck.php';

# reset click track statistics

if (!isset($_GET['doit']) || $_GET['doit'] != 'yes') {
  print $GLOBALS['I18N']->get('Are you sure you want to clear all click statistics? ');
  print PageLink2('resetstats&doit=yes',$GLOBALS['I18N']->get('Yes, sure'));
} else {
  foreach (array('linktrack','linktrack_ml','linktrack_uml_click','linktrack_forward','linktrack_userclick') as $table) {
    Sql_Query(sprintf('delete from %s',$GLOBALS['tables'][$table]));
  }

  print $GLOBALS['I18N']->get('Statistics erased');
}

?>
