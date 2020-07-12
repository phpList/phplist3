<?php

/*

=========================================================================

General settings for language and database

=========================================================================

*/

// select the language module to use
// Look for <country>.inc files in the texts directory
// to find your language
// this is the language for the frontend pages. In the admin pages you can
// choose your language by using the dropdown in the pages.
$language_module = 'english.inc';

// what is your Mysql database server
$database_host = 'localhost';

// what is the name of the database we are using
$database_name = 'phplistdb';

// who do we log in as?
$database_user = 'phplist';

// and what password do we use
$database_password = 'phplist';

// the mysql server port number if not the default
$database_port = null;

// the socket to be used
$database_socket = null;

// enable database connection compression
$database_connection_compression = false;

// force database connection to use SSL
$database_connection_ssl = false;

// if you use multiple installations of phpList you can set this to
// something to identify this one. it will be prepended to email report
// subjects
$installation_name = 'phpList';

// if you want a prefix to all your tables, specify it here,
$table_prefix = 'phplist_';

// if you want to use a different prefix to user tables, specify it here.
// read README.usertables for more information
$usertable_prefix = 'phplist_user_';

// if you change the path to the phpList system, make the change here as well
// path should be relative to the root directory of your webserver (document root)
// If you install phpList in the document root then the value should be an empty string, not '/'.
// Warning: Do not change this after installation. You can only change this before you
// install and initialise phpList.
$pageroot = '/lists';

/*

=========================================================================

Settings for handling bounces

=========================================================================

*/

// Message envelope. This is the email that system messages come from
// it is useful to make this one where you can process the bounces on
// you will probably get a X-Authentication-Warning in your message
// when using this with sendmail
// NOTE: this is *very* different from the From: line in a message
// to use this feature, uncomment the following line, and change the email address
// to some existing account on your system
// requires PHP version > "4.0.5" and "4.3.1+" without safe_mode
// $message_envelope = 'listbounces@yourdomain';

// Handling bounces. Check README.bounces for more info
// This can be 'pop' or 'mbox'
$bounce_protocol = 'pop';

// set this to 0, if you set up a cron to download bounces regularly by using the
// commandline option. If this is 0, users cannot run the page from the web
// frontend. Read README.commandline to find out how to set it up on the
// commandline
define('MANUALLY_PROCESS_BOUNCES', 1);

// when the protocol is pop, specify these three
$bounce_mailbox_host = 'localhost';
$bounce_mailbox_user = 'popuser';
$bounce_mailbox_password = 'password';

// the "port" is the remote port of the connection to retrieve the emails
// the default should be fine but if it doesn't work, you can try the second
// one. To do that, add a # before the first line and take off the one before the
// second line
$bounce_mailbox_port = '110/pop3/notls';
//$bounce_mailbox_port = "110/pop3";

// it's getting more common to have secure connections, in which case you probably want to use
//$bounce_mailbox_port = "995/pop3/ssl/novalidate-cert";

// when the protocol is mbox specify this one
// it needs to be a local file in mbox format, accessible to your webserver user
$bounce_mailbox = '/var/spool/mail/listbounces';

// set this to 0 if you want to keep your messages in the mailbox. this is potentially
// a problem, because bounces will be counted multiple times, so only do this if you are
// testing things.
$bounce_mailbox_purge = 1;

// set this to 0 if you want to keep unprocessed messages in the mailbox. Unprocessed
// messages are messages that could not be matched with a user in the system
// messages are still downloaded into phpList, so it is safe to delete them from
// the mailbox and view them in phpList
$bounce_mailbox_purge_unprocessed = 1;

// how many bounces in a row need to have occurred for a user to be marked unconfirmed
$bounce_unsubscribe_threshold = 5;

// Set to 0 to received by mail bounce deletions in the advanced bounce processing report
define('REPORT_DELETED_BOUNCES', 0);

/*

=========================================================================

Security related settings

=========================================================================

*/

// if you use login, how many lists can be created per administrator
define('MAXLIST', 1);

// if you use commandline, you will need to identify the users who are allowed to run
// the script. See README.commandline for more info
// $commandline_users = array("admin");
// or you can use the following to disable the check (take off the # in front of the line)
$commandline_users = array();

