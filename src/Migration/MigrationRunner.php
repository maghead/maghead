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

/**
 * MigrationScript Runner
 *
 * The instance should only be for one connection to simplify the APIs.
 */
class MigrationRunner
{
    protected $scripts;

    protected $logger;

    public function __construct(array $scripts, Logger $logger)
    {
        $this->scripts = $scripts;
        $this->logger = $logger;
    }

    public function getLastMigrationTimestamp(Connection $conn, BaseDriver $driver)
    {
        $meta = new MetadataManager($conn, $driver);

        return $meta['migration'] ?: 0;
    }

    public function resetMigrationTimestamp(Connection $conn, BaseDriver $driver)
    {
        $metadata = new MetadataManager($conn, $driver);
        $metadata['migration'] = 0;
    }

    public function updateLastMigrationTimestamp(Connection $conn, BaseDriver $driver, $id)
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
     * @return file[] scripts
     */
    public function getUpgradeScripts(Connection $conn, BaseDriver $driver)
    {
        $meta = new MetadataManager($conn, $driver);
        $timestamp = $meta['migration'] ?: 0;

        $scripts = array_filter($this->scripts, function ($class) use ($timestamp) {
            $id = $class::getId();

            return $id > $timestamp;
        });
        usort($scripts, function($a, $b) {
            return $a::getId() <=> $b::getId();
        });
        return $scripts;
    }

    public function getDowngradeScripts(Connection $conn, BaseDriver $driver)
    {
        $meta = new MetadataManager($conn, $driver);
        $timestamp = $meta['migration'] ?: 0;

        $scripts = array_filter($this->scripts, function ($class) use ($timestamp) {
            $id = $class::getId();

            return $id <= $timestamp;
        });
        usort($scripts, function($a, $b) {
            return $b::getId() <=> $a::getId();
        });
        return $scripts;
    }


    /**
     * Run downgrade scripts.
     */
    public function runDowngrade(Connection $conn, BaseDriver $driver, $steps = 1)
    {
        $meta = new MetadataManager($conn, $driver);
        $timestamp = $meta['migration'] ?: 0;
        $scripts = $this->getDowngradeScripts($conn, $driver);

        if ($steps) {
            $scripts = array_slice($scripts, 0, $steps);
        }

        if (count($scripts) == 0) {
            $this->logger->info('No migration script found.');
            return;
        }

        $this->logger->info('Found '.count($scripts).' migration scripts to run downgrade!');

        foreach ($scripts as $idx => $cls) {
            $this->logger->info("{$idx}. $cls::upgrade");
        }

        while ($steps--) {
            // downgrade a migration one at one time.
            if ($script = array_pop($scripts)) {
                $this->logger->info("Running {$script}::downgrade");
                $migration = new $script($conn, $driver, $this->logger);
                $migration->downgrade();
                if ($nextScript = end($scripts)) {
                    $id = $nextScript::getId();
                    $this->updateLastMigrationTimestamp($conn, $driver, $id);
                    $this->logger->info("Updated migration timestamp to $id.");
                }
            }
        }
    }

    /**
     * Run upgrade scripts.
     */
    public function runUpgrade(Connection $conn, BaseDriver $driver, $steps = 0)
    {
        $scripts = $this->getUpgradeScripts($conn, $driver);

        if ($steps) {
            $scripts = array_slice($scripts, 0, $steps);
        }

        if (count($scripts) == 0) {
            $this->logger->info('No migration script found.');
            return;
        }

        $this->logger->info('Found '.count($scripts).' migration scripts to run upgrade!');

        foreach ($scripts as $idx => $cls) {
            $this->logger->info("{$idx}. $cls::downgrade");
        }

        try {
            $this->logger->info('Begining transaction...');
            $conn->beginTransaction();
            foreach ($scripts as $script) {
                $migration = new $script($conn, $driver, $this->logger);
                $migration->upgrade();
                $this->updateLastMigrationTimestamp($conn, $driver, $script::getId());
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
}
