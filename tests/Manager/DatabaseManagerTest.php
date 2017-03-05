<?php
use Maghead\Manager\ConnectionManager;
use Maghead\Manager\DatabaseManager;
use Maghead\Testing\ModelTestCase;

/**
 * @group manager
 */
class DatabaseManagerTest extends ModelTestCase
{
    public function getModels()
    {
        return [ ];
    }

    public function testCreateDB()
    {
        $dbManager = new DatabaseManager($this->connManager);
        list($conn, $ds) = $dbManager->create($this->getMasterDataSourceId(), 'test_aaa');

        $this->assertNotEmpty($ds);

        $dbManager->drop($this->getMasterDataSourceId(), 'test_aaa');
    }
}
