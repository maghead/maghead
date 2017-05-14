<?php

namespace Maghead\Testing;

use Maghead\Console\Application;
use Maghead\Runtime\Bootstrap;

/**
 * @codeCoverageIgnore
 */
abstract class CommandWorkFlowTestCase extends TestCase
{
    public static $globalApp;

    public $app;

    public static function getApplication()
    {
        return static::$globalApp;
    }

    /*
    abstract public static function setupApplication();
    */

    public static function setUpBeforeClass()
    {
        static::$globalApp = static::setupApplication();
    }

    public static function tearDownAfterClass()
    {
        if ($config = Bootstrap::getConfig()) {
            if ($configServerUrl = $config->getConfigServerUrl()) {
                MongoConfigWriter::remove($config);
            }
        }
        Bootstrap::removeConfig();
    }

    public static function setupApplication()
    {
        // Note that we don't use getCurrentDriverType method because we can't
        // call dynamic method here.
        $type = getenv('DB') ?: static::DEFAULT_DRIVER_TYPE;
        copy("tests/config/$type.yml", "tests/config/tmp.yml");

        ob_start();
        $app = new Application;
        $app->run(['maghead','use','tests/config/tmp.yml']);
        ob_end_clean();
        return $app;
    }

    protected function assertPreConditions()
    {
        // fwrite(STDOUT, __METHOD__ . "\n");
        $this->app = static::$globalApp;
    }


}
