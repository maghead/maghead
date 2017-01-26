<?php

namespace Maghead\Manager;

use Maghead\SqlBuilder\BaseBuilder;
use Maghead\Schema\SchemaCollection;
use Maghead\Connection;
use CLIFramework\Logger;
use PDOException;

class TableManager 
{
    protected $conn;

    protected $builder;

    protected $logger;

    public function __construct(Connection $conn, BaseBuilder $builder, Logger $logger = null)
    {
        $this->conn = $conn;
        $this->builder = $builder;
        $this->logger = $logger;
    }

    /**
     * Remove schemas from database.
     */
    public function remove(array $schemas)
    {
        if ($sqls = $this->builder->prepare()) {
            $this->executeStatements($sqls);
        }

        foreach ($schemas as $schema) {
            $sqls = (array) $this->builder->dropTable($schema);
            if (!empty($sqls)) {
                $this->executeStatements($sqls);
            }
        }

        if ($sqls = $this->builder->finalize()) {
            $this->executeStatements($sqls);
        }
    }

    public function build(array $schemas)
    {
        if ($sqls = $this->builder->prepare()) {
            $this->executeStatements($sqls);
        }
        foreach ($schemas as $schema) {
            $sqls = $this->builder->buildTable($schema);
            if (!empty($sqls)) {
                $this->executeStatements($sqls);
            }
        }
        foreach ($schemas as $schema) {
            $sqls = $this->builder->buildIndex($schema);
            if (!empty($sqls)) {
                $this->executeStatements($sqls);
            }
        }
        if ($sqls = $this->builder->finalize()) {
            $this->executeStatements($sqls);
        }
    }

    public function seed(array $schemas, ConfigLoader $config = null)
    {
        $seedBuilder = new SeedBuilder($this->logger);
        $seedBuilder->build(new SchemaCollection($schemas));
        if ($config) {
            $seedBuilder->buildConfigSeeds($config);
        }
    }

    protected function executeStatements(array $sqls)
    {
        foreach ($sqls as $sql) {
            $this->executeStatement($sql);
        }
    }

    protected function executeStatement($sql)
    {
        try {
            $this->logger->debug($sql);
            $this->conn->query($sql);
        } catch (PDOException $e) {
            PDOExceptionPrinter::show($e, $sql, [], $this->logger);
        }
    }
}
