<?php
require_once dirname(__FILE__) . '/accesscheck.php';

# Typical call
//foreach ($GLOBALS['plugins'] as $plugin) {
//  if ($plugin->enabled) {
//    $html .= $plugin->addSnippet();
//  }
//}

class phplistPlugin {
  ############################################################
  # Registration & Config
  
  public $name= "Default Plugin";
  public $version= "unknown";
  public $authors= "";
  public $enabled= 0; // use directly, can be privitsed later and calculated with __get and __set
  public $system_root = ''; ## root dir of the phpList admin directory
  #@@Some ideas to implement this:
  # * Start each method with if (!$this->enabled) return parent :: parentMethod($args);
  # * Don't add to manage Global plugins if disabled
  public $coderoot= "./PLUGIN_ROOTDIR/defaultplugin/"; # coderoot relative to the phplist admin directory
  # optional configuration variables
  public $configvars = array ();
  # config var    array( type, name [array values]));
  public $DBstruct= array ();
  # These files can be called from the commandline
  # This should hold an array per file: filename (without .php) => path relative to admin/
  public $commandlinePages = array();
  
  public $configArray = array();

  public $importTabTitle = ''; ## title of the tab for the import page
  
  public $needI18N = 0;
  
  /* array of pages in this plugin to add to the menu
   * 
   * example format: 
   * 
   */
  public $menuLinks = array(); 
    
  
  function name() {
    return $this->name;
  }
  
  /* 
   * constructor
   * plugins should not run SQL queries as construction time
   * use the "activate" function instead
   * that way you can use processDBerror to handle DB errors
   */

  function phplistplugin() {
    # constructor
    # Startup code, other objects might not be constructed yet 
    #print ("<BR>Construct " . $this->name);
    ## try to prepend PLUGIN ROOTDIR, if necessary
    if (!is_dir($this->coderoot)) {
      $this->coderoot = PLUGIN_ROOTDIR . '/' . $this->coderoot;
    }
    ## always enable in dev mode
    if (!empty($GLOBALS['developer_email'])) {
      $this->enabled = 1;
    }
    $this->importTabTitle = $this->name;
    $this->system_root = dirname(__FILE__);
  }
  
  function activate() {
    # Startup code, all other objects are constructed 
    # returns success or failure, false means we cannot start
  }
    
  function displayAbout() {
    # Return html snippet to tell about coopyrights of used third party code.
    # author is already displayed
    return null;
  }

  function i18nLanguageDir() {
    # Return i18n Language Dir so that main page content can be extended
    return null;
  }

  function writeConfig($name, $value) {
    #  write a value to the general config to be retrieved at a later stage
    # parameters: name -> name of the variable
    #             value -> value of the variablesiable, can be a scalar, array or object
    # returns success or failure    $store = '';
    if (is_object($value) || is_array($value)) {
      $store= 'SER:' . serialize($value);
    } else {
      $store= $value;
    }
    Sql_Query(sprintf('replace into %s set item = "%s-%s",value="%s",editable=0', $GLOBALS['tables']['config'], $this->name, addslashes($name), addslashes($store)));
    ## force refresh of config in session
    unset($_SESSION['config']);
    return 1;
  }

  function getConfig($name) {
    # read a value from the general config to be retrieved at a later stage
    # parameters: name -> name of the variable
    # returns value
    
    if (isset($_SESSION['config'][$this->name.'-'.addslashes($name)])) {
      return $_SESSION['config'][$this->name.'-'.addslashes($name)];
    }
    
    $req= Sql_Fetch_Array_Query(sprintf('select value from  %s where item = "%s-%s"', $GLOBALS['tables']['config'], $this->name, addslashes($name)));
    $result= stripslashes($req[0]);
    if (!empty ($result) && strpos('SER:', $result) == 1) {
      $result= substr($result, 4);
      $value= unserialize($result);
    } else {
      $value = $result;
    }
    $_SESSION['config'][$this->name.'-'.addslashes($name)] = $value;
    return $result;
  }

