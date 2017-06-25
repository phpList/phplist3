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
        $user = $this->params['user'];
        $password = $this->params['password'];
        $db = mysqli_init();

        mysqli_real_connect($db, 'localhost', $user, $password, 'mysql');
        mysqli_query($db,'drop database if exists phplistbehattestdb');
        mysqli_query($db,'create database phplistbehattestdb');
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

}
