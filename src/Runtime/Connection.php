<?php

namespace Maghead\Runtime;

use Maghead\DSN\DSNParser;
use Maghead\DSN\DSN;
use Maghead\Runtime\Connector\PDOMySQLConnector;
use SQLBuilder\Driver\PDODriverFactory;
use SQLBuilder\Raw;

use PDO;

class Connection extends PDO
{
    /**
     * @var array
     */
    public $config;

    /**
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $pass;

    /**
     * @var Maghead\DSN\DSN
     */
    private $dsn;


    /**
     * @var SQLBuilder\Driver\BaseDriver
     */
    private $queryDriver;

    public function prepareAndExecute($sql, array $args = array())
    {
        $stm = $this->prepare($sql);
        $stm->execute($args); // $success
        return $stm;
    }

    public function getQueryDriver()
    {
        if ($this->queryDriver) {
            return $this->queryDriver;
        }
        return $this->queryDriver = PDODriverFactory::create($this);
    }

    public function __clone()
    {
        $this->dsn = clone $this->dsn;
    }

    public function raw($val)
    {
        return new Raw($val);
    }
}
