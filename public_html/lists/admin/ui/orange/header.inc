  </head>
<body bgcolor="#ffffff" background="ui/orange/images/bg.png">
<div id="dialog"></div><div id="hiddendiv"></div>

<a name="top"></a>
<div align=center>
<table class="header" cellspacing="0" cellpadding="0" width="710" border="0">
<tr>
<td bgcolor="#000000" rowspan="3"><img height="1" alt="" src="ui/orange/images/transparent.png" width="1" border="0" /></td>
<td bgcolor="#000000"><img height="1" alt="" src="ui/orange/images/transparent.png" width="708" border="0" /></td>
<td bgcolor="#000000" rowspan="3"><img height="1" alt="" src="ui/orange/images/transparent.png" width="1" border="0"  /></td>
</tr>

<tr valign="top" align="left">
<td>
<!--TOP table starts-->
<table cellspacing="0" cellpadding="0" width="708" bgcolor="#ffffff" border="0">
  <tr valign="top">
    <td colspan="2" rowspan="2" height="63" background="ui/orange/images/topstrip.png" /><a href="http://www.phplist.com" target="_blank"><img src="ui/orange/images/masthead.png" border="0" width="577" height="75" alt="" /></a></td>
   <td align="left" background="ui/orange/images/topstrip.png" bgcolor="#F0D1A3">
   <div class="poweredby"> powered by:<br />
   [<a class="powered" href="http://www.php.net/" target="_new">PHP</a>] + 
   [<a class="powered" href="http://www.mysql.com/" target="_new">mySQL</a>]</div></td></tr>
  <tr vAlign="bottom">
    <td vAlign="bottom" width="132"  background="ui/orange/images/topright.png" bgcolor="#F0D1A3">
   <span class="webblermenu">phplist</span></td></tr>
  <tr>
    <td bgcolor="#000000"><img height="1" alt="" src="ui/orange/images/transparent.png" width="20" border="0" /></td>
    <td bgcolor="#000000"><img height="1" alt="" src="ui/orange/images/transparent.png" width="576" border="0" /></td>
    <td bgcolor="#000000"><img height="1" alt="" src="ui/orange/images/transparent.png" width="132" border="0" /></td></tr>
  <tr valign="top">
    <td>&nbsp;</td>
<td><div align="left">
<br />
<?php
include dirname(__FILE__).'/mainmenu.php';
print topMenu();
print $languageswitcher;
?>
