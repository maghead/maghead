<?php

namespace Maghead\Console\Command\ConfigCommand;

use Maghead\Console\Command\BaseCommand;
use Maghead\Manager\DatabaseManager;
use Maghead\Manager\DataSourceManager;

use Maghead\Runtime\Config\MongoConfigWriter;

use SQLBuilder\Driver\PDODriverFactory;
use SQLBuilder\Driver\PDOSQLiteDriver;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Universal\Query\CreateDatabaseQuery;
use PDO;
use Exception;

use MongoDB\Client;

class UploadCommand extends BaseCommand
{
    public function brief()
    {
        return 'upload the current config to the config server';
    }

    public function execute($mongoUrl = null)
    {
        $config = $this->getConfig();

        if ($mongoUrl) {
            $client = new Client($mongoUrl);
        } else if ($mongoUrl = $config->getConfigServerUrl()) {
            $client = new Client($mongoUrl);
        } else {
            $client = new Client;
        }

        $this->logger->info("uploading...");
        $result = MongoConfigWriter::write($client, $config);

        $isAcknowledged = $result->isAcknowledged();
        $this->logger->info("isAcknowledged: $isAcknowledged");
    }
}
