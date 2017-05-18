<?php

namespace Maghead\Migration;

use Maghead\Manager\MetadataManager;
use Maghead\Manager\DataSourceManager;
use Maghead\Runtime\Connection;
use GetOptionKit\OptionResult;
use CLIFramework\Logger;
use Magsql\Driver\BaseDriver;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * MigrationScript Runner.
 *
 * The instance should only be used for one connection to simplify the APIs.
 */
class MigrationRunner
{
    protected $scripts;

    protected $logger;

    protected $conn;

    protected $driver;

    protected $metadata;

    public function __construct(Connection $conn, BaseDriver $driver, Logger $logger, array $scripts)
    {
        $this->conn = $conn;
        $this->driver = $driver;
        $this->logger = $logger;
        $this->scripts = $scripts;

        $this->metadata = new MetadataManager($conn, $driver);
    }

    public function getLastMigrationTimestamp()
    {
        return $this->metadata['migration'] ?: 0;
    }

    public function resetMigrationTimestamp()
    {
        $this->metadata['migration'] = 0;
    }

    public function updateLastMigrationTimestamp($timestamp)
    {
        $lastId = $this->metadata['migration'];
        $this->metadata['migration'] = $timestamp;
    }

    /**
     * Each data source has it's own migration timestamp,
     * we use the data source ID to get the migration timestamp
     * and filter the migration script.
     *
     * @return file[] scripts
     */
    public function getUpgradeScripts()
    {
        $timestamp = $this->getLastMigrationTimestamp();

        $scripts = array_filter($this->scripts, function ($class) use ($timestamp) {
            $id = $class::getId();

            return $id > $timestamp;
        });
        usort($scripts, function ($a, $b) {
            return $a::getId() <=> $b::getId();
        });
        return $scripts;
    }

    public function getDowngradeScripts()
    {
        $timestamp = $this->getLastMigrationTimestamp();

        $scripts = array_filter($this->scripts, function ($class) use ($timestamp) {
            $id = $class::getId();

            return $id <= $timestamp;
        });
        usort($scripts, function ($a, $b) {
            return $b::getId() <=> $a::getId();
        });
        return $scripts;
    }


    /**
     * Run downgrade scripts.
     */
    public function runDowngrade($steps = 1)
    {
        $timestamp = $this->getLastMigrationTimestamp();
        $scripts = $this->getDowngradeScripts();

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
                $migration = new $script($this->conn, $this->driver, $this->logger);
                $migration->downgrade();
                if ($nextScript = end($scripts)) {
                    $id = $nextScript::getId();
                    $this->updateLastMigrationTimestamp($id);
                    $this->logger->info("Updated migration timestamp to $id.");
                }
            }
        }
    }

    /**
     * Run upgrade scripts.
     */
    public function runUpgrade($steps = 0)
    {
        $scripts = $this->getUpgradeScripts();
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
            $this->conn->beginTransaction();
            foreach ($scripts as $script) {
                $migration = new $script($this->conn, $this->driver, $this->logger);
                $migration->upgrade();
                $this->updateLastMigrationTimestamp($script::getId());
            }
            $this->logger->info('Committing...');
            $this->conn->commit();
        } catch (Exception $e) {
            $this->logger->error(get_class($e).' was thrown: '.$e->getMessage());
            $this->logger->error('Rolling back ...');
            $this->conn->rollback();
            $this->logger->error('Recovered, escaping...');
            throw $e;
        }
    }
}
