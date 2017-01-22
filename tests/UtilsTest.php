<?php
use Maghead\Utils;

class UtilsTest extends PHPUnit_Framework_TestCase
{
    public function testBreakDSN()
    {
        $params = Utils::breakDSN('pgsql:host=localhost;dbname=lazy_test');
        $this->assertEquals( 'pgsql' , $params['driver'] );
        $this->assertEquals( 'localhost' , $params['host'] );
        $this->assertEquals( 'lazy_test' , $params['dbname'] );
    }

    public function testSqliteMemDSN()
    {
        $params = Utils::breakDSN('sqlite::memory:');
        $this->assertEquals( 'sqlite' , $params['driver'] );
        ok( $params[':memory:'] );
    }

    public function testEvaluateFunction()
    {
        $this->assertEquals( 1, Utils::evaluate(1) );
        $this->assertEquals( 2, Utils::evaluate( function() { return 2; }) );
    }
}

