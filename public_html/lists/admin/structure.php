<?php
#######################################################################
# Builds the global array DBstruct out of two subarrays. DBStruct holds the column definition for the database 
 # tables. A table definition must be in either $DBstructuser or $DBstructphplist. The global array 'tables' will be 
 # constructed out of DBStruct and will hold only the table names. Plugins can have their own DBStructs and these 
 # will be added to $DBstructphplist.
#######################################################################

require_once dirname(__FILE__).'/accesscheck.php';

## don't remove spaces
if (!defined  (  'STRUCTUREVERSION'  )) {
  define("STRUCTUREVERSION","dev");
}

$DBstructuser = array( # order of tables is essential for smooth upgrade
    "attribute" => array ( # attributes of a user or a message
        "id" => array("integer not null primary key auto_increment","ID"),
        "name" => array("varchar(255) not null","Name"),
        "type" => array("varchar(30)","Type of attribute"),
        "listorder" => array("integer","order of listing"),
        "default_value" => array("varchar(255)","Default value"),
        "required" => array("tinyint","Required for user to fill out"),
        "tablename" => array("varchar(255)","Name of table with values"),
        "index_1" => array("nameindex (name)",""),
        "index_2" => array("idnameindex (id,name)","")
    ),
    "user_attribute" => array(
        "attributeid" => array("integer not null","attribute"),
        "userid" => array("integer not null","user"),
        "value" => array("varchar(255)","Value of this attribute for this user"),
        "primary key" => array("(attributeid,userid)","PKey"),
        "index_1" => array("userindex (userid)",""),
        "index_2" => array("attindex (attributeid)",""),
    #    "index_3" => array("userattid (attributeid,userid)",""), ## already Pk
        "index_4" => array("attuserid (userid,attributeid)",""),
    ),
    "user" => array ( # a user in the system
        "id" => array("integer not null primary key auto_increment","sysexp:ID"),
        "email" => array("varchar(255) not null","Email"),
        "confirmed" => array("tinyint default 0","sysexp:Is this user confirmed"),
        "blacklisted" => array("tinyint default 0","sysexp:Is this user blacklisted"),
        "optedin" => array("tinyint default 0","sysexp:Did this user manually confirm"),
        "bouncecount" => array("integer default 0","sysexp:Number of bounces on this user"),
        "entered" => array("datetime", "sysexp:Entered"),
        "modified" => array("timestamp", "sysexp:Last Modified"),
        "uniqid" => array("varchar(255)","sys:Unique ID for User"),
        "htmlemail" => array("tinyint default 0","Send this user HTML emails"),
        "subscribepage" => array("integer","sys:Which page was used to subscribe"),
        "rssfrequency" => array("varchar(100)","RSS Frequency"),
        "password" => array("varchar(255)","Password"),
        "passwordchanged" => array("date","sys:Last time password was changed"),
        "disabled" => array("tinyint default 0","Is this account disabled?"),
        "extradata" => array("text","Additional data"),
        "foreignkey" => array("varchar(100)","Foreign Key"),
        "unique_1" => array(" email (email)","sys:unique"),
        "index_1" => array("foreignkey (foreignkey)","sys:Foreign Key"),
        "index_2" => array("idxuniqid (uniqid)","sys:index"),
    #    "index_3" => array("emailidx (email)","sys:index"),
        "index_4" => array("enteredindex (entered)","sys:index"),
        "index_5" => array('confidx (confirmed)','sys:index'),
        "index_6" => array('blidx (blacklisted)','sys:index'),
        "index_7" => array('optidx (optedin)','sys:index'),
    ),
    "user_history" => array(
        "id" => array("integer not null primary key auto_increment","sys:ID"),
        "userid" => array("integer not null",""),
        "ip" => array("varchar(255)",""),
        "date" => array("datetime",""),
        "summary" => array("varchar(255)","History Summary"),
        "detail" => array("text","History Detail"),
        "systeminfo" => array("text","System Information"),
        "index_1" => array("userididx (userid)","sys:index"),
        "index_2" => array("dateidx (date)","sys:index"),
    ),
  );
  
  $DBstructphplist = array(
    "list" => array ( # a list in the system
        "id" => array("integer not null primary key auto_increment","ID"),
        "name" => array("varchar(255) not null","Name"),
        "description" => array("Text","Description"),
        "entered" => array("datetime","Entered"),
        "listorder" => array("integer","Order of listing"),
        "prefix" => array("varchar(10)","Subject prefix"),
        "rssfeed" => array("varchar(255)","Rss Feed"),
        "modified" => array("timestamp", "Modified"),
        "active" => array("tinyint","Active"),
        "owner" => array("integer","Admin who is owner of this list"),
        "category" => array('varchar(255) default ""',"List category"),    
        "index_1" => array("nameidx (name)",""),
        "index_2" => array("listorderidx (listorder)",""),
    ),
    "listrss" => array( # rss details for a RSS source of a list, obsolete...
        "listid" => array("integer not null","List ID"),
        "type" => array("varchar(255) not null","Type of this entry"),
        "entered" => array("datetime not null",""),
        "info" => array("text",""),
        "index_1" => array("listididx (listid)",""),
        "index_2" => array("enteredidx (entered)",""),
    ),
    "listuser" => array ( # user subscription to a list
        "userid" => array("integer not null","User ID"),
        "listid" => array("integer not null","List ID"),
        "entered" => array("datetime", "Entered"),
        "modified" => array("timestamp", "Modified"),
        "primary key" => array("(userid,listid)","Primary Key"),
        "index_1" => array("userenteredidx (userid,entered)",""),
        "index_2" => array("userlistenteredidx (userid,listid,entered)",""),
        "index_3" => array("useridx (userid)",""),
        "index_4" => array("listidx (listid)",""),
    ),
    "message" => array ( # a message
        "id" => array("integer not null primary key auto_increment","ID"),
        "subject" => array("varchar(255) not null default '(no subject)'","subject"),
        "fromfield" => array("varchar(255) not null default ''","from"),
        "tofield" => array("varchar(255) not null default ''","tofield"),
        "replyto" => array("varchar(255) not null default ''","reply-to"),
        "message" => array("mediumtext","Message"),
        "textmessage" => array("mediumtext","Text version of Message"),
        "footer" => array("text","Footer for a message"),
        "entered" => array("datetime","Entered"),
        "modified" => array("timestamp", "Modified"),
        "embargo" => array("datetime","Time to send message"),
        "repeatinterval" => array("integer default 0","Number of seconds to repeat the message"),
        "repeatuntil" => array("datetime","Final time to stop repetition"),
        "requeueinterval" => array("integer default 0","Number of seconds to requeue the message"),
        "requeueuntil" => array("datetime","Final time to stop requeueing"),
#        "status" => array("enum('submitted','inprocess','sent','cancelled','prepared','draft')","Status"),
        "status" => array("varchar(255)","Status"),
        "userselection" => array("text","query to select the users for this message"),
        "sent" => array("datetime", "sent"),
        "htmlformatted" => array("tinyint default 0","Is this message HTML formatted"),
        "sendformat" => array("varchar(20)","Format to send this message in"),
        "template" => array("integer","Template to use"),
        "processed" => array("mediumint unsigned default 0", "Number Processed"),
        "astext" => array("integer default 0","Sent as text"),
        "ashtml" => array("integer default 0","Sent as HTML"),
        "astextandhtml" => array("integer default 0","Sent as Text and HTML"), // obsolete
        "aspdf" => array("integer default 0","Sent as PDF"),
        "astextandpdf" => array("integer default 0","Sent as Text and PDF"),
        "viewed" => array("integer default 0","Was the message viewed"),
        "bouncecount" => array("integer default 0","How many bounces on this message"),
        "sendstart" => array("datetime","When did sending of this message start"),
        "rsstemplate" => array("varchar(100)","if used as a RSS template, what frequency"),
        "owner" => array("integer","Admin who is owner")
    ),
    "messagedata" => array(
        "name" => array("varchar(100) not null","Name of field"),
        "id" => array("integer not null", "Message ID"),
        "data" => array("text","Data"),
        "primary key" => array("(name,id)",""),
    ),
    "listmessage" => array ( # linking messages to a list
        "id" => array("integer not null primary key auto_increment","ID"),
        "messageid" => array("integer not null","Message ID"),
        "listid" => array("integer not null","List ID"),
        "entered" => array("datetime", "Entered"),
        "modified" => array("timestamp","Modified"),
        "unique_1" => array("(messageid,listid)",""),
        "index_1" => array("listmessageidx (listid,messageid)",""),
    ),
    "user_blacklist" => array(
        "email" => array("varchar(255) not null unique","Email"),
        "added" => array("datetime","When added to blacklist"),
        "index_1" => array("emailidx (email)",""),
    ),
    "user_blacklist_data" => array(
        "email" => array("varchar(150) not null unique","Email"),
        "name" => array("varchar(100) not null","Name of Dataitem"),
        "data" => array("text",""),
        "index_1" => array("emailidx (email)",""),
        "index_2" => array("emailnameidx (email,name)",""),
    ),
    "rssitem" => array(
        "id" => array("integer not null primary key auto_increment","ID"),
        "title" => array("varchar(100) not null","Title"),
        "link" => array("varchar(100) not null","Link"),
        "source" => array("varchar(255)",""),
        "list" => array("integer not null",""),
        "index_1" => array("titlelinkidx (title,link)",""),
        "index_2" => array("titleidx (title)",""),
        "index_3" => array("listidx (list)",""),
        "added" => array("datetime",""),
        "processed" => array("mediumint unsigned default 0", "Number Processed"),
        "astext" => array("integer default 0","Sent as text"),
        "ashtml" => array("integer default 0","Sent as HTML"),
    ),
    "rssitem_data" => array(
        "itemid" => array("integer not null","rss item id"),
        "tag" => array("varchar(100) not null",""),
        "primary key" => array("(itemid,tag)",""),
        "data" => array("text","")
    ),
    "rssitem_user" => array(
        "itemid" => array("integer not null","rss item id"),
        "userid" => array("integer not null","user id"),
        "entered" => array("timestamp", "Entered"),
        "primary key" => array("(itemid,userid)","")
    ),
    "user_rss" => array(
        "userid" => array("integer not null primary key","user id"),
        "last" => array("datetime", "Last time this user was sent something")
    ),
    "message_attachment" => array( # attachments for a message
        "id" => array("integer not null primary key auto_increment","ID"),
        "messageid" => array("integer not null","Message ID"),
          "attachmentid" => array("integer not null","Attachment ID"),
          "index_1" => array("messageidx (messageid)",""),
          "index_2" => array("messageattidx (messageid,attachmentid)",""),
      ),
      "attachment" => array (
        "id" => array("integer not null primary key auto_increment","ID"),
        "filename" => array("varchar(255)","file"),
        "remotefile" => array("varchar(255)","The original location on the uploader machine"),
        "mimetype" => array("varchar(255)","The type of attachment"),
        "description" => array("text","Description"),
        "size" => array("integer","Size of the file")
      ),
      "usermessage" => array ( # linking messages to a user
          #"id" => array("integer not null primary key auto_increment","ID"),
          "messageid" => array("integer not null","Message ID"),
          "userid" => array("integer not null","User ID"),
          "entered" => array("datetime not null", "Entered"),
          "viewed" => array("datetime","When viewed"),
          "status" => array("varchar(255)","Status of message"),
          "primary key" => array("(userid,messageid)","Primary key"),
          "index_1" => array("messageidindex (messageid)",""),
          "index_2" => array("useridindex (userid)",""),
          "index_3" => array("enteredindex (entered)",""),
          "index_4" => array("statusidx (status)",""),
          "index_5" => array("viewedidx (viewed)",""),
      ),
      "sendprocess" => array( # keep track of running send processes to avoid to many running concurrently
          "id" => array("integer not null primary key auto_increment","ID"),
          "started" => array("datetime", "Start Time"),
          "modified" => array("timestamp","Modified"),
          "alive" => array("integer default 1","Is this process still alive?"),
          "ipaddress" => array("varchar(50)","IP Address of who started it"),
          "page" => array("varchar(100)","The page that this process runs in")
      ),
      "template" => array(
          "id" => array("integer not null primary key auto_increment","ID"),
          "title" => array("varchar(255) not null","Title"),
          "template" => array("longblob","The template"),
          "listorder" => array("integer",""),
          "unique_1" => array("(title)","")
      ),
      "templateimage" => array(
          "id" => array("integer not null primary key auto_increment","ID"),
          "template" => array("integer not null default 0","id of template"),
          "mimetype" => array("varchar(100)","Mime Type"),
          "filename" => array("varchar(100)","Filename"),
          "data" => array("longblob","The image"),
          "width" => array("integer",""),
          "height" => array("integer",""),
          "index_1" => array("templateidx (template)",""),
      ),
      "bounce" => array(
          "id" => array("integer not null primary key auto_increment","ID"),
          "date" => array("datetime","Date received"),
          "header" => array("text","Header of bounce"),
          "data" => array("blob","The bounce"),
          "status" => array("varchar(255)","Status of this bounce"),
          "comment" => array("text","System Comment"),
          "index_1" => array("dateindex (date)","")
      ),
      "user_message_bounce" => array( # bounce. We can have one usermessage bounce multiple times
          "id" => array("integer not null primary key auto_increment","ID"),
          "user" => array("integer not null","User ID"),
          "message" => array("integer not null","Message ID"),
          "bounce" => array("integer not null","Bounce ID"),
          "time" => array("timestamp","When did it bounce"),
          "index_1" => array("umbindex (user,message,bounce)","index"),
          "index_2" => array("useridx (user)","index"),
          "index_3" => array("msgidx (message)","index"),
          "index_4" => array("bounceidx (bounce)","index"),
      ),
      "user_message_forward" => array(
          "id" => array("integer not null primary key auto_increment","ID"),
          "user" => array("integer not null","User ID"),
          "message" => array("integer not null","Message ID"),
          "forward" => array("varchar(255)","Forward email"),
          "status" => array("varchar(255)","Status of forward"),
          "time" => array("timestamp","When was it forwarded"),
          "index_1" => array("usermessageidx (user,message)","index"),
          "index_2" => array("useridx (user)","index"),
          "index_3" => array("messageidx (message)","index"),
      ),
      "config" => array(
          "item" => array("varchar(35) not null primary key","ID"),
          "value" => array("longtext","Value"),
          "editable" => array("tinyint default 1","Editable?"),
          "type" => array("varchar(25)","Type of data"),
      ),
      "admin" => array (
          "id" => array("integer not null primary key auto_increment","sys:ID"),
          "loginname" => array("varchar(25) not null","Login Name (max 25 chars)"),
          "namelc" => array("varchar(255)","sys:Normalised loginname"),
          "email" => array("varchar(255) not null","Email"),
          "created" => array("datetime","sys:Time Created"),
          "modified" => array("timestamp","sys:Time modified"),
          "modifiedby" => array("varchar(25)","sys:Modified by"),
          "password" => array("varchar(255)","sys:Password"),
          "passwordchanged" => array("date","sys:Last time password was changed"),
          "superuser" => array("tinyint default 0","Is this user Super Admin?"),
          "disabled" => array("tinyint default 0","Is this account disabled?"),
          "unique_1" => array("loginnameidx (loginname)",""),
#          "index_1" => array("loginnameidx (loginname)",""),
      ),
      "adminattribute" => array ( # attributes for an admin
          "id" => array("integer not null primary key auto_increment","ID"),
          "name" => array("varchar(255) not null","Name"),
          "type" => array("varchar(30)","Type of attribute"),
          "listorder" => array("integer","order of listing"),
          "default_value" => array("varchar(255)","Default value"),
          "required" => array("tinyint","Required for user to fill out"),
          "tablename" => array("varchar(255)","Name of table with values")
      ),
      "admin_attribute" => array( # attributes of an admin
          "adminattributeid" => array("integer not null","attribute number"),
          "adminid" => array("integer not null","id of admin"),
          "value" => array("varchar(255)","Value of this attribute for this admin"),
          "primary key" => array("(adminattributeid,adminid)","PKey")
      ),
      "admin_password_request" => array( # password changes, 2.11.4 an up
          "id_key" => array ("integer not null primary key auto_increment", "Id"),
          "date" => array ("datetime", "When the password change request was made"),
          "admin" => array ("integer", "Admin's Id"),
          "key_value" => array ("varchar (32) not null", "Key")
      ),
      "task" => array(
          "id" => array("integer not null primary key auto_increment","ID"),
          "page" => array("varchar(25) not null","Page, page in system"),
          "type" => array("varchar(25) not null","Type: system, list, user, message, admin"),
          "unique_1" => array("(page)",""),
          "index_1" => array("pageidx (page)","index"),
          "index_2" => array("pagetypeidx (page,type)","index"),
      ),
      "admin_task" => array(
          "adminid" => array("integer not null","id of admin"),
          "taskid" => array("integer not null","id of task"),
          "level" => array("integer","Level: all,none,view,add,edit,delete,self"),
          "primary key" => array("(adminid,taskid)","PKey")
      ),
      "subscribepage" => array(
          "id" => array("integer not null primary key auto_increment","ID"),
          "title" => array("varchar(255) not null","Title"),
          "active" => array("tinyint default 0",""),
          "owner" => array("integer","Admin who is owner of this page")
      ),
      "subscribepage_data" => array(
          "id" => array("integer not null","ID"),
          "name" => array("varchar(100) not null","Name of field"),
          "data" => array("text","data"),
          "primary key" => array("(id,name)","")
      ),
      "eventlog" => array(
          "id" => array("integer not null primary key auto_increment","ID"),
          "entered" => array("datetime",""),
          "page" => array("varchar(100)","page this log was for"),
          "entry" => array("text",""),
          "index_1" => array("enteredidx (entered)","index"),
          "index_2" => array("pageidx (page)","index"),
      ),
      "urlcache" => array(
         "id" => array("integer not null primary key auto_increment","ID"),
         "url" => array("varchar(255) not null",""),
         "lastmodified" => array("integer",""),
         "added" => array("datetime",""),
         "content" => array("mediumtext",""),
         "index_1" => array("urlindex (url)",""),
      ),
      "gchartcache" => array(
         "url" => array("text not null",""),
         "lastmodified" => array("timestamp",""),
         "added" => array("datetime",""),
         "content" => array("blob",""),
         "index_1" => array("urlindex (url (1000))",""),
      ),
      # obsoleted table, but may still contain data
  
      ## keep it in for now, but could be dropped at some point
      ## once all dependencies have been removed
      "linktrack" => array ( 
          "linkid" => array("integer not null primary key auto_increment", "Link ID"),
          "messageid" => array("integer not null","Message ID"),
          "userid" => array("integer not null","User ID"),
          "url" => array("varchar(255)", "URL to log"),
          "forward" => array("text","URL to forward to"),
          "firstclick" => array("datetime","When first clicked"),
          "latestclick" => array("timestamp", "When last clicked"),
          "clicked" => array("integer default 0", "Number of clicks"),
          "index_1" => array("midindex (messageid)",""),
          "index_2" => array("uidindex (userid)",""),
          "index_3" => array("urlindex (url)",""),
          "index_4" => array("miduidindex (messageid,userid)",""),
#          "index_5" => array("miduidurlindex (messageid,userid,url)",""),
          "unique_1" => array("miduidurlindex (messageid,userid,url)","")
      ),
      "linktrack_ml" => array ( # ml -> message link
          "messageid" => array("integer not null","Message ID"),
          'forwardid' => array('integer not null','ID in forward table'),
          "firstclick" => array("datetime","When first clicked"),
          "latestclick" => array("datetime", "When last clicked"),
          "total" => array("integer default 0", "Number of times this link has been sent to users for this message"),
          "clicked" => array("integer default 0", "Number of clicks"),
          "htmlclicked" => array("integer default 0", "Number of clicks from HTML emails"),
          "textclicked" => array("integer default 0", "Number of clicks from text emails"),
          "primary key" => array("(messageid,forwardid)",""),
          "index_1" => array("midindex (messageid)",""),
          "index_2" => array("fwdindex (forwardid)",""),
      ),
      "linktrack_uml_click" => array ( # uml -> user message link, click
          "id" => array("integer not null primary key auto_increment", "ID"),
          "messageid" => array("integer not null","Message ID"),
          "userid" => array("integer not null","User ID"),
          'forwardid' => array('integer','ID in forward table'),
          "firstclick" => array("datetime","When first clicked"),
          "latestclick" => array("datetime", "When last clicked"),
          "clicked" => array("integer default 0", "Number of clicks"),
          "htmlclicked" => array("integer default 0", "Number of clicks from HTML emails"),
          "textclicked" => array("integer default 0", "Number of clicks from text emails"),
          "index_1" => array("midindex (messageid)",""),
          "index_2" => array("uidindex (userid)",""),
          "index_4" => array("miduidindex (messageid,userid)",""),
          "unique_1" => array("miduidfwdid (messageid,userid,forwardid)","")
      ),
      "linktrack_forward" => array (
          "id" => array("integer not null primary key auto_increment", "forward ID"),
          "url" => array("varchar(255)", "URL to log"),
  #        "forward" => array("text","URL to forward to"),
          "personalise" => array("tinyint default 0","Forward adding the UID?"),
          "index_1" => array("urlindex (url)",""),
  #        "index_2" => array("urlforwardindex (url,forward(255))",""),
  #        'unique_1' => array('fwdunique (forward (500))','Forward should be unique'),
          'unique_1' => array('urlunique (url)','URL should be unique'),
      ),
      "linktrack_userclick" => array (
          "linkid" => array("integer not null",""),
          "userid" => array("integer not null",""),
          "messageid" => array("integer not null",""),
          "name" => array("varchar(255)","Name of data"),
          "data" => array("text",""),
          "date" => array("datetime",""),
          "index_1" => array("linkindex (linkid)",""),
          "index_2" => array("uidindex (userid)",""),
          "index_3" => array("midindex (messageid)",""),
          "index_4" => array("linkuserindex (linkid,userid)",""),
          "index_5" => array("linkusermessageindex (linkid,userid,messageid)",""),
      ),
      "userstats" => array(
          "id" => array("integer not null primary key auto_increment",""),
          "unixdate" => array("integer","date in unix format"),
          "item" => array("varchar(255)",""),
          "listid" => array("integer default 0",""),
          "value" => array("integer default 0",""),
          "index_1" => array("dateindex (unixdate)",""),
          "index_2" => array("itemindex (item)",""),
          "index_3" => array("listindex (listid)",""),
          "index_4" => array("listdateindex (listid,unixdate)",""),
          "unique_1" => array("entry (unixdate,item,listid)",""),
      ),
      # session table structure, table is created as the name identified in the config file
  #   "sessiontable" => array(
  #      "sessionid" => array("CHAR(32) NOT NULL PRIMARY KEY",""),
  #      "lastactive" => array("INTEGER NOT NULL",""),
  #      "data" => array("LONGTEXT",""),
  #    ),
      "bounceregex" => array(
          "id" => array("integer not null primary key auto_increment","ID"),
          "regex" => array("varchar(255)","Regex"),
          "action" => array("varchar(255)","Action on rule"),
          "listorder" => array("integer default 0",""),
          "admin" => array("integer",""),
          "comment" => array("text",""),
          "status" => array("varchar(255)",""),
          "count" => array("integer default 0","Count of matching bounces on this rule"),
          "unique_1" => array("regex (regex)",""),
       ),
       "bounceregex_bounce" => array(
          "regex" => array("integer not null","Related regex"),
          "bounce" => array("integer not null","Related bounce"),
          "primary key" => array("(regex,bounce)",""),
        ),
      "admintoken" => array(
        "id" => array("integer not null primary key auto_increment","ID"),
        "adminid" => array("integer not null","adminid"),
        "value" => array('varchar(255)',''),
        "entered" => array('integer not null',''),
        "expires" => array('datetime not null',''),
      ),
      "i18n" => array (
        "lan" => array('varchar(255) not null','Language ISO'),
        "original" => array('text not null','Original string'),
        "translation" => array('text not null','Translated string'),
        "index_1" => array('lanorigidx (lan(50),original(200))','index'),
      ),
        
  /*    "translation" => array(
        "id" => array("integer not null primary key auto_increment",""),
        "translator" => array("varchar(255)","Name of translator"),
        "email" => array("varchar(255)","email of translator"),
        "pass" => array("varchar(255)","encrypted password for translation"),
        "ident" => array("varchar(255)","Translation identifier")
      ),
      "translation_data" => array(
        "id" => array("integer not null","Translation ID"),
        "item" => array("varchar(100)","Item to translate"),
        "primary key" => array("(id,item)","")
      )
  */
  //   "querycount" => array(
  //     "query" => array('text',''),
  //     "count" => array('integer default 0',''),
  //     'frontend' => array('integer default 0',''),
  //     'unique_1' => array('qindex (query (500))',''),
  //   ),
  
  );

    if (!empty($GLOBALS["plugins"]) && sizeof($GLOBALS["plugins"])) {
      foreach ($GLOBALS["plugins"] as $pluginName => $plugin) {
        $DBstructphplist = array_merge($DBstructphplist, $plugin->DBstruct);
      }
    }
    
    $DBstruct = $DBstructuser  + $DBstructphplist; # order of tables is essential for smooth upgrade

?>