//# silent resubscribe
// when someone signs up with an email address already in the database,
// phpList will simply accept it and subscribe them as if it is the first time
// however, that allows anyone to overwrite data of someone else
// see also https://mantis.phplist.com/view.php?id=15557
// if you don't like that, you can stop this from happening and send the subscriber to the
// preferences page instead. To do so, uncomment (remove the #) the next line
//define('SILENT_RESUBSCRIBE',false);

// as of version 2.4.1, you can have your users define a password for themselves as well
// this will cause some public pages to ask for an email and a password when the password is
// set for the user. If you want to activate this functionality, set the following
// to 1. See README.passwords for more information
define('ASKFORPASSWORD', 0);

// if you use passwords, they will be stored hashed
// set this one to the algorythm to use. You can find out which ones are
// supported by your system with the command
// $ php -r "var_dump(hash_algos());";
// "sha256" is fairly common on the latest systems, but if your system is very old (not a good idea)
// you may want to set it to "sha1" or "md5"
// if you use encrypted passwords, users can only request you as an administrator to
// reset the password. They will not be able to request the password from
// the system
// if you change this, you may have to use the "Forgot password" system to get back in your installation
define('HASH_ALGO', 'sha256');

// if you also want to force people who unsubscribe to provide a password before
// processing their unsubscription, set this to 1. You need to have the above one set
// to 1 for this to have an effect
define('UNSUBSCRIBE_REQUIRES_PASSWORD', 0);

// if a user should immediately be unsubscribed, when using their personal URL, instead of
// the default way, which will ask them for a reason, set this to 1
define('UNSUBSCRIBE_JUMPOFF', 0);

// To not send confirmation of unsubscription , instead of
// the default way, which will send it, set this to false
define('UNSUBSCRIBE_CONFIRMATION', true);

// when a user unsubscribes they are sent one final email informing them of
// their unsubscription. In order for that email to actually go out, a gracetime
// needs to be set otherwise it will never go out. The default of 5 minutes should
// be fine, but you can increase it if you experience problems
$blacklist_gracetime = 5;

// to increase security the session of a user is checked for the IP address
// this needs to be the same for every request. This may not work with
// network situations where you connect via multiple proxies, so you can
// switch off the checking by setting this to 0
define('CHECK_SESSIONIP', 1);

// Check for host of email entered for subscription
// Do not use it if your server is not 24hr online
// make the 0 a 1, if you want to use it
$check_for_host = 0;

/*

=========================================================================

Debugging and informational

=========================================================================

*/

// if test is true (not 0) it will not actually send ANY messages,
// but display what it would have sent
define('TEST', 1);

// if you set verbose to 1, it will show the messages that will be sent. Do not do this
// if you have a lot of users, because it is likely to crash your browser
define('VERBOSE', 0);

// some warnings may show up about your PHP settings. If you want to get rid of them
// set this value to 0
define('WARN_ABOUT_PHP_SETTINGS', 1);

// user history system info.
// when logging the history of a user, you can specify which system variables you
// want to log. These are the ones that are found in the $_SERVER and the $_ENV
// variables of PHP. check http://www.php.net/manual/en/language.variables.predefined.php
// the values are different per system, but these ones are quite common.
$userhistory_systeminfo = array(
    'HTTP_USER_AGENT',
    'HTTP_REFERER',
    'REMOTE_ADDR',
);

// add spamblock
// if you set this to 1, phplist will try to check if the subscribe attempt is a spambot trying to send
// nonsense. If you think this doesn't work, set this to 0
// this is currently only implemented on the subscribe pages
define('USE_SPAM_BLOCK', 1);

// notify spam
// when phplist detects a possible spam attack, it can send you a notification about it
// you can check for a while to see if the spam check was correct and if so, set this value
// to 0, if you think the check does it's job correctly.
// it will only send you emails if you have "Does the admin get copies of subscribe, update and unsubscribe messages"
// in the configuration set to true
define('NOTIFY_SPAM', 1);

/*
=========================================================================

Security

=========================================================================

*/

// CHECK REFERRER. Set this to "true" to activate a check on each request to make sure that
// the "referrer" in the request is from ourselves. This is not failsafe, as the referrer may
// not exist, or can be spoofed, but it will help a little
// it is also possible that it doesn't work with Webservers that are not Apache, we haven't tested that.
define('CHECK_REFERRER', false);

