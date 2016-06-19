<?php

namespace LazyRecord\Migration;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use LazyRecord\Metadata;
use LazyRecord\ConnectionManager;
use LazyRecord\ServiceContainer;
use GetOptionKit\OptionResult;
use Exception;
use RuntimeException;
use CLIFramework\Logger;

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

    public function addDataSource($dsId)
    {
        $this->dataSourceIds[] = $dsId;
    }

    public function load($directory)
    {
        if (!file_exists($directory)) {
            return array();
        }
        $loaded = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory), RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $path) {
            if ($path->isFile() && $path->getExtension() === 'php') {
                $code = file_get_contents($path);
                if (preg_match('#Migration#', $code)) {
                    $this->logger->debug("Loading migration script: $path");
                    require_once $path;
                    $loaded[] = $path;
                }
            }
        }

        return $loaded;
    }

    public function getLastMigrationId($dsId)
    {
        $meta = Metadata::createWithDataSource($dsId);

        return $meta['migration'] ?: 0;
    }

    public function resetMigrationId($dsId)
    {
        $metadata = Metadata::createWithDataSource($dsId);
        $metadata['migration'] = 0;
    }

    public function updateLastMigrationId($dsId, $id)
    {
        $metadata = Metadata::createWithDataSource($dsId);
        $lastId = $metadata['migration'];
        $metadata['migration'] = $id;
        $this->logger->info("Updating migration version to $id.");
    }

    public function loadMigrationScripts()
    {
        $classes = get_declared_classes();
        $classes = array_filter($classes, function ($class) {
            return is_a($class, 'LazyRecord\\Migration\\Migration', true)
                && $class != 'LazyRecord\\Migration\\Migration';
        });

        // sort class with timestamp suffix
        usort($classes, function ($a, $b) {
            if (preg_match('#_(\d+)$#', $a, $regsA) && preg_match('#_(\d+)$#', $b, $regsB)) {
                list($aId, $bId) = array($regsA[1], $regsB[1]);
                if ($aId == $bId) {
                    return 0;
                }

                return $aId < $bId ? -1 : 1;
            }

            return 0;
        });

        return $classes;
    }

    /**
     * Each data source has it's own migration timestamp,
     * we use the data source ID to get the migration timestamp 
     * and filter the migration script
     *
     * @param string $dsId
     */
    public function getUpgradeScripts($dsId)
    {
        $lastMigrationId = $this->getLastMigrationId($dsId);
        $this->logger->debug("Found last migration id: $lastMigrationId");
        $scripts = $this->loadMigrationScripts();
        return array_filter($scripts, function ($class) use ($lastMigrationId) {
            $id = $class::getId();
            return $id > $lastMigrationId;
        });
    }

    public function getDowngradeScripts($dsId)
    {
        $scripts = $this->loadMigrationScripts();
        $lastMigrationId = $this->getLastMigrationId($dsId);

        return array_filter($scripts, function ($class) use ($lastMigrationId) {
            $id = $class::getId();

            return $id <= $lastMigrationId;
        });
    }

    /**
     * Run downgrade scripts.
     */
    public function runDowngrade(array $scripts = null, $steps = 1)
    {
        $this->logger->info('Performing downgrade...');

        foreach ($this->dataSourceIds as $dsId) {
            $driver = $this->connectionManager->getQueryDriver($dsId);
            $connection = $this->connectionManager->getConnection($dsId);

            $this->logger->info("Running downgrade over data source: $dsId");

            if (!$scripts) {
                $scripts = $this->getDowngradeScripts($dsId);
            }
            $this->logger->info('Found '.count($scripts).' migration scripts to run downgrade!');
            while ($steps--) {
                // downgrade a migration one at one time.
                if ($script = array_pop($scripts)) {
                    $this->logger->info("Running downgrade migration script $script on data source $dsId");
                    $migration = new $script($driver, $connection);
                    $migration->downgrade();

                    if ($nextScript = end($scripts)) {
                        $this->updateLastMigrationId($dsId, $nextScript::getId());
                    }
                }
            }
        }
    }

    /**
     * Run upgrade scripts.
     */
    public function runUpgrade(array $scripts = null)
    {
        $this->logger->info('Performing upgrade...');

        foreach ($this->dataSourceIds as $dsId) {
            $this->logger->info("Running upgrade over data source: $dsId");

            $driver = $this->connectionManager->getQueryDriver($dsId);
            $connection = $this->connectionManager->getConnection($dsId);

            if (!$scripts) {
                $scripts = $this->getUpgradeScripts($dsId);
                if (count($scripts) == 0) {
                    $this->logger->info('No migration script found.');

                    return;
                }
            }
            $this->logger->info('Found '.count($scripts).' migration scripts to run upgrade!');

            try {
                $this->logger->info("Begining transaction...");
                $connection->beginTransaction();
                foreach ($scripts as $script) {
                    $this->logger->info("$script: Performing upgrade on data source $dsId");
                    $migration = new $script($driver, $connection);
                    $migration->upgrade();
                    $this->updateLastMigrationId($dsId, $script::getId());
                }
                $this->logger->info("Committing...");
                $connection->commit();
            } catch (Exception $e) {
                $this->logger->error(get_class($e) . ' was thrown: '.$e->getMessage());
                $this->logger->error("Rolling back ...");
                $connection->rollback();
                $this->logger->error("Recovered, escaping...");
                break;
            }
        }
    }

    public function runUpgradeAutomatically(OptionResult $options = null)
    {
        foreach ($this->dataSourceIds as $dsId) {
            $driver = $this->connectionManager->getQueryDriver($dsId);
            $connection = $this->connectionManager->getConnection($dsId);

            $this->logger->info("Performing automatic upgrade over data source: $dsId");

            $script = new AutomaticMigration($driver, $connection, $options);
            try {
                $this->logger->info('Begining transaction...');
                $connection->beginTransaction();

                $script->upgrade();

                $this->logger->info('Committing...');
                $connection->commit();
            } catch (Exception $e) {
                $this->logger->error('Exception was thrown: '.$e->getMessage());
                $this->logger->warn('Rolling back ...');
                $connection->rollback();
                $this->logger->warn('Recovered, escaping...');
                break;
            }
        }
    }
}
