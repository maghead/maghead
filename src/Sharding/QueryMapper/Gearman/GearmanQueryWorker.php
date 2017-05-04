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

use Maghead\Runtime\Config\Config;
use Maghead\Runtime\Config\FileConfigLoader;
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

    protected static function createDefaultLogger()
    {
        $logger = new Logger('query_worker');
        $logger->pushHandler(new StreamHandler('query_worker.log', Logger::INFO));
        $logger->pushHandler(new ErrorLogHandler(), Logger::DEBUG);
        return $logger;
    }

    protected static function createDefaultGearmanWorker()
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

        $shardId = $queryJob->shardId;

        $this->logger->debug("Loading shard {$queryJob->shardId}");
        $shard = $this->shardManager->loadShard($queryJob->shardId);

        $conn = $this->dataSourceManager->getReadConnection($queryJob->shardId);

        $driver = $conn->getQueryDriver();

        $query = $queryJob->query;
        $args = new ArgumentArray;
        $sql = $query->toSql($driver, $args);

        $this->logger->debug("SQL: {$sql}");

        $stm = $conn->prepare($sql);
        $stm->execute($args->toArray());
        $rows = $stm->fetchAll(PDO::FETCH_ASSOC);

        /*
        $job->sendStatus($x, $workloadSize);
        */
        return serialize([ $shardId => $rows ]);
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
