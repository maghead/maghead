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

    }

    public function getTableSpec()
    {

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



