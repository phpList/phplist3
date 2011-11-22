<?php

/*

=========================================================================

General settings for language and database

=========================================================================

*/


# select the language module to use
# Look for <country>.inc files in the texts directory
# to find your language
# this is the language for the frontend pages. In the admin pages you can
# choose your language by using the dropdown in the pages.
$language_module = "english.inc";

# what is your Mysql database server
$database_host = "localhost";

# what is the name of the database we are using
$database_name = "phplistdb";

# who do we log in as?
$database_user = "phplist";

# and what password do we use
$database_password = 'phplist';

# if you use multiple installations of PHPlist you can set this to
# something to identify this one. it will be prepended to email report
# subjects
$installation_name = 'PHPlist';

# if you want a prefix to all your tables, specify it here,
$table_prefix = "phplist_";

# if you want to use a different prefix to user tables, specify it here.
# read README.usertables for more information
$usertable_prefix = "phplist_user_";

# if you change the path to the PHPlist system, make the change here as well
# path should be relative to the root directory of your webserver (document root)
# you cannot actually change the "admin", but you can change the "lists"
$pageroot = '/lists';
$adminpages = '/lists/admin';

/*

=========================================================================

Settings for handling bounces

=========================================================================

*/

# Message envelope. This is the email that system messages come from
# it is useful to make this one where you can process the bounces on
# you will probably get a X-Authentication-Warning in your message
# when using this with sendmail
# NOTE: this is *very* different from the From: line in a message
# to use this feature, uncomment the following line, and change the email address
# to some existing account on your system
# requires PHP version > "4.0.5" and "4.3.1+" without safe_mode
# $message_envelope = 'listbounces@yourdomain';

# Handling bounces. Check README.bounces for more info
# This can be 'pop' or 'mbox'
$bounce_protocol = 'pop';

# set this to 0, if you set up a cron to download bounces regularly by using the
# commandline option. If this is 0, users cannot run the page from the web
# frontend. Read README.commandline to find out how to set it up on the
# commandline
define ("MANUALLY_PROCESS_BOUNCES",1);

# when the protocol is pop, specify these three
$bounce_mailbox_host = 'localhost';
$bounce_mailbox_user = 'popuser';
$bounce_mailbox_password = 'password';

# the "port" is the remote port of the connection to retrieve the emails
# the default should be fine but if it doesn't work, you can try the second
# one. To do that, add a # before the first line and take off the one before the
# second line

$bounce_mailbox_port = "110/pop3/notls";
#$bounce_mailbox_port = "110/pop3";

# when the protocol is mbox specify this one
# it needs to be a local file in mbox format, accessible to your webserver user
$bounce_mailbox = '/var/spool/mail/listbounces';

# set this to 0 if you want to keep your messages in the mailbox. this is potentially
# a problem, because bounces will be counted multiple times, so only do this if you are
# testing things.
$bounce_mailbox_purge = 1;

# set this to 0 if you want to keep unprocessed messages in the mailbox. Unprocessed
# messages are messages that could not be matched with a user in the system
# messages are still downloaded into PHPlist, so it is safe to delete them from
# the mailbox and view them in PHPlist
$bounce_mailbox_purge_unprocessed = 1;

# how many bounces in a row need to have occurred for a user to be marked unconfirmed
$bounce_unsubscribe_threshold = 5;


/*

=========================================================================

Security related settings

=========================================================================

*/

# set this to 1 if you want PHPlist to deal with login for the administrative
# section of the system
# you will be able to add administrators who control their own lists
# default login is "admin" with password "phplist"
#
$require_login = 1;

# if you use login, how many lists can be created per administrator
define("MAXLIST",1);

# if you use commandline, you will need to identify the users who are allowed to run
# the script. See README.commandline for more info
$commandline_users = array("admin");
# or you can use the following to disable the check (take off the # in front of the line)
# $commandline_users = array();

# as of version 2.4.1, you can have your users define a password for themselves as well
# this will cause some public pages to ask for an email and a password when the password is
# set for the user. If you want to activate this functionality, set the following
# to 1. See README.passwords for more information
define("ASKFORPASSWORD",0);

# if you also want to force people who unsubscribe to provide a password before
# processing their unsubscription, set this to 1. You need to have the above one set
# to 1 for this to have an effect
define("UNSUBSCRIBE_REQUIRES_PASSWORD",0);

# if a user should immediately be unsubscribed, when using their personal URL, instead of
# the default way, which will ask them for a reason, set this to 1
define("UNSUBSCRIBE_JUMPOFF",0);

