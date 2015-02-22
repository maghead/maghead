<?php
namespace LazyRecord;
use CLIFramework\Logger;
use LazyRecord\ConfigLoader;
use LazyRecord\SqlBuilder\BaseBuilder;
use LazyRecord\Schema\SchemaInterface;
use PDO;

class DatabaseBuilder
{
    public function __construct(PDO $conn, BaseBuilder $builder, Logger $logger = NULL)
    {
        $this->conn    = $conn;
        $this->builder = $builder;
        $this->logger  = $logger;
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
        $this->logger->info('Building Table SQL for ' . $schema);

        $sqls = $this->builder->buildTable($schema);
        foreach( $sqls as $sql ) {
            $this->conn->query( $sql );
            $error = $this->conn->errorInfo();
            if ($error[1]) {
                $msg =  $class . ': ' . var_export( $error , true );
                // static::$logger->error($msg);
            }
        }
        return "--- Schema $class \n" . join("\n",$sqls);
    }


    public function buildIndexSql(SchemaInterface $schema)
    {
        $class = get_class($schema);
        $this->logger->info('Building Index SQL for ' . $schema);

        $sqls = $this->builder->buildIndex($schema);
        foreach ($sqls as $sql) {
            $this->logger->debug($sql);
            $this->conn->query( $sql );
            $error = $this->conn->errorInfo();

            if ($error[1]) {
                $msg =  $class . ': ' . var_export( $error , true );
                // static::$logger->error($msg);
            }
        }
        return "--- Index For $class \n" . join("\n",$sqls);
    }


    public function buildForeignKeysSql(SchemaInterface $schema)
    {
        $class = get_class($schema);

        $sqls = $this->builder->buildForeignKeys($schema);
        foreach ($sqls as $sql) {
            $this->logger->debug($sql);
            $this->conn->query( $sql );
            $error = $this->conn->errorInfo();
            if ($error[1]) {
                $msg =  $class . ': ' . var_export( $error , true );
                // static::$logger->error($msg);
            }
        }
        return "--- Index For $class \n" . join("\n",$sqls);

    }
}
