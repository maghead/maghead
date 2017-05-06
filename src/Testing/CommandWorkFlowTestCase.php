<?php

namespace Maghead\Testing;

use Maghead\Console;

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

    public static function setupApplication()
    {
        $type = static::getCurrentDriverType();
        copy("tests/config/$type.yml", "tests/config/tmp.yml");

        ob_start();
        $app = new Console;
        $app->run(['maghead','use','tests/config/tmp.yml']);
        ob_clean();
        return $app;
    }

    protected function assertPreConditions()
    {
        // fwrite(STDOUT, __METHOD__ . "\n");
        $this->app = static::$globalApp;
    }
}