# when a user unsubscribes they are sent one final email informing them of
# their unsubscription. In order for that email to actually go out, a gracetime
# needs to be set otherwise it will never go out. The default of 5 minutes should
# be fine, but you can increase it if you experience problems
$blacklist_gracetime = 5;

# to increase security the session of a user is checked for the IP address
# this needs to be the same for every request. This may not work with
# network situations where you connect via multiple proxies, so you can
# switch off the checking by setting this to 0
define("CHECK_SESSIONIP",1);

# if you use passwords, you can store them encrypted or in plain text
# if you want to encrypt them, set this one to 1
# if you use encrypted passwords, users can only request you as an administrator to
# reset the password. They will not be able to request the password from
# the system
define("ENCRYPTPASSWORD",0);

# Check for host of email entered for subscription
# Do not use it if your server is not 24hr online
# make the 0 a 1, if you want to use it
$check_for_host = 0;

/*

=========================================================================

Debugging and informational

=========================================================================

*/


# if test is true (not 0) it will not actually send ANY messages,
# but display what it would have sent
define ("TEST",1);

# if you set verbose to 1, it will show the messages that will be sent. Do not do this
# if you have a lot of users, because it is likely to crash your browser
# (it does mine, Mozilla 0.9.2, well 1.6 now, but I would still keep it off :-)
define ("VERBOSE",0);

# some warnings may show up about your PHP settings. If you want to get rid of them
# set this value to 0
define ("WARN_ABOUT_PHP_SETTINGS",1);

# If you set up your system to send the message automatically, you can set this value
# to 0, so "Process Queue" will disappear from the site
# this will also stop users from loading the page on the web frontend, so you will
# have to make sure that you run the queue from the commandline
# check README.commandline how to do this
define ("MANUALLY_PROCESS_QUEUE",1);

# if you want to use \r\n for formatting messages set the 0 to 1
# see also http://www.securityfocus.com/archive/1/255910
# this is likely to break things for other mailreaders, so you should
# only use it if all your users have Outlook (not Express)
define("WORKAROUND_OUTLOOK_BUG",0);

# user history system info.
# when logging the history of a user, you can specify which system variables you
# want to log. These are the ones that are found in the $_SERVER and the $_ENV
# variables of PHP. check http://www.php.net/manual/en/language.variables.predefined.php
# the values are different per system, but these ones are quite common.
$userhistory_systeminfo = array(
  'HTTP_USER_AGENT',
  'HTTP_REFERER',
  'REMOTE_ADDR'
);

# add spamblock
# if you set this to 1, phplist will try to check if the subscribe attempt is a spambot trying to send
# nonsense. If you think this doesn't work, set this to 0
# this is currently only implemented on the subscribe pages
define('USE_SPAM_BLOCK',1);

# notify spam
# when phplist detects a possible spam attack, it can send you a notification about it
# you can check for a while to see if the spam check was correct and if so, set this value
# to 0, if you think the check does it's job correctly.
# it will only send you emails if you have "Does the admin get copies of subscribe, update and unsubscribe messages"
# in the configuration set to true
define('NOTIFY_SPAM',1);

/*

=========================================================================

Feedback to developers

=========================================================================

*/

# use Register to "register" to PHPlist.com. Once you set TEST to 0, the system will then
# request the "Powered By" image from www.phplist.com, instead of locally. This will give me
# a little bit of an indication of how much it is used, which will encourage me to continue
# developing PHPlist. If you do not like this, set Register to 0.
define ("REGISTER",1);

# CREDITS
# We request you retain some form of credits on the public elements of
# PHPlist. These are the subscribe pages and the emails.
# This not only gives respect to the large amount of time given freely
# by the developers but also helps build interest, traffic and use of
# PHPlist, which is beneficial to future developments.
# By default the webpages and the HTML emails will include an image and
# the text emails will include a powered by line

# If you want to remove the image from the HTML emails, set this constant
# to be 1, the HTML emails will then only add a line of text as signature
define("EMAILTEXTCREDITS",0);

# if you want to also remove the image from your public webpages
# set the next one to 1, and the pages will only include a line of text
define("PAGETEXTCREDITS",0);

# in order to get some feedback about performance, PHPlist can send statistics to a central
# email address. To de-activate this set the following value to 1
define ("NOSTATSCOLLECTION",0);

# this is the email it will be sent to. You can leave the default, or you can set it to send
# to your self. If you use the default you will give me some feedback about performance
# which is useful for me for future developments
# $stats_collection_address = 'phplist-stats@phplist.com';