// if you activate the check above, you can add domain names in this array for those domains
// that you trust and that can be allowed as well
// only mention the domain for each.
// for example: $allowed_referrers = array('mydomain.com','msn.com','yahoo.com','google.com');
$allowed_referrers = array();

/*

=========================================================================

Feedback to developers

=========================================================================

*/

// use Register to "register" to phpList.com. Once you set TEST to 0, the system will then
// request the "Powered By" image from www.phplist.com, instead of locally. This will give me
// a little bit of an indication of how much it is used, which will encourage me to continue
// developing phpList. If you do not like this, set Register to 0.
define('REGISTER', 1);

// CREDITS
// We request you retain some form of credits on the public elements of
// phpList. These are the subscribe pages and the emails.
// This not only gives respect to the large amount of time given freely
// by the developers but also helps build interest, traffic and use of
// phpList, which is beneficial to future developments.
// By default the webpages and the HTML emails will include an image and
// the text emails will include a powered by line

// If you want to remove the image from the HTML emails, set this constant
// to be 1, the HTML emails will then only add a line of text as signature
define('EMAILTEXTCREDITS', 0);

// if you want to also remove the image from your public webpages
// set the next one to 1, and the pages will only include a line of text
define('PAGETEXTCREDITS', 0);

// in order to get some feedback about performance, phpList can send statistics to a central
// email address. To de-activate this set the following value to 1
define('NOSTATSCOLLECTION', 0);

// this is the email it will be sent to. You can leave the default, or you can set it to send
// to your self. If you use the default you will give me some feedback about performance
// which is useful for me for future developments
// $stats_collection_address = 'phplist-stats@phplist.com';

/*

=========================================================================

Queue and Load management

=========================================================================

*/

// If you set up your system to send the message automatically (from commandline),
// you can set this value to 0, so "Process Queue" will disappear from the site
// this will also stop users from loading the page on the web frontend, so you will
// have to make sure that you run the queue from the commandline
// check README.commandline how to do this
define('MANUALLY_PROCESS_QUEUE', 1);

// This setting will activate an initial setup choice for processing the queue
// When "true" it will allow a choice between remote queue processing with the
// phpList.com service or processing it locally in the browser.
// when the value is "false", you can use remote processing in your own way
// as explain at https://resources.phplist.com/system/remote_processing
// define('SHOW_PQCHOICE',false);

// batch processing
// if you are on a shared host, it will probably be appreciated if you don't send
// out loads of emails in one go. To do this, you can configure batch processing.
// Please note, the following two values can be overridden by your ISP by using
// a server wide configuration. So if you notice these values to be different
// in reality, that may be the case

// max messages to process
// if there are multiple messages in the queue, set a maximum to work on
define('MAX_PROCESS_MESSAGE', 999);

// process parallel
// if there are multiple messages in the queue, divide the max batch across them
// instead of sending them one by one.
// this only works if you use batch processing. It will divide the batch between the
// campaigns that need sending.
//define('PROCESSCAMPAIGNS_PARALLEL',true);

// define the amount of emails you want to send per period. If 0, batch processing
// is disabled and messages are sent out as fast as possible
define('MAILQUEUE_BATCH_SIZE', 0);

// define the length of one batch processing period, in seconds (3600 is an hour)
define('MAILQUEUE_BATCH_PERIOD', 3600);

// to avoid overloading the server that sends your email, you can add a little delay
// between messages that will spread the load of sending
// you will need to find a good value for your own server
// value is in seconds, and you can use fractions, eg "0.5" is half a second
// (or you can play with the autothrottle below)
define('MAILQUEUE_THROTTLE', 0);

// Mailqueue autothrottle. This will try to automatically change the delay
// between messages to make sure that the MAILQUEUE_BATCH_SIZE (above) is spread evently over
// MAILQUEUE_BATCH_PERIOD, instead of firing the Batch in the first few minutes of the period
// and then waiting for the next period. This only works with mailqueue_throttle off
// and MAILQUEUE_BATCH_PERIOD being a positive value
// it still needs tweaking, so send your feedback to mantis.phplist.com if you find
// any issues with it
define('MAILQUEUE_AUTOTHROTTLE', 0);

