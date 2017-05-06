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
use Maghead\Runtime\Config\Config;
use Maghead\Runtime\Config\FileConfigLoader;
use Maghead\Schema;
use Maghead\Schema\SchemaUtils;
use Maghead\TableBuilder\TableBuilder;

use Maghead\DSN\DSNParser;
use Maghead\DSN\DSN;
use Maghead\Utils;

use RuntimeException;
use InvalidArgumentException;

use CLIFramework\Logger;

class CloneShard extends BaseShardOperation
{
    protected $dropFirst = false;

    protected $verbose = false;

    protected $storageEngine = 'InnoDB';

    protected $multiprocess;

    /**
     * @var string 'master' or 'salve'
     */
    protected $replicateAs;

    /**
     * @var string user ID used for replicate
     */
    protected $replicateUser;


    /**
     * @var locking
     */
    protected $locking;


    public function setDropFirst($enabled = true)
    {
        $this->dropFirst = $enabled;
    }


    public function setVerbose($verbose = true)
    {
        $this->verbose = $verbose;
    }

    public function setReplicateAs($replicateAs)
    {
        $this->replicateAs = $replicateAs;
    }

    public function setMultiprocess($numberOfProcesses)
    {
        $this->multiprocess = $numberOfProcesses;
    }


    /**
     *   --locking=LOCKING  choose the lock type for the operation: no-locks = do
     *                      not use any table locks, lock-all = use table locks
     *                      but no transaction and no consistent read, snaphot
     *                      (default): consistent read using a single transaction.
     */
    public function setLocking($locking)
    {
        if (!in_array($locking, ['no-locks', 'lock-all'])) {
            throw new InvalidArgumentException("Invalid locking value: '$locking'");
        }
        $this->locking = $locking;
    }

    public function clone($mappingId, $instanceId, $newNodeId, $srcNodeId)
    {
        // setup the database name.
        $dbName = $newNodeId;

        // Create the node config from the instance node config.
        $newNodeConfig = $this->dataSourceManager->getNodeConfig($srcNodeId);
        if (!$newNodeConfig) {
            throw new RuntimeException("Node config of '$srcNodeId' is undefined.");
        }

        // Create a new DSN base on the source node config
        $newNodeConfig['database'] = $dbName;
        $newNodeConfig = DSN::update($newNodeConfig);

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

        if ($this->verbose) {
            $args[] = '--verbose';
        }

        $args[] = "--new-storage-engine={$this->storageEngine}";
        $args[] = "--default-storage-engine={$this->storageEngine}";

        if ($this->replicateAs) {
            $args[] = "--replication={$this->replicateAs}";
        }
        if ($this->replicateUser) {
            $args[] = "--rpl-user={$this->replicateUser}";
        }
        if ($this->multiprocess) {
            $args[] = "--multiprocess={$this->multiprocess}";
        }

        if ($this->locking) {
            $args[] = "--locking={$this->locking}";
        }

        $args[] = "--source";
        $args[] = $this->buildSourceParam($srcNode);

        $args[] = "--destination";
        $args[] = $this->buildDestParam($newNodeConfig);

        if ($this->dropFirst) {
            $args[] = "--drop-first";
        }

        $args[] = $this->getDB($srcNode) . ":" . $this->getDB($newNodeConfig);
        $command = implode(' ', $args);

        // $lastLine = exec($command, $output, $retval);
        $lastLine = system($command, $retval);
        if (($retval != 0)) {
            throw new RuntimeException("mysqldbcopy failed: $command");
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
        if (isset($nodeConfig['write'])) {
            $idx = array_rand($nodeConfig['write']);
            return $this->buildParam($nodeConfig['write'][$idx]);
        }
        return $this->buildParam($nodeConfig);
    }

    protected function buildSourceParam(array $nodeConfig)
    {
        // rebuild the node config if we have the dsn
        if (isset($nodeConfig['read'])) {
            $idx = array_rand($nodeConfig['read']);
            return $this->buildParam($nodeConfig['read'][$idx]);
        }
        return $this->buildParam($nodeConfig);
    }
}
