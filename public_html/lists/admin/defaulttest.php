<?php

class phplistTest
{
    public $name = 'Default test';
    public $purpose = 'Test to be extended to test all kinds of things';
    public $userdata = array();

    public function __construct()
    {
        /*
    $this->userdata = Sql_Fetch_Assoc_Query(sprintf('select * from %s where email = "%s"',$GLOBALS['tables']['user'],$GLOBALS['developer_email']));
    if (!$this->userdata['id']) {
      Sql_Query(sprintf('insert into %s (email) values("%s")',$GLOBALS['tables']['user'],$GLOBALS['developer_email']));
      print "Bounce user created: ".$GLOBALS['developer_email'].'<br/>';
    }
    $GLOBALS['message_envelope'] = $GLOBALS['developer_email'];
*/
        return 1;
    }

    public function runtest()
    {
        $this->userdata = Sql_Fetch_Assoc_Query(sprintf('select * from %s where email = "%s"',
            $GLOBALS['tables']['user'], $GLOBALS['developer_email']));
        if (!$this->userdata['id']) {
            Sql_Query(sprintf('insert into %s (email) values("%s")', $GLOBALS['tables']['user'],
                $GLOBALS['developer_email']));
            echo 'Bounce user created: '.$GLOBALS['developer_email'].'<br/>';
        }
        $GLOBALS['message_envelope'] = $GLOBALS['developer_email'];

        return 1;
    }
}