// Domain Throttling
// You can activate domain throttling, by setting USE_DOMAIN_THROTTLE to 1
// define the maximum amount of emails you want to allow sending to any domain and the number
// of seconds for that amount. This will make sure you don't send too many emails to one domain
// which may cause blacklisting. Particularly the big ones are tricky about this.
// it may cause a dramatic increase in the amount of time to send a message, depending on how
// many users you have that have the same domain (eg hotmail.com)
// if too many failures for throttling occur, the send process will automatically add an extra
// delay to try to improve that. The example sends 1 message every 2 minutes.

define('USE_DOMAIN_THROTTLE', 0);
define('DOMAIN_BATCH_SIZE', 1);
define('DOMAIN_BATCH_PERIOD', 120);

// if you have very large numbers of users on the same domains, this may result in the need
// to run processqueue many times, when you use domain throttling. You can also tell phplist
// to simply delay a bit between messages to increase the number of messages sent per queue run
// if you want to use that set this to 1, otherwise simply run the queue many times. A cron
// process every 10 or 15 minutes is recommended.
define('DOMAIN_AUTO_THROTTLE', 0);

// MAX_PROCESSQUEUE_TIME
// to limit the time, regardless of batch processing or other throttling of a single run of "processqueue"
// you can set the MAX_PROCESSQUEUE_TIME in seconds
// if a single queue run exceeds this amount, it will stop, just to pick up from where it left off next time
// this allows multiple installations each to run the queue, but slow installations (eg with large emails)
// set to 0 to disable this feature.
define('MAX_PROCESSQUEUE_TIME', 0);

/*

=========================================================================

Miscellaneous

=========================================================================

*/

//# default system language
// set the default system language. If the language cannot be detected, it will fall back to
// this one. It has to be the "ISO code" of the language.
// to find what languages are available here, check out http://translate.phplist.com/
$default_system_language = 'en';

//# use Precedence
// according to the email standards, the Precedence header is outdated, and should not be used
// however, Google/Gmail requests that the header is used.
// So, it's up to you what to do. Yes, or No. Defaults to "yes" use it
// see also https://mantis.phplist.com/view.php?id=16688
//define('USE_PRECEDENCE_HEADER',false);

// if you do not require users to actually sign up to lists, but only want to
// use the subscribe page as a kind of registration system, you can set this to 1 and
// users will not receive an error when they do not check a list to subscribe to
define('ALLOW_NON_LIST_SUBSCRIBE', 0);

// Show private lists
// If you have a mixture of public and private lists, you can set this to 1, to allow
// your subscribers to see (and unsubscribe from) your private lists on their
// preferences page. By default it won't show private (non-public) lists
// see also https://mantis.phplist.com/view.php?id=15274
define('PREFERENCEPAGE_SHOW_PRIVATE_LISTS', 0);

// Show the link(s) to subscribe page(s) on the phpList public homepage (lists/)
define('SHOW_SUBSCRIBELINK', true);

// Show link to the preferences page on the phpList public homepage (lists/)
define('SHOW_PREFERENCESLINK', true);

// Show link to the unsubscribe page on the phpList public homepage (lists/)
define('SHOW_UNSUBSCRIBELINK', true);

// Show 'All Subscribers' section on Subscriber Lists page
// This flag enabled will show a list called “All subscribers” on the
// Subscriber Lists page that has all subscribers in the system as members.
// This prevents confusion if there are subscribers not assigned to lists and
// therefore total subscribers are than the sum of all list members.
define('SHOW_LIST_OFALL_SUBSCRIBERS', false);

// wrap html
// in some cases, strange newlines appear in the HTML source of campaigns
// If that's happening to you, you may want to set this one
// check https://mantis.phplist.com/view.php?id=15603 for more info
// define('WORDWRAP_HTML',60);

// year ranges. If you use dates, by default the drop down for year will be from
// three years before until 10 years after this the current value for year. If there
// is no current value the current year will be used.
// if you want to use a bigger range you can set the start and end year here
// be aware that the drop down may become very large.
// if set to 0 they will use the default behaviour. So I'm afraid you can't start with
// year 0. Also be aware not to set the end year to something relatively soon in the
// future, or it will stop working when you reach that year.
define('DATE_START_YEAR', 0);
define('DATE_END_YEAR', 0);

