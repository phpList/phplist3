<?php

namespace Context;

use Behat\Testwork\Tester\Result\TestResult;
use Behat\Mink\Driver\Selenium2Driver;

class ScreenshotContext extends BaseContext
{
    protected $scenarioTitle = null;
    protected static $wsendUser = null;

    /**
     * @BeforeScenario
     */
    public function cacheScenarioName($event)
    {
        // it's only to have a clean screenshot name later
        $this->scenarioTitle = $event->getScenario()->getTitle();
    }

    /**
     * @AfterStep
     */
    public function takeScreenshotAfterFailedStep($event)
    {
        if ($event->getTestResult()->getResultCode() !== TestResult::FAILED) {
            return;
        }

        $this->takeAScreenshot();
    }

    /**
     * @Then take a screenshot
     */
    public function takeAScreenshot()
    {
        if (!$this->isJavascript()) {
            print "Screenshot cannot be taken from non javascript scenario.\n";

            return;
        }

        $screenshot = $this->getSession()->getDriver()->getScreenshot();

        $filename = $this->getScreenshotFilename();
        file_put_contents($filename, $screenshot);

        $url = $this->getScreenshotUrl($filename);

        print sprintf("Screenshot is available :\n%s", $url);
    }

    protected function getScreenshotFilename()
    {
        $filename = $this->scenarioTitle;
        $filename = preg_replace("#[^a-zA-Z0-9\._-]#", '_', $filename);

        if (!is_dir(__DIR__.'/output/screenshots/')) {
            mkdir(__DIR__.'/output/screenshots/',0777,true);
        }

        return sprintf('%s//output/screenshots/%s.png', __DIR__, $filename);
    }

    protected function isJavascript()
    {
        return $this->getSession()->getDriver() instanceof Selenium2Driver;
    }
}
