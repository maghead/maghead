<?php

namespace LazyRecord;

use SQLBuilder\Driver\PDODriverFactory;
use PDO;
use LazyRecord\DSN\DSNParser;
use LazyRecord\DSN\DSN;

class Connection extends PDO
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $pass;

    /**
     * @var LazyRecord\DSN\DSN
     */
    private $dsn;

    public static function create(array $config)
    {
        $connection = new self($config['dsn'], $config['user'], $config['pass'], $config['connection_options']);
        $connection->config = $config;
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // TODO: can we make this optional ?
        return $connection;
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

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    public function getDSN()
    {
        if ($this->dsn) {
            return $this->dsn;
        }
        $parser = new DSNParser();

        return $this->dsn = $parser->parse($this->config['dsn']);
    }

    public function __clone()
    {
        $this->dsn = clone $this->dsn;
    }
}
