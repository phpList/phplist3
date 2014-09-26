<?php
//=========================================================================//
/* DEFINITION OF "parameters" STRUCTURE
   This structure cotain each group of data needed to configure
   the PHPList application using the installer script.
   Below each group are defined and added to this structure.
   All parameters of each group are disposed in his 'personal' array 
   to give developers a more resumed estructure to work with.
*/
$parameters = Array();

//=========================================================================//


//--------------------------------------------------------------------------
//  FIRST GROUP (0)- LANGUAGE PARAMETERS
//  GROUP STRUCTURE: $language_def
//--------------------------------------------------------------------------

$parameters[0]["name"] = "Language parameters\n";
$parameters[0]["file_description"] = "

/*=========================================================================

General settings for language

=========================================================================*/";
$language_def[0]["name"] = "language_module";
$language_def[0]["default"] = "english.inc";
$language_def[0]["installation"] = "ADVANCED";
$language_def[0]["commented"] = 0;
$language_def[0]["config-type"] = "variable";
$language_def[0]["html-entity"] = "list-single-getLanguages";
$language_def[0]["appl_description"] = "Select the language for your PHPList installation";
$language_def[0]["js_description"] = "Language";
$language_def[0]["file_description"] = "
# select the language module to use
# Look for <country>.inc files in the texts directory
# to find your language
# this is the language for the frontend pages. In the admin pages you can
# choose your language by using the dropdown in the pages.\n";

$parameters[0]["parameters"] = $language_def;



//--------------------------------------------------------------------------
//  SECOND GROUP (1)- DATABASE PARAMETERS
//  GROUP STRUCTURE: $database_def
//--------------------------------------------------------------------------

$parameters[1]["name"] = "Database parameters\n";
$parameters[1]["file_description"] = "

/*=========================================================================

General settings for database

=========================================================================*/";
$database_def[0]["name"] = "database_name";
$database_def[0]["default"] = "phplistdb";
$database_def[0]["installation"] = "BASIC";
$database_def[0]["commented"] = 0;
$database_def[0]["config-type"] = "variable";
$database_def[0]["html-entity"] = "textfield-alfa-20";
$database_def[0]["appl_description"] = "Database name";
$language_def[0]["js_description"] = "Database name";
$database_def[0]["file_description"] = "\n# what is the name of the database we are using\n";

$database_def[1]["name"] = "database_host";
$database_def[1]["default"] = "localhost";
$database_def[1]["installation"] = "BASIC";
$database_def[1]["commented"] = 0;
$database_def[1]["config-type"] = "variable";
$database_def[1]["html-entity"] = "textfield-alfa-40";
$database_def[1]["appl_description"] = "Database host (usually 'localhost')";
$language_def[1]["js_description"] = "Database host";
$database_def[1]["file_description"] = "\n# what is your Mysql database server\n";

$database_def[2]["name"] = "database_user";
$database_def[2]["default"] = "phplist";
$database_def[2]["installation"] = "BASIC";
$database_def[2]["commented"] = 0;
$database_def[2]["config-type"] = "variable";
$database_def[2]["html-entity"] = "textfield--10";
$database_def[2]["appl_description"] = "Database user, previously created";
$language_def[2]["js_description"] = "Database user";
$database_def[2]["file_description"] = "\n# who do we log in as?\n";

$database_def[3]["name"] = "database_password";
$database_def[3]["default"] = "phplist";
$database_def[3]["installation"] = "BASIC";
$database_def[3]["commented"] = 0;
$database_def[3]["config-type"] = "variable";
$database_def[3]["html-entity"] = "textfield-pass-10";
$database_def[3]["appl_description"] = "Database pass of user";
$language_def[3]["js_description"] = "Database password";
$database_def[3]["file_description"] = "\n# and what password do we use\n";

$database_def[4]["name"] = "table_prefix";
$database_def[4]["default"] = "phplist_";
$database_def[4]["installation"] = "ADVANCED";
$database_def[4]["commented"] = 0;
$database_def[4]["config-type"] = "variable";
$database_def[4]["html-entity"] = "no-required|textfield-alfa-20";
$database_def[4]["appl_description"] = "Table prefix";
$database_def[4]["file_description"] = "\n# if you want a prefix to all your tables, specify it here.\n";

$database_def[5]["name"] = "usertable_prefix";
$database_def[5]["default"] = "phplist_user_";
$database_def[5]["installation"] = "ADVANCED";
$database_def[5]["commented"] = 0;
$database_def[5]["config-type"] = "variable";
$database_def[5]["html-entity"] = "no-required|textfield-alfa-20";
$database_def[5]["appl_description"] = "User-table prefix";
$database_def[5]["file_description"] = "
# if you want to use a different prefix to user tables, specify it here.
# read README.usertables for more information.\n";

$parameters[1]["parameters"] = $database_def;



//--------------------------------------------------------------------------
// TIRTH GROUP (2)- BOUNCE HANDLE PARAMETERS
// GROUP STRUCTURE: $bounce_def
//--------------------------------------------------------------------------

$parameters[2]["name"] = "Return Mail parameters\n";
$parameters[2]["file_description"] = "

/*=========================================================================

Settings for handling bounces

=========================================================================*/";
$bounce_def[0]["name"] = "message_envelope";
$bounce_def[0]["default"] = "listbounces@yourdomain";
$bounce_def[0]["installation"] = "BASIC";
$bounce_def[0]["commented"] = 1;
$bounce_def[0]["config-type"] = "variable";
$bounce_def[0]["html-entity"] = "textfield-alfa-50";
$bounce_def[0]["appl_description"] = "Email address";
$bounce_def[0]["file_description"] = "
# Message envelope. This is the email that system messages come from
# it is useful to make this one where you can process the bounces on
# you will probably get a X-Authentication-Warning in your message
# when using this with sendmail
# NOTE: this is *very* different from the From: line in a message
# to use this feature, uncomment the following line, and change the email address
# to some existing account on your system
# requires PHP version > \"4.0.5\" and \"4.3.1+\" without safe_mode\n";

$bounce_def[1]["name"] = "bounce_protocol";
$bounce_def[1]["default"] = "pop";
$bounce_def[1]["installation"] = "ADVANCED";
$bounce_def[1]["commented"] = 0;
$bounce_def[1]["config-type"] = "variable";
$bounce_def[1]["html-entity"] = "textfield-alfa-10";
$bounce_def[1]["appl_description"] = "Bounce protocol";
$bounce_def[1]["file_description"] = "
# Handling bounces. Check README.bounces for more info
# This can be 'pop' or 'mbox'\n";

$bounce_def[2]["name"] = "MANUALLY_PROCESS_BOUNCES";
$bounce_def[2]["default"] = "1";
$bounce_def[2]["installation"] = "ADVANCED";
$bounce_def[2]["commented"] = 0;
$bounce_def[2]["config-type"] = "constant";
$bounce_def[2]["html-entity"] = "boolean";
$bounce_def[2]["appl_description"] = "Process bounces manually?";
$bounce_def[2]["file_description"] = "
# set this to 0, if you set up a cron to download bounces regularly by using the
# commandline option. If this is 0, users cannot run the page from the web
# frontend. Read README.commandline to find out how to set it up on the
# commandline\n";

$bounce_def[3]["name"] = "bounce_mailbox_host";
$bounce_def[3]["default"] = "localhost";
$bounce_def[3]["installation"] = "BASIC";
$bounce_def[3]["commented"] = 0;
$bounce_def[3]["config-type"] = "variable";
$bounce_def[3]["html-entity"] = "textfield-alfa-40";
$bounce_def[3]["appl_description"] = "Mailbox Host";
$bounce_def[3]["file_description"] = "\n# when the protocol is pop, specify these three\n";

$bounce_def[4]["name"] = "bounce_mailbox_user";
$bounce_def[4]["default"] = "popuser";
$bounce_def[4]["installation"] = "BASIC";
$bounce_def[4]["commented"] = 0;
$bounce_def[4]["config-type"] = "variable";
$bounce_def[4]["html-entity"] = "textfield-alfa-10";
$bounce_def[4]["appl_description"] = "User";
$bounce_def[4]["file_description"] = "";

$bounce_def[5]["name"] = "bounce_mailbox_password";
$bounce_def[5]["default"] = "password";
$bounce_def[5]["installation"] = "BASIC";
$bounce_def[5]["commented"] = 0;
$bounce_def[5]["config-type"] = "variable";
$bounce_def[5]["html-entity"] = "textfield-alfa-10";
$bounce_def[5]["appl_description"] = "Password";
$bounce_def[5]["file_description"] = "";

$bounce_def[6]["name"] = "bounce_mailbox_port";
$bounce_def[6]["default"] = "110/pop3/notls";
$bounce_def[6]["installation"] = "ADVANCED";
$bounce_def[6]["commented"] = 0;
$bounce_def[6]["config-type"] = "variable";
$bounce_def[6]["html-entity"] = "textfield-alfa-20";
$bounce_def[6]["appl_description"] = "Bounce Mailbox port";
$bounce_def[6]["file_description"] = "
# the \"port\" is the remote port of the connection to retrieve the emails
# the default should be fine but if it doesn't work, you can try the second
# one. To do that, add a # before the first line and take off the one before the
# second line\n";

$bounce_def[7]["name"] = "bounce_mailbox";
$bounce_def[7]["default"] = "/var/spool/mail/listbounces";
$bounce_def[7]["installation"] = "ADVANCED";
$bounce_def[7]["commented"] = 0;
$bounce_def[7]["config-type"] = "variable";
$bounce_def[7]["html-entity"] = "textfield-alfa-40";
$bounce_def[7]["appl_description"] = "Bounce mailbox (path)";
$bounce_def[7]["js_description"] = "Bounce mailbox";
$bounce_def[7]["file_description"] = "
# when the protocol is mbox specify this one
# it needs to be a local file in mbox format, accessible to your webserver user\n";

$bounce_def[8]["name"] = "bounce_mailbox_purge";
$bounce_def[8]["default"] = "1";
$bounce_def[8]["installation"] = "ADVANCED";
$bounce_def[8]["commented"] = 0;
$bounce_def[8]["config-type"] = "variable";
$bounce_def[8]["html-entity"] = "boolean";
$bounce_def[8]["appl_description"] = "Purge bounce mailbox?";
$bounce_def[8]["file_description"] = "
# set this to 0 if you want to keep your messages in the mailbox. this is potentially
# a problem, because bounces will be counted multiple times, so only do this if you are
# testing things.\n";