// empty value prefix. This can be used to identify values in select attributes
// that are not allowed to be selected and cause an error "Please enter your ..."
// by using a top value that starts with this string, you can make sure that the
// selects do not have a default value, that may be accidentally selected
// eg. "-- choose your country"
define('EMPTY_VALUE_PREFIX', '--');

// admin details for messages
// if this is enabled phplist will initialise the From in new messages to be the
// details of the logged in administrator who is sending the message
// otherwise it will default to the values set in the configure page that identify
// the "Default for 'From:' in a campaign"
define('USE_ADMIN_DETAILS_FOR_MESSAGES', 1);

// attribute value reorder limit
// for selectable attributes, like "select" and "radio" you can manage the order of the values
// by adding a number for each of them. After a certain number of values, this will disappear
// and it will automatically order alphabetically. This "certain number" is controlled here
// and it defaults to 100
// if you want to use this, uncomment this line and change the value
// define('ATTRIBUTEVALUE_REORDER_LIMIT',100);

// test emails
// if you send a test email, phplist will by default send you two emails, one in HTML format
// and the other in Text format. If you set this to 1, you can override this behaviour
// and only have a test email sent to you that matches the user record of the user that the
// test emails are sent to
define('SEND_ONE_TESTMAIL', 0);

// send a webpage. You can send the contents of a webpage, by adding
// [URL:http://website/file.html] as the content of a message. This can also be personalised
// for users by using eg
// [URL:http://website/file.html?email=[email]]
// the timeout for refetching a URL can be defined here. When the last time a URL has been
// fetched exceeds this time, the URL will be refetched. This is in seconds, 3600 is an hour
// this only affects sending within the same "process queue". If a new process queue is started
// the URL will be fetched the first time anyway. Therefore this is only useful is processing
// your queue takes longer than the time identified here.
define('REMOTE_URL_REFETCH_TIMEOUT', 3600);

// Users Page Max. The page listing subscribers will stop listing them and require a search,
// when the amount of subscribers is over 1000. With this settings you can change that cut-off point
define('USERSPAGE_MAX', 1000);

// Message Age. The Scheduling tab has an option to stop sending a message when it has reached a certain date.
// This can be used to avoid the campaign going out, eg when an event has already taken place.
// This value defaults to the moment of creating the campaign + the number of seconds set here.
// phpList will mark the campaign as sent, when this date has been reached
// 15768000 is about 6 months, but other useful values can be
// 2592000 - 30 days
// 604800 - a week.
// 86400 - a day
define('DEFAULT_MESSAGEAGE', 15768000);

// Repetition. This adds the option to repeat the same message in the future.
// After the message has been sent, this option will cause the system to automatically
// create a new message with the same content. Be careful with it, because you may
// send the same message to your users
// the embargo of the message will be increased with the repetition interval you choose
// also read the README.repetition for more info
define('USE_REPETITION', 0);

// admin language
// if you want to disable the language switch for the admin interface (and run all in english)
// set this one to 0
define('LANGUAGE_SWITCH', 1);

//# error 404 page
//# custom "File not found".
//# in several places, phpList may generate a "file not found" page. If you want to provide your own design for this
//# page, you can set this here. This needs to be a file that is in your webserver document root
//# eg /home/youraccount/public_html/404.html
//# the contents are displayed "as-is", so it will not run any PHP code in the file.
define('ERROR404PAGE', '404.html');

// Add a Reply-To header. Set this to true to show a Reply to field on the Compose tab when creating a campaign.
define('USE_REPLY_TO', false);

/*

=========================================================================

Message sending options
* phpList now only uses phpMailer for sending, but below you can
* tweak a few options on how that is done

=========================================================================

*/

// If you are using PHPMailer 5 then you can use a different version to the phplist distribution by specifying
// the location of the PHPMailer autoload file
// if not set, the PHPMailer version included in the distribution will be used
// eg for Debian based systems, it may be something like the example below
// when you do this, you may need to run some tests, to see if the phpMailer version you have works ok
//define ('PHPMAILER_PATH', '/usr/share/php/libphp-phpmailer/PHPMailerAutoload.php');
//
// If you are using PHPMailer 6 then you can use a different version by setting the location of the PHPMailer
// src directory, e.g.
//define ('PHPMAILER_PATH', '/var/www/PHPMailer-master/src');

