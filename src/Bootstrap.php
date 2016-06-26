<?php

namespace LazyRecord;

use LazyRecord\SqlBuilder\BaseBuilder;
use LazyRecord\SqlBuilder;
use LazyRecord\SeedBuilder;
use LazyRecord\Connection;
use LazyRecord\Schema\SchemaCollection;
use CLIFramework\Logger;
use PDO;
use PDOException;

class Bootstrap
{
    protected $conn;

    protected $queryDriver;

    protected $builder;

    protected $logger;

    public function __construct(Connection $conn, BaseBuilder $builder = null, Logger $logger = null)
    {
        $this->conn = $conn;
        $this->queryDriver = $conn->createQueryDriver();
        if (!$builder) {
            $builder = SqlBuilder::create($this->queryDriver);
        }
        $this->builder = $builder;

        if (!$logger) {
            $c = ServiceContainer::getInstance();
            $logger ?: $c['logger'];
        }
        $this->logger = $logger;
    }

    public function build(array $schemas)
    {
        if ($sqls = $this->builder->prepare()) {
            $this->executeStatements($sqls);
        }
        foreach ($schemas as $schema) {
            $class = get_class($schema);
            $sqls = $this->builder->buildTable($schema);
            if (!empty($sqls)) {
                $this->executeStatements($sqls);
            }
        }
        foreach ($schemas as $schema) {
            $class = get_class($schema);
            $sqls = $this->builder->buildIndex($schema);
            if (!empty($sqls)) {
                $this->executeStatements($sqls);
            }
        }
        if ($sqls = $this->builder->finalize()) {
            $this->executeStatements($sqls);
        }
    }

    public function seed(array $schemas)
    {
        $collection = new SchemaCollection($schemas);
        $seedBuilder = new SeedBuilder($this->logger);
        $seedBuilder->build($collection);
        // $seedBuilder->buildConfigSeeds($this->getConfigLoader());
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