$bounce_def[9]["name"] = "bounce_mailbox_purge_unprocessed";
$bounce_def[9]["default"] = "1";
$bounce_def[9]["installation"] = "ADVANCED";
$bounce_def[9]["commented"] = 0;
$bounce_def[9]["config-type"] = "variable";
$bounce_def[9]["html-entity"] = "boolean";
$bounce_def[9]["appl_description"] = "Purge unprocessed bounces?";
$bounce_def[9]["file_description"] = "
# set this to 0 if you want to keep unprocessed messages in the mailbox. Unprocessed
# messages are messages that could not be matched with a user in the system
# messages are still downloaded into PHPlist, so it is safe to delete them from
# the mailbox and view them in PHPlist\n";

$bounce_def[10]["name"] = "bounce_unsucribe_threshold";
$bounce_def[10]["default"] = "5";
$bounce_def[10]["installation"] = "ADVANCED";
$bounce_def[10]["commented"] = 0;
$bounce_def[10]["config-type"] = "variable";
$bounce_def[10]["html-entity"] = "textfield-int-3";
$bounce_def[10]["appl_description"] = "# of bounces to unconfirm a user";
$bounce_def[10]["file_description"] = "\n# how many bounces in a row need to have occurred for a user to be marked unconfirmed\n";

$parameters[2]["parameters"] = $bounce_def;



//--------------------------------------------------------------------------
//  FOURTH GROUP (3)- INSTALLATION PARAMETERS
//  GROUP STRUCTURE: $installation_def
//--------------------------------------------------------------------------

$parameters[3]["name"] = "Installation parameters\n";
$parameters[3]["file_description"] = "

/*=========================================================================

Settings for installation definitions

=========================================================================*/";

$installation_def[0]["name"] = "installation_name";
$installation_def[0]["default"] = "PHPlist";
$installation_def[0]["installation"] = "ADVANCED";
$installation_def[0]["commented"] = 0;
$installation_def[0]["config-type"] = "variable";
$installation_def[0]["html-entity"] = "textfield-alfa-20";
$installation_def[0]["appl_description"] = "Installation name";
$installation_def[0]["file_description"] = "
# if you use multiple installations of PHPlist you can set this to
# something to identify this one. it will be prepended to email report
# subjects\n";

$installation_def[1]["name"] = "pageroot";
$installation_def[1]["default"] = "/lists";
$installation_def[1]["installation"] = "ADVANCED";
$installation_def[1]["commented"] = 0;
$installation_def[1]["config-type"] = "variable";
$installation_def[1]["html-entity"] = "textfield-alfa-20";
$installation_def[1]["appl_description"] = "PHPList installation path";
$installation_def[1]["file_description"] = "
# if you change the path to the PHPlist system, make the change here as well
# path should be relative to the root directory of your webserver (document root)
# you cannot actually change the \"admin\", but you can change the \"lists\"\n";

$installation_def[2]["name"] = "admin_pages";
$installation_def[2]["default"] = "/lists/admin";
$installation_def[2]["installation"] = "ADVANCED";
$installation_def[2]["commented"] = 0;
$installation_def[2]["config-type"] = "variable";
$installation_def[2]["html-entity"] = "textfield-alfa-10";
$installation_def[2]["appl_description"] = "Admin page path";
$installation_def[2]["file_description"] = "";

$parameters[3]["parameters"] = $installation_def;



//--------------------------------------------------------------------------
//  FIFTH GROUP (4)- SECURITY RELATED PARAMETERS
//  GROUP STRUCTURE: $security_def
//--------------------------------------------------------------------------

$parameters[4]["name"] = "Security parameters\n";
$parameters[4]["file_description"] = "

/*=========================================================================

Security related settings

=========================================================================*/";

$security_def[0]["name"] = "require_login";
$security_def[0]["default"] = "1";
$security_def[0]["installation"] = "ADVANCED";
$security_def[0]["commented"] = 0;
$security_def[0]["config-type"] = "variable";
$security_def[0]["html-entity"] = "boolean";
$security_def[0]["appl_description"] = "Login autentication is required?";
$security_def[0]["file_description"] = "
# set this to 1 if you want PHPlist to deal with login for the administrative
# section of the system
# you will be able to add administrators who control their own lists
# default login is \"admin\" with password \"phplist\"\n";

$security_def[1]["name"] = "MAXLIST";
$security_def[1]["default"] = "1";
$security_def[1]["installation"] = "ADVANCED";
$security_def[1]["commented"] = 0;
$security_def[1]["config-type"] = "constant";
$security_def[1]["html-entity"] = "textfield-int-5";
$security_def[1]["appl_description"] = "Number of lists per administrator users";
$security_def[1]["file_description"] = "\n# if you use login, how many lists can be created per administrator\n";

/*
$security_def[2]["name"] = "commandline_users";
$security_def[2]["default"] = "admin";
$security_def[2]["installation"] = "ADVANCED";
$security_def[2]["commented"] = 0;
$security_def[2]["config-type"] = "array";
$security_def[2]["html-entity"] = "addtextfield-alfa-30";
$security_def[2]["appl_description"] = "";
$security_def[2]["file_description"] = "
# if you use commandline, you will need to identify the users who are allowed to run
# the script. See README.commandline for more info\n";
$security_def[2]["file_description_after"] = "
# or you can use the following to disable the check (take off the # in front of the line)
# ".'$comandline_users = array();'."\n";
*/

$security_def[2]["name"] = "ASKFORPASSWORD";
$security_def[2]["default"] = "0";
$security_def[2]["installation"] = "ADVANCED";
$security_def[2]["commented"] = 0;
$security_def[2]["config-type"] = "constant";
$security_def[2]["html-entity"] = "boolean";
$security_def[2]["appl_description"] = "Ask for password?";
$security_def[2]["file_description"] = "
# as of version 2.4.1, you can have your users define a password for themselves as well
# this will cause some public pages to ask for an email and a password when the password is
# set for the user. If you want to activate this functionality, set the following
# to 1. See README.passwords for more information\n";

$security_def[3]["name"] = "UNSUBSCRIBE_REQUIRES_PASSWORD";
$security_def[3]["default"] = "0";
$security_def[3]["installation"] = "ADVANCED";
$security_def[3]["commented"] = 0;
$security_def[3]["config-type"] = "constant";
$security_def[3]["html-entity"] = "boolean";
$security_def[3]["appl_description"] = "Force unsuscribed to provide a password?";
$security_def[3]["file_description"] = "
# if you also want to force people who unsubscribe to provide a password before
# processing their unsubscription, set this to 1. You need to have the above one set
# to 1 for this to have an effect\n";

$security_def[4]["name"] = "UNSUBSCRIBE_JUMPOFF";
$security_def[4]["default"] = "0";
$security_def[4]["installation"] = "ADVANCED";
$security_def[4]["commented"] = 0;
$security_def[4]["config-type"] = "constant";
$security_def[4]["html-entity"] = "boolean";
$security_def[4]["appl_description"] = "Unsucribe users without ask for reasons?";
$security_def[4]["file_description"] = "
# if a user should immediately be unsubscribed, when using their personal URL, instead of
# the default way, which will ask them for a reason, set this to 1\n";

$security_def[5]["name"] = "blacklist_gracetime";
$security_def[5]["default"] = "5";
$security_def[5]["installation"] = "ADVANCED";
$security_def[5]["commented"] = 0;
$security_def[5]["config-type"] = "variable";
$security_def[5]["html-entity"] = "textfield-int-5";
$security_def[5]["appl_description"] = "To send an unsuscribe confirmation email, wait...|seconds";
$security_def[5]["js_description"] = "Blacklist gracetime";
$security_def[5]["file_description"] = "
# when a user unsubscribes they are sent one final email informing them of
# their unsubscription. In order for that email to actually go out, a gracetime
# needs to be set otherwise it will never go out. The default of 5 minutes should
# be fine, but you can increase it if you experience problems\n";

$security_def[6]["name"] = "CHECK_SESSIONIP";
$security_def[6]["default"] = "1";
$security_def[6]["installation"] = "ADVANCED";
$security_def[6]["commented"] = 0;
$security_def[6]["config-type"] = "constant";
$security_def[6]["html-entity"] = "boolean";
$security_def[6]["appl_description"] = "Check user session by IP?";
$security_def[6]["file_description"] = "
# to increase security the session of a user is checked for the IP address
# this needs to be the same for every request. This may not work with
# network situations where you connect via multiple proxies, so you can
# switch off the checking by setting this to 0\n";

$security_def[7]["name"] = "ENCRYPTPASSWORD";
$security_def[7]["default"] = "0";
$security_def[7]["installation"] = "ADVANCED";
$security_def[7]["commented"] = 0;
$security_def[7]["config-type"] = "constant";
$security_def[7]["html-entity"] = "boolean";
$security_def[7]["appl_description"] = "Encrypt passwords?";
$security_def[7]["file_description"] = "
# if you use passwords, you can store them encrypted or in plain text
# if you want to encrypt them, set this one to 1
# if you use encrypted passwords, users can only request you as an administrator to
# reset the password. They will not be able to request the password from
# the system\n";

$security_def[8]["name"] = "check_for_host";
$security_def[8]["default"] = "0";
$security_def[8]["installation"] = "ADVANCED";
$security_def[8]["commented"] = 0;
$security_def[8]["config-type"] = "variable";
$security_def[8]["html-entity"] = "boolean";
$security_def[8]["appl_description"] = "Check for host of entered emails for subscription?";
$security_def[8]["file_description"] = "
# Check for host of email entered for subscription
# Do not use it if your server is not 24hr online
# make the 0 a 1, if you want to use it\n";

$parameters[4]["parameters"] = $security_def;



//--------------------------------------------------------------------------
//  SIXTH GROUP (5)- DEBUGGING AND INFORMATIONAL
//  GROUP STRUCTURE: $debuginf_def
//--------------------------------------------------------------------------

$parameters[5]["name"] = "Debugging & Informational parameters\n";
$parameters[5]["file_description"] = "

/*=========================================================================

Debugging and informational

=========================================================================*/";

