<?php

namespace Maghead\Manager;

use Exception;
use PDOException;
use InvalidArgumentException;
use PDO;
use ArrayAccess;
use Maghead\DSN\DSN;
use Maghead\Connection;
use Maghead\Connector\PDOMySQLConnector;

class DataSourceManager extends ConnectionManager
{
    const DEFAULT_MASTER_NODE_ID = 'master';

    protected $masterNodeId;

    public function getMasterNodeConfig()
    {
        return $this->getNodeConfig($this->masterNodeId ?: self::DEFAULT_MASTER_NODE_ID);
    }

    public function setMasterNodeId($nodeId)
    {
        $this->masterNodeId = $nodeId;
    }

    /**
     * Get master data source id.
     *
     * @return string 'master'
     */
    public function getMasterConnection()
    {
        return $this->getConnection($this->masterNodeId ?: self::DEFAULT_MASTER_NODE_ID);
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
