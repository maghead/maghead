<?php
namespace LazyRecord\TableParser;

abstract class BaseTablePaser
{
    public $driver;
    public $connection;

    public function __construct($driver,$connection)
    {
        $this->driver = $driver;
        $this->connection = $connection;
    }

    abstract function getTables();

}



