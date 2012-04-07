<?php
namespace LazyRecord;

class MysqlTableParser
{
    public $driver;
    public $connection;

    public function __construct($driver,$connection)
    {
        $this->driver = $driver;
        $this->connection = $connection;
    }

    public function getTables()
    {
        $stm = $this->connection->query('show tables;');
        $rows = $stm->fetchAll();
        return array_map(function($row) { return $row[0]; },$rows);
    }

    public function getTableSchema($table)
    {
        $stm = $this->connection->query("show columns from $table;");
        $schema = new Schema\RuntimeSchema;
        $rows = $stm->fetchAll();
        foreach( $rows as $row ) {

        }
    }
}

class TableParser
{
    static function create($driver,$connection) 
    {
        $parser = new MysqlTableParser($driver,$connection);
        return $parser;
    }
}