  function displayConfig($name) {
    ## displayConfig
    # purpose: display input for a config variable in the backend
    # parameters: 
    # name -> name of the config variable, as found in $this->configvars
    # return, HTML snippet of input to slot into a form
    $name= trim(strtolower($name));
    $name= preg_replace('/\W/', '', $name);
    $type= $this->configvars[$name][0];
    $label= $this->configvars[$name][1];
    $currentvalue= $this->getConfig($name);
    $html= '';
    switch ($type) {
      case 'attributeselect' :
        $html= sprintf('<select name="%s"><option value=""> --%s</option>', $name, $GLOBALS['I18N']->get('choose'));
        $req= Sql_Query(sprintf('select * from %s', $GLOBALS['tables']['attribute']));
        while ($row= Sql_Fetch_Array($req)) {
          $html .= sprintf('<option value="%d" %s>%s</option>', $row['id'], $row['id'] == $currentvalue ? 'selected="selected"' : '', substr(htmlspecialchars($row['name']),0,25));
        }
        $html .= '</select>';
        return $html;
      case 'radio' :
        $values= $this->configvars[$name][2];
        foreach ($values as $key => $label) {
          $html .= sprintf('<input type="radio" name="%s" value="%s" %s> %s', $name, $key, $currentvalue == $key ? 'checked="checked"' : '', $label);
        }
        return $html;
      case 'textarea' :
        $html= sprintf('<textarea name="%s" rows="10" cols="40" wrap="virtual">%s </textarea>', $name, htmlspecialchars($currentvalue));
        return $html;
      case 'text' :
      default :
        $html= sprintf('<input type="text" name="%s" value="%s" size="45">', $name, htmlspecialchars($currentvalue));
        return $html;
    }
  }


  ############################################################
  # Main interface hooks

  function adminmenu() {
    return array (
      # page, description
      "main" => "Main Page",
      "helloworld" => "Hello World page"
    );
  }

  ############################################################
  # Frontend

  function displaySubscriptionChoice($pageData, $userID= 0) {
    # return snippet for the Subscribe page
    return '';
  }

  function parseThankyou($pageid= 0, $userid= 0, $text= "") {
    # parse the text of the thankyou page
    # parameters:
    #  pageid -> id of the subscribe page
    #  userid -> id of the user
    #  text -> current text of the page
    # returns parsed text
    return $text;
  }
  
  ############################################################
  # Messages

  function displayMessages($msg, &$status) {
    # purpose: add or change status display of a message
    # Should become more uniform like the other display functions, using the webblerlist
    # 200711 Bas
    return null;
  }
  
  ############################################################
  # Message

  function sendMessageTab($messageid= 0, $messagedata= array ()) {
    ## add a tab to the "Send a Message page" for options to be set in the plugin
    # parameters: 
    #    messageid = ID of the message being displayed (should always be > 0)
    #    messagedata = associative array of all data from the db for this message
    # returns: HTML code to slot into the form to submit to the database
    return '';
  }

  function sendMessageTabTitle($messageid= 0) {
    ## If adding a TAB to the Send a Message page, what is the TAB's name
    # parameters: none
    # returns: short title (less than about 10 characters)
    return '';
  }

  function sendMessageTabSave($messageid= 0, $data= array ()){
    ## add a tab to the "Send a Message page" for options to be set in the plugin
    # parameters: 
    #    messageid = ID of the message being saved (should always be > 0)
    #    messagedata = associative array of all data from the db for this message
    # returns: HTML code to communicate the result to the user
    return '';
  }

  function sendFormats() {
    ## sendFormats();
    # parameters: none
    # returns array of "shorttag" => "description" of possible formats this plugin can provide
    # this will be listed in the "Send As" list of radio buttons, so that an editor can choose the format
    # prefix the shorttag with _ to suppress it from the send page (for internal use)
    return array ();
  }

  ############################################################
  # Processqueue

  /* canSend
   *
   * can this message be sent to this subscriber
   * if false is returned, the message will be identified as sent to the subscriber
   * and never tried again
   * 
   * @param $messagedata array of all message data
   * @param $userdata array of all user data
   * returns bool: true, send it, false don't send it
 */

  function canSend ($messagedata, $subscriberdata) {
    return true; //@@@
  }
  