$debuginf_def[0]["name"] = "TEST";
$debuginf_def[0]["default"] = "0";
$debuginf_def[0]["installation"] = "ADVANCED";
$debuginf_def[0]["commented"] = 0;
$debuginf_def[0]["config-type"] = "constant";
$debuginf_def[0]["html-entity"] = "boolean";
$debuginf_def[0]["appl_description"] = "Are you working in TEST MODE?";
$debuginf_def[0]["file_description"] = "
# if test is true (not 0) it will not actually send ANY messages,
# but display what it would have sent\n";

$debuginf_def[1]["name"] = "VERBOSE";
$debuginf_def[1]["default"] = "0";
$debuginf_def[1]["installation"] = "ADVANCED";
$debuginf_def[1]["commented"] = 0;
$debuginf_def[1]["config-type"] = "constant";
$debuginf_def[1]["html-entity"] = "boolean";
$debuginf_def[1]["appl_description"] = "Do you want to display any message to send?";
$debuginf_def[1]["file_description"] = "
# if you set verbose to 1, it will show the messages that will be sent. Do not do this
# if you have a lot of users, because it is likely to crash your browser
# (it does mine, Mozilla 0.9.2, well 1.6 now, but I would still keep it off :-)\n";

$debuginf_def[2]["name"] = "WARN_ABOUT_PHP_SETTINGS";
$debuginf_def[2]["default"] = "1";
$debuginf_def[2]["installation"] = "ADVANCED";
$debuginf_def[2]["commented"] = 0;
$debuginf_def[2]["config-type"] = "constant";
$debuginf_def[2]["html-entity"] = "boolean";
$debuginf_def[2]["appl_description"] = "Do You need show up warnings about PHP settings?";
$debuginf_def[2]["file_description"] = "
# some warnings may show up about your PHP settings. If you want to get rid of them
# set this value to 0\n";

$debuginf_def[3]["name"] = "MANUALLY_PROCESS_QUEUE";
$debuginf_def[3]["default"] = "1";
$debuginf_def[3]["installation"] = "ADVANCED";
$debuginf_def[3]["commented"] = 0;
$debuginf_def[3]["config-type"] = "constant";
$debuginf_def[3]["html-entity"] = "boolean";
$debuginf_def[3]["appl_description"] = "Queue will be processed manually?";
$debuginf_def[3]["file_description"] = "
# If you set up your system to send the message automatically, you can set this value
# to 0, so \"Process Queue\" will disappear from the site
# this will also stop users from loading the page on the web frontend, so you will
# have to make sure that you run the queue from the commandline
# check README.commandline how to do this\n";

$debuginf_def[4]["name"] = "WORKAROUND_OUTLOOK_BUG";
$debuginf_def[4]["default"] = "0";
$debuginf_def[4]["installation"] = "ADVANCED";
$debuginf_def[4]["commented"] = 0;
$debuginf_def[4]["config-type"] = "constant";
$debuginf_def[4]["html-entity"] = "boolean";
$debuginf_def[4]["appl_description"] = "You will need use special breaklines for Outlook?";
$debuginf_def[4]["file_description"] = "
# if you want to use \r\n 
# for formatting messages set the 0 to 1
# see also http://www.securityfocus.com/archive/1/255910
# this is likely to break things for other mailreaders, so you should
# only use it if all your users have Outlook (not Express)\n";

$debuginf_def[5]["name"] = "userhistory_systeminfo";
$debuginf_def[5]["default"] = "HTTP_USER_AGENT|HTTP_REFERER|REMOTE_ADDR";
$debuginf_def[5]["installation"] = "HIDDEN";
$debuginf_def[5]["commented"] = 0;
$debuginf_def[5]["config-type"] = "array";
$debuginf_def[5]["html-entity"] = "addlist-3";
$debuginf_def[5]["appl_description"] = "";
$debuginf_def[5]["file_description"] = "
# user history system info.
# when logging the history of a user, you can specify which system variables you
# want to log. These are the ones that are found in the $_SERVER and the $_ENV
# variables of PHP. check http://www.php.net/manual/en/language.variables.predefined.php
# the values are different per system, but these ones are quite common.\n";

$debuginf_def[6]["name"] = "USE_SPAM_BLOCK";
$debuginf_def[6]["default"] = "1";
$debuginf_def[6]["installation"] = "ADVANCED";
$debuginf_def[6]["commented"] = 0;
$debuginf_def[6]["config-type"] = "constant";
$debuginf_def[6]["html-entity"] = "boolean";
$debuginf_def[6]["appl_description"] = "Do You want to use SpamBlock to check subscribe attempt?";
$debuginf_def[6]["file_description"] = "
# add spamblock
# if you set this to 1, phplist will try to check if the subscribe attempt is a spambot trying to send
# nonsense. If you think this doesn't work, set this to 0
# this is currently only implemented on the subscribe pages\n";

$debuginf_def[7]["name"] = "NOTIFY_SPAM";
$debuginf_def[7]["default"] = "1";
$debuginf_def[7]["installation"] = "ADVANCED";
$debuginf_def[7]["commented"] = 0;
$debuginf_def[7]["config-type"] = "constant";
$debuginf_def[7]["html-entity"] = "boolean";
$debuginf_def[7]["appl_description"] = "Notify if some Spam attack happend?";
$debuginf_def[7]["file_description"] = "
# notify spam
# when phplist detects a possible spam attack, it can send you a notification about it
# you can check for a while to see if the spam check was correct and if so, set this value
# to 0, if you think the check does it\'s job correctly.
# it will only send you emails if you have \"Does the admin get copies of subscribe, update and unsubscribe messages\"
# in the configuration set to true\n";

$parameters[5]["parameters"] = $debuginf_def;



//--------------------------------------------------------------------------
//  SEVENTH GROUP (6)- FEEDBACK TO DEVELOPERS
//  GROUP STRUCTURE: $develop_def
//--------------------------------------------------------------------------

$parameters[6]["name"] = "Feedback to developers\n";
$parameters[6]["file_description"] = "

/*=========================================================================

Feedback to developers

=========================================================================*/";

$develop_def[0]["name"] = "REGISTER";
$develop_def[0]["default"] = "1";
$develop_def[0]["installation"] = "ADVANCED";
$develop_def[0]["commented"] = 0;
$develop_def[0]["config-type"] = "constant";
$develop_def[0]["html-entity"] = "boolean";
$develop_def[0]["appl_description"] = "Do you want be regitered in PHPList.com?";
$develop_def[0]["file_description"] = "
# use Register to \"register\" to PHPlist.com. Once you set TEST to 0, the system will then
# request the \"Powered By\" image from www.phplist.com, instead of locally. This will give me
# a little bit of an indication of how much it is used, which will encourage me to continue
# developing PHPlist. If you do not like this, set Register to 0.\n";

$develop_def[1]["name"] = "EMAILTEXTCREDITS";
$develop_def[1]["default"] = "0";
$develop_def[1]["installation"] = "ADVANCED";
$develop_def[1]["commented"] = 0;
$develop_def[1]["config-type"] = "constant";
$develop_def[1]["html-entity"] = "boolean";
$develop_def[1]["appl_description"] = "Can you accept to retain our PHPList credits in your sended emails?";
$develop_def[1]["file_description"] = "
# CREDITS
# We request you retain some form of credits on the public elements of
# PHPlist. These are the subscribe pages and the emails.
# This not only gives respect to the large amount of time given freely
# by the developers but also helps build interest, traffic and use of
# PHPlist, which is beneficial to future developments.
# By default the webpages and the HTML emails will include an image and
# the text emails will include a powered by line

# If you want to remove the image from the HTML emails, set this constant
# to be 1, the HTML emails will then only add a line of text as signature\n";

$develop_def[2]["name"] = "PAGETEXTCREDITS";
$develop_def[2]["default"] = "0";
$develop_def[2]["installation"] = "ADVANCED";
$develop_def[2]["commented"] = 0;
$develop_def[2]["config-type"] = "constant";
$develop_def[2]["html-entity"] = "boolean";
$develop_def[2]["appl_description"] = "Can you accept to retain our PHPList image in your WebPage?";
$develop_def[2]["file_description"] = "
# if you want to also remove the image from your public webpages
# set the next one to 1, and the pages will only include a line of text\n";

$develop_def[3]["name"] = "NOSTATSCOLLECTION";
$develop_def[3]["default"] = "0";
$develop_def[3]["installation"] = "ADVANCED";
$develop_def[3]["commented"] = 0;
$develop_def[3]["config-type"] = "constant";
$develop_def[3]["html-entity"] = "boolean";
$develop_def[3]["appl_description"] = "Keep statistics reports informed by email?";
$develop_def[3]["file_description"] = "
# in order to get some feedback about performance, PHPlist can send statistics to a central
# email address. To de-activate this set the following value to 1\n";

$develop_def[4]["name"] = "stats_collection_address";
$develop_def[4]["default"] = "phplist-stats@tincan.co.uk";
$develop_def[4]["installation"] = "ADVANCED";
$develop_def[4]["commented"] = 1;
$develop_def[4]["config-type"] = "variable";
$develop_def[4]["html-entity"] = "boolean";
$develop_def[4]["appl_description"] = "";
$develop_def[4]["file_description"] = "
# this is the email it will be sent to. You can leave the default, or you can set it to send
# to your self. If you use the default you will give me some feedback about performance
# which is useful for me for future developments\n";

$parameters[6]["parameters"] = $develop_def;



//--------------------------------------------------------------------------
//  EIGHTH GROUP (7)- MISCELLANEOUS
//  GROUP STRUCTURE: $misc_def
//--------------------------------------------------------------------------

$parameters[7]["name"] = "Miscellaneous\n";
$parameters[7]["file_description"] = "

/*=========================================================================

Miscellaneous

=========================================================================*/";

$miscell_def[0]["name"] = "NUMCRITERIAS";
$miscell_def[0]["default"] = "2";
$miscell_def[0]["installation"] = "ADVANCED";
$miscell_def[0]["commented"] = 0;
$miscell_def[0]["config-type"] = "constant";
$miscell_def[0]["html-entity"] = "textfield-int-5";
$miscell_def[0]["appl_description"] = "Number of selectable criterias in message sending";
$miscell_def[0]["js_description"] = "Number of criterias";
$miscell_def[0]["file_description"] = "
# the number of criterias you want to be able to select when sending a message.
# Useful is is to make it the same as the number of selectable attributes you enter in the
# system, but that is up to you (selectable = select, radio or checkbox)\n";