// To use a SMTP server please give your server hostname here, leave it blank to use the standard
// PHP mail() command.
define('PHPMAILERHOST', '');

// in the above you can specify multiple SMTP servers like this:
// 'server1:port1;server2:port2;server3:port3' eg
//define('PHPMAILERHOST','smtp1.mydomain.com:25;smtp2.mydomain.com:2500;smtp3.phplist.com:5123');

// if you want to use smtp authentication when sending the email uncomment the following
// two lines and set the username and password to be the correct ones
//$phpmailer_smtpuser = 'smtpuser';
//$phpmailer_smtppassword = 'smtppassword';

//# you can set this to send out via a different SMTP port
// define('PHPMAILERPORT',25);

//# test vs blast
// you can send test messages via a different SMTP host than the actual campaign queue
// if not set, these default to the above PHPMAILERHOST and PHPMAILERPORT
// define('PHPMAILERTESTHOST','testsmtp.mydomain.com');
// define('PHPMAILERBLASTHOST','livesmtp.mydomain.com');
// define('PHPMAILERBLASTPORT',25);

// to use SSL/TLS when sending set this value
// it can either be "ssl" or "tls" or false to not use SSL/TLS at all
// define("PHPMAILER_SECURE",'ssl');

//# SMTP debugging
// Enable debugging output by phpmailer when sending test emails
// See https://phpmailer.github.io/PHPMailer/classes/PHPMailer.PHPMailer.PHPMailer.html#property_SMTPDebug
// define('PHPMAILER_SMTP_DEBUG', 0);

//# Smtp Timeout
//# If you use SMTP for sending, you can set the timeout of the SMTP connection
//# defaults to 5 seconds
// define('SMTP_TIMEOUT',5);

// Pop-Before-Smtp
// If you use Pop before Smtp, set to true
// And complete smtp settings (PHPMAILERHOST,  phpmailer_smtpuser', phpmailer_smtppassword)
define('POP_BEFORE_SMTP', false);
define('POPBEFORESMTP_DEBUG', false);

/*

=========================================================================

Advanced Features, HTML editor, RSS, Attachments, Plugins. PDF creation

=========================================================================

*/

// Usertrack
// Usertrack is used to track views or opens of campaigns. This only works in HTML messages
// as it relies on a little image being pulled from the phpList system to update the database
// To add it to your campaigns, you need to add [USERTRACK] somewhere.
// From version 3 onwards, this is automatically done with the following setting. If you do not
// want it, you can switch it off here, by uncommenting the next line
// define('ALWAYS_ADD_USERTRACK',0);

// Click tracking
// If you set this to 1, all links in your emails will be converted to links that
// go via phpList. This will make sure that clicks are tracked. Default: 1
// If you disable a URL conversion, set to 0.
define('CLICKTRACK', 1);

// Click track, list detail
// if you enable this, you will get some extra statistics about unique users who have clicked the
// links in your messages, and the breakdown between clicks from text or html messages.
// However, this will slow down the process to view the statistics, so it is
// recommended to leave it off, but if you're very curious, you can enable it
define('CLICKTRACK_SHOWDETAIL', 0);

// If you want to upload images in the editor, you need to specify the location
// of the directory where the images go. This needs to be writable by the webserver,
// and it needs to be in your public document (website) area
// the directory is relative to the webserver root directory
//   eg if your webserver root is /home/user/public_html
//   then the images directory is /home/user/public_html/uploadimages
// This is a potential security risk, so read README.security for more information
define('UPLOADIMAGES_DIR', 'uploadimages');

//# for the above, you can also use subdirectories, for example
//define("UPLOADIMAGES_DIR","images/newsletter/uploaded");


// EMBEDEXTERNALIMAGES
// this flag will fetch images in your content that are remotely hosted and put them
// inside the message that is sent.
define('EMBEDEXTERNALIMAGES',false);


// Manual text part, will give you an input box for the text version of the message
// instead of trying to create it by parsing the HTML version into plain text
define('USE_MANUAL_TEXT_PART', 0);

