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

    protected function getScreenshotUrl($filename)
    {
        if (!self::$wsendUser) {
            self::$wsendUser = $this->getWsendUser();
        }

        exec(sprintf(
            'curl -F "uid=%s" -F "filehandle=@%s" %s 2>/dev/null',
            self::$wsendUser,
            $filename,
            'https://wsend.net/upload_cli'
        ), $output, $return);

        return $output[0];
    }

    protected function getWsendUser()
    {
        // create a wsend anonymous user
        $curl = curl_init('https://wsend.net/createunreg');
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'start=1');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $wsendUser = curl_exec($curl);
        curl_close($curl);

        return $wsendUser;
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
