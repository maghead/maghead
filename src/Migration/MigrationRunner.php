<?php

namespace Maghead\Migration;

use Maghead\Manager\MetadataManager;
use Maghead\Manager\ConnectionManager;
use Maghead\Connection;
use Maghead\ServiceContainer;
use GetOptionKit\OptionResult;
use CLIFramework\Logger;
use SQLBuilder\Driver\BaseDriver;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MigrationRunner
{
    protected $logger;

    protected $dataSourceIds = array();

    protected $connectionManager;

    public function __construct(Logger $logger = null, $dsIds)
    {
        if (!$logger) {
            $c = ServiceContainer::getInstance();
            $logger = $c['logger'];
        }
        $this->logger = $logger;
        $this->connectionManager = ConnectionManager::getInstance();

        // XXX: get data source id list from config loader
        $this->dataSourceIds = (array) $dsIds;
    }

    public function getLastMigrationId(Connection $conn, BaseDriver $driver)
    {
        $meta = new MetadataManager($conn, $driver);

        return $meta['migration'] ?: 0;
    }

    public function resetMigrationId(Connection $conn, BaseDriver $driver)
    {
        $metadata = new MetadataManager($conn, $driver);
        $metadata['migration'] = 0;
    }

    public function updateLastMigrationId(Connection $conn, BaseDriver $driver, $id)
    {
        $metadata = new MetadataManager($conn, $driver);
        $lastId = $metadata['migration'];
        $metadata['migration'] = $id;
    }

    /**
     * Each data source has it's own migration timestamp,
     * we use the data source ID to get the migration timestamp 
     * and filter the migration script.
     *
     * @param string $dsId
     */
    public function getUpgradeScripts(Connection $conn, BaseDriver $driver)
    {
        $lastMigrationId = $this->getLastMigrationId($conn, $driver);
        $this->logger->debug("Found last migration id: $lastMigrationId");
        $scripts = MigrationLoader::getDeclaredMigrationScripts();
        return array_filter($scripts, function ($class) use ($lastMigrationId) {
            $id = $class::getId();

            return $id > $lastMigrationId;
        });
    }

    public function getDowngradeScripts(Connection $conn, BaseDriver $driver)
    {
        $scripts = MigrationLoader::getDeclaredMigrationScripts();
        $lastMigrationId = $this->getLastMigrationId($conn, $driver);

        return array_filter($scripts, function ($class) use ($lastMigrationId) {
            $id = $class::getId();

            return $id <= $lastMigrationId;
        });
    }

    /**
     * Run downgrade scripts.
     */
    public function runDowngrade(Connection $conn, BaseDriver $driver, array $scripts = null, $steps = 1)
    {
        if (!$scripts) {
            $scripts = $this->getDowngradeScripts($conn, $driver);
        }
        $this->logger->info('Found '.count($scripts).' migration scripts to run downgrade!');
        while ($steps--) {
            // downgrade a migration one at one time.
            if ($script = array_pop($scripts)) {
                $this->logger->info("Running {$script}::downgrade");
                $migration = new $script($conn, $driver, $this->logger);
                $migration->downgrade();
                if ($nextScript = end($scripts)) {
                    $id = $nextScript::getId();
                    $this->updateLastMigrationId($conn, $driver, $id);
                    $this->logger->info("Updated migration timestamp to $id.");
                }
            }
        }
    }

    /**
     * Run upgrade scripts.
     */
    public function runUpgrade(Connection $conn, BaseDriver $driver, array $scripts = null)
    {
        if (!$scripts) {
            $scripts = $this->getUpgradeScripts($conn, $driver);
            if (count($scripts) == 0) {
                $this->logger->info('No migration script found.');

                return;
            }
        }
        $this->logger->info('Found '.count($scripts).' migration scripts to run upgrade!');
        try {
            $this->logger->info('Begining transaction...');
            $conn->beginTransaction();
            foreach ($scripts as $script) {
                $migration = new $script($conn, $driver, $this->logger);
                $migration->upgrade();
                $this->updateLastMigrationId($conn, $driver, $script::getId());
            }
            $this->logger->info('Committing...');
            $conn->commit();
        } catch (Exception $e) {
            $this->logger->error(get_class($e).' was thrown: '.$e->getMessage());
            $this->logger->error('Rolling back ...');
            $conn->rollback();
            $this->logger->error('Recovered, escaping...');
            throw $e;
        }
    }

    public function runUpgradeAutomatically(Connection $conn, BaseDriver $driver, array $schemas, OptionResult $options = null)
    {
        $script = new AutomaticMigration($conn, $driver, $this->logger, $options);
        try {
            $this->logger->info('Begining transaction...');
            $conn->beginTransaction();

            // where to find the schema?
            $script->upgrade($schemas);

            $this->logger->info('Committing...');
            $conn->commit();
        } catch (Exception $e) {
            $this->logger->error('Exception was thrown: '.$e->getMessage());
            $this->logger->warn('Rolling back ...');
            $conn->rollback();
            $this->logger->warn('Recovered, escaping...');
            throw $e;
        }
    }
}
