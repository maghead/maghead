<?php
use Maghead\Manager\DataSourceManager;
use Maghead\Manager\ConnectionManager;
use Maghead\Manager\DatabaseManager;
use Maghead\Testing\ModelTestCase;

/**
 * @group manager
 */
class DatabaseManagerTest extends ModelTestCase
{
    protected $skipDriver = 'sqlite';

    public function models()
    {
        return [ ];
    }

    /**
     * @rebuild false
     */
    public function testCreate()
    {
        $connectionManager = new ConnectionManager($this->config->getInstances());
        $conn = $connectionManager->connectInstance('local');
        $this->assertNotNull($conn);

        $dbManager = new DatabaseManager($conn);
        $dbManager->create('t1', [ 'charset' => 'utf8' ]);
        $dbManager->create('t2');
    }

    /**
     * @rebuild false
     * @depends testCreate
     */
    public function testDrop()
    {
        $connectionManager = new ConnectionManager($this->config->getInstances());
        $conn = $connectionManager->connectInstance('local');
        $this->assertNotNull($conn);

        $dbManager = new DatabaseManager($conn);
        $dbManager->drop('t1');
        $dbManager->drop('t2');
    }
}
