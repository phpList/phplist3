<?php

## testing the sendMailDirect function

# print function sendMailDirect($email, $subject, $message) {

class sendmaildirect extends phplistTest
{
    public $name = 'sendMailDirect';
    public $purpose = 'SMTP delivery of request for confirmation';

    public function runtest()
    {

    ## for this test to work, we should not use the developer_email
    unset($GLOBALS['developer_email']);

        print '<br/>Should be successful: ';
    ## this one should succeed
    $ok = sendMailDirect('info@phplist.com', 'Test', 'Test sending');
        if ($ok) {
            print $GLOBALS['img_tick'];
        } else {
            print $GLOBALS['img_cross'];
        }

    ## and this one fail
    print '<br/>Should fail: ';
        $fail = sendMailDirect('phplist.admin.invalidemail@gmail.com', 'Test', 'Test sending');
        if (!$fail) {
            print $GLOBALS['smtpError'];
            print $GLOBALS['img_tick'];
        } else {
            print $GLOBALS['img_cross'];
        }

        return $ok && !$fail;
    }
}