  /* throttleSend
   *
   * can this message be sent to this subscriber
   * if false is returned, the message will be identified as failed
   * and re-tried in the next queue run
   * 
   * @param $messagedata array of all message data
   * @param $userdata array of all user data
   * returns bool: true, do not send it, false send it
 */

  function throttleSend($messagedata, $subscriberdata) {
    return false;
  }

  /**
   * messageStatusLimitReached
   *
   * @param $recentlySent integer 
   * @return text to display in the status panel of the active message
   */
  function messageStatusLimitReached($recentlySent) {
    return '';
  }

  /** messageStatus
   *
   * @param integer $id messageid
   * @param string $status message status
   * @return possible additional text to display
   */
  function messageStatus($id,$status) {
    return '';
  }
  
  function parseOutgoingTextMessage($messageid, $content, $destination, $userdata= null) {
    ## parseOutgoingTextMessage
    # parameters: 
    #  messageid: ID of the message
    #  text content: entire text content of a message going out
    #  destination: destination email
    #  userhtmlpref: 1/0 whether the user wants to receive HTML
    # returns: parsed content
    return $content;
  }

  function parseOutgoingHTMLMessage($messageid, $content, $destination, $userdata= null) {
    ## parseOutgoingHTMLMessage
    # parameters: 
    #  messageid: ID of the message
    #  text content: entire HTML content of a message going out
    #  destination: destination email
    #  userhtmlpref: 1/0 whether the user wants to receive HTML
    # returns: parsed content
    return $content;
  }

  function getMessageAttachment($messageid, $content) {
    ###getMessageAttachment($messageid,$mail->Body);
    # parameters: $messageid,$messagecontent
    # returns array (
    #  'content' => Content of the attachment 
    #  'filename' => name of the attached file
    #  'mimetype' => mimetype of the attachment
    # );
    return array ();
  }

  function mimeWrap($messageid, $body, $header, $contenttype, $destination) {
    ### mimeWrap
    # purpose: wrap the actual contents of the message in another MIME layer
    # Designed to ENCRYPT the fully expanded message just before sending
    # Designed to be called by phplistmailer
    # parameters:
    #   messageid: message being sent 
    #   body: current body of message
    #   header: current header of message, except for the Content-Type
    #   contenttype: Content-Type of message
    #   destination: email that this message is going out to
    # returns array(newheader,newbody,newcontenttype)
    return array (
      $header,
      $body,
      $contenttype
    );
  }

  function setFinalDestinationEmail($messageid, $uservalues, $email) {
    ### setFinalDestinationEmail
    # purpose: change the actual recipient based on user Attribute values:
    # parameters: 
    #   messageid: message being sent 
    #   uservalues: array of "attributename" => "attributevalue" of all user attributes
    #   email: email that this message is current set to go out to
    # returns: email that it should go out to
    return $email;
  }

  /**
   * messageHeaders
   *
   * return headers for the message to be added, as "key => val"
   *
   * @param object $mail
   * @return array (headeritem => headervalue)
   */
  function messageHeaders($mail) {
    return array();
  }

  /** parseFinalMessage
    * purpose: create the actual message, based on the text and html content as prepared by phplist
    * parameters:
    * sendformat: the send format chosen by the admin
    *    if this is not one of the sendFormats() set up for this plugin, return 0
    * htmlmessage: contents of HTML version of message
    * textmessage: contents of Text version of message
    * mail:  mail object that is going to be send
    * 
    ### you can alter the outgoing mail by calling the required methods of the mail object
    * returns 1 if the message has been dealt with successfully and 0 if not
    */
    
  function parseFinalMessage($sendformat, $htmlmessage, $textmessage, & $mail,$messageid) {
    return 0;
  }

  function processSuccesFailure($messageid, $sendformat, $userdata, $success= true) {
    # purpose: process the success or failure of sending an email in $sendformat
    #   if function returns false, caller will know the whole email should be marked as failed
    # Currently used in sendemaillib.php
    # 200710 Bas
    return true;
  }

  /*
   * processSendStats
   * called when sending a campaign has finished
   * @param integer $sent - amount sent
   * @param integer $invalid - amount invalid
   * @param integer $failed_sent - amount failed
   * @param integer $unconfirmed - amount unconfirmed
   *
   * receive stats from phpList for further handling
   * @return null
   */

