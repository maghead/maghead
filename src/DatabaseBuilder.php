<?php

namespace LazyRecord;

use CLIFramework\Logger; use LazyRecord\SqlBuilder\BaseBuilder;
use PDO;
use PDOException;

class DatabaseBuilder
{
    public $conn;

    public $builder;

    public $logger;

    public function __construct(PDO $conn, BaseBuilder $builder, Logger $logger = null)
    {
        $this->conn = $conn;
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