/*

=========================================================================

Miscellaneous

=========================================================================

*/


# the number of criterias you want to be able to select when sending a message.
# Useful is is to make it the same as the number of selectable attributes you enter in the
# system, but that is up to you (selectable = select, radio or checkbox)
define ("NUMCRITERIAS",2);

# if you do not require users to actually sign up to lists, but only want to
# use the subscribe page as a kind of registration system, you can set this to 1 and
# users will not receive an error when they do not check a list to subscribe to
define("ALLOW_NON_LIST_SUBSCRIBE",0);

# batch processing
# if you are on a shared host, it will probably be appreciated if you don't send
# out loads of emails in one go. To do this, you can configure batch processing.
# Please note, the following two values can be overridden by your ISP by using
# a server wide configuration. So if you notice these values to be different
# in reality, that may be the case

# define the amount of emails you want to send per period. If 0, batch processing
# is disabled and messages are sent out as fast as possible
define("MAILQUEUE_BATCH_SIZE",0);

# define the length of one batch processing period, in seconds (3600 is an hour)
define("MAILQUEUE_BATCH_PERIOD",3600); 

# to avoid overloading the server that sends your email, you can add a little delay
# between messages that will spread the load of sending
# you will need to find a good value for your own server
# value is in seconds (or you can play with the autothrottle below)
define('MAILQUEUE_THROTTLE',0); 

# year ranges. If you use dates, by default the drop down for year will be from
# three years before until 10 years after this the current value for year. If there
# is no current value the current year will be used.
# if you want to use a bigger range you can set the start and end year here
# be aware that the drop down may become very large.
# if set to 0 they will use the default behaviour. So I'm afraid you can't start with
# year 0. Also be aware not to set the end year to something relatively soon in the
# future, or it will stop working when you reach that year.
define('DATE_START_YEAR',0);
define('DATE_END_YEAR',0);

# empty value prefix. This can be used to identify values in select attributes
# that are not allowed to be selected and cause an error "Please enter your ..."
# by using a top value that starts with this string, you can make sure that the
# selects do not have a default value, that may be accidentally selected
# eg. "-- choose your country"
define('EMPTY_VALUE_PREFIX','--');

# admin details for messages
# if this is enabled phplist will initialise the From in new messages to be the
# details of the logged in administrator who is sending the message
# otherwise it will default to the values set in the configure page that identify
# the From for system messages
define('USE_ADMIN_DETAILS_FOR_MESSAGES',1);

# test emails
# if you send a test email, phplist will by default send you two emails, one in HTML format
# and the other in Text format. If you set this to 1, you can override this behaviour
# and only have a test email sent to you that matches the user record of the user that the
# test emails are sent to
define('SEND_ONE_TESTMAIL',0);

# when using the import2 option you can set this to allow phplist handle bigger files
# server restrictions might apply too though, so you must be careful and check with your sysadmin first
# the value here is in megabytes, so 64 will be 64MB
# define('IMPORT_FILESIZE',64);

/*

=========================================================================

Experimental Features

=========================================================================

*/

# email address validation level
# 0 = No email address validation. So PHPList can be used as a non-email-sending registration system.
# 1 = phplist 2.10.4 style email validation.
# 2 = RFC821 email validation without escaping and quoting of local part.
# 3 = RFC821 email validation.
# This is an expirimental email address validation based on the original RFC. It will validate all kind 
# of 'weird' emails like !#$%&'*+-/=.?^_`{|}~@example.com and escaped\ spaces\ are\ allowed@[1.0.0.127]
# not implemented are:    
#   Length of domainPart is not checked
#   Not accepted are CR and LF even if escaped by \
#   Not accepted is Folding
#   Not accepted is literal domain-part (eg. [1.0.0.127])
#   Not accepted is comments (eg. (this is a comment)@example.com)
# Extra:
#   topLevelDomain can only be one of the defined ones
define("EMAIL_ADDRESS_VALIDATION_LEVEL",1);

# list exclude will add the option to send a message to users who are on a list
# except when they are on another list.
# this is currently marked experimental

define("USE_LIST_EXCLUDE",0);

# admin authentication module.
# to validate the login for an administrator, you can define your own authentication module
# this is not finished yet, so don't use it unless you're happy to play around with it
# if you have modules to contribute, open a tracker issue at http://mantis.phplist.com
# the default module is phplist_auth.inc, which you can find in the "auth" subdirectory of the
# admin directory. It will tell you the functions that need to be defined for phplist to
# retrieve it's information.
# phplist will look for a file in that directory, or you can enter the full path to the file