$miscell_def[1]["name"] = "ALLOW_NON_LIST_SUBSCRIBE";
$miscell_def[1]["default"] = "0";
$miscell_def[1]["installation"] = "ADVANCED";
$miscell_def[1]["commented"] = 0;
$miscell_def[1]["config-type"] = "constant";
$miscell_def[1]["html-entity"] = "boolean";
$miscell_def[1]["appl_description"] = "Each subscribed user must select a list?";
$miscell_def[1]["file_description"] = "
# if you do not require users to actually sign up to lists, but only want to
# use the subscribe page as a kind of registration system, you can set this to 1 and
# users will not receive an error when they do not check a list to subscribe to\n";

$miscell_def[2]["name"] = "MAILQUEUE_BATCH_SIZE";
$miscell_def[2]["default"] = "0";
$miscell_def[2]["installation"] = "ADVANCED";
$miscell_def[2]["commented"] = 0;
$miscell_def[2]["config-type"] = "constant";
$miscell_def[2]["html-entity"] = "textfield-int-8";
$miscell_def[2]["html-group"] = "Mail queue features";
$miscell_def[2]["appl_description"] = "Number of emails per period...|mails";
$miscell_def[2]["js_description"] = "Number of emails";
$miscell_def[2]["file_description"] = "
# batch processing
# if you are on a shared host, it will probably be appreciated if you don't send
# out loads of emails in one go. To do this, you can configure batch processing.
# Please note, the following two values can be overridden by your ISP by using
# a server wide configuration. So if you notice these values to be different
# in reality, that may be the case

# define the amount of emails you want to send per period. If 0, batch processing
# is disabled and messages are sent out as fast as possible\n";

$miscell_def[3]["name"] = "MAILQUEUE_BATCH_PERIOD";
$miscell_def[3]["default"] = "3600";
$miscell_def[3]["installation"] = "ADVANCED";
$miscell_def[3]["commented"] = 0;
$miscell_def[3]["config-type"] = "constant";
$miscell_def[3]["html-entity"] = "textfield-int-8";
$miscell_def[3]["html-group"] = "Mail queue features";
$miscell_def[3]["appl_description"] = "Length of a period...|seconds";
$miscell_def[3]["js_description"] = "Length of a period";
$miscell_def[3]["file_description"] = "\n# define the length of one batch processing period, in seconds (3600 is an hour)\n";

$miscell_def[4]["name"] = "MAILQUEUE_THROTTLE";
$miscell_def[4]["default"] = "0";
$miscell_def[4]["installation"] = "ADVANCED";
$miscell_def[4]["commented"] = 0;
$miscell_def[4]["config-type"] = "constant";
$miscell_def[4]["html-entity"] = "textfield-int-5";
$miscell_def[4]["html-group"] = "Mail queue features";
$miscell_def[4]["appl_description"] = "Mail queue throttle...|seconds";
$miscell_def[4]["js_description"] = "Seconds for Mail queue throttle";
$miscell_def[4]["file_description"] = "
# to avoid overloading the server that sends your email, you can add a little delay
# between messages that will spread the load of sending
# you will need to find a good value for your own server
# value is in seconds (or you can play with the autothrottle feature witch is into the experimental section)\n";

$miscell_def[5]["name"] = "DATE_START_YEAR";
$miscell_def[5]["default"] = "0";
$miscell_def[5]["installation"] = "ADVANCED";
$miscell_def[5]["commented"] = 0;
$miscell_def[5]["config-type"] = "constant";
$miscell_def[5]["html-entity"] = "textfield-int-5";
$miscell_def[5]["html-group"] = "Drop-Down boxes";
$miscell_def[5]["appl_description"] = "First year for drop-down lists is...|<br><i>(0 = default behaviour<br>&lt;year&gt; = first year to display)</i>";
$miscell_def[5]["js_description"] = "First year for drop-down";
$miscell_def[5]["file_description"] = "
# year ranges. If you use dates, by default the drop down for year will be from
# three years before until 10 years after this the current value for year. If there
# is no current value the current year will be used.
# if you want to use a bigger range you can set the start and end year here
# be aware that the drop down may become very large.
# if set to 0 they will use the default behaviour. So I'm afraid you can't start with
# year 0. Also be aware not to set the end year to something relatively soon in the
# future, or it will stop working when you reach that year.\n";

$miscell_def[6]["name"] = "DATE_END_YEAR";
$miscell_def[6]["default"] = "0";
$miscell_def[6]["installation"] = "ADVANCED";
$miscell_def[6]["commented"] = 0;
$miscell_def[6]["config-type"] = "constant";
$miscell_def[6]["html-entity"] = "textfield-int-5";
$miscell_def[6]["html-group"] = "Drop-Down boxes";
$miscell_def[6]["appl_description"] = "Last year for drop-down lists is...|<br><i>(0 = default behaviour<br>&lt;year&gt; = Last year to display)</i>";
$miscell_def[6]["js_description"] = "Last year for drop-down";
$miscell_def[6]["file_description"] = "";

$miscell_def[7]["name"] = "EMPTY_VALUE_PREFIX";
$miscell_def[7]["default"] = "--";
$miscell_def[7]["installation"] = "ADVANCED";
$miscell_def[7]["commented"] = 0;
$miscell_def[7]["config-type"] = "constant";
$miscell_def[7]["html-entity"] = "textfield-alfa-25";
$miscell_def[7]["html-group"] = "Drop-Down boxes";
$miscell_def[7]["appl_description"] = "Default value (no selectable)";
$miscell_def[7]["js_description"] = "Default value for drop-down";
$miscell_def[7]["file_description"] = "
# empty value prefix. This can be used to identify values in select attributes
# that are not allowed to be selected and cause an error \"Please enter your ...\"
# by using a top value that starts with this string, you can make sure that the
# selects do not have a default value, that may be accidentally selected
# eg. \"-- choose your country\"\n";

$miscell_def[8]["name"] = "USE_ADMIN_DETAILS_FOR_MESSAGES";
$miscell_def[8]["default"] = "1";
$miscell_def[8]["installation"] = "ADVANCED";
$miscell_def[8]["commented"] = 0;
$miscell_def[8]["config-type"] = "constant";
$miscell_def[8]["html-entity"] = "boolean";
$miscell_def[8]["appl_description"] = "Do you want to use Admin details for messages?";
$miscell_def[8]["file_description"] = "
# admin details for messages
# if this is enabled phplist will initialise the From in new messages to be the
# details of the logged in administrator who is sending the message
# otherwise it will default to the values set in the configure page that identify
# the From for system messages\n";

$miscell_def[9]["name"] = "SEND_ONE_TESTMAIL";
$miscell_def[9]["default"] = "0";
$miscell_def[9]["installation"] = "ADVANCED";
$miscell_def[9]["commented"] = 0;
$miscell_def[9]["config-type"] = "constant";
$miscell_def[9]["html-entity"] = "boolean";
$miscell_def[9]["appl_description"] = "Do you want send only one test email?";
$miscell_def[9]["file_description"] = "
# test emails
# if you send a test email, phplist will by default send you two emails, one in HTML format
# and the other in Text format. If you set this to 1, you can override this behaviour
# and only have a test email sent to you that matches the user record of the user that the
# test emails are sent to\n";

$parameters[7]["parameters"] = $miscell_def;



//--------------------------------------------------------------------------
//  NINETH GROUP (8)- EXPERIMENTAL FEATURES
//  GROUP STRUCTURE: $experim_def
//--------------------------------------------------------------------------

$parameters[8]["name"] = "Experimental Features\n";
$parameters[8]["file_description"] = "

/*=========================================================================

Experimental Features

=========================================================================*/";

$experim_def[0]["name"] = "USE_LIST_EXCLUDE";
$experim_def[0]["default"] = "0";
$experim_def[0]["installation"] = "ADVANCED";
$experim_def[0]["commented"] = 0;
$experim_def[0]["config-type"] = "constant";
$experim_def[0]["html-entity"] = "boolean";
$experim_def[0]["appl_description"] = "Users witch are in several list must be excluded from a sending?";
$experim_def[0]["file_description"] = "
# list exclude will add the option to send a message to users who are on a list
# except when they are on another list.
# this is currently marked experimental\n";

$experim_def[1]["name"] = "admin_auth_module";
$experim_def[1]["default"] = "/usr/local/etc/auth.inc";
$experim_def[1]["installation"] = "ADVANCED";
$experim_def[1]["commented"] = 1;
$experim_def[1]["config-type"] = "variable";
$experim_def[1]["html-entity"] = "textfield-alfa-30";
$experim_def[1]["html-group"] = "Admin features";
$experim_def[1]["appl_description"] = "Path of Admin Authentication Module|<br>So experimental, be sure before use it!)";
$experim_def[1]["js_description"] = "Path of Admin Authentication Module";
$experim_def[1]["file_description"] = "
# admin authentication module.
# to validate the login for an administrator, you can define your own authentication module
# this is not finished yet, so don't use it unless you're happy to play around with it
# if you have modules to contribute, send them to phplist2@tincan.co.uk
# the default module is phplist_auth.inc, which you can find in the \"auth\" subdirectory of the
# admin directory. It will tell you the functions that need to be defined for phplist to
# retrieve it's information.
# phplist will look for a file in that directory, or you can enter the full path to the file

# eg
#\$admin_auth_module = 'phplist_auth.inc';

# or\n";

$experim_def[2]["name"] = "LANGUAGE_SWITCH";
$experim_def[2]["default"] = "1";
$experim_def[2]["installation"] = "ADVANCED";
$experim_def[2]["commented"] = 0;
$experim_def[2]["config-type"] = "constant";
$experim_def[2]["html-entity"] = "boolean";
$experim_def[2]["html-group"] = "Admin features";
$experim_def[2]["appl_description"] = "Enable admin language?";
$experim_def[2]["file_description"] = "
# admin language
# if you want to disable the language switch for the admin interface (and run all in english)
# set this one to 0\n";


$experim_def[3]["name"] = "STACKED_ATTRIBUTE_SELECTION";
$experim_def[3]["default"] = "0";
$experim_def[3]["installation"] = "ADVANCED";
$experim_def[3]["commented"] = 0;
$experim_def[3]["config-type"] = "constant";
$experim_def[3]["html-entity"] = "boolean";
$experim_def[3]["appl_description"] = "Enable Stacked Attribute Selection?|(Don't use with IE)";
$experim_def[3]["file_description"] = "
# stacked attribute selection
# this is a new method of making a selection of attributes to send your messages to
# to start with, it doesn't seem to work very well in Internet Explorer, but it works fine
# using Mozilla, Firefox, Opera (haven't tried any other browsers)
# so if you use IE, you may not want to try this.

