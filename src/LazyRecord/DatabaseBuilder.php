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
        $sqls = array();
        foreach ($schemas as $schema) {
            $sqls[] = $this->buildTableSql($schema);
            $sqls[] = $this->buildIndexSql($schema);
            $sqls[] = $this->buildForeignKeysSql($schema);
        }
        return $sqls;
    }


    public function buildTableSql(SchemaInterface $schema)
    {
        $class = get_class($schema);
        $sqls = $this->builder->buildTable($schema);
        if (!empty($sqls)) {
            $this->logger->info('Building table definition for ' . $schema);
            foreach ($sqls as $sql ) {
                $this->query($sql);
            }
        }
        return "--- Schema $class \n" . join("\n",$sqls);
    }

    public function query($sql) {
        try {
            $this->logger->debug($sql);
            $this->conn->query( $sql );
        } catch (PDOException $e) {
            PDOExceptionPrinter::show($e, $sql, [], $this->logger);
        }
    }

    public function buildIndexSql(SchemaInterface $schema)
    {
        $class = get_class($schema);
        $sqls = $this->builder->buildIndex($schema);
        if (!empty($sqls)) {
            $this->logger->info('Building index for ' . $schema);
            foreach ($sqls as $sql) {
                $this->query($sql);
            }
            return "--- Index For $class \n" . join("\n",$sqls);
        }
        return "";
    }


    public function buildForeignKeysSql(SchemaInterface $schema)
    {
        $class = get_class($schema);
        $sqls = $this->builder->buildForeignKeys($schema);
        if (!empty($sqls)) {
            $this->logger->info('Building foreign key index: ' . $schema);
            foreach ($sqls as $sql) {
                $this->query($sql);
            }
            return "--- Index For $class \n" . join("\n",$sqls);
        }
        return "";
    }
}
