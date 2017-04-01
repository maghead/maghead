<?php

namespace Maghead\Command\ShardCommand;

use Maghead\Command\BaseCommand;
use PDO;
use Exception;

class AllocateCommand extends BaseCommand
{
    public function brief()
    {
        return 'allocate a shard';
    }

    public function execute()
    {
        $config = $this->getConfig();
        $dsId = $this->getCurrentDataSourceId();
        $ds = $config->getDataSource($dsId);
    }
}
