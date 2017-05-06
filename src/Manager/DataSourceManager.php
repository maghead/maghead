<?php

namespace Maghead\Manager;

use Exception;
use PDOException;
use InvalidArgumentException;
use PDO;
use ArrayAccess;
use Maghead\DSN\DSN;
use Maghead\Runtime\Connection;
use Maghead\Runtime\Connector\PDOMySQLConnector;

class DataSourceManager extends ConnectionManager
{
    const DEFAULT_MASTER_NODE_ID = "master";

    public function getMasterNodeConfig()
    {
        return $this->getNodeConfig(self::DEFAULT_MASTER_NODE_ID);
    }

    /**
     * Get master data source id.
     *
     * @return string 'master'
     */
    public function getMasterConnection()
    {
        return $this->getConnection(self::DEFAULT_MASTER_NODE_ID);
    }

    /**
     * Get singleton instance.
     */
    public static function getInstance()
    {
        static $instance;
        if ($instance) {
            return $instance;
        }

        return $instance = new static();
    }
}
