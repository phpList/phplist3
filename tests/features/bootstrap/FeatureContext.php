<?php

use Behat\Behat\Context\Context;

use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\MinkContext;
#use Behat\MinkExtension\Context\RawMinkContext;

//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//

/**
 * Features context.
 */
class FeatureContext extends MinkContext
{
    private $params = array();
    private $data = array();

    /**
     * @var mysqli
     */
    private $db;

    /**
     * Null if user is not logged in
     * @var string
     */
    private $token;

    /**
     * @var array
     */
    private $currentUser;

    /**
     * Initializes context.
     * Every scenario gets its own context object.
     *
     * @param array $admin
     * @param array $database
     */
    public function __construct( $database = array(), $admin = array())
    {
        // merge default database value into configured value
        $database = array_merge(array(
            'host'      => 'localhost',
            'password'  => 'phplist',
            'user'      => 'phplist',
            'name'      => 'phplistdb'
        ),$database);

        // merge default admin user value into configured value
        $admin = array_merge(array(
            'username' => 'admin',
            'password' => 'admin'
        ),$admin);

        $this->params = array(
            'db_host' => $database['host'],
            'db_user' => $database['user'],
            'db_password' => $database['password'],
            'db_name' => $database['name'],
            'admin_username' => $admin['username'],
            'admin_password' => $admin['password']
        );
        
        $this->db = mysqli_init();
        mysqli_real_connect(
            $this->db,
            $database['host'],
            $database['user'],
            $database['password'],
            $database['name']
        );
    }

    public function __call($method, $parameters)
    {
        // we try to call the method on the Page first
        $page = $this->getSession()->getPage();
        if (method_exists($page, $method)) {
            return call_user_func_array(array($page, $method), $parameters);
        }

        // we try to call the method on the Session
        $session = $this->getSession();
        if (method_exists($session, $method)) {
            return call_user_func_array(array($session, $method), $parameters);
        }

        // could not find the method at all
        throw new \RuntimeException(sprintf(
            'The "%s()" method does not exist.', $method
        ));
    }

    /**
     * Everyone who tried Behat with Mink and a JavaScript driver (I use 
     * Selenium2Driver with phantomjs) has had issues with trying to assert something 
     * in the current web page while some JavaScript code has not been finished yet 
     * (pending Ajax query for example).
     * 
     * The proper and recommended way of dealing with these issues is to use a spin 
     * method in your context, that will run the assertion or code multiple times 
     * before failing. Here is my implementation that you can add to your BaseContext:
     */
    public function spins($closure, $tries = 10)
    {
        for ($i = 0; $i <= $tries; $i++) {
            try {
                $closure();

                return;
            } catch (\Exception $e) {
                if ($i == $tries) {
                    throw $e;
                }
            }

            sleep(1);
        }
    }
    
    // Output page contents in case of failure
    // TODO: extend docs
    protected function throwExpectationException($message)
    {
        throw new ExpectationException($message, $this->getSession());
    }

    /**
     * @When something long is taking long but should output :text
     */
    public function somethingLongShouldOutput($text)
    {
        $this->find('css', 'button#longStuff')->click();

        $this->spins(function() use ($text) { 
            $this->assertSession()->pageTextContains($text);
        });
    }

    /**
     * @Then do something on a button that might not be there yet
     */
    public function doSomethingNotThereYet()
    {
        $this->spins(function() { 
            $button = $this->find('css', 'button#mightNotBeThereYet');
            if (!$button) {
                throw \Exception('Button is not there yet :(');
            }
            $button->click();
        });
    }


//
// Place your definition and hook methods here:
//
//    /**
//     * @Given /^I have done something with "([^"]*)"$/
//     */
//    public function iHaveDoneSomethingWith($argument)
//    {
//        doSomethingWith($argument);
//    }
//
    /**
     * @Given /^I have logged in as an administrator$/
     */
    public function iAmAuthenticatedAsAdmin() {
        $this->visit('/lists/admin/');
        $this->fillField('login', $this->params['admin_username']);
        $this->fillField('password', $this->params['admin_password']);
        $this->pressButton('Continue');

        if (null === $this->getSession ()->getPage ()->find ('named', array('content', 'Dashboard'))) {
            $this->throwExpectationException('Login failed: Dashboard link not found');
        }

        // store current token
        $link = $this->getSession()->getPage()->findLink('dashboard');
        $href = $link->getAttribute('href');
        $this->token = substr($href,strpos($href,'tk=')+3);
        $this->currentUser = $this->generateCurrentUserInfo($this->params['admin_username']);
    }