# stacked attribute selection allows you to continuously add a selection of attributes
# to your message. This is quite a bit more powerful than the old method, but it can also
# cause very complex queries to be constructed that may take too long to calculate
# If you want to try this, set the value to 1, and give us feedback on how it's going

# if you want to use dates for attribute selections, you need to use this one\n";


$experim_def[4]["name"] = "REMOTE_URL_REFETCH_TIMEOUT";
$experim_def[4]["default"] = "3600";
$experim_def[4]["installation"] = "ADVANCED";
$experim_def[4]["commented"] = 0;
$experim_def[4]["config-type"] = "constant";
$experim_def[4]["html-entity"] = "textfield-int-5";
$experim_def[4]["appl_description"] = "Time out to refetch remote URL added in your emails|seconds";
$experim_def[4]["js_description"] = "Seconds for timeout";
$experim_def[4]["file_description"] = "
# send a webpage. You can send the contents of a webpage, by adding
# [URL:http://website/file.html] as the content of a message. This can also be personalised
# for users by using eg
# [URL:http://website/file.html?email=[email]]
# the timeout for refetching a URL can be defined here. When the last time a URL has been
# fetched exceeds this time, the URL will be refetched. This is in seconds, 3600 is an hour
# this only affects sending within the same \"process queue\". If a new process queue is started
# the URL will be fetched the first time anyway. Therefore this is only useful is processing
# your queue takes longer than the time identified here.\n";

$experim_def[5]["name"] = "MAILQUEUE_AUTOTHROTTLE";
$experim_def[5]["default"] = "0";
$experim_def[5]["installation"] = "ADVANCED";
$experim_def[5]["commented"] = 0;
$experim_def[5]["config-type"] = "constant";
$experim_def[5]["html-entity"] = "boolean";
$experim_def[5]["appl_description"] = "Auto-define the delay between messages?|<br><i>(to take effect you must disable mail queue throttle)</i>";
$experim_def[5]["file_description"] = "
# Mailqueue autothrottle. This will try to automatically change the delay
# between messages to make sure that the MAILQUEUE_BATCH_SIZE (above) is spread evently over
# MAILQUEUE_BATCH_PERIOD, instead of firing the Batch in the first few minutes of the period
# and then waiting for the next period. This only works with mailqueue_throttle off
# it still needs tweaking, so send your feedback to mantis.tincan.co.uk if you find
# any issues with it\n";

$experim_def[6]["name"] = "CLICKTRACK";
$experim_def[6]["default"] = "0";
$experim_def[6]["installation"] = "ADVANCED";
$experim_def[6]["commented"] = 0;
$experim_def[6]["config-type"] = "constant";
$experim_def[6]["html-entity"] = "boolean";
$experim_def[6]["appl_description"] = "Enable click tracking?";
$experim_def[6]["file_description"] = "
# Click tracking
# If you set this to 1, all links in your emails will be converted to links that
# go via phplist. This will make sure that clicks are tracked. This is experimental and
# all your findings when using this feature should be reported to mantis
# for now it's off by default until we think it works correctly\n";

$experim_def[7]["name"] = "CLICKTRACK_SHOWDETAIL";
$experim_def[7]["default"] = "0";
$experim_def[7]["installation"] = "ADVANCED";
$experim_def[7]["commented"] = 0;
$experim_def[7]["config-type"] = "constant";
$experim_def[7]["html-entity"] = "boolean";
$experim_def[7]["appl_description"] = "Enable click tracking statistics?|<br><i>(This option will slow down the process to view statistics, is recommended let it off)</i>";
$experim_def[7]["file_description"] = "
# Click track, list detail
# if you enable this, you will get some extra statistics about unique users who have clicked the
# links in your messages, and the breakdown between clicks from text or html messages.
# However, this will slow down the process to view the statistics, so it is
# recommended to leave it off, but if you're very curious, you can enable it\n";

$experim_def[8]["name"] = "USE_DOMAIN_THROTTLE";
$experim_def[8]["default"] = "0";
$experim_def[8]["installation"] = "ADVANCED";
$experim_def[8]["commented"] = 0;
$experim_def[8]["config-type"] = "constant";
$experim_def[8]["html-entity"] = "boolean";
$experim_def[8]["html-group"] = "Domain Throttling";
$experim_def[8]["appl_description"] = "Enable Domain Throttling?";
$experim_def[8]["file_description"] = "
# Domain Throttling
# You can activate domain throttling, by setting USE_DOMAIN_THROTTLE to 1
# define the maximum amount of emails you want to allow sending to any domain and the number
# of seconds for that amount. This will make sure you don't send too many emails to one domain
# which may cause blacklisting. Particularly the big ones are tricky about this.
# it may cause a dramatic increase in the amount of time to send a message, depending on how
# many users you have that have the same domain (eg hotmail.com)
# if too many failures for throttling occur, the send process will automatically add an extra
# delay to try to improve that. The example sends 1 message every 2 minutes.\n";

$experim_def[9]["name"] = "DOMAIN_BATCH_SIZE";
$experim_def[9]["default"] = "1";
$experim_def[9]["installation"] = "ADVANCED";
$experim_def[9]["commented"] = 0;
$experim_def[9]["config-type"] = "constant";
$experim_def[9]["html-entity"] = "boolean";
$experim_def[9]["html-group"] = "Domain Throttling";
$experim_def[9]["appl_description"] = "Number of emails per period";
$experim_def[9]["file_description"] = "";

$experim_def[10]["name"] = "DOMAIN_BATCH_PERIOD";
$experim_def[10]["default"] = "120";
$experim_def[10]["installation"] = "ADVANCED";
$experim_def[10]["commented"] = 0;
$experim_def[10]["config-type"] = "constant";
$experim_def[10]["html-entity"] = "textfield-int-4";
$experim_def[10]["html-group"] = "Domain Throttling";
$experim_def[10]["appl_description"] = "Length of each period|in seconds";
$experim_def[10]["js_description"] = "Length of period";
$experim_def[10]["file_description"] = "";

$experim_def[11]["name"] = "DOMAIN_AUTO_THROTTLE";
$experim_def[11]["default"] = "0";
$experim_def[11]["installation"] = "ADVANCED";
$experim_def[11]["commented"] = 0;
$experim_def[11]["config-type"] = "constant";
$experim_def[11]["html-entity"] = "boolean";
$experim_def[11]["html-group"] = "Domain Throttling";
$experim_def[11]["appl_description"] = "Define the period automatically?";
$experim_def[11]["file_description"] = "
# if you have very large numbers of users on the same domains, this may result in the need
# to run processqueue many times, when you use domain throttling. You can also tell phplist
# to simply delay a bit between messages to increase the number of messages sent per queue run
# if you want to use that set this to 1, otherwise simply run the queue many times. A cron
# process every 10 or 15 minutes is recommended.\n";

$experim_def[12]["name"] = "USE_ADVANCED_BOUNCEHANDLING";
$experim_def[12]["default"] = "0";
$experim_def[12]["installation"] = "ADVANCED";
$experim_def[12]["commented"] = 0;
$experim_def[12]["config-type"] = "constant";
$experim_def[12]["html-entity"] = "boolean";
$experim_def[12]["appl_description"] = "Enable advance bounces handling?";
$experim_def[12]["file_description"] = "
# advanced bounce processing
# with advanced bounce handling you are able to define regular expressions that match bounces and the
# action that needs to be taken when an expression matches. This will improve getting rid of bad emails in
# your system, which will be a good thing for making sure you are not being blacklisted by other
# mail systems
# if you use this, you will need to teach your system regularly about patterns in new bounces\n";

$parameters[8]["parameters"] = $experim_def;



//--------------------------------------------------------------------------
//  TENTH GROUP (9)- ADVANCED FEATURES (Part.1)
//  GROUP STRUCTURE: $advanced1_def
//--------------------------------------------------------------------------

$parameters[9]["name"] = "Advanced Features (Part.1)\n";
$parameters[9]["file_description"] = "

/*================================================================================

Advanced Features, HTML editor, RSS, Attachments, Plugins. PDF creation (Part.1)

================================================================================*/";

$advanced1_def[0]["name"] = "HTMLEMAIL_ENCODING";
$advanced1_def[0]["default"] = "quoted-printable";
$advanced1_def[0]["installation"] = "ADVANCED";
$advanced1_def[0]["commented"] = 0;
$advanced1_def[0]["config-type"] = "constant";
$advanced1_def[0]["html-entity"] = "textfield-alfa-20";
$advanced1_def[0]["appl_description"] = "Encoding for HTML message";
$advanced1_def[0]["file_description"] = "
# you can specify the encoding for HTML and plaintext messages here. This only
# works if you do not use the phpmailer (see below)
# the default should be fine. Valid options are 7bit, quoted-printable and base64\n";

$advanced1_def[1]["name"] = "TEXTEMAIL_ENCODING";
$advanced1_def[1]["default"] = "7bit";
$advanced1_def[1]["installation"] = "ADVANCED";
$advanced1_def[1]["commented"] = 0;
$advanced1_def[1]["config-type"] = "constant";
$advanced1_def[1]["html-entity"] = "textfield-alfa-20";
$advanced1_def[1]["appl_description"] = "Encoding for plaintext message";
$advanced1_def[1]["file_description"] = "";

$advanced1_def[2]["name"] = "ENABLE_RSS";
$advanced1_def[2]["default"] = "0";
$advanced1_def[2]["installation"] = "ADVANCED";
$advanced1_def[2]["commented"] = 0;
$advanced1_def[2]["config-type"] = "constant";
$advanced1_def[2]["html-entity"] = "boolean";
$advanced1_def[2]["appl_description"] = "Enable RSS sending?";
$advanced1_def[2]["file_description"] = "
# PHPlist can send RSS feeds to users. Feeds can be sent daily, weekly or
# monthly. To use the feature you need XML support in your PHP installation, and you
# need to set this constant to 1\n";

$advanced1_def[3]["name"] = "MANUALLY_PROCESS_RSS";
$advanced1_def[3]["default"] = "1";
$advanced1_def[3]["installation"] = "ADVANCED";
$advanced1_def[3]["commented"] = 0;
$advanced1_def[3]["config-type"] = "constant";
$advanced1_def[3]["html-entity"] = "boolean";
$advanced1_def[3]["appl_description"] = "Enable manual processing of RSS?";
$advanced1_def[3]["file_description"] = "\n# if you have set up a cron to download the RSS entries, you can set this to be 0\n";

