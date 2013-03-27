<?php

/**
 * This class make sure to launch Selenium Server, Selenium client
 * and then run the tests.
 *
 * PS: requires "alexandresalome/php-selenium" package.
 *
 */

class AcceptanceTestCase extends TestCase
{
    /**
     * Is selenium server launched
     *
     */
    static protected $seleniumLaunched = false;

    /**
     * Is the php server launched
     *
     */
    static protected $serverLaunched = false;

    /**
     * The selenium browser
     *
     */
    static protected $loadedBrowser = null;

    /**
     * The "alexandresalome/php-selenium" browser
     */
    public $browser;

    /**
     * Launch the selenium server and the php build in server (PHP 5.4)
     * before running any test within this class
     * 
     */
    public static function setUpBeforeClass()
    {
        static::launchSelenium();
        static::launchServer();
    }

    /**
     * Kill the php server and the selenium browser
     *
     */
    public static function tearDownAfterClass()
    {
        static::killServer();
        if(AcceptanceTestCase::$loadedBrowser)
        {
            AcceptanceTestCase::$loadedBrowser->stop();
            AcceptanceTestCase::$loadedBrowser = null;
        }
    }

    /**
     * Start selenium browser (that connects to the selenium 
     * server)
     *
     */
    public function setUp()
    {
        parent::setUp();

        $this->startbrowser();
    }

    /**
     * Asserts if the page has some text
     *
     */
    public function assertBodyHasText($needle)
    {
        $text = $this->browser->getBodyText();

        $needle = (array)$needle;

        foreach ($needle as $singleNiddle) {
            $this->assertContains($singleNiddle, $text, "Body text does not contain '$singleNiddle'");
        }
    }

    /**
     * Inverse of the above method ;B
     *
     */
    public function assertBodyHasNotText($needle)
    {
        $text = $this->browser->getBodyText();

        $needle = (array)$needle;

        foreach ($needle as $singleNiddle) {
            $this->assertNotContains($singleNiddle, $text, "Body text does not contain '$singleNiddle'");
        }
    }

    /**
     * Asserts if an specific element has some content
     *
     */
    public function assertElementHasText($locator, $needle)
    {
        $text = $this->browser->getText($locator);

        $needle = (array)$needle;

        foreach ($needle as $singleNiddle) {
            $this->assertContains($singleNiddle, $text, "Body text does not contain '$singleNiddle'");
        }
    }

    /**
     * Inverse of the above method ;B
     *
     */
    public function assertElementHasNotText($locator, $needle)
    {
        $text = $this->browser->getText($locator);

        $needle = (array)$needle;

        foreach ($needle as $singleNiddle) {
            $this->assertNotContains($singleNiddle, $text, "Given element do contain '$singleNiddle' but it shoudn't");
        }
    }

    /**
     * Asserts if the page has some html
     *
     */
    public function assertBodyHasHtml($needle)
    {
        $html = str_replace("\n", '', $this->browser->getHtmlSource());

        $needle = (array)$needle;

        foreach ($needle as $singleNiddle) {
            $this->assertContains($singleNiddle, $html, "Body html does not contain '$singleNiddle'");
        }
    }

    /**
     * Asserts location
     *
     */
    public function assertLocation($location)
    {
        $current_location = substr($this->browser->getLocation(), strlen($location)*-1);

        $this->assertEquals($current_location, $location, "The current location ($current_location) is not '$location'");
    }

    /**
     * Protected method that starts the browser
     *
     */
    protected function startBrowser()
    {
        if(! AcceptanceTestCase::$loadedBrowser)
        {
            $client  = new Selenium\Client('localhost', 4444);
            $this->browser = $client->getBrowser('http://localhost:4443');
            $this->browser->start();
            $this->browser->windowMaximize();

            AcceptanceTestCase::$loadedBrowser = $this->browser;
        }
        else
        {
            $this->browser = AcceptanceTestCase::$loadedBrowser;
            $this->browser->open('/');
        }
        
    }

    /**
     * Protected method that launch selenium server.
     *
     * PS: For this to works you should place the selenium server 
     * (the <something>.jar file) inside the ~/.selenium/ directory.
     *
     */
    protected static function launchSelenium()
    {
        if(AcceptanceTestCase::$seleniumLaunched)
            return;

        if(@fsockopen('localhost', 4444) == false)
        {
            $selenium_dir = $_SERVER['HOME'].'/.selenium';
            $files = scandir($selenium_dir);

            foreach ($files as $file) {
                if(substr($file,-4) == '.jar')
                {
                    $command = "java -jar $selenium_dir/$file";
                    static::execAsync($command);
                    sleep(1);
                    break;
                }
            }
        }

        AcceptanceTestCase::$seleniumLaunched = true;
    }

    /**
     * Launch the PHP server (php artisan serve)
     *
     */
    protected static function launchServer()
    {
        if(AcceptanceTestCase::$serverLaunched)
            return;

        $command = "php artisan serve --port 4443";
        static::execAsync($command);

        AcceptanceTestCase::$serverLaunched = true;
    }

    /**
     * Kills selenium server, find it by the port.
     *
     */
    protected static function killSelenium()
    {
        static::killProcessByPort('4444');
        AcceptanceTestCase::$seleniumLaunched = false;
    }

    /**
     * Kills php server, find it by the port.
     *
     */
    protected static function killServer()
    {
        static::killProcessByPort('4443');
        AcceptanceTestCase::$serverLaunched = false;
    }

    /**
     * Method to run exec() function asyncrously
     *
     */
    private static function execAsync($command)
    {
        $force_async = " >/dev/null 2>&1 &";
        exec($command.$force_async);
    }

    /**
     * Find and kills a process by port :B
     *
     */
    private static function killProcessByPort($port)
    {
        $processInfo = exec("lsof -i :$port");
        preg_match('/^\S+\s*(\d+)/', $processInfo, $matches);

        if(isset($matches[1]))
        {
            $pid = $matches[1];
            exec("kill $pid");
        }
    }
}
