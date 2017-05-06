<?php

use CLIFramework\Testing\CommandTestCase;
use Maghead\Console;
use PHPUnit\Framework\TestCase;



abstract class CommandWorkFlowTestCase extends TestCase
{
    public static $app;

    public static function getApplication()
    {
        return static::$app;
    }

    abstract public static function setupApplication();

    public static function setUpBeforeClass()
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        static::$app = static::setupApplication();
    }
}

/**
 * @group command
 */
class DbCommandsTest extends CommandWorkFlowTestCase
{
    public static function getCurrentDriverType()
    {
        return getenv('DB') ?: 'sqlite';
    }

    public static function setupApplication()
    {
        $type = static::getCurrentDriverType();
        $app = new Console;
        if ($type == "sqlite") {
            return $this->markTestSkipped('sqlite migration is not supported.');
        }
        copy("tests/config/$type.yml", "tests/config/tmp.yml");
        $app->run(['maghead','use','tests/config/tmp.yml']);
        return $app;
    }

    public function testDbList()
    {
        $this->expectOutputRegex('/master/');
        static::$app->run(['maghead','db','list']);
    }

    /**
     * @depends testDbList
     */
    public function testDbCreate()
    {
        $ret = static::$app->run(["maghead","db","add","--user", "root", "testing2",  "mysql:host=localhost;dbname=testing2"]);
        $this->assertTrue($ret);
    }

    /**
     * @depends testDbCreate
     */
    public function testDbReCreate()
    {
        $this->expectOutputRegex('/Database testing2 is dropped successfully/');
        $ret = static::$app->run(['maghead','db','recreate', 'testing2']);
        $this->assertTrue($ret);
    }

    /**
     * @depends testDbReCreate
     */
    public function testDbDrop()
    {
        $this->expectOutputRegex('/Database testing2 is dropped successfully/');
        $ret = static::$app->run(['maghead','db','drop', 'testing2']);
        $this->assertTrue($ret);
    }

    /**
     * @depends testDbDrop
     */
    public function testDbRemove()
    {
        $this->expectOutputRegex('/testing2 is removed successfully/');
        $ret = static::$app->run(['maghead','db','remove', 'testing2']);
        $this->assertTrue($ret);
    }
}