$advanced1_def[4]["name"] = "USEFCK";
$advanced1_def[4]["default"] = "1";
$advanced1_def[4]["installation"] = "ADVANCED";
$advanced1_def[4]["commented"] = 0;
$advanced1_def[4]["config-type"] = "constant";
$advanced1_def[4]["html-entity"] = "boolean";
$advanced1_def[4]["appl_description"] = "Enable FCKeditor?";
$advanced1_def[4]["file_description"] = "
# the FCKeditor is now included in PHPlist, but the use of it is experimental
# if it's not working for you, set this to 0
# NOTE: If you enable TinyMCE please disable FCKeditor and vice-versa.\n";

$advanced1_def[5]["name"] = "FCKIMAGES_DIR";
$advanced1_def[5]["default"] = "uploadimages";
$advanced1_def[5]["installation"] = "ADVANCED";
$advanced1_def[5]["commented"] = 0;
$advanced1_def[5]["config-type"] = "constant";
$advanced1_def[5]["html-entity"] = "textfield-alfa-15";
$advanced1_def[5]["appl_description"] = "Define FCKeditor image upload path";
$advanced1_def[5]["js_description"] = "FCKeditor image upload path";
$advanced1_def[5]["file_description"] = "
# If you want to upload images to the FCKeditor, you need to specify the location
# of the directory where the images go. This needs to be writable by the webserver,
# and it needs to be in your public document (website) area
# the directory is relative to the root of PHPlist as set above
# This is a potential security risk, so read README.security for more information\n";

$advanced1_def[6]["name"] = "USETINYMCEMESG";
$advanced1_def[6]["default"] = "0";
$advanced1_def[6]["installation"] = "ADVANCED";
$advanced1_def[6]["commented"] = 0;
$advanced1_def[6]["config-type"] = "constant";
$advanced1_def[6]["html-entity"] = "boolean";
$advanced1_def[6]["html-group"] = "TinyMCE features";
$advanced1_def[6]["appl_description"] = "Enable TinyMCE?";
$advanced1_def[6]["file_description"] = "
# TinyMCE Support (http://tinymce.moxiecode.com/)
# It is suggested to copy the tinymce/jscripts/tiny_mce directory from the
# standard TinyMCE distribution into the public_html/lists/admin/plugins
# directory in order to keep the install clean.
# NOTE: If you enable TinyMCE please disable FCKeditor and vice-versa.
# Set this to 1 to turn on TinyMCE for writing messages:\n";

$advanced1_def[7]["name"] = "USETINYMCETEMPL";
$advanced1_def[7]["default"] = "0";
$advanced1_def[7]["installation"] = "ADVANCED";
$advanced1_def[7]["commented"] = 0;
$advanced1_def[7]["config-type"] = "constant";
$advanced1_def[7]["html-entity"] = "boolean";
$advanced1_def[7]["html-group"] = "TinyMCE features";
$advanced1_def[7]["appl_description"] = "Enable Templates edition?";
$advanced1_def[7]["file_description"] = "\n# Set this to 1 to turn on TinyMCE for editing templates:\n";

$advanced1_def[8]["name"] = "TINYMCEPATH";
$advanced1_def[8]["default"] = "plugins/tiny_mce/tiny_mce.js";
$advanced1_def[8]["installation"] = "ADVANCED";
$advanced1_def[8]["commented"] = 0;
$advanced1_def[8]["config-type"] = "constant";
$advanced1_def[8]["html-entity"] = "textfield-alfa-23";
$advanced1_def[8]["html-group"] = "TinyMCE features";
$advanced1_def[8]["appl_description"] = "Path to TinyMCE script|<br><i>(related to admin path)</i>";
$advanced1_def[8]["js_description"] = "TinyMCE script path";
$advanced1_def[8]["file_description"] = "\n# Set this to path of the TinyMCE script, relative to the admin directory:\n";

$advanced1_def[9]["name"] = "TINYMCELANG";
$advanced1_def[9]["default"] = "en";
$advanced1_def[9]["installation"] = "ADVANCED";
$advanced1_def[9]["commented"] = 0;
$advanced1_def[9]["config-type"] = "constant";
$advanced1_def[9]["html-entity"] = "textfield-alfa-5";
$advanced1_def[9]["html-group"] = "TinyMCE features";
$advanced1_def[9]["appl_description"] = "Default language";
$advanced1_def[9]["file_description"] = "\n# Set this to the language you wish to use for TinyMCE:\n";

$advanced1_def[10]["name"] = "TINYMCETHEME";
$advanced1_def[10]["default"] = "advanced";
$advanced1_def[10]["installation"] = "ADVANCED";
$advanced1_def[10]["commented"] = 0;
$advanced1_def[10]["config-type"] = "array";
$advanced1_def[10]["html-entity"] = "list-single-simple|default|advanced";
$advanced1_def[10]["html-group"] = "TinyMCE features";
$advanced1_def[10]["appl_description"] = "Default Theme";
$advanced1_def[10]["file_description"] = "\n# Set this to the theme you wish to use.  Default options are: simple, default and advanced.\n";

$advanced1_def[11]["name"] = "TINYMCEOPTS";
$advanced1_def[11]["default"] = "";
$advanced1_def[11]["installation"] = "ADVANCED";
$advanced1_def[11]["commented"] = 0;
$advanced1_def[11]["config-type"] = "constant";
$advanced1_def[11]["html-entity"] = "no-required|textfield-alfa-20";
$advanced1_def[11]["html-group"] = "TinyMCE features";
$advanced1_def[11]["appl_description"] = "Aditional options|<br><i>(option1:\"value\",option2:\"value\",...)";
$advanced1_def[11]["js_description"] = "Aditional options";
$advanced1_def[11]["file_description"] = "
# Set this to any additional options you wish.  Please be careful with this as you can
# inadvertantly break TinyMCE.  Rever to the TinyMCE documentation for full details.
# Should be in the format: \',option1:\"value\",option2:\"value\"\'   <--- note comma at beginning\n";

$parameters[9]["parameters"] = $advanced1_def;



//--------------------------------------------------------------------------
//  ILEVENTH GROUP (10)- ADVANCED FEATURES (Part.2)
//  GROUP STRUCTURE: $advanced2_def
//--------------------------------------------------------------------------

$parameters[10]["name"] = "Advanced Features (Part.2)\n";
$parameters[10]["file_description"] = "

/*================================================================================

Advanced Features, HTML editor, RSS, Attachments, Plugins. PDF creation (Part.2)

================================================================================*/";

$advanced2_def[0]["name"] = "USE_MANUAL_TEXT_PART";
$advanced2_def[0]["default"] = "0";
$advanced2_def[0]["installation"] = "ADVANCED";
$advanced2_def[0]["commented"] = 0;
$advanced2_def[0]["config-type"] = "constant";
$advanced2_def[0]["html-entity"] = "boolean";
$advanced2_def[0]["appl_description"] = "Use a manual textbox to edit messages?";
$advanced2_def[0]["file_description"] = "
# Manual text part, will give you an input box for the text version of the message
# instead of trying to create it by parsing the HTML version into plain text\n";

$advanced2_def[1]["name"] = "ALLOW_ATTACHMENTS";
$advanced2_def[1]["default"] = "0";
$advanced2_def[1]["installation"] = "ADVANCED";
$advanced2_def[1]["commented"] = 0;
$advanced2_def[1]["config-type"] = "constant";
$advanced2_def[1]["html-entity"] = "boolean";
$advanced2_def[1]["appl_description"] = "Allow attachements?";
$advanced2_def[1]["file_description"] = "
# attachments is a new feature and is currently still experimental
# set this to 1 if you want to try it
# caution, message may become very large. it is generally more
# acceptable to send a URL for download to users
# if you try it, it will be appreciated to give feedback to the
# users mailinglist, so we can learn whether it is working ok
# using attachments requires PHP 4.1.0 and up\n";

$advanced2_def[2]["name"] = "NUMATTACHMENTS";
$advanced2_def[2]["default"] = "1";
$advanced2_def[2]["installation"] = "ADVANCED";
$advanced2_def[2]["commented"] = 0;
$advanced2_def[2]["config-type"] = "constant";
$advanced2_def[2]["html-entity"] = "textfield-int-3";
$advanced2_def[2]["appl_description"] = "Attachements per message...";
$advanced2_def[2]["js_description"] = "Number of attachements per message";
$advanced2_def[2]["file_description"] = "
# if you use the above, how many would you want to add per message (max)
# You can leave this 1, even if you want to attach more files, because
# you will be able to add them sequentially\n";

$advanced2_def[3]["name"] = "FILESYSTEM_ATTACHMENTS";
$advanced2_def[3]["default"] = "0";
$advanced2_def[3]["installation"] = "ADVANCED";
$advanced2_def[3]["commented"] = 0;
$advanced2_def[3]["config-type"] = "constant";
$advanced2_def[3]["html-entity"] = "boolean";
$advanced2_def[3]["appl_description"] = "Attach message at real send time?|<br><i>(if not, it will be attached at creation time)</i>";
$advanced2_def[3]["file_description"] = "
# when using attachments you can upload them to the server
# if you want to use attachments from the local filesystem (server) set this to 1
# filesystem attachments are attached at real send time of the message, not at
# the time of creating the message\n";

$advanced2_def[4]["name"] = "MIMETYPES_FILE";
$advanced2_def[4]["default"] = "/etc/mime.types";
$advanced2_def[4]["installation"] = "ADVANCED";
$advanced2_def[4]["commented"] = 0;
$advanced2_def[4]["config-type"] = "constant";
$advanced2_def[4]["html-entity"] = "textfield-alfa-30";
$advanced2_def[4]["html-group"] = "MIME Types";
$advanced2_def[4]["appl_description"] = "Path to MIME Types file";
$advanced2_def[4]["file_description"] = "
# if you add filesystem attachments, you will need to tell PHPlist where your
# mime.types file is.\n";

