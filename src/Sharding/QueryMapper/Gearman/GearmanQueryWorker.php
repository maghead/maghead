<?php

namespace Maghead\Sharding\QueryMapper\Gearman;

use GearmanWorker;
use GearmanJob;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;

use SQLBuilder\Universal\Query\SelectQuery;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Driver\BaseDriver;

use Maghead\Config;
use Maghead\ConfigLoader;
use Maghead\Manager\DataSourceManager;
use Maghead\Sharding\Manager\ShardManager;

use PDO;

class GearmanQueryWorker
{
    const PROVIDE_FUNCTION = "query";

    protected $config;

    protected $dataSourceManager;

    protected $worker;

    protected $logger;

    private $shardManager;

    public function __construct(Config $config, DataSourceManager $dataSourceManager, GearmanWorker $worker = null, Logger $logger = null)
    {
        $this->config = $config;
        $this->dataSourceManager = $dataSourceManager;

        $this->logger = $logger ?: self::createDefaultLogger();
        $this->worker = $worker ?: self::createDefaultGearmanWorker();
        $this->worker->addFunction(self::PROVIDE_FUNCTION, [$this, 'work']);

        $this->shardManager = new ShardManager($this->config, $this->dataSourceManager);
    }

    static protected function createDefaultLogger()
    {
        $logger = new Logger('query_worker');
        $logger->pushHandler(new StreamHandler('query_worker.log', Logger::INFO));
        $logger->pushHandler(new ErrorLogHandler(), Logger::DEBUG);
        return $logger;
    }

    static protected function createDefaultGearmanWorker()
    {
        $worker = new GearmanWorker();
        $worker->addServer();
        return $worker;
    }

    public function work(GearmanJob $job)
    {
        $this->logger->info("Received job: " . $job->handle());

        $workload = $job->workload();
        $workloadSize = $job->workloadSize();
        $this->logger->info("Workload: $workload ($workloadSize)");

        $queryJob = unserialize($workload);

        $this->logger->debug("Loading shard {$queryJob->shardId}");
        $shard = $this->shardManager->getShard($queryJob->shardId);

        $nodeId = $shard->selectReadNode();
        $this->logger->debug("Selected read node {$nodeId} from shard {$queryJob->shardId}");

        $this->logger->debug("Getting the connection of {$nodeId}");
        $conn = $this->dataSourceManager->getConnection($nodeId);

        $driver = $conn->getQueryDriver();

        $query = $queryJob->query;
        $args = new ArgumentArray;
        $sql = $query->toSql($driver, $args);

        $this->logger->debug("SQL: {$sql}");

        $stm = $conn->prepare($sql);
        $stm->execute( $args->toArray() );
        $rows = $stm->fetchAll(PDO::FETCH_ASSOC);

        /*
        $job->sendStatus($x, $workloadSize);
        */
        return serialize([ $nodeId => $rows ]);
    }

    public function run()
    {
        $this->logger->info("Gearman worker is running...");
        while ($this->worker->work()) {
            if ($this->worker->returnCode() != GEARMAN_SUCCESS) {
                $this->logger->error("Error: " . $this->worker->returnCode());
                break;
            }
        }
    }
}
