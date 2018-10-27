<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;


/**
 * Class MailContext
 */
class MailContext implements Context
{
    private $mailDir;

    /**
     * @var Message[]
     */
    private $messages = [];

    private $renderSentEmailTable = true;

    public function __construct($mail_dir)
    {
        $this->mailDir = $mail_dir;
    }

    /**
     * Empty mails directory before scenario
     *
     * @BeforeScenario @emptySentMail
     * @Then I have empty mailbox
     */
    public function iHaveEmptyEmailBox()
    {
        $files = $this->getMailFiles();
        foreach($files as $file){
            unlink($file);
        }
        // always empty messages before scenario begin
        $this->messages = [];
    }

    /**
     * @Given /I should have email sent with the following:/
     */
    public function iHaveSentEmailWithTheFollowing(TableNode $node)
    {
        // we should disable rendering sent email table first.
        $this->renderSentEmailTable = false;

        $map = array(
            'from' => 'iHaveEmailSentFrom',
            'from_address' => 'iHaveEmailSentFromAddress',
            'to_address' => 'iHaveEmailSentToAddress',
            'to' => 'iHaveSentEmailToAddress',
        );
        $rows = $node->getRowsHash();
        $exceptions = [];
        foreach($rows as $name => $expected){
            $underscore = strtr($name,array(
                ' ' => "_",
            ));
            if(!isset($map[$underscore])){
                throw new \InvalidArgumentException(sprintf(
                    'Can not find email sent property for this "%s" key. Valid property is: ',
                    implode(", ", array_keys($map))
                ));
            }
            $callback = [$this, $map[$underscore]];
            try {
                call_user_func($callback,$expected);
            }catch (\Exception $e){
                $exceptions[] = $e->getMessage();
            }
        }

        $this->renderSentEmailTable = true;

        if(count($exceptions) > 0){
            $message = sprintf(
                "Failed to assert sent email. Error Messages:\n%s".PHP_EOL,
                implode(PHP_EOL,$exceptions)
            );
            $this->renderException($message);
        }
    }


    /**
     * @Given /I should have email sent from address "(.*)"/
     * @throws Exception
     */
    public function iHaveEmailSentFromAddress($from)
    {
        if(!$this->assertEmailProperty('getFromAddress',$from)){
            $this->renderException(sprintf(
                'No email sent from "%s" address.',
                $from
            ));
        }
    }

    /**
     * @Given /I should have email sent from "(.*)"/
     */
    public function iHaveEmailSentFrom($senderName)
    {
        if(!$this->assertEmailProperty('getFromName',$senderName)){
            $this->renderException(sprintf(
                'No sender with "%s" name found in sent email.',
                $senderName
            ));
        }
    }

    /**
     * @Given /I should have email sent to address "(.*)"/
     */
    public function iHaveEmailSentToAddress($to)
    {
        if(!$this->assertEmailProperty('getToAddress',$to)){
            $this->renderException(sprintf(
                'No sent email found to "%s" address.',
                $to
            ));
        }
    }

    /**
     * @Given /^I should have email sent to "(.*)"/
     */
    public function iHaveEmailSentToName($toName)
    {
        if(!$this->assertEmailProperty('getToName',$toName)){
            $this->renderException(sprintf(
                'No sent email found to "%s".',
                $toName
            ));
        }
    }

    /**
     * @Given /I should have email sent contains "(.*)"/
     * @throws AssertSentEmailException
     */
    public function iHaveEmailSentContains($expected)
    {
        if(!$this->assertEmailProperty('getTextMessage',$expected,true)){
            $this->renderException(sprintf(
                'There are no sent email messages containing "%s".',
                $expected
            ));
        }
    }

    /**
     * @param $message
     * @throws AssertSentEmailException
     */
    private function renderException($message)
    {
        throw new AssertSentEmailException($message);
    }

    private function assertEmailProperty($property,$expected, $assertContains = false)
    {
        $state = false;
        foreach($this->getMessages() as $message){
            $callback = [$message,$property];
            if(!is_callable($callback)){
                throw new \Exception(
                    sprintf(
                        'Function "%s" is not callable for "%s" class.',
                        $property,
                        get_class($message)
                    )
                );
            }
            $value = call_user_func($callback);
            if(is_array($value)){
                if(in_array($expected,$value)){
                    $state = true;
                    break;
                }
            }
            elseif($assertContains){
                if(strpos($value,$expected) !== false){
                    $state = true;
                    break;
                }
            }
            elseif($value == $expected){
                $state = true;
                break;
            }
        }
        return $state;
    }

    /**
     * @return Message[]
     */
    private function getMessages()
    {
        if(empty($this->messages)){
            $files = $this->getMailFiles();
            foreach($files as $file){
                $contents = file_get_contents($file,LOCK_EX);
                $this->messages[] = Message::from($contents);
            }
        }
        return $this->messages;
    }

    private function getMailFiles()
    {
        clearstatcache(true);
        $files = [];
        foreach(scandir($this->mailDir) as $fileName){
            if($fileName == '.' || $fileName == '..') continue;
            $files[] = $this->mailDir.DIRECTORY_SEPARATOR.$fileName;
        }
        return $files;
    }
}

class AssertSentEmailException extends \Exception
{
}