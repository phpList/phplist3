<?php


use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\MinkContext;

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
     */
    public function __construct($admin = array())
    {
        $this->params = array(
            'admin_username' => $admin['username'],
            'admin_password' => $admin['password']
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
            } catch (Exception $e) {
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

    /**
     * @Given /^I have logged in as an administrator$/
     */
    public function iAmAuthenticatedAsAdmin() {
        $this->visit('/lists/admin/');
        $this->fillField('login', $this->params['admin_username']);
        $this->fillField('password', $this->params['admin_password']);
        $this->pressButton('Continue');
    }

    /**
     * @return bool
     */
    public function isLoggedIn($throwsException = false)
    {
        $retVal = $this->token != null;
        if(!$retVal && $throwsException){
            throw new Exception('Not logged in yet');
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
     * @Given I refresh the page
     */
    public function iRefreshThePage()
    {
        $this->getSession()->getDriver()->reload();
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
     * @Given I fill in :arg1 with :arg2 emails
     */
    public function iFillInWithEmails($arg1, $arg2)
    {
        $content = "";
        for ($i = 0; $i < $arg2; $i++ ){
          $content .= 'user'.$i.'@phplist.dev'.PHP_EOL;
        }
        $this->fillField($arg1, $content);
    }

    /**
     * @Given /^I should see the email address I entered$/
     */
    public function iShouldSeeTheEmailAddressIEntered()
    {
        $this->assertSession()->pageTextContains($this->data['email']);
    }

    /**
     * @Given /^I have subscriber with email "([^"]*)"/
     */
    public function iHaveSubscriber($email)
    {
        $this->clickLink('S');
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
    
    /**
     * @Then I click on :arg1
     */
    public function iClickOn($arg1)
    {  
        $arg1= $this->find("css",'submit btn btn-primary');
        $this->getSession()->click($arg1);
    }

    /**
     * @When I enter text :arg1
     * 
     * requires the CKEDITOR, which is not there by default
     */
    public function iEnterText($arg1)
    { 

        $script = <<<JS
            (function(){
        CKEDITOR.instances.message.setData( '<p>This is the editor data.</p>' ); })();
JS;
        //$this->getSession()->executeScript("document.body.innerHTML = '<p>".$arg1."</p>'");}
        $this->getSession()->evaluateScript($script);
    }

    /**
     * @Then I should read :arg1
     */
    public function iShouldRead($arg1)
    {
        $script = <<<JS
        (function(){
            CKEDITOR.instances.message.getData();})();

JS;
        $this->getSession()->evaluateScript($script);
    }

    /**
     * @Then :arg1 checkbox should be checked
     */


    /**
    * @Then /^Radio button with id "([^"]*)" should be checked$/
    */
    public function RadioButtonWithIdShouldBeChecked($sId)
    {
        $elementByCss = $this->getSession()->getPage()->find('css', 'input[type="radio"]:checked#'.$sId);
    }

    /**
     * @When I switch back from iframe
     */
    public function iSwitchBackFrom($name=null)
    {
     $this->getSession()->getDriver()->switchToIframe(null);
    }

    /**
     * @Then I switch to other iframe :arg1
     */
    public function iSwitchToOtherIframe($arg1)
    {
      $this->getSession()->switchToIframe($arg1);
    }
    
    /**
     * @Given I mouse over :arg1
     */
    public function iMouseOver($arg1)
    {
         $page = $this->getSession()->getPage();
    $findName = $page->find("xpath", '//*[@id="menuTop"]/ul[5]/li');
    if (!$findName) {
        throw new Exception($arg1 . " could not be found");
    } else {
        $findName->mouseOver();
    }
}
    /**
     * @Given I click over :arg1
     */
    public function iClickOver($arg1)
    {
         $page = $this->getSession()->getPage();
    $findName = $page->find("xpath", '//*[@id="wrapp"]/form/div[1]/div/span[1]/a');
        $findName->click();
    }

    /**
    * @Given I write :text into :field
    */
    public function iWriteTextIntoField($text, $field)
    {
      $field = $this->getSession()
        ->getDriver()
        ->getWebDriverSession()
        ->element('xpath', '//*[@id="edit_list_categories"]/div/input');
        $field->postValue(['value' => [$text]]);
    }


    /**
    * @Given I go back
    */
    public function iGoBack()
    {
        $this->getSession()->getDriver()->back();
    }

    /**
    * @When I confirm the popup
    */
    public function iConfirmThePopup()
    {  
        $this->getSession()->getDriver()->getWebDriverSession()->accept_alert();
    }

    /**
    * @Given I go back to :arg1
    */
    public function iGoBackTo($page)
    {
        $this->getSession()->getDriver()->back();
    }

    /**
    * @Then The header color should be black
    */
    public function theDivContextMenuBlockMenuColorShouldBeBlack()
    {

        // JS script that makes the CSS assertion in the browser.

        $script = <<<JS
            (function(){
                return $('#header').css('color') === 'rgb(51, 51, 51)';
            })();
JS;

        if (!$this->getSession()->evaluateScript($script)) {
            throw new Exception();
        }
    }

    /**
    * @Then I should see :message on popups
    */
    public function iShouldSeeOnPopups($message)
    {   
        return $message == $this->getSession()->getDriver()->getWebDriverSession()->getAlert_text();
    }


    /**
     * @Then I must see the email address I entered
     */
    public function iMustSeeTheEmailAddressIEntered()
    {
        throw new PendingException();
    }

    /** 
    * @Then I must see :text
    */
    public function iMustSee($text)
    {
        $maxAttempts = 3;
        $waitTimeMs = 1000;
        $this->getSession()->wait(5000, "document.readyState === 'complete'");

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $this->assertSession()->pageTextContains($this->fixStepArgument($text));
                return;
            } catch (\WebDriver\Exception\StaleElementReference $e) {
                // Handle Selenium stale element exception, retry
            } catch (\Behat\Mink\Exception\ResponseTextException $e) {
                // Handle Mink text assertion failure, retry
            }

            usleep($waitTimeMs * 1000);
        }

        throw new Exception(sprintf("Text '%s' not found after %d attempts.", $text, $maxAttempts));
    }
}
