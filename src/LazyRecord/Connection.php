<?php
namespace LazyRecord;
use SQLBuilder\Driver\MySQLDriver;
use SQLBuilder\Driver\PDODriverFactory;
use PDO;
use LazyRecord\DSN\DSNParser;
use LazyRecord\DSN\DSN;

class Connection extends PDO
{
    protected $config;

    private $dsn;

    static public function create(array $config)
    {
        $connection = new self($config['dsn'], $config['user'], $config['pass'], $config['connection_options']);
        $connection->config = $config;
        return $connection;
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    public function prepareAndExecute($sql, array $args = array())
    {
        $stm = $this->prepare($sql);
        $stm->execute($args); // $success 
        return $stm;
    }

    public function createQueryDriver()
    {
        return PDODriverFactory::create($this);
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getDSN()
    {
        if ($this->dsn) {
            return $this->dsn;
        }
        return $this->dsn = DSNParser::parse($this->config['dsn']);
    }
}



