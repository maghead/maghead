<?php
use Maghead\Connection;

class ConnectionTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $conn = new Connection('sqlite::memory:');
        $driver = $conn->getQueryDriver();
        $this->assertInstanceOf('SQLBuilder\Driver\BaseDriver', $driver);
        $this->assertInstanceOf('SQLBuilder\Driver\PDOSQLiteDriver', $driver);
    }
}

