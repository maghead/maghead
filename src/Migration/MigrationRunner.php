<?php

namespace Maghead\Migration;

use Maghead\Metadata;
use Maghead\ConnectionManager;
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

    /**
     * Load migration script from specific directory.
     */
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

    public function getLastMigrationId(Connection $conn, BaseDriver $driver)
    {
        $meta = new Metadata($conn, $driver);

        return $meta['migration'] ?: 0;
    }

    public function resetMigrationId(Connection $conn, BaseDriver $driver)
    {
        $metadata = new Metadata($conn, $driver);
        $metadata['migration'] = 0;
    }

    public function updateLastMigrationId(Connection $conn, BaseDriver $driver, $id)
    {
        $metadata = new Metadata($conn, $driver);
        $lastId = $metadata['migration'];
        $metadata['migration'] = $id;
    }

    public function loadMigrationScripts()
    {
        $classes = get_declared_classes();
        $classes = array_filter($classes, function ($class) {
            return is_a($class, 'Maghead\\Migration\\Migration', true)
                && $class != 'Maghead\\Migration\\Migration';
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
     * and filter the migration script.
     *
     * @param string $dsId
     */
    public function getUpgradeScripts(Connection $conn, BaseDriver $driver)
    {
        $lastMigrationId = $this->getLastMigrationId($conn, $driver);
        $this->logger->debug("Found last migration id: $lastMigrationId");
        $scripts = $this->loadMigrationScripts();

        return array_filter($scripts, function ($class) use ($lastMigrationId) {
            $id = $class::getId();

            return $id > $lastMigrationId;
        });
    }

    public function getDowngradeScripts(Connection $conn, BaseDriver $driver)
    {
        $scripts = $this->loadMigrationScripts();
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