$advanced2_def[5]["name"] = "DEFAULT_MIMETYPE";
$advanced2_def[5]["default"] = "application/octet-stream";
$advanced2_def[5]["installation"] = "ADVANCED";
$advanced2_def[5]["commented"] = 0;
$advanced2_def[5]["config-type"] = "constant";
$advanced2_def[5]["html-entity"] = "textfield-alfa-30";
$advanced2_def[5]["html-group"] = "MIME Types";
$advanced2_def[5]["appl_description"] = "Default MIME Type|<br><i>(If cannot be determined for a file)</i>";
$advanced2_def[5]["js_description"] = "Default MIME Type";
$advanced2_def[5]["file_description"] = "\n# if a mimetype cannot be determined for a file, specify the default mimetype here:\n";

$advanced2_def[6]["name"] = "export_mimetype";
$advanced2_def[6]["default"] = "application/csv";
$advanced2_def[6]["installation"] = "ADVANCED";
$advanced2_def[6]["commented"] = 0;
$advanced2_def[6]["config-type"] = "variable";
$advanced2_def[6]["html-entity"] = "textfield-alfa-30";
$advanced2_def[6]["html-group"] = "MIME Types";
$advanced2_def[6]["appl_description"] = "";
$advanced2_def[6]["file_description"] = "
# the mime type for the export files. You can try changing this to
# application/vnd.ms-excel to make it open automatically in excel\n";

$advanced2_def[7]["name"] = "PLUGIN_ROOTDIR";
$advanced2_def[7]["default"] = "/home/me/phplistplugins";
$advanced2_def[7]["installation"] = "ADVANCED";
$advanced2_def[7]["commented"] = 0;
$advanced2_def[7]["config-type"] = "constant";
$advanced2_def[7]["html-entity"] = "textfield-alfa-20";
$advanced2_def[7]["appl_description"] = "Path of user Plugins";
$advanced2_def[7]["file_description"] = "
# you can create your own pages to slot into PHPlist and do certain things
# that are more specific to your situation (plugins)
# if you do this, you can specify the directory where your plugins are. It is
# useful to keep this outside the PHPlist system, so they are retained after
# upgrading
# there are some example plugins in the \"plugins\" directory inside the
# admin directory
# this directory needs to be absolute, or relative to the admin directory
# uncomment this one to see the examples in the system (and then comment the one below)
#define(\"PLUGIN_ROOTDIR\",\"plugins\");\n";

$advanced2_def[8]["name"] = "attachment_repository";
$advanced2_def[8]["default"] = "/tmp";
$advanced2_def[8]["installation"] = "ADVANCED";
$advanced2_def[8]["commented"] = 0;
$advanced2_def[8]["config-type"] = "variable";
$advanced2_def[8]["html-entity"] = "textfield-alfa-10";
$advanced2_def[8]["appl_description"] = "Attachements repository";
$advanced2_def[8]["file_description"] = "
# the attachment repository is the place where the files are stored (if you use
# ALLOW_ATTACHMENTS)
# this needs to be writable to your webserver user
# it also needs to be a full path, not a relative one
# for secutiry reasons it is best if this directory is not public (ie below
# your website document root)\n";

$advanced2_def[9]["name"] = "FPDF_FONTPATH";
$advanced2_def[9]["default"] = "/home/pdf/font/";
$advanced2_def[9]["installation"] = "ADVANCED";
$advanced2_def[9]["commented"] = 1;
$advanced2_def[9]["config-type"] = "constant";
$advanced2_def[9]["html-entity"] = "textfield-alfa-10";
$advanced2_def[9]["html-group"] = "FPDF metrics";
$advanced2_def[9]["appl_description"] = "FPDF fonts path";
$advanced2_def[9]["file_description"] = "
# if you want to be able to send your messages as PDF attachments, you need to install
# FPDF (http://www.fpdf.org), and set this variables accordingly:\n";

$advanced2_def[10]["name"] = "fpdf.php";
$advanced2_def[10]["default"] = "";
$advanced2_def[10]["installation"] = "HIDDEN";
$advanced2_def[10]["commented"] = 1;
$advanced2_def[10]["config-type"] = "required";
$advanced2_def[10]["html-group"] = "FPDF metrics";
$advanced2_def[10]["appl_description"] = "";
$advanced2_def[10]["file_description"] = "";

$advanced2_def[11]["name"] = "USE_PDF";
$advanced2_def[11]["default"] = "1";
$advanced2_def[11]["installation"] = "ADVANCED";
$advanced2_def[11]["commented"] = 1;
$advanced2_def[11]["config-type"] = "constant";
$advanced2_def[11]["html-entity"] = "boolean";
$advanced2_def[11]["html-group"] = "FPDF metrics";
$advanced2_def[11]["appl_description"] = "Enable FPDF?";
$advanced2_def[11]["file_description"] = "";

$advanced2_def[12]["name"] = "pdf_font";
$advanced2_def[12]["default"] = "Times";
$advanced2_def[12]["installation"] = "ADVANCED";
$advanced2_def[12]["commented"] = 1;
$advanced2_def[12]["config-type"] = "variable";
$advanced2_def[12]["html-entity"] = "boolean";
$advanced2_def[12]["html-group"] = "FPDF metrics";
$advanced2_def[12]["appl_description"] = "Default PDF Font";
$advanced2_def[12]["file_description"] = "";

$advanced2_def[13]["name"] = "pdf_fontstyle";
$advanced2_def[13]["default"] = "";
$advanced2_def[13]["installation"] = "ADVANCED";
$advanced2_def[13]["commented"] = 1;
$advanced2_def[13]["config-type"] = "variable";
$advanced2_def[13]["html-entity"] = "no-required|textfield-alfa-20";
$advanced2_def[13]["html-group"] = "FPDF metrics";
$advanced2_def[13]["appl_description"] = "PDF font-style";
$advanced2_def[13]["file_description"] = "";

$advanced2_def[14]["name"] = "pdf_fontsize";
$advanced2_def[14]["default"] = "14";
$advanced2_def[14]["installation"] = "ADVANCED";
$advanced2_def[14]["commented"] = 1;
$advanced2_def[14]["config-type"] = "variable";
$advanced2_def[14]["html-entity"] = "textfield-int-20";
$advanced2_def[14]["html-group"] = "FPDF metrics";
$advanced2_def[14]["appl_description"] = "PDF font-size";
$advanced2_def[14]["file_description"] = "";

$parameters[10]["parameters"] = $advanced2_def;



//--------------------------------------------------------------------------
//  TWELVETH GROUP (11)- ADVANCED FEATURES (Part.3)
//  GROUP STRUCTURE: $advanced3_def
//--------------------------------------------------------------------------

$parameters[11]["name"] = "Advanced Features (Part.3)\n";
$parameters[11]["file_description"] = "

/*================================================================================

Advanced Features, HTML editor, RSS, Attachments, Plugins. PDF creation (Part.3)

================================================================================*/";


$advanced3_def[0]["name"] = "EXPORT_EXCEL";
$advanced3_def[0]["default"] = "0";
$advanced3_def[0]["installation"] = "ADVANCED";
$advanced3_def[0]["commented"] = 0;
$advanced3_def[0]["config-type"] = "constant";
$advanced3_def[0]["html-entity"] = "boolean";
$advanced3_def[0]["appl_description"] = "Enable exportation to Excel?";
$advanced3_def[0]["file_description"] = "\n# if you want to use export format optimized for Excel, set this one to 1\n";

$advanced3_def[1]["name"] = "USE_REPETITION";
$advanced3_def[1]["default"] = "0";
$advanced3_def[1]["installation"] = "ADVANCED";
$advanced3_def[1]["commented"] = 0;
$advanced3_def[1]["config-type"] = "constant";
$advanced3_def[1]["html-entity"] = "boolean";
$advanced3_def[1]["appl_description"] = "Enable message repetition?";
$advanced3_def[1]["file_description"] = "
# Repetition. This adds the option to repeat the same message in the future.
# After the message has been sent, this option will cause the system to automatically
# create a new message with the same content. Be careful with it, because you may
# send the same message to your users
# the embargo of the message will be increased with the repetition interval you choose
# also read the README.repetition for more info\n";

$advanced3_def[2]["name"] = "USE_PREPARE";
$advanced3_def[2]["default"] = "0";
$advanced3_def[2]["installation"] = "ADVANCED";
$advanced3_def[2]["commented"] = 0;
$advanced3_def[2]["config-type"] = "constant";
$advanced3_def[2]["html-entity"] = "boolean";
$advanced3_def[2]["appl_description"] = "Enable Admin prepared message?|<br><i>(is recomended leave it off)</i>";
$advanced3_def[2]["file_description"] = "
# Prepare a message. This system allows you to create messages as a super admin
# that can then be reviewed and selected by sub admins to send to their own lists
# it is old functionality that is quite confusing, and therefore by default it
# is now off. If you used to use it, you can switch it on here. If you did not
# use it, or are a new user, it is better to leave it off. It has nothing to
# do with being able to edit messages.\n";

$advanced3_def[3]["name"] = "KEEPFORWARDERATTRIBUTES";
$advanced3_def[3]["default"] = "0";
$advanced3_def[3]["installation"] = "ADVANCED";
$advanced3_def[3]["commented"] = 0;
$advanced3_def[3]["config-type"] = "constant";
$advanced3_def[3]["html-entity"] = "boolean";
$advanced3_def[3]["html-group"] = "Forward features";
$advanced3_def[3]["appl_description"] = "Enable 'forward to a friend'?";
$advanced3_def[3]["file_description"] = "
#0011857: forward to friend, retain attributes
# When forwarding ('to a friend') the message will be using the attributes of the destination email by default.
# This often means the message gets stripped of al its attributes. 
# When setting this constant to 1, the message will use the attributes of the forwarding user. It can be used
# to connect the destinatory to the forwarder and/or reward the forwarder.\n";

$advanced3_def[4]["name"] = "FORWARD_EMAIL_COUNT";
$advanced3_def[4]["default"] = "1";
$advanced3_def[4]["installation"] = "ADVANCED";
$advanced3_def[4]["commented"] = 0;
$advanced3_def[4]["config-type"] = "constant";
$advanced3_def[4]["html-entity"] = "textfield-int-3";
$advanced3_def[4]["html-group"] = "Forward features";
$advanced3_def[4]["appl_description"] = "Number of addresses to send";
$advanced3_def[4]["file_description"] = "
#0011860: forward to friend, multiple emails
# This setting defines howmany email addresses you can enter in the forward page.
# Default is 1 to not change behaviour from previous version.\n";

