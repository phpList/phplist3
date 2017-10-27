<?php

namespace Context;

use Behat\Behat\Context\Context as ContextInterface;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\MinkExtension\Context\RawMinkContext;

abstract class BaseContext extends RawMinkContext implements ContextInterface, SnippetAcceptingContext
{
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

    // Output page contents in case of failure
    protected function throwExpectationException($message)
    {
        throw new ExpectationException($message, $this->getSession());
    }
}


