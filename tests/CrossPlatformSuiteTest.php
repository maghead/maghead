<?php

use PHPUnit\Framework\TestSuite;

abstract class AbstractDatabaseTestSuite extends TestSuite
{
    static $crossPlatformTests = [
        'AuthorBooks\Tests\AuthorTest',
        'AuthorBooks\Tests\BookTest',
    ];

    public function setTestingDriverType($type)
    {
        foreach ($this->tests() as $ts) {
            foreach ($ts->tests() as $tc) {
                if (method_exists($tc, 'setCurrentDriverType')) {
                    $tc->setCurrentDriverType($type);
                }
            }
        }
    }

}

class PgsqlSuiteTest extends AbstractDatabaseTestSuite
{
    /**
     * @requires extension pgsql
     */
    public static function suite()
    {
        $suite = new self;
        foreach (static::$crossPlatformTests as $testCase) {
            $suite->addTestSuite($testCase);
        }
        $suite->setTestingDriverType('pgsql');
        return $suite;
    }
}

class MysqlSuiteTest extends AbstractDatabaseTestSuite
{
    /**
     * @requires extension mysql
     */
    public static function suite()
    {
        $suite = new self;
        foreach (static::$crossPlatformTests as $testCase) {
            $suite->addTestSuite($testCase);
        }
        $suite->setTestingDriverType('mysql');
        return $suite;
    }
}

class SqliteSuiteTest extends AbstractDatabaseTestSuite
{
    /**
     * @requires extension sqlite
     */
    public static function suite()
    {
        $suite = new self;
        foreach (static::$crossPlatformTests as $testCase) {
            $suite->addTestSuite($testCase);
        }
        $suite->setTestingDriverType('sqlite');
        return $suite;
    }
}