  function processSendStats($sent,$invalid,$failed_sent,$unconfirmed) {
    return;
  }

  /* sendReport
   * @param string $subject
   * @param string $message
   * @return null
   */
  function sendReport ($subject,$message) {
  }

  /* sendError
   * @param string $subject
   * @param string $to
   * @param string $message
   * @return null
   */
  function sendError ($subject,$to = '',$message = '') {
  }
  
  /* processDBerror
   * @param integer $error number
   * @return null
   */
  function processDBerror($errorid) {
    
  }

  /* importReport
   * called when a subscriber import has finished
   * @param string $report
   * @return null
   */
  function importReport($report) {
  }

  /* processError
   * @param string msg
   */
  function processError($msg) {
    
  }

  /*
   * campaignStarted
   * called when sending of a campaign starts
   * @param array messagedata - associative array with all data for campaign
   * @return null
   */

  function campaignStarted($messagedata = array()) {
  }

  /* allowMessageToBeQueued
   * called to verify that the message can be added to the queue
   * @param array messagedata - associative array with all data for campaign
   * @return empty string if allowed, or error string containing reason for not allowing
   */
  function allowMessageToBeQueued($messagedata = array()) {
    return '';
  }

  /* messageQueued
   * called when a message is placed in the queue
   * @param integer id message id
   * @return null
   */

  function messageQueued($id) {
  }

  /* messageReQueued
   * called when a message is placed back in the queue
   * @param integer id message id
   * @return null
   */

  function messageReQueued($id) {
  }
  /* messageQueueFinished
   * called when a sending of the queue has finished
   * @return null
   */
  function messageQueueFinished() {
  }

  /* logEvent
   * @param string msg message to log
   * @return true when dealt with or false to pass on
   */
  function logEvent($msg = '') {
    return false;
  }

  /* logout
   * called on logout
   * @param none
   * @return null
   */

  function logout() {
    return '';
  }

  ############################################################
  # User

  function displayUsers( $user, $rowid, $list ) {
    # purpose: add columns for this plugin to WebblerListing
    # Currently used in users.php and members.php
    # 200710 Bas
    # (array) user, should have an id to connect to the database
    # (mixed) rowid, used to place data in the right row  of the WebblerListing
    # (WebblerListing) list, will hold the result

    return null; #@@idea: return false could mean don't display this user at all
  }
  
  function deleteUser($id) {
    # purpose: allow plugins to delete their data when deleting a user
    # 200711 Bas
    return true;
  }

  ############################################################
  # List

  function displayLists($list) {
    # purpose: return html snippet with plugin info for this list
    # Currently used in lists.php
    # 200711 Bas
    return null;
  }
  function displayEditList($list) {
    # purpose: return tablerows with list attributes for this list
    # Currently used in list.php
    # 200710 Bas
    return null;
  }

  function processEditList($id) {
    # purpose: process edit list page (usually save fields)
    # return false if failed
    # 200710 Bas
    return true;
  }

  ############################################################
  # Subscribe page

  function displaySubscribePageEdit($subscribePageData) {
    # purpose: return tablerows with subscribepage options for this list
    # Currently used in spageedit.php
    # 200710 Bas
    return null;
  }

  /*
   * unsubscribePage
   * @param email string - email to unsubscribe
   * @return true when processed, which will then override the standard subscribe page
   */

  function unsubscribePage($email) {
    return false;
  }

  function processSubscribePageEdit($subscribePageID) {
    # purpose: process selected subscribepage options for this list 
    # return false if failed
    # Currently used in spageedit.php
    # 200710 Bas
    return true;
  }
  
  function importContent() {
    # purpose: show content for this plugin on the import page
    return '';
  }

  ######################################
  # Static functions to manage the collection of plugins

  static function isEnabled($pluginName) {
    # see if a plugin is enabled, static method so it can be called even if existance of plugin is unknown.
    return array_key_exists($pluginName, $GLOBALS['plugins']) && $GLOBALS['plugins'][$pluginName]->enabled;
  }

};
?>
