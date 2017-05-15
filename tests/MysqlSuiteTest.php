<?php

require_once __DIR__ . "/CrossPlatformSuiteTest.php";

class MysqlSuiteTest extends CrossPlatformSuiteTest
{
    /**
     * @requires extension mysql
     */
    public static function suite()
    {
        $suite = new self;
        $suite->registerTests($suite);
        $suite->setTestingDriverType('mysql');
        return $suite;
    }
}

