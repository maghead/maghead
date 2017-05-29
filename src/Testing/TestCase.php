<?php

namespace Maghead\Testing;

/**
 * @codeCoverageIgnore
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    const DEFAULT_DRIVER_TYPE = 'sqlite';


    /**
     * @var string
     *
     * This is used for filtering test cases for specific database driver. e.g. sqlite, mysql, pgsql... etc
     */
    protected $onlyDriver;


    /**
     * @var string
     *
     * This is used for skipping specific database driver such as sqlite...
     */
    protected $skipDriver;

    protected $currentDriverType;

    public function setCurrentDriverType($type)
    {
        $this->currentDriverType = $type;
    }

    /**
     * By overriding the DB environment variable, we can test specific test suites.
     */
    public function getCurrentDriverType()
    {
        return $this->currentDriverType ?: getenv('DB') ?: self::DEFAULT_DRIVER_TYPE;
    }

    /**
     * skips the test case for the driver
     *
     * @param string $driver
     */
    protected function skipDrivers($driver)
    {
        $drivers = is_array($driver) ? $driver : func_get_args();
        if (in_array($this->getCurrentDriverType(), $drivers)) {
            return $this->markTestSkipped("Skip drivers: " . join(',', $drivers));
        }
    }

    /**
     * run the test case only for the drivers
     *
     * @param string $driver
     */
    protected function forDrivers($driver)
    {
        $drivers = is_array($driver) ? $driver : func_get_args();
        if (!in_array($this->getCurrentDriverType(), $drivers)) {
            return $this->markTestSkipped("only for drivers: " . join(',', $drivers));
        }
    }

    public function setUp()
    {
        if ($this->onlyDriver !== null && $this->forDrivers($this->onlyDriver)) {
            $this->markTestSkipped("{$this->onlyDriver} only");
        }

        if ($this->skipDriver !== null && $this->skipDrivers($this->skipDriver)) {
            $this->markTestSkipped("Skip {$this->skipDriver}");
        }

        $driver = $this->getCurrentDriverType();
        if (! extension_loaded("pdo_{$driver}")) {
            $this->markTestSkipped("pdo_{$driver} extension is required for model testing");
        }
    }

    public static function assertFileEquals($expect, $actual, $message = '', $canonicalize = false, $ignoreCase = false)
    {
        if (!file_exists($expect)) {
            echo "\n$expect\n";
            echo "==================\n";
            echo file_get_contents($actual);
            echo "==================\n";
            copy($actual, $expect);
        }
        parent::assertFileEquals($expect, $actual, $message, $canonicalize, $ignoreCase);
    }

    /*
    protected function assertPreConditions()
    {
        parent::assertPreConditions();
    }
    */
}
