<?php
use LazyRecord\Connection;

class ConnectionTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $conn = new Connection('sqlite::memory:');
        ok($conn);

        $driver = $conn->createQueryDriver();
        ok($driver);

        $this->assertInstanceOf('SQLBuilder\Driver\BaseDriver', $driver);
        $this->assertInstanceOf('SQLBuilder\Driver\PDOSQLiteDriver', $driver);
    }
}

