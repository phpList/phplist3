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
     * Initializes context.
     * Every scenario gets its own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct($base_url, $db_user, $db_password, $db_name, $admin_username, $admin_password)
    {
    
        $this->params = array(
            'base_url' => $base_url
            , 'db_user' => $db_user
            , 'db_password' => $db_password
            , 'db_name' => $db_name
            , 'admin_username' => $admin_username
            , 'admin_password' => $admin_password
        );
        
        $this->db = mysqli_init();
        mysqli_real_connect(
            $this->db
            , 'localhost', $this->params['db_user']
            , $this->params['db_password']
            , $this->params['db_name']
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
     * @When /^I recreate the database$/
     */
    public function iRecreateTheDatabase()
    {
        mysqli_query($this->db,'drop database if exists phplistbehattestdb');
        mysqli_query($this->db,'create database phplistbehattestdb');
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
     * @Given /^I have logged in as an administrator$/
     */
    public function iAmAuthenticatedAsAdmin() {
        $this->visit($this->params['base_url'] . '/lists/admin/');
        $this->fillField('login', $this->params['admin_username']);
        $this->fillField('password', $this->params['admin_password']);
        $this->pressButton('Continue');
        
        if (null === $this->getSession ()->getPage ()->find ('named', array('content', 'Dashboard'))) {
            $this->throwExpectationException('Login failed: Dashboard link not found');
        }
    }
}
