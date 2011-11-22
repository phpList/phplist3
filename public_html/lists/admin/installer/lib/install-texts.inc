<?php
# language dependent text used in installer

///// PHP texts /////
$strInstallerTitle = 'installer';
$strIntroInstaller = '<p>Phplist installer will help you to create your database and config file for the system in several steps. Please keep in mind that if permissions are not well set up phplist won\'t be able to create either your db or your config file. The best thing to do first is to check if you have the right permissions so the installer will work well. Installer will need write permission on the lists/config/config.php file. You can probably do this through ftp, or if you have console access do chmod 646 lists/config/config.php.</p>
<p>You can always make the manual install following the instructions <a href="http://docs.phplist.com/PhplistInstallation">here</a>.</p>
<p>In several steps, as said, installer will check for all the configuration needed to start using the system.</p>'.
// 'Some advances features are not availables for edit for now, if you still want to use them you will have to edit those manually after the creation of the config file.</p>'.
'

<p>For more help undertanding phplist install you can take a look at <a href="http://docs.phplist.com/">phplist docs</a>, <a href="http://mantis.phplist.com/">mantis</a> for report a bug or ask for a future feature and the <a href="http://forums.phplist.com/">forums</a> for tips, answers and extra help.</p>
<p>The phplist installer requires cookies to be enabled.</p>';
$strChooseInstallation = '<b>Choose the installation type:</b><br>You can choose witch kind of installation you want to aply. Witch the basic installation (if you are an unexperience user) you can give to the installer the basic information to have a PHPList distribution installed.
The Advance mode let you (if you are an expert user) to adjust some anohter parameters to give PHPList the profile and powerfull do you need to manage your emailing as you want.
<font size=2><br><br>For single installation, we recomend you to choose the BASIC mode.<br><br></font>';
$strExplainInstall = 'This data are referer to the email address where the refused email must return.
this mean you must enter e email address where you want all mails witch can\'t be delivered will be received.
Consider all data are required, you must not forget any one of it';
$strConfigDirNoWritable = "Error: PHPList cannot write the configuration file into {path}. This is a permission matter, please, change it to let the applicacion complete its work.";
$strConfigExists = 'Your config file exists, you must remove it write the configuration again';
$strDbExplain = 'Here you will try to create/check your phplist database. You wont be able to continue if the Installer can\'t make a connection to the database.
Try not to use special characters like "\'-/*%", keep it simple using letter, numbers and "_".';
$strAdvanceMode = 'You are in the ADVANCE configuration area of the installation process';
$strReadyToInstall = 'Right now you are ready to complete the entire installation of PHPList.<br/>If you want to continue, only click in the next link bellow, if not...<br/>Well, only left this page out!';
$strReadyToUse = '<font size=2>Installation finished.</font><br>To use the application, only click %shere%s';
$strNextStep = 'Next step';
$strWriteToConfig = 'Write to config now!';
$strStep = 'Step';
$strStep0 = 'Database definitions';
$strStep1 = 'Bounces setting';
$strStep2 = 'Advanced configuration';
$strStep3 = 'Writing config file!';
$strConnectionRefused = "Connection refused, check your host, user or password";
$strDbNotExist = "database %s does not exist";
$strDbNotCreate = "You can't create database '%s', contact your sysadmin";
$strDbExists = "The database '%s' exists, you can't create it again";
$strDbCreated = "Database '%s' created";
$strDbConnRef = "problem to connect to this server";
$strDbServerBusy = "server busy";
$strDbWrongUserData = "User or password are wrong, check it";
$strDbDeniedCr = "Maybe the user '%s' has not permission to access this database, or the database '%s' does not exist and/or the user has not permission to create it";
$strDbHostNeeded = "The database host must be supplied";
$strDbUserNeeded = "The database user must be supplied";
$strDbPassNeeded = "The database password must be supplied";
$strDbConnOK = "Database connection OK";
$strConfigNotWritable = 'Config file exists, but it could not be overwrited because you don\'t have permission to do that.';
$strConfigDirNotWritable = 'Current directory of the config file exists (%s), but it could not be access to create a file on it.';
$strConfigRewriteError = 'Config file exists, and was trying to rewrite it, but was an error in the process. Check it with the sysadmin of your server.';
$strConfigRewrited = 'Config file exists, and was rewrited fine.';
$strConfigWrited = 'Your config file was writed Ok!';


///// JS texts /////
$strJsDbName = "You must enter a database name";
$strJsDbHost = "You must enter database host";
$strJsDbUser = "You must enter database user";
$strJsDbPass = "You must enter database password";
$strJsMailAccount = "You must enter mail account";
$strJsMailValidAccount = "You must enter a valid mail account";
$strJsMailHost = "You must enter the mail server (host) for this account";
$strJsMailUser = "You must enter the user for connect to the mail server (host)";
$strJsMailPass = "You must enter the password for this user";
?>
