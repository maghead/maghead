<?php
use Maghead\Manager\DataSourceManager;
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
        $dbManager = new DatabaseManager($this->dataSourceManager);
        list($conn, $ds) = $dbManager->create($this->getMasterDataSourceId(), 'test_aaa');

        $this->assertNotEmpty($ds);

        // free the connection
        $conn = null;

        $dbManager->drop($this->getMasterDataSourceId(), 'test_aaa');
    }
}
