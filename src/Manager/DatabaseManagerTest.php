<?php

namespace Maghead\Manager;

use Maghead\Runtime\Config\FileConfigLoader;
use PHPUnit\Framework\TestCase;

class DatabaseManagerTest extends TestCase
{
    public function testConnectInstance()
    {
        $config = FileConfigLoader::load('tests/config/mysql.yml');
        $connectionManager = new ConnectionManager($config->getInstances());
        $conn = $connectionManager->connectInstance('local');
        $this->assertInstanceOf('Maghead\\Runtime\\Connection', $conn);
        return $conn;
    }

    /**
     * @depends testConnectInstance
     */
    public function testCreate($conn)
    {
        $dbManager = new DatabaseManager($conn);
        list($stm, $sql) = $dbManager->create('t1', [ 'charset' => 'utf8' ]);

        list($stateCode, $driverCode, $driverMsg) = $stm->errorInfo();
        $this->assertEquals('00000', $stateCode);

        list($stm, $sql) = $dbManager->create('t2');
        list($stateCode, $driverCode, $driverMsg) = $stm->errorInfo();
        $this->assertEquals('00000', $stateCode);
        return $conn;
    }

    /**
     * @depends testCreate
     */
    public function testDrop($conn)
    {
        $dbManager = new DatabaseManager($conn);
        list($stm, $sql) = $dbManager->drop('t1');
        list($stateCode, $driverCode, $driverMsg) = $stm->errorInfo();
        $this->assertEquals('00000', $stateCode);
        list($stm, $sql) = $dbManager->drop('t2');
        list($stateCode, $driverCode, $driverMsg) = $stm->errorInfo();
        $this->assertEquals('00000', $stateCode);
    }
}

