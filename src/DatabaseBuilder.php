<?php
namespace LazyRecord;
use CLIFramework\Logger;
use LazyRecord\ConfigLoader;
use LazyRecord\SqlBuilder\BaseBuilder;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\ServiceContainer;
use PDO;
use PDOException;
use LazyRecord\PDOExceptionPrinter;

class DatabaseBuilder
{

    public $conn;

    public $builder;

    public $logger;

    public function __construct(PDO $conn, BaseBuilder $builder, Logger $logger = NULL)
    {
        $this->conn    = $conn;
        $this->builder = $builder;

        $c = ServiceContainer::getInstance();
        $this->logger  = $logger ?: $c['logger'];
    }

    public function build(array $schemas)
    {
        if ($sqls = $this->builder->prepare()) {
            $this->executeStatements($sqls);
        }
        foreach ($schemas as $schema) {
            $class = get_class($schema);
            $this->logger->info("Building table for $class");
            $sqls = $this->builder->buildTable($schema);
            $this->executeStatements($sqls);
        }
        foreach ($schemas as $schema) {
            $class = get_class($schema);
            $this->logger->info("Building index for $class");

            $sqls = $this->builder->buildIndex($schema);
            $this->executeStatements($sqls);
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
            $this->conn->query( $sql );
        } catch (PDOException $e) {
            PDOExceptionPrinter::show($e, $sql, [], $this->logger);
        }
    }
}
