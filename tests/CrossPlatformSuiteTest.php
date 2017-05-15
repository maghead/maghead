<?php

use PHPUnit\Framework\TestSuite;


abstract class CrossPlatformSuiteTest extends TestSuite
{
    static $crossPlatformTests = [
        'AuthorBooks\\Tests\\AuthorTest',
        'AuthorBooks\\Tests\\AuthorAddressTest',
        'AuthorBooks\\Tests\\BookTest',
        'AuthorBooks\\Tests\\AuthorBookTest',
        'AuthorBooks\\Tests\\AuthorCollectionTest',
        'PageApp\\Tests\\PageTest',
    ];


    public static function registerTests(TestSuite $suite)
    {
        if (getenv('DB') && getenv('DB') !== 'mysql') {
            return;
        }
        foreach (static::$crossPlatformTests as $testCase) {
            if (!class_exists($testCase, true)) {
                throw new Exception("$testCase doesn't exist.");
            }
            $suite->addTestSuite($testCase);
        }
    }

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