# eg
#$admin_auth_module = 'phplist_auth.inc';

# or
#$admin_auth_module = '/usr/local/etc/auth.inc';

# stacked attribute selection
# this is a new method of making a selection of attributes to send your messages to
# to start with, it doesn't seem to work very well in Internet Explorer, but it works fine
# using Mozilla, Firefox, Opera (haven't tried any other browsers)
# so if you use IE, you may not want to try this.

# stacked attribute selection allows you to continuously add a selection of attributes
# to your message. This is quite a bit more powerful than the old method, but it can also
# cause very complex queries to be constructed that may take too long to calculate
# If you want to try this, set the value to 1, and give us feedback on how it's going

# if you want to use dates for attribute selections, you need to use this one

define("STACKED_ATTRIBUTE_SELECTION",0);


# send a webpage. You can send the contents of a webpage, by adding
# [URL:http://website/file.html] as the content of a message. This can also be personalised
# for users by using eg
# [URL:http://website/file.html?email=[email]]
# the timeout for refetching a URL can be defined here. When the last time a URL has been
# fetched exceeds this time, the URL will be refetched. This is in seconds, 3600 is an hour
# this only affects sending within the same "process queue". If a new process queue is started
# the URL will be fetched the first time anyway. Therefore this is only useful is processing
# your queue takes longer than the time identified here.
define('REMOTE_URL_REFETCH_TIMEOUT',3600);

# Mailqueue autothrottle. This will try to automatically change the delay
# between messages to make sure that the MAILQUEUE_BATCH_SIZE (above) is spread evently over
# MAILQUEUE_BATCH_PERIOD, instead of firing the Batch in the first few minutes of the period
# and then waiting for the next period. This only works with mailqueue_throttle off
# it still needs tweaking, so send your feedback to mantis.tincan.co.uk if you find
# any issues with it
define('MAILQUEUE_AUTOTHROTTLE',1); //Changed for test. (old value = 0)

# Click tracking
# If you set this to 1, all links in your emails will be converted to links that
# go via phplist. This will make sure that clicks are tracked. This is experimental and
# all your findings when using this feature should be reported to mantis
# for now it's off by default until we think it works correctly
define('CLICKTRACK',0);

# Click track, list detail
# if you enable this, you will get some extra statistics about unique users who have clicked the
# links in your messages, and the breakdown between clicks from text or html messages.
# However, this will slow down the process to view the statistics, so it is
# recommended to leave it off, but if you're very curious, you can enable it
define('CLICKTRACK_SHOWDETAIL',0);

# Click track link map
# if you want the links in your emails to look a bit more professional, you can set the click track
# link map. If you do this, you will need to add a RewriteRule in your Apache config, which maps this
# back to the original lt.php
# it's quite useful to keep links short in the emails, particularly text emails
# basically the effect is that /lists/lt.php?id=XYX is changed to /lt/XYZ
# if for example your rewrite rule is:
# RewriteRule   ^/lt/(.*)$ /lists/lt.php?id=$1 [PT]
# more info at http://www.google.com/search?q=mod_rewrite (phplist docs to follow at some point)
#define('CLICKTRACK_LINKMAP','/lt/');

# Add Usertrack
# tracking opens now seems fairly common, however flawed it still is. Set this option to 1
# to always add [USERTRACK] to any message being sent out, even if someone forgot to add it to
# the template, footer or message body
define('ALWAYS_ADD_USERTRACK',0);

# Domain Throttling
# You can activate domain throttling, by setting USE_DOMAIN_THROTTLE to 1
# define the maximum amount of emails you want to allow sending to any domain and the number
# of seconds for that amount. This will make sure you don't send too many emails to one domain
# which may cause blacklisting. Particularly the big ones are tricky about this.
# it may cause a dramatic increase in the amount of time to send a message, depending on how
# many users you have that have the same domain (eg hotmail.com)
# if too many failures for throttling occur, the send process will automatically add an extra
# delay to try to improve that. The example sends 1 message every 2 minutes.

define('USE_DOMAIN_THROTTLE',0);
define('DOMAIN_BATCH_SIZE',1);
define('DOMAIN_BATCH_PERIOD',120);

