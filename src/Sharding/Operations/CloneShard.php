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

    public function __construct(Config $config, Logger $logger)
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

    public function clone($instanceId, $newNodeId, $srcNodeId)
    {
        // setup the database name.
        $dbName = $newNodeId;

        // Create the node config from the instance node config.
        $newNodeConfig = $this->connectionManager->getNodeConfig($instanceId);
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

        // TODO: support --rpl and --rpl-user
        // To copy one or more databases from a master to a slave, you can use the following command to copy the databases. Use the master as the source and the slave as the destination:
        //
        //     shell> mysqldbcopy --source=root@localhost:3310 \
        //        --destination=root@localhost:3311 test123 \
        //        --rpl=master \
        //        --rpl-user=rpl
        //
        // To copy a database from one slave to another attached to the same master, you can use the following command using the slave with the database to be copied as the source and the slave where the database needs to copied to as the destination:
        //
        // shell> mysqldbcopy --source=root@localhost:3311 \
        //    --destination=root@localhost:3312 test123 --rpl=slave \
        //    --rpl-user=rpl
        //
        // TODO: support --multiprocess option
        //
        // @see https://dev.mysql.com/doc/mysql-utilities/1.5/en/mysqldbcopy.html

        $args = [Utils::findBin('mysqldbcopy')];
        $args[] = '--source';
        $args[] = $this->buildSourceParam($srcNode);

        $args[] = '--destination';
        $args[] = $this->buildDestParam($newNodeConfig);

        if ($this->dropFirst) {
            $args[] = '--drop-first';
        }

        $args[] = $this->getDB($srcNode) . ":" . $this->getDB($newNodeConfig);
        $command = implode(' ', $args);

        $this->logger->debug("Performing: $command");

        var_dump($command);

        // $lastLine = exec($command, $output, $retval);
        $lastLine = system($command, $retval);
        if (($retval != 0)) {
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
        // var_dump($nodeConfig);
        $param = $nodeConfig['user'];
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

    protected function buildDestParam(array $nodeConfig)
    {
        // rebuild the node config if we have the dsn
        if (isset($nodeConfig['dsn'])) {
            $dsn = DSN::createForWrite($nodeConfig);
            if ($host = $dsn->getHost()) {
                $nodeConfig['host'] = $host;
            }
            if ($port = $dsn->getPort()) {
                $nodeConfig['port'] = $port;
            }
            if ($socket = $dsn->getUnixSocket()) {
                $nodeConfig['unix_socket'] = $socket;
            }
        }
        return $this->buildParam($nodeConfig);
    }

    protected function buildSourceParam(array $nodeConfig)
    {

        // rebuild the node config if we have the dsn
        if (isset($nodeConfig['dsn'])) {
            $dsn = DSN::createForRead($nodeConfig);
            if ($host = $dsn->getHost()) {
                $nodeConfig['host'] = $host;
            }
            if ($port = $dsn->getPort()) {
                $nodeConfig['port'] = $port;
            }
            if ($socket = $dsn->getUnixSocket()) {
                $nodeConfig['unix_socket'] = $socket;
            }
        }
        var_dump( $nodeConfig );
        return $this->buildParam($nodeConfig);
    }
}