$advanced3_def[5]["name"] = "FORWARD_PERSONAL_NOTE_SIZE";
$advanced3_def[5]["default"] = "0";
$advanced3_def[5]["installation"] = "ADVANCED";
$advanced3_def[5]["commented"] = 0;
$advanced3_def[5]["config-type"] = "constant";
$advanced3_def[5]["html-entity"] = "textfield-int-3";
$advanced3_def[5]["html-group"] = "Forward features";
$advanced3_def[5]["appl_description"] = "Personal-note size|<i>0 turn off it - 500 is recomended</i>";
$advanced3_def[5]["js_description"] = "Personal-note size";
$advanced3_def[5]["file_description"] = "
#0011996: forward to friend - personal message
# Allow user to add a personal note when forwarding 'to a friend'
# 0 will turn this option off. default is 0 to not change behaviour from previous version.
# 500 is recommended as a sound value to write a little introductory note to a friend
#The note is prepeded to both text and html messages and will be stripped of all html \n";

$advanced3_def[6]["name"] = "FORWARD_ALTERNATIVE_CONTENT";
$advanced3_def[6]["default"] = "0";
$advanced3_def[6]["installation"] = "ADVANCED";
$advanced3_def[6]["commented"] = 0;
$advanced3_def[6]["config-type"] = "constant";
$advanced3_def[6]["html-entity"] = "boolean";
$advanced3_def[6]["html-group"] = "Forward features";
$advanced3_def[6]["appl_description"] = "Enable alternative content?";
$advanced3_def[6]["file_description"] = "
#0013076: different content when forwarding 'to a friend'
# Allow admin to enter a different message that will be sent when forwarding 'to a friend'
# This will show an extra tab in the message dialog. \n";

$advanced3_def[7]["name"] = "FORWARD_FRIEND_COUNT_ATTRIBUTE";
$advanced3_def[7]["default"] = "FriendCount";
$advanced3_def[7]["installation"] = "ADVANCED";
$advanced3_def[7]["commented"] = 1;
$advanced3_def[7]["config-type"] = "constant";
$advanced3_def[7]["html-entity"] = "no-required|textfield-alfa-10";
$advanced3_def[7]["html-group"] = "Forward features";
$advanced3_def[7]["appl_description"] = "Count of succesfull handovers";
$advanced3_def[7]["js_description"] = "Count of handovers";
$advanced3_def[7]["file_description"] = "
#0013845 Lead Ref Scheme
# When this setting has a value <> \'\' all succesfull handovers to the MTA will be counted
# and saved in the attribute with the name of this setting.\n";

$advanced3_def[8]["name"] = "PHPMAILER";
$advanced3_def[8]["default"] = "1";
$advanced3_def[8]["installation"] = "ADVANCED";
$advanced3_def[8]["commented"] = 0;
$advanced3_def[8]["config-type"] = "constant";
$advanced3_def[8]["html-entity"] = "boolean";
$advanced3_def[8]["html-group"] = "PHPMAILER features";
$advanced3_def[8]["appl_description"] = "You want to use PHPMailer class?";
$advanced3_def[8]["file_description"] = "
# If you want to use the PHPMailer class from https://github.com/PHPMailer/PHPMailer, set the following
# to 1. If you tend to send out html emails, it is recommended to do so.\n";

$advanced3_def[9]["name"] = "PHPMAILERHOST";
$advanced3_def[9]["default"] = "";
$advanced3_def[9]["installation"] = "ADVANCED";
$advanced3_def[9]["commented"] = 0;
$advanced3_def[9]["config-type"] = "constant";
$advanced3_def[9]["html-entity"] = "no-required|textfield-alfa-20";
$advanced3_def[9]["html-group"] = "PHPMAILER features";
$advanced3_def[9]["appl_description"] = "SMTP host (If you want use one)|<br><i>(leave it empty to use the default host)</i>";
$advanced3_def[9]["file_description"] = "
# To use a SMTP please give your server hostname here, leave it blank to use the standard
# PHP mail() command.\n";

$advanced3_def[10]["name"] = "phpmailer_smtpuser";
$advanced3_def[10]["default"] = "smtpuser";
$advanced3_def[10]["installation"] = "HIDDEN";
$advanced3_def[10]["commented"] = 1;
$advanced3_def[10]["config-type"] = "variable";
$advanced3_def[10]["html-entity"] = "textfield-alfa-20";
$advanced3_def[10]["html-group"] = "PHPMAILER features";
$advanced3_def[10]["appl_description"] = "SMTP user";
$advanced3_def[10]["file_description"] = "
# if you want to use smtp authentication when sending the email uncomment the following
# two lines and set the username and password to be the correct ones\n";

$advanced3_def[11]["name"] = "phpmailer_smtppassword";
$advanced3_def[11]["default"] = "smtpassword";
$advanced3_def[11]["installation"] = "HIDDEN";
$advanced3_def[11]["commented"] = 1;
$advanced3_def[11]["config-type"] = "variable";
$advanced3_def[11]["html-entity"] = "textfield-pass-20";
$advanced3_def[11]["html-group"] = "PHPMAILER features";
$advanced3_def[11]["appl_description"] = "SMTP password";
$advanced3_def[11]["file_description"] = "";

$advanced3_def[12]["name"] = "tmpdir";
$advanced3_def[12]["default"] = "/tmp";
$advanced3_def[12]["installation"] = "ADVANCED";
$advanced3_def[12]["commented"] = 0;
$advanced3_def[12]["config-type"] = "variable";
$advanced3_def[12]["html-entity"] = "textfield-alfa-20";
$advanced3_def[12]["appl_description"] = "Temp directory";
$advanced3_def[12]["file_description"] = "
# tmpdir. A location where phplist can write some temporary files if necessary
# Make sure it is writable by your webserver user, and also check that you have
# open_basedir set to allow access to this directory. Linux users can leave it as it is.
# this directory is used for all kinds of things, mostly uploading of files (like in
# import), creating PDFs and more\n";

$parameters[11]["parameters"] = $advanced3_def;
 


//--------------------------------------------------------------------------
//  TIRTHEENTH GROUP (12)- ADVANCED FEATURES (Part.4)
//  GROUP STRUCTURE: $advanced3_def
//--------------------------------------------------------------------------

$parameters[12]["name"] = "Advanced Features (Part.4)\n";
$parameters[12]["file_description"] = "

/*================================================================================

Advanced Features, HTML editor, RSS, Attachments, Plugins. PDF creation (Part.4)

================================================================================*/";


$advanced4_def[0]["name"] = "form_action";
$advanced4_def[0]["default"] = "index.php";
$advanced4_def[0]["installation"] = "HIDDEN";
$advanced4_def[0]["commented"] = 1;
$advanced4_def[0]["config-type"] = "variable";
$advanced4_def[0]["html-entity"] = "textfield-alfa-30";
$advanced4_def[0]["appl_description"] = "Form action";
$advanced4_def[0]["file_description"] = "
# if you are on Windoze, and/or you are not using apache, in effect when you are getting
# \"Method not allowed\" errors you will want to uncomment this
# ie take off the #-character in the next line
# using this is not guaranteed to work, sorry. Easier to use Apache instead :-)\n";

$advanced4_def[1]["name"] = "database_module";
$advanced4_def[1]["default"] = "mysql.inc";
$advanced4_def[1]["installation"] = "ADVANCED";
$advanced4_def[1]["commented"] = 0;
$advanced4_def[1]["config-type"] = "variable";
$advanced4_def[1]["html-entity"] = "textfield-alfa-15";
$advanced4_def[1]["appl_description"] = "DataBase module to use";
$advanced4_def[1]["file_description"] = "
# select the database module to use
# anyone wanting to submit other database modules is
# very welcome!\n";

$advanced4_def[2]["name"] = "SessionTableName";
$advanced4_def[2]["default"] = "phplistsessions";
$advanced4_def[2]["installation"] = "HIDDEN";
$advanced4_def[2]["commented"] = 1;
$advanced4_def[2]["config-type"] = "variable";
$advanced4_def[2]["html-entity"] = "textfield-alfa-30";
$advanced4_def[2]["appl_description"] = "Table to store sessions...";
$advanced4_def[2]["js_description"] = "Session table";
$advanced4_def[2]["file_description"] = "
# you can store sessions in the database instead of the default place by assigning
# a tablename to this value. The table will be created and will not use any prefixes
# this only works when using mysql and only for administrator sessions\n";

$advanced4_def[3]["name"] = "database_module";
$advanced4_def[3]["default"] = "adodb.inc";
$advanced4_def[3]["installation"] = "HIDDEN";
$advanced4_def[3]["commented"] = 1;
$advanced4_def[3]["config-type"] = "variable";
$advanced4_def[3]["html-entity"] = "textfield-alfa-30";
$advanced4_def[3]["appl_description"] = "";
$advanced4_def[3]["file_description"] = "
# there is now support for the use of ADOdb http://php.weblogs.com/ADODB
# this is still experimental, and any findings should be reported in the
# bugtracker
# in order to use it, define the following settings:\n";

$advanced4_def[4]["name"] = "adodb_inc_file";
$advanced4_def[4]["default"] = "/path/to/adodb_inc.php";
$advanced4_def[4]["installation"] = "HIDDEN";
$advanced4_def[4]["commented"] = 1;
$advanced4_def[4]["config-type"] = "variable";
$advanced4_def[4]["html-entity"] = "textfield-alfa-30";
$advanced4_def[4]["appl_description"] = "";
$advanced4_def[4]["file_description"] = "";

$advanced4_def[5]["name"] = "adodb_driver";
$advanced4_def[5]["default"] = "mysql";
$advanced4_def[5]["installation"] = "HIDDEN";
$advanced4_def[5]["commented"] = 1;
$advanced4_def[5]["config-type"] = "variable";
$advanced4_def[5]["html-entity"] = "textfield-alfa-10";
$advanced4_def[5]["appl_description"] = "";
$advanced4_def[5]["file_description"] = "";

$advanced4_def[6]["name"] = "error_level";
$advanced4_def[6]["default"] = "error_reporting(0)";
$advanced4_def[6]["installation"] = "ADVANCED";
$advanced4_def[6]["commented"] = 0;
$advanced4_def[6]["config-type"] = "call";
$advanced4_def[6]["html-entity"] = "textfield-alfa-15";
$advanced4_def[6]["appl_description"] = "Define the Error level";
$advanced4_def[6]["file_description"] = "\n# if you want more trouble, make this 63 (very unlikely you will like the result)\n";

$parameters[12]["parameters"] = $advanced4_def;
?>
