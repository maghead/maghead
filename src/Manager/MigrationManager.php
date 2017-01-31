<?php
namespace Maghead\Manager;

use Maghead\Manager\MetadataManager;
use Maghead\Manager\ConnectionManager;
use Maghead\Migration\MigrationLoader;
use Maghead\Migration\MigrationRunner;
use Maghead\Connection;
use Maghead\ServiceContainer;
use GetOptionKit\OptionResult;
use CLIFramework\Logger;
use SQLBuilder\Driver\BaseDriver;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;


/**
 * Top layer API for migration
 */
class MigrationManager
{
    protected $connectionManager;

    protected $logger;

    public function __construct(ConnectionManager $connectionManager, Logger $logger)
    {
        $this->connectionManager = $connectionManager;
        $this->logger = $logger;
    }

    public function upgrade(array $ids = null, $steps = 1)
    {
        if (!$ids) {
            $ids = $this->connectionManager->getDataSourceIdList();
        }
        foreach ($ids as $id) {
            $this->logger->info("Performing upgrade on node $id");
            $conn = $this->connectionManager->getConnection($id);
            $driver = $conn->getQueryDriver();

            $scripts = MigrationLoader::getDeclaredMigrationScripts();
            $runner = new MigrationRunner($scripts, $this->logger);
            $runner->runUpgrade($conn, $driver);

            $this->logger->info("node $id is successfully migrated.");
        }
    }

    public function downgrade(array $ids = null, $steps = 1)
    {
        if (!$ids) {
            $ids = $this->connectionManager->getDataSourceIdList();
        }
        foreach ($ids as $id) {
            $this->logger->info("Performing downgrade on node $id");
            $conn = $this->connectionManager->getConnection($id);
            $driver = $conn->getQueryDriver();

            $scripts = MigrationLoader::getDeclaredMigrationScripts();
            $runner = new MigrationRunner($scripts, $this->logger);
            $runner->runDowngrade($conn, $driver);

            $this->logger->info("node $id is successfully migrated.");
        }

    }


}
