<?php

use Behat\MinkExtension\Context\MinkContext;

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
    public function __construct(array $parameters)
    {
        $this->params = $parameters;
        $this->db = mysqli_init();
        mysqli_real_connect(
            $this->db
            , 'localhost', $this->params['db_user']
            , $this->params['db_password']
            , $this->params['db_name']
        );
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
        $result = mysqli_fetch_assoc(mysqli_query($this->db,'select count(*) as count from phplist_message;'));
        $campaignCount = $result['count'];

        if ($campaignCount > 0) {
            throw new Exception('One or more campagins already exist');
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
    }

}
