<?php

class sendpage extends phplistTest
{
    public $name = 'Send a Webpage';
    public $purpose = 'Testing that sending a webpage works ok';

    public function sendpage()
    {
        parent::phplistTest();
    }

    public function runtest()
    {
        if (empty($this->userdata['email'])) {
            print $GLOBALS['I18N']->get('Test email not set ');

            return 0;
        }

        ## insert an HTML page as a message
        Sql_Query(sprintf('insert into %s
      (subject,fromfield,message,footer,entered,status,sendformat)
      values("phplist test sendpage","test","[URL:http://www.phplist.com]","Unsubscribe link: [UNSUBSCRIBE]",now(),"test","text and HTML")',
            $GLOBALS['tables']['message']));
        require_once dirname(__FILE__) . '/../sendemaillib2.php';
        $testmsg = Sql_Insert_id();
        print $GLOBALS['I18N']->get('Sending HTML version to ') . $this->userdata['email'];
        $suc6 = 0;
        $suc6 = sendEmail($testmsg, $this->userdata['email'], $this->userdata['uniqid'], 1);
        if ($suc6) {
            print ' ... ' . $GLOBALS['I18N']->get('OK');
        } else {
            print ' ... ' . $GLOBALS['I18N']->get('Failed');
        }
        print '<br/>';
        print $GLOBALS['I18N']->get('Sending Text version to ') . $this->userdata['email'];
        $suc6 = $suc6 && sendEmail($testmsg, $this->userdata['email'], $this->userdata['uniqid'], 0);
        if ($suc6) {
            print ' ... ' . $GLOBALS['I18N']->get('OK');
        } else {
            print ' ... ' . $GLOBALS['I18N']->get('Failed');
        }
        print '<br/>';
        if (CLICKTRACK) {
            print $GLOBALS['I18N']->get('Note: Links in emails will not work, because this is a test message, which is deleted after sending') . '<br/>';
        }
        print $GLOBALS['I18N']->get('Check your INBOX to see if all worked ok') . '<br/>';
        #deleteMessage($testmsg);
        print "Message ID: $testmsg<br/>";

        return $suc6;
    }
}
