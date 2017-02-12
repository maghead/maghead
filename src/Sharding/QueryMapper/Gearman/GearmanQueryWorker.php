<?php

namespace Maghead\Sharding\QueryMapper\Gearman;

use GearmanWorker;
use GearmanJob;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;

class GearmanQueryWorker
{
    protected $worker;

    protected $logger;

    public function __construct(GearmanWorker $worker = null, Logger $logger = null)
    {
        $this->logger = $logger ?: self::createDefaultLogger();
        $this->worker = $worker ?: self::createDefaultGearmanWorker();
        $this->worker->addFunction("reverse", [$this, 'work']);
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

    public function work($job)
    {
        $this->logger->info("Received job: " . $job->handle());

        $workload = $job->workload();
        $workload_size = $job->workloadSize();

        $this->logger->info("Workload: $workload ($workload_size)");

        # This status loop is not needed, just showing how it works
        for ($x = 0; $x < $workload_size; $x++)
        {
            $this->logger->debug("Sending status: $x/$workload_size complete");
            $job->sendStatus($x, $workload_size);
        }

        $result = strrev($workload);
        $this->logger->debug("Result: $result");

        # Return what we want to send back to the client.
        return $result;
    }

    public function run()
    {
        $this->logger->info("Gearman worker is running...");
        while ($this->worker->work()) {
            if ($this->worker->returnCode() != GEARMAN_SUCCESS) {
                $this->logger->addError("Error: " . $this->worker->returnCode());
                break;
            }
        }
    }
}