// set this to 1 to allow adding attachments to the mails
// caution, message may become very large. it is generally more
// acceptable to send a URL for download to users
// using attachments requires PHP 4.1.0 and up
define('ALLOW_ATTACHMENTS', 0);

// when using attachments you can upload them to the server
// if you want to use attachments from the local filesystem (server) set this to 1
// filesystem attachments are attached at real send time of the message, not at
// the time of creating the message
define('FILESYSTEM_ATTACHMENTS', 0);

// if you add filesystem attachments, you will need to tell phpList where your
// mime.types file is.
define('MIMETYPES_FILE', '/etc/mime.types');

// if a mimetype cannot be determined for a file, specify the default mimetype here:
define('DEFAULT_MIMETYPE', 'application/octet-stream');

// you can create your own pages to slot into phpList and do certain things
// that are more specific to your situation (plugins)
// if you do this, you can specify the directory where your plugins are. It is
// useful to keep this outside the phpList system, so they are retained after
// upgrading
// there are some example plugins in the "plugins" directory inside the
// admin directory
// this directory needs to be absolute, or relative to the admin directory

//define("PLUGIN_ROOTDIR","/home/me/phplistplugins");

// uncomment this one to see the examples in the system (and then comment the
// one above)
define('PLUGIN_ROOTDIR', 'plugins');

// the attachment repository is the place where the files are stored (if you use
// ALLOW_ATTACHMENTS)
// this needs to be writable to your webserver user
// it also needs to be a full path, not a relative one
// for secutiry reasons it is best if this directory is not public (ie below
// your website document root)
$attachment_repository = '/tmp';

// the mime type for the export files. You can try changing this to
// application/vnd.ms-excel to make it open automatically in excel
// or text/tsv
$export_mimetype = 'application/csv';

// if you want to use export format optimized for Excel, set this one to 1
define('EXPORT_EXCEL', 0);

// tmpdir. A location where phplist can write some temporary files if necessary
// Make sure it is writable by your webserver user, and also check that you have
// open_basedir set to allow access to this directory. Linux users can leave it as it is.
// this directory is used for all kinds of things, mostly uploading of files (like in
// import), creating PDFs and more

// On Linux based systems, it will be good to make sure this directory is on the same
// filesystem as your phpList installation. In some systems, renaming files or directories
// across filesystems fails.

$tmpdir = '/tmp';

// if you are on Windoze, and/or you are not using apache, in effect when you are getting
// "Method not allowed" errors you will want to uncomment this
// ie take off the #-character in the next line
// using this is not guaranteed to work, sorry. Easier to use Apache instead :-)
// $form_action = 'index.php';

// select the database module to use
// anyone wanting to submit other database modules is
// very welcome!
$database_module = 'mysqli.inc';

// you can store sessions in the database instead of the default place by assigning
// a tablename to this value. The table will be created and will not use any prefixes
// this only works when using mysql and only for administrator sessions
// $SessionTableName = "phplistsessions";

/*

=========================================================================

Experimental Features
* these are things that require a bit more fine tuning and feedback in the bugtracker

=========================================================================

*/
// email address validation level [Bas]
// 0 = No email address validation. So PHPList can be used as a non-email-sending registration system.
// 1 = 10.4 style email validation.
// 2 = RFC821 email validation without escaping and quoting of local part.
// 3 = RFC821 email validation.
// This is an expirimental email address validation based on the original RFC. It will validate all kind
// of 'weird' emails like !#$%&'*+-/=.?^_`{|}~@example.com and escaped\ spaces\ are\ allowed@[1.0.0.127]
// not implemented are:
//   Length of domainPart is not checked
//   Not accepted are CR and LF even if escaped by \
//   Not accepted is Folding
//   Not accepted is literal domain-part (eg. [1.0.0.127])
//   Not accepted is comments (eg. (this is a comment)@example.com)
// Extra:
//   topLevelDomain can only be one of the defined ones
define('EMAIL_ADDRESS_VALIDATION_LEVEL', 2);

