<?php

namespace Maghead\Sharding\Operations;

use Maghead\Sharding\ShardDispatcher;
use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\Shard;
use Maghead\Sharding\ShardCollection;
use Maghead\Manager\ConnectionManager;
use Maghead\Manager\DatabaseManager;
use Maghead\Manager\DataSourceManager;
use Maghead\Manager\ConfigManager;
use Maghead\Manager\MetadataManager;
use Maghead\Manager\TableManager;
use Maghead\Config;
use Maghead\Schema;
use Maghead\Schema\SchemaUtils;
use Maghead\TableBuilder\TableBuilder;

use Maghead\DSN\DSNParser;
use Maghead\DSN\DSN;
use Maghead\Utils;

use RuntimeException;

use CLIFramework\Logger;

class CloneShard
{
    protected $config;

    protected $connectionManager;

    protected $dataSourceManager;

    protected $logger;

    protected $dropFirst = false;

    public function __construct(Config $config, $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->connectionManager = new ConnectionManager($config->getInstances());
        $this->dataSourceManager = new DataSourceManager($config->getDataSources());
    }

    public function setDropFirst($enabled = true)
    {
        $this->dropFirst = $enabled;
    }

    public function clone($srcNodeId, $newNodeId, $dbName = null)
    {
        // create a new node with the new dbname
        if (!$dbName) {
            $dbName = $newNodeId;
        }

        $newNodeConfig = $this->dataSourceManager->getNodeConfig($srcNodeId);
        if (!$newNodeConfig) {
            throw new RuntimeException("Node config of '$srcNodeId' is undefined.");
        }

        // create a new DSN base on the source node config
        $dsn = DSNParser::parse($newNodeConfig['dsn']);
        $dsn->setAttribute('dbname', $dbName);
        $newNodeConfig['dsn'] = $dsn->__toString();

        // add the new node to the config
        $this->config->addDataSource($newNodeId, $newNodeConfig);

        // add the new node to the connection manager
        $this->dataSourceManager->addNode($newNodeId, $newNodeConfig);

        $srcNode = $this->dataSourceManager->getNodeConfig($srcNodeId);

        $args = [Utils::findBin('mysqldbcopy')];
        $args[] = '--source';
        $args[] = $this->buildParam($srcNode);

        $args[] = '--destination';
        $args[] = $this->buildParam($newNodeConfig);

        if ($this->dropFirst) {
            $args[] = '--drop-first';
        }

        $args[] = $this->getDB($srcNode) . ":" . $this->getDB($newNodeConfig);
        $command = implode(' ', $args);

        $this->logger->debug("Performing: $command");

        // $lastLine = exec($command, $output, $retval);
        $lastLine = system($command, $retval);
        if (($retval != 0) ) {
            throw new RuntimeException("mysqldbcopy failed");
        }
    }

    protected function getDB(array $nodeConfig)
    {
        if (isset($nodeConfig['database'])) {
            return $nodeConfig['database'];
        }
        $dsn = DSNParser::parse($nodeConfig['dsn']);
        return $dsn->getDatabaseName();
    }


    protected function buildParam(array $nodeConfig)
    {
        $param = $nodeConfig['user'];

        $dsn = DSNParser::parse($nodeConfig['dsn']);
        if ($host = $dsn->getHost()) {
            $nodeConfig['host'] = $host;
        }
        if ($port = $dsn->getPort()) {
            $nodeConfig['port'] = $port;
        }

        if ($socket = $dsn->getUnixSocket()) {
            $nodeConfig['unix_socket'] = $socket;
        }


        if (isset($nodeConfig['password'])) {
            $param .= ":" . $nodeConfig['portword'];
        }
        if (isset($nodeConfig['host'])) {
            $param .= "@" . $nodeConfig['host'];
        }

        if (isset($nodeConfig['port'])) {
            $param .= ":" . $nodeConfig['port'];
        }
        if (isset($nodeConfig['unix_socket'])) {
            $param .= ":" . $nodeConfig['unix_socket'];
        }
        return $param;
    }
}
