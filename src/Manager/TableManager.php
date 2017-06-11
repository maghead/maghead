<?php

namespace Maghead\Manager;

use Maghead\TableBuilder\TableBuilder;
use Maghead\Schema\SchemaCollection;
use Maghead\Runtime\Connection;
use Maghead\Runtime\PDOExceptionPrinter;
use CLIFramework\Logger;
use PDOException;

class TableManager
{
    protected $conn;

    protected $driver;

    /**
     * @var Maghead\TableBuilder\BaseBuilder
     */
    protected $builder;

    /**
     * @var CLIFramework\Logger
     */
    protected $logger;

    public function __construct(Connection $conn, array $options = [], Logger $logger = null)
    {
        $this->conn = $conn;
        $this->driver = $conn->getQueryDriver();
        $this->builder = TableBuilder::create($this->driver, $options);
        $this->logger = $logger;
    }


    /**
     * Remove schemas from database.
     *
     * @param Maghead\Schema\Schema
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

    /**
     * Build tables from schema objects.
     *
     * @param DeclareSchema[] $schemas
     */
    public function build($schemas)
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

    protected function executeStatements(array $sqls)
    {
        foreach ($sqls as $sql) {
            $this->executeStatement($sql);
        }
    }

    protected function executeStatement($sql)
    {
        try {
            if ($this->logger) {
                $this->logger->debug($sql);
            }
            $this->conn->query($sql);
        } catch (PDOException $e) {
            if ($this->logger) {
                PDOExceptionPrinter::show($this->logger, $e, $sql, []);
            }
            throw $e;
        }
    }
}
