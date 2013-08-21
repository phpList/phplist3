<?php

/*

* the phpList config file. 
* 
* The minimum requirements to get phpList working are in this file. 
* If you are interested in tweaking more options, check out the config_extended.php file
* or visit http://resources.phplist.com/system/config
* 
*/

# what is your Mysql database server hostname
$database_host = "localhost";

# what is the name of the database we are using
$database_name = "phplistdb";

# who do we log in as?
$database_user = "phplist";

# and what password do we use
$database_password = 'phplist';

# if you have an SMTP server, set it here. Otherwise it will use the normal php mail() function
define("PHPMAILERHOST",'');

# if test is true (not 0) it will not actually send ANY messages, but display what it would have sent
# this is here, to make sure you edited the config file and mails are not sent accidentally

define ("TEST",1);
