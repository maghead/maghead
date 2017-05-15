<?php

require_once __DIR__ . "/CrossPlatformSuiteTest.php";

class PgsqlSuiteTest extends CrossPlatformSuiteTest
{
    /**
     * @requires extension pgsql
     */
    public static function suite()
    {
        $suite = new self;
        $suite->registerTests($suite);
        $suite->setTestingDriverType('pgsql');
        return $suite;
    }
}