    /**
     * @param $name
     * @return array
     * @throws Exception
     */
    private function generateCurrentUserInfo($name)
    {
        $db = $this->getMysqli();
        $query = sprintf(
            'SELECT * from %s where loginname="%s"',
            'phplist_admin',
            $name
        );
        $results = $db->query($query)->fetch_assoc();
        if(!isset($results['id']) ){
            throw new \Exception($db->error);
        }
        return $results;
    }
    /**
     * @return bool
     */
    public function isLoggedIn($throwsException = false)
    {
        $retVal = $this->token != null;
        if(!$retVal && $throwsException){
            throw new \Exception('Not logged in yet');
        }
        return $retVal;
    }

    /**
     * @return array
     */
    public function getCurrentUser()
    {
        $this->isLoggedIn(true);
        return $this->currentUser;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        $this->isLoggedIn(true);
        return $this->token;
    }

    /**
     * @When /^I recreate the database$/
     */
    public function iRecreateTheDatabase()
    {
        mysqli_query($this->db,'drop database if exists '.$this->params['db_name']);
        mysqli_query($this->db,'create database '.$this->params['db_name']);
    }
    
    /**
     * @When I fill in :arg1 with a valid username
     */
    public function iFillInWithAValidUsername($arg1)
    {
        $this->fillField($arg1, $this->params['admin_username']);
    }

    /**
     * @When I fill in :arg1 with a valid password
     */
    public function iFillInWithAValidPassword($arg1)
    {
        $this->fillField($arg1, $this->params['admin_password']);
    }

    /**
     * @When /^I fill in "([^"]*)" with an email address$/
     */
    public function iFillInWithAnEmailAddress($fieldName)
    {
        $this->data['email'] = 'email@domain.com'; // at some point really make random
        $this->fillField($fieldName, $this->data['email']);
    }

    /**
     * @Given /^I should see the email address I entered$/
     */
    public function iShouldSeeTheEmailAddressIEntered()
    {
        $this->assertSession()->pageTextContains($this->data['email']);
    }

    /**
     * @Given /^No campaigns yet exist$/
     */
    public function iHaveNotYetCreatedCampaigns()
    {
        // Count the number of campaigns in phplist_message table
        $result = mysqli_fetch_assoc(
            mysqli_query(
                $this->db,'
                    select 
                        count(*) as count 
                    from 
                        phplist_message;
                ')
        );
        $campaignCount = $result['count'];

        if ($campaignCount > 0) {
            $this->throwExpectationException('One or more campagins already exist');
        }
    }

    /**
     * @Given /^I have subscriber with email "([^"]*)"/
     */
    public function iHaveSubscriber($email)
    {
        $this->clickLink('S');
    }

    /**
     * @return mysqli
     */
    public function getMysqli()
    {
        return $this->db;
    }

    /**
     * @var array $params
     * @return string
     */
    public function generateUrl($params)
    {
        $token = $this->getToken();
        $params['tk'] = $token;
        $url = $this->getSession()->getCurrentUrl();

        $queryPath = [];
        foreach($params as $name=>$value){
            $queryPath[] = $name.'='.$value;
        }
        $link = $url.'?'.implode('&',$queryPath);
        return $link;
    }

    /**
     * @param $num
     * @Then /^I wait for .* (second|seconds)/
     */
    public function iWaitForSeconds($num)
    {
        $num = (int) $num;
        sleep($num);
    }

    /**
     * @Then /^I wait for the ajax response$/
     */
    public function iWaitForTheAjaxResponse()
    {
        $this->getSession()->wait(5000, '(0 === jQuery.active)');
    }
}
