<?php

namespace Maghead\Command\ShardCommand;

use Maghead\Command\BaseCommand;
use PDO;
use Exception;

class PruneCommand extends BaseCommand
{
    public function brief()
    {
        return 'prune a shard';
    }

    public function execute()
    {
        $config = $this->getConfig();
        $dsId = $this->getCurrentDataSourceId();
        $ds = $config->getDataSource($dsId);
    }
}