# if you have very large numbers of users on the same domains, this may result in the need
# to run processqueue many times, when you use domain throttling. You can also tell phplist
# to simply delay a bit between messages to increase the number of messages sent per queue run
# if you want to use that set this to 1, otherwise simply run the queue many times. A cron
# process every 10 or 15 minutes is recommended.
define('DOMAIN_AUTO_THROTTLE',0);

# admin language
# if you want to disable the language switch for the admin interface (and run all in english)
# set this one to 0
define('LANGUAGE_SWITCH',1);

# advanced bounce processing
# with advanced bounce handling you are able to define regular expressions that match bounces and the
# action that needs to be taken when an expression matches. This will improve getting rid of bad emails in
# your system, which will be a good thing for making sure you are not being blacklisted by other
# mail systems
# if you use this, you will need to teach your system regularly about patterns in new bounces
define('USE_ADVANCED_BOUNCEHANDLING',0);


/*

=========================================================================

Advanced Features, HTML editor, Attachments, Plugins. PDF creation

=========================================================================

*/

# you can specify the encoding for HTML and plaintext messages here. This only
# works if you do not use the phpmailer (see below)
# the default should be fine. Valid options are 7bit, quoted-printable and base64
define("HTMLEMAIL_ENCODING","quoted-printable");
define("TEXTEMAIL_ENCODING",'7bit');

//##### rssmanager plugin #####
//obsolete, moved to rssmanager plugin 
//# PHPlist can send rss feeds to users. Feeds can be sent daily, weekly or
//# monthly. To use the feature you need XML support in your PHP installation, and you
//# need to set this constant to 1
//define("ENABLE_RSS",0);

# if you have set up a cron to download the RSS entries, you can set this to be 0
define("MANUALLY_PROCESS_RSS",1);

# the FCKeditor is now included in PHPlist, but the use of it is experimental
# if it's not working for you, set this to 0
# NOTE: If you enable TinyMCE please disable FCKeditor and vice-versa.
define("USEFCK",1);

# If you want to upload images to the FCKeditor, you need to specify the location
# of the directory where the images go. This needs to be writable by the webserver,
# and it needs to be in your public document (website) area
# the directory is relative to the root of PHPlist as set above
# This is a potential security risk, so read README.security for more information
define("FCKIMAGES_DIR","uploadimages");

# alternatively, you can set UPLOADIMAGES_DIR, which will take precedence over the FCKIMAGES_DIR
# and it's location will need to be in the document root of your website, instead of in the 
# phplist root. To use this, comment out the following line, and set it to a directory in your
# website document root, that is writable by your webserver user
#define("UPLOADIMAGES_DIR","uploadimages");

# TinyMCE Support (http://tinymce.moxiecode.com/)
# It is suggested to copy the tinymce/jscripts/tiny_mce directory from the
# standard TinyMCE distribution into the public_html/lists/admin/plugins
# directory in order to keep the install clean.
# NOTE: If you enable TinyMCE please disable FCKeditor and vice-versa.
# Set this to 1 to turn on TinyMCE for writing messages:
define("USETINYMCEMESG", 0);
# Set this to 1 to turn on TinyMCE for editing templates:
define("USETINYMCETEMPL", 0);
# Set this to path of the TinyMCE script, relative to the admin directory:
define("TINYMCEPATH", "plugins/tiny_mce/tiny_mce.js");
# Set this to the language you wish to use for TinyMCE:
define("TINYMCELANG", "en");
# Set this to the theme you wish to use.  Default options are: simple, default and advanced.
define("TINYMCETHEME", "advanced");
# Set this to any additional options you wish.  Please be careful with this as you can
# inadvertantly break TinyMCE.  Rever to the TinyMCE documentation for full details.
# Should be in the format: ',option1:"value",option2:"value"'   <--- note comma at beginning
define("TINYMCEOPTS", "");

# Manual text part, will give you an input box for the text version of the message
# instead of trying to create it by parsing the HTML version into plain text
define("USE_MANUAL_TEXT_PART",0);

# attachments is a new feature and is currently still experimental
# set this to 1 if you want to try it
# caution, message may become very large. it is generally more
# acceptable to send a URL for download to users
# if you try it, it will be appreciated to give feedback to the
# users mailinglist, so we can learn whether it is working ok
# using attachments requires PHP 4.1.0 and up
define("ALLOW_ATTACHMENTS",0);

# if you use the above, how many would you want to add per message (max)
# You can leave this 1, even if you want to attach more files, because
# you will be able to add them sequentially
define("NUMATTACHMENTS",1);