// Time Zone
// By default phpList will operate in the Timezone of your server. If you want to work
// in a different Timezone, you can set that here. It will need to be a valid setting for
// both PHP and Mysql. The value should be a city in the world
// to find PHP timezones, check out http://php.net/manual/en/timezones.php
// You will also need to tell Mysql about your timezones, which means you have to load the timezone
// data in the Mysql Database, which you can find out about here:
// http://dev.mysql.com/doc/refman/5.0/en/mysql-tzinfo-to-sql.html
// make sure that the value you use works for both PHP and Mysql. If you find strange discrepancies
// in the dates and times used in phpList, you probably used the wrong value.

// define('SYSTEM_TIMEZONE','Europe/London');

// HTTP_HOST
// In some systems (eg behind load balancing proxies) you may need to set the HOST to be something else
// then the system identifies. If you do, you can set it here.
//define('HTTP_HOST','your.website.com');

// list exclude will add the option to send a message to users who are on a list
// except when they are on another list.
define('USE_LIST_EXCLUDE', 0);

//# message queue prepare
// this option will handle the sending of the queue a little bit differently
// it assumes running the queue many times in small batches
// the first run will find all subscribers that need to receive a campaign and mark them all
// as "todo" in the database. Subsequent calls will then work through the "todo" list and
// send them. This can be useful if the SQL query to find subscribers for a campaign is slow
// which is the case when your database is filling up.
// set to 1 to use
define('MESSAGEQUEUE_PREPARE', 0);

// admin authentication module.
// to validate the login for an administrator, you can define your own authentication module
// this is not finished yet, so don't use it unless you're happy to play around with it
// if you have modules to contribute, open a tracker issue at http://mantis.phplist.com
// the default module is phplist_auth.inc, which you can find in the "auth" subdirectory of the
// admin directory. It will tell you the functions that need to be defined for phplist to
// retrieve it's information.
// phplist will look for a file in that directory, or you can enter the full path to the file

// eg
//$admin_auth_module = 'phplist_auth.inc';

// or
//$admin_auth_module = '/usr/local/etc/auth.inc';

// Public protocol
// phpList will automatically use the protocol you run the admin interface on for clicktrack links and
// tracking images
// but if you want to force this to be http, when you eg run the admin on https, uncomment the below line
// see also https://mantis.phplist.com/view.php?id=16611
//define('PUBLIC_PROTOCOL','http');

// Admin protocol
// similar to the above, if you need to force the admin pages on either http or https (eg when behind a
// proxy that prevents proper auto-detection), you can set it here
//define('ADMIN_PROTOCOL','https');

// advanced bounce processing
// with advanced bounce handling you are able to define regular expressions that match bounces and the
// action that needs to be taken when an expression matches. This will improve getting rid of bad emails in
// your system, which will be a good thing for making sure you are not being blacklisted by other
// mail systems
// if you use this, you will need to teach your system regularly about patterns in new bounces
define('USE_ADVANCED_BOUNCEHANDLING', 0);

// When forwarding ('to a friend') the message will be using the attributes of the destination email by default.
// This often means the message gets stripped of al its attributes.
// When setting this constant to 1, the message will use the attributes of the forwarding user. It can be used
// to connect the destinatory to the forwarder and/or reward the forwarder.
define('KEEPFORWARDERATTRIBUTES', 0);

// forward to friend, multiple emails
// This setting defines howmany email addresses you can enter in the forward page.
// Default is 1 to not change behaviour from previous version.
define('FORWARD_EMAIL_COUNT', 1);

// forward to friend - personal message
// Allow user to add a personal note when forwarding 'to a friend'
// 0 will turn this option off. default is 0 to not change behaviour from previous version.
// 500 is recommended as a sound value to write a little introductory note to a friend
// The note is prepeded to both text and html messages and will be stripped of all html
define('FORWARD_PERSONAL_NOTE_SIZE', 0);

// different content when forwarding 'to a friend'
// Allow admin to enter a different message that will be sent when forwarding 'to a friend'
// This will show an extra tab in the message dialog.
define('FORWARD_ALTERNATIVE_CONTENT', 0);

// To disable the automatic updater change the value to false. By default the value is true.
define('ALLOW_UPDATER', true);

// Google mail Feedback loop configuration
// When feedback loop is configured in Google mail according to https://support.google.com/mail/answer/6254652?hl=en
// adds constant to email headers
define('GOOGLE_SENDERID', '');
