<?php

namespace Maghead\Runtime\Connector;

use PHPUnit\Framework\TestCase;

class PDOConnectorTest extends TestCase
{
    public function testSqliteConnector()
    {
        $conn = PDOConnector::connect([
            'driver' => 'sqlite',
            'dsn' => 'sqlite::memory:',
            'user' => null,
            'password' => null,
            'connection_options' => null,
        ]);
        $this->assertInstanceOf('Maghead\\Runtime\\Connection', $conn);
    }
}