# when using attachments you can upload them to the server
# if you want to use attachments from the local filesystem (server) set this to 1
# filesystem attachments are attached at real send time of the message, not at
# the time of creating the message
define("FILESYSTEM_ATTACHMENTS",0);

# if you add filesystem attachments, you will need to tell PHPlist where your
# mime.types file is.
define("MIMETYPES_FILE","/etc/mime.types");

# if a mimetype cannot be determined for a file, specify the default mimetype here:
define("DEFAULT_MIMETYPE","application/octet-stream");

# you can create your own pages to slot into PHPlist and do certain things
# that are more specific to your situation (plugins)
# if you do this, you can specify the directory where your plugins are. It is
# useful to keep this outside the PHPlist system, so they are retained after
# upgrading
# there are some example plugins in the "plugins" directory inside the
# admin directory
# this directory needs to be absolute, or relative to the admin directory

define("PLUGIN_ROOTDIR","/home/me/phplistplugins");

# uncomment this one to see the examples in the system (and then comment the
# one above)
#define("PLUGIN_ROOTDIR","plugins");


# the attachment repository is the place where the files are stored (if you use
# ALLOW_ATTACHMENTS)
# this needs to be writable to your webserver user
# it also needs to be a full path, not a relative one
# for secutiry reasons it is best if this directory is not public (ie below
# your website document root)
$attachment_repository = '/tmp';

# if you want to be able to send your messages as PDF attachments, you need to install
# FPDF (http://www.fpdf.org) and set these variables accordingly

# define('FPDF_FONTPATH','/home/pdf/font/');
# require('fpdf.php');
# define("USE_PDF",1);
# $pdf_font = 'Times';
# $pdf_fontstyle = '';
# $pdf_fontsize = 14;

# the mime type for the export files. You can try changing this to
# application/vnd.ms-excel to make it open automatically in excel
$export_mimetype = 'application/csv';

# if you want to use export format optimized for Excel, set this one to 1
define("EXPORT_EXCEL",0);

# Repetition. This adds the option to repeat the same message in the future.
# After the message has been sent, this option will cause the system to automatically
# create a new message with the same content. Be careful with it, because you may
# send the same message to your users
# the embargo of the message will be increased with the repetition interval you choose
# also read the README.repetition for more info
define("USE_REPETITION",0);

# Prepare a message. This system allows you to create messages as a super admin
# that can then be reviewed and selected by sub admins to send to their own lists
# it is old functionality that is quite confusing, and therefore by default it
# is now off. If you used to use it, you can switch it on here. If you did not
# use it, or are a new user, it is better to leave it off. It has nothing to
# do with being able to edit messages.
define("USE_PREPARE",0);

# If you want to use the PHPMailer class from phpmailer.sourceforge.net, set the following
# to 1. If you tend to send out html emails, it is recommended to do so.
define("PHPMAILER",1);

# To use a SMTP please give your server hostname here, leave it blank to use the standard
# PHP mail() command.
define("PHPMAILERHOST",'');

# if you want to use smtp authentication when sending the email uncomment the following
# two lines and set the username and password to be the correct ones
#$phpmailer_smtpuser = 'smtpuser';
#$phpmailer_smtppassword = 'smtppassword';

# tmpdir. A location where phplist can write some temporary files if necessary
# Make sure it is writable by your webserver user, and also check that you have
# open_basedir set to allow access to this directory. Linux users can leave it as it is.
# this directory is used for all kinds of things, mostly uploading of files (like in
# import), creating PDFs and more
$tmpdir = '/tmp';

# if you are on Windoze, and/or you are not using apache, in effect when you are getting
# "Method not allowed" errors you will want to uncomment this
# ie take off the #-character in the next line
# using this is not guaranteed to work, sorry. Easier to use Apache instead :-)
# $form_action = 'index.php';

# select the database module to use
# anyone wanting to submit other database modules is
# very welcome!
$database_module = "mysql.inc";

# you can store sessions in the database instead of the default place by assigning
# a tablename to this value. The table will be created and will not use any prefixes
# this only works when using mysql and only for administrator sessions
# $SessionTableName = "phplistsessions";

# there is now support for the use of ADOdb http://php.weblogs.com/ADODB
# this is still experimental, and any findings should be reported in the
# bugtracker
# in order to use it, define the following settings:
#$database_module = 'adodb.inc';
#$adodb_inc_file = '/path/to/adodb_inc.php';
#$adodb_driver = 'mysql';

# if you want more trouble, make this 63 (very unlikely you will like the result)
$error_level = error_reporting(0);

?>
