<?php

require_once __DIR__ . "/CrossPlatformSuiteTest.php";

class SqliteSuiteTest extends CrossPlatformSuiteTest
{
    /**
     * @requires extension sqlite
     */
    public static function suite()
    {
        $suite = new self;
        $suite->registerTests($suite);
        $suite->setTestingDriverType('sqlite');
        $suite->addTestSuite('Maghead\\TableParser\\SqliteTableParserTest');
        return $suite;
    }
}
